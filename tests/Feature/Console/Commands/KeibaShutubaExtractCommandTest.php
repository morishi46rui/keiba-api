<?php

namespace Tests\Feature\Console\Commands;

use App\Domains\ShutubaExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeibaShutubaExtractCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系: extract（デフォルト）アクション
    // ========================================

    public function test_extractで全出馬表データの抽出が実行される(): void
    {
        $mock = Mockery::mock(ShutubaExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->with();

        $this->app->instance(ShutubaExtractorService::class, $mock);

        $this->artisan('keiba:shutuba-extract')
            ->assertExitCode(0)
            ->expectsOutputToContain('全ての出馬表HTMLからデータを抽出します')
            ->expectsOutputToContain('抽出が完了しました');
    }

    // ========================================
    // 正常系: extract:YYYY アクション
    // ========================================

    public function test_extract_YYYY形式で年単位の抽出が実行される(): void
    {
        $mock = Mockery::mock(ShutubaExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->with('2024');

        $this->app->instance(ShutubaExtractorService::class, $mock);

        $this->artisan('keiba:shutuba-extract', ['action' => 'extract:2024'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年の出馬表HTMLからデータを抽出します')
            ->expectsOutputToContain('抽出が完了しました');
    }

    // ========================================
    // 正常系: extract:YYYY:MM アクション
    // ========================================

    public function test_extract_YYYY_MM形式で月単位の抽出が実行される(): void
    {
        $mock = Mockery::mock(ShutubaExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->with('2024', '01');

        $this->app->instance(ShutubaExtractorService::class, $mock);

        $this->artisan('keiba:shutuba-extract', ['action' => 'extract:2024:1'])
            ->assertExitCode(0)
            ->expectsOutputToContain('2024年01月の出馬表HTMLからデータを抽出します')
            ->expectsOutputToContain('抽出が完了しました');
    }

    public function test_extract_YYYY_MM形式で月が2桁にフォーマットされる(): void
    {
        $mock = Mockery::mock(ShutubaExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->with('2024', '07');

        $this->app->instance(ShutubaExtractorService::class, $mock);

        $this->artisan('keiba:shutuba-extract', ['action' => 'extract:2024:7'])
            ->assertExitCode(0);
    }

    // ========================================
    // 異常系: 不明なアクション
    // ========================================

    public function test_不明なアクション時にエラーを返す(): void
    {
        $this->artisan('keiba:shutuba-extract', ['action' => 'unknown'])
            ->assertExitCode(1)
            ->expectsOutputToContain('不明なアクション');
    }

    // ========================================
    // 異常系: 無効なフォーマット
    // ========================================

    public function test_extract_の無効なフォーマット時にエラーを返す(): void
    {
        $this->artisan('keiba:shutuba-extract', ['action' => 'extract:2024:01:01'])
            ->assertExitCode(1)
            ->expectsOutputToContain('無効なフォーマット');
    }

    // ========================================
    // 異常系: サービスが例外を投げた場合
    // ========================================

    public function test_サービスが例外を投げた場合にエラーを返す(): void
    {
        $mock = Mockery::mock(ShutubaExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->andThrow(new \Exception('テストエラー'));

        $this->app->instance(ShutubaExtractorService::class, $mock);

        $this->artisan('keiba:shutuba-extract')
            ->assertExitCode(1)
            ->expectsOutputToContain('エラーが発生しました');
    }
}
