<?php

namespace App\Services;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PnlAnalyticsService
{
    private const int DAILY_TARGET = 100;
    private const int DAILY_TARGET_WEEKEND = 50;

    public function getChartData(): array
    {
        $firstTradeDate = DB::table('trades')
//            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->min('closed_at');

        // Если нет закрытых сделок, возвращаем пустой график
        if (!$firstTradeDate) {
            return [
                'summary' => [
                    'tradingDays' => 0,
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
            ->where('status', 'closed')
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
        $historiesData = $labels = [];
        foreach ($trade->pnlHistory as $history) {
            $historiesData[] = (float)$history->unrealized_pnl;
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
                    'text' => 'USD'
                ]
            ],
            'series' => [
                [
                    'name' => 'История P&L',
                    'data' => $historiesData
                ],
            ],
            'tooltip' => [
                'valuePrefix' => '',
                'valueSuffix' => ' USD'
            ]
        ];
    }
}
