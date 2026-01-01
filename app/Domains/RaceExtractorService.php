<?php

namespace App\Domains;

use App\Models\CornerPosition;
use App\Models\LapTime;
use App\Models\Payoff;
use App\Models\RaceInfo;
use App\Models\RaceResult;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * HTMLからレース情報を抽出してDBに保存するサービスクラス
 * Scala版のRowExtractorを移植
 */
class RaceExtractorService
{
    /**
     * HTMLをパースしてDOMXPathオブジェクトを返す
     */
    private function parseHtml(string $html): DOMXPath
    {
        $dom = new DOMDocument();
        // エラーを抑制してHTMLをロード
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        return new DOMXPath($dom);
    }

    /**
     * レース基本情報を抽出
     *
     * @return array{
     *     race_name: string,
     *     surface: string,
     *     distance: int,
     *     weather: string,
     *     surface_state: string,
     *     race_start: string,
     *     race_number: int,
     *     date: string,
     *     place_detail: string,
     *     race_class: string
     * }
     */
    private function extractRaceInfo(string $html, string $fileName): array
    {
        $xpath = $this->parseHtml($html);

        // レース名を取得
        $raceNameNodes = $xpath->query("//div[contains(@class, 'racedata')]//h1");
        $raceName = $raceNameNodes->length > 0 ? trim($raceNameNodes->item(0)->textContent) : '';

        // レースデータを取得
        $raceDataNodes = $xpath->query("//div[contains(@class, 'racedata')]//span");
        $raceDataText = $raceDataNodes->length > 0 ? trim($raceDataNodes->item(0)->textContent) : '';

        // 日付を抽出
        $datePattern = '/(\d{4})年(\d+)月(\d+)日/';
        if (preg_match($datePattern, $html, $dateMatch)) {
            $date = sprintf('%s-%02d-%02d', $dateMatch[1], (int) $dateMatch[2], (int) $dateMatch[3]);
        } else {
            $date = '';
        }

        // 馬場と距離を抽出
        $surfacePattern = '/(芝|ダート|ダ)[^0-9]*(\d+)m/';
        if (preg_match($surfacePattern, $raceDataText, $surfaceMatch)) {
            $surface = $surfaceMatch[1] === 'ダ' ? 'ダート' : $surfaceMatch[1];
            $distance = (int) $surfaceMatch[2];
        } else {
            $surface = '';
            $distance = 0;
        }

        // 天候を抽出
        $weatherPattern = '/天候\s*[：:]\s*([^\s\/]+)/';
        $weather = preg_match($weatherPattern, $raceDataText, $weatherMatch) ? $weatherMatch[1] : '';

        // 馬場状態を抽出
        $surfaceStatePattern = '/芝\s*[：:]\s*([^\s\/]+)|ダート\s*[：:]\s*([^\s\/]+)/';
        if (preg_match($surfaceStatePattern, $raceDataText, $surfaceStateMatch)) {
            $surfaceState = $surfaceStateMatch[1] ?? ($surfaceStateMatch[2] ?? '');
        } else {
            $surfaceState = '';
        }

        // 発走時刻を抽出
        $raceStartPattern = '/発走\s*[：:]\s*(\d+:\d+)/';
        $raceStart = preg_match($raceStartPattern, $raceDataText, $raceStartMatch) ? $raceStartMatch[1] : '';

        // レース番号を抽出（ファイル名の末尾2桁）
        $raceNumber = strlen($fileName) >= 2 ? (int) substr($fileName, -2) : 0;

        // 開催詳細を抽出
        $smalltxtNodes = $xpath->query("//p[contains(@class, 'smalltxt')]");
        $smalltxtText = $smalltxtNodes->length > 0 ? trim($smalltxtNodes->item(0)->textContent) : '';

        $placePattern = '/(\d+)回\s*([^\d]+)\s*(\d+)日目/';
        if (preg_match($placePattern, $smalltxtText, $placeMatch)) {
            $placeDetail = sprintf('%s回%s%s日目', $placeMatch[1], trim($placeMatch[2]), $placeMatch[3]);
        } else {
            $placeDetail = '';
        }

        // レースクラスを抽出
        $classPattern = '/(.+?)(\d+万下|未勝利|新馬|オープン)/';
        if (preg_match($classPattern, $raceName, $classMatch)) {
            $raceClass = trim($classMatch[1]) . $classMatch[2];
        } else {
            $raceClass = $raceName;
        }

        return [
            'race_name' => $raceName ?: null,
            'surface' => $surface ?: null,
            'distance' => $distance ?: null,
            'weather' => $weather ?: null,
            'surface_state' => $surfaceState ?: null,
            'race_start' => $raceStart ?: null,
            'race_number' => $raceNumber,
            'date' => $date ?: null,
            'place_detail' => $placeDetail ?: null,
            'race_class' => $raceClass ?: null,
        ];
    }

