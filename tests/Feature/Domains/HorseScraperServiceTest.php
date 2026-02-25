<?php

namespace Tests\Feature\Domains;

use App\Domains\HorseScraperService;
use App\Models\RaceInfo;
use App\Models\RaceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HorseScraperServiceTest extends TestCase
{
    use RefreshDatabase;

    private HorseScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HorseScraperService;
        Storage::fake('local');
    }

    // ========================================
    // 正常系: 既存HTMLがある馬はスキップ
    // ========================================

    public function test_既存HTMLファイルがある馬はスキップする(): void
    {
        Storage::disk('local')->put('html/horse_html/2019104308.html', '<html>dummy</html>');

        Http::fake();

        $this->service->scrapeByHorseIds(['2019104308']);

        Http::assertNothingSent();

        // ファイル内容が変更されていないことを確認
        $this->assertEquals(
            '<html>dummy</html>',
            Storage::disk('local')->get('html/horse_html/2019104308.html')
        );
    }

    // ========================================
    // 正常系: 新規馬HTMLをダウンロードして保存
    // ========================================

    public function test_新規馬HTMLをダウンロードして保存する(): void
    {
        Http::fake([
            'https://db.netkeiba.com/horse/2019104308/' => Http::response('<html>horse page</html>', 200),
        ]);

        $this->service->scrapeByHorseIds(['2019104308']);

        Http::assertSentCount(1);

        Storage::disk('local')->assertExists('html/horse_html/2019104308.html');
        $this->assertEquals(
            '<html>horse page</html>',
            Storage::disk('local')->get('html/horse_html/2019104308.html')
        );
    }

    // ========================================
    // 正常系: 複数馬IDを処理
    // ========================================

    public function test_複数馬IDを処理して既存はスキップし新規はダウンロードする(): void
    {
        Storage::disk('local')->put('html/horse_html/2019104308.html', '<html>existing</html>');

        Http::fake([
            'https://db.netkeiba.com/horse/2020105555/' => Http::response('<html>new horse</html>', 200),
        ]);

        $this->service->scrapeByHorseIds(['2019104308', '2020105555']);

        // 既存の馬はHTTPリクエストしない
        Http::assertSentCount(1);

        // 新規の馬のみ保存
        Storage::disk('local')->assertExists('html/horse_html/2020105555.html');
        $this->assertEquals(
            '<html>new horse</html>',
            Storage::disk('local')->get('html/horse_html/2020105555.html')
        );
    }

    // ========================================
    // 異常系: HTTPエラー時にファイルを作成しない
    // ========================================

    public function test_HTTPエラー時にファイルを作成しない(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->service->scrapeByHorseIds(['2019104308']);

        Storage::disk('local')->assertMissing('html/horse_html/2019104308.html');
    }

    // ========================================
    // 正常系: scrapeAllでrace_resultsからhorse_idを収集
    // ========================================

    public function test_scrapeAllがrace_resultsからhorse_idを収集してスクレイピングする(): void
    {
        // テスト用のRaceInfoとRaceResultを作成
        $raceInfo = RaceInfo::create([
            'race_name' => 'テストレース',
            'race_number' => 1,
            'date' => '2024-01-01',
            'place_detail' => '1回中山1日',
        ]);

        RaceResult::create([
            'race_info_id' => $raceInfo->id,
            'order_of_finish' => 1,
            'frame_number' => 1,
            'horse_number' => 1,
            'horse_name' => 'テスト馬',
            'sex' => '牡',
            'age' => 3,
            'jockey_weight' => '57.0',
            'jockey_name' => 'テスト騎手',
            'time' => '1:35.0',
            'margin' => '',
            'pop' => '1',
            'odds' => '2.5',
            'last_3F' => '34.5',
            'pass' => '1-1-1-1',
            'horse_weight' => '480',
            'stable' => '美',
            'horse_id' => '2019104308',
            'jockey_id' => '05339',
            'trainer_id' => '01088',
            'owner_id' => '040568',
        ]);

        Http::fake([
            'https://db.netkeiba.com/horse/2019104308/' => Http::response('<html>horse</html>', 200),
        ]);

        $this->service->scrapeAll();

        Http::assertSentCount(1);
        Storage::disk('local')->assertExists('html/horse_html/2019104308.html');
    }

    // ========================================
    // 境界値: 空の馬IDリスト
    // ========================================

    public function test_空の馬IDリストでは何もしない(): void
    {
        Http::fake();

        $this->service->scrapeByHorseIds([]);

        Http::assertNothingSent();
    }
}
