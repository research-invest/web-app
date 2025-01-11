<?php

declare(strict_types=1);

use App\Orchid\Screens\Currency\CurrencyEditScreen;
use App\Orchid\Screens\Currency\CurrencyListScreen;
use App\Orchid\Screens\Examples\ExampleActionsScreen;
use App\Orchid\Screens\Examples\ExampleCardsScreen;
use App\Orchid\Screens\Examples\ExampleChartsScreen;
use App\Orchid\Screens\Examples\ExampleFieldsAdvancedScreen;
use App\Orchid\Screens\Examples\ExampleFieldsScreen;
use App\Orchid\Screens\Examples\ExampleGridScreen;
use App\Orchid\Screens\Examples\ExampleLayoutsScreen;
use App\Orchid\Screens\Examples\ExampleScreen;
use App\Orchid\Screens\Examples\ExampleTextEditorsScreen;
use App\Orchid\Screens\PlatformScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\Statistics\CompositeIndex;
use App\Orchid\Screens\Trading\CheckListItem\CheckListItemEditScreen;
use App\Orchid\Screens\Trading\CheckListItem\CheckItemListScreen;
use App\Orchid\Screens\Trading\Deals\DealCloseScreen;
use App\Orchid\Screens\Trading\Deals\DealEditScreen;
use App\Orchid\Screens\Trading\Deals\DealsListScreen;
use App\Orchid\Screens\Trading\StrategiesScreen;
use App\Orchid\Screens\Trading\TradingPeriodScreen;
use App\Orchid\Screens\Trading\TradingReportScreen;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the need "dashboard" middleware group. Now create something great!
|
*/

// Main
Route::screen('/main', PlatformScreen::class)
    ->name('platform.main');

Route::screen('/statistics/normalize', \App\Orchid\Screens\Statistics\Normalize::class)
    ->name('platform.statistics.normalize');

Route::screen('/statistics/top-performing-coins', \App\Orchid\Screens\Statistics\TopPerformingCoins\TopPerformingCoins::class)
    ->name('platform.statistics.top-performing-coins');

Route::screen('/statistics/top-performing-coins-table', \App\Orchid\Screens\Statistics\TopPerformingCoins\TopPerformingCoinsTable::class)
    ->name('platform.statistics.top-performing-coins-table');

Route::screen('/statistics/volume-by-range', \App\Orchid\Screens\Statistics\VolumeByRange::class)
    ->name('platform.statistics.volume-by-range');


Route::screen('/statistics/composite-index', CompositeIndex::class)
    ->name('platform.composite-index');

Route::screen('/trading/futures-calculator', \App\Orchid\Screens\Trading\FuturesCalculator::class)
    ->name('platform.trading.futures-calculator');

Route::screen('/trading/deals/periods', TradingPeriodScreen::class)
->name('platform.trading.periods');

Route::screen('/trading/deals/strategies', StrategiesScreen::class)
->name('platform.trading.strategies');

Route::screen('/trading/deals', DealsListScreen::class)
->name('platform.trading.deals')
->breadcrumbs(fn (Trail $trail) =>
    $trail->parent('platform.index')
        ->push('Журнал сделок', route('platform.trading.deals'))
);

Route::screen('/trading/deals/create', DealEditScreen::class)
->name('platform.trading.deal.create')
->breadcrumbs(fn (Trail $trail) =>
    $trail
        ->parent('platform.trading.deals')
        ->push('Новая сделка')
);

Route::screen('/trading/deals/{trade}/edit', DealEditScreen::class)
->name('platform.trading.deal.edit')
->breadcrumbs(fn (Trail $trail, $trade) =>
    $trail
        ->parent('platform.trading.deals')
        ->push('Редактирование сделки #' . $trade->id)
);

Route::screen('trading/deals/{trade}/close', DealCloseScreen::class)
    ->name('platform.trading.deal.close')
    ->breadcrumbs(fn (Trail $trail, $trade) =>
        $trail
            ->parent('platform.trading.deal.edit', $trade)
            ->push('Закрытие сделки')
    );

Route::screen('trading/report/{period}', TradingReportScreen::class)
    ->name('platform.trading.report');

Route::screen('trading/check-list', CheckItemListScreen::class)
    ->name('platform.trading.check-list');

Route::screen('trading/check-list/{checkListItem?}/edit', CheckListItemEditScreen::class)
    ->name('platform.trading.check-item.edit');

Route::screen('trading/check-list/create', CheckListItemEditScreen::class)
    ->name('platform.trading.check-item.create');

Route::screen('currencies/{currency}/edit', CurrencyEditScreen::class)
    ->name('platform.currencies.edit')
    ->breadcrumbs(fn (Trail $trail, $currency) => $trail
        ->parent('platform.currencies')
        ->push($currency->name, route('platform.currencies.edit', $currency)));

Route::screen('currencies', CurrencyListScreen::class)
    ->name('platform.currencies')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Валюты', route('platform.currencies')));

// Platform > Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// Platform > System > Users > User
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

// Platform > System > Users > Create
Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

// Platform > System > Users
Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

// Platform > System > Roles > Role
Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

// Platform > System > Roles > Create
Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

// Platform > System > Roles
Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));

// Example...
Route::screen('example', ExampleScreen::class)
    ->name('platform.example')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Example Screen'));

Route::screen('/examples/form/fields', ExampleFieldsScreen::class)->name('platform.example.fields');
Route::screen('/examples/form/advanced', ExampleFieldsAdvancedScreen::class)->name('platform.example.advanced');
Route::screen('/examples/form/editors', ExampleTextEditorsScreen::class)->name('platform.example.editors');
Route::screen('/examples/form/actions', ExampleActionsScreen::class)->name('platform.example.actions');

Route::screen('/examples/layouts', ExampleLayoutsScreen::class)->name('platform.example.layouts');
Route::screen('/examples/grid', ExampleGridScreen::class)->name('platform.example.grid');
Route::screen('/examples/charts', ExampleChartsScreen::class)->name('platform.example.charts');
Route::screen('/examples/cards', ExampleCardsScreen::class)->name('platform.example.cards');

//Route::screen('idea', Idea::class, 'platform.screens.idea');
