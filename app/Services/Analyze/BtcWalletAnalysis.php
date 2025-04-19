<?php

namespace App\Services\Analyze;

use App\Models\BtcWallets\Wallet;

class BtcWalletAnalysis
{
    protected Wallet $wallet;

    public function __construct(Wallet $wallet)
    {
        $this->wallet = $wallet;
    }

    public function calculate(): array
    {
        $history = $this->wallet->balances()->latest('updated_at')
            ->take(30)
            ->pluck('balance')
            ->reverse();

        $metrics = [
            'whale_score' => $this->calculateWhaleScore($history),
            'momentum' => $this->calculateMomentum($history),
            'correlation' => $this->calculateCorrelation($history),
            'smart_index' => $this->calculateSmartIndex($history),
            'stability' => $this->calculateStability($history),
        ];

        return $metrics;
    }

    protected function calculateWhaleScore($history): float
    {
        return $history->max() / 10_000; // Пример: делим на условную "китовую" сумму
    }

    protected function calculateMomentum($history): int
    {
        return $history->last() - $history->first(); // Простой прирост за период
    }

    protected function calculateCorrelation($history): float
    {
        // Для примера — пока просто 0.5
        return 0.5;
    }

    protected function calculateSmartIndex($history): float
    {
        return ($this->calculateMomentum($history) * 0.4) + ($this->calculateStability($history) * 0.6);
    }

    protected function calculateStability($history): float
    {
        $values = $history->toArray();
        $count = count($values);

        if ($count === 0) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_reduce($values, static function ($carry, $value) use ($mean) {
                return $carry + (($value - $mean) ** 2);
            }, 0) / $count;

        $stdDev = sqrt($variance);

        return 1 / ($stdDev + 0.001); // Обратная волатильность
    }


//    protected function calculateGrowth(array $values): float
//    {
//        $first = reset($values);
//        $last = end($values);
//        return ($last - $first) / $first * 100;
//    }
//
//    protected function calculateVolatility(array $values): float
//    {
//        $avg = array_sum($values) / count($values);
//        $squaredDiffs = array_map(fn($v) => pow($v - $avg, 2), $values);
//        $variance = array_sum($squaredDiffs) / count($squaredDiffs);
//        return sqrt($variance);
//    }
//
//    protected function calculateCompositeIndex(float $growth, float $volatility): float
//    {
//        return $growth / ($volatility ?: 1); // избегаем деления на 0
//    }
}

