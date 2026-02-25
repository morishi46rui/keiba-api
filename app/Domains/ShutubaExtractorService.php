<?php

namespace App\Domains;

use App\Models\RaceInfo;
use App\Models\Shutuba;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * HTMLから出馬表情報を抽出してDBに保存するサービスクラス
 */
class ShutubaExtractorService
{
    /**
     * HTMLをパースしてDOMXPathオブジェクトを返す
     */
    private function parseHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        // エラーを抑制してHTMLをロード
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        return new DOMXPath($dom);
    }

    /**
     * 出馬表データを抽出
     *
     * @return array<array{
     *     frame_number: int,
     *     horse_number: int,
     *     horse_name: string,
     *     horse_id: string,
     *     sex: string,
     *     age: int,
     *     jockey_weight: string,
     *     jockey_name: string,
     *     jockey_id: string,
     *     trainer_name: string,
     *     trainer_id: string,
     *     horse_weight: string,
     *     odds: string,
     *     pop: string
     * }>
     */
    private function extractShutubaData(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $results = [];

        // 出馬表テーブルを取得
        $resultTables = $xpath->query("//table[contains(@class, 'Shutuba_Table') or contains(@class, 'ShutubaTable') or contains(@id, 'shutuba_table') or contains(@class, 'race_table_01')]");

        if ($resultTables->length === 0) {
            return [];
        }

        $table = $resultTables->item(0);
        $rows = $xpath->query('.//tr', $table);

        // ヘッダー行をスキップして処理
        foreach ($rows as $index => $row) {
            if ($index === 0) {
                continue; // ヘッダー行スキップ
            }

            $cells = $xpath->query('.//td', $row);

            if ($cells->length < 4) {
                continue;
            }

            try {
                // 各セルからデータを抽出
                $frameNumber = (int) trim($cells->item(0)->textContent);
                $horseNumber = (int) trim($cells->item(1)->textContent);
                $horseName = trim($cells->item(3)->textContent);

                // 性別と年齢を分離
                $sexAndAge = trim($cells->item(4)->textContent ?? '');
                $sex = mb_substr($sexAndAge, 0, 1);
                $age = (int) mb_substr($sexAndAge, 1);

                $jockeyWeight = $cells->length > 5 ? trim($cells->item(5)->textContent) : '';
                $jockeyName = $cells->length > 6 ? trim($cells->item(6)->textContent) : '';

                $trainerName = $cells->length > 7 ? trim($cells->item(7)->textContent) : '';

                $horseWeight = $cells->length > 8 ? trim($cells->item(8)->textContent) : '';

                // オッズと人気
                $odds = '';
                $pop = '';
                if ($cells->length > 9) {
                    $odds = trim($cells->item(9)->textContent);
                }
                if ($cells->length > 10) {
                    $pop = trim($cells->item(10)->textContent);
                }

                // 各種IDを抽出
                $horseId = $this->extractIdFromHtml(
                    $cells->item(3)->ownerDocument->saveHTML($cells->item(3)),
                    '/horse/([^/"]+)/'
                );

                $jockeyId = '';
                if ($cells->length > 6) {
                    $jockeyId = $this->extractIdFromHtml(
                        $cells->item(6)->ownerDocument->saveHTML($cells->item(6)),
                        '/jockey/([^/"]+)/'
                    );
                }

                $trainerId = '';
                if ($cells->length > 7) {
                    $trainerId = $this->extractIdFromHtml(
                        $cells->item(7)->ownerDocument->saveHTML($cells->item(7)),
                        '/trainer/([^/"]+)/'
                    );
                }

                $results[] = [
                    'frame_number' => $frameNumber,
                    'horse_number' => $horseNumber,
                    'horse_name' => $horseName,
                    'horse_id' => $horseId,
                    'sex' => $sex,
                    'age' => $age ?: null,
                    'jockey_weight' => $jockeyWeight ?: null,
                    'jockey_name' => $jockeyName ?: null,
                    'jockey_id' => $jockeyId ?: null,
                    'trainer_name' => $trainerName ?: null,
                    'trainer_id' => $trainerId ?: null,
                    'horse_weight' => $horseWeight ?: null,
                    'odds' => $odds ?: null,
                    'pop' => $pop ?: null,
                ];
            } catch (Exception $e) {
                echo "行の処理でエラー: {$e->getMessage()}\n";

                continue;
            }
        }

        return $results;
    }

    /**
     * HTMLからIDを抽出
     */
    private function extractIdFromHtml(string $html, string $pattern): string
    {
        $delimitedPattern = '#'.$pattern.'#';
        if (preg_match($delimitedPattern, $html, $match)) {
            return $match[1];
        }

        return '';
    }

    /**
     * HTMLファイルを処理して出馬表データをDBに保存
     */
    public function extractFromHtml(string $filePath): void
    {
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        echo "処理中: {$fileName}\n";

        try {
            $html = file_get_contents($filePath);

            if ($html === false) {
                throw new Exception("ファイルの読み込みに失敗しました: {$filePath}");
            }

            // レースIDからレース情報を検索
            $raceInfo = RaceInfo::where('race_number', (int) substr($fileName, -2))
                ->first();

            if (! $raceInfo) {
                echo "レース情報が見つかりません: {$fileName}\n";

                return;
            }

            // 既存の出馬表データを削除（重複を防止）
            Shutuba::where('race_info_id', $raceInfo->id)->delete();

            // 出馬表データを抽出して保存
            $shutubaData = $this->extractShutubaData($html);

            foreach ($shutubaData as $data) {
                $data['race_info_id'] = $raceInfo->id;
                Shutuba::create($data);
            }

            echo "完了: {$fileName} (出馬表: ".count($shutubaData)."件)\n";
        } catch (Exception $e) {
            echo "ファイル処理エラー {$fileName}: {$e->getMessage()}\n";
        }
    }

    /**
     * HTMLファイルを再帰的に検索
     *
     * @return array<string>
     */
    private function findHtmlFiles(string $directory): array
    {
        $htmlFiles = [];

        if (! is_dir($directory)) {
            return $htmlFiles;
        }

        $items = scandir($directory);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                $htmlFiles = array_merge($htmlFiles, $this->findHtmlFiles($path));
            } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'html') {
                $htmlFiles[] = $path;
            }
        }

        return $htmlFiles;
    }

    /**
     * 指定されたディレクトリ内の全HTMLファイルを処理
     */
    public function extract(?string $year = null, ?string $month = null): void
    {
        $baseFolder = storage_path('app/html/shutuba_html');

        if (! is_dir($baseFolder)) {
            echo "HTMLフォルダが見つかりません: {$baseFolder}\n";

            return;
        }

        // 年・月フィルタを適用
        if ($year !== null && $month !== null) {
            $targetFolder = $baseFolder.DIRECTORY_SEPARATOR.$year.DIRECTORY_SEPARATOR.$month;
        } elseif ($year !== null) {
            $targetFolder = $baseFolder.DIRECTORY_SEPARATOR.$year;
        } else {
            $targetFolder = $baseFolder;
        }

        if (! is_dir($targetFolder)) {
            echo "指定されたフォルダが見つかりません: {$targetFolder}\n";

            return;
        }

        $htmlFiles = $this->findHtmlFiles($targetFolder);

        $filterMessage = match (true) {
            $year !== null && $month !== null => "{$year}年{$month}月",
            $year !== null => "{$year}年",
            default => '全期間',
        };

        echo "対象: {$filterMessage}\n";
        echo count($htmlFiles)."個のHTMLファイルを処理します\n";

        foreach ($htmlFiles as $file) {
            $this->extractFromHtml($file);
        }

        echo "\n処理完了！\n";
    }
}
