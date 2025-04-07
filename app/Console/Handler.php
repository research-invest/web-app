<?php

namespace App\Console;

use App\Console\Commands\Alerts\CheckFavoritePairs;
use App\Console\Commands\Alerts\CheckLiquidationWarnings;
use App\Console\Commands\Alerts\CheckTradeLevels;
use App\Console\Commands\Alerts\FreeSpaceAlert;
use App\Console\Commands\Alerts\HunterFunding;
use App\Console\Commands\Alerts\SendSmartMoneyAlert;
use App\Console\Commands\Alerts\SendTradePnLNotification;
use App\Console\Commands\AnalyzeTopPerformingCoinSnapshots;
use App\Console\Commands\CollectTopPerformingCoinSnapshots;
use App\Console\Commands\Features\CollectFundingRates;
use App\Console\Commands\Features\FundingDealsConfig;
use App\Console\Commands\Features\FundingDeals;
use App\Console\Commands\SendIndexChart;
use App\Console\Commands\UpdateCurrencies;
use App\Console\Commands\UpdateTradesPnL;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {

        $schedule->command(FundingDealsConfig::class)
            ->runInBackground()
            ->hourly();

        $schedule->command(FundingDeals::class)
            ->runInBackground()
            ->everyThirtySeconds();


        $schedule->command(UpdateCurrencies::class)
            ->runInBackground()
            ->withoutOverlapping()
            ->everyMinute();

        $schedule->command(UpdateTradesPnL::class)
            ->withoutOverlapping()
            ->everyThreeMinutes();

//        $schedule->command(CheckTradeLevels::class)->everyTwoMinutes();
        $schedule->command(SendTradePnLNotification::class)->everyTenMinutes();
        $schedule->command(CheckLiquidationWarnings::class)->everyFiveMinutes();
        $schedule->command(FreeSpaceAlert::class)->hourly();
        $schedule->command(SendSmartMoneyAlert::class)->everyThirtyMinutes();
        $schedule->command(SendIndexChart::class)->everyThirtyMinutes();
//        $schedule->command(CheckFavoritePairs::class)->everyThirtyMinutes();

        $schedule->command(CollectTopPerformingCoinSnapshots::class)
            ->everyTenMinutes()
            ->withoutOverlapping();

        $schedule->command(CollectFundingRates::class)->hourly();
        $schedule->command(HunterFunding::class)->hourly();

//        $schedule->command(AnalyzeTopPerformingCoinSnapshots::class)
//            ->everyThirtyMinutes()
//            ->withoutOverlapping();

        /**
         * 1) ОТ ДО
         * 2) пофиксить запуск за минуту до
         * 3) добавить поля ошибка, данные открытия сделки/закрытия, количества сделок, лимит на количество
         * 4) добавить таблицу таймингов
         * 5) сделать открыие/закрытие сделки
         * 6) уведомление в телега бота
         * 7)
         */
    }
}
