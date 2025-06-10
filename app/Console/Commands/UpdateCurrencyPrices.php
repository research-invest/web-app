<?php
/**
 * php artisan update:currency-prices:run
 */

namespace App\Console\Commands;

use App\Models\CurrencyPrice;
use App\Services\CurrencyPriceService;
use Illuminate\Console\Command;

class UpdateCurrencyPrices extends Command
{
    protected $signature = 'update:currency-prices:run';
    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->cleanup();
        $this->updateCoingecko();

        $this->info("\nЦены валют успешно обновлены.\n");

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function updateCoingecko(): void
    {
        (new CurrencyPriceService())->getPricesByCoinGecko();
    }

    private function cleanup(): void
    {
        $deleted = CurrencyPrice::query()
            ->where('created_at', '<=', now()->subDays(3))
            ->delete();

        if ($deleted > 0) {
            $this->info("\nУдалено {$deleted} устаревших записей.");
        }
    }
}
