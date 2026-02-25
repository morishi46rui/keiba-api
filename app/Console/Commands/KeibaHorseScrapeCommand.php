<?php

namespace App\Console\Commands;

use App\Domains\HorseScraperService;
use Illuminate\Console\Command;

class KeibaHorseScrapeCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:horse-scrape
                            {action : 実行するアクション (scrapehtml, scrapehtml:HORSE_ID)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'ネット競馬から馬情報HTMLをスクレイピング';

    /**
     * コマンドを実行
     */
    public function handle(HorseScraperService $scraper): int
    {
        $action = $this->argument('action');

        try {
            if ($action === 'scrapehtml') {
                $this->info('全ての馬HTMLをスクレイピングします...');
                $scraper->scrapeAll();
                $this->info('スクレイピングが完了しました。');

                return 0;
            }

            if (str_starts_with($action, 'scrapehtml:')) {
                $horseId = substr($action, strlen('scrapehtml:'));

                if (empty($horseId)) {
                    $this->error('馬IDを指定してください (例: scrapehtml:2019104308)');

                    return 1;
                }

                $this->info("馬 {$horseId} のHTMLをスクレイピングします...");
                $scraper->scrapeByHorseIds([$horseId]);
                $this->info('スクレイピングが完了しました。');

                return 0;
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  scrapehtml              - 全ての馬HTMLをスクレイピング');
            $this->line('  scrapehtml:HORSE_ID     - 特定馬のHTMLをスクレイピング');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:horse-scrape scrapehtml');
            $this->line('  php artisan keiba:horse-scrape scrapehtml:2019104308');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
