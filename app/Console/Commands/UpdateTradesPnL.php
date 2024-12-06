<?php
/**
 * php artisan trades:update-pnl
 */

namespace App\Console\Commands;

use App\Models\Trade;
use Illuminate\Console\Command;

class UpdateTradesPnL extends Command
{
    protected $signature = 'trades:update-pnl';
    protected $description = '';

    public function handle()
    {
        $openTrades = Trade::where('status', 'open')->get();

        /**
         * @var Trade $trade
         */
        foreach ($openTrades as $trade) {
            $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
            $trade->updatePnL($currentPrice);
        }

        $this->info('PNL updated for ' . $openTrades->count() . ' trades');
    }
}
