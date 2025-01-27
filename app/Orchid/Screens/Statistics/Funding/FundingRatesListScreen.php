<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Statistics\Funding;

use App\Models\Currency;
use App\Models\User;
use App\Orchid\Layouts\Currency\Funding\CurrenciesFundingListLayout;
use Orchid\Screen\Screen;

class FundingRatesListScreen extends Screen
{

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'currencies' => Currency::filters(\App\Orchid\Filters\Currency\FiltersLayout::class)
                ->isActive()
                ->features()
                ->filters()
                ->select('currencies.*')
                ->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Валюты';
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
//            UserFiltersLayout::class,
            CurrenciesFundingListLayout::class,

//            Layout::modal('editUserModal', UserEditLayout::class)
//                ->deferred('loadUserOnOpenModal'),
        ];
    }

    /**
     * Loads user data when opening the modal window.
     *
     * @return array
     */
    public function loadUserOnOpenModal(User $user): iterable
    {
        return [
            'user' => $user,
        ];
    }
}
