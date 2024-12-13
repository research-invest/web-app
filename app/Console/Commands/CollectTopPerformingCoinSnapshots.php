<?php
/**
 * php artisan collect-top-performing-coin-snapshots:run
 */

namespace App\Console\Commands;

use App\Models\Currency;
use App\Models\TopPerformingCoinSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectTopPerformingCoinSnapshots extends Command
{
    protected $signature = 'collect-top-performing-coin-snapshots:run';

    protected $description = '';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->collect();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    public function collect(): void
    {
        try {
//            $priceChangePercent = (int)$this->option('price-change') ?: 10;
//            $minVolumeDiff = (int)$this->option('volume-diff') ?: 20;
            $priceChangePercent = 10;
            $minVolumeDiff = 20;

            $result = (new \App\Services\Api\TopPerformingCoins())->getTopPerformingCoins($priceChangePercent, $minVolumeDiff);

            $now = Carbon::now();
            $snapshots = $currencies = [];

            foreach (Currency::all() as $currency) {
                $currencies[$currency->code] = [
                    'id' => $currency->id,
                    'price' => $currency->last_price,
                ];
            }

            foreach ($result as $coin) {
                if (!isset($currencies[$coin['symbol']])) {
                    $this->warn("Currency {$coin['symbol']} not found in database");
                    continue;
                }

                $snapshots[] = [
                    'currency_id' => $currencies[$coin['symbol']]['id'],
                    'symbol' => $coin['symbol'],
                    'price' => $currencies[$coin['symbol']]['price'],
                    'price_change_percent' => round($coin['price_change_percent'], 2),
                    'volume_diff_percent' => round($coin['volume_diff_percent'], 2),
                    'created_at' => $now
                ];
            }

            if (!empty($snapshots)) {
                TopPerformingCoinSnapshot::insert($snapshots);
                $this->info('Successfully collected ' . count($snapshots) . ' snapshots');
            } else {
                $this->info('No data to collect');
            }
        } catch (\Exception $e) {
            Log::error('Error collecting coin snapshots: ' . $e->getMessage());
            $this->error('Failed to collect snapshots: ' . $e->getMessage());
        }
    }
}
