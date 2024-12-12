<?php

namespace App\Services;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PnlAnalyticsService
{
    private const int DAILY_TARGET = 100;
    private const int DAILY_TARGET_WEEKEND = 50;

    public function __construct(protected ?int $periodId = null)
    {

    }
    public function getPlanFactChartData(): array
    {
        $firstTradeDate = DB::table('trades')
//            ->where('status', 'closed')
            ->when(
                $this->periodId,
                fn($query) => $query->where('trade_period_id', $this->periodId),
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

        $startDate = Carbon::parse($firstTradeDate)->startOfDay();
        $endDate = Carbon::now();

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
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
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

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $totalDays++;

            $labels[] = $date->format('d.m');

            // Фактический PNL
            $cumulativeActual += ($actualPnl[$dateStr] ?? 0);
            $actualData[] = round($cumulativeActual, 2);

            // Плановый PNL с учетом выходных
            $dailyTarget = $date->isWeekend() ? self::DAILY_TARGET_WEEKEND : self::DAILY_TARGET;
            $plannedData[] = round($plannedData[count($plannedData) - 1] + $dailyTarget, 2);
        }

        $targetPnl = round($this->calculateTargetPnl($startDate, $endDate), 2);

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
            ->whereIn('status', [
                Trade::STATUS_CLOSED,
                Trade::STATUS_LIQUIDATED,
            ])
            ->when(
                $this->periodId,
                fn($query) => $query->where('trade_period_id', $this->periodId)
            )
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

    private function calculateTargetPnl(Carbon $startDate, Carbon $endDate): float
    {
        $targetPnl = 0;
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $targetPnl += $date->isWeekend() ? 50 : 100;
        }
        return $targetPnl;
    }

    public function getPnlHistoryChart(Trade $trade)
    {
        $unrealizedData = $roeData = $labels = [];
        foreach ($trade->pnlHistory as $history) {
            $unrealizedData[] = (float)$history->unrealized_pnl;
            $roeData[] = (float)$history->roe;
            $labels[] = $history->price;
        }

        return [
            'chart' => [
                'type' => 'line'
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
                $this->periodId,
                fn($query) => $query->where('trade_period_id', $this->periodId)
            )
            ->whereNotNull('closed_at')
            ->whereNull('deleted_at')
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
}
