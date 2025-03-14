<?php

namespace App\Services\Strategy\Deals;

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

    public function calculateByExistingOrders(array $existingOrders): array
    {
        $totalInvestment = array_sum(array_column($existingOrders, 'size')); // Общая вложенная сумма
        $nextOrders = [];

        foreach ($this->buySteps as $step) {
            $buyPrice = round($this->entryPrice * (1 + $step), 2);
            if (!in_array($buyPrice, array_column($existingOrders, 'price'), true)) {
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

}
