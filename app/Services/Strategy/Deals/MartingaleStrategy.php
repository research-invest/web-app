<?php

namespace App\Services\Strategy\Deals;

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

    public function calculateByExistingOrders(array $existingOrders): array
    {
        $totalInvestment = array_sum(array_column($existingOrders, 'size')); // Общая вложенная сумма
        $nextOrders = [];

        $size = end($existingOrders)['size'] * 2; // Удваиваем объем последнего ордера

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

}
