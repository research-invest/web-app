<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Statistics;

use App\Orchid\Filters\Statistics\VolumeByRange\FiltersLayout;

use App\Services\Api\Tickers;
use App\Services\IndexCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Screen;
use App\Orchid\Layouts\Charts\HighchartsChart;

class CompositeIndex extends Screen
{
    public function query(Request $request): iterable
    {
        $currency = $request->get('currency', 'TAOUSDT');
//        $currency = $request->get('currency', 'BTCUSDT');
        $tickerService = new Tickers();
        $indexCalculator = new IndexCalculator();

        // Получаем данные для разных интервалов
        $data3m = $tickerService->getTickers($currency, 180);
        $data15m = $tickerService->getTickers($currency, 900);
        $data1h = $tickerService->getTickers($currency, 3600);

        // Рассчитываем индекс
        $indexData = $indexCalculator->calculateIndex($data3m, $data15m, $data1h);

        // Форматируем данные для графика
        $chartData = array_map(function ($item) {
            return [
                'x' => Carbon::parse($item['timestamp'])->timestamp * 1000,
                'y' => $item['score']
            ];
        }, $indexData);

        return [
            'indexChartData' => [
                [
                    'name' => 'Композитный индекс',
                    'data' => $chartData
                ]
            ],
            'priceData' => [
                [
                    'name' => $currency,
                    'data' => array_map(function ($item) {
                        return [
                            'x' => Carbon::parse($item['timestamp'])->timestamp * 1000,
                            'y' => $item['last_price']
                        ];
                    }, $data3m)
                ]
            ]
        ];
    }

    public function name(): ?string
    {
        return 'Композитный индекс';
    }

    public function layout(): iterable
    {
        return [

            FiltersLayout::class,

            // График цены
            new HighchartsChart([
                'chart' => [
                    'type' => 'line',
                    'zoomType' => 'x'
                ],
                'title' => [
                    'text' => 'Цена актива'
                ],
                'xAxis' => [
                    'type' => 'datetime'
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'Цена'
                    ]
                ],
                'series' => $this->query(request())['priceData'],

                'accessibility' => [
                    'enabled' => false
                ]
            ]),

            // График индекса
            new HighchartsChart([
                'chart' => [
                    'type' => 'line',
                    'zoomType' => 'x'
                ],
                'title' => [
                    'text' => 'Композитный индекс'
                ],
                'xAxis' => [
                    'type' => 'datetime'
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'Значение индекса'
                    ]
                ],
                'plotOptions' => [
                    'series' => [
                        'lineWidth' => 1,
                        'marker' => [
                            'enabled' => false
                        ]
                    ]
                ],
                'series' => $this->query(request())['indexChartData'],

                'accessibility' => [
                    'enabled' => false
                ]
            ])
        ];
    }
}
