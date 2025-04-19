<?php

namespace App\Orchid\Filters\Statistics\BtcWallets;

use App\Models\BtcWallets\Wallet;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class TypesFilter extends Filter
{

    /**
     * The displayable name of the filter.
     *
     * @return string
     */
    public function name(): string
    {
        return '';
    }

    /**
     * The array of matched parameters.
     *
     * @return array
     */
    public function parameters(): array
    {
        return ['visible_type', 'label_type'];
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
        if ($vt = $this->request->get('visible_type')) {
            $builder->where('visible_type', $vt);
        }

        if ($lt = $this->request->get('label_type')) {
            $builder->where('label_type', $lt);
        }

        return $builder;
    }

    /**
     * Get the display fields.
     */
    public function display(): array
    {
        return [
            Select::make('visible_type')
                ->options(Wallet::getVisibleTypes())
                ->title('Тип наблюдения')
                ->empty('Все типы'),

            Select::make('label_type')
                ->options(Wallet::getLabelTypes())
                ->title('Тип метки')
                ->empty('Все типы'),
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
