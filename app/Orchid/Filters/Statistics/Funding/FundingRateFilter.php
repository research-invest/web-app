<?php

namespace App\Orchid\Filters\Statistics\Funding;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class FundingRateFilter extends Filter
{
    public function name(): string
    {
        return 'Funding Rate >=';
    }

    public function parameters(): array
    {
        return ['funding_rate'];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->whereHas('latestFundingRate', function (Builder $query) {
            $query->where('funding_rate', '>=', $this->request->get('funding_rate'));
        });
    }

    public function display(): array
    {
        return [
            Input::make('funding_rate')
                ->type('number')
                ->step(0.000001)
                ->title('Funding Rate >=')
                ->placeholder('Мин значение')
        ];
    }
}
