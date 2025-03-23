<?php

/**
 * php artisan funding:collect
 */

namespace App\Console\Commands\Features;

use App\Models\Currency;
use App\Models\Funding\FundingRate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Lin\Ku\Kucoin;
use Lin\Ku\KucoinFuture;

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

    private function collect(): void
    {
        $this->kukoin();
    }

    private function kukoin(): void
    {
        $service = new KucoinFuture();
        $result = $service->contracts()->getActive();
//        27 => array:64 [
//        "symbol" => "AGLDUSDTM"
//      "rootSymbol" => "USDT"
//      "type" => "FFWCSX"
//      "firstOpenDate" => 1646208000000
//      "expireDate" => null
//      "settleDate" => null
//      "baseCurrency" => "AGLD"
//      "quoteCurrency" => "USDT"
//      "settleCurrency" => "USDT"
//      "maxOrderQty" => 1000000
//      "maxPrice" => 1000000.0
//      "lotSize" => 1
//      "tickSize" => 0.0001
//      "indexPriceTickSize" => 0.0001
//      "multiplier" => 1.0
//      "initialMargin" => 0.02
//      "maintainMargin" => 0.012
//      "maxRiskLimit" => 10000
//      "minRiskLimit" => 10000
//      "riskStep" => 5000
//      "makerFeeRate" => 0.0002
//      "takerFeeRate" => 0.0006
//      "takerFixFee" => 0.0
//      "makerFixFee" => 0.0
//      "settlementFee" => null
//      "isDeleverage" => true
//      "isQuanto" => false
//      "isInverse" => false
//      "markMethod" => "FairPrice"
//      "fairMethod" => "FundingRate"
//      "fundingBaseSymbol" => ".AGLDINT8H"
//      "fundingQuoteSymbol" => ".USDTINT8H"
//      "fundingRateSymbol" => ".AGLDUSDTMFPI8H"
//      "indexSymbol" => ".KAGLDUSDT"
//      "settlementSymbol" => ""
//      "status" => "Open"
//      "fundingFeeRate" => -0.000527
//      "predictedFundingFeeRate" => -0.001442
//      "fundingRateGranularity" => 28800000
//      "openInterest" => "627827"
//      "turnoverOf24h" => 983813.31322824
//      "volumeOf24h" => 979675.0
//      "markPrice" => 1.0048
//      "indexPrice" => 1.005
//      "lastTradePrice" => 1.0033
//      "nextFundingRateTime" => 3148885
//      "maxLeverage" => 50
//      "sourceExchanges" => array:6 [ …6]
//      "premiumsSymbol1M" => ".AGLDUSDTMPI"
//      "premiumsSymbol8H" => ".AGLDUSDTMPI8H"
//      "fundingBaseSymbol1M" => ".AGLDINT"
//      "fundingQuoteSymbol1M" => ".USDTINT"
//      "lowPrice" => 0.9657
//      "highPrice" => 1.0414
//      "priceChgPct" => 0.0228
//      "priceChg" => 0.0224
//      "k" => 26000.0
//      "m" => 9000.0
//      "f" => 1.3
//      "mmrLimit" => 0.3
//      "mmrLevConstant" => 50.0
//      "supportCross" => true
//      "buyLimit" => 1.0551
//      "sellLimit" => 0.9547
//    ]
        $this->resetAllFunding();

        /**
         * @var Currency $currency
         */
        foreach ($result as $item) {
            $currency = Currency::firstOrCreate(
                [
                    'code' => $item['symbol'],
                    'name' => $item['symbol'],
                    'exchange' => Currency::EXCHANGE_KUKOIN,
                    'type' => Currency::TYPE_FEATURE
                ]
            );

            $fundingTime = Carbon::createFromTimestamp(
                $item['nextSettleTime'] / 1000
            );

            $currency->update([
                'funding_rate' => $item['fundingRate'] * 100,
                'next_settle_time' => $fundingTime->timestamp,
            ]);

            $fundingRate = new FundingRate([
                'funding_rate' => $currency->funding_rate,
                'max_funding_rate' => $item['maxFundingRate'] * 100,
                'min_funding_rate' => $item['minFundingRate'] * 100,
                'collect_cycle' => $item['collectCycle'],
                'next_settle_time' => $item['nextSettleTime'],
                'timestamp' => $item['timestamp']
            ]);

//            $this->calculateDiffs($currency, $fundingRate);

            $currency->fundingRates()->save($fundingRate);
        }
    }

    private function mexc(): int
    {
        $response = Http::get('https://contract.mexc.com/api/v1/contract/funding_rate');

        if (!$response->successful()) {
            $this->error('Failed to fetch funding rates');
            return 1;
        }

        $this->resetAllFunding();

        $data = $response->json()['data'];

        /**
         * @var Currency $currency
         */
        foreach ($data as $item) {
            $currency = Currency::firstOrCreate(
                [
                    'code' => $item['symbol'],
                    'name' => $item['symbol'],
                    'exchange' => Currency::EXCHANGE_MEXC,
                    'type' => Currency::TYPE_FEATURE
                ]
            );

            $fundingTime = Carbon::createFromTimestamp(
                $item['nextSettleTime'] / 1000
            );

            $currency->update([
                'funding_rate' => $item['fundingRate'] * 100,
                'next_settle_time' => $fundingTime->timestamp,
            ]);

            $fundingRate = new FundingRate([
                'funding_rate' => $currency->funding_rate,
                'max_funding_rate' => $item['maxFundingRate'] * 100,
                'min_funding_rate' => $item['minFundingRate'] * 100,
                'collect_cycle' => $item['collectCycle'],
                'next_settle_time' => $item['nextSettleTime'],
                'timestamp' => $item['timestamp']
            ]);

//            $this->calculateDiffs($currency, $fundingRate);

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

    private function resetAllFunding(): void
    {
        $update = <<<SQL
        UPDATE currencies AS c
            SET funding_rate = 0
        WHERE funding_rate is not null
        SQL;

        DB::update($update);
    }
}
