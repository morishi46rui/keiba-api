<?php

namespace Tests\Feature\Console\Commands;

use App\Domains\RaceListScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeibaScrapeUrlCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系: scrapeurl:YYYY 形式
    // ========================================

    public function test_scrapeurl_YYYY形式で年単位の収集が実行される(): void
    {
        $mock = Mockery::mock(RaceListScraperService::class);
        $mock->shouldReceive('scrapeYear')
            ->once()
            ->with('2024');

        $this->app->instance(RaceListScraperService::class, $mock);

        $this->artisan('keiba:scrape-url', ['action' => 'scrapeurl:2024'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年のレースURLを収集します')
            ->expectsOutputToContain('レースURL収集が完了しました');
    }

    // ========================================
    // 正常系: scrapeurl:YYYY:MM 形式
    // ========================================

    public function test_scrapeurl_YYYY_MM形式で月単位の収集が実行される(): void
    {
        $mock = Mockery::mock(RaceListScraperService::class);
        $mock->shouldReceive('scrapeYearMonth')
            ->once()
            ->with('2024', '01');

        $this->app->instance(RaceListScraperService::class, $mock);

        $this->artisan('keiba:scrape-url', ['action' => 'scrapeurl:2024:1'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年01月のレースURLを収集します')
            ->expectsOutputToContain('レースURL収集が完了しました');
    }

    public function test_scrapeurl_YYYY_MM形式で月が2桁にフォーマットされる(): void
    {
        $mock = Mockery::mock(RaceListScraperService::class);
        $mock->shouldReceive('scrapeYearMonth')
            ->once()
            ->with('2024', '03');

        $this->app->instance(RaceListScraperService::class, $mock);

        $this->artisan('keiba:scrape-url', ['action' => 'scrapeurl:2024:3'])
            ->assertExitCode(0);
    }

    // ========================================
    // 異常系: 不明なアクション
    // ========================================

    public function test_不明なアクション時にエラーを返す(): void
    {
        $this->artisan('keiba:scrape-url', ['action' => 'unknown'])
            ->assertExitCode(1)
            ->expectsOutputToContain('不明なアクション');
    }

    // ========================================
    // 異常系: 無効なフォーマット
    // ========================================

    public function test_scrapeurl_の無効なフォーマット時にエラーを返す(): void
    {
        $this->artisan('keiba:scrape-url', ['action' => 'scrapeurl:2024:01:01'])
            ->assertExitCode(1)
            ->expectsOutputToContain('無効なフォーマット');
    }

    // ========================================
    // 異常系: サービスが例外を投げた場合
    // ========================================

    public function test_サービスが例外を投げた場合にエラーを返す(): void
    {
        $mock = Mockery::mock(RaceListScraperService::class);
        $mock->shouldReceive('scrapeYear')
            ->once()
            ->andThrow(new \Exception('テストエラー'));

        $this->app->instance(RaceListScraperService::class, $mock);

        $this->artisan('keiba:scrape-url', ['action' => 'scrapeurl:2024'])
            ->assertExitCode(1)
            ->expectsOutputToContain('エラーが発生しました');
    }
}
