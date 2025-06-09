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
                        // BTC корреляции
                        [
                            'name' => 'BTC 4H',
                            'data' => $this->chartData['btcCorrelation4h'],
                            'yAxis' => 1,
                            'color' => '#FFA000',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'dashStyle' => 'ShortDash'
                        ],
                        [
                            'name' => 'BTC 12H',
                            'data' => $this->chartData['btcCorrelation12h'],
                            'yAxis' => 1,
                            'color' => '#FF6F00',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'dashStyle' => 'ShortDot'
                        ],
                        [
                            'name' => 'BTC 24H',
                            'data' => $this->chartData['btcCorrelation24h'],
                            'yAxis' => 1,
                            'color' => '#E65100',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ]
                        ],
                        [
                            'name' => 'BTC Среднее',
                            'data' => $this->chartData['btcCorrelationAvg'],
                            'yAxis' => 1,
                            'color' => '#FFC107',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'lineWidth' => 3
                        ],
                        // ETH корреляции
                        [
                            'name' => 'ETH 4H',
                            'data' => $this->chartData['ethCorrelation4h'],
                            'yAxis' => 1,
                            'color' => '#00C853',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'dashStyle' => 'ShortDash'
                        ],
                        [
                            'name' => 'ETH 12H',
                            'data' => $this->chartData['ethCorrelation12h'],
                            'yAxis' => 1,
                            'color' => '#00B8D4',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'dashStyle' => 'ShortDot'
                        ],
                        [
                            'name' => 'ETH 24H',
                            'data' => $this->chartData['ethCorrelation24h'],
                            'yAxis' => 1,
                            'color' => '#00BFA5',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ]
                        ],
                        [
                            'name' => 'ETH Среднее',
                            'data' => $this->chartData['ethCorrelationAvg'],
                            'yAxis' => 1,
                            'color' => '#4CAF50',
                            'tooltip' => [
                                'valueSuffix' => '%',
                                'valueDecimals' => 2
                            ],
                            'lineWidth' => 3
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
        // BTC корреляции
        $btcCorrelation4h = [];
        $btcCorrelation12h = [];
        $btcCorrelation24h = [];
        $btcCorrelationAvg = [];
        
        // ETH корреляции
        $ethCorrelation4h = [];
        $ethCorrelation12h = [];
        $ethCorrelation24h = [];
        $ethCorrelationAvg = [];

        foreach ($historicalData as $data) {
            $timestamp = $data->created_at->timestamp * 1000;

            // Цена
            $priceData[] = [$timestamp, round($data->current_price, 8)];
            
            // BTC корреляции
            $btc4h = round($data->price_change_vs_btc_4h, 5);
            $btc12h = round($data->price_change_vs_btc_12h, 5);
            $btc24h = round($data->price_change_vs_btc_24h, 5);
            
            $btcCorrelation4h[] = [$timestamp, $btc4h];
            $btcCorrelation12h[] = [$timestamp, $btc12h];
            $btcCorrelation24h[] = [$timestamp, $btc24h];
            
            // Среднее значение BTC (исключаем null значения)
            $btcValues = array_filter([$btc4h, $btc12h, $btc24h], function($value) {
                return $value !== null;
            });
            $btcAvg = count($btcValues) > 0 ? array_sum($btcValues) / count($btcValues) : null;
            $btcCorrelationAvg[] = [$timestamp, $btcAvg];
            
            // ETH корреляции
            $eth4h = round($data->price_change_vs_eth_4h, 5);
            $eth12h = round($data->price_change_vs_eth_12h, 5);
            $eth24h = round($data->price_change_vs_eth_24h, 5);
            
            $ethCorrelation4h[] = [$timestamp, $eth4h];
            $ethCorrelation12h[] = [$timestamp, $eth12h];
            $ethCorrelation24h[] = [$timestamp, $eth24h];
            
            // Среднее значение ETH (исключаем null значения)
            $ethValues = array_filter([$eth4h, $eth12h, $eth24h], function($value) {
                return $value !== null;
            });
            $ethAvg = count($ethValues) > 0 ? array_sum($ethValues) / count($ethValues) : null;
            $ethCorrelationAvg[] = [$timestamp, $ethAvg];
        }

        return [
            'price' => $priceData,
            // BTC данные
            'btcCorrelation4h' => $btcCorrelation4h,
            'btcCorrelation12h' => $btcCorrelation12h,
            'btcCorrelation24h' => $btcCorrelation24h,
            'btcCorrelationAvg' => $btcCorrelationAvg,
            // ETH данные
            'ethCorrelation4h' => $ethCorrelation4h,
            'ethCorrelation12h' => $ethCorrelation12h,
            'ethCorrelation24h' => $ethCorrelation24h,
            'ethCorrelationAvg' => $ethCorrelationAvg
        ];
    }
}
