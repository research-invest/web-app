<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Trading\Deals\Funding;

use App\Models\Currency;
use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class FormConfigLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('config.name')
                ->title('Название'),

            Select::make('config.exchange')
                ->options(Currency::getExchanges())
                ->title('Выберите биржу'),

            Input::make('config.min_funding_rate')
                ->title('min_funding_rate')
                ->type('number')
                ->step('0.00000001')
                ->required(),

            Input::make('config.position_size')
                ->title('position_size')
                ->value(200)
                ->type('number')
                ->required(),

            Input::make('config.leverage')
                ->title('leverage')
                ->value(10)
                ->type('number')
                ->required(),

            CheckBox::make('config.is_testnet')
                ->sendTrueOrFalse()
                ->value(0)
                ->placeholder('is testnet'),

            TextArea::make('config.notes')
                ->title('Комментарий')
                ->rows(3),
        ];
    }
}
