<?php

namespace App\Services\Strategy\Deals;

use App\Models\Trade;
use App\Models\TradePnlHistory;
use Illuminate\Support\Collection;

class ATRStrategy
{

    public function __construct(
        protected $entryPrice,
        protected $initialCapital,
        protected $atr = 5,
        protected $multiplier = 1.5,
        protected $levels = 10,
        protected $profitTarget = 0.1,
    )
    {
    }

    public function calculate(): array
    {
        $buyLevels = [];
        $totalInvestment = 0;
        $size = $this->initialCapital / $this->levels; // Равномерное распределение средств

        for ($i = 1; $i <= $this->levels; $i++) {
            $buyPrice = round($this->entryPrice - ($this->atr * $this->multiplier * $i), 2);
            $buyLevels[] = ['price' => $buyPrice, 'size' => $size];
            $totalInvestment += $size;
        }

        // Цель прибыли
        $profitTargetValue = $totalInvestment * (1 + $this->profitTarget);
        $sellTarget = round($totalInvestment / $this->levels * (1 + $this->profitTarget), 2);

        return [
            'buy_levels' => $buyLevels,
            'sell_target' => $sellTarget,
            'expected_profit' => round($profitTargetValue, 2)
        ];
    }


    public function calculateByExistingOrders(Collection $existingOrders): array
    {
        $totalInvestment = $existingOrders->sum('size');

        $nextOrders = [];

        for ($i = count($existingOrders) + 1; $i <= $this->levels; $i++) {
            $buyPrice = round($this->entryPrice - ($this->atr * $this->multiplier * $i), 2);
            $size = ($this->initialCapital - $totalInvestment) / ($this->levels - count($existingOrders));
            $nextOrders[] = ['price' => (float)$buyPrice, 'size' => (float)$size];
        }

        // Рассчитываем целевой уровень выхода
        $profitTargetValue = $totalInvestment * (1 + $this->profitTarget);
        $sellTarget = round($profitTargetValue / count($existingOrders), 2);

        return [
            'existing_orders' => $existingOrders,
            'next_orders' => $nextOrders,
            'sell_target' => (float)$sellTarget,
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
                'text' => "Ценовые уровни: ATR-стратегия",
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
