<?php

namespace App\Orchid\Filters\Deals;

use App\Models\TradePeriod;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class PeriodFilter extends Filter
{
    public function name(): string
    {
        return 'Период';
    }

    public function parameters(): array
    {
        return ['period_id'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->where('trade_period_id', $this->request->get('period_id'));
    }

    public function display(): array
    {
        return [
            Select::make('period_id')
                ->set('id', 'select-periods')
                ->fromModel(TradePeriod::class, 'name', 'id')
                ->title('Выберите период')
                ->empty('Все периоды')
        ];
    }
}
