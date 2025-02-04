<?php


namespace App\Orchid\Layouts\Trading\Deals\FundingSimulations;

use App\Models\FundingSimulation;
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
                ->render(fn(FundingSimulation $funding) => Link::make((string)$funding->id)
                    ->rawClick()
                    ->route('platform.trading.funding_simulation.edit', $funding)
                ),

            TD::make('currency', 'Пара')
                ->render(function(FundingSimulation $funding) {
                    return Link::make($funding->currency->name)
                        ->rawClick()
                        ->icon('share-alt')
                        ->route('platform.trading.funding_simulation.edit', $funding);
                }),


            TD::make('funding_time', 'Время')
                ->render(function(FundingSimulation $funding) {
                    return $funding->funding_time->toDateTimeString();
                }),

            TD::make('funding_rate', 'rate'),
            TD::make('entry_price', 'Цена входа'),
            TD::make('exit_price', 'Цена выхода'),
            TD::make('profit_loss', 'Профит'),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(FundingSimulation $funding) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make('Перейти в валюту')
                            ->route('platform.currencies.edit', $funding->currency_id)
                            ->target('_blank')
                            ->icon('dollar'),

                        Link::make('Открыть TradingView')
                            ->icon('grid')
                            ->target('_blank')
                            ->rawClick()
                            ->href($funding->currency->getTVLink()),

                        Link::make(__('Изменить'))
                            ->rawClick()
                            ->route('platform.trading.funding_simulation.edit', $funding->id)
                            ->icon('bs.pencil'),

//                        Button::make(__('Delete'))
//                            ->icon('bs.trash3')
//                            ->confirm('Вы уверены?')
//                            ->method('remove', [
//                                'id' => $funding->id,
//                            ]),
                    ])),
        ];
    }
}
