<?php
namespace App\Services\Analyze;


class MarketOscillator
{
    /**
     * Нормализация вектора (приведение к единичной длине)
     */
    private function normalize(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(function($x) {
            return $x * $x;
        }, $vector)));

        if ($magnitude == 0) return array_fill(0, count($vector), 0);

        return array_map(function($x) use ($magnitude) {
            return $x / $magnitude;
        }, $vector);
    }

    /**
     * Скалярное произведение векторов
     */
    private function dotProduct(array $v1, array $v2): float
    {
        return array_sum(array_map(function($a, $b) {
            return $a * $b;
        }, $v1, $v2));
    }

    /**
     * Расчет угла между векторами PNL
     * Возвращает значение от -1 до 1
     * -1: противоположное движение
     * 0: независимое движение
     * 1: одинаковое движение
     */
    private function calculateCorrelation(array $longPnl, array $shortPnl): float
    {
        // Нормализуем векторы
        $normalizedLong = $this->normalize($longPnl);
        $normalizedShort = $this->normalize($shortPnl);

        // Считаем косинус угла между векторами
        return $this->dotProduct($normalizedLong, $normalizedShort);
    }

    /**
     * Расчет тренда движения
     * Возвращает значение от -100 до 100
     */
    private function calculateTrend(array $values): float
    {
        if (empty($values)) return 0;

        // Создаем вектор разниц
        $differences = [];
        for ($i = 1; $i < count($values); $i++) {
            $differences[] = $values[$i] - $values[$i-1];
        }

        // Нормализуем и суммируем
        return array_sum($this->normalize($differences)) * 100;
    }

    /**
     * Основной метод анализа
     */
    public function analyze(array $longPnl, array $shortPnl): array
    {
        // Корреляция движения
        $correlation = $this->calculateCorrelation($longPnl, $shortPnl);

        // Тренды
        $longTrend = $this->calculateTrend($longPnl);
        $shortTrend = $this->calculateTrend($shortPnl);

        // Общий тренд рынка
        $marketTrend = ($longTrend - $shortTrend) / 2;

        return [
            'correlation' => round($correlation * 100, 2),
            'market_trend' => round($marketTrend, 2),
            'long_strength' => round($longTrend, 2),
            'short_strength' => round($shortTrend, 2)
        ];
    }
}
