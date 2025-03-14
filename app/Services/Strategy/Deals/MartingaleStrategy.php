<?php

namespace App\Services\Strategy\Deals;

use App\Models\Trade;
use App\Models\TradePnlHistory;
use Illuminate\Support\Collection;

class MartingaleStrategy
{

    public function __construct(
        protected $entryPrice,
        protected $initialCapital,
        protected $step = 0.05,
        protected $profitTarget = 0.1,
    )
    {
    }

    function calculate(): array
    {
        $buyLevels = [];
        $size = $this->initialCapital / 15; // Начальный объем небольшой
        $totalInvestment = 0;

        for ($i = 1; $i <= 5; $i++) {
            $buyPrice = round($this->entryPrice * (1 - $this->step * $i), 2);
            $buyLevels[] = ['price' => $buyPrice, 'size' => $size];
            $totalInvestment += $size;
            $size *= 2; // Увеличение объема
        }

        $profitTargetValue = $totalInvestment * (1 + $this->profitTarget);
        $sellTarget = round($profitTargetValue / 5, 2);

        return [
            'buy_levels' => $buyLevels,
            'sell_target' => $sellTarget,
            'expected_profit' => round($profitTargetValue, 2)
        ];
    }

    public function calculateByExistingOrders(Collection $existingOrders): array
    {
        $nextOrders = [];
        $totalInvestment = $existingOrders->sum('size');

        $size = $existingOrders->last()['size'] * 2;

        for ($i = 1; $i <= 3; $i++) {
            $buyPrice = round($this->entryPrice * (1 - $this->step * ($i + count($existingOrders))), 2);
            $nextOrders[] = ['price' => $buyPrice, 'size' => $size];
            $size *= 2;
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
                'text' => "Ценовые уровни: Martingale-стратегия",
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
