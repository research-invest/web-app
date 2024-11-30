<?php

namespace App\Services\Analyze;

use App\Services\Strategy\SmartMoneyStrategy;

class TechnicalAnalysis
{
    public function analyzeEntry(string $symbol, array $klines)
    {
        $recommendations = [
            'support_levels' => [],
            'resistance_levels' => [],
            'entry_points' => [],
            'strength' => 0
        ];

        // Анализ уровней по объемам
        $volumeLevels = $this->analyzeVolumeLevels($klines);

        // RSI для определения перекупленности/перепроданности
        $rsi = $this->calculateRSI($klines);

        // Bollinger Bands для волатильности
        $bb = $this->calculateBollingerBands($klines);

        // VWAP для определения справедливой цены
        $vwap = $this->calculateVWAP($klines);

        // Комбинируем сигналы
        foreach ($klines as $index => $candle) {
            $strength = 0;

            // Если цена около уровня объема
            if ($this->isPriceNearVolumeLevel($candle['close'], $volumeLevels)) {
                $strength += 2;
            }

            // Если RSI показывает экстремум
            if ($rsi[$index] < 30 || $rsi[$index] > 70) {
                $strength += 1;
            }

            // Если цена около границ Bollinger
            if ($this->isPriceNearBollinger($candle['close'], $bb[$index])) {
                $strength += 1;
            }

            // Если несколько индикаторов подтверждают
            if ($strength >= 3) {
                $recommendations['entry_points'][] = [
                    'price' => $candle['close'],
                    'strength' => $strength,
                    'timestamp' => $candle['timestamp']
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Анализ уровней по объемам
     */
    public function analyzeVolumeLevels(array $klines): array
    {
        $volumeLevels = [];
        $priceVolumes = [];

        // Группируем объемы по ценовым уровням
        foreach ($klines as $candle) {
            $price = round($candle['close'], 2);
            if (!isset($priceVolumes[$price])) {
                $priceVolumes[$price] = 0;
            }
            $priceVolumes[$price] += (float)$candle['volume'];
        }

        // Находим уровни с высоким объемом (выше среднего на 2 стандартных отклонения)
        $avgVolume = array_sum($priceVolumes) / count($priceVolumes);
        $stdDev = $this->calculateStdDev($priceVolumes, $avgVolume);

        foreach ($priceVolumes as $price => $volume) {
            if ($volume > ($avgVolume + 2 * $stdDev)) {
                $volumeLevels[] = [
                    'price' => (float)$price,
                    'volume' => $volume,
                    'strength' => ($volume - $avgVolume) / $stdDev
                ];
            }
        }

        return $volumeLevels;
    }

    /**
     * Расчет RSI (Relative Strength Index)
     * Период по умолчанию - 14
     */
    public function calculateRSI(array $klines, int $period = 14): array
    {
        $rsi = [];
        $gains = [];
        $losses = [];

        // Вычисляем изменения цены
        for ($i = 1, $iMax = count($klines); $i < $iMax; $i++) {
            $change = $klines[$i]['close'] - $klines[$i-1]['close'];
            $gains[] = max($change, 0);
            $losses[] = abs(min($change, 0));

            if ($i >= $period) {
                $avgGain = array_sum(array_slice($gains, -$period)) / $period;
                $avgLoss = array_sum(array_slice($losses, -$period)) / $period;

                if ($avgLoss == 0) {
                    $rsi[] = 100;
                } else {
                    $rs = $avgGain / $avgLoss;
                    $rsi[] = 100 - (100 / (1 + $rs));
                }
            } else {
                $rsi[] = null;
            }
        }

        return $rsi;
    }

    /**
     * Расчет полос Боллинджера
     * Период по умолчанию - 20, множитель - 2
     */
    public function calculateBollingerBands(array $klines, int $period = 20, float $multiplier = 2): array
    {
        $bb = [];

        for ($i = 0, $iMax = count($klines); $i < $iMax; $i++) {
            if ($i < $period - 1) {
                $bb[] = [
                    'upper' => null,
                    'middle' => null,
                    'lower' => null
                ];
                continue;
            }

            // Получаем срез последних N свечей
            $slice = array_slice($klines, $i - $period + 1, $period);
            $closes = array_column($slice, 'close');

            // Считаем SMA (middle band)
            $sma = array_sum($closes) / $period;

            // Считаем стандартное отклонение
            $stdDev = $this->calculateStdDev($closes, $sma);

            $bb[] = [
                'upper' => $sma + ($multiplier * $stdDev),
                'middle' => $sma,
                'lower' => $sma - ($multiplier * $stdDev)
            ];
        }

        return $bb;
    }

    /**
     * Расчет VWAP (Volume Weighted Average Price)
     */
    public function calculateVWAP(array $klines): array
    {
        $vwap = [];
        $cumTypicalPriceVol = 0;
        $cumVolume = 0;

        foreach ($klines as $candle) {
            // Типичная цена = (High + Low + Close) / 3
            $typicalPrice = ($candle['high'] + $candle['low'] + $candle['close']) / 3;
            $volume = (float)$candle['volume'];

            $cumTypicalPriceVol += $typicalPrice * $volume;
            $cumVolume += $volume;

            $vwap[] = $cumVolume > 0 ? $cumTypicalPriceVol / $cumVolume : null;
        }

        return $vwap;
    }

    /**
     * Вспомогательный метод для расчета стандартного отклонения
     */
    private function calculateStdDev(array $values, float $mean): float
    {
        $variance = 0;
        foreach ($values as $value) {
            $variance += ((float)$value - $mean) ** 2;
        }
        return sqrt($variance / count($values));
    }

    /**
     * Проверяет, находится ли цена около уровня объема
     * @param float $currentPrice Текущая цена
     * @param array $volumeLevels Уровни объемов
     * @param float $threshold Порог отклонения в процентах (по умолчанию 0.5%)
     * @return bool|array Возвращает false или массив с информацией об уровне
     */
    private function isPriceNearVolumeLevel(float $currentPrice, array $volumeLevels, float $threshold = 0.5): bool|array
    {
        foreach ($volumeLevels as $level) {
            $levelPrice = $level['price'];
            $deviation = abs($currentPrice - $levelPrice) / $levelPrice * 100;

            if ($deviation <= $threshold) {
                return [
                    'is_near' => true,
                    'level_price' => $levelPrice,
                    'deviation' => $deviation,
                    'strength' => $level['strength'],
                    'distance_percent' => $deviation
                ];
            }
        }

        return false;
    }

    /**
     * Проверяет, находится ли цена около границ полос Боллинджера
     * @param float $currentPrice Текущая цена
     * @param array $bbBands Текущие значения полос Боллинджера
     * @param float $threshold Порог отклонения в процентах (по умолчанию 0.2%)
     * @return bool|array Возвращает false или массив с информацией о положении
     */
    private function isPriceNearBollinger(float $currentPrice, array $bbBands, float $threshold = 0.2): bool|array
    {
        if (!isset($bbBands['upper']) || !isset($bbBands['lower'])) {
            return false;
        }

        $upperDev = abs($currentPrice - $bbBands['upper']) / $bbBands['upper'] * 100;
        $lowerDev = abs($currentPrice - $bbBands['lower']) / $bbBands['lower'] * 100;

        if ($upperDev <= $threshold) {
            return [
                'is_near' => true,
                'band' => 'upper',
                'deviation' => $upperDev,
                'signal_type' => 'resistance',
                'strength' => ($threshold - $upperDev) / $threshold // чем ближе к границе, тем сильнее сигнал
            ];
        }

        if ($lowerDev <= $threshold) {
            return [
                'is_near' => true,
                'band' => 'lower',
                'deviation' => $lowerDev,
                'signal_type' => 'support',
                'strength' => ($threshold - $lowerDev) / $threshold
            ];
        }

        return false;
    }

    /**
     * Комплексный анализ сигналов
     */
    public function analyzeSignals(float $currentPrice, array $klines): array
    {
        $volumeLevels = $this->analyzeVolumeLevels($klines);
        $bb = $this->calculateBollingerBands($klines);
        $lastBB = end($bb);

        $signals = [];

        // Проверяем близость к уровням объема
        $volumeSignal = $this->isPriceNearVolumeLevel($currentPrice, $volumeLevels);
        if ($volumeSignal) {
            $signals['volume'] = [
                'type' => 'volume_level',
                'data' => $volumeSignal,
                'description' => sprintf(
                    'Цена находится около сильного уровня объема %.2f (отклонение %.2f%%)',
                    $volumeSignal['level_price'],
                    $volumeSignal['deviation']
                )
            ];
        }

        // Проверяем близость к полосам Боллинджера
        $bbSignal = $this->isPriceNearBollinger($currentPrice, $lastBB);
        if ($bbSignal) {
            $signals['bollinger'] = [
                'type' => 'bollinger_band',
                'data' => $bbSignal,
                'description' => sprintf(
                    'Цена находится около %s полосы Боллинджера (сила сигнала: %.2f)',
                    $bbSignal['band'] === 'upper' ? 'верхней' : 'нижней',
                    $bbSignal['strength']
                )
            ];
        }

        // Оценка общей силы сигналов
        $signalStrength = 0;
        if (isset($signals['volume'])) {
            $signalStrength += $volumeSignal['strength'];
        }
        if (isset($signals['bollinger'])) {
            $signalStrength += $bbSignal['strength'];
        }

        return [
            'signals' => $signals,
            'total_strength' => $signalStrength,
            'recommendation' => $this->getRecommendation($signals, $signalStrength)
        ];
    }

    /**
     * Получение рекомендации на основе сигналов
     */
    private function getRecommendation(array $signals, float $totalStrength): array
    {
        $recommendation = [
            'action' => 'neutral',
            'confidence' => 0,
            'description' => 'Нет четких сигналов для входа'
        ];

        if ($totalStrength >= 1.5) {
            $hasResistance = isset($signals['bollinger']) &&
                            $signals['bollinger']['data']['band'] === 'upper';
            $hasSupport = isset($signals['bollinger']) &&
                         $signals['bollinger']['data']['band'] === 'lower';

            if ($hasSupport && isset($signals['volume'])) {
                $recommendation = [
                    'action' => 'buy',
                    'confidence' => $totalStrength,
                    'description' => 'Сильный сигнал на покупку: поддержка BB + объемный уровень'
                ];
            } elseif ($hasResistance && isset($signals['volume'])) {
                $recommendation = [
                    'action' => 'sell',
                    'confidence' => $totalStrength,
                    'description' => 'Сильный сигнал на продажу: сопротивление BB + объемный уровень'
                ];
            }
        }

        return $recommendation;
    }

    private function analyzeSmartMoney(array $candles): array
    {
        $strategy = new SmartMoneyStrategy();
        return $strategy->analyze($candles);
    }

    public function analyze(array $candles): array
    {
        return [
            'smart_money' => $this->analyzeSmartMoney($candles)
        ];
    }
}
