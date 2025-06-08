<?php

namespace App\Orchid\Screens\Statistics;

use App\Models\CurrencyPrice;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class CryptoCorrelationScreen extends Screen
{
    public function query(): array
    {
        $currencies = CurrencyPrice::query()
            ->select([
                'currencies_prices.*',
                'currencies.code',
                'currencies.name'
            ])
            ->join('currencies', 'currencies.id', '=', 'currencies_prices.currency_id')
            ->whereNotNull('currency_id')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('currency_id')
            ->map(function ($prices) {
                $latest = $prices->first();
                return [
                    'code' => $latest->code,
                    'name' => $latest->name,
                    'current_price' => $latest->current_price,
                    'market_cap' => $latest->market_cap,
                    'volume' => $latest->total_volume,

                    // Изменения относительно BTC
                    'btc_4h' => $latest->price_change_vs_btc_4h,
                    'btc_12h' => $latest->price_change_vs_btc_12h,
                    'btc_24h' => $latest->price_change_vs_btc_24h,
                    'btc_volume_24h' => $latest->volume_change_vs_btc_24h,

                    // Изменения относительно ETH
                    'eth_4h' => $latest->price_change_vs_eth_4h,
                    'eth_12h' => $latest->price_change_vs_eth_12h,
                    'eth_24h' => $latest->price_change_vs_eth_24h,
                    'eth_volume_24h' => $latest->volume_change_vs_eth_24h,
                ];
            })
            ->values()
            ->toArray();

        return [
            'currencies' => $currencies
        ];
    }

    public function name(): ?string
    {
        return 'Корреляция с BTC/ETH';
    }

    public function description(): ?string
    {
        return 'Анализ движения монет относительно BTC и ETH';
    }

    public function layout(): array
    {
        return [
            Layout::table('currencies', [
                TD::make('code', 'Монета')
                    ->sort()
                    ->render(fn ($row) => $row['code']),

                TD::make('current_price', 'Цена')
                    ->sort()
                    ->render(fn ($row) => number_format($row['current_price'], 8)),

                TD::make('market_cap', 'Капитализация')
                    ->sort()
                    ->render(fn ($row) => number_format($row['market_cap'], 0)),

                // BTC корреляция
                TD::make('btc_correlation', 'BTC корреляция')
                    ->render(function ($row) {
                        $html = [];

                        // 4H
                        $change4h = $row['btc_4h'];
                        $color4h = $this->getColorByChange($change4h);
                        $html[] = "<div style='color: {$color4h}'>4H: {$change4h}%</div>";

                        // 12H
                        $change12h = $row['btc_12h'];
                        $color12h = $this->getColorByChange($change12h);
                        $html[] = "<div style='color: {$color12h}'>12H: {$change12h}%</div>";

                        // 24H
                        $change24h = $row['btc_24h'];
                        $color24h = $this->getColorByChange($change24h);
                        $html[] = "<div style='color: {$color24h}'>24H: {$change24h}%</div>";

                        return implode('', $html);
                    }),

                // ETH корреляция
                TD::make('eth_correlation', 'ETH корреляция')
                    ->render(function ($row) {
                        $html = [];

                        // 4H
                        $change4h = $row['eth_4h'];
                        $color4h = $this->getColorByChange($change4h);
                        $html[] = "<div style='color: {$color4h}'>4H: {$change4h}%</div>";

                        // 12H
                        $change12h = $row['eth_12h'];
                        $color12h = $this->getColorByChange($change12h);
                        $html[] = "<div style='color: {$color12h}'>12H: {$change12h}%</div>";

                        // 24H
                        $change24h = $row['eth_24h'];
                        $color24h = $this->getColorByChange($change24h);
                        $html[] = "<div style='color: {$color24h}'>24H: {$change24h}%</div>";

                        return implode('', $html);
                    }),

                // Объем
                TD::make('volume_correlation', 'Объем 24H')
                    ->render(function ($row) {
                        $html = [];

                        // BTC Volume
                        $btcVolume = $row['btc_volume_24h'];
                        $btcColor = $this->getColorByChange($btcVolume);
                        $html[] = "<div style='color: {$btcColor}'>BTC: {$btcVolume}%</div>";

                        // ETH Volume
                        $ethVolume = $row['eth_volume_24h'];
                        $ethColor = $this->getColorByChange($ethVolume);
                        $html[] = "<div style='color: {$ethColor}'>ETH: {$ethVolume}%</div>";

                        return implode('', $html);
                    }),
            ]),
        ];
    }

    private function getColorByChange(?float $change): string
    {
        if ($change === null) {
            return 'inherit';
        }

        if ($change > 5) {
            return 'green';
        }

        if ($change < -5) {
            return 'red';
        }

        if ($change > 0) {
            return '#90EE90'; // Light green
        }

        if ($change < 0) {
            return '#FFB6C1'; // Light red
        }

        return 'inherit';
    }
}
