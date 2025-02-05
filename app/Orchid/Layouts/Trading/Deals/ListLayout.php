<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals;

use App\Helpers\MathHelper;
use App\Helpers\StringHelper;
use App\Models\Currency;
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
                ->render(function(Trade $trade) {
                    $route = route('platform.trading.deal.edit', $trade);
                    $text = $trade->currency->name;
                    if ($trade->is_fake) {
                        $text .= Trade::FAKE_TRADE_TEXT;
                        return "<a href='{$route}' class='text-danger'>{$text}</a>";
                    }
                    return "<a data-turbo=\"false\" href='{$route}'>{$text}</a>";
                }),

            TD::make('position_type', 'Тип'),
            TD::make('position_size', 'Размер'),
            TD::make('entry_price', 'Цена входа')
                ->render(fn(Trade $trade) => MathHelper::formatNumber((float)$trade->entry_price)
                ),

            TD::make('price', 'Текущая цена')
                ->render(fn(Trade $trade) => MathHelper::formatNumber((float)$trade->currency->last_price)),

            TD::make('exit_price', 'Цена выхода')
                ->render(fn(Trade $trade) => MathHelper::formatNumber((float)$trade->exit_price)
                ),

            TD::make('liquidation', 'Ликвидация')
                ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->getLiquidationPrice())
                ),
            TD::make('leverage', 'Плечо'),

            TD::make('realized_pnl', 'PNL')
                ->render(function (Trade $trade) {
                    $pnl = $trade->currentPnL;
                    $color = $pnl >= 0 ? 'success' : 'danger';

                    return "<span class='text-{$color}'>" . number_format((float)$pnl, 2) . " USDT</span>";
                })
                ->sort()
                ->alignRight(),

            TD::make('profit_percentage', 'roe')
            ->defaultHidden()
            ->sort(),

            TD::make('status', 'Статус'),

            TD::make('open_currency_volume', 'Объем открытия сделки')
                ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->open_currency_volume))
                ->defaultHidden(),

            TD::make('close_currency_volume', 'Объем закрытия сделки')
                ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->close_currency_volume))
                ->defaultHidden(),

            TD::make('created_at', 'Дата открытия')
                ->sort()
                ->render(fn(Trade $trade) => $trade->created_at->toDateTimeString()
                )->defaultHidden(),

            TD::make('duration_time', 'Длительность')
                ->render(fn(Trade $trade) => $trade->getDurationTime()
                )->defaultHidden(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Trade $trade) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Перейти в валюту')
                            ->route('platform.currencies.edit', $trade->currency_id)
                            ->target('_blank')
                            ->icon('dollar'),

                        Link::make('Открыть TradingView')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($trade->currency->getTVLink()),

                        Link::make(__('Изменить'))
                            ->rawClick()
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
