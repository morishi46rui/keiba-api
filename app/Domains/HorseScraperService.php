<?php

namespace App\Domains;

use App\Models\RaceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ネット競馬から馬情報をスクレイピングするサービス
 */
class HorseScraperService
{
    /**
     * 全ての馬HTMLをスクレイピング
     */
    public function scrapeAll(): void
    {
        $horseIds = $this->collectHorseIds();
        $this->scrapeByHorseIds($horseIds);
    }

    /**
     * 指定した馬IDのHTMLをスクレイピング
     *
     * @param  array<string>  $horseIds
     */
    public function scrapeByHorseIds(array $horseIds): void
    {
        $totalCount = count($horseIds);
        $downloaded = 0;
        $skipped = 0;

        Log::info("馬情報スクレイピング開始: 合計{$totalCount}頭");

        foreach ($horseIds as $index => $horseId) {
            $filePath = "html/horse_html/{$horseId}.html";

            // 既にファイルが存在する場合はスキップ
            if (Storage::disk('local')->exists($filePath)) {
                Log::info(($index + 1).": スキップ (既に存在) {$horseId}");
                $skipped++;

                continue;
            }

            $url = "https://db.netkeiba.com/horse/{$horseId}/";

            Log::info(($index + 1).": ダウンロード中 {$url}");

            try {
                $html = $this->fetchHtml($url);

                // ディレクトリが存在しない場合は作成
                Storage::disk('local')->makeDirectory('html/horse_html');

                // HTMLを保存
                Storage::disk('local')->put($filePath, $html);

                $downloaded++;

                // サーバーに負荷をかけないよう待機
                sleep(3);
            } catch (\Exception $e) {
                Log::error("ダウンロードエラー {$url}: ".$e->getMessage());
            }
        }

        Log::info("\n=== 馬情報スクレイピング完了 ===");
        Log::info("合計: {$totalCount}");
        Log::info("ダウンロード: {$downloaded}");
        Log::info("スキップ: {$skipped}");
    }

    /**
     * race_resultsテーブルからユニークな馬IDを収集
     *
     * @return array<string>
     */
    private function collectHorseIds(): array
    {
        return RaceResult::whereNotNull('horse_id')
            ->where('horse_id', '!=', '')
            ->distinct()
            ->pluck('horse_id')
            ->toArray();
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
