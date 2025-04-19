<?php

namespace App\Orchid\Filters\Statistics\BtcWallets;

use Illuminate\Database\Eloquent\Builder;
use Orchid\Filters\Filter;
use Orchid\Screen\Fields\Input;

class AddressFilter extends Filter
{
    public function name(): string
    {
        return 'Адрес';
    }

    public function parameters(): array
    {
        return [
            'address',
        ];
    }

    public function run(Builder $builder): Builder
    {
        return $builder->where('address', '=', $this->request->get('address'));
    }

    public function display(): array
    {
        return [
            Input::make('address')
                ->title('Address'),
        ];
    }
}
