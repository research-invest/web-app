<?php

declare(strict_types=1);

namespace App\Services;

class IndexCalculator
{
    private array $weights = [
        '3m' => 0.8,    // 3-минутный интервал: 10% веса
        '15m' => 0.1,   // 15-минутный интервал: 40% веса
        '1h' => 0.1     // часовой интервал: 50% веса
    ];

//{
//"symbol": "TAOUSDT",
//"open": 488.7,
//"high": 527.2,
//"low": 475.2,
//"close": 497,
//"quote_volume": 58606417.50402,
//"timestamp": "2024-11-19T12:33:00Z",
//"last_price": 497,
//"volume": 117176.7353
//},

# score будет в диапазоне от -1 до 1, где:
# положительные значения указывают на восходящий тренд
# отрицательные значения указывают на нисходящий тренд
# абсолютное значение показывает силу тренда

    public function calculateCandleDirection(array $candle): array
    {
        $direction = $candle['last_price'] > $candle['open'] ? 1 : -1;
        $amplitude = abs($candle['high'] - $candle['low']);

        return [
            'direction' => $direction,
            'amplitude' => $amplitude
        ];
    }

    public function normalizeAmplitudes(array $amplitudes): array
    {
        $maxAmplitude = max($amplitudes);
        return array_map(function($amplitude) use ($maxAmplitude) {
            return $amplitude / ($maxAmplitude ?: 1);
        }, $amplitudes);
    }

    public function calculateIndex(array $data3m, array $data15m, array $data1h, int $lookback = 10): array
    {
        $result = [];

        // Убедимся, что у нас есть достаточно данных
        $minLength = min(count($data3m), count($data15m), count($data1h));
        if ($minLength < $lookback) {
            return $result;
        }

        // Обработаем данные для каждой временной точки
        for ($i = $lookback; $i < $minLength; $i++) {
            $score = 0;

            // Расчет для 3-минутного интервала
            $slice3m = array_slice($data3m, $i - $lookback, $lookback);
            $score += $this->calculateTimeframeScore($slice3m, $this->weights['3m']);

            // Расчет для 15-минутного интервала
            $slice15m = array_slice($data15m, $i - $lookback, $lookback);
            $score += $this->calculateTimeframeScore($slice15m, $this->weights['15m']);

            // Расчет для часового интервала
            $slice1h = array_slice($data1h, $i - $lookback, $lookback);
            $score += $this->calculateTimeframeScore($slice1h, $this->weights['1h']);

            $result[] = [
                'timestamp' => $data3m[$i]['timestamp'],
                'score' => round($score, 4)
            ];
        }

        return $result;
    }

    private function calculateTimeframeScore(array $data, float $weight): float
    {
        $directions = [];
        $amplitudes = [];

        foreach ($data as $candle) {
            $analysis = $this->calculateCandleDirection($candle);
            $directions[] = $analysis['direction'];
            $amplitudes[] = $analysis['amplitude'];
        }

        $normalizedAmplitudes = $this->normalizeAmplitudes($amplitudes);
        $weightedSum = 0;

        foreach ($directions as $i => $direction) {
            $weightedSum += $direction * $normalizedAmplitudes[$i];
        }

        return ($weightedSum / count($data)) * $weight;
    }
}