    /**
     * レース結果を抽出
     *
     * @return array<array{
     *     order_of_finish: int,
     *     frame_number: int,
     *     horse_number: int,
     *     horse_name: string,
     *     sex: string,
     *     age: int,
     *     jockey_weight: string,
     *     jockey_name: string,
     *     time: string,
     *     margin: string,
     *     pop: string,
     *     odds: string,
     *     last_3F: string,
     *     pass: string,
     *     horse_weight: string,
     *     stable: string,
     *     horse_id: string,
     *     jockey_id: string,
     *     trainer_id: string,
     *     owner_id: string,
     *     position: ?int,
     *     position_label: ?int
     * }>
     */
    private function extractRaceResults(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $results = [];

        // レース結果テーブルを取得
        $resultTables = $xpath->query("//table[contains(@summary, 'レース結果') or contains(@class, 'race_table_01')]");

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

            if ($cells->length < 18) {
                continue;
            }

            try {
                // 各セルからデータを抽出
                $orderOfFinish = (int) trim($cells->item(0)->textContent);
                $frameNumber = (int) trim($cells->item(1)->textContent);
                $horseNumber = (int) trim($cells->item(2)->textContent);
                $horseName = trim($cells->item(3)->textContent);

                // 性別と年齢を分離
                $sexAndAge = trim($cells->item(4)->textContent);
                $sex = mb_substr($sexAndAge, 0, 1);
                $age = (int) mb_substr($sexAndAge, 1);

                $jockeyWeight = trim($cells->item(5)->textContent);
                $jockeyName = trim($cells->item(6)->textContent);
                $time = trim($cells->item(7)->textContent);
                $margin = trim($cells->item(8)->textContent);
                // cells(9) = タイム指数 (プレミアム機能) - スキップ
                $pass = trim($cells->item(10)->textContent);
                $last3F = trim($cells->item(11)->textContent);
                $odds = trim($cells->item(12)->textContent);
                $pop = trim($cells->item(13)->textContent);
                $horseWeight = trim($cells->item(14)->textContent);

                // 厩舎情報を抽出（cells(18)）
                $stable = '';
                if ($cells->length > 18) {
                    $trainerText = trim($cells->item(18)->textContent);
                    if (str_contains($trainerText, '[西]')) {
                        $stable = '西';
                    } elseif (str_contains($trainerText, '[東]')) {
                        $stable = '東';
                    } elseif (str_contains($trainerText, '[地方]')) {
                        $stable = '地方';
                    } elseif (str_contains($trainerText, '[海外]')) {
                        $stable = '海外';
                    }
                }

                // 各種IDを抽出
                $horseId = $this->extractIdFromHtml($cells->item(3)->ownerDocument->saveHTML($cells->item(3)), '/horse/([^/]+)/');
                $jockeyId = $this->extractIdFromHtml($cells->item(6)->ownerDocument->saveHTML($cells->item(6)), '/jockey/result/recent/([^/]+)/');

                $trainerId = '';
                if ($cells->length > 18) {
                    $trainerId = $this->extractIdFromHtml($cells->item(18)->ownerDocument->saveHTML($cells->item(18)), '/trainer/result/recent/([^/]+)/');
                }

                $ownerId = '';
                if ($cells->length > 19) {
                    $ownerId = $this->extractIdFromHtml($cells->item(19)->ownerDocument->saveHTML($cells->item(19)), '/owner/result/recent/([^/]+)/');
                }

                // 着順とラベルを分解
                $positionData = KeibaUtil::parsePosition(trim($cells->item(0)->textContent));

                $results[] = [
                    'order_of_finish' => $orderOfFinish,
                    'frame_number' => $frameNumber,
                    'horse_number' => $horseNumber,
                    'horse_name' => $horseName,
                    'sex' => $sex,
                    'age' => $age,
                    'jockey_weight' => $jockeyWeight,
                    'jockey_name' => $jockeyName,
                    'time' => $time,
                    'margin' => $margin,
                    'pop' => $pop,
                    'odds' => $odds,
                    'last_3F' => $last3F,
                    'pass' => $pass,
                    'horse_weight' => $horseWeight,
                    'stable' => $stable,
                    'horse_id' => $horseId,
                    'jockey_id' => $jockeyId,
                    'trainer_id' => $trainerId,
                    'owner_id' => $ownerId,
                    'position' => $positionData['position'],
                    'position_label' => $positionData['label'],
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
        // パターンにデリミタを追加（#を使用してスラッシュをエスケープ不要に）
        $delimitedPattern = '#' . $pattern . '#';
        if (preg_match($delimitedPattern, $html, $match)) {
            return $match[1];
        }
        return '';
    }

    /**
     * コーナー通過順位を抽出
     *
     * @return array<array{corner_number: int, position_text: string}>
     */
    private function extractCornerPositions(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $positions = [];

        // コーナー通過順位テーブルを取得
        $cornerTables = $xpath->query("//table[contains(@summary, 'コーナー通過順位')]");

        if ($cornerTables->length === 0) {
            return [];
        }

        $table = $cornerTables->item(0);
        $tbody = $xpath->query('.//tbody', $table);

        if ($tbody->length === 0) {
            return [];
        }

        $rows = $xpath->query('.//tr', $tbody->item(0));

        foreach ($rows as $row) {
            $th = $xpath->query('.//th', $row);
            $td = $xpath->query('.//td', $row);

            if ($th->length > 0 && $td->length > 0) {
                $cornerText = trim($th->item(0)->textContent);
                $positionText = trim($td->item(0)->textContent);

                // "3コーナー" から "3" を抽出
                if (preg_match('/(\d+)コーナー/', $cornerText, $match)) {
                    $cornerNumber = (int) $match[1];
                    if (! empty($positionText)) {
                        $positions[] = [
                            'corner_number' => $cornerNumber,
                            'position_text' => $positionText,
                        ];
                    }
                }
            }
        }

        return $positions;
    }

    /**
     * ラップタイムを抽出
     *
     * @return array<array{furlong_no: int, lap_time: string}>
     */
    private function extractLapTimes(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $lapTimes = [];

        // ラップタイムのテーブルを取得
        $tables = $xpath->query("//table[@cellpadding='0']");

        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);

            foreach ($rows as $row) {
                $th = $xpath->query('.//th', $row);

                // "ラップ" を含む行を探す
                if ($th->length > 0 && preg_match('/\s*ラップ\s*/', $th->item(0)->textContent)) {
                    $tds = $xpath->query('.//td', $row);

                    foreach ($tds as $index => $td) {
                        $time = trim($td->textContent);
                        $furlongNo = $index + 1;

                        if (! empty($time) && $time !== '-') {
                            $lapTimes[] = [
                                'furlong_no' => $furlongNo,
                                'lap_time' => $time,
                            ];
                        }
                    }
                }
            }
        }

        return $lapTimes;
    }

