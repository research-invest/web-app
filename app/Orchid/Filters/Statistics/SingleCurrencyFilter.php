<?php

namespace App\Orchid\Filters\Statistics;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class SingleCurrencyFilter extends Filter
{
    public function name(): string
    {
        return 'Валюты';
    }

    public function parameters(): array
    {
        return ['currency'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->whereIn('currency', $this->request->get('currencies'));
    }

    public function display(): array
    {
        return [
            Select::make('currency')
                ->fromModel(Currency::class, 'code', 'name')
                ->displayAppend('namePrice')
                ->title('Выберите валюты')
                ->empty('Все валюты')
        ];
    }
}
