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
        $currencies = (new Currencies())->getCurrencies();

        $bar = $this->output->createProgressBar(count($currencies));
        $bar->start();

//  "symbol" => "1000CATUSDT"
//  "exchange" => "binance"
//  "last_price" => 0.03923
//  "volume" => 70652699.013495

        foreach ($currencies as $currencyData) {
            Currency::updateOrCreate(
                ['code' => $currencyData['symbol']],
                [
                    'name' => $currencyData['symbol'],
                    'exchange' => $currencyData['exchange'],
                    'last_price' => $currencyData['last_price'],
                    'volume' => $currencyData['volume'] ?? 0,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nВалюты успешно обновлены.\n");
    }
}
