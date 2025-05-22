<?php

namespace App\Services\Analyze;


class MarketOscillator
{
    /**
     * Нормализация вектора (приведение к единичной длине)
     */
    private function normalize(array $vector): array
    {
        // Вычисляем длину (модуль) вектора по формуле: √(x₁² + x₂² + ... + xₙ²)
        $magnitude = sqrt(array_sum(array_map(function ($x) {
            return $x * $x;
        }, $vector)));

        // Делим каждую компоненту на длину вектора
        return array_map(function ($x) use ($magnitude) {
            return $x / $magnitude;
        }, $vector);
    }

    /**
     * Скалярное произведение векторов
     */
    private function dotProduct(array $v1, array $v2): float
    {
        // Скалярное произведение: a·b = a₁b₁ + a₂b₂ + ... + aₙbₙ
        return array_sum(array_map(function ($a, $b) {
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
        for ($i = 1, $iMax = count($values); $i < $iMax; $i++) {
            $differences[] = $values[$i] - $values[$i - 1];
        }

        // Нормализуем и суммируем
        return array_sum($this->normalize($differences)) * 100;
    }

    /**
     * Расчет тренда объемов
     * Возвращает значение от -100 до 100
     */
    private function calculateVolumeTrend(array $volumes): float
    {
        if (empty($volumes)) return 0;

        // Создаем вектор разниц для объемов
        $differences = [];
        for ($i = 1, $iMax = count($volumes); $i < $iMax; $i++) {
            $differences[] = $volumes[$i] - $volumes[$i - 1];
        }

        // Нормализуем и суммируем
        return array_sum($this->normalize($differences)) * 100;
    }

    /**
     * Расчет корреляции цены и объема
     */
    private function calculatePriceVolumeCorrelation(array $pnl, array $volumes): float
    {
        // Получаем изменения цены
        $priceChanges = [];
        for ($i = 1, $iMax = count($pnl); $i < $iMax; $i++) {
            $priceChanges[] = $pnl[$i] - $pnl[$i - 1];
        }

        // Получаем изменения объема (берем те же точки, что и для цены)
        $volumeChanges = [];
        for ($i = 1, $iMax = count($volumes); $i < $iMax; $i++) {
            $volumeChanges[] = $volumes[$i] - $volumes[$i - 1];
        }

        // Нормализуем векторы
        $normalizedPrice = $this->normalize($priceChanges);
        $normalizedVolume = $this->normalize($volumeChanges);

        // Считаем корреляцию
        return $this->dotProduct($normalizedPrice, $normalizedVolume);
    }

    /**
     * Расчет взвешенного тренда объемов для всех активов
     */
    private function calculateWeightedVolumeTrend(
        array $assetVolumes,
        array $btcVolumes,
        array $ethVolumes
    ): float {
        // Веса для каждого типа объемов
        $weights = [
            'asset' => 0.5,  // Основной вес на объемы торгуемого актива
            'btc' => 0.3,    // Влияние BTC
            'eth' => 0.2     // Влияние ETH
        ];

        $assetTrend = $this->calculateVolumeTrend($assetVolumes);
        $btcTrend = $this->calculateVolumeTrend($btcVolumes);
        $ethTrend = $this->calculateVolumeTrend($ethVolumes);

        return (
            $assetTrend * $weights['asset'] +
            $btcTrend * $weights['btc'] +
            $ethTrend * $weights['eth']
        );
    }

    /**
     * Расчет корреляции между объемами разных активов
     */
    private function calculateVolumeCorrelations(
        array $assetVolumes,
        array $btcVolumes,
        array $ethVolumes
    ): array {
        $assetBtcCorr = $this->calculateCorrelation($assetVolumes, $btcVolumes);
        $assetEthCorr = $this->calculateCorrelation($assetVolumes, $ethVolumes);
        $btcEthCorr = $this->calculateCorrelation($btcVolumes, $ethVolumes);

        return [
            'asset_btc' => $assetBtcCorr,
            'asset_eth' => $assetEthCorr,
            'btc_eth' => $btcEthCorr
        ];
    }

    /**
     * Основной метод анализа с учетом всех объемов
     */
    public function analyze(
        array $longPnl,
        array $shortPnl,
        array $assetVolumes,
        array $btcVolumes,
        array $ethVolumes
    ): array {
        // Базовый анализ PNL
        $correlation = $this->calculateCorrelation($longPnl, $shortPnl);
        $longTrend = $this->calculateTrend($longPnl);
        $shortTrend = $this->calculateTrend($shortPnl);
        $marketTrend = ($longTrend - $shortTrend) / 2;

        // Анализ объемов с учетом всех активов
        $weightedVolumeTrend = $this->calculateWeightedVolumeTrend(
            $assetVolumes,
            $btcVolumes,
            $ethVolumes
        );

        // Корреляции между объемами
        $volumeCorrelations = $this->calculateVolumeCorrelations(
            $assetVolumes,
            $btcVolumes,
            $ethVolumes
        );

        // Корреляция цены и объемов основного актива
        $longPriceVolumeCorr = $this->calculatePriceVolumeCorrelation($longPnl, $assetVolumes);
        $shortPriceVolumeCorr = $this->calculatePriceVolumeCorrelation($shortPnl, $assetVolumes);

        return [
            // Базовые метрики
            'correlation' => round($correlation * 100, 2),
            'market_trend' => round($marketTrend, 2),
            'long_strength' => round($longTrend, 2),
            'short_strength' => round($shortTrend, 2),

            // Метрики объемов
            'weighted_volume_trend' => round($weightedVolumeTrend, 2),
            'volume_correlations' => [
                'asset_btc' => round($volumeCorrelations['asset_btc'] * 100, 2),
                'asset_eth' => round($volumeCorrelations['asset_eth'] * 100, 2),
                'btc_eth' => round($volumeCorrelations['btc_eth'] * 100, 2)
            ],
            'price_volume_correlations' => [
                'long' => round($longPriceVolumeCorr * 100, 2),
                'short' => round($shortPriceVolumeCorr * 100, 2)
            ]
        ];
    }
}
