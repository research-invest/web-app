<?php

/**
 * php artisan funding:collect
 */

namespace App\Console\Commands\Features;

use App\Models\Currency;
use App\Models\FundingRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CollectFundingRates extends Command
{
    protected $signature = 'funding:collect';
    protected $description = 'Collect funding rates from MEXC';

    public function handle()
    {
        $timeStart = microtime(true);

        $this->collect();

        $this->info('Использовано памяти: ' . (memory_get_peak_usage() / 1024 / 1024) . " MB");
        $this->info('Время выполнения в секундах: ' . ((microtime(true) - $timeStart)));
    }

    private function collect(): int
    {
        $response = Http::get('https://contract.mexc.com/api/v1/contract/funding_rate');

        if (!$response->successful()) {
            $this->error('Failed to fetch funding rates');
            return 1;
        }

        $data = $response->json()['data'];

        foreach ($data as $item) {
            $currency = Currency::firstOrCreate(
                [
                    'code' => $item['symbol'],
                    'name' => $item['symbol'],
                    'exchange' => 'mexc',
                    'type' => Currency::TYPE_FEATURE
                ]
            );

            $fundingRate = new FundingRate([
                'funding_rate' => $item['fundingRate'],
                'max_funding_rate' => $item['maxFundingRate'],
                'min_funding_rate' => $item['minFundingRate'],
                'collect_cycle' => $item['collectCycle'],
                'next_settle_time' => $item['nextSettleTime'],
                'timestamp' => $item['timestamp']
            ]);

            $this->calculateDiffs($currency, $fundingRate);

            $currency->fundingRates()->save($fundingRate);
        }

        return 0;
    }

    private function calculateDiffs(Currency $currency, FundingRate $newRate): void
    {
        $periods = [
            'diff_8h' => 8 * 3600,
            'diff_24h' => 24 * 3600,
            'diff_48h' => 48 * 3600,
            'diff_7d' => 7 * 24 * 3600,
            'diff_30d' => 30 * 24 * 3600
        ];

        $startFields = [
            'diff_8h' => 'start_funding_8h',
            'diff_24h' => 'start_funding_24h',
            'diff_48h' => 'start_funding_48h',
            'diff_7d' => 'start_funding_7d',
            'diff_30d' => 'start_funding_30d'
        ];

        foreach ($periods as $field => $seconds) {
            $oldRate = $currency->fundingRates()
                ->where('timestamp', '<=', $newRate->timestamp - ($seconds * 1000))
                ->orderByDesc('timestamp')
                ->first();

            if ($oldRate) {
                $newRate->$field = $newRate->funding_rate - $oldRate->funding_rate;
                
                // Обновляем начальное значение фандинга в валюте
                $startField = $startFields[$field];
                if ($currency->$startField === null) {
                    $currency->$startField = $oldRate->funding_rate;
                    $currency->save();
                }
            }
        }
    }
}