    /**
     * 払戻金を抽出
     *
     * @return array<array{ticket_type: int, horse_number: string, payoff: string, favorite_order: string}>
     */
    private function extractPayoffs(string $html): array
    {
        $xpath = $this->parseHtml($html);
        $payoffs = [];

        // 払戻金テーブルを取得
        $payoffTables = $xpath->query("//table[contains(@class, 'pay_table_01') or contains(@summary, '払戻金')]");

        foreach ($payoffTables as $table) {
            $rows = $xpath->query('.//tr', $table);

            foreach ($rows as $row) {
                $th = $xpath->query('.//th', $row);
                $td = $xpath->query('.//td', $row);

                if ($th->length > 0 && $td->length >= 3) {
                    try {
                        $ticketTypeText = trim($th->item(0)->textContent);

                        // 改行や余分な空白を削除して、スペース区切りに統一
                        $horseNumber = preg_replace('/\s+/', ' ', trim($td->item(0)->textContent));
                        $payoff = preg_replace('/\s+/', ' ', trim($td->item(1)->textContent));
                        $favoriteOrder = preg_replace('/\s+/', ' ', trim($td->item(2)->textContent));

                        // 馬券種類を数値に変換
                        $ticketType = KeibaUtil::ticketTypeToInt($ticketTypeText);

                        $payoffs[] = [
                            'ticket_type' => $ticketType,
                            'horse_number' => $horseNumber,
                            'payoff' => $payoff,
                            'favorite_order' => $favoriteOrder,
                        ];
                    } catch (Exception $e) {
                        echo "払戻金の処理でエラー: {$e->getMessage()}\n";
                        continue;
                    }
                }
            }
        }

        return $payoffs;
    }

