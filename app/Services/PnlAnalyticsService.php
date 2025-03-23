<?php

namespace App\Services;

use App\Helpers\MathHelper;
use App\Helpers\UserHelper;
use App\Models\Trade;
use App\Models\TradePeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PnlAnalyticsService
{
    private const int DAILY_TARGET = 100;
    private const int DAILY_TARGET_WEEKEND = 50;

    public function __construct(protected ?TradePeriod $period = null)
    {
    }

    public function getPlanFactChartData(): array
    {
        $firstTradeDate = DB::table('trades')
            ->where('user_id', UserHelper::getId())
//            ->where('status', 'closed')
            ->when(
                $this->period,
                fn($query) => $query->where('trade_period_id', $this->period?->id),
            )
            ->whereNotNull('closed_at')
            ->min('closed_at');

        // Если нет закрытых сделок, возвращаем пустой график
        if (!$firstTradeDate) {
            return [
                'summary' => [
                    'tradingDays' => 0,
                    'totalDays' => 0,
                    'totalPnl' => 0,
                    'targetPnl' => 0,
                    'difference' => 0
                ],
                'graph' => [
                    'chart' => [
                        'type' => 'line'
                    ],
                    'title' => [
                        'text' => 'Плановый vs Фактический P&L'
                    ],
                    'xAxis' => [
                        'categories' => []
                    ],
                    'yAxis' => [
                        'title' => [
                            'text' => 'USD'
                        ]
                    ]
                ],
            ];
        }

        $startDate = Carbon::parse($this->period->start_date)->startOfDay();
        $endDate = $this->period->is_active ? Carbon::now() : Carbon::parse($this->period->end_date)->endOfDay();

        // Получаем фактический PNL по закрытым сделкам
        $actualPnl = DB::table('trades')
            ->select(
                DB::raw('DATE(closed_at) as date'),
                DB::raw('SUM(realized_pnl) as daily_pnl')
            )
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->where('user_id', UserHelper::getId())
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
            ->where('is_fake', 0)
            ->whereBetween('closed_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn($item) => (float)$item->daily_pnl)
            ->toArray();

        // Формируем данные для графика
        $labels = [];
        $actualData = [];
        $plannedData = [0];
        $cumulativeActual = 0;
        $totalDays = 0;

        $dailyTargetValue = when($this->period?->daily_target, $this->period?->daily_target, self::DAILY_TARGET);
        $weekendTargetValue = when($this->period?->weekend_target, $this->period?->weekend_target, self::DAILY_TARGET_WEEKEND);

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $totalDays++;

            $labels[] = $date->format('d.m');

            // Фактический PNL
            $cumulativeActual += ($actualPnl[$dateStr] ?? 0);
            $actualData[] = round($cumulativeActual, 2);

            // Плановый PNL с учетом выходных
            $dailyTarget = $date->isWeekend() ? $weekendTargetValue : $dailyTargetValue;

            $plannedData[] = round($plannedData[count($plannedData) - 1] + $dailyTarget, 2);
        }

        $targetPnl = round($this->calculateTargetPnl($startDate, $endDate, $dailyTargetValue, $weekendTargetValue), 2);

        return [
            'summary' => [
                'totalDays' => $totalDays,
                'tradingDays' => count($actualPnl),
                'totalPnl' => round($cumulativeActual, 2),
                'targetPnl' => $targetPnl,
                'difference' => round($cumulativeActual - $targetPnl, 2)
            ],

            'graph' => [
                'chart' => [
                    'type' => 'line'
                ],
                'title' => [
                    'text' => 'Плановый vs Фактический P&L'
                ],
                'xAxis' => [
                    'categories' => $labels
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'USD'
                    ]
                ],
                'series' => [
                    [
                        'name' => 'Плановый P&L',
                        'data' => $plannedData
                    ],
                    [
                        'name' => 'Фактический P&L',
                        'data' => $actualData
                    ]
                ],
                'tooltip' => [
                    'valuePrefix' => '',
                    'valueSuffix' => ' USD'
                ]
            ],
        ];
    }

    public function getDealTypeChartData(): array
    {
        $trades = DB::table('trades')
            ->select(
                'position_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(realized_pnl) as total_pnl')
            )
            ->where('user_id', UserHelper::getId())
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->when(
                $this->period,
                fn($query) => $query->where('trade_period_id', $this->period->id)
            )
            ->where('is_fake', 0)
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
            ->groupBy('position_type')
            ->get();

        $data = [];
        foreach ($trades as $trade) {
            $data[] = [
                'name' => $trade->position_type === 'long' ? 'Лонг' : 'Шорт',
                'y' => $trade->count,
                'pnl' => round($trade->total_pnl, 2)
            ];
        }

        return [
            'graph' => [
                'chart' => [
                    'type' => 'pie'
                ],
                'title' => [
                    'text' => 'Распределение сделок по типам'
                ],
                'tooltip' => [
                    'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b><br>Количество: <b>{point.y}</b><br>P&L: <b>${point.pnl}</b>'
                ],
                'plotOptions' => [
                    'pie' => [
                        'allowPointSelect' => true,
                        'cursor' => 'pointer',
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => '<b>{point.name}</b>: {point.percentage:.1f}%'
                        ]
                    ]
                ],
                'series' => [[
                    'name' => 'Тип сделок',
                    'colorByPoint' => true,
                    'data' => $data
                ]]
            ]
        ];
    }

    public function getCurrencyTypeChartData(): array
    {
        $trades = DB::table('trades')
            ->select(
                'currency_id',
                DB::raw('currencies.name as name'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(realized_pnl) as total_pnl')
            )
            ->join('currencies', 'currencies.id', '=', 'trades.currency_id')
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->where('user_id', UserHelper::getId())
            ->when(
                $this->period,
                fn($query) => $query->where('trade_period_id', $this->period->id)
            )
            ->where('is_fake', 0)
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
            ->groupBy('currency_id', 'currencies.name')
            ->get();

        $data = [];
        foreach ($trades as $trade) {
            $data[] = [
                'name' => $trade->name,
                'y' => $trade->count,
                'pnl' => round($trade->total_pnl, 2)
            ];
        }

        return [
            'graph' => [
                'chart' => [
                    'type' => 'pie'
                ],
                'title' => [
                    'text' => 'Распределение сделок по валютам'
                ],
                'tooltip' => [
                    'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b><br>Количество: <b>{point.y}</b><br>P&L: <b>${point.pnl}</b>'
                ],
                'plotOptions' => [
                    'pie' => [
                        'allowPointSelect' => true,
                        'cursor' => 'pointer',
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => '<b>{point.name}</b>: {point.percentage:.1f}%'
                        ]
                    ]
                ],
                'series' => [[
                    'name' => 'Валюты',
                    'colorByPoint' => true,
                    'data' => $data
                ]]
            ]
        ];
    }

    private function calculateTargetPnl(Carbon $startDate, Carbon $endDate, int $dailyTarget, int $weekendTarget): float
    {
        $targetPnl = 0;
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $targetPnl += $date->isWeekend() ? $weekendTarget : $dailyTarget;
        }
        return $targetPnl;
    }

    public function getPnlHistoryChart(Trade $trade)
    {
        $unrealizedData = $roeData = $labels = [];
        foreach ($trade->pnlHistory as $history) {
            $unrealizedData[] = (float)$history->unrealized_pnl;
            $roeData[] = (float)$history->roe;
            $labels[] = MathHelper::formatNumber($history->price);
        }

        return [
            'chart' => [
                'type' => 'line',
//                'type' => 'column',
            ],
            'title' => [
                'text' => 'История P&L'
            ],
            'xAxis' => [
                'categories' => $labels
            ],
            'yAxis' => [
                'title' => [
                    'text' => ''
                ]
            ],

            'rangeSelector' => [
                'enabled' => true,
                'buttons' => [
                    [
                        'type' => 'hour',
                        'count' => 1,
                        'text' => '1ч'
                    ],
                    [
                        'type' => 'hour',
                        'count' => 4,
                        'text' => '4ч'
                    ],
                    [
                        'type' => 'day',
                        'count' => 1,
                        'text' => '1д'
                    ],
                    [
                        'type' => 'all',
                        'text' => 'Все'
                    ]
                ]
            ],
            'navigator' => [
                'enabled' => true,
            ],
            'scrollbar' => [
                'enabled' => true
            ],
            'series' => [
                [
                    'name' => 'Нереализованный P&L',
                    'data' => $unrealizedData
                ],
                [
                    'name' => 'roe',
                    'data' => $roeData
                ],
            ],
            'tooltip' => [
                'valuePrefix' => '',
                'valueSuffix' => ' '
            ]
        ];
    }

    public function getPnlHistoryVolumeChart(Trade $trade)
    {
        if ($trade->pnlHistory->isEmpty()) {
            return [];
        }

        $volumesData = $volumesDataBtc = $volumesDataEth = $labels = [];
        $firstVolume = (float)$trade->pnlHistory->first()->volume ?: 1;
        $firstVolumeBtc = (float)$trade->pnlHistory->first()->volume_btc ?: 1;
        $firstVolumeEth = (float)$trade->pnlHistory->first()->volume_eth ?: 1;

        foreach ($trade->pnlHistory as $history) {
            $volumesData[] = ((float)$history->volume / $firstVolume) * 100;
            $volumesDataBtc[] = ((float)$history->volume_btc / $firstVolumeBtc) * 100;
            $volumesDataEth[] = ((float)$history->volume_eth / $firstVolumeEth) * 100;

//            $labels[] = sprintf(
//                "Price: %s | Vol: %s | BTC: %s | ETH: %s",
//                MathHelper::formatNumber($history->price),
//                MathHelper::formatNumber($history->volume),
//                MathHelper::formatNumber($history->volume_btc),
//                MathHelper::formatNumber($history->volume_eth)
//            );

            $labels[] = MathHelper::formatNumber($history->price);
        }

        return [
            'chart' => [
                'type' => 'line'
            ],
            'title' => [
                'text' => 'Торговый объем'
            ],
            'xAxis' => [
                'categories' => $labels
            ],
            'yAxis' => [
                'title' => [
                    'text' => ''
                ]
            ],
            'navigator' => [
                'enabled' => true,
            ],
            'scrollbar' => [
                'enabled' => true
            ],
            'series' => [
                [
                    'name' => 'Торговый объем ' . $trade->currency->name,
                    'data' => $volumesData
                ],
                [
                    'name' => 'Торговый объем BTC',
                    'data' => $volumesDataBtc
                ],
                [
                    'name' => 'Торговый объем ETH',
                    'data' => $volumesDataEth
                ],
            ],
            'tooltip' => [
                'valuePrefix' => '',
                'valueSuffix' => ' '
            ]
        ];
    }

    public function getPnlHistoryFundingRateChart(Trade $trade)
    {
        $fundingRatesData = $labels = [];
        foreach ($trade->pnlHistory as $history) {
            $fundingRatesData[] = (float)$history->funding_rate;
            $labels[] = MathHelper::formatNumber($history->price);
        }

        return [
            'chart' => [
                'type' => 'line'
            ],
            'title' => [
                'text' => 'Funding rates'
            ],
            'xAxis' => [
                'categories' => $labels
            ],
            'yAxis' => [
                'title' => [
                    'text' => ''
                ]
            ],
            'navigator' => [
                'enabled' => true,
            ],
            'scrollbar' => [
                'enabled' => true
            ],
            'series' => [
                [
                    'name' => 'Funding Rates',
                    'data' => $fundingRatesData
                ],
            ],
            'tooltip' => [
                'valuePrefix' => '',
                'valueSuffix' => ' '
            ]
        ];
    }

    public function getTopProfitableTradesChart(): array
    {
        // выбираем топ-10 прибыльных сделок
        $topTradesIds = DB::table('trades')
            ->select('id')
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->when(
                $this->period,
                fn($query) => $query->where('trade_period_id', $this->period->id)
            )
            ->where('user_id', UserHelper::getId())
            ->where('is_fake', 0)
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
            ->where('realized_pnl', '>', 0)
            ->orderBy('realized_pnl', 'desc')
            ->limit(10)
            ->pluck('id');

        $trades = DB::table('trades')
            ->select(
                'position_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(realized_pnl) as total_pnl')
            )
            ->whereIn('id', $topTradesIds)
            ->groupBy('position_type')
            ->get();

        $data = [];
        foreach ($trades as $trade) {
            $data[] = [
                'name' => $trade->position_type === 'long' ? 'Лонг' : 'Шорт',
                'y' => $trade->count,
                'pnl' => round($trade->total_pnl, 2),
                'avgPnl' => round($trade->total_pnl / $trade->count, 2)
            ];
        }

        return [
            'graph' => [
                'chart' => [
                    'type' => 'pie'
                ],
                'title' => [
                    'text' => 'Топ-10 прибыльных сделок по типам'
                ],
                'tooltip' => [
                    'pointFormat' => '{series.name}: <b>{point.percentage:.1f}%</b><br>' .
                        'Количество: <b>{point.y}</b><br>' .
                        'Общий P&L: <b>${point.pnl}</b><br>' .
                        'Средний P&L: <b>${point.avgPnl}</b>'
                ],
                'plotOptions' => [
                    'pie' => [
                        'allowPointSelect' => true,
                        'cursor' => 'pointer',
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => '<b>{point.name}</b>: {point.percentage:.1f}%'
                        ]
                    ]
                ],
                'series' => [[
                    'name' => 'Тип сделок',
                    'colorByPoint' => true,
                    'data' => $data
                ]]
            ]
        ];
    }

    public function getTradesDurationChart(): array
    {
        // Выбираем топ-50 прибыльных сделок
        $trades = DB::table('trades')
            ->select(
                DB::raw('TIMESTAMPDIFF(HOUR, created_at, closed_at) as duration'),
                'realized_pnl'
            )
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->where('user_id', UserHelper::getId())
            ->when(
                $this->period,
                fn($query) => $query->where('trade_period_id', $this->period->id)
            )
            ->where('is_fake', 0)
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
            ->where('realized_pnl', '>', 0)
            ->orderBy('realized_pnl', 'desc')
            ->limit(50)
            ->get();

        // Определяем интервалы
        $intervals = [
            '< 1 часа' => 0,
            '1-6 часов' => 0,
            '6-24 часа' => 0,
            '1-3 дня' => 0,
            '> 3 дней' => 0
        ];

        $totalPnlByInterval = [
            '< 1 часа' => 0,
            '1-6 часов' => 0,
            '6-24 часа' => 0,
            '1-3 дня' => 0,
            '> 3 дней' => 0
        ];

        foreach ($trades as $trade) {
            $duration = $trade->duration;
            $pnl = $trade->realized_pnl;

            if ($duration < 1) {
                $intervals['< 1 часа']++;
                $totalPnlByInterval['< 1 часа'] += $pnl;
            } elseif ($duration < 6) {
                $intervals['1-6 часов']++;
                $totalPnlByInterval['1-6 часов'] += $pnl;
            } elseif ($duration < 24) {
                $intervals['6-24 часа']++;
                $totalPnlByInterval['6-24 часа'] += $pnl;
            } elseif ($duration < 72) {
                $intervals['1-3 дня']++;
                $totalPnlByInterval['1-3 дня'] += $pnl;
            } else {
                $intervals['> 3 дней']++;
                $totalPnlByInterval['> 3 дней'] += $pnl;
            }
        }

        $data = [];
        foreach ($intervals as $name => $count) {
            if ($count > 0) {
                $data[] = [
                    'name' => $name,
                    'y' => $count,
                    'pnl' => round($totalPnlByInterval[$name], 2),
                    'avgPnl' => $count > 0 ? round($totalPnlByInterval[$name] / $count, 2) : 0
                ];
            }
        }

        return [
            'graph' => [
                'chart' => [
                    'type' => 'column'
                ],
                'title' => [
                    'text' => 'Распределение длительности прибыльных сделок'
                ],
                'xAxis' => [
                    'type' => 'category'
                ],
                'yAxis' => [
                    'title' => [
                        'text' => 'Количество сделок'
                    ]
                ],
                'tooltip' => [
                    'headerFormat' => '<span style="font-size:11px">{series.name}</span><br>',
                    'pointFormat' => '<span style="color:{point.color}">{point.name}</span>: <b>{point.y}</b> сделок<br>' .
                        'Общий P&L: <b>${point.pnl}</b><br>' .
                        'Средний P&L: <b>${point.avgPnl}</b>'
                ],
                'series' => [[
                    'name' => 'Длительность сделок',
                    'colorByPoint' => true,
                    'data' => $data
                ]]
            ]
        ];
    }
}
