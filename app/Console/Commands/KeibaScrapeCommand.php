<?php

namespace App\Console\Commands;

use App\Domains\RaceScraperService;
use App\Domains\KeibaUtil;
use Illuminate\Console\Command;

class KeibaScrapeCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:scrape
                            {action : 実行するアクション (scrapehtml, scrapehtml:YYYY, scrapehtml:YYYY:MM, raceurl:RACE_ID)}
                            {--year= : 年を指定 (例: 2024)}
                            {--month= : 月を指定 (例: 01)}
                            {--race-id= : レースIDを指定 (12桁の数字)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'ネット競馬からレース情報をスクレイピング';

    /**
     * コマンドを実行
     */
    public function handle(RaceScraperService $scraper): int
    {
        $action = $this->argument('action');

        try {
            // アクションに応じた処理を実行
            if ($action === 'scrapehtml') {
                $this->info('全てのレースHTMLをスクレイピングします...');
                $scraper->scrapeAll();
                $this->info('スクレイピングが完了しました。');
                return 0;
            }

            if (str_starts_with($action, 'scrapehtml:')) {
                // scrapehtml:2022 または scrapehtml:2022:01 の形式
                $parts = explode(':', substr($action, strlen('scrapehtml:')));

                if (count($parts) === 1) {
                    $year = $parts[0];
                    $this->info("{$year}年のレースHTMLをスクレイピングします...");
                    $scraper->scrapeYear($year);
                    $this->info('スクレイピングが完了しました。');
                    return 0;
                } elseif (count($parts) === 2) {
                    $year = $parts[0];
                    $month = sprintf('%02d', (int) $parts[1]);
                    $this->info("{$year}年{$month}月のレースHTMLをスクレイピングします...");
                    $scraper->scrapeYearMonth($year, $month);
                    $this->info('スクレイピングが完了しました。');
                    return 0;
                } else {
                    $this->error('無効なフォーマットです。使用例: scrapehtml:YYYY または scrapehtml:YYYY:MM');
                    return 1;
                }
            }

            if (str_starts_with($action, 'raceurl:')) {
                // raceurl:202505040701 の形式
                $raceId = substr($action, strlen('raceurl:'));

                if (!KeibaUtil::isValidRaceId($raceId)) {
                    $this->error('race_idは12桁の数字を指定してください (例: raceurl:202505040701)');
                    return 1;
                }

                $url = KeibaUtil::buildRaceUrl($raceId);
                $this->line($url);
                return 0;
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  scrapehtml              - 全てのレースをスクレイピング');
            $this->line('  scrapehtml:YYYY         - 特定年のレースをスクレイピング');
            $this->line('  scrapehtml:YYYY:MM      - 特定年月のレースをスクレイピング');
            $this->line('  raceurl:RACE_ID         - レースURLを表示');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:scrape scrapehtml:2024');
            $this->line('  php artisan keiba:scrape scrapehtml:2024:01');
            $this->line('  php artisan keiba:scrape raceurl:202505040701');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
