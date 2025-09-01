<?php

namespace App\Console;

use App\Console\Commands\Alerts\CheckLiquidationWarnings;
use App\Console\Commands\Alerts\FreeSpaceAlert;
use App\Console\Commands\Alerts\GenerateMarketOscillator;
use App\Console\Commands\Alerts\SendSmartMoneyAlert;
use App\Console\Commands\Alerts\SendTradePnLNotification;
use App\Console\Commands\Alerts\UsdtEthPrinterNotify;
use App\Console\Commands\BtcWallets\UpdateWalletBalances;
use App\Console\Commands\ClearOldPnlHistory;
use App\Console\Commands\CollectTopPerformingCoinSnapshots;
use App\Console\Commands\Features\CollectFundingRates;
use App\Console\Commands\Features\DeleteOldFundingRates;
use App\Console\Commands\Features\FundingDealsConfig;
use App\Console\Commands\Features\FundingDealsDelete;
use App\Console\Commands\UpdateCurrencies;
use App\Console\Commands\UpdateCurrencyPrices;
use App\Console\Commands\UpdateTradesPnL;
use Illuminate\Console\Scheduling\Schedule;

class Handler
{
    public function __invoke(Schedule $schedule): void
    {
//        $schedule->command(FundingDealsConfig::class)
////            ->withoutOverlapping()
//            ->runInBackground()
//            ->hourly();

//        $schedule->command(FundingDealsDelete::class)
////            ->withoutOverlapping()
//            ->runInBackground()
//            ->everyTwoMinutes();

//        $schedule->command(FundingDeals::class)
//            ->runInBackground()
//            ->everyThirtySeconds();

        $schedule->command(UpdateCurrencies::class)
            ->runInBackground()
//            ->withoutOverlapping()
            ->everyFiveMinutes();

        $schedule->command(UpdateCurrencyPrices::class)
            ->runInBackground()
//            ->withoutOverlapping()
            ->hourly();

        $schedule->command(UpdateTradesPnL::class)
            ->runInBackground()
//            ->withoutOverlapping()
            ->everyFiveMinutes();

//        $schedule->command(ClearOldPnlHistory::class)
//            ->runInBackground()
//            ->withoutOverlapping()
//            ->daily();

//        $schedule->command(CheckTradeLevels::class)->everyTwoMinutes();
        $schedule->command(SendTradePnLNotification::class)
//            ->withoutOverlapping()
            ->runInBackground()
            ->everyTenMinutes();

//        $schedule->command(GenerateMarketOscillator::class)
//            ->withoutOverlapping()
//            ->runInBackground()
//            ->everyTenMinutes();

        $schedule->command(CheckLiquidationWarnings::class)
//            ->withoutOverlapping()
            ->runInBackground()
            ->everyFiveMinutes();

        $schedule->command(FreeSpaceAlert::class)
//            ->withoutOverlapping()
            ->runInBackground()
            ->hourly();

        $schedule->command(SendSmartMoneyAlert::class)
//            ->withoutOverlapping()
            ->runInBackground()
            ->everyThirtyMinutes();
//        $schedule->command(SendIndexChart::class)->everyThirtyMinutes();
//        $schedule->command(CheckFavoritePairs::class)->everyThirtyMinutes();

        $schedule->command(CollectTopPerformingCoinSnapshots::class)
            ->everyTenMinutes()
            ->withoutOverlapping();

//        $schedule->command(CollectFundingRates::class)
//            ->runInBackground()
////            ->withoutOverlapping()
//            ->hourlyAt(50);

        $schedule->command(UpdateWalletBalances::class)
            ->runInBackground()
//            ->withoutOverlapping()
//            ->hourly()
            ->everyFourHours()
        ;

        $schedule->command(DeleteOldFundingRates::class)
            ->runInBackground()
//            ->withoutOverlapping()
            ->daily();

        $schedule->command(UsdtEthPrinterNotify::class)
            ->runInBackground()
//            ->withoutOverlapping()
            ->hourly();


//        $schedule->command(AnalyzeTopPerformingCoinSnapshots::class)
//            ->everyThirtyMinutes()
//            ->withoutOverlapping();

        /**
         * 1) ОТ ДО
         * 2) пофиксить запуск за минуту до
         * 3) добавить поля ошибка, данные открытия сделки/закрытия, количества сделок, лимит на количество
         * 5) сделать открыие/закрытие сделки
         * 6) уведомление в телега бота
         * 7)
         */
    }
}
