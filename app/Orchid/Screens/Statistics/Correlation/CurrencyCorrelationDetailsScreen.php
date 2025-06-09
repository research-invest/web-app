<?php

namespace App\Orchid\Screens\Statistics\Correlation;

use App\Helpers\MathHelper;
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
    private $historicalData;

    public function query(Request $request): array
    {
        $currencyId = $request->route('currency');

        $this->currency = CurrencyPrice::query()
            ->where('currency_id', $currencyId)
            ->orderByDesc('id')
            ->firstOrFail();

        $this->loadHistoricalData($currencyId);
        $this->prepareChartData();

        return [
            'currency' => $this->currency,
            'chartData' => $this->chartData
        ];
    }

    private function loadHistoricalData($currencyId): void
    {
        $this->historicalData = CurrencyPrice::query()
            ->where('currency_id', $currencyId)
            ->orderBy('created_at')
            ->limit(200)
            ->get();
    }

    private function prepareChartData(): void
    {
        $this->chartData = [
            'price' => $this->getPriceData(),
            'correlation' => $this->getCorrelationData(),
            'volume' => $this->getVolumeData()
        ];
    }

    private function getPriceData(): array
    {
        $priceData = [];
        foreach ($this->historicalData as $data) {
            $timestamp = $data->created_at->timestamp * 1000;
            $priceData[] = [$timestamp, round($data->current_price, 8)];
        }
        return $priceData;
    }

    private function getCorrelationData(): array
    {
        $btcCorrelation4h = [];
        $btcCorrelation12h = [];
        $btcCorrelation24h = [];
        $btcCorrelationAvg = [];

        $ethCorrelation4h = [];
        $ethCorrelation12h = [];
        $ethCorrelation24h = [];
        $ethCorrelationAvg = [];

        foreach ($this->historicalData as $data) {
            $timestamp = $data->created_at->timestamp * 1000;

            // BTC корреляции
            $btc4h = round($data->price_change_vs_btc_4h, 5);
            $btc12h = round($data->price_change_vs_btc_12h, 5);
            $btc24h = round($data->price_change_vs_btc_24h, 5);

            $btcCorrelation4h[] = [$timestamp, $btc4h];
            $btcCorrelation12h[] = [$timestamp, $btc12h];
            $btcCorrelation24h[] = [$timestamp, $btc24h];

            // Среднее значение BTC
            $btcValues = array_filter([$btc4h, $btc12h, $btc24h], fn($value) => $value !== null);
            $btcAvg = count($btcValues) > 0 ? array_sum($btcValues) / count($btcValues) : null;
            $btcCorrelationAvg[] = [$timestamp, $btcAvg];

            // ETH корреляции
            $eth4h = round($data->price_change_vs_eth_4h, 5);
            $eth12h = round($data->price_change_vs_eth_12h, 5);
            $eth24h = round($data->price_change_vs_eth_24h, 5);

            $ethCorrelation4h[] = [$timestamp, $eth4h];
            $ethCorrelation12h[] = [$timestamp, $eth12h];
            $ethCorrelation24h[] = [$timestamp, $eth24h];

            // Среднее значение ETH
            $ethValues = array_filter([$eth4h, $eth12h, $eth24h], fn($value) => $value !== null);
            $ethAvg = count($ethValues) > 0 ? array_sum($ethValues) / count($ethValues) : null;
            $ethCorrelationAvg[] = [$timestamp, $ethAvg];
        }

        return [
            'btc4h' => $btcCorrelation4h,
            'btc12h' => $btcCorrelation12h,
            'btc24h' => $btcCorrelation24h,
            'btcAvg' => $btcCorrelationAvg,
            'eth4h' => $ethCorrelation4h,
            'eth12h' => $ethCorrelation12h,
            'eth24h' => $ethCorrelation24h,
            'ethAvg' => $ethCorrelationAvg,
        ];
    }

    private function getVolumeData(): array
    {
        $volumeData = [];
        $normalizedVolume = [];
        $avgVolume = 0;

        // Сначала вычисляем среднее значение объема
        $volumes = $this->historicalData->pluck('total_volume')->filter()->toArray();
        if (count($volumes) > 0) {
            $avgVolume = array_sum($volumes) / count($volumes);
        }

        foreach ($this->historicalData as $data) {
            $timestamp = $data->created_at->timestamp * 1000;

            $volumeData[] = [$timestamp, (float)$data->total_volume];

            // Нормализованный объем (отношение к среднему)
            $normalized = $avgVolume > 0 ? ($data->total_volume / $avgVolume) : null;
            $normalizedVolume[] = [$timestamp, $normalized];
        }

        return [
            'volume' => $volumeData,
            'normalized' => $normalizedVolume
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
                new HighchartsChart($this->getCorrelationChartConfig())
            ])->title('График корреляции')
             ->description('Показывает движение цены и корреляцию с основными криптовалютами'),

            Layout::block([
                new HighchartsChart($this->getVolumeChartConfig())
            ])->title('График объемов')
             ->description('Показывает динамику объемов торгов и их отношение к среднему значению'),
        ];
    }

    private function getCorrelationChartConfig(): array
    {
        return [
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
                    'data' => $this->chartData['correlation']['btc4h'],
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
                    'data' => $this->chartData['correlation']['btc12h'],
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
                    'data' => $this->chartData['correlation']['btc24h'],
                    'yAxis' => 1,
                    'color' => '#E65100',
                    'tooltip' => [
                        'valueSuffix' => '%',
                        'valueDecimals' => 2
                    ]
                ],
                [
                    'name' => 'BTC Среднее',
                    'data' => $this->chartData['correlation']['btcAvg'],
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
                    'data' => $this->chartData['correlation']['eth4h'],
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
                    'data' => $this->chartData['correlation']['eth12h'],
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
                    'data' => $this->chartData['correlation']['eth24h'],
                    'yAxis' => 1,
                    'color' => '#00BFA5',
                    'tooltip' => [
                        'valueSuffix' => '%',
                        'valueDecimals' => 2
                    ]
                ],
                [
                    'name' => 'ETH Среднее',
                    'data' => $this->chartData['correlation']['ethAvg'],
                    'yAxis' => 1,
                    'color' => '#4CAF50',
                    'tooltip' => [
                        'valueSuffix' => '%',
                        'valueDecimals' => 2
                    ],
                    'lineWidth' => 3
                ]
            ]
        ];
    }

    private function getVolumeChartConfig(): array
    {
        return [
            'chart' => [
                'height' => 400,
                'type' => 'line',
                'zoomType' => 'x'
            ],
            'title' => [
                'text' => "Объемы торгов {$this->currency->symbol}"
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
                        'text' => 'Объем'
                    ],
                    'labels' => [
                        'formatter' => "function() { return Highcharts.numberFormat(this.value, 0); }"
                    ]
                ],
                [
                    'title' => [
                        'text' => 'Отношение к среднему'
                    ],
                    'labels' => [
                        'format' => '{value}x'
                    ],
                    'opposite' => true,
                    'plotLines' => [
                        [
                            'value' => 1,
                            'color' => '#666',
                            'dashStyle' => 'shortdash',
                            'width' => 2,
                            'label' => [
                                'text' => 'Средний объем'
                            ]
                        ]
                    ]
                ]
            ],
            'tooltip' => [
                'shared' => true,
                'crosshairs' => true,
                'xDateFormat' => '%d.%m.%Y %H:%M',
                'pointFormatter' => "function() {
                    if (this.series.name === 'Объем') {
                        return '<span style=\"color:' + this.color + '\">●</span> ' + this.series.name + ': <b>' + Highcharts.numberFormat(this.y, 0) + '</b><br/>';
                    }
                    return '<span style=\"color:' + this.color + '\">●</span> ' + this.series.name + ': <b>' + Highcharts.numberFormat(this.y, 2) + 'x</b><br/>';
                }"
            ],
            'series' => [
                [
                    'name' => 'Объем',
                    'data' => $this->chartData['volume']['volume'],
                    'yAxis' => 0,
                    'color' => '#7B1FA2',
                    'tooltip' => [
                        'valueDecimals' => 0
                    ]
                ],
                [
                    'name' => 'Нормализованный объем',
                    'data' => $this->chartData['volume']['normalized'],
                    'yAxis' => 1,
                    'color' => '#E91E63',
                    'tooltip' => [
                        'valueSuffix' => 'x',
                        'valueDecimals' => 2
                    ]
                ]
            ]
        ];
    }
}
