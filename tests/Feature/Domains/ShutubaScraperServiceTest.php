<?php

namespace Tests\Feature\Domains;

use App\Domains\ShutubaScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * ShutubaScraperServiceのテスト
 *
 * 注意: ShutubaScraperServiceはstorage_path()経由で直接ファイルシステムを
 * 読み込むため、Storage::fake()ではモックできない部分があります。
 * collectUrls()のテストでは実際のファイルシステムを操作します。
 */
class ShutubaScraperServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShutubaScraperService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShutubaScraperService;
        Storage::fake('local');
    }

    // ========================================
    // 正常系: 既存HTMLがある場合はスキップ
    // ========================================

    public function test_既存HTMLファイルがある場合はスキップする(): void
    {
        // URLディレクトリ構造をstorage_path配下に作成
        $urlBasePath = storage_path('app/url/race/2024/01');
        @mkdir($urlBasePath, 0755, true);
        file_put_contents(
            $urlBasePath.'/race_url.txt',
            "https://race.netkeiba.com/race/result.html?race_id=202406010101|20240106\n"
        );

        // 既存HTMLを配置
        Storage::disk('local')->put('html/shutuba_html/2024/01/06/中山/202406010101.html', '<html>existing</html>');

        Http::fake();

        $this->service->scrapeYearMonth('2024', '01');

        Http::assertNothingSent();

        // テスト後のクリーンアップ
        @unlink($urlBasePath.'/race_url.txt');
        @rmdir($urlBasePath);
        @rmdir(storage_path('app/url/race/2024'));
        @rmdir(storage_path('app/url/race'));
    }

    // ========================================
    // 正常系: 新規出馬表HTMLをダウンロードして保存
    // ========================================

    public function test_新規出馬表HTMLをダウンロードして保存する(): void
    {
        // URLディレクトリ構造をstorage_path配下に作成
        $urlBasePath = storage_path('app/url/race/2024/01');
        @mkdir($urlBasePath, 0755, true);
        file_put_contents(
            $urlBasePath.'/race_url.txt',
            "https://race.netkeiba.com/race/result.html?race_id=202406010101|20240106\n"
        );

        Http::fake([
            'https://race.netkeiba.com/race/shutuba.html?race_id=202406010101' => Http::response('<html>shutuba page</html>', 200),
        ]);

        $this->service->scrapeYearMonth('2024', '01');

        Http::assertSentCount(1);
        Storage::disk('local')->assertExists('html/shutuba_html/2024/01/06/中山/202406010101.html');

        // テスト後のクリーンアップ
        @unlink($urlBasePath.'/race_url.txt');
        @rmdir($urlBasePath);
        @rmdir(storage_path('app/url/race/2024'));
        @rmdir(storage_path('app/url/race'));
    }

    // ========================================
    // 異常系: HTTPエラー時にファイルを作成しない
    // ========================================

    public function test_HTTPエラー時にファイルを作成しない(): void
    {
        $urlBasePath = storage_path('app/url/race/2024/02');
        @mkdir($urlBasePath, 0755, true);
        file_put_contents(
            $urlBasePath.'/race_url.txt',
            "https://race.netkeiba.com/race/result.html?race_id=202406020201|20240201\n"
        );

        Http::fake([
            '*' => Http::response('', 500),
        ]);

        $this->service->scrapeYearMonth('2024', '02');

        Storage::disk('local')->assertMissing('html/shutuba_html/2024/02/01/函館/202406020201.html');

        // テスト後のクリーンアップ
        @unlink($urlBasePath.'/race_url.txt');
        @rmdir($urlBasePath);
        @rmdir(storage_path('app/url/race/2024'));
        @rmdir(storage_path('app/url/race'));
    }

    // ========================================
    // 異常系: URLディレクトリが存在しない場合
    // ========================================

    public function test_URLディレクトリが存在しない場合は何もしない(): void
    {
        Http::fake();

        $urlBasePath = storage_path('app/url/race');

        // ディレクトリが存在する場合は一時的にリネームして退避
        $backupPath = null;
        if (is_dir($urlBasePath)) {
            $backupPath = $urlBasePath.'_backup_'.uniqid();
            rename($urlBasePath, $backupPath);
        }

        try {
            $this->assertDirectoryDoesNotExist($urlBasePath);

            $this->service->scrapeAll();

            Http::assertNothingSent();
        } finally {
            // テスト後にディレクトリを復元
            if ($backupPath !== null && is_dir($backupPath)) {
                rename($backupPath, $urlBasePath);
            }
        }
    }

    // ========================================
    // 正常系: レースIDからトラック名が正しく解決される
    // ========================================

    public function test_レースIDからトラック名が正しく解決される(): void
    {
        // race_id=202405010101 → trackCode=01 → 札幌
        $urlBasePath = storage_path('app/url/race/2024/03');
        @mkdir($urlBasePath, 0755, true);
        file_put_contents(
            $urlBasePath.'/race_url.txt',
            "https://race.netkeiba.com/race/result.html?race_id=202401010101|20240301\n"
        );

        Http::fake([
            '*' => Http::response('<html>shutuba</html>', 200),
        ]);

        $this->service->scrapeYearMonth('2024', '03');

        // trackCode=01 → 札幌
        Storage::disk('local')->assertExists('html/shutuba_html/2024/03/01/札幌/202401010101.html');

        // テスト後のクリーンアップ
        @unlink($urlBasePath.'/race_url.txt');
        @rmdir($urlBasePath);
        @rmdir(storage_path('app/url/race/2024'));
        @rmdir(storage_path('app/url/race'));
    }

    // ========================================
    // 正常系: 旧形式URL（日付なし）の処理
    // ========================================

    public function test_旧形式URLでday_unknownとして保存される(): void
    {
        $urlBasePath = storage_path('app/url/race/2024/04');
        @mkdir($urlBasePath, 0755, true);
        // 旧形式: URLのみ（|日付なし）
        file_put_contents(
            $urlBasePath.'/race_url.txt',
            "https://race.netkeiba.com/race/result.html?race_id=202405010101\n"
        );

        Http::fake([
            '*' => Http::response('<html>shutuba</html>', 200),
        ]);

        $this->service->scrapeYearMonth('2024', '04');

        // day = 'unknown'
        Storage::disk('local')->assertExists('html/shutuba_html/2024/04/unknown/東京/202405010101.html');

        // テスト後のクリーンアップ
        @unlink($urlBasePath.'/race_url.txt');
        @rmdir($urlBasePath);
        @rmdir(storage_path('app/url/race/2024'));
        @rmdir(storage_path('app/url/race'));
    }
}
