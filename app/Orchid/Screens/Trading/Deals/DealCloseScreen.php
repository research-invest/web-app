<?php

namespace App\Orchid\Screens\Trading\Deals;

use App\Models\Trade;
use App\Models\TradePeriod;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DealCloseScreen extends Screen
{
    public $trade;

    public function name(): ?string
    {
        return 'Закрытие сделки #' . $this->trade->id;
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
            Button::make('Закрыть сделку')
                ->icon('check')
                ->method('closeTrade')
                ->class('btn btn-warning'),

            Button::make('Отмена')
                ->icon('close')
                ->method('cancel')
                ->class('btn btn-secondary')
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('trade.exit_price')
                    ->title('Цена выхода')
                    ->type('number')
                    ->step('0.00000001')
                    ->required()
                    ->value($this->trade->currency->last_price ?? $this->trade->entry_price),

                Select::make('trade.close_reason')
                    ->title('Причина закрытия')
                    ->options([
                        'manual' => 'Ручное закрытие',
                        'tp' => 'Take Profit',
                        'sl' => 'Stop Loss',
                        'liquidation' => 'Ликвидация'
                    ])
                    ->required(),

                Group::make([
                    Input::make('trade.commission_open')
                        ->title('Комиссия за открытие сделки')
                        ->step('0.01')
                        ->type('number'),

                    Input::make('trade.commission_close')
                        ->title('Комиссия за закрытие сделки')
                        ->step('0.01')
                        ->type('number'),

                    Input::make('trade.commission_finance')
                        ->title('Комиссия за финансирование')
                        ->step('0.01')
                        ->type('number'),
                ]),

                Input::make('trade.realized_pnl')
                    ->title('Реализованный PnL'),

                TextArea::make('trade.notes')
                    ->title('Комментарий к закрытию')
                    ->rows(3),
            ])
        ];
    }

    public function closeTrade(Trade $trade, Request $request)
    {
        $data = $request->get('trade');

        $exitPrice = $data['exit_price'];
        $closeReason = $data['close_reason'];

        $averagePrice = $trade->getAverageEntryPrice();

        // Расчет P&L
        if ($trade->isTypeLong()) {
            $pnl = ($exitPrice - $averagePrice) * $trade->position_size * $trade->leverage / $averagePrice;
        } else {
            $pnl = ($averagePrice - $exitPrice) * $trade->position_size * $trade->leverage / $averagePrice;
        }

        $currentPeriod = TradePeriod::isActive()
            ->latest()
            ->byCreator()
            ->first();

        $trade
            ->fill($data)
            ->fill([
                'status' => $closeReason === 'liquidation' ? Trade::STATUS_LIQUIDATED : Trade::STATUS_CLOSED,
                'exit_price' => $exitPrice,
                'realized_pnl' => empty($data['realized_pnl']) ? $pnl : $data['realized_pnl'],
                'closed_at' => now(),
                'notes' => $trade->notes . "\n\nЗакрытие: " . $request->input('notes'),
                'close_currency_volume' => $trade->currency->volume,
                'profit_percentage' => $trade->getProfitPercentage(),
                'trade_period_id' => $currentPeriod?->id, // Устанавливаем период по дате закрытия
            ]);

        $trade->save();

        // Создаем ордер закрытия
        $trade->orders()->create([
            'price' => $exitPrice,
            'size' => $trade->position_size,
            'type' => 'exit',
            'executed_at' => now()
        ]);

        Toast::success('Сделка успешно закрыта');

        return redirect()->route('platform.trading.deals');
    }

    public function cancel()
    {
        return redirect()->route('platform.trading.deal.edit', $this->trade->id);
    }
}
