<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals;

use App\Helpers\StringHelper;
use App\Models\Trade;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'trades';


    public $template = 'orchid.trades.table';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->width(20)
                ->render(fn(Trade $trade) => Link::make((string)$trade->id)
                    ->rawClick()
                    ->route('platform.trading.deal.edit', $trade)
                ),

            TD::make('currency', 'Пара')
                ->render(fn(Trade $trade) => Link::make($trade->currency->name)
                    ->rawClick()
                    ->route('platform.trading.deal.edit', $trade)
                ),

            TD::make('position_type', 'Тип'),
            TD::make('position_size', 'Размер'),
            TD::make('entry_price', 'Цена входа'),

            TD::make('price', 'Текущая цена')
                ->render(fn(Trade $trade) => $trade->currency->last_price),

            TD::make('exit_price', 'Цена выхода'),
            TD::make('pnl', 'PNL')
                ->render(function (Trade $trade) {
                    $pnl = $trade->status === Trade::STATUS_OPEN
                        ? $trade->getUnrealizedPnL($trade->currency->last_price)
                        : $trade->realized_pnl;

                    $color = $pnl >= 0 ? 'success' : 'danger';

                    return "<span class='text-{$color}'>" . number_format((float)$pnl, 2) . " USDT</span>";
                })
                ->alignRight(),
            TD::make('leverage', 'Плечо'),
            TD::make('status', 'Статутс'),

            TD::make('created_at', 'Дата открытия')
                ->sort()
                ->render(fn (Trade $trade) =>
                    $trade->created_at->toDateTimeString()
                )->defaultHidden(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Trade $trade) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Изменить'))
                            ->route('platform.trading.deal.edit', $trade->id)
                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm('Вы уверены?')
                            ->method('remove', [
                                'id' => $trade->id,
                            ]),
                    ])),
        ];
    }
}
