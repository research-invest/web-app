<?php

namespace App\Services;

use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PnlAnalyticsService
{
    // Целевой дневной PNL в USD
    private const int DAILY_TARGET = 100;

    private function getActualPnl($startDate, $endDate): array
    {
        return DB::table('trades')
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
            ->map(fn($item) => $item->daily_pnl)
            ->toArray();
    }

    public function getChartData(int $days = 30): array
    {
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays($days);

        // Получаем фактический PNL только по закрытым сделкам
        $actualPnl = $this->getActualPnl($startDate, $endDate);

        $labels = [];
        $actualData = [];
        $plannedData = [];
        $cumulativeActual = 0;
        $cumulativePlanned = 0;

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            // Метка для оси X
            $labels[] = $date->format('d.m');

            // Фактический PNL
            $cumulativeActual += ($actualPnl[$dateStr] ?? 0);
            $actualData[] = round($cumulativeActual, 2);

            // Плановый PNL (каждый день +100)
            $cumulativePlanned += self::DAILY_TARGET;
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
            ]
        ];
    }
}
