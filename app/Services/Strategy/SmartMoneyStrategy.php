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
        $closes = array_column($candles, 'close');

        $volumeCondition = $this->checkVolumeCondition($volumes);
        $priceCondition = $this->checkPriceRange($highs, $lows);

        // Определяем тип зоны
        if ($volumeCondition && $priceCondition) {

            // Получаем значения для сообщения
            $lastVolumeRatio = $volumes[array_key_last($volumes)] / $this->calculateMovingAverage($volumes, $this->periodLength);
            $lastPriceRange = ($highs[array_key_last($highs)] - $lows[array_key_last($lows)]) / $lows[array_key_last($lows)] * 100;

            // Определяем тренд за последние N свечей
            $trendUp = $closes[array_key_last($closes)] > $closes[array_key_last($closes) - 10];

            if ($trendUp) {
                return [
                    'type' => 'distribution',
                    'message' => sprintf(
                        'Возможная зона распределения (сигнал для SHORT). Объем превышает средний в %.2fx, диапазон цены: %.2f%%',
                        $lastVolumeRatio,
                        $lastPriceRange
                    )
                ];
            }

            return [
                'type' => 'accumulation',
                'message' => sprintf(
                    'Возможная зона накопления (сигнал для LONG). Объем превышает средний в %.2fx, диапазон цены: %.2f%%',
                    $lastVolumeRatio,
                    $lastPriceRange
                )
            ];
        }

        return [];
    }

    public function analyzeV2(array $candles): array
    {
        // Проверяем, есть ли достаточно свечей для анализа
        if (count($candles) < 20) {
            return [
                'accumulation' => 'Недостаточно данных',
                'volume_delta' => '0',
                'trend' => 'Неопределен',
                'recommendation' => 'Недостаточно данных для анализа',
                'recommendation_type' => 'info'
            ];
        }

        // Анализируем последние 20 свечей
        $recentCandles = array_slice($candles, -20);

        // Рассчитываем дельту объема
        $volumeDelta = $this->calculateVolumeDelta($recentCandles);

        // Определяем тренд
        $trend = $this->determineTrend($recentCandles);

        // Определяем накопление/распределение
        $accumulation = $this->determineAccumulation($recentCandles);

        // Формируем рекомендацию
        $recommendation = $this->generateRecommendation($trend, $volumeDelta, $accumulation);

        return [
            'accumulation' => $accumulation,
            'volume_delta' => number_format($volumeDelta, 2),
            'trend' => $trend,
            'recommendation' => $recommendation['text'],
            'recommendation_type' => $recommendation['type']
        ];
    }

    private function calculateMovingAverage(array $data, int $period): float
    {
        $windowData = array_slice($data, -$period);
        if ($count = count($windowData)) {
            return array_sum($windowData) / $count;
        }

        return 0;
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

    private function checkVolumeCondition(array $volumes): bool
    {
        $lastIndex = count($volumes) - 1;
        $volumeMA = $this->calculateMovingAverage($volumes, $this->periodLength);
        return $volumes[$lastIndex] > ($volumeMA * $this->volumeThreshold);
    }

    private function checkPriceRange(array $highs, array $lows): bool
    {
        $lastIndex = count($highs) - 1;
        $priceRange = ($highs[$lastIndex] - $lows[$lastIndex]) / $lows[$lastIndex] * 100;
        return $priceRange < $this->priceRangeThreshold;
    }

    private function calculateVolumeDelta(array $candles): float
    {
        $buyVolume = 0;
        $sellVolume = 0;

        foreach ($candles as $candle) {
            if ($candle['close'] > $candle['open']) {
                $buyVolume += $candle['volume'];
            } else {
                $sellVolume += $candle['volume'];
            }
        }

        return $buyVolume - $sellVolume;
    }

    private function determineTrend(array $candles): string
    {
        $firstPrice = $candles[0]['close'];

        if (!$firstPrice) {
            return '-';
        }
        $lastPrice = end($candles)['close'];
        $priceChange = (($lastPrice - $firstPrice) / $firstPrice) * 100;

        if ($priceChange > 3) {
            return 'Восходящий';
        }

        if ($priceChange < -3) {
            return 'Нисходящий';
        }
        return 'Боковой';
    }

    private function determineAccumulation(array $candles): string
    {
        $highVolumeBars = 0;
        $averageVolume = array_sum(array_column($candles, 'volume')) / count($candles);

        foreach ($candles as $candle) {
            if ($candle['volume'] > $averageVolume * 1.5) {
                $highVolumeBars++;
            }
        }

        if ($highVolumeBars >= 5) {
            return 'Активное накопление';
        }

        if ($highVolumeBars >= 3) {
            return 'Умеренное накопление';
        }
        return 'Распределение';
    }

    private function generateRecommendation(string $trend, float $volumeDelta, string $accumulation): array
    {
        if ($trend === 'Восходящий' && $volumeDelta > 0 && str_contains($accumulation, 'накопление')) {
            return [
                'text' => 'Сильный сигнал на покупку. Наблюдается восходящий тренд с подтверждением объемом.',
                'type' => 'success'
            ];
        }

        if ($trend === 'Нисходящий' && $volumeDelta < 0) {
            return [
                'text' => 'Рекомендуется воздержаться от покупок. Нисходящий тренд с давлением продаж.',
                'type' => 'danger'
            ];
        }

        if ($trend === 'Боковой') {
            return [
                'text' => 'Рекомендуется дождаться более четких сигналов. Рынок в боковом движении.',
                'type' => 'warning'
            ];
        }

        return [
            'text' => 'Смешанные сигналы. Требуется дополнительный анализ.',
            'type' => 'info'
        ];
    }
}
