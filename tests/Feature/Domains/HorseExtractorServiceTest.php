<?php

namespace Tests\Feature\Domains;

use App\Domains\HorseExtractorService;
use App\Models\Horse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HorseExtractorServiceTest extends TestCase
{
    use RefreshDatabase;

    private HorseExtractorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HorseExtractorService;
    }

    // ========================================
    // 正常系: HTMLから血統情報を抽出してDBに保存
    // ========================================

    public function test_HTMLから血統情報を抽出して既存の馬レコードを更新する(): void
    {
        // 事前に馬レコードを作成
        Horse::create([
            'netkeiba_id' => '2019104308',
            'name' => 'テスト馬',
        ]);

        // テスト用HTMLファイルを作成
        $html = $this->buildHorseHtml(
            sex: '牡',
            coatColor: '栗毛',
            birthYear: '2019',
            sire: 'ディープインパクト',
            dam: 'テスト母馬',
            sireSire: 'サンデーサイレンス',
            sireOfDam: 'テスト母父',
            damDam: 'テスト母母',
            trainerId: '01088',
            ownerId: '040568',
            breederId: '352527'
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'horse_test_');
        rename($tmpFile, $tmpFile.'.html');
        $tmpFile = $tmpFile.'.html';

        // ファイル名をhorse_idに合わせてリネーム
        $targetFile = dirname($tmpFile).'/2019104308.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $horse = Horse::where('netkeiba_id', '2019104308')->first();
        $this->assertNotNull($horse);
        $this->assertEquals('牡', $horse->sex);
        $this->assertEquals('栗毛', $horse->coat_color);
        $this->assertEquals(2019, $horse->birth_year);
        $this->assertEquals('ディープインパクト', $horse->sire);
        $this->assertEquals('テスト母馬', $horse->dam);
        $this->assertEquals('テスト母父', $horse->sire_of_dam);
        $this->assertEquals('サンデーサイレンス', $horse->sire_sire);
        $this->assertEquals('テスト母母', $horse->dam_dam);
        $this->assertEquals('01088', $horse->trainer_id);
        $this->assertEquals('040568', $horse->owner_id);
        $this->assertEquals('352527', $horse->breeder_id);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 正常系: 馬レコードが存在しない場合は新規作成
    // ========================================

    public function test_馬レコードが存在しない場合は新規作成して血統情報を保存する(): void
    {
        $html = $this->buildHorseHtml(
            sex: '牝',
            coatColor: '鹿毛',
            birthYear: '2020',
            sire: 'キングカメハメハ',
            dam: 'テスト母馬2',
        );

        $targetFile = sys_get_temp_dir().'/9999999999.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $horse = Horse::where('netkeiba_id', '9999999999')->first();
        $this->assertNotNull($horse);
        $this->assertEquals('牝', $horse->sex);
        $this->assertEquals('鹿毛', $horse->coat_color);
        $this->assertEquals(2020, $horse->birth_year);
        $this->assertEquals('キングカメハメハ', $horse->sire);
        $this->assertEquals('テスト母馬2', $horse->dam);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 正常系: 性別「セ」(セン馬)の抽出
    // ========================================

    public function test_セン馬の性別を正しく抽出する(): void
    {
        $html = $this->buildHorseHtml(
            sex: 'セ',
            coatColor: '黒鹿毛',
            birthYear: '2018',
        );

        $targetFile = sys_get_temp_dir().'/1111111111.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $horse = Horse::where('netkeiba_id', '1111111111')->first();
        $this->assertNotNull($horse);
        $this->assertEquals('セ', $horse->sex);
        $this->assertEquals('黒鹿毛', $horse->coat_color);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 異常系: プロフィールテーブルがないHTML
    // ========================================

    public function test_プロフィールテーブルがないHTMLでもエラーにならない(): void
    {
        $html = '<html><body><div>馬情報なし</div></body></html>';

        $targetFile = sys_get_temp_dir().'/2222222222.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $horse = Horse::where('netkeiba_id', '2222222222')->first();
        $this->assertNotNull($horse);
        // 血統情報はnullのまま
        $this->assertNull($horse->sex);
        $this->assertNull($horse->sire);
        $this->assertNull($horse->dam);

        // クリーンアップ
        @unlink($targetFile);
    }

    // ========================================
    // 異常系: ファイルが存在しない場合
    // ========================================

    public function test_存在しないファイルを指定してもエラーにならない(): void
    {
        // extractFromHtml内でtry-catchされるので例外は発生しない
        $this->service->extractFromHtml('/tmp/nonexistent_horse_file.html');

        // 馬レコードが作成されていないことを確認
        $this->assertDatabaseCount('horses', 0);
    }

    // ========================================
    // 正常系: 生年月日から生年のみを抽出
    // ========================================

    public function test_生年月日パターンから生年のみを抽出する(): void
    {
        $html = $this->buildHorseHtml(
            birthYear: '2017',
        );

        $targetFile = sys_get_temp_dir().'/3333333333.html';
        file_put_contents($targetFile, $html);

        $this->service->extractFromHtml($targetFile);

        $horse = Horse::where('netkeiba_id', '3333333333')->first();
        $this->assertNotNull($horse);
        $this->assertEquals(2017, $horse->birth_year);

        // クリーンアップ
        @unlink($targetFile);
    }

    /**
     * テスト用の馬HTMLを構築するヘルパー
     */
    private function buildHorseHtml(
        string $sex = '',
        string $coatColor = '',
        string $birthYear = '',
        string $sire = '',
        string $dam = '',
        string $sireSire = '',
        string $sireOfDam = '',
        string $damDam = '',
        string $trainerId = '',
        string $ownerId = '',
        string $breederId = '',
    ): string {
        $sexCoat = '';
        if ($sex) {
            $sexCoat = "{$sex} {$coatColor}";
        }

        $birthDateRow = '';
        if ($birthYear) {
            $birthDateRow = "<tr><th>生年月日</th><td>{$birthYear}年3月15日</td></tr>";
        }

        $trainerRow = '';
        if ($trainerId) {
            $trainerRow = "<tr><th>調教師</th><td><a href=\"/trainer/{$trainerId}/\">テスト調教師</a></td></tr>";
        }

        $ownerRow = '';
        if ($ownerId) {
            $ownerRow = "<tr><th>馬主</th><td><a href=\"/owner/{$ownerId}/\">テスト馬主</a></td></tr>";
        }

        $breederRow = '';
        if ($breederId) {
            $breederRow = "<tr><th>生産者</th><td><a href=\"/breeder/{$breederId}/\">テスト生産者</a></td></tr>";
        }

        $pedigreeTable = '';
        if ($sire || $dam) {
            $pedigreeTable = <<<HTML
            <table class="blood_table" summary="血統">
                <tr>
                    <td class="b_ml" rowspan="4"><a href="/horse/sire01/">{$sire}</a></td>
                    <td class="b_s" rowspan="2"><a href="/horse/siresire01/">{$sireSire}</a></td>
                </tr>
                <tr></tr>
                <tr>
                    <td class="b_s" rowspan="2">父の母</td>
                </tr>
                <tr></tr>
                <tr>
                    <td class="b_ml" rowspan="4"><a href="/horse/dam01/">{$dam}</a></td>
                    <td class="b_s" rowspan="2"><a href="/horse/sireofdam01/">{$sireOfDam}</a></td>
                </tr>
                <tr></tr>
                <tr>
                    <td class="b_s" rowspan="2"><a href="/horse/damdam01/">{$damDam}</a></td>
                </tr>
                <tr></tr>
            </table>
            HTML;
        }

        return <<<HTML
        <html>
        <body>
            <div class="horse_title">
                <p class="txt_01">{$sexCoat}</p>
            </div>
            <table class="db_prof_table" summary="のプロフィール">
                {$birthDateRow}
                {$trainerRow}
                {$ownerRow}
                {$breederRow}
            </table>
            {$pedigreeTable}
        </body>
        </html>
        HTML;
    }
}
