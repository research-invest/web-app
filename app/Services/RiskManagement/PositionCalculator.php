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
        $positionSize = $this->trade->position_size;
        $priceStep = $currentPrice * 0.05;
        $levelsCount = 5;

        // Получаем историю точек
        $historyPoints = $this->getBuyPointsHistory();

        // Генерируем точки для линий тренда от центральной точки
        $upTrendPoints = [];
        $downTrendPoints = [];
        $centerX = 0.5;

        for ($i = 1; $i <= $levelsCount; $i++) {
            $x = $centerX + ($i / 10);
            $priceUp = $currentPrice + ($priceStep * $i);
            $priceDown = $currentPrice - ($priceStep * $i);

            if ($this->trade->isTypeShort()) {
                $averagePrice = $this->calculateAveragePrice($currentPrice, $priceUp, $positionSize, $positionSize);
                $profit = $this->calculateProfit($priceDown);

                $upTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceUp,
                    'dataLabels' => [
                        'enabled' => true,
                        'format' => "Усреднение: {y:.2f} ($averagePrice)"
                    ]
                ];

                $downTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceDown,
                    'dataLabels' => [
                        'enabled' => true,
                        'format' => "Продажа: {y:.2f} ($profit)"
                    ]
                ];
            } else {
                // Для лонга
                $averagePrice = $this->calculateAveragePrice($currentPrice, $priceDown, $positionSize, $positionSize);
                $profit = $this->calculateProfit($priceUp);

                $upTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceUp,
                    'dataLabels' => [
                        'enabled' => true,
                        'format' => "Продажа: {y:.2f} ($profit)"
                    ]
                ];

                $downTrendPoints[] = [
                    'x' => $x,
                    'y' => $priceDown,
                    'dataLabels' => [
                        'enabled' => true,
                        'format' => "Усреднение: {y:.2f} ($averagePrice})"
                    ]
                ];
            }
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400
            ],
            'title' => [
                'text' => "Ценовые уровни: {$this->trade->currency->name}",
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
            'plotOptions' => [
                'series' => [
                    'states' => [
                        'hover' => [
                            'enabled' => false
                        ],
                        'inactive' => [
                            'opacity' => 1
                        ]
                    ],
                    'stickyTracking' => false,
                    'enableMouseTracking' => false
                ],
                'line' => [
                    'marker' => [
                        'enabled' => true,
                        'states' => [
                            'hover' => [
                                'enabled' => false
                            ]
                        ]
                    ]
                ]
            ],
            'tooltip' => [
                'shared' => true,
                'valueDecimals' => 8
            ],
            'series' => [
                [
                    'name' => 'История и текущая цена',
                    'data' => $historyPoints,
                    'color' => '#666666',
                    'lineWidth' => 2,
                    'marker' => [
                        'enabled' => true,
                        'radius' => 6
                    ]
                ],
                [
                    'name' => 'Тренд вверх',
                    'data' => $upTrendPoints,
                    'color' => '#22bb33',
                    'dashStyle' => 'shortdash',
                    'lineWidth' => 1
                ],
                [
                    'name' => 'Тренд вниз',
                    'data' => $downTrendPoints,
                    'color' => '#f77',
                    'dashStyle' => 'shortdash',
                    'lineWidth' => 1
                ]
            ],
            'credits' => [
                'enabled' => false
            ],
            'accessibility' => [
                'enabled' => false
            ],
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
        $targetProfit = $this->trade->target_profit_amount;
        $entryPrice = (float)$this->trade->getAverageEntryPrice();
        $positionSize = (float)$this->trade->position_size;
        $leverage = (float)$this->trade->leverage;

        // Вычисляем необходимое изменение цены для достижения целевой прибыли
        $requiredPriceChange = ($targetProfit / ($positionSize * $leverage));

        // Вычисляем целевой курс в зависимости от типа позиции
        if ($this->trade->isTypeLong()) {
            // Для лонга: целевая цена = цена входа + необходимое изменение
            $targetPrice = $entryPrice * (1 + $requiredPriceChange);
            return $currentPrice >= $targetPrice;
        } else {
            // Для шорта: целевая цена = цена входа - необходимое изменение
            $targetPrice = $entryPrice * (1 - $requiredPriceChange);
            return $currentPrice <= $targetPrice;
        }
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
            "Символ: {$this->trade->currency->name}\n" .
            "Позиция: " . ($this->trade->isTypeLong() ? 'Лонг' : 'Шорт') . "\n" .
            "Текущая цена: $currentPrice\n" .
            "Целевая цена: {$this->trade->take_profit_price}\n" .
            "Потенциальная прибыль: $profit USDT";

        $this->telegram->sendMessage($message);
    }

    private function sendAveragingAlert(array $level): void
    {
        $message = "🔄 Сигнал на усреднение\n" .
            "Символ: {$this->trade->currency->name}\n" .
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

    private function calculateAveragePrice(float $entryPrice, float $newPrice, float $entrySize, float $additionalSize): float
    {
        $totalSize = $entrySize + $additionalSize;
        return (($entryPrice * $entrySize) + ($newPrice * $additionalSize)) / $totalSize;
    }

    private function calculateProfit(float $exitPrice): float
    {
        $entryPrice = $this->trade->getAverageEntryPrice();
        $positionSize = (float)$this->trade->position_size;
        $leverage = (float)$this->trade->leverage;

        if ($this->trade->isTypeLong()) {
            $priceChange = ($exitPrice - $entryPrice) / $entryPrice; // Процент изменения цены
        } else {
            $priceChange = ($entryPrice - $exitPrice) / $entryPrice; // Для шорта наоборот
        }

        // Прибыль = Размер позиции * Процент изменения цены * Плечо
        $profit = $positionSize * $priceChange * $leverage;

        return round($profit, 2); // Округляем до 2 знаков после запятой
    }

    private function getBuyPointsHistory(): array
    {
        $points = [];
        $x = 0.1; // Начинаем слева
        $centerX = 0.5; // Центральная точка

        // Начальная точка входа
        $points[] = [
            'x' => $x,
            'y' => (float)$this->trade->entry_price,
            'dataLabels' => [
                'enabled' => true,
                'format' => 'Вход: {y:.2f}'
            ]
        ];

        // Получаем все ордера усреднения в хронологическом порядке
        $averagingOrders = $this->trade->orders()
            ->where('type', 'add')
            ->orderBy('created_at')
            ->get();

        // Распределяем точки между началом и центром
        $stepX = ($centerX - $x) / (count($averagingOrders) + 1);

        foreach ($averagingOrders as $order) {
            $x += $stepX;
            $points[] = [
                'x' => $x,
                'y' => (float)$order->price,
                'dataLabels' => [
                    'enabled' => true,
                    'format' => 'Закуп: {y:.2f}'
                ]
            ];
        }

        // Добавляем текущую цену в центр
        $points[] = [
            'x' => $centerX,
            'y' => (float)$this->trade->currency->last_price,
            'dataLabels' => [
                'enabled' => true,
                'format' => 'Текущая: {y:.2f}'
            ]
        ];

        return $points;
    }
}
