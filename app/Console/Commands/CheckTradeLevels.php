<?php

namespace App\Console\Commands;

use App\Models\Trade;
use App\Services\RiskManagement\PositionCalculator;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class CheckTradeLevels extends Command
{
    protected $signature = 'trades:check-levels';
    protected $description = '';

    public function handle(TelegramService $telegram)
    {
        Trade::with('currency')
            ->where('status', 'open')
            ->each(function (Trade $trade) {
                $calculator = new PositionCalculator($trade);
                $calculator->checkPriceLevels($trade->currency->last_price);
            });

        $this->info('Price levels checked successfully');
    }
}
