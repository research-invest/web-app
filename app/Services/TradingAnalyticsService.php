<?php

namespace App\Services;

use App\Models\TradePeriod;
use App\Models\Trade;
use Illuminate\Support\Collection;

class TradingAnalyticsService
{
    public function analyze(TradePeriod $period): array
    {
        $trades = Trade::where('trade_period_id', $period->id)
            ->with('currency')
            ->get();

        return [
            'summary' => $this->getSummary($trades),
            'positions' => $this->getPositionsAnalysis($trades),
            'topTrades' => $this->getTopTrades($trades),
            'lossTrades' => $this->getLossTrades($trades),
        ];
    }

    private function getSummary(Collection $trades): array
    {
        $totalProfit = $trades->sum('realized_pnl');
        $totalLoss = $trades->where('realized_pnl', '<', 0)->sum('realized_pnl');

        return [
            'totalProfit' => $totalProfit,
            'totalLoss' => $totalLoss,
            'netResult' => $totalProfit + $totalLoss,
            'tradesCount' => $trades->count(),
            'winRate' => $trades->where('realized_pnl', '>', 0)->count()
                / max(1, $trades->count()) * 100,
        ];
    }

    private function getPositionsAnalysis(Collection $trades): array
    {
        $longs = $trades->where('position_type', Trade::POSITION_TYPE_LONG)->count();
        $shorts = $trades->where('position_type', Trade::POSITION_TYPE_SHORT)->count();

        return [
            'longs' => $longs,
            'shorts' => $shorts,
            'ratio' => $longs > 0 ? round($shorts / $longs, 2) : 0,
        ];
    }

    private function getTopTrades(Collection $trades): Collection
    {
        return $trades
            ->where('realized_pnl', '>', 0)
            ->sortByDesc('realized_pnl')
            ->take(5);
    }

    private function getLossTrades(Collection $trades): Collection
    {
        return $trades
            ->where('realized_pnl', '<', 0)
            ->sortBy('realized_pnl');
    }
}
