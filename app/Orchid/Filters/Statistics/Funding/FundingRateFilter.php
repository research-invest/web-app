<?php

namespace App\Orchid\Filters\Statistics\Funding;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class FundingRateFilter extends Filter
{
    public function name(): string
    {
        return 'Funding Rate >= <=';
    }

    public function parameters(): array
    {
        return [
            'fr_less', //<=
            'fr_more', //>=
        ];
    }

    public function run(Builder $builder): Builder
    {
        if ($less = $this->request->get('fr_less')) {
            $builder->where('funding_rate', '<=', $less);
        }
        if ($more = $this->request->get('fr_more')) {
            $builder->where('funding_rate', '>=', $more);
        }

        return $builder;
//        return $builder->whereHas('latestFundingRate', function (Builder $query) {
//        });
    }

    public function display(): array
    {
        return [
            Input::make('fr_less')
                ->type('number')
                ->step(0.000001)
                ->title('Funding Rate <='),

            Input::make('fr_more')
                ->type('number')
                ->step(0.000001)
                ->title('Funding Rate >='),
        ];
    }
}
