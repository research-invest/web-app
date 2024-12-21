<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Currency;

use App\Helpers\MathHelper;
use App\Models\Currency;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CurrenciesListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'currencies';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (Currency $currency) =>  Link::make((string)$currency->id)
                    ->route('platform.currencies.edit', $currency->id)
                    ->rawClick()
                    ->icon('bs.pencil')),

            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (Currency $currency) => Link::make($currency->name)
                    ->route('platform.currencies.edit', $currency->id)
                    ->rawClick()),

            TD::make('code', 'Код'),

            TD::make('last_price', 'Цена')
                ->sort()
                ->render(fn(Currency $currency) => MathHelper::formatNumber($currency->last_price)),

            TD::make('volume', 'Объем')
                ->sort()
                ->render(fn(Currency $currency) =>
                    sprintf('%s (%s)', MathHelper::humanNumber($currency->volume),
                        MathHelper::formatNumber($currency->volume))
                ),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', __('Last edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('exchange', 'Источник')
                ->defaultHidden(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (Currency $currency) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.currencies.edit', $currency->id)
                            ->rawClick()
                            ->icon('bs.pencil'),

                    ])),
        ];
    }
}
