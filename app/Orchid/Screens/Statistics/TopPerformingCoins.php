<?php

namespace App\Orchid\Screens\Statistics;

use App\Orchid\Filters\Statistics\TopPerformingCoins\FiltersLayout;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\Api\TopPerformingCoins as TopPerformingCoinsService;
use Illuminate\Http\Request;
use Orchid\Screen\Action;
use Orchid\Screen\Screen;

class TopPerformingCoins extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Request $request): iterable
    {
        $priceChangePercent = (int)$request->get('price_change_percent', 10);
        $minVolumeDiff = (int)$request->get('volume_diff_percent', 20);

        $result = (new TopPerformingCoinsService())->getTopPerformingCoins($priceChangePercent, $minVolumeDiff);

        $priceData = [];
        $volumeData = [];
        $categories = [];

        foreach ($result as $coin) {
            $categories[] = $coin['symbol'];
            $priceData[] = round($coin['price_change_percent'], 1);
            $volumeData[] = round($coin['volume_diff_percent'], 1);
        }

        return [
            'categories' => $categories,
            'priceData' => $priceData,
            'volumeData' => $volumeData
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Валюты с хорошей динамикой';
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
                    'type' => 'bar',
                    'height' => '150%',
                ],
                'title' => [
                    'text' => 'Сравнение изменений цены и объема',
                    'align' => 'left'
                ],
                'xAxis' => [
                    'categories' => $this->query(request())['categories'],
                    'title' => [
                        'text' => null
                    ],
                    'gridLineWidth' => 1,
                    'lineWidth' => 0
                ],
                'yAxis' => [
                    'min' => 0,
                    'title' => [
                        'text' => 'Процентное изменение',
                        'align' => 'high'
                    ],
                    'labels' => [
                        'overflow' => 'justify',
                        'format' => '{value}%'
                    ],
                    'gridLineWidth' => 0
                ],
                'tooltip' => [
                    'valueSuffix' => '%'
                ],
                'plotOptions' => [
                    'bar' => [
                        'borderRadius' => '50%',
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => '{point.y:.1f}%'
                        ],
                        'groupPadding' => 0.1
                    ]
                ],
                'legend' => [
                    'layout' => 'vertical',
                    'align' => 'right',
                    'verticalAlign' => 'top',
                    'x' => -40,
                    'y' => 80,
                    'floating' => true,
                    'borderWidth' => 1,
                    'backgroundColor' => '#FFFFFF',
                    'shadow' => true
                ],
                'credits' => [
                    'enabled' => false
                ],
                'series' => [
                    [
                        'name' => 'Изменение цены',
                        'data' => $this->query(request())['priceData'],
                        'color' => '#7cb5ec'
                    ],
                    [
                        'name' => 'Изменение объема',
                        'data' => $this->query(request())['volumeData'],
                        'color' => '#434348'
                    ]
                ]
            ]),


        ];
    }
}
