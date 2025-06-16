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
    protected $description = 'Удаляет историю pnl старше 3 дней от открытия сделки, кроме точки закрытия';

    public function handle()
    {
        $trades = Trade::with(['pnlHistory'])->get();

        foreach ($trades as $trade) {
            $open = $trade->created_at;
            $close = $trade->closed_at;
            $border = $open->copy()->addDays(3);

            // id всех записей, которые нужно оставить
            $keepIds = [];

            // Оставляем все записи, которые <= 3 дней от открытия
            foreach ($trade->pnlHistory as $history) {
                if ($history->created_at->lte($border)) {
                    $keepIds[] = $history->id;
                }
            }

            // Если сделка закрыта — оставляем одну запись с датой закрытия (если есть)
            if ($close) {
                $closeHistory = $trade->pnlHistory->where('created_at', $close)->first();
                if ($closeHistory) {
                    $keepIds[] = $closeHistory->id;
                }
            }

            // Удаляем остальные
            $delete = $trade->pnlHistory->whereNotIn('id', $keepIds);
            if ($delete->count()) {
                DB::table('trade_pnl_history')->whereIn('id', $delete->pluck('id'))->delete();
                $this->info("Trade #{$trade->id}: удалено {$delete->count()} записей");
            }
        }

        $this->info('Готово!');
    }
}
