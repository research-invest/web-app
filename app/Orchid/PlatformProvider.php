<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;
use Orchid\Support\Color;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Dashboard $dashboard
     *
     * @return void
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * Register the application menu.
     *
     * @return Menu[]
     */
    public function menu(): array
    {
        return [
            Menu::make('Главная')
                ->icon('control-play')
                ->rawClick()
                ->route('platform.index'),

            Menu::make('Валюты')
                ->icon('dollar')
                ->rawClick()
                ->route('platform.currencies'),

            Menu::make('Статистика')
                ->icon('bs.book')
                ->title('Статистика')
                ->list([
                    Menu::make('Funding')
                        ->icon('bs.book')
                        ->route('platform.statistics.funding'),

                    Menu::make('Нормализация')
                        ->icon('bs.book')
                        ->rawClick()
                        ->route('platform.statistics.normalize'),

                    Menu::make('Хорошая динамика')
                        ->icon('bs.book')
                        ->rawClick()
                        ->route('platform.statistics.top-performing-coins'),

                    Menu::make('Хорошая динамика (таблица)')
                        ->icon('bs.book')
                        ->rawClick()
                        ->route('platform.statistics.top-performing-coins-table'),

                    Menu::make('Объем по диапазону')
                        ->icon('bs.book')
                        ->route('platform.statistics.volume-by-range'),

                    Menu::make('Композитный индекс')
                        ->icon('bs.book')
                        ->rawClick()
                        ->route('platform.composite-index')
                        ->divider(),

                    Menu::make('Doge/Tao/One/')
                        ->icon('bs.book')
                        ->target('_blank')
                        ->rawClick()
                        ->href('http://p635704.for-test-only.ru/'),

                    Menu::make('Neo/Link/')
                        ->icon('bs.book')
                        ->target('_blank')
                        ->rawClick()
                        ->href('http://a8kg.fvds.ru/'),
                ]),
            Menu::make('Торговля')
                ->icon('money')
                ->title('Торговля')
                ->list([
                    Menu::make('Сделки')
                        ->icon('briefcase')
                        ->rawClick()
                        ->route('platform.trading.deals'),

                    Menu::make('Сделки симуляция фандинга')
                        ->icon('briefcase')
                        ->rawClick()
                        ->route('platform.trading.funding_simulations'),

                    Menu::make('Калькулятор сделок')
                        ->icon('calculator')
                        ->rawClick()
                        ->route('platform.trading.futures-calculator'),

                    Menu::make('Торговые стратегии')
                        ->icon('dropbox')
                        ->rawClick()
                        ->route('platform.trading.strategies'),

                    Menu::make('Торговые периоды')
                        ->icon('book-open')
                        ->rawClick()
                        ->route('platform.trading.periods'),

                    Menu::make('Чек-листы')
                        ->icon('list')
                        ->route('platform.trading.check-list')
                        ->title('Управление чек-листами'),

                ])
                ->divider(),


            Menu::make('Настройки')
                ->icon('wrench')
                ->route('platform.settings.list'),

            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles')
                ->divider(),
        ];
    }

    /**
     * Register permissions for the application.
     *
     * @return ItemPermission[]
     */
    public function permissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
