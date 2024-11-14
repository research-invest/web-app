<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Deals;


use App\Models\Currency;
use App\Models\Trade;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\RiskManagement\PositionCalculator;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Picture;
use Orchid\Screen\Fields\RadioButtons;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DealEditScreen extends Screen
{

    public $trade;

    public function name(): ?string
    {
        return $this->trade->exists ? 'Редактирование сделки' : 'Новая сделка';
    }

    public function query(Trade $trade): iterable
    {
        $this->trade = $trade;

        return [
            'trade' => $trade
        ];
    }

    public function commandBar(): iterable
    {
        return [

            Link::make('Калькулятор')
                ->icon('calculator')
                ->target('_blank')
                ->route('platform.trading.futures-calculator', ['trade_id' => $this->trade->id])
                ->class('btn btn-info'),

            ModalToggle::make('Добавить ордер')
                ->modal('addOrderModal')
                ->method('addOrder')
                ->icon('plus')
                ->class('btn btn-primary')
                ->canSee($this->trade->exists && $this->trade->status === 'open'),

            Button::make('Сохранить')
                ->icon('save')
                ->method('save'),

            Button::make('Закрыть сделку')
                ->icon('check')
                ->method('closeTrade')
                ->class('btn btn-warning')
                ->canSee($this->trade->exists && $this->trade->status === 'open'),

            Button::make('Удалить')
                ->icon('trash')
                ->method('remove')
                ->confirm('Уверены?')
                ->class('btn btn-danger')
                ->canSee($this->trade->exists),

        ];
    }

    public function layout(): iterable
    {
        return [
            // Модальное окно для добавления ордера
            Layout::modal('addOrderModal', [
                Layout::rows([
                    Input::make('price')
                        ->title('Цена')
                        ->type('number')
                        ->step('0.00000001')
                        ->required(),

                    Input::make('size')
                        ->title('Размер (USDT)')
                        ->type('number')
                        ->required(),

                    Select::make('type')
                        ->title('Тип ордера')
                        ->options([
                            'add' => 'Дополнительный вход',
                            'exit' => 'Частичное закрытие',
                        ])
                        ->required(),

                    TextArea::make('notes')
                        ->title('Комментарий')
                        ->rows(3),
                ])
            ])->title('Добавить ордер'),

            Layout::tabs([
                'Основная информация' => [
                    Layout::rows([
//                        Select::make('trade.currency_id')
//                            ->fromModel(Currency::class, 'code', 'id')
//                            ->title('Валюта')
//                            ->required(),

                        Relation::make('trade.currency_id')
                            ->fromModel(Currency::class, 'code', 'id')
                            ->displayAppend('namePrice')
                            ->required()
                            ->title('Выберите валюту'),

                        RadioButtons::make('trade.position_type')
                            ->title('Тип позиции')
                            ->options([
                                'long' => 'Лонг',
                                'short' => 'Шорт'
                            ])
                            ->required(),

                        Group::make([
                            Input::make('trade.entry_price')
                                ->title('Цена входа')
                                ->type('number')
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.position_size')
                                ->title('Размер позиции (USDT)')
                                ->type('number')
                                ->required(),

                            Input::make('trade.leverage')
                                ->title('Плечо')
                                ->type('number')
                                ->min(1)
                                ->max(125)
                                ->required(),
                        ]),

                        Group::make([
                            Input::make('trade.stop_loss_price')
                                ->title('Стоп-лосс')
                                ->type('number')
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.take_profit_price')
                                ->title('Тейк-профит')
                                ->type('number')
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.target_profit_amount')
                                ->title('Целевая прибыль ($)')
                                ->type('number'),
                        ]),

                        Select::make('trade.status')
                            ->title('Статус')
                            ->options([
                                'open' => 'Открыта',
                                'closed' => 'Закрыта',
                                'liquidated' => 'Ликвидирована'
                            ])
                            ->required(),

                        TextArea::make('trade.notes')
                            ->title('Заметки')
                            ->rows(3),
                    ])
                ],

                'Ордера' => [
                    Layout::view('trading.trade-orders', ['trade' => $this->trade])
                ],

                'Статистика' => [
                    Layout::view('trading.trade-stats', ['trade' => $this->trade])
                ],

                'Потенциальный P&L' => [
                    Layout::view('trading.trade-potential-pnl', [
                        'trade' => $this->trade,
                        'steps' => $this->calculatePnLSteps($this->trade)
                    ])
                ],

                'Управление рисками' => [
                    new HighchartsChart(
                        $this->getRiskManagementChart()
                    ),
                ],
            ])
        ];
    }

    private function getRiskManagementChart(): array
    {
        if(!$this->trade->exist){
            return [];
        }

        $calculator = new PositionCalculator(
            trade: $this->trade,
        );

        return $calculator->getChartConfig();
    }

    public function save(Trade $trade, Request $request)
    {
        $data = $request->get('trade');

        // Если это новая сделка
        if (!$trade->exists) {
            $trade->fill($data);
            $trade->save();

            // Создаем первый ордер при создании сделки
            $trade->orders()->create([
                'price' => $trade->entry_price,
                'size' => $trade->position_size,
                'type' => 'entry',
                'executed_at' => now()
            ]);

            Toast::success('Сделка создана');
        } else {
            $trade->fill($data)->save();
            Toast::success('Сделка обновлена');
        }

        return redirect()->route('platform.trading.deal.edit', $trade->id);
    }

    public function closeTrade(Trade $trade, Request $request)
    {
        return redirect()->route('platform.trading.deal.close', $trade->id);
    }

    public function remove(Trade $trade)
    {
        $trade->delete();

        Toast::success('Сделка удалена');

        return redirect()->route('platform.trading.deals');
    }


    /**
     * Добавление нового ордера
     */
    public function addOrder(Trade $trade, Request $request)
    {
        $order = $trade->orders()->create([
            'price' => $request->input('price'),
            'size' => $request->input('size'),
            'type' => $request->input('type'),
            'executed_at' => now(),
        ]);

        // Обновляем общий размер позиции
        if ($request->input('type') === 'add') {
            $trade->position_size += $request->input('size');
        } elseif ($request->input('type') === 'exit') {
            $trade->position_size -= $request->input('size');
        }

        // Если позиция полностью закрыта
        if ($trade->position_size <= 0) {
            $trade->status = 'closed';
            $trade->closed_at = now();

            // Расчет P&L для закрытой части
            $pnl = $this->calculatePartialPnl(
                $trade->position_type,
                $trade->entry_price,
                $request->input('price'),
                $request->input('size'),
                $trade->leverage
            );

            $trade->realized_pnl = ($trade->realized_pnl ?? 0) + $pnl;
        }

        $trade->save();

        Toast::success('Ордер добавлен');
    }

    /**
     * Расчет P&L для частичного закрытия
     */
    private function calculatePartialPnl(
        string $positionType,
        float  $entryPrice,
        float  $exitPrice,
        float  $size,
        int    $leverage
    ): float
    {
        if ($positionType === 'long') {
            return ($exitPrice - $entryPrice) * $size * $leverage / $entryPrice;
        }
        return ($entryPrice - $exitPrice) * $size * $leverage / $entryPrice;
    }

    /**
     * Расчет шагов для таблицы потенциального P&L
     */
    private function calculatePnLSteps(Trade $trade): array
    {
        if (!$trade->exists) {
            return [];
        }

        // Получаем среднюю цену входа
        $averagePrice = $trade->getAverageEntryPrice();

        // Определяем диапазон цен (±50% от средней цены)
        $maxPrice = $averagePrice * 1.5;
        $minPrice = $averagePrice * 0.5;

        // Рассчитываем оптимальное количество шагов (например, 20 шагов)
        $steps = 20;
        $stepSize = ($maxPrice - $minPrice) / $steps;

        $results = [];

        // Генерируем строки для таблицы
        for ($i = 0; $i <= $steps; $i++) {
            $price = $trade->isTypeLong() ?
                $minPrice + ($stepSize * $i) :
                $maxPrice - ($stepSize * $i);

            // Расчет P&L для текущей цены
            $pnl = $trade->isTypeLong()
                ? ($price - $averagePrice) * $trade->position_size * $trade->leverage / $averagePrice
                : ($averagePrice - $price) * $trade->position_size * $trade->leverage / $averagePrice;

            // Расчет ROE (Return on Equity)
            $roe = ($pnl / $trade->position_size) * 100;

            $results[] = [
                'price' => $price,
                'pnl' => $pnl,
                'roe' => $roe,
                'price_change' => (($price - $averagePrice) / $averagePrice) * 100,
                'is_current' => false, // будет обновляться в шаблоне
                'is_tp' => abs($price - $trade->take_profit_price) < $stepSize,
                'is_sl' => abs($price - $trade->stop_loss_price) < $stepSize,
            ];
        }

        return $results;
    }
}
