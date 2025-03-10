<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Rows;

class UserApiKeysLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Group::make([
                Input::make('user.mexc_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('mexc api key'),

                Input::make('user.mexc_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('mexc secret key'),
            ]),

            Group::make([
                Input::make('user.bybit_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('bybit api key'),

                Input::make('user.bybit_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('bybit secret key'),
            ]),

            Group::make([
                Input::make('user.binance_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('binance api key'),

                Input::make('user.binance_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('binance secret key'),
            ]),

            Group::make([
                Input::make('user.bigx_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('bigx api key'),

                Input::make('user.bigx_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('bigx secret key'),
            ]),

            Group::make([
                Input::make('user.gate_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('gate api key'),

                Input::make('user.gate_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('gate secret key'),
            ]),

            Group::make([
                Input::make('user.kucoin_api_key')
                    ->type('text')
                    ->max(255)
                    ->title('kucoin api key'),

                Input::make('user.kucoin_secret_key')
                    ->type('text')
                    ->max(255)
                    ->title('kucoin secret key'),

                Input::make('user.kucoin_api_passphrase')
                    ->type('text')
                    ->max(255)
                    ->title('kucoin passphrase'),
            ]),


        ];
    }
}
