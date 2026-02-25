<?php

namespace App\Console\Commands;

use App\Domains\RaceExtractorService;
use Illuminate\Console\Command;

class KeibaExtractCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:extract
                            {action? : 実行するアクション (extract, extract:YYYY, extract:YYYY:MM)}
                            {--year= : 年を指定 (例: 2022)}
                            {--month= : 月を指定 (例: 03)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'HTMLからレース情報を抽出してDBに保存';

    /**
     * コマンドを実行
     */
    public function handle(RaceExtractorService $extractor): int
    {
        $action = $this->argument('action') ?? 'extract';

        try {
            // アクションに応じた処理を実行
            if ($action === 'extract') {
                $this->info('全てのレースHTMLからデータを抽出します...');
                $extractor->extract();
                $this->info('抽出が完了しました。');

                return 0;
            }

            if (str_starts_with($action, 'extract:')) {
                // extract:2022 または extract:2022:03 の形式
                $parts = explode(':', substr($action, strlen('extract:')));

                if (count($parts) === 1) {
                    $year = $parts[0];
                    $this->info("{$year}年のレースHTMLからデータを抽出します...");
                    $extractor->extract($year);
                    $this->info('抽出が完了しました。');

                    return 0;
                } elseif (count($parts) === 2) {
                    $year = $parts[0];
                    $month = sprintf('%02d', (int) $parts[1]);
                    $this->info("{$year}年{$month}月のレースHTMLからデータを抽出します...");
                    $extractor->extract($year, $month);
                    $this->info('抽出が完了しました。');

                    return 0;
                } else {
                    $this->error('無効なフォーマットです。使用例: extract:YYYY または extract:YYYY:MM');

                    return 1;
                }
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  extract              - 全てのレースHTMLからデータを抽出');
            $this->line('  extract:YYYY         - 特定年のレースHTMLからデータを抽出');
            $this->line('  extract:YYYY:MM      - 特定年月のレースHTMLからデータを抽出');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:extract');
            $this->line('  php artisan keiba:extract extract:2022');
            $this->line('  php artisan keiba:extract extract:2022:03');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
