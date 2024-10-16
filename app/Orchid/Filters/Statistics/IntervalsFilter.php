<?php

namespace App\Orchid\Filters\Statistics;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class IntervalsFilter extends Filter
{
    public function name(): string
    {
        return 'Интервал';
    }

    public function parameters(): array
    {
        return ['interval'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder;
    }

    public function display(): array
    {
        return [
            Select::make('interval')
                ->options([
                    60 => '1 минута',
                    180 => '3 минуты',
                    300 => '5 минут',
                    600 => '10 минут',
                    1800 => '30 минут',
                    3600 => '1 час',
                    3600 * 2 => '2 часа',
                    3600 * 8 => '8 часов',
                ])
                ->title('Выберите интервал')
        ];
    }
}
