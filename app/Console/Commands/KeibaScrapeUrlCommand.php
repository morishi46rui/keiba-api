<?php

namespace App\Console\Commands;

use App\Domains\RaceListScraperService;
use Illuminate\Console\Command;

class KeibaScrapeUrlCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:scrape-url
                            {action : 実行するアクション (scrapeurl:YYYY, scrapeurl:YYYY:MM)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'ネット競馬のレース一覧からレースURLを収集';

    /**
     * コマンドを実行
     */
    public function handle(RaceListScraperService $scraper): int
    {
        $action = $this->argument('action');

        try {
            if (str_starts_with($action, 'scrapeurl:')) {
                // scrapeurl:2022 または scrapeurl:2022:01 の形式
                $parts = explode(':', substr($action, strlen('scrapeurl:')));

                if (count($parts) === 1) {
                    $year = $parts[0];
                    $this->info("{$year}年のレースURLを収集します...");
                    $scraper->scrapeYear($year);
                    $this->info('レースURL収集が完了しました。');

                    return 0;
                } elseif (count($parts) === 2) {
                    $year = $parts[0];
                    $month = sprintf('%02d', (int) $parts[1]);
                    $this->info("{$year}年{$month}月のレースURLを収集します...");
                    $scraper->scrapeYearMonth($year, $month);
                    $this->info('レースURL収集が完了しました。');

                    return 0;
                } else {
                    $this->error('無効なフォーマットです。使用例: scrapeurl:YYYY または scrapeurl:YYYY:MM');

                    return 1;
                }
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  scrapeurl:YYYY         - 特定年のレースURLを収集');
            $this->line('  scrapeurl:YYYY:MM      - 特定年月のレースURLを収集');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:scrape-url scrapeurl:2024');
            $this->line('  php artisan keiba:scrape-url scrapeurl:2024:01');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
