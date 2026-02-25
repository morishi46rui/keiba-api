<?php

namespace App\Domains;

use App\Models\Horse;
use DOMDocument;
use DOMXPath;
use Exception;

/**
 * HTMLから馬の血統情報を抽出してDBに保存するサービスクラス
 */
class HorseExtractorService
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
     * 馬の基本情報と血統情報を抽出
     *
     * @return array{
     *     sex: ?string,
     *     coat_color: ?string,
     *     birth_year: ?int,
     *     sire: ?string,
     *     dam: ?string,
     *     sire_of_dam: ?string,
     *     sire_sire: ?string,
     *     dam_dam: ?string,
     *     trainer_id: ?string,
     *     owner_id: ?string,
     *     breeder_id: ?string
     * }
     */
    private function extractHorseData(string $html): array
    {
        $xpath = $this->parseHtml($html);

        $data = [
            'sex' => null,
            'coat_color' => null,
            'birth_year' => null,
            'sire' => null,
            'dam' => null,
            'sire_of_dam' => null,
            'sire_sire' => null,
            'dam_dam' => null,
            'trainer_id' => null,
            'owner_id' => null,
            'breeder_id' => null,
        ];

        // プロフィールテーブルから基本情報を抽出
        $profileTable = $xpath->query("//table[contains(@class, 'db_prof_table') or contains(@summary, 'のプロフィール')]");

        if ($profileTable->length > 0) {
            $rows = $xpath->query('.//tr', $profileTable->item(0));

            foreach ($rows as $row) {
                $th = $xpath->query('.//th', $row);
                $td = $xpath->query('.//td', $row);

                if ($th->length === 0 || $td->length === 0) {
                    continue;
                }

                $label = trim($th->item(0)->textContent);
                $value = trim($td->item(0)->textContent);
                $tdHtml = $td->item(0)->ownerDocument->saveHTML($td->item(0));

                switch ($label) {
                    case '生年月日':
                        // 生年を抽出
                        if (preg_match('/(\d{4})年/', $value, $match)) {
                            $data['birth_year'] = (int) $match[1];
                        }
                        break;

                    case '調教師':
                        $data['trainer_id'] = $this->extractIdFromHtml($tdHtml, '/trainer/([^/"]+)/');
                        break;

                    case '馬主':
                        $data['owner_id'] = $this->extractIdFromHtml($tdHtml, '/owner/([^/"]+)/');
                        break;

                    case '生産者':
                        $data['breeder_id'] = $this->extractIdFromHtml($tdHtml, '/breeder/([^/"]+)/');
                        break;
                }
            }
        }

        // 馬名タイトル付近から性別・毛色を抽出
        $horseTitle = $xpath->query("//div[contains(@class, 'horse_title')]//p[contains(@class, 'txt_01')]");
        if ($horseTitle->length > 0) {
            $titleText = trim($horseTitle->item(0)->textContent);
            // "牡 栗毛" のような形式を想定
            if (preg_match('/(牡|牝|セ)\s*(.+)/', $titleText, $match)) {
                $data['sex'] = $match[1];
                $data['coat_color'] = trim($match[2]);
            }
        }

        // 血統テーブルから血統情報を抽出
        $pedigreeTable = $xpath->query("//table[contains(@class, 'blood_table') or contains(@summary, '血統')]");

        if ($pedigreeTable->length > 0) {
            $table = $pedigreeTable->item(0);
            $rows = $xpath->query('.//tr', $table);

            // 血統テーブルの構造:
            // 行0: 父の父 | (rowspan) 父の父の父
            // 行1: (空)   | 父の父の母
            // 行2: 父の母 | 父の母の父
            // 行3: (空)   | 父の母の母
            // 行4: 母の父 | 母の父の父
            // 行5: (空)   | 母の父の母
            // 行6: 母の母 | 母の母の父
            // 行7: (空)   | 母の母の母
            // ただし実際の構造はサイトにより異なるため、リンクテキストから取得を試みる

            $allLinks = $xpath->query('.//a', $table);
            $linkTexts = [];
            foreach ($allLinks as $link) {
                $href = $link->getAttribute('href');
                $text = trim($link->textContent);
                if (! empty($text) && str_contains($href, '/horse/')) {
                    $linkTexts[] = $text;
                }
            }

            // 一般的な血統テーブルのリンク順序: 父の父, ..., 父, 父の母, ..., 母の父(母父), ..., 母, 母の母, ...
            // 最も簡単なアプローチ: td要素から直接取得

            // 1列目（大きなセル）から父・母を取得
            $firstColCells = $xpath->query(".//td[contains(@class, 'b_ml')]|.//td[@rowspan='4']", $table);

            if ($firstColCells->length >= 2) {
                $data['sire'] = trim($firstColCells->item(0)->textContent);
                $data['dam'] = trim($firstColCells->item(1)->textContent);
            }

            // 2列目から父の父・父の母・母の父(母父)・母の母を取得
            $secondColCells = $xpath->query(".//td[contains(@class, 'b_s')]|.//td[@rowspan='2']", $table);

            if ($secondColCells->length >= 4) {
                $data['sire_sire'] = trim($secondColCells->item(0)->textContent);
                // secondColCells[1] = 父の母
                $data['sire_of_dam'] = trim($secondColCells->item(2)->textContent); // 母の父 = 母父
                $data['dam_dam'] = trim($secondColCells->item(3)->textContent);
            } elseif ($secondColCells->length >= 2) {
                $data['sire_sire'] = trim($secondColCells->item(0)->textContent);
                $data['dam_dam'] = trim($secondColCells->item(1)->textContent);
            }
        }

        return $data;
    }

    /**
     * HTMLからIDを抽出
     */
    private function extractIdFromHtml(string $html, string $pattern): ?string
    {
        $delimitedPattern = '#'.$pattern.'#';
        if (preg_match($delimitedPattern, $html, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * HTMLファイルを処理して馬情報をDBに更新
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

            // ファイル名 = horse_id
            $horseId = $fileName;

            // 馬レコードを検索
            $horse = Horse::where('netkeiba_id', $horseId)->first();

            if (! $horse) {
                // 馬レコードが存在しない場合は作成
                $horse = Horse::create(['netkeiba_id' => $horseId]);
            }

            // 血統情報を抽出
            $horseData = $this->extractHorseData($html);

            // 馬レコードを更新
            $horse->fill($horseData);
            $horse->save();

            echo "完了: {$fileName}\n";
        } catch (Exception $e) {
            echo "ファイル処理エラー {$fileName}: {$e->getMessage()}\n";
        }
    }

    /**
     * 指定されたディレクトリ内の全HTMLファイルを処理
     */
    public function extract(): void
    {
        $baseFolder = storage_path('app/html/horse_html');

        if (! is_dir($baseFolder)) {
            echo "HTMLフォルダが見つかりません: {$baseFolder}\n";

            return;
        }

        $htmlFiles = $this->findHtmlFiles($baseFolder);

        echo "馬情報抽出開始\n";
        echo count($htmlFiles)."個のHTMLファイルを処理します\n";

        foreach ($htmlFiles as $file) {
            $this->extractFromHtml($file);
        }

        echo "\n処理完了！\n";
    }

    /**
     * HTMLファイルを検索（フラット構造）
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
}
