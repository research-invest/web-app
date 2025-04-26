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
        $totalProfit = $deals->sum('profit');
        $averageProfit = $totalDeals > 0 ? $totalProfit / $totalDeals : 0;

        $topDeals = $deals->sortByDesc('profit')->take(5);
        $worstDeals = $deals->sortBy('profit')->take(5);

        $byCurrency = $deals->groupBy('currency_id');
        $coinStats = $byCurrency->map(function ($deals, $currencyId) {
            $currency = $deals->first()->currency;
            return [
                'currency_id' => $currencyId,
                'coin' => $currency ? $currency->symbol : 'N/A',
                'total_profit' => $deals->sum('profit'),
                'count' => $deals->count(),
            ];
        });

        $topCoins = $coinStats->sortByDesc('total_profit')->take(3);
        $worstCoins = $coinStats->sortBy('total_profit')->take(3);


        return [
            'config' => $config,
            'totalDeals' => $totalDeals,
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
