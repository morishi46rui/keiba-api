<?php

namespace Tests\Feature\Domains;

use App\Domains\ShutubaExtractorService;
use App\Models\RaceInfo;
use App\Models\Shutuba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShutubaExtractorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShutubaExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShutubaExtractorService;
    }

    // ========================================
    // 正常系: HTMLから出馬表データを抽出してDBに保存
    // ========================================

    public function test_HTMLから出馬表データを抽出してDBに保存する(): void
    {
        // レース情報を作成（ファイル名の末尾2桁 = race_number）
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1, // ファイル名の末尾2桁 "01"
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        $html = $this->buildShutubaHtml([
            [
                'frame' => 1,
                'number' => 1,
                'horseName' => 'テスト馬A',
                'horseId' => '2019104308',
                'sexAge' => '牡3',
                'jockeyWeight' => '57.0',
                'jockeyName' => 'テスト騎手A',
                'jockeyId' => '05339',
                'trainerName' => 'テスト調教師A',
                'trainerId' => '01088',
                'horseWeight' => '480',
                'odds' => '2.5',
                'pop' => '1',
            ],
            [
                'frame' => 2,
                'number' => 2,
                'horseName' => 'テスト馬B',
                'horseId' => '2020105555',
                'sexAge' => '牝4',
                'jockeyWeight' => '55.0',
                'jockeyName' => 'テスト騎手B',
                'jockeyId' => '05340',
                'trainerName' => 'テスト調教師B',
                'trainerId' => '01089',
                'horseWeight' => '450',
                'odds' => '5.0',
                'pop' => '2',
            ],
        ]);

        // ファイル名の末尾2桁が race_number に対応
        $targetFile = sys_get_temp_dir().'/202406010101.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        // 出馬表データが2件保存されていることを確認
        $this->assertDatabaseCount('shutubas', 2);

        $this->assertDatabaseHas('shutubas', [
            'race_info_id' => $raceInfo->id,
            'frame_number' => 1,
            'horse_number' => 1,
            'horse_name' => 'テスト馬A',
            'horse_id' => '2019104308',
            'sex' => '牡',
            'age' => 3,
        ]);

        $this->assertDatabaseHas('shutubas', [
            'race_info_id' => $raceInfo->id,
            'frame_number' => 2,
            'horse_number' => 2,
            'horse_name' => 'テスト馬B',
            'horse_id' => '2020105555',
            'sex' => '牝',
            'age' => 4,
        ]);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 正常系: 既存データがある場合は削除してから再保存
    // ========================================

    public function test_既存出馬表データを削除してから再保存する(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 2,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        // 既存の出馬表データを作成
        Shutuba::create([
            'race_info_id' => $raceInfo->id,
            'frame_number' => 1,
            'horse_number' => 1,
            'horse_name' => '古いデータ',
        ]);

        $html = $this->buildShutubaHtml([
            [
                'frame' => 1,
                'number' => 1,
                'horseName' => '新しいデータ',
                'horseId' => '2019104308',
                'sexAge' => '牡3',
                'jockeyWeight' => '57.0',
                'jockeyName' => '騎手',
                'jockeyId' => '05339',
                'trainerName' => '調教師',
                'trainerId' => '01088',
                'horseWeight' => '480',
                'odds' => '2.5',
                'pop' => '1',
            ],
        ]);

        // ファイル名の末尾2桁 "02" = race_number 2
        $targetFile = sys_get_temp_dir().'/202406010102.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        // 古いデータが削除され、新しいデータのみが保存されている
        $this->assertDatabaseCount('shutubas', 1);
        $this->assertDatabaseHas('shutubas', [
            'horse_name' => '新しいデータ',
        ]);
        $this->assertDatabaseMissing('shutubas', [
            'horse_name' => '古いデータ',
        ]);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 異常系: レース情報が見つからない場合
    // ========================================

    public function test_レース情報が見つからない場合は出馬表データを保存しない(): void
    {
        $html = $this->buildShutubaHtml([
            [
                'frame' => 1,
                'number' => 1,
                'horseName' => 'テスト馬',
                'horseId' => '2019104308',
                'sexAge' => '牡3',
                'jockeyWeight' => '57.0',
                'jockeyName' => '騎手',
                'jockeyId' => '05339',
                'trainerName' => '調教師',
                'trainerId' => '01088',
                'horseWeight' => '480',
                'odds' => '2.5',
                'pop' => '1',
            ],
        ]);

        // race_number 99に対応するRaceInfoがないのでスキップ
        $targetFile = sys_get_temp_dir().'/202406010199.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $this->assertDatabaseCount('shutubas', 0);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 異常系: 出馬表テーブルがないHTML
    // ========================================

    public function test_出馬表テーブルがないHTMLでは何も保存しない(): void
    {
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 3,
            'date' => '2024-01-06',
            'place_detail' => '1回中山1日',
        ]);

        $html = '<html><body><div>テーブルなし</div></body></html>';

        $targetFile = sys_get_temp_dir().'/202406010103.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $this->assertDatabaseCount('shutubas', 0);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 異常系: ファイルが存在しない場合
    // ========================================

    public function test_存在しないファイルを指定してもエラーにならない(): void
    {
        $this->service->extractFromHtml('/tmp/nonexistent_shutuba_file.html');

        $this->assertDatabaseCount('shutubas', 0);
    }

    /**
     * テスト用の出馬表HTMLを構築するヘルパー
     */
    private function buildShutubaHtml(array $entries): string
    {
        $rows = '';
        foreach ($entries as $entry) {
            $rows .= <<<HTML
            <tr>
                <td>{$entry['frame']}</td>
                <td>{$entry['number']}</td>
                <td></td>
                <td><a href="/horse/{$entry['horseId']}/">{$entry['horseName']}</a></td>
                <td>{$entry['sexAge']}</td>
                <td>{$entry['jockeyWeight']}</td>
                <td><a href="/jockey/{$entry['jockeyId']}/">{$entry['jockeyName']}</a></td>
                <td><a href="/trainer/{$entry['trainerId']}/">{$entry['trainerName']}</a></td>
                <td>{$entry['horseWeight']}</td>
                <td>{$entry['odds']}</td>
                <td>{$entry['pop']}</td>
            </tr>
            HTML;
        }

        return <<<HTML
        <html>
        <body>
            <table class="Shutuba_Table">
                <tr><th>枠</th><th>馬番</th><th></th><th>馬名</th><th>性齢</th><th>斤量</th><th>騎手</th><th>調教師</th><th>馬体重</th><th>オッズ</th><th>人気</th></tr>
                {$rows}
            </table>
        </body>
        </html>
        HTML;
    }
}
