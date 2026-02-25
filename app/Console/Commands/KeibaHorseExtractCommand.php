<?php

namespace App\Console\Commands;

use App\Domains\HorseExtractorService;
use Illuminate\Console\Command;

class KeibaHorseExtractCommand extends Command
{
    /**
     * コマンド名とシグネチャ
     *
     * @var string
     */
    protected $signature = 'keiba:horse-extract
                            {action? : 実行するアクション (extract)}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = 'HTMLから馬の血統情報を抽出してDBに保存';

    /**
     * コマンドを実行
     */
    public function handle(HorseExtractorService $extractor): int
    {
        $action = $this->argument('action') ?? 'extract';

        try {
            if ($action === 'extract') {
                $this->info('全ての馬HTMLから血統データを抽出します...');
                $extractor->extract();
                $this->info('抽出が完了しました。');

                return 0;
            }

            $this->error("不明なアクション: {$action}");
            $this->line('');
            $this->line('使用可能なアクション:');
            $this->line('  extract              - 全ての馬HTMLから血統データを抽出');
            $this->line('');
            $this->line('例:');
            $this->line('  php artisan keiba:horse-extract');
            $this->line('  php artisan keiba:horse-extract extract');

            return 1;

        } catch (\Exception $e) {
            $this->error('エラーが発生しました: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return 1;
        }
    }
}
