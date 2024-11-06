<?php

namespace App\Orchid\Filters\Statistics\Normalize;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class CurrencyFilter extends Filter
{
    public function name(): string
    {
        return 'Валюты';
    }

    public function parameters(): array
    {
        return ['currencies'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->whereIn('currency', $this->request->get('currencies'));
    }

    public function display(): array
    {
        return [
            Select::make('currencies')
                ->fromModel(Currency::class, 'code', 'name')
                ->multiple()
                ->title('Выберите валюты')
                ->empty('Все валюты')
        ];
    }
}
