<?php

namespace App\Domains;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ネット競馬のレース一覧ページからレースURLを収集するサービス
 */
class RaceListScraperService
{
    /**
     * 特定年の全月のレースURLを収集
     */
    public function scrapeYear(string $year): void
    {
        for ($m = 1; $m <= 12; $m++) {
            $month = sprintf('%02d', $m);
            $this->scrapeYearMonth($year, $month);
        }
    }

    /**
     * 特定年月のレースURLを収集
     */
    public function scrapeYearMonth(string $year, string $month): void
    {
        $filePath = "url/race/{$year}/{$month}/race_url.txt";

        // 既存race_url.txtがあればスキップ
        if (Storage::disk('local')->exists($filePath)) {
            Log::info("スキップ (既に存在): {$filePath}");

            return;
        }

        Log::info("{$year}年{$month}月のレースURL収集を開始します");

        try {
            // カレンダーページから開催日を取得
            $dates = $this->fetchRaceDates($year, $month);

            if (empty($dates)) {
                Log::info("{$year}年{$month}月の開催日が見つかりませんでした");

                return;
            }

            Log::info('開催日数: '.count($dates));

            // 各開催日のレースURLを収集
            $allUrls = [];
            foreach ($dates as $date) {
                Log::info("開催日 {$date} のレースURLを取得中...");

                try {
                    $urls = $this->fetchRaceUrls($date);
                    foreach ($urls as $url) {
                        $allUrls[] = $url.'|'.$date;
                    }

                    Log::info("開催日 {$date}: ".count($urls).'件のレースURLを取得');
                } catch (\RuntimeException $e) {
                    Log::error("開催日 {$date} のレースURL取得でエラー: ".$e->getMessage());
                }
            }

            if (empty($allUrls)) {
                Log::info("{$year}年{$month}月のレースURLが見つかりませんでした");

                return;
            }

            // race_url.txtに保存
            $this->saveUrls($year, $month, $allUrls);

            Log::info("{$year}年{$month}月: ".count($allUrls).'件のレースURLを保存しました');

        } catch (\RuntimeException $e) {
            Log::error("{$year}年{$month}月のレースURL収集でエラー: ".$e->getMessage());
        }
    }

    /**
     * カレンダーページから開催日リストを取得
     *
     * @return array<string> YYYYMMDD形式の日付リスト
     */
    private function fetchRaceDates(string $year, string $month): array
    {
        $url = "https://db.netkeiba.com/race/list/{$year}{$month}01/";

        $html = $this->fetchHtml($url);

        // サーバーに負荷をかけないよう待機
        sleep(3);

        $dates = [];

        // <a href="/race/list/YYYYMMDD/"> 形式のリンクを抽出
        if (preg_match_all('/\/race\/list\/(\d{8})\//', $html, $matches)) {
            $dates = array_unique($matches[1]);
            sort($dates);
        }

        return array_values($dates);
    }

    /**
     * 特定日のレースURLリストを取得
     *
     * @return array<string> レースURLリスト
     */
    private function fetchRaceUrls(string $date): array
    {
        $url = "https://db.netkeiba.com/race/list/{$date}/";

        $html = $this->fetchHtml($url);

        // サーバーに負荷をかけないよう待機
        sleep(3);

        $urls = [];

        // <a href="/race/XXXXXXXXXXXX/"> 形式のリンクを抽出
        if (preg_match_all('/\/race\/(\d{12})\//', $html, $matches)) {
            $raceIds = array_unique($matches[1]);
            foreach ($raceIds as $raceId) {
                $urls[] = "https://db.netkeiba.com/race/{$raceId}/";
            }
        }

        return $urls;
    }

    /**
     * HTMLを取得
     *
     * @throws \RuntimeException HTTPリクエストが失敗した場合
     */
    private function fetchHtml(string $url): string
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTPリクエストが失敗しました: '.$response->status());
        }

        return $response->body();
    }

    /**
     * 収集したURLをrace_url.txtに保存
     *
     * @param  array<string>  $urls  URL|YYYYMMDD形式の行リスト
     */
    private function saveUrls(string $year, string $month, array $urls): void
    {
        $directory = "url/race/{$year}/{$month}";
        $filePath = "{$directory}/race_url.txt";

        // ディレクトリが存在しない場合は作成
        Storage::disk('local')->makeDirectory($directory);

        // URLリストを保存
        $content = implode("\n", $urls)."\n";
        Storage::disk('local')->put($filePath, $content);
    }
}
