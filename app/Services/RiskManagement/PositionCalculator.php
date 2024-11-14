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
        $positionSize = (float)$this->trade->position_size;
        $priceStep = $currentPrice * 0.10;
        $levelsCount = 5;
        $buyHistory = $this->getBuyPointsHistory(); // Добавить эту строку

        // Генерируем точки для линий тренда
        $upTrendPoints = [];
        $downTrendPoints = [];

        // Для шорта: расчет прибыли при продаже
        // Для лонга: расчет средней цены при усреднении
        for ($i = 0; $i <= $levelsCount; $i++) {
            $x = 0.5 + ($i/10);
            $priceUp = $currentPrice + ($priceStep * $i);
            $priceDown = $currentPrice - ($priceStep * $i);

            if ($this->trade->isTypeShort()) {
                // Для шорта: верхняя линия показывает точки усреднения
                $averagePrice = $this->calculateAveragePrice($entryPrice, $priceUp, $positionSize, $positionSize);
                $upTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceUp,
                    'averagePrice' => $averagePrice,
                    'tooltip' => sprintf(
                        "Усреднение:\nЦена: %.2f\nСредняя цена: %.2f",
                        $priceUp,
                        $averagePrice
                    )
                ];

                // Нижняя линия показывает потенциальную прибыль
                $profit = $this->calculateProfit($priceDown, $positionSize);
                $downTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceDown,
                    'profit' => $profit,
                    'tooltip' => sprintf(
                        "Продажа:\nЦена: %.2f\nПрибыль: %.2f USDT",
                        $priceDown,
                        $profit
                    )
                ];
            } else {
                // Для лонга: нижняя линия показывает точки усреднения
                $averagePrice = $this->calculateAveragePrice($entryPrice, $priceDown, $positionSize, $positionSize);
                $downTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceDown,
                    'averagePrice' => $averagePrice,
                    'tooltip' => sprintf(
                        "Усреднение:\nЦена: %.2f\nСредняя цена: %.2f",
                        $priceDown,
                        $averagePrice
                    )
                ];

                // Верхняя линия показывает потенциальную прибыль
                $profit = $this->calculateProfit($priceUp, $positionSize);
                $upTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceUp,
                    'profit' => $profit,
                    'tooltip' => sprintf(
                        "Продажа:\nЦена: %.2f\nПрибыль: %.2f USDT",
                        $priceUp,
                        $profit
                    )
                ];
            }
        }

        $series = [
            // Текущая цена (горизонтальная линия)
            [
                'name' => 'Текущая цена',
                'data' => [[0, $currentPrice], [1, $currentPrice]],
                'color' => '#666666',
                'lineWidth' => 2
            ],
            // История входов и усреднений (линия слева)
            [
                'name' => 'История входов',
                'data' => $buyHistory['linePoints'],
                'color' => '#f77',
                'lineWidth' => 2,
                'marker' => [
                    'enabled' => true,
                    'radius' => 6
                ],
                'dataLabels' => [
                    'enabled' => true,
                    'format' => 'Закуп: {y:.2f}',
                    'align' => 'left',
                    'x' => 10
                ]
            ],
            // Линия тренда вверх
            [
                'name' => 'Тренд вверх',
                'data' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => $point['y'],
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => $this->trade->isTypeShort()
                                ? 'Усреднение: {y:.2f} (ср. ' . number_format($point['averagePrice'], 2) . ')'
                                : 'Продажа: {y:.2f} (приб. ' . number_format($point['profit'], 2) . ')'
                        ]
                    ];
                }, $upTrendPoints),
                'color' => '#22bb33',
                'dashStyle' => 'shortdash',
                'lineWidth' => 1
            ],
            // Линия тренда вниз
            [
                'name' => 'Тренд вниз',
                'data' => array_map(function($point) {
                    return [
                        'x' => $point['x'],
                        'y' => $point['y'],
                        'dataLabels' => [
                            'enabled' => true,
                            'format' => $this->trade->isTypeShort()
                                ? 'Продажа: {y:.2f} (приб. ' . number_format($point['profit'], 2) . ')'
                                : 'Усреднение: {y:.2f} (ср. ' . number_format($point['averagePrice'], 2) . ')'
                        ]
                    ];
                }, $downTrendPoints),
                'color' => '#f77',
                'dashStyle' => 'shortdash',
                'lineWidth' => 1
            ]
        ];

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

        if ($this->trade->isTypeLong()) {
            return $currentPrice >= $targetPrice;
        }

        return $currentPrice <= $targetPrice;
    }

    private function isPriceLevelReached(float $currentPrice, float $levelPrice): bool
    {
        if ($this->trade->isTypeLong()) {
            return $currentPrice <= $levelPrice;
        }

        return $currentPrice >= $levelPrice;
    }

    private function sendTargetAlert(float $currentPrice): void
    {
        $profit = $this->calculateProfit($currentPrice);
        $message = "🎯 Достигнута целевая цена\n" .
                  "Символ: {$this->trade->currency->symbol}\n" .
                  "Позиция: " . ($this->trade->isTypeLong() ? 'Лонг' : 'Шорт') . "\n" .
                  "Текущая цена: $currentPrice\n" .
                  "Целевая цена: {$this->trade->take_profit_price}\n" .
                  "Потенциальная прибыль: $profit USDT";

        $this->telegram->sendMessage($message);
    }

    private function sendAveragingAlert(array $level): void
    {
        $message = "🔄 Сигнал на усреднение\n" .
                  "Символ: {$this->trade->currency->symbol}\n" .
                  "Позиция: " . ($this->trade->isTypeLong() ? 'Лонг' : 'Шорт') . "\n" .
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
            $price = $this->trade->isTypeLong()
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

    private function calculateAveragePrice(float $entryPrice, float $newPrice, float $entrySize, float $additionalSize): float
    {
        $totalSize = $entrySize + $additionalSize;
        return (($entryPrice * $entrySize) + ($newPrice * $additionalSize)) / $totalSize;
    }

    private function calculateProfit(float $exitPrice, float $positionSize): float
    {
        $entryPrice = (float)$this->trade->entry_price;
        $leverage = (float)$this->trade->leverage;

        if ($this->trade->isTypeLong()) {
            return ($exitPrice - $entryPrice) * $positionSize * $leverage;
        } else {
            return ($entryPrice - $exitPrice) * $positionSize * $leverage;
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

    private function getBuyPointsHistory(): array
    {
        $points = [];
        $linePoints = [];
        $x = 0.2; // Начальная позиция слева
        $xStep = 0.05; // Шаг между точками

        // Добавляем начальную точку входа
        $points[] = [
            'x' => $x,
            'y' => (float)$this->trade->entry_price,
            'type' => 'entry'
        ];
        $linePoints[] = [$x, (float)$this->trade->entry_price];

        // Получаем все ордера усреднения в хронологическом порядке
        $averagingOrders = $this->trade->orders()
            ->where('type', 'add')
            ->orderBy('created_at')
            ->get();

        foreach ($averagingOrders as $order) {
            $x += $xStep;
            $points[] = [
                'x' => $x,
                'y' => (float)$order->price,
                'type' => 'averaging'
            ];
            $linePoints[] = [$x, (float)$order->price];
        }

        return [
            'points' => $points,
            'linePoints' => $linePoints
        ];
    }
}
