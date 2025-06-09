<?php

namespace App\Orchid\Screens\Statistics\Correlation;

use App\Helpers\MathHelper;
use App\Models\CurrencyPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class CryptoCorrelationScreen extends Screen
{
    public function query(Request $request): array
    {
        $latestIds = CurrencyPrice::query()
            ->select('currency_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('currency_id');

        $query = CurrencyPrice::query()
            ->joinSub($latestIds, 'latest_prices', function ($join) {
                $join->on('currencies_prices.id', '=', 'latest_prices.latest_id');
            })
            ->orderByDesc('total_volume');

        $currencies = $query->paginate(20)
            ->through(function (CurrencyPrice $price) {
                return [
                    'id' => $price->id,
                    'currency_id' => $price->currency_id,
                    'code' => $price->symbol,
                    'name' => $price->name,
                    'current_price' => $price->current_price,
                    'market_cap' => $price->market_cap,
                    'volume' => $price->total_volume,

                    // Изменения относительно BTC
                    'btc_4h' => $price->price_change_vs_btc_4h,
                    'btc_12h' => $price->price_change_vs_btc_12h,
                    'btc_24h' => $price->price_change_vs_btc_24h,
                    'btc_volume_24h' => $price->volume_change_vs_btc_24h,

                    // Изменения относительно ETH
                    'eth_4h' => $price->price_change_vs_eth_4h,
                    'eth_12h' => $price->price_change_vs_eth_12h,
                    'eth_24h' => $price->price_change_vs_eth_24h,
                    'eth_volume_24h' => $price->volume_change_vs_eth_24h,
                ];
            });

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
                    ->render(function ($row) {
                        return Link::make($row['code'])
                            ->rawClick()
                            ->route('platform.statistics.crypto-correlation.details', [
                                'currency' => $row['currency_id']
                            ]);
                    }),

                TD::make('current_price', 'Цена')
                    ->sort()
                    ->render(fn($row) => MathHelper::humanNumber($row['current_price'])),

                TD::make('market_cap', 'Капитализация')
                    ->sort()
                    ->render(fn($row) => MathHelper::humanNumber($row['market_cap'])),

                // BTC корреляция
                TD::make('btc_correlation', 'BTC корреляция')
                    ->render(function ($row) {
                        $html = [];

                        // 4H
                        $change4h = $row['btc_4h'];
                        $color4h = $this->getColorByChange($change4h);
                        $html[] = "<div class='p-1 mb-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color4h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>4H: " .
                            ($change4h !== null ? number_format($change4h, 4) . '%' : '-') . "</div>";

                        // 12H
                        $change12h = $row['btc_12h'];
                        $color12h = $this->getColorByChange($change12h);
                        $html[] = "<div class='p-1 mb-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color12h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>12H: " .
                            ($change12h !== null ? number_format($change12h, 4) . '%' : '-') . "</div>";

                        // 24H
                        $change24h = $row['btc_24h'];
                        $color24h = $this->getColorByChange($change24h);
                        $html[] = "<div class='p-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color24h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>24H: " .
                            ($change24h !== null ? number_format($change24h, 4) . '%' : '-') . "</div>";

                        return implode('', $html);
                    }),

                // ETH корреляция
                TD::make('eth_correlation', 'ETH корреляция')
                    ->render(function ($row) {
                        $html = [];

                        // 4H
                        $change4h = $row['eth_4h'];
                        $color4h = $this->getColorByChange($change4h);
                        $html[] = "<div class='p-1 mb-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color4h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>4H: " .
                            ($change4h !== null ? number_format($change4h, 4) . '%' : '-') . "</div>";

                        // 12H
                        $change12h = $row['eth_12h'];
                        $color12h = $this->getColorByChange($change12h);
                        $html[] = "<div class='p-1 mb-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color12h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>12H: " .
                            ($change12h !== null ? number_format($change12h, 4) . '%' : '-') . "</div>";

                        // 24H
                        $change24h = $row['eth_24h'];
                        $color24h = $this->getColorByChange($change24h);
                        $html[] = "<div class='p-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$color24h}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>24H: " .
                            ($change24h !== null ? number_format($change24h, 4) . '%' : '-') . "</div>";

                        return implode('', $html);
                    }),

                // Объем
                TD::make('volume_correlation', 'Объем 24H')
                    ->render(function ($row) {
                        $html = [];

                        // BTC Volume
                        $btcVolume = $row['btc_volume_24h'];
                        $btcColor = $this->getColorByChange($btcVolume);
                        $html[] = "<div class='p-1 mb-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$btcColor}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>BTC: " .
                            ($btcVolume !== null ? number_format($btcVolume, 4) . '%' : '-') . "</div>";

                        // ETH Volume
                        $ethVolume = $row['eth_volume_24h'];
                        $ethColor = $this->getColorByChange($ethVolume);
                        $html[] = "<div class='p-1 rounded font-weight-bold' style='background: rgba(0,0,0,0.05); color: {$ethColor}; text-shadow: 0 0 1px rgba(255,255,255,0.5);'>ETH: " .
                            ($ethVolume !== null ? number_format($ethVolume, 4) . '%' : '-') . "</div>";

                        return implode('', $html);
                    }),
            ])
        ];
    }

    private function getColorByChange(?float $change): string
    {
        if ($change === null) {
            return '#666666';  // Темно-серый для null значений
        }

        if ($change > 5) {
            return '#00b300';  // Насыщенный зеленый для сильного роста
        }

        if ($change < -5) {
            return '#cc0000';  // Насыщенный красный для сильного падения
        }

        if ($change > 0) {
            return '#2d862d';  // Умеренный зеленый для роста
        }

        if ($change < 0) {
            return '#b30000';  // Умеренный красный для падения
        }

        return '#666666';  // Темно-серый для нулевых значений
    }
}
