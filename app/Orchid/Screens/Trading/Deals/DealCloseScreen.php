<?php

namespace App\Orchid\Screens\Trading\Deals;

use App\Models\Trade;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
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
                Input::make('exit_price')
                    ->title('Цена выхода')
                    ->type('number')
                    ->step('0.00000001')
                    ->required()
                    ->value($this->trade->currency->last_price ?? $this->trade->entry_price),

                Select::make('close_reason')
                    ->title('Причина закрытия')
                    ->options([
                        'manual' => 'Ручное закрытие',
                        'tp' => 'Take Profit',
                        'sl' => 'Stop Loss',
                        'liquidation' => 'Ликвидация'
                    ])
                    ->required(),

                TextArea::make('notes')
                    ->title('Комментарий к закрытию')
                    ->rows(3),
            ])
        ];
    }

    public function closeTrade(Trade $trade, Request $request)
    {
        $exitPrice = $request->input('exit_price');
        $closeReason = $request->input('close_reason');

        $averagePrice = $trade->getAverageEntryPrice();

        // Расчет P&L
        if ($trade->position_type === 'long') {
            $pnl = ($exitPrice - $averagePrice) * $trade->position_size * $trade->leverage / $averagePrice;
        } else {
            $pnl = ($averagePrice - $exitPrice) * $trade->position_size * $trade->leverage / $averagePrice;
        }

        // Обновляем сделку
        $trade->update([
            'status' => $closeReason === 'liquidation' ? 'liquidated' : 'closed',
            'exit_price' => $exitPrice,
            'realized_pnl' => $pnl,
            'closed_at' => now(),
            'notes' => $trade->notes . "\n\nЗакрытие: " . $request->input('notes'),
            'close_currency_volume' => $trade->currency->volume,
        ]);

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
