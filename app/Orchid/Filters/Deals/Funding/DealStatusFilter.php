<?php

namespace App\Orchid\Filters\Deals\Funding;

use App\Models\Funding\FundingDeal;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class DealStatusFilter extends Filter
{
    public function name(): string
    {
        return 'Статус';
    }

    public function parameters(): array
    {
        return [];
    }

    public function run(Builder $builder): Builder
    {
        if ($status = $this->request->get('status')) {
            return $builder->where('status', $status);
        }

        return $builder;
    }

    public function display(): array
    {
        return [
            Select::make('status')
                ->options(FundingDeal::getStatuses())
                ->value($this->request->get('status'))
                ->title('Выберите статус сделки')
                ->empty('Все статусы')
        ];
    }
}
