<?php
/**
 * php artisan trades:check-levels
 */
namespace App\Console\Commands\Alerts;

use App\Models\Trade;
use App\Services\RiskManagement\PositionCalculator;
use Illuminate\Console\Command;

class CheckTradeLevels extends Command
{
    protected $signature = 'trades:check-levels';
    protected $description = '';

    public function handle()
    {
        Trade::with('currency')
            ->where('status', 'open')
            ->each(function (Trade $trade) {
                $calculator = new PositionCalculator($trade);
                $calculator->checkPriceLevels($trade->currency->last_price);
            });

        $this->info('Проверка уровней успеднения пройдена');
    }
}
