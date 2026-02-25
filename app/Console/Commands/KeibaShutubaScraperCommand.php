<?php

namespace App\Console\Commands;

use App\Domains\ShutubaScraperService;
use Illuminate\Console\Command;

class KeibaShutubaScraperCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:shutuba-scrape
                            {action : 実行するアクション (scrapehtml, scrapehtml:YYYY, scrapehtml:YYYY:MM)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'ネット競馬から出馬表HTMLをスクレイピング';

    /**
     * コマンドを実行
     */
    public function handle(ShutubaScraperService $scraper): int
    {
        $action = $this->argument('action');

        try {
            if ($action === 'scrapehtml') {
                $this->info('全ての出馬表HTMLをスクレイピングします...');
                $scraper->scrapeAll();
                $this->info('スクレイピングが完了しました。');

                return 0;
            }

            if (str_starts_with($action, 'scrapehtml:')) {
                // scrapehtml:2022 または scrapehtml:2022:01 の形式
                $parts = explode(':', substr($action, strlen('scrapehtml:')));

                if (count($parts) === 1) {
                    $year = $parts[0];
                    $this->info("{$year}年の出馬表HTMLをスクレイピングします...");
                    $scraper->scrapeYear($year);
                    $this->info('スクレイピングが完了しました。');

                    return 0;
                } elseif (count($parts) === 2) {
                    $year = $parts[0];
                    $month = sprintf('%02d', (int) $parts[1]);
                    $this->info("{$year}年{$month}月の出馬表HTMLをスクレイピングします...");
                    $scraper->scrapeYearMonth($year, $month);
                    $this->info('スクレイピングが完了しました。');

                    return 0;
                } else {
                    $this->error('無効なフォーマットです。使用例: scrapehtml:YYYY または scrapehtml:YYYY:MM');

                    return 1;
                }
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  scrapehtml              - 全ての出馬表をスクレイピング');
            $this->line('  scrapehtml:YYYY         - 特定年の出馬表をスクレイピング');
            $this->line('  scrapehtml:YYYY:MM      - 特定年月の出馬表をスクレイピング');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:shutuba-scrape scrapehtml');
            $this->line('  php artisan keiba:shutuba-scrape scrapehtml:2024');
            $this->line('  php artisan keiba:shutuba-scrape scrapehtml:2024:01');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
