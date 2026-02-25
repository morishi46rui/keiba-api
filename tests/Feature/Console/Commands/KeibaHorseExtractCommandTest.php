<?php

namespace Tests\Feature\Console\Commands;

use App\Domains\HorseExtractorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class KeibaHorseExtractCommandTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 正常系: extract（デフォルト）アクション
    // ========================================

    public function test_extractで全馬の血統データ抽出が実行される(): void
    {
        $mock = Mockery::mock(HorseExtractorService::class);
        $mock->shouldReceive('extract')
            ->once();

        $this->app->instance(HorseExtractorService::class, $mock);

        $this->artisan('keiba:horse-extract')
            ->assertExitCode(0)
            ->expectsOutputToContain('全ての馬HTMLから血統データを抽出します')
            ->expectsOutputToContain('抽出が完了しました');
    }

    // ========================================
    // 正常系: 明示的にextractを指定
    // ========================================

    public function test_明示的にextractを指定して実行できる(): void
    {
        $mock = Mockery::mock(HorseExtractorService::class);
        $mock->shouldReceive('extract')
            ->once();

        $this->app->instance(HorseExtractorService::class, $mock);

        $this->artisan('keiba:horse-extract', ['action' => 'extract'])
            ->assertExitCode(0)
            ->expectsOutputToContain('全ての馬HTMLから血統データを抽出します');
    }

    // ========================================
    // 異常系: 不明なアクション
    // ========================================

    public function test_不明なアクション時にエラーを返す(): void
    {
        $this->artisan('keiba:horse-extract', ['action' => 'unknown'])
            ->assertExitCode(1)
            ->expectsOutputToContain('不明なアクション');
    }

    // ========================================
    // 異常系: サービスが例外を投げた場合
    // ========================================

    public function test_サービスが例外を投げた場合にエラーを返す(): void
    {
        $mock = Mockery::mock(HorseExtractorService::class);
        $mock->shouldReceive('extract')
            ->once()
            ->andThrow(new \Exception('テストエラー'));

        $this->app->instance(HorseExtractorService::class, $mock);

        $this->artisan('keiba:horse-extract')
            ->assertExitCode(1)
            ->expectsOutputToContain('エラーが発生しました');
    }
}
