<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Funding;

use App\Models\Funding\FundingDealConfig;
use App\Models\Trade;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class FundingDealConfigStatsScreen extends Screen
{
    public $config;

    public function query(FundingDealConfig $config): iterable
    {

        $deals = $config->deals()->get();

        $totalDeals = $deals->count();
        $sumProfit = $deals->where('total_pnl', '>', 0)->sum('total_pnl');
        $sumLoss = $deals->where('total_pnl', '<', 0)->sum('total_pnl'); // будет отрицательное число
        $totalProfit = $deals->sum('total_pnl');

        $averageProfit = $totalDeals > 0 ? $totalProfit / $totalDeals : 0;

        $topDeals = $deals->sortByDesc('total_pnl')->take(5);
        $worstDeals = $deals->sortBy('total_pnl')->take(5);

        $byCurrency = $deals->groupBy('currency_id');
        $coinStats = $byCurrency->map(function ($deals, $currencyId) {
            $currency = $deals->first()->currency;
            return [
                'currency_id' => $currencyId,
                'coin' => $currency ? $currency->name : 'N/A',
                'total_profit' => $deals->sum('total_pnl'),
                'count' => $deals->count(),
            ];
        });

        $topCoins = $coinStats->sortByDesc('total_profit')->take(3);
        $worstCoins = $coinStats->sortBy('total_profit')->take(3);


        return [
            'config' => $config,
            'totalDeals' => $totalDeals,
            'sumProfit' => $sumProfit,
            'sumLoss' => $sumLoss,
            'totalProfit' => $totalProfit,
            'averageProfit' => $averageProfit,
            'topDeals' => $topDeals,
            'worstDeals' => $worstDeals,
            'topCoins' => $topCoins,
            'worstCoins' => $worstCoins,
        ];
    }

    public function name(): ?string
    {
        return 'Статистика по конфигу сделки';
    }

    public function layout(): iterable
    {
        return [
            Layout::view('trading.funding.stats'),
        ];
    }
}
