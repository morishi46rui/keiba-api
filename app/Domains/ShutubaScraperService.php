<?php

namespace App\Domains;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ネット競馬から出馬表をスクレイピングするサービス
 */
class ShutubaScraperService
{
    /**
     * 全ての出馬表をスクレイピング
     */
    public function scrapeAll(): void
    {
        $this->scrapeWithFilter(null, null);
    }

    /**
     * 特定年の出馬表をスクレイピング
     */
    public function scrapeYear(string $year): void
    {
        $this->scrapeWithFilter($year, null);
    }

    /**
     * 特定年月の出馬表をスクレイピング
     */
    public function scrapeYearMonth(string $year, string $month): void
    {
        $this->scrapeWithFilter($year, $month);
    }

    /**
     * フィルタを適用して出馬表をスクレイピング
     */
    private function scrapeWithFilter(?string $yearFilter, ?string $monthFilter): void
    {
        $urlsWithDate = $this->collectUrls($yearFilter, $monthFilter);

        $totalCount = count($urlsWithDate);
        $downloaded = 0;
        $skipped = 0;

        Log::info("出馬表スクレイピング開始: 合計URL数: {$totalCount}");

        foreach ($urlsWithDate as $index => $urlData) {
            [$url, $year, $month, $day] = $urlData;

            // レースIDを抽出（/race/XXXX/ 形式と ?race_id=XXXX 形式の両方に対応）
            if (! preg_match('/(?:\/race\/(\d+)\/|race_id=(\d+))/', $url, $matches)) {
                Log::warning("レースIDを抽出できませんでした: {$url}");

                continue;
            }
            // マッチしたグループを取得（グループ1またはグループ2）
            $matches[1] = ! empty($matches[1]) ? $matches[1] : $matches[2];

            $raceId = $matches[1];
            $trackCode = KeibaUtil::parseTrackCode($raceId);
            $trackName = KeibaUtil::getTrackName($trackCode);

            // 保存先ディレクトリとファイルパスを構築
            $directory = "html/shutuba_html/{$year}/{$month}/{$day}/{$trackName}";
            $filePath = "{$directory}/{$raceId}.html";

            // 既にファイルが存在する場合はスキップ
            if (Storage::disk('local')->exists($filePath)) {
                Log::info(($index + 1).": スキップ (既に存在) {$raceId}");
                $skipped++;

                continue;
            }

            // 出馬表URLを構築
            $shutubaUrl = "https://race.netkeiba.com/race/shutuba.html?race_id={$raceId}";

            Log::info(($index + 1).": ダウンロード中 {$shutubaUrl}");

            try {
                $html = $this->fetchHtml($shutubaUrl);

                // ディレクトリが存在しない場合は作成
                Storage::disk('local')->makeDirectory($directory);

                // HTMLを保存
                Storage::disk('local')->put($filePath, $html);

                $downloaded++;

                // サーバーに負荷をかけないよう待機
                sleep(3);
            } catch (\Exception $e) {
                Log::error("ダウンロードエラー {$shutubaUrl}: ".$e->getMessage());
            }
        }

        Log::info("\n=== 出馬表スクレイピング完了 ===");
        Log::info("合計: {$totalCount}");
        Log::info("ダウンロード: {$downloaded}");
        Log::info("スキップ: {$skipped}");
    }

    /**
     * URLリストを収集
     *
     * @return array<array{string, string, string, string}>
     */
    private function collectUrls(?string $yearFilter, ?string $monthFilter): array
    {
        $urlsWithDate = [];
        $urlBasePath = storage_path('app/url/race');

        Log::info("URLベースパス: {$urlBasePath}");
        Log::info('年フィルタ: '.($yearFilter ?? 'なし').', 月フィルタ: '.($monthFilter ?? 'なし'));

        if (! is_dir($urlBasePath)) {
            Log::warning("URLディレクトリが見つかりません: {$urlBasePath}");
            echo "URLディレクトリが見つかりません: {$urlBasePath}\n";

            return [];
        }

        // 年ディレクトリを走査
        $yearDirs = scandir($urlBasePath);

        foreach ($yearDirs as $yearDir) {
            if ($yearDir === '.' || $yearDir === '..') {
                continue;
            }

            $yearPath = "{$urlBasePath}/{$yearDir}";
            if (! is_dir($yearPath)) {
                continue;
            }

            // 年フィルタをチェック
            if ($yearFilter !== null && $yearDir !== $yearFilter) {
                continue;
            }

            // 月ディレクトリを走査
            $monthDirs = scandir($yearPath);
            foreach ($monthDirs as $monthDir) {
                if ($monthDir === '.' || $monthDir === '..') {
                    continue;
                }

                $monthPath = "{$yearPath}/{$monthDir}";
                if (! is_dir($monthPath)) {
                    continue;
                }

                // 月フィルタをチェック
                if ($monthFilter !== null && $monthDir !== $monthFilter) {
                    continue;
                }

                // race_url.txtを読み込み
                $raceUrlFile = "{$monthPath}/race_url.txt";
                if (! file_exists($raceUrlFile)) {
                    continue;
                }

                $lines = file($raceUrlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (str_contains($line, '|')) {
                        // 新形式: URL|YYYYMMDD
                        [$url, $raceDate] = explode('|', $line, 2);
                        $day = substr($raceDate, 6, 2);
                    } else {
                        // 旧形式: URLのみ
                        $url = $line;
                        $day = 'unknown';
                    }

                    $urlsWithDate[] = [$url, $yearDir, $monthDir, $day];
                }
            }
        }

        return $urlsWithDate;
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
}