    /**
     * HTMLファイルを処理してレース情報とレース結果をDBに保存
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

            // レース基本情報を抽出
            $raceInfoData = $this->extractRaceInfo($html, $fileName);

            // RaceInfoを保存または取得
            $raceInfo = RaceInfo::findOrCreateByUniqueKey($raceInfoData);

            // 既存のレース情報を更新
            $raceInfo->fill($raceInfoData);
            $raceInfo->save();

            // 既存のレース結果・コーナー通過順位・ラップタイム・払戻金を削除（重複を防止）
            RaceResult::where('race_info_id', $raceInfo->id)->delete();
            CornerPosition::where('race_info_id', $raceInfo->id)->delete();
            LapTime::where('race_info_id', $raceInfo->id)->delete();
            Payoff::where('race_info_id', $raceInfo->id)->delete();

            // レース結果を抽出して保存
            $raceResults = $this->extractRaceResults($html);

            foreach ($raceResults as $resultData) {
                $resultData['race_info_id'] = $raceInfo->id;
                RaceResult::create($resultData);
            }

            // コーナー通過順位を抽出して保存
            $cornerPositions = $this->extractCornerPositions($html);

            foreach ($cornerPositions as $cornerData) {
                $cornerData['race_info_id'] = $raceInfo->id;
                CornerPosition::create($cornerData);
            }

            // ラップタイムを抽出して保存
            $lapTimes = $this->extractLapTimes($html);

            foreach ($lapTimes as $lapData) {
                $lapData['race_info_id'] = $raceInfo->id;
                LapTime::create($lapData);
            }

            // 払戻金を抽出して保存
            $payoffs = $this->extractPayoffs($html);

            foreach ($payoffs as $payoffData) {
                $payoffData['race_info_id'] = $raceInfo->id;
                Payoff::create($payoffData);
            }

            echo "完了: {$fileName} (レース結果: " . count($raceResults) .
                '件, コーナー: ' . count($cornerPositions) .
                '件, ラップ: ' . count($lapTimes) .
                '件, 払戻: ' . count($payoffs) . "件)\n";
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

            $path = $directory . DIRECTORY_SEPARATOR . $item;

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
        $baseFolder = storage_path('app/race_html');

        if (! is_dir($baseFolder)) {
            echo "HTMLフォルダが見つかりません: {$baseFolder}\n";
            return;
        }

        // 年・月フィルタを適用
        if ($year !== null && $month !== null) {
            $targetFolder = $baseFolder . DIRECTORY_SEPARATOR . $year . DIRECTORY_SEPARATOR . $month;
        } elseif ($year !== null) {
            $targetFolder = $baseFolder . DIRECTORY_SEPARATOR . $year;
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
        echo count($htmlFiles) . "個のHTMLファイルを処理します\n";

        foreach ($htmlFiles as $file) {
            $this->extractFromHtml($file);
        }

        echo "\n処理完了！\n";
    }
}
