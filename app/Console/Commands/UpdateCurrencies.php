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
        $now = now();

        Currency::query()->update(['is_active' => false]);

//  "symbol" => "1000CATUSDT"
//  "exchange" => "binance"
//  "last_price" => 0.03923
//  "volume" => 70652699.013495
        foreach ($currencies as $currencyData) {

            $update = [
                'name' => $currencyData['symbol'],
                'exchange' => $currencyData['exchange'],
                'last_price' => $currencyData['last_price'],
                'volume' => $currencyData['volume'],
                'is_active' => true,
            ];

            if ($now->minute === 0) {
                $update['start_volume_1h'] = $currencyData['volume'];
                $update['start_price_1h'] = $currencyData['last_price'];

                // кратно ли 4 часам
                if ($now->hour % 4 === 0) {
                    $update['start_volume_4h'] = $currencyData['volume'];
                    $update['start_price_4h'] = $currencyData['last_price'];
                }

                // полночь ли сейчас
                if ($now->hour === 0 && $now->minute === 0) {
                    $update['start_volume_24h'] = $currencyData['volume'];
                    $update['start_price_24h'] = $currencyData['last_price'];
                }
            }

            Currency::updateOrCreate(
                ['code' => $currencyData['symbol']],
                $update
            );

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nВалюты успешно обновлены.\n");
    }
}
