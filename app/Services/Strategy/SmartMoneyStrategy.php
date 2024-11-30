<?php

namespace App\Services\Strategy;

/**
 * Определяет зоны накопления на основе:
 * - Повышенного объема
 * - Узкого ценового диапазона
 * - Относительно бокового движения цены
 */
class SmartMoneyStrategy
{

    private float $volumeThreshold = 1.5;
    private float $priceRangeThreshold = 0.3;
    private int $periodLength = 20;

    public function analyze(array $candles): array
    {
        $volumes = array_column($candles, 'volume');
        $highs = array_column($candles, 'high');
        $lows = array_column($candles, 'low');

        // Рассчитываем средний объем
        $volumeMA = $this->calculateMovingAverage($volumes, $this->periodLength);

        // Проверяем условия для зоны накопления
        $lastIndex = count($candles) - 1;

        $highVolume = $volumes[$lastIndex] > ($volumeMA * $this->volumeThreshold);
        $priceRange = ($highs[$lastIndex] - $lows[$lastIndex]) / $lows[$lastIndex] * 100;
        $tightRange = $priceRange < $this->priceRangeThreshold;

        $isAccumulation = $highVolume && $tightRange;

        return [
            'is_accumulation' => $isAccumulation,
            'volume_ratio' => $volumes[$lastIndex] / $volumeMA,
            'price_range' => $priceRange,
            'message' => $this->generateAlert($isAccumulation, $volumes[$lastIndex] / $volumeMA, $priceRange)
        ];
    }

    private function calculateMovingAverage(array $data, int $period): float
    {
        $windowData = array_slice($data, -$period);
        return array_sum($windowData) / count($windowData);
    }

    private function generateAlert(bool $isAccumulation, float $volumeRatio, float $priceRange): string
    {
        if ($isAccumulation) {
            return sprintf(
                "Обнаружена зона накопления! Объем превышает средний в %.2f раз, ценовой диапазон: %.2f%%",
                $volumeRatio,
                $priceRange
            );
        }
        return 'Зона накопления не обнаружена';
    }

    public function setVolumeThreshold(float $threshold): void
    {
        $this->volumeThreshold = $threshold;
    }

    public function setPriceRangeThreshold(float $threshold): void
    {
        $this->priceRangeThreshold = $threshold;
    }

    public function setPeriodLength(int $length): void
    {
        $this->periodLength = $length;
    }
}
