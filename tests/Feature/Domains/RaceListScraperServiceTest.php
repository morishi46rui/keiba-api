<?php

namespace Tests\Feature\Domains;

use App\Domains\RaceListScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * RaceListScraperServiceのテスト
 *
 * 注意: サービス内でPHP標準のsleep(3)を使用しているため、
 * HTTPリクエストを伴うテストケースは実行に数秒かかります。
 * テスト高速化のためには、サービス側でIlluminate\Support\Sleepの使用を推奨します。
 */
class RaceListScraperServiceTest extends TestCase
{
    use RefreshDatabase;

    private RaceListScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RaceListScraperService;
        Storage::fake('local');
    }

    // ========================================
    // AC3: 既存race_url.txtがある月はスキップ
    // ========================================

    public function test_既存race_url_txtがある月はスキップする(): void
    {
        Storage::disk('local')->put('url/race/2024/01/race_url.txt', "dummy\n");

        Http::fake();

        $this->service->scrapeYearMonth('2024', '01');

        // HTTPリクエストが発行されていないことを確認（sleep も呼ばれない）
        Http::assertNothingSent();

        // ファイル内容が変更されていないことを確認
        $this->assertEquals("dummy\n", Storage::disk('local')->get('url/race/2024/01/race_url.txt'));
    }

    // ========================================
    // 正常系: 年指定で12ヶ月分スキップ確認
    // ========================================

    public function test_年指定で全月にrace_url_txtが存在する場合は全てスキップする(): void
    {
        Http::fake();

        for ($m = 1; $m <= 12; $m++) {
            $month = sprintf('%02d', $m);
            Storage::disk('local')->put("url/race/2024/{$month}/race_url.txt", "dummy\n");
        }

        $this->service->scrapeYear('2024');

        Http::assertNothingSent();
    }

    // ========================================
    // 異常系: 開催日がない場合
    // ========================================

    public function test_開催日がない月はrace_url_txtを作成しない(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><body>開催日なし</body></html>',
                200
            ),
        ]);

        $this->service->scrapeYearMonth('2024', '02');

        Storage::disk('local')->assertMissing('url/race/2024/02/race_url.txt');
    }

    // ========================================
    // 異常系: HTTPエラー時の処理
    // ========================================

    public function test_カレンダーページのHTTPエラー時にrace_url_txtを作成しない(): void
    {
        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->service->scrapeYearMonth('2024', '03');

        Storage::disk('local')->assertMissing('url/race/2024/03/race_url.txt');
    }
}
