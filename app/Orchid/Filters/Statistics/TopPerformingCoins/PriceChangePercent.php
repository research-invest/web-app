<?php

namespace App\Orchid\Filters\Statistics\TopPerformingCoins;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;


class PriceChangePercent extends Filter
{
    public function name(): string
    {
        return 'Процент изменения цены';
    }

    public function parameters(): array
    {
        return ['price_change_percent'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder;
    }

    public function display(): array
    {
        return [
            Input::make('price_change_percent')
                ->title('Процент изменения цены')
        ];
    }
}
