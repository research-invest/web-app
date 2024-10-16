<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Statistics;

use App\Orchid\Filters\Statistics\FiltersLayout;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\Api\Tickers;
use Orchid\Screen\Action;
use Orchid\Screen\Screen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Helpers\MathHelper;

class Normalize extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $currencies = $request->get('currencies', ['TAOUSDT']);
        $interval = (int)$request->get('interval', 60);
        $tickerService = new Tickers();
        $priceChartData = [];
        $volumeChartData = [];

        foreach ($currencies as $currency) {
            $result = $tickerService->getTickers($currency, $interval);
            $data = collect($result);

            if ($data->isEmpty()) {
                continue;
            }

            $initialPrice = $data->first()['last_price'];
            $initialVolume = $data->first()['volume'];

            $normalizedPriceData = $data->map(function ($item) use ($initialPrice) {
                $price = $item['last_price'];
                $normalizedPrice = ($price - $initialPrice) / $initialPrice * 100;

                return [
                    'x' => Carbon::parse($item['timestamp'])->timestamp * 1000,
                    'y' => round($normalizedPrice, 2),
                    'price' => MathHelper::formatNumber($price)
                ];
            })->values()->toArray();

            $normalizedVolumeData = $data->map(function ($item) use ($initialVolume) {
                $volume = $item['volume'];
                $normalizedVolume = ($volume - $initialVolume) / $initialVolume * 100;

                return [
                    'x' => Carbon::parse($item['timestamp'])->timestamp * 1000,
                    'y' => round($normalizedVolume, 2),
                    'volume' => MathHelper::formatNumber($volume)
                ];
            })->values()->toArray();

            $priceChartData[] = [
                'name' => $currency,
                'data' => $normalizedPriceData
            ];

            $volumeChartData[] = [
                'name' => $currency,
                'data' => $normalizedVolumeData
            ];
        }

        return [
            'priceChartData' => $priceChartData,
            'volumeChartData' => $volumeChartData,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Нормализация';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return '';
    }

    public function permission(): ?iterable
    {
        return [
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return Action[]
     */
    public function commandBar(): iterable
    {
        return [
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            FiltersLayout::class,

            new HighchartsChart([
                'chart' => [
                    'type' => 'line',
                    'zoomType' => 'x'
                ],
                'title' => [
                    'text' => 'Нормализованные курсы валют'
                ],
                'xAxis' => [
                    'type' => 'datetime'
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'Процентное изменение цены'
                    ],
                    'labels' => [
                        'format' => '{value}%'
                    ]
                ],
                'tooltip' => [
                    'pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y:.2f}%</b> (Цена: {point.price})<br/>',
                    'valueDecimals' => 2,
                    'split' => true
                ],
                'plotOptions' => [
                    'series' => [
                        'lineWidth' => 1,
                        'marker' => [
                            'enabled' => false
                        ],
                        'states' => [
                            'hover' => [
                                'lineWidth' => 1
                            ]
                        ],
                        'connectNulls' => false
                    ]
                ],
                'series' => $this->query(request())['priceChartData']
            ]),

            new HighchartsChart([
                'chart' => [
                    'type' => 'line',
                    'zoomType' => 'x'
                ],
                'title' => [
                    'text' => 'Нормализованные объемы торгов'
                ],
                'xAxis' => [
                    'type' => 'datetime'
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'Процентное изменение объема'
                    ],
                    'labels' => [
                        'format' => '{value}%'
                    ]
                ],
                'tooltip' => [
                    'pointFormat' => '<span style="color:{series.color}">{series.name}</span>: <b>{point.y:.2f}%</b> (Объем: {point.volume})<br/>',
                    'valueDecimals' => 2,
                    'split' => true
                ],
                'plotOptions' => [
                    'series' => [
                        'lineWidth' => 1,
                        'marker' => [
                            'enabled' => false
                        ],
                        'states' => [
                            'hover' => [
                                'lineWidth' => 1
                            ]
                        ],
                        'connectNulls' => false
                    ]
                ],
                'series' => $this->query(request())['volumeChartData']
            ]),
        ];
    }
}
