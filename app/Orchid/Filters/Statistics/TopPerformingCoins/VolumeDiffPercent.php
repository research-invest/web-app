<?php

namespace App\Orchid\Filters\Statistics\TopPerformingCoins;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class VolumeDiffPercent extends Filter
{
    public function name(): string
    {
        return 'Процентное изменения объема';
    }

    public function parameters(): array
    {
        return ['volume_diff_percent'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder;
    }

    public function display(): array
    {
        return [
            Input::make('volume_diff_percent')
                ->title('Процент изменения цены')
        ];
    }
}
