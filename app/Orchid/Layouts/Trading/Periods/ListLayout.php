<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Periods;

use App\Models\TradePeriod;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class ListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'periods';


    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->width(20),

            TD::make('name', 'Название'),
            TD::make('start_date', 'Дата начала')
                ->render(fn (TradePeriod $period) =>
                    $period->start_date->toDateString()
                ),
            TD::make('start_date', 'Дата окончания')
                ->render(fn (TradePeriod $period) =>
                    $period->end_date->toDateString()
                ),

            TD::make('daily_target', 'Дневная цель'),
            TD::make('weekend_target', 'Цель выходного дня'),

            TD::make('is_active', 'Активность')
                ->render(function (TradePeriod $period) {

                    return Button::make($period->is_active ? 'Деактивировать' : 'Активировать')
                        ->icon('bs.check-circle')
                        ->method('togglePeriodActive')
                        ->novalidate()
                        ->parameters([
                            'period' => $period->id
                        ])
                        ->class($period->is_active ? 'text-success' : 'text-muted');

                    $status = $period->is_active ? 'active' : 'finish';
                    $color = $period->is_active ? 'success' : 'danger';
                    return "<span class='text-{$color}'>" . $status . "</span>";
                })
                ->alignRight(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(TradePeriod $period) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
//                        Link::make(__('Изменить'))
//                            ->route('platform.trading.deal.edit', $period->id)
//                            ->icon('bs.pencil'),
                    ])),
        ];
    }
}
