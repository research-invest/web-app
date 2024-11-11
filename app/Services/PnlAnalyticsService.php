<?php

namespace App\Services;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PnlAnalyticsService
{
    private const DAILY_TARGET = 100;

    public function getChartData(): array
    {
        $firstTradeDate = DB::table('trades')
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->min('closed_at');

        // Если нет закрытых сделок, возвращаем пустой график
        if (!$firstTradeDate) {
            return [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Фактический P&L',
                        'data' => [],
                        'borderColor' => 'rgb(75, 192, 192)',
                        'tension' => 0.1
                    ],
                    [
                        'label' => 'Плановый P&L',
                        'data' => [],
                        'borderColor' => 'rgb(255, 99, 132)',
                        'tension' => 0.1
                    ]
                ],
                'summary' => [
                    'tradingDays' => 0,
                    'totalPnl' => 0,
                    'targetPnl' => 0,
                    'difference' => 0
                ]
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
            ->whereBetween('closed_at', [$startDate, $endDate])
            ->groupBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn($item) => (float)$item->daily_pnl)
            ->toArray();

        // Формируем данные для графика
        $labels = [];
        $actualData = [];
        $plannedData = [];
        $cumulativeActual = 0;
        $cumulativePlanned = 0;
        $tradingDays = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            // Если в этот день были закрытые сделки, увеличиваем счетчик торговых дней
            if (isset($actualPnl[$dateStr])) {
                $tradingDays++;
            }

            $labels[] = $date->format('d.m');

            // Фактический PNL
            $cumulativeActual += ($actualPnl[$dateStr] ?? 0);
            $actualData[] = round($cumulativeActual, 2);

            // Плановый PNL (считаем только по торговым дням)
            $cumulativePlanned = $tradingDays * self::DAILY_TARGET;
            $plannedData[] = round($cumulativePlanned, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Фактический P&L',
                    'data' => $actualData,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ],
                [
                    'label' => 'Плановый P&L',
                    'data' => $plannedData,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.1
                ]
            ],
            'summary' => [
                'tradingDays' => $tradingDays,
                'totalPnl' => round($cumulativeActual, 2),
                'targetPnl' => round($cumulativePlanned, 2),
                'difference' => round($cumulativeActual - $cumulativePlanned, 2)
            ]
        ];
    }
}
