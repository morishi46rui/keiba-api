<?php

namespace Tests\Feature\Console\Commands;

use App\Domains\ShutubaScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeibaShutubaScraperCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系: scrapehtml アクション
    // ========================================

    public function test_scrapehtmlで全出馬表のスクレイピングが実行される(): void
    {
        $mock = Mockery::mock(ShutubaScraperService::class);
        $mock->shouldReceive('scrapeAll')
            ->once();

        $this->app->instance(ShutubaScraperService::class, $mock);

        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml'])
            ->assertExitCode(0)
            ->expectsOutputToContain('全ての出馬表HTMLをスクレイピングします')
            ->expectsOutputToContain('スクレイピングが完了しました');
    }

    // ========================================
    // 正常系: scrapehtml:YYYY アクション
    // ========================================

    public function test_scrapehtml_YYYY形式で年単位のスクレイピングが実行される(): void
    {
        $mock = Mockery::mock(ShutubaScraperService::class);
        $mock->shouldReceive('scrapeYear')
            ->once()
            ->with('2024');

        $this->app->instance(ShutubaScraperService::class, $mock);

        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml:2024'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年の出馬表HTMLをスクレイピングします')
            ->expectsOutputToContain('スクレイピングが完了しました');
    }

    // ========================================
    // 正常系: scrapehtml:YYYY:MM アクション
    // ========================================

    public function test_scrapehtml_YYYY_MM形式で月単位のスクレイピングが実行される(): void
    {
        $mock = Mockery::mock(ShutubaScraperService::class);
        $mock->shouldReceive('scrapeYearMonth')
            ->once()
            ->with('2024', '01');

        $this->app->instance(ShutubaScraperService::class, $mock);

        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml:2024:1'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年01月の出馬表HTMLをスクレイピングします')
            ->expectsOutputToContain('スクレイピングが完了しました');
    }

    public function test_scrapehtml_YYYY_MM形式で月が2桁にフォーマットされる(): void
    {
        $mock = Mockery::mock(ShutubaScraperService::class);
        $mock->shouldReceive('scrapeYearMonth')
            ->once()
            ->with('2024', '03');

        $this->app->instance(ShutubaScraperService::class, $mock);

        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml:2024:3'])
            ->assertExitCode(0);
    }

    // ========================================
    // 異常系: 不明なアクション
    // ========================================

    public function test_不明なアクション時にエラーを返す(): void
    {
        $this->artisan('keiba:shutuba-scrape', ['action' => 'unknown'])
            ->assertExitCode(1)
            ->expectsOutputToContain('不明なアクション');
    }

    // ========================================
    // 異常系: 無効なフォーマット
    // ========================================

    public function test_scrapehtml_の無効なフォーマット時にエラーを返す(): void
    {
        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml:2024:01:01'])
            ->assertExitCode(1)
            ->expectsOutputToContain('無効なフォーマット');
    }

    // ========================================
    // 異常系: サービスが例外を投げた場合
    // ========================================

    public function test_サービスが例外を投げた場合にエラーを返す(): void
    {
        $mock = Mockery::mock(ShutubaScraperService::class);
        $mock->shouldReceive('scrapeAll')
            ->once()
            ->andThrow(new \Exception('テストエラー'));

        $this->app->instance(ShutubaScraperService::class, $mock);

        $this->artisan('keiba:shutuba-scrape', ['action' => 'scrapehtml'])
            ->assertExitCode(1)
            ->expectsOutputToContain('エラーが発生しました');
    }
}
