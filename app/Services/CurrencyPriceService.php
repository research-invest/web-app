<?php

namespace App\Services;


use App\Models\Currency;
use App\Models\CurrencyPrice;
use App\Services\External\Coingecko\Coins\Markets;

class CurrencyPriceService
{

    private $volumeBtc;

    private $priceBtc;

    private $volumeEth;
    private $priceEth;

    public function __construct()
    {
        $currencyData = Currency::query()
            ->whereIn('code', [Currency::CODE_BTC, Currency::CODE_ETH])
            ->get(['code', 'volume', 'last_price'])
            ->keyBy('code');

        $this->volumeBtc = $currencyData[Currency::CODE_BTC]->volume ?? 0;
        $this->volumeEth = $currencyData[Currency::CODE_ETH]->volume ?? 0;
        $this->priceBtc = $currencyData[Currency::CODE_BTC]->last_price ?? 0;
        $this->priceEth = $currencyData[Currency::CODE_ETH]->last_price ?? 0;
    }


    public function getPricesByCoinGecko()
    {
        $pages = 1; // можно увеличить

        for ($page = 1; $page <= $pages; $page++) {
            $coins = (new Markets())->getMarkets();

            foreach ($coins as $coin) {
                $priceChanges = $this->calculatePriceChanges(
                    $coin['id'],
                    $coin['current_price'],
                    $coin['total_volume']
                );

                CurrencyPrice::create([
                    'currency_id' => $this->getCurrencyId($coin['symbol']),
                    'coin_id' => $coin['id'],
                    'symbol' => $coin['symbol'],
                    'name' => $coin['name'],
                    'source' => CurrencyPrice::SOURCE_COINGECKO,

                    'current_price' => $coin['current_price'],
                    'market_cap' => $coin['market_cap'],
                    'market_cap_rank' => $coin['market_cap_rank'],
                    'total_volume' => $coin['total_volume'],

                    'price_change_24h' => $coin['price_change_24h'],
                    'price_change_percentage_24h' => $coin['price_change_percentage_24h'],

                    'circulating_supply' => $coin['circulating_supply'],
                    'total_supply' => $coin['total_supply'],
                    'max_supply' => $coin['max_supply'],

                    'ath' => $coin['ath'],
                    'atl' => $coin['atl'],

                    'price_btc' => $this->priceBtc,
                    'price_eth' => $this->priceEth,

                    'volume_btc' => $this->volumeBtc,
                    'volume_eth' => $this->volumeEth,

                    'price_change_vs_btc_24h' => $priceChanges['price_change_vs_btc_24h'] ?? null,
                    'price_change_vs_eth_24h' => $priceChanges['price_change_vs_eth_24h'] ?? null,

                    'price_change_vs_btc_12h' => $priceChanges['price_change_vs_btc_12h'] ?? null,
                    'price_change_vs_eth_12h' => $priceChanges['price_change_vs_eth_12h'] ?? null,

                    'price_change_vs_btc_4h' => $priceChanges['price_change_vs_btc_4h'] ?? null,
                    'price_change_vs_eth_4h' => $priceChanges['price_change_vs_eth_4h'] ?? null,

                    'volume_change_vs_btc_24h' => $priceChanges['volume_change_vs_btc_24h'] ?? null,
                    'volume_change_vs_eth_24h' => $priceChanges['volume_change_vs_eth_24h'] ?? null,
                ]);
            }
        }
    }

