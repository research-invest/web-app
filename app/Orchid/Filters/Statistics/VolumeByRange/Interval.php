<?php

namespace App\Orchid\Filters\Statistics\VolumeByRange;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class Interval extends Filter
{
    public function name(): string
    {
        return 'Диапазон цены';
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
            Input::make('interval')
                ->title('Диапазон цены')
        ];
    }
}
