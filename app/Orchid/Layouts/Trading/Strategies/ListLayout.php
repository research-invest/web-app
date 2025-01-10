<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Strategies;

use App\Models\TradePeriod;
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
    public $target = 'strategies';


    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->width(20),

            TD::make('name', 'Название'),
            TD::make('description', 'Описание'),

//            TD::make(__('Actions'))
//                ->align(TD::ALIGN_CENTER)
//                ->width('100px')
//                ->render(fn(TradePeriod $period) => DropDown::make()
//                    ->icon('bs.three-dots-vertical')
//                    ->list([
//                        Link::make('Открыть отчет')
//                            ->target('_blank')
//                            ->route('platform.trading.report', $period->id)
//                            ->icon('notebook'),
////                        Link::make(__('Изменить'))
////                            ->route('platform.trading.deal.edit', $period->id)
////                            ->icon('bs.pencil'),
//                    ])),
        ];
    }

    protected function striped(): bool
    {
        return true;
    }
}
