<?php

namespace App\Orchid\Screens\Statistics;

use App\Orchid\Filters\Statistics\VolumeByRange\FiltersLayout;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\Api\TopPerformingCoins as TopPerformingCoinsService;
use App\Services\Api\VolumeRange;
use Illuminate\Http\Request;
use Orchid\Screen\Action;
use Orchid\Screen\Screen;

class VolumeByRange extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $currency = $request->get('currency', 'TAOUSDT');
        $interval = (int)$request->get('interval', 20);

        $result = (new VolumeRange())->getVolumeByRange($currency, $interval);

        $categories = [];
        $volumeData = [];
        $tradesData = [];

        foreach ($result as $item) {
            $categories[] = "{$item['price_start']} - {$item['price_end']}";
            $volumeData[] = round($item['total_volume'] / 1000000, 2); // Конвертируем в миллионы
            $tradesData[] = $item['trades_count'];
        }

        return [
            'categories' => $categories,
            'volumeData' => $volumeData,
            'tradesData' => $tradesData
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Объемы по диапазону цены';
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
                    'height' => 600,
                    'type' => 'column'
                ],
                'title' => [
                    'text' => 'Объем торгов по ценовым диапазонам',
                    'align' => 'left'
                ],
                'xAxis' => [
                    'categories' => $this->query(request())['categories'],
                    'title' => [
                        'text' => 'Ценовой диапазон'
                    ],
                    'gridLineWidth' => 1,
                    'lineWidth' => 0
                ],
                'yAxis' => [
                    [
                        'title' => [
                            'text' => 'Объем (млн)',
                            'align' => 'high'
                        ],
                        'labels' => [
                            'format' => '{value}M'
                        ],
                        'gridLineWidth' => 0
                    ],
                    [
                        'title' => [
                            'text' => 'Количество сделок',
                            'align' => 'high'
                        ],
                        'opposite' => true,
                        'gridLineWidth' => 0
                    ]
                ],
                'tooltip' => [
                    'shared' => true
                ],
                'plotOptions' => [
                    'column' => [
                        'borderRadius' => 3,
                        'dataLabels' => [
                            'enabled' => true,
                        ],
                    ]
                ],
                'legend' => [
                    'align' => 'right',
                    'verticalAlign' => 'top',
                    'floating' => true,
                    'backgroundColor' => '#FFFFFF',
                    'shadow' => true
                ],
                'credits' => [
                    'enabled' => false
                ],
                'series' => [
                    [
                        'name' => 'Объем',
                        'data' => $this->query(request())['volumeData'],
                        'color' => '#7cb5ec',
                        'tooltip' => [
                            'valueSuffix' => 'M'
                        ]
                    ],
                    [
                        'name' => 'Количество сделок',
                        'data' => $this->query(request())['tradesData'],
                        'color' => '#434348',
                        'yAxis' => 1
                    ]
                ]
            ]),
        ];
    }
}
