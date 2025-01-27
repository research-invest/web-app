<?php

namespace App\Orchid\Filters\Statistics\Funding;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;

class FundingDiffFilter extends Filter
{
    public function name(): string
    {
        return 'Funding Diff >=';
    }

    public function parameters(): array
    {
        return ['diff_value', 'diff_period'];
    }

    public function run(Builder $builder): Builder
    {
        $period = $this->request->get('diff_period', 'diff_8h');
        return $builder->whereHas('latestFundingRate', function (Builder $query) use ($period) {
            $query->where($period, '>=', $this->request->get('diff_value'));
        });
    }

    public function display(): array
    {
        return [
            Select::make('diff_period')
                ->title('Period')
                ->options([
                    'diff_8h' => '8 hours',
                    'diff_24h' => '24 hours',
                    'diff_48h' => '48 hours',
                    'diff_7d' => '7 days',
                    'diff_30d' => '30 days',
                ])
                ->value('diff_8h'),

            Input::make('diff_value')
                ->type('number')
                ->step(0.000001)
                ->title('Diff Value >=')
                ->placeholder('Enter minimum diff value')
        ];
    }
}
