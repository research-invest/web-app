<?php

namespace App\Orchid\Screens\Statistics\BtcWallets;

use App\Models\BtcWallets\Wallet;
use App\Orchid\Filters\Currency\FiltersLayout;
use App\Orchid\Layouts\BtcWallets\WalletsListLayout;
use App\Models\User;
use Orchid\Screen\Screen;

class BtcWalletsListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'wallets' => Wallet::filters(FiltersLayout::class)
                ->filters()
                ->paginate(50),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Китовые кошельки';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return '';
    }

    public function permission(): ?iterable
    {
        return [
        ];
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [
        ];
    }

    /**
     * The screen's layout elements.
     *
     * @return string[]|\Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
//            FiltersLayout::class,
            WalletsListLayout::class,
        ];
    }
}
