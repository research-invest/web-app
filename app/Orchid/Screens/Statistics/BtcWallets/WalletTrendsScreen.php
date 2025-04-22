<?php


namespace App\Orchid\Screens\Statistics\BtcWallets;

use App\Models\BtcWallets\WalletReport;
use App\Orchid\Layouts\Charts\HighchartsChart;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class WalletTrendsScreen extends Screen
{
    /**
     * @var WalletReport[] Данные отчетов
     */
    public $reports;

    /**
     * @var array Параметры периода
     */
    public $period;

    public function query(Request $request): iterable
    {
        // Период по умолчанию - последние 3 месяца
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subMonths(3);

        // Если передан период через фильтр
        if ($request->has('period')) {
            $dates = $request->get('period');
            $startDate = Carbon::parse($dates['start']);
            $endDate = Carbon::parse($dates['end']);
        }

        // Получаем отчеты за указанный период
        $reports = WalletReport::whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();

        return [
            'reports' => $reports,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Анализ трендов кошельков BTC';
    }

    public function description(): ?string
    {
        return 'Анализ активности китовых кошельков за период времени';
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Обновить')
                ->icon('reload')
                ->rawClick()
                ->method('refresh'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                DateRange::make('period')
                    ->title('Выберите период')
                    ->value($this->period),

                Button::make('Применить')
                    ->icon('filter')
                    ->method('refresh'),
            ]),

            Layout::tabs([
                'Общий тренд активности' => [
                    new HighchartsChart($this->getOverallTrendChart()),
                ],
                'Соотношение роста/падения' => [
                    new HighchartsChart($this->getGrowthVsDropChart()),
                ],
                'Динамика общего баланса' => [
                    new HighchartsChart($this->getTotalBalanceChart()),
                ],
                'Корреляция с ценой BTC' => [
                    new HighchartsChart($this->getPriceCorrelationChart()),
                ],
            ]),

            Layout::view('statistics.btc-wallets.wallet-reports-info'),
        ];
    }

    /**
     * Метод обновления фильтра
     */
    public function refresh(Request $request)
    {
        return redirect()->route('platform.statistics.btc-wallets.trends', ['period' => $request->get('period')]);
    }

    /**
     * График общего тренда активности кошельков
     */
    private function getOverallTrendChart(): array
    {
        $dates = [];
        $growthData = [];
        $dropData = [];

        foreach ($this->reports as $report) {
            $timestamp = $report->report_date->timestamp * 1000;
            $dates[] = $timestamp;
            $growthData[] = [$timestamp, $report->grown_wallets_count];
            $dropData[] = [$timestamp, -$report->dropped_wallets_count]; // Отрицательные для визуализации
        }

        return [
            'chart' => [
                'height' => 500,
                'type' => 'area',
                'zoomType' => 'x'
            ],
            'title' => [
                'text' => 'Общий тренд активности кошельков'
            ],
            'subtitle' => [
                'text' => 'Количество кошельков с изменением баланса >5%'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%d.%m.%Y}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Количество кошельков'
                ]
            ],
            'tooltip' => [
                'shared' => true,
                'crosshairs' => true
            ],
            'series' => [
                [
                    'name' => 'Рост',
                    'data' => $growthData,
                    'color' => '#4CAF50',
                    'type' => 'area',
                    'fillOpacity' => 0.3
                ],
                [
                    'name' => 'Падение',
                    'data' => $dropData,
                    'color' => '#F44336',
                    'type' => 'area',
                    'fillOpacity' => 0.3
                ]
            ]
        ];
    }

    /**
     * График соотношения роста и падения
     */
    private function getGrowthVsDropChart(): array
    {
        $dates = [];
        $ratioData = [];

        foreach ($this->reports as $report) {
            $timestamp = $report->report_date->timestamp * 1000;

            // Рассчитываем соотношение (если есть падение)
            $ratio = $report->dropped_wallets_count > 0
                ? $report->grown_wallets_count / $report->dropped_wallets_count
                : $report->grown_wallets_count;

            $dates[] = $timestamp;
            $ratioData[] = [$timestamp, round($ratio, 2)];
        }

        return [
            'chart' => [
                'height' => 500,
                'type' => 'line',
                'zoomType' => 'x'
            ],
            'title' => [
                'text' => 'Соотношение рост/падение'
            ],
            'subtitle' => [
                'text' => 'Значения > 1 означают преобладание роста над падением'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%d.%m.%Y}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Соотношение'
                ],
                'plotLines' => [
                    [
                        'value' => 1,
                        'color' => 'red',
                        'dashStyle' => 'shortdash',
                        'width' => 2,
                        'label' => [
                            'text' => 'Баланс'
                        ]
                    ]
                ]
            ],
            'tooltip' => [
                'valueSuffix' => ' (рост/падение)'
            ],
            'series' => [
                [
                    'name' => 'Соотношение',
                    'data' => $ratioData,
                    'color' => '#3F51B5'
                ]
            ]
        ];
    }

    /**
     * График динамики общего баланса
     */
    private function getTotalBalanceChart(): array
    {
        $balanceData = [];

        foreach ($this->reports as $report) {
            $timestamp = $report->report_date->timestamp * 1000;
            $balanceData[] = [$timestamp, round($report->total_balance, 2)];
        }

        return [
            'chart' => [
                'height' => 500,
                'type' => 'area',
                'zoomType' => 'x'
            ],
            'title' => [
                'text' => 'Динамика общего баланса кошельков'
            ],
            'subtitle' => [
                'text' => 'Общий объем BTC на отслеживаемых кошельках'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%d.%m.%Y}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Баланс BTC'
                ],
                'labels' => [
                    'format' => '{value} BTC'
                ]
            ],
            'tooltip' => [
                'valueSuffix' => ' BTC'
            ],
            'series' => [
                [
                    'name' => 'Общий баланс',
                    'data' => $balanceData,
                    'color' => '#FF9800',
                    'fillOpacity' => 0.3
                ]
            ]
        ];
    }

    /**
     * График корреляции с ценой BTC
     */
    private function getPriceCorrelationChart(): array
    {
        $ratioData = [];
        $priceData = [];

        foreach ($this->reports as $report) {
            // Пропускаем отчеты без цены
            if (!$report->market_price) {
                continue;
            }

            $timestamp = $report->report_date->timestamp * 1000;

            // Соотношение роста/падения
            $ratio = $report->dropped_wallets_count > 0
                ? $report->grown_wallets_count / $report->dropped_wallets_count
                : $report->grown_wallets_count;

            $ratioData[] = [$timestamp, round($ratio, 2)];
            $priceData[] = [$timestamp, round($report->market_price, 2)];
        }

        return [
            'chart' => [
                'height' => 500,
                'type' => 'line',
                'zoomType' => 'x'
            ],
            'title' => [
                'text' => 'Корреляция активности с ценой BTC'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%d.%m.%Y}'
                ]
            ],
            'yAxis' => [
                [
                    'title' => [
                        'text' => 'Соотношение рост/падение'
                    ],
                    'labels' => [
                        'format' => '{value}'
                    ],
                    'plotLines' => [
                        [
                            'value' => 1,
                            'color' => 'red',
                            'dashStyle' => 'shortdash',
                            'width' => 2
                        ]
                    ]
                ],
                [
                    'title' => [
                        'text' => 'Цена BTC (USD)'
                    ],
                    'labels' => [
                        'format' => '${value}'
                    ],
                    'opposite' => true
                ]
            ],
            'tooltip' => [
                'shared' => true
            ],
            'series' => [
                [
                    'name' => 'Соотношение рост/падение',
                    'data' => $ratioData,
                    'yAxis' => 0,
                    'color' => '#3F51B5'
                ],
                [
                    'name' => 'Цена BTC',
                    'data' => $priceData,
                    'yAxis' => 1,
                    'color' => '#FFC107',
                    'tooltip' => [
                        'valuePrefix' => '$'
                    ]
                ]
            ]
        ];
    }
}
