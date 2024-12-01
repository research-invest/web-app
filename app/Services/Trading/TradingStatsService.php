<?php

namespace App\Services\Trading;

use App\Models\Currency;
use App\Models\Trade;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TradingStatsService
{
    /**
     * Получить статистику торгов для валюты
     */
    public function getStats(Currency $currency): array
    {
        $trades = Trade::where('currency_id', $currency->id)
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->get();

        if ($trades->isEmpty()) {
            return [
                'total_trades' => 0,
                'successful_trades' => 0,
                'success_rate' => 0,
                'total_pnl' => 0,
                'average_pnl' => 0,
                'average_duration' => '0ч',
                'max_drawdown' => 0,
            ];
        }

        $successfulTrades = $trades->where('realized_pnl', '>', 0)->count();
        $totalPnl = $trades->sum('realized_pnl');

        return [
            'total_trades' => $trades->count(),
            'successful_trades' => $successfulTrades,
            'success_rate' => $this->calculateSuccessRate($trades->count(), $successfulTrades),
            'total_pnl' => $this->formatPnl($totalPnl),
            'average_pnl' => $this->formatPnl($totalPnl / $trades->count()),
            'average_duration' => $this->calculateAverageDuration($trades),
            'max_drawdown' => $this->calculateMaxDrawdown($trades),
            'monthly_stats' => $this->getMonthlyStats($trades),
            'position_sizes' => $this->getPositionSizeStats($trades),
            'detailed_stats' => [
                'long_trades' => $this->getDirectionalStats($trades, 'long'),
                'short_trades' => $this->getDirectionalStats($trades, 'short'),
            ]
        ];
    }

    /**
     * Рассчитать процент успешных сделок
     */
    private function calculateSuccessRate(int $total, int $successful): float
    {
        if ($total === 0) return 0;
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Форматировать значение P&L
     */
    private function formatPnl(?float $pnl): string
    {
        return number_format($pnl, 2) . ' USDT';
    }

    /**
     * Рассчитать среднюю длительность сделок
     */
    private function calculateAverageDuration($trades): string
    {
        $avgMinutes = $trades->avg(function ($trade) {
            return Carbon::parse($trade->closed_at)->diffInMinutes($trade->created_at);
        });

        if ($avgMinutes < 60) {
            return round($avgMinutes) . 'м';
        }

        return round($avgMinutes / 60, 1) . 'ч';
    }

    /**
     * Рассчитать максимальную просадку
     */
    private function calculateMaxDrawdown($trades): float
    {
        $peak = 0;
        $maxDrawdown = 0;
        $runningPnl = 0;

        foreach ($trades->sortBy('created_at') as $trade) {
            $runningPnl += $trade->realized_pnl;

            if ($runningPnl > $peak) {
                $peak = $runningPnl;
            }

            $drawdown = (($peak - $runningPnl) / ($peak?:0.1)) * 100;

            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return round($maxDrawdown, 2);
    }

    /**
     * Получить статистику по месяцам
     */
    private function getMonthlyStats($trades): array
    {
        return $trades->groupBy(function ($trade) {
            return Carbon::parse($trade->created_at)->format('Y-m');
        })->map(function ($monthTrades) {
            return [
                'total_trades' => $monthTrades->count(),
                'successful_trades' => $monthTrades->where('realized_pnl', '>', 0)->count(),
                'total_pnl' => $this->formatPnl($monthTrades->sum('realized_pnl')),
            ];
        })->toArray();
    }

    /**
     * Получить статистику по размерам позиций
     */
    private function getPositionSizeStats($trades): array
    {
        $sizes = $trades->groupBy(function ($trade) {
            // Группируем по размеру позиции с шагом в 100 USDT
            return floor($trade->position_size / 100) * 100;
        });

        return $sizes->map(function ($sizeTrades, $size) {
            return [
                'size_range' => $size . '-' . ($size + 100) . ' USDT',
                'count' => $sizeTrades->count(),
                'avg_pnl' => $this->formatPnl($sizeTrades->avg('realized_pnl')),
                'success_rate' => $this->calculateSuccessRate(
                    $sizeTrades->count(),
                    $sizeTrades->where('realized_pnl', '>', 0)->count()
                )
            ];
        })->toArray();
    }

    /**
     * Получить статистику по направлению сделок (лонг/шорт)
     */
    private function getDirectionalStats($trades, string $direction): array
    {
        $directionTrades = $trades->where('direction', $direction);
        $successful = $directionTrades->where('realized_pnl', '>', 0)->count();

        return [
            'total_trades' => $directionTrades->count(),
            'successful_trades' => $successful,
            'success_rate' => $this->calculateSuccessRate($directionTrades->count(), $successful),
            'total_pnl' => $this->formatPnl($directionTrades->sum('realized_pnl')),
            'average_pnl' => $this->formatPnl($directionTrades->avg('realized_pnl')),
        ];
    }
}
