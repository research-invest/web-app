<?php
/**
 * php artisan update:currencies:run
 */

namespace App\Console\Commands;

use App\Models\Currency;
use App\Services\Api\Currencies;
use Illuminate\Console\Command;

class UpdateCurrencies extends Command
{
    protected $signature = 'update:currencies:run';
    protected $description = 'Обновление или добавление валют в базу данных';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->update();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function update()
    {
        $currenciesService = new Currencies();
        $currencies = $currenciesService->getCurrencies();

        $bar = $this->output->createProgressBar(count($currencies));
        $bar->start();

        foreach ($currencies as $currencyData) {
            Currency::updateOrCreate(
                ['code' => $currencyData['symbol']],
                [
                    'name' => $currencyData['symbol'],
                    'exchange' => $currencyData['exchange'],
                    'last_price' => $currencyData['last_price']
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nВалюты успешно обновлены.\n");
    }
}
