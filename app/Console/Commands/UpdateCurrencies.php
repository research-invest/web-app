<?php
/**
 * php artisan update:currencies:run
 */

namespace App\Console\Commands;

use App\Models\Currency;
use App\Services\Api\Currencies;
use App\Services\External\Coingecko\Coins\Tickers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateCurrencies extends Command
{
    protected $signature = 'update:currencies:run';
    protected $description = 'Обновление или добавление валют в базу данных';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->updateCoingecko();
        $this->updateSelll();

        $this->info("\nВалюты успешно обновлены.\n");

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function updateSelll()
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


            if (!is_array($currency)) {

                Log::info("UpdateCurrencies: No Array ", [
                    '$currency' => $currency,
                    '$currencies' => $currencies,
                    'time' => $now->format('Y-m-d H:i:s'),
                    'minute' => $now->minute,
                    'hour' => $now->hour,
                ]);

                continue;
            }

            $data = [
                'name' => $currency['symbol'],
                'exchange' => $currency['exchange'],
                'last_price' => $currency['last_price'],
                'volume' => $currency['volume'],
                'is_active' => $currency['last_price'] > 0,
            ];

            if ($now->minute === 0) {
                $data['start_volume_1h'] = $currency['volume'];
                $data['start_price_1h'] = $currency['last_price'];

                // кратно ли 4 часам
                if ($now->hour % 4 === 0) {
                    $data['start_volume_4h'] = $currency['volume'];
                    $data['start_price_4h'] = $currency['last_price'];
                }
            }

            // полночь ли сейчас
            if ($now->hour === 0) {
                $data['start_volume_24h'] = $currency['volume'];
                $data['start_price_24h'] = $currency['last_price'];
            }

            Currency::updateOrCreate(
                ['code' => $currency['symbol']],
                $data
            );

            $bar->advance();
        }

//        Log::info("UpdateCurrencies: Check conditions " . $now->hour, [
//            'time' => $now->format('Y-m-d H:i:s'),
//            'minute' => $now->minute,
//            'hour' => $now->hour,
//            'timezone' => config('app.timezone'),
//            'data' => $data
//        ]);

        $bar->finish();
    }

    private function updateCoingecko()
    {
        $currencies = Currency::query()
            ->where('source_price', Currency::SOURCE_PRICE_COINGECKO)
            ->get();

        $bar = $this->output->createProgressBar(count($currencies));

        foreach ($currencies as $currency) {
            $data = (new Tickers())->getTickers($currency->coingecko_code);

            $market = [];
            foreach ($data['tickers'] as $item) {
                if ($item['market']['name'] === 'Gate') {
                    $market = $item;
                    break;
                }
            }

            if (!$market) {
                continue;
            }

            $volume = $market['volume'] ?? 0;
            $price = $market['last'] ?? 0;

            ///bid_ask_spread_percentage

            if (!$price) {
                continue;
            }

            $currency->update([
                'last_price' => $price,
                'volume' => $volume,
            ]);

            $bar->advance();
        }

        $bar->finish();
    }
}
