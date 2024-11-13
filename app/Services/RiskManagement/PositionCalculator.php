<?php

namespace App\Services\RiskManagement;

use App\Models\Trade;
use App\Services\TelegramService;

class PositionCalculator
{
    private Trade $trade;
    private array $averagingLevels = [];
    private TelegramService $telegram;

    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
        $this->telegram = app(TelegramService::class);
        $this->calculateAveragingLevels();
    }

    public function getChartConfig(): array
    {
        $currentPrice = (float)$this->trade->currency->last_price;
        $entryPrice = (float)$this->trade->entry_price;
        $priceStep = $currentPrice * 0.10; // 10% шаг
        $levelsCount = 5;

        // Генерируем точки для линий тренда
        $upTrendPoints = [];
        $downTrendPoints = [];

        // Точки для линии вверх (продажа)
        for ($i = 0; $i <= $levelsCount; $i++) {
            $price = $currentPrice + ($priceStep * $i);
            $upTrendPoints[] = [0.5 + ($i/10), $price]; // Начинаем от центра (0.5) и идем вправо
        }

        // Точки для линии вниз (усреднение)
        for ($i = 0; $i <= $levelsCount; $i++) {
            $price = $currentPrice - ($priceStep * $i);
            $downTrendPoints[] = [0.5 + ($i/10), $price]; // Начинаем от центра (0.5) и идем вправо
        }

        // Получаем точки закупа из истории сделки
        $buyPoints = $this->getBuyPointsFromHistory();

        $series = [
            [
                'name' => 'Текущая цена',
                'data' => [[0, $currentPrice], [1, $currentPrice]],
                'color' => '#666666',
                'lineWidth' => 2
            ],
            [
                'name' => 'Тренд вверх (продажа)',
                'data' => $upTrendPoints,
                'color' => '#22bb33',
                'dashStyle' => 'shortdash',
                'lineWidth' => 1
            ],
            [
                'name' => 'Тренд вниз (усреднение)',
                'data' => $downTrendPoints,
                'color' => '#f77',
                'dashStyle' => 'shortdash',
                'lineWidth' => 1
            ]
        ];

        // Добавляем точки закупа только если они есть
        if (!empty($buyPoints)) {
            $series[] = [
                'name' => 'Точки закупа',
                'type' => 'scatter',
                'data' => $buyPoints,
                'color' => '#f77',
                'marker' => [
                    'symbol' => 'circle',
                    'radius' => 6
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'format' => 'Закуп: {y:.2f}',
                    'align' => 'left',
                    'x' => 10
                ]
            ];
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400
            ],
            'title' => [
                'text' => "Ценовые уровни: {$this->trade->currency->symbol}",
                'align' => 'left'
            ],
            'xAxis' => [
                'visible' => false,
                'min' => 0,
                'max' => 1
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Цена'
                ],
                'labels' => [
                    'format' => '{value:.8f}'
                ]
            ],
            'tooltip' => [
                'shared' => true,
                'valueDecimals' => 8
            ],
            'series' => $series,
            'credits' => [
                'enabled' => false
            ]
        ];
    }

    public function checkPriceLevels(float $currentPrice): void
    {
        // Проверка достижения целевой цены
        if ($this->isTargetPriceReached($currentPrice)) {
            $this->sendTargetAlert($currentPrice);
        }

        // Проверка уровней усреднения
        foreach ($this->averagingLevels as $level) {
            if ($this->isPriceLevelReached($currentPrice, $level['price'])) {
                $this->sendAveragingAlert($level);
            }
        }
    }

    private function isTargetPriceReached(float $currentPrice): bool
    {
        $targetPrice = $this->trade->take_profit_price;

        if ($this->trade->position_type === 'long') {
            return $currentPrice >= $targetPrice;
        }

        return $currentPrice <= $targetPrice;
    }

    private function isPriceLevelReached(float $currentPrice, float $levelPrice): bool
    {
        if ($this->trade->position_type === 'long') {
            return $currentPrice <= $levelPrice;
        }

        return $currentPrice >= $levelPrice;
    }

    private function sendTargetAlert(float $currentPrice): void
    {
        $profit = $this->calculateProfit($currentPrice);
        $message = "🎯 Достигнута целевая цена\n" .
                  "Символ: {$this->trade->currency->symbol}\n" .
                  "Позиция: " . ($this->trade->position_type === 'long' ? 'Лонг' : 'Шорт') . "\n" .
                  "Текущая цена: $currentPrice\n" .
                  "Целевая цена: {$this->trade->take_profit_price}\n" .
                  "Потенциальная прибыль: $profit USDT";

        $this->telegram->sendMessage($message);
    }

    private function sendAveragingAlert(array $level): void
    {
        $message = "🔄 Сигнал на усреднение\n" .
                  "Символ: {$this->trade->currency->symbol}\n" .
                  "Позиция: " . ($this->trade->position_type === 'long' ? 'Лонг' : 'Шорт') . "\n" .
                  "Уровень цены: {$level['price']}\n" .
                  "Рекомендуемый объем: {$level['recommendedSize']} USDT\n" .
                  "Уровень: {$level['level']}";

        $this->telegram->sendMessage($message);
    }

    private function calculateAveragingLevels(): void
    {
        $this->averagingLevels = [];
        $entryPrice = $this->trade->entry_price;
        $positionSize = $this->trade->position_size;

        // Точки закупа (можно вынести в конфиг)
        $buyPoints = [
            ['percentage' => 2, 'sizeMultiplier' => 1.5],   // -2% от цены входа
            ['percentage' => 4, 'sizeMultiplier' => 2.0],   // -4% от цены входа
            ['percentage' => 6, 'sizeMultiplier' => 2.5],   // -6% от цены входа
        ];

        foreach ($buyPoints as $index => $point) {
            // Рассчитываем цену для точки закупа
            $price = $this->trade->position_type === 'long'
                ? $entryPrice * (1 - $point['percentage'] / 100)
                : $entryPrice * (1 + $point['percentage'] / 100);

            $this->averagingLevels[] = [
                'level' => $index + 1,
                'price' => $price,
                'percentage' => $point['percentage'],
                'recommendedSize' => $positionSize * $point['sizeMultiplier'],
                'type' => 'buy_point'
            ];
        }
    }

    private function generatePlotLines(): array
    {
        $entryPrice = (float)$this->trade->entry_price;
        $positionSize = (float)$this->trade->position_size;
        $plotLines = [];

        // Настройки для ценовых уровней
        $levelsCount = 5;
        $priceStepPercent = 10; // 10% шаг

        // Генерируем уровни для продажи (вверх)
        for ($i = 1; $i <= $levelsCount; $i++) {
            $price = $entryPrice * (1 + ($priceStepPercent * $i) / 100);
            $averagePrice = $this->calculateAveragePrice($entryPrice, $price, $positionSize, $positionSize);

            $plotLines[] = [
                'value' => $price,
                'color' => '#22bb33',
                'width' => 1,
                'dashStyle' => 'shortdash',
                'label' => [
                    'text' => sprintf(
                        'Продажа: %.2f (Ср. %.2f)',
                        $price,
                        $averagePrice
                    ),
                    'style' => ['color' => '#22bb33']
                ]
            ];
        }

        // Генерируем уровни для усреднения (вниз)
        for ($i = 1; $i <= $levelsCount; $i++) {
            $price = $entryPrice * (1 - ($priceStepPercent * $i) / 100);
            $averagePrice = $this->calculateAveragePrice($entryPrice, $price, $positionSize, $positionSize);

            $plotLines[] = [
                'value' => $price,
                'color' => '#f77',
                'width' => 1,
                'dashStyle' => 'shortdash',
                'label' => [
                    'text' => sprintf(
                        'Усреднение: %.2f (Ср. %.2f)',
                        $price,
                        $averagePrice
                    ),
                    'style' => ['color' => '#f77']
                ]
            ];
        }

        return $plotLines;
    }

    private function calculateAveragePrice(
        float $entryPrice,
        float $newPrice,
        float $entrySize,
        float $additionalSize
    ): float {
        $totalSize = $entrySize + $additionalSize;

        if ($this->trade->position_type === 'long') {
            return (($entryPrice * $entrySize) + ($newPrice * $additionalSize)) / $totalSize;
        } else {
            // Для шорта расчет прибыли/убытка
            $priceDiff = $entryPrice - $newPrice;
            return $priceDiff * $additionalSize;
        }
    }

    private function calculateTrendLine(string $direction): float
    {
        $entryPrice = $this->trade->entry_price;
        $trendPercentage = 5; // Можно вынести в конфиг

        if ($direction === 'up') {
            return $entryPrice * (1 + $trendPercentage / 100);
        }

        return $entryPrice * (1 - $trendPercentage / 100);
    }

    private function calculateProfit(float $currentPrice): float
    {
        $entryPrice = $this->trade->entry_price;
        $positionSize = $this->trade->position_size;
        $leverage = $this->trade->leverage;

        if ($this->trade->position_type === 'long') {
            $priceChange = ($currentPrice - $entryPrice) / $entryPrice;
        } else {
            $priceChange = ($entryPrice - $currentPrice) / $entryPrice;
        }

        // Учитываем плечо при расчете прибыли
        return $positionSize * $priceChange * $leverage;
    }

    private function getBuyPointsFromHistory(): array
    {
        $buyPoints = [];

        $averagingOrders = $this->trade->orders()
            ->where('type', 'add')
//            ->where('status', 'filled')
            ->get();

        foreach ($averagingOrders as $order) {
            $buyPoints[] = [
                0.2, // фиксированная позиция слева
                (float)$order->price
            ];
        }

        return $buyPoints;
    }
}
