<?php

namespace App\Orchid\Screens\Trading;

use Orchid\Screen\Screen;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Group;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Actions\Button;
use Illuminate\Http\Request;

class FuturesCalculator extends Screen
{
    public $result = [];
    public $formData = [];

    public function name(): ?string
    {
        return 'Калькулятор сделки';
    }

    public function query(): iterable
    {
        return [
            'result' => $this->result,
            'formData' => $this->formData
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [

            Layout::view('trading.futures-calculator-results'),

            Layout::rows([
                Group::make([
                    Input::make('entry_price')
                        ->title('Цена входа')
                        ->type('number')
                        ->step('0.00000001')
                        ->value($this->formData['entry_price'] ?? null)
                        ->required(),

                    Input::make('position_size')
                        ->title('Размер позиции (USDT)')
                        ->type('number')
                        ->value($this->formData['position_size'] ?? null)
                        ->required(),

                    Input::make('leverage')
                        ->title('Плечо')
                        ->type('number')
                        ->min(1)
                        ->max(125)
                        ->value($this->formData['leverage'] ?? null)
                        ->required(),
                ]),

                Group::make([
                    Input::make('stop_loss_percent')
                        ->title('Стоп-лосс (%)')
                        ->type('number')
                        ->step('0.01')
                        ->value($this->formData['stop_loss_percent'] ?? null)
                        ->required(),

                    Input::make('take_profit_percent')
                        ->title('Тейк-профит (%)')
                        ->type('number')
                        ->step('0.01')
                        ->value($this->formData['take_profit_percent'] ?? null)
                        ->required(),
                ]),

                Button::make('Рассчитать')
                    ->method('calculate')
                    ->class('btn btn-primary')
            ])->title('Основные параметры'),

            Layout::rows([
                // Динамические поля для дополнительных ордеров
                ...collect($this->formData['additional_orders'] ?? [])
                    ->map(function ($order, $index) {
                        return Group::make([
                            Input::make("additional_orders[$index][price]")
                                ->title('Цена')
                                ->type('number')
                                ->value($order['price'] ?? null)
                                ->step('0.00000001'),

                            Input::make("additional_orders[$index][size]")
                                ->title('Размер (USDT)')
                                ->type('number')
                                ->value($order['size'] ?? null),

                            Button::make('Удалить')
                                ->method('removeOrder', ['index' => $index])
                                ->class('btn btn-danger')
                        ]);
                    })->toArray(),

                // Кнопка для добавления нового ордера
                Button::make('Добавить ордер')
                    ->method('addOrder')
                    ->class('btn btn-secondary'),
            ])->title('Дополнительные ордера'),

        ];
    }

    public function addOrder()
    {
        if (!isset($this->formData['additional_orders'])) {
            $this->formData['additional_orders'] = [];
        }
        $this->formData['additional_orders'][] = ['price' => null, 'size' => null];
    }

    public function calculate(Request $request)
    {
        $this->formData = $request->all();

        // Очищаем пустые ордера
        $this->formData['additional_orders'] = collect($this->formData['additional_orders'] ?? [])
            ->filter(function ($order) {
                return !empty($order['price']) && !empty($order['size']);
            })->values()->toArray();

        $entryPrice = (float)$request->input('entry_price');
        $positionSize = (float)$request->input('position_size');
        $leverage = (int)$request->input('leverage');
        $stopLossPercent = (float)$request->input('stop_loss_percent');
        $takeProfitPercent = (float)$request->input('take_profit_percent');
        $additionalOrders = $this->formData['additional_orders'];

        // Базовые расчеты
        $contractSize = $positionSize * $leverage;
        $margin = $positionSize;

        // Расчет с учетом дополнительных ордеров
        $totalSize = $positionSize;
        $totalContracts = $contractSize;
        $averagePrice = $entryPrice;
        $totalMargin = $margin;

        foreach ($additionalOrders as $order) {
            $orderPrice = (float)$order['price'];
            $orderSize = (float)$order['size'];

            $orderContracts = $orderSize * $leverage;
            $totalContracts += $orderContracts;
            $totalSize += $orderSize;
            $totalMargin += $orderSize;

            $averagePrice = (($averagePrice * $contractSize) + ($orderPrice * $orderContracts)) /
                           ($contractSize + $orderContracts);
        }

        // Пересчитываем цену ликвидации с учетом средней цены
        $maintenanceMargin = 0.005; // 0.5% (может отличаться в зависимости от биржи)
        $liquidationPrice = $averagePrice * (1 - (1 / $leverage) + $maintenanceMargin);

        // Расчет стопов и профита
        $stopLossPrice = $averagePrice * (1 - ($stopLossPercent / 100));
        $takeProfitPrice = $averagePrice * (1 + ($takeProfitPercent / 100));

        // Расчет потенциальной прибыли/убытка
        $potentialLoss = ($averagePrice - $stopLossPrice) * $totalContracts / $averagePrice;
        $potentialProfit = ($takeProfitPrice - $averagePrice) * $totalContracts / $averagePrice;

        $this->result = [
            'average_price' => round($averagePrice, 8),
            'total_position_size' => round($totalSize, 2),
            'total_contracts' => round($totalContracts, 2),
            'total_margin' => round($totalMargin, 2),
            'effective_leverage' => round($totalContracts / $totalMargin, 2),
            'liquidation_price' => round($liquidationPrice, 8),
            'stop_loss_price' => round($stopLossPrice, 8),
            'take_profit_price' => round($takeProfitPrice, 8),
            'potential_loss' => round($potentialLoss, 2),
            'potential_profit' => round($potentialProfit, 2),
            'risk_reward_ratio' => round(abs($potentialProfit / $potentialLoss), 2),
        ];
    }

    public function removeOrder(Request $request)
    {
        $index = $request->get('index');
        if (isset($this->formData['additional_orders'][$index])) {
            unset($this->formData['additional_orders'][$index]);
            $this->formData['additional_orders'] = array_values($this->formData['additional_orders']);
        }
    }
}
