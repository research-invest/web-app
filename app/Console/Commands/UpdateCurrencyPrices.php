<?php
/**
 * php artisan update:currency-prices:run
 */

namespace App\Console\Commands;

use App\Services\CurrencyPriceService;
use Illuminate\Console\Command;

class UpdateCurrencyPrices extends Command
{
    protected $signature = 'update:currency-prices:run';
    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->updateCoingecko();

        $this->info("\nЦены валют успешно обновлены.\n");

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function updateCoingecko(): void
    {
        (new CurrencyPriceService())->getPricesByCoinGecko();
    }
}
