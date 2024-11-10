<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals;

use App\Helpers\StringHelper;
use App\Models\Trade;
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

            TD::make('position_type', 'Тип позиции'),
            TD::make('entry_price', 'Цена входа'),
            TD::make('leverage', 'Плечо'),
            TD::make('currency', 'Валюта')
                ->render(fn (Trade $trade) => $trade->currency->name),
            TD::make('status', 'Статутс'),

//            TD::make('is_active', '')
//                ->render(fn (Trade $trade) =>
//                     $trade->currency->name
//                        ? '<i class="text-success">●</i>'
//                        : '<i class="text-danger">●</i>'
//                ),
//
//            TD::make('is_temp_card', 'Временная карта')
//                ->popover('Добавленная в заявке')
//                ->render(fn (Trade $trade) =>
//                     $trade->is_temp_card
//                        ? '<i class="text-success">●</i> Да'
//                        : '<i class="text-danger">●</i> Нет'
//                ),
//
//            TD::make('currency_id', 'Банк')
//                ->render(fn(Trade $trade) => $trade->currency->name
//                ),
//
//            TD::make('card', 'Номер')
//                ->render(function (Trade $trade) {
//                    return $trade->format_card;
//                }),
//
//            TD::make('fio', 'ФИО'),
//            TD::make('description', 'Описание'),
//            TD::make('status', 'Статус')->render(fn (Trade $trade) =>
//                $trade->getStatusName()
//            ),
//
//            TD::make('last_used_at', 'Последнее использование')
//                ->sort()
//                ->render(fn (Trade $trade) =>
//                    $trade->last_used_at?->toDateTimeString()
//                ),

            TD::make('created_at', 'Дата добавления')
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
                    ])),
        ];
    }
}
