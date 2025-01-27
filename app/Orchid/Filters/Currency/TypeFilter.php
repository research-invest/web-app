<?php

declare(strict_types=1);

namespace App\Orchid\Filters\Currency;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class TypeFilter extends Filter
{

    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Тип';
    }

    /**
     * The array of matched parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return ['type'];
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
        $type = trim($this->request->get('type'));
        return $builder->where('type', $type);
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Select::make('type')
                ->options(Currency::getTypes())
                ->title('Выберите тип')
                ->empty('Все типы')
        ];
    }

    /**
     * Value to be displayed
     */
    public function value(): string
    {
        return $this->name().': ' . $this->request->get('type');
    }
}
