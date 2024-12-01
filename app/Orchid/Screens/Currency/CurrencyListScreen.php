<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Currency;

use App\Models\Currency;
use App\Orchid\Filters\Statistics\SingleCurrencyFilter;
use App\Orchid\Layouts\Currency\CurrenciesListLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class CurrencyListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'currencies' => Currency::query()
                ->leftJoin('currencies_favorites', function($join) {
                    $join->on('currencies.id', '=', 'currencies_favorites.currency_id')
                         ->where('currencies_favorites.user_id', '=', auth()->id());
                })
                ->filters()
                ->orderByRaw('CASE WHEN currencies_favorites.currency_id IS NOT NULL THEN 0 ELSE 1 END')
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
            CurrenciesListLayout::class,

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
