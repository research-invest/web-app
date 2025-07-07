<?php
/**
 * php artisan pnl-clear-old:run
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Trade;

class ClearOldPnlHistory extends Command
{
    protected $signature = 'pnl-clear-old:run';
    protected $description = 'Удаляет историю PnL старше 1 недели';

    public function handle()
    {
        $weekAgo = now()->subWeeks(1);

        $deleted = DB::table('trade_pnl_history')
            ->where('created_at', '<', $weekAgo)
            ->delete();

        $this->info("Всего удалено: {$deleted} записей");
        $this->info('Готово!');
    }
}
