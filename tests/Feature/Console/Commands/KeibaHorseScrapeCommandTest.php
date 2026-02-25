<?php

namespace Tests\Feature\Console\Commands;

use App\Domains\HorseScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeibaHorseScrapeCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系: scrapehtml アクション
    // ========================================

    public function test_scrapehtmlで全馬HTMLのスクレイピングが実行される(): void
    {
        $mock = Mockery::mock(HorseScraperService::class);
        $mock->shouldReceive('scrapeAll')
            ->once();

        $this->app->instance(HorseScraperService::class, $mock);

        $this->artisan('keiba:horse-scrape', ['action' => 'scrapehtml'])
            ->assertExitCode(0)
            ->expectsOutputToContain('全ての馬HTMLをスクレイピングします')
            ->expectsOutputToContain('スクレイピングが完了しました');
    }

    // ========================================
    // 正常系: scrapehtml:HORSE_ID アクション
    // ========================================

    public function test_scrapehtml_HORSE_IDで特定馬のスクレイピングが実行される(): void
    {
        $mock = Mockery::mock(HorseScraperService::class);
        $mock->shouldReceive('scrapeByHorseIds')
            ->once()
            ->with(['2019104308']);

        $this->app->instance(HorseScraperService::class, $mock);

        $this->artisan('keiba:horse-scrape', ['action' => 'scrapehtml:2019104308'])
            ->assertExitCode(0)
            ->expectsOutputToContain('馬 2019104308 のHTMLをスクレイピングします')
            ->expectsOutputToContain('スクレイピングが完了しました');
    }

    // ========================================
    // 異常系: 不明なアクション
    // ========================================

    public function test_不明なアクション時にエラーを返す(): void
    {
        $this->artisan('keiba:horse-scrape', ['action' => 'unknown'])
            ->assertExitCode(1)
            ->expectsOutputToContain('不明なアクション');
    }

    // ========================================
    // 異常系: scrapehtml: で馬IDが空
    // ========================================

    public function test_scrapehtml_で馬IDが空の場合にエラーを返す(): void
    {
        $this->artisan('keiba:horse-scrape', ['action' => 'scrapehtml:'])
            ->assertExitCode(1)
            ->expectsOutputToContain('馬IDを指定してください');
    }

    // ========================================
    // 異常系: サービスが例外を投げた場合
    // ========================================

    public function test_サービスが例外を投げた場合にエラーを返す(): void
    {
        $mock = Mockery::mock(HorseScraperService::class);
        $mock->shouldReceive('scrapeAll')
            ->once()
            ->andThrow(new \Exception('テストエラー'));

        $this->app->instance(HorseScraperService::class, $mock);

        $this->artisan('keiba:horse-scrape', ['action' => 'scrapehtml'])
            ->assertExitCode(1)
            ->expectsOutputToContain('エラーが発生しました');
    }
}