    private function calculatePriceChanges(string $coinId, float $currentPrice, float $currentVolume): array
    {
        $periods = [
            '24h' => 24,
            '12h' => 12,
            '4h' => 4
        ];

        $results = [];

        foreach ($periods as $key => $hours) {
            $historicalData = CurrencyPrice::where('coin_id', $coinId)
                ->where('created_at', '<=', now()->subHours($hours))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($historicalData) {
                // Изменение цены относительно BTC
                $btcPriceChangePercent = 0;
                if ($historicalData->price_btc != 0 && $this->priceBtc != 0 && $historicalData->current_price != 0) {
                    // Изменение цены монеты
                    $coinPriceChange = (($currentPrice - $historicalData->current_price) / $historicalData->current_price) * 100;
                    
                    // Изменение цены BTC
                    $btcPriceChange = (($this->priceBtc - $historicalData->price_btc) / $historicalData->price_btc) * 100;
                    
                    // Если BTC растет, а монета падает (или наоборот) - отрицательная корреляция
                    // Если оба растут или оба падают - положительная корреляция
                    // Сила корреляции зависит от разницы в процентах
                    if (($btcPriceChange > 0 && $coinPriceChange < 0) || ($btcPriceChange < 0 && $coinPriceChange > 0)) {
                        $btcPriceChangePercent = -abs($coinPriceChange - $btcPriceChange);
                    } else {
                        $btcPriceChangePercent = abs($coinPriceChange - $btcPriceChange);
                    }
                }

                // Изменение цены относительно ETH
                $ethPriceChangePercent = 0;
                if ($historicalData->price_eth != 0 && $this->priceEth != 0 && $historicalData->current_price != 0) {
                    // Изменение цены монеты
                    $coinPriceChange = (($currentPrice - $historicalData->current_price) / $historicalData->current_price) * 100;
                    
                    // Изменение цены ETH
                    $ethPriceChange = (($this->priceEth - $historicalData->price_eth) / $historicalData->price_eth) * 100;
                    
                    // Аналогичная логика для ETH
                    if (($ethPriceChange > 0 && $coinPriceChange < 0) || ($ethPriceChange < 0 && $coinPriceChange > 0)) {
                        $ethPriceChangePercent = -abs($coinPriceChange - $ethPriceChange);
                    } else {
                        $ethPriceChangePercent = abs($coinPriceChange - $ethPriceChange);
                    }
                }

                // Изменение объема относительно BTC
                $btcVolumeChangePercent = 0;
                if ($historicalData->volume_btc != 0 && $this->volumeBtc != 0 && $historicalData->total_volume != 0) {
                    $coinVolumeChange = (($currentVolume - $historicalData->total_volume) / $historicalData->total_volume) * 100;
                    $btcVolumeChange = (($this->volumeBtc - $historicalData->volume_btc) / $historicalData->volume_btc) * 100;
                    
                    // Та же логика для объемов
                    if (($btcVolumeChange > 0 && $coinVolumeChange < 0) || ($btcVolumeChange < 0 && $coinVolumeChange > 0)) {
                        $btcVolumeChangePercent = -abs($coinVolumeChange - $btcVolumeChange);
                    } else {
                        $btcVolumeChangePercent = abs($coinVolumeChange - $btcVolumeChange);
                    }
                }

                // Изменение объема относительно ETH
                $ethVolumeChangePercent = 0;
                if ($historicalData->volume_eth != 0 && $this->volumeEth != 0 && $historicalData->total_volume != 0) {
                    $coinVolumeChange = (($currentVolume - $historicalData->total_volume) / $historicalData->total_volume) * 100;
                    $ethVolumeChange = (($this->volumeEth - $historicalData->volume_eth) / $historicalData->volume_eth) * 100;
                    
                    // Та же логика для объемов ETH
                    if (($ethVolumeChange > 0 && $coinVolumeChange < 0) || ($ethVolumeChange < 0 && $coinVolumeChange > 0)) {
                        $ethVolumeChangePercent = -abs($coinVolumeChange - $ethVolumeChange);
                    } else {
                        $ethVolumeChangePercent = abs($coinVolumeChange - $ethVolumeChange);
                    }
                }

                $results["price_change_vs_btc_{$key}"] = $btcPriceChangePercent;
                $results["price_change_vs_eth_{$key}"] = $ethPriceChangePercent;

                if ($key === '24h') {
                    $results['volume_change_vs_btc_24h'] = $btcVolumeChangePercent;
                    $results['volume_change_vs_eth_24h'] = $ethVolumeChangePercent;
                }
            }
        }

        return $results;
    }

    private function getCurrencyId(mixed $symbol): ?int
    {
        $code = strtoupper($symbol . 'USDT');
        return Currency::query()->where('code', $code)->first()?->id;
    }

}

