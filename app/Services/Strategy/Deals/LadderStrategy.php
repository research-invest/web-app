<?php

namespace App\Services\Strategy\Deals;

use App\Models\Trade;
use App\Models\TradePnlHistory;
use Illuminate\Support\Collection;

/**
 * Лестница + трейлинг — сбалансированный вариант
 */
class LadderStrategy
{

    public function __construct(
        protected       $entryPrice,
        protected       $initialCapital,
        protected       $profitTarget = 0.1,
        protected array $buySteps = [0, -0.05, -0.1],
        protected array $sellSteps = [0.1, 0.15, 0.2],
    )
    {
    }

    public function calculate(): array
    {
        $buyLevels = [];
        $sellLevels = [];
        $totalInvestment = 0;
        $size = $this->initialCapital / count($this->buySteps); // Равномерный размер

        foreach ($this->buySteps as $step) {
            $buyPrice = round($this->entryPrice * (1 + $step), 2);
            $buyLevels[] = ['price' => $buyPrice, 'size' => $size];
            $totalInvestment += $size;
        }

        foreach ($this->sellSteps as $step) {
            $sellPrice = round($this->entryPrice * (1 + $step), 2);
            $sellLevels[] = ['price' => $sellPrice, 'size' => $size];
        }

        $profitTargetValue = $totalInvestment * (1 + $this->profitTarget);

        return [
            'buy_levels' => $buyLevels,
            'sell_levels' => $sellLevels,
            'expected_profit' => round($profitTargetValue, 2)
        ];
    }

    public function calculateByExistingOrders(Collection $existingOrders): array
    {
        $nextOrders = [];
        $totalInvestment = $existingOrders->sum('size');

        foreach ($this->buySteps as $step) {
            $buyPrice = round($this->entryPrice * (1 + $step), 2);
            if (!$existingOrders->pluck('price')->contains($buyPrice)) {
                $size = ($this->initialCapital - $totalInvestment) / count($this->buySteps);
                $nextOrders[] = ['price' => $buyPrice, 'size' => $size];
            }
        }

        // Рассчитываем целевой уровень выхода
        $profitTargetValue = $totalInvestment * (1 + $this->profitTarget);
        $sellTarget = round($profitTargetValue / count($existingOrders), 2);

        return [
            'existing_orders' => $existingOrders,
            'next_orders' => $nextOrders,
            'sell_target' => $sellTarget,
            'expected_profit' => round($profitTargetValue, 2)
        ];
    }


    public function getChartConfig(Trade $trade): array
    {
        $orderData = $this->calculateByExistingOrders($trade->orders);

        $existingOrders = $orderData['existing_orders'];
        $nextOrders = $orderData['next_orders'];
        $sellTarget = $orderData['sell_target'];

        $buyPoints = [];
        $averagePoints = [];
        $sellPoints = [['x' => count($existingOrders) + count($nextOrders), 'y' => $sellTarget]];

        // Получаем текущую цену (последняя цена в списке ордеров)
        $currentPrice = $trade->currency->last_price;
        $currentPricePoint = [['x' => 0, 'y' => $currentPrice]];

        foreach ($existingOrders as $order) {
            $buyPoints[] = ['x' => count($buyPoints), 'y' => (float)$order['price']];
        }

        foreach ($nextOrders as $order) {
            $averagePoints[] = ['x' => count($buyPoints) + count($averagePoints), 'y' => $order['price']];
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400
            ],
            'title' => [
                'text' => "Ценовые уровни: Ladder-стратегия",
                'align' => 'left'
            ],
            'xAxis' => [
                'visible' => false,
                'min' => 0,
                'max' => count($existingOrders) + count($nextOrders) + 1
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Цена'
                ],
                'labels' => [
                    'format' => '{value:.2f}'
                ]
            ],
            'series' => [
                [
                    'name' => 'Текущая цена',
                    'data' => $currentPricePoint,
                    'color' => '#666666',
                    'type' => 'scatter',
                    'marker' => [
                        'symbol' => 'circle',
                        'radius' => 6
                    ]
                ],
                [
                    'name' => 'Точки входа',
                    'data' => $buyPoints,
                    'color' => '#007bff',
                    'type' => 'scatter',
                    'marker' => [
                        'symbol' => 'circle',
                        'radius' => 6
                    ]
                ],
                [
                    'name' => 'Точки усреднения',
                    'data' => $averagePoints,
                    'color' => '#f0ad4e',
                    'type' => 'scatter',
                    'marker' => [
                        'symbol' => 'diamond',
                        'radius' => 6
                    ]
                ],
                [
                    'name' => 'Точка выхода',
                    'data' => $sellPoints,
                    'color' => '#22bb33',
                    'type' => 'scatter',
                    'marker' => [
                        'symbol' => 'triangle',
                        'radius' => 8
                    ]
                ]
            ],
            'credits' => [
                'enabled' => false
            ],
            'accessibility' => [
                'enabled' => false
            ]
        ];
    }

}
