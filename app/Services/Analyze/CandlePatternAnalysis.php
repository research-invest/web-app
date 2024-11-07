<?php

namespace App\Services\Analyze;

class CandlePatternAnalysis
{
    public function findPatterns(array $klines)
    {
        $patterns = [];

        foreach ($klines as $i => $candle) {
            // Пропускаем первые свечи, так как нужно несколько для паттерна
            if ($i < 3) continue;

            // Поиск паттерна "Молот"
            if ($this->isHammer($candle)) {
                $patterns[] = [
                    'type' => 'hammer',
                    'price' => $candle['close'],
                    'reliability' => 0.7
                ];
            }

            // Поиск паттерна "Поглощение"
            if ($this->isEngulfing($klines[$i-1], $candle)) {
                $patterns[] = [
                    'type' => 'engulfing',
                    'price' => $candle['close'],
                    'reliability' => 0.8
                ];
            }

            // Другие паттерны...
        }

        return $patterns;
    }
}
