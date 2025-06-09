<?php

namespace App\Orchid\Screens\Statistics\Correlation;

use App\Models\CurrencyPrice;
use App\Orchid\Layouts\Charts\HighchartsChart;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class CurrencyCorrelationDetailsScreen extends Screen
{
    public $currency;
    public $chartData;

    public function query(Request $request): array
    {
        $currencyId = $request->route('currency');

        $this->currency = CurrencyPrice::query()
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->firstOrFail();

        $this->chartData = $this->getChartData($currencyId);

        return [
            'currency' => $this->currency,
            'chartData' => $this->chartData
        ];
    }

    public function name(): ?string
    {
        return $this->currency ? "Анализ {$this->currency->symbol}" : 'Анализ корреляции';
    }

    public function description(): ?string
    {
        return 'Детальный анализ движения цены относительно BTC и ETH';
    }

    public function commandBar(): array
    {
        return [
            Link::make('Назад к списку')
                ->route('platform.statistics.crypto-correlation')
                ->icon('arrow-left'),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::block([
                new HighchartsChart([
                    'chart' => [
                        'height' => 600,
                        'type' => 'line',
                        'zoomType' => 'x'
                    ],
                    'title' => [
                        'text' => "Корреляция {$this->currency->symbol} с BTC/ETH"
                    ],
                    'xAxis' => [
                        'type' => 'datetime',
                        'labels' => [
                            'format' => '{value:%d.%m.%Y %H:%M}'
                        ]
                    ],
                    'yAxis' => [
                        [
                            'title' => [
                                'text' => 'Цена'
                            ],
                            'labels' => [
                                'format' => '{value}'
                            ]
                        ],
                        [
                            'title' => [
                                'text' => 'Корреляция (%)'
                            ],
                            'labels' => [
                                'format' => '{value}%'
                            ],
                            'opposite' => true,
                            'plotLines' => [
                                [
                                    'value' => 0,
                                    'color' => 'red',
                                    'dashStyle' => 'shortdash',
                                    'width' => 2,
                                    'label' => [
                                        'text' => 'Нейтральная зона'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'tooltip' => [
                        'shared' => true,
                        'crosshairs' => true,
                        'xDateFormat' => '%d.%m.%Y %H:%M'
                    ],
                    'series' => [
                        [
                            'name' => "Цена {$this->currency->symbol}",
                            'data' => $this->chartData['price'],
                            'yAxis' => 0,
                            'color' => '#3F51B5',
                            'tooltip' => [
                                'valueDecimals' => 8
                            ]
                        ],
                        [
                            'name' => 'Корреляция с BTC',
                            'data' => $this->chartData['btcCorrelation'],
                            'yAxis' => 1,
                            'color' => '#FFC107',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ]
                        ],
                        [
                            'name' => 'Корреляция с ETH',
                            'data' => $this->chartData['ethCorrelation'],
                            'yAxis' => 1,
                            'color' => '#4CAF50',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ]
                        ]
                    ]
                ])
            ])->title('График корреляции')
             ->description('Показывает движение цены и корреляцию с основными криптовалютами'),
        ];
    }

    private function getChartData($currencyId): array
    {
        $historicalData = CurrencyPrice::query()
            ->where('currency_id', $currencyId)
            ->orderBy('created_at')
            ->limit(200)
            ->get();

        $priceData = [];
        $btcCorrelationData = [];
        $ethCorrelationData = [];

        foreach ($historicalData as $data) {
            $timestamp = $data->created_at->timestamp * 1000;

            $priceData[] = [$timestamp, round($data->current_price, 8)];
            $btcCorrelationData[] = [$timestamp, round($data->price_change_vs_btc_24h, 5)];
            $ethCorrelationData[] = [$timestamp, round($data->price_change_vs_eth_24h, 5)];
        }

        return [
            'price' => $priceData,
            'btcCorrelation' => $btcCorrelationData,
            'ethCorrelation' => $ethCorrelationData
        ];
    }
}
