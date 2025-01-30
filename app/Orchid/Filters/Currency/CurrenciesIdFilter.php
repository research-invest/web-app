<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Currency;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class CurrenciesIdFilter extends Filter
{

    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Валюта';
    }

    /**
     * The array of matched parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return ['currency_id'];
    }

    /**
     * Apply to a given Eloquent query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function run(Builder $builder): Builder
    {
        $currencyId = $this->request->get('currency_id');
        return $builder->where('currency_id', $currencyId);
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Select::make('currency_id')
                ->fromModel(Currency::class, 'name', 'id')
                ->applyScope('spot')
                ->displayAppend('namePrice')
                ->title('Выберите валюту')
                ->empty('Все валюты')
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return '';
    }
}
