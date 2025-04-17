<?php

namespace App\Orchid\Layouts\BtcWallets;

use App\Models\BtcWallets\Wallet;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Components\Cells\DateTimeSplit;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class WalletsListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'wallets';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn(Wallet $wallet) => Link::make((string)$wallet->id)
                    ->route('platform.statistics.btc-wallets.show', $wallet->id)
                    ->rawClick()
                    ->icon('bs.pencil')),

            TD::make('address', 'Адрес')
                ->cantHide()
                ->filter(Input::make())
                ->render(fn(Wallet $wallet) => Link::make($wallet->address)
                    ->route('platform.statistics.btc-wallets.show', $wallet->id)
                    ->rawClick()),

            TD::make('balance', 'Баланс')
                ->sort(),

            TD::make('diff_percent', 'Diff percent')
                ->sort()
                ->render(function(Wallet $wallet) {
                    $color = $wallet->diff_percent > 0 ? 'green' : ($wallet->diff_percent < 0 ? 'red' : 'inherit');
                    return sprintf(
                        ' <small style="color: %s">%+.1f%%</small>',
                        $color,
                        $wallet->diff_percent,
                    );
                }),

            TD::make('created_at', __('Created'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make('updated_at', __('Last edit'))
                ->usingComponent(DateTimeSplit::class)
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort(),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn(Wallet $wallet) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([
                        Link::make(__('Edit'))
                            ->route('platform.statistics.btc-wallets.show', $wallet->id)
                            ->rawClick()
                            ->icon('bs.pencil'),

                    ])),
        ];
    }

    protected function striped(): bool
    {
        return true;
    }
}
