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

        //Currency::query()->update(['is_active' => false]);

//  "symbol" => "1000CATUSDT"
//  "exchange" => "binance"
//  "last_price" => 0.03923
//  "volume" => 70652699.013495
        foreach ($currencies as $currency) {

            $data = [
                'name' => $currency['symbol'],
                'exchange' => $currency['exchange'],
                'last_price' => $currency['last_price'],
                'volume' => $currency['volume'],
                'is_active' => true,
            ];

            if ($now->minute === 0) {
                $data['start_volume_1h'] = $currency['volume'];
                $data['start_price_1h'] = $currency['last_price'];

                // кратно ли 4 часам
                if ($now->hour % 4 === 0) {
                    $data['start_volume_4h'] = $currency['volume'];
                    $data['start_price_4h'] = $currency['last_price'];
                }

                // полночь ли сейчас
                if ($now->hour === 0) {
                    $data['start_volume_24h'] = $currency['volume'];
                    $data['start_price_24h'] = $currency['last_price'];
                }
            }

            Currency::updateOrCreate(
                ['code' => $currency['symbol']],
                $data
            );

            $bar->advance();
        }

        $bar->finish();
        $this->info("\nВалюты успешно обновлены.\n");
    }
}
