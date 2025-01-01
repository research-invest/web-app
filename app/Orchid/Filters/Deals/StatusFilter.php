<?php

namespace App\Orchid\Filters\Deals;

use App\Models\Trade;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Select;

class StatusFilter extends Filter
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
        $status = $this->request->get('status');
        if (!$status) {
            return $builder->where('status', Trade::STATUS_OPEN);
        }

        return $builder->where('status', $status);
    }

    public function display(): array
    {
        return [
            Select::make('status')
                ->options(Trade::getStatuses())
                ->value($this->request->get('status'))
                ->title('Выберите статус')
                ->empty('Все статусы')
        ];
    }
}
