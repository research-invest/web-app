<?php

namespace App\Orchid\Screens\Trading;

use App\Helpers\MathHelper;
use App\Models\Trade;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\PnlAnalyticsService;
use App\Services\TradingAnalyticsService;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;

class GlobalReportScreen extends Screen
{
    private $analyticsService;
    private $pnlAnalyticsService;
    private $analytics;

    public function __construct(
        TradingAnalyticsService $analyticsService,
        PnlAnalyticsService $pnlAnalyticsService
    ) {
        $this->analyticsService = $analyticsService;
        $this->pnlAnalyticsService = $pnlAnalyticsService;
    }

    public function query(): array
    {
        $this->analytics = $this->analyticsService->analyzeGlobal();

        return [
            'metrics' => [
                'netResult' => [
                    'value' => number_format($this->analytics['summary']['netResult'], 2) . '$',
                ],
                'tradesCount' => [
                    'value' => (string)$this->analytics['summary']['tradesCount'],
                ],
                'winRate' => [
                    'value' => number_format($this->analytics['summary']['winRate'], 2) . '%',
                ],
                'ratio' => [
                    'value' => (string)$this->analytics['positions']['ratio'],
                ],
            ],
            'topTrades' => $this->analytics['topTrades'],
            'lossTrades' => $this->analytics['lossTrades'],
            'chartData' => $this->pnlAnalyticsService->getPlanFactChartData(),
            'dealTypeChartData' => $this->pnlAnalyticsService->getDealTypeChartData(),
            'topProfitableTradesChart' => $this->pnlAnalyticsService->getTopProfitableTradesChart(),
            'currencyTypeChartData' => $this->pnlAnalyticsService->getCurrencyTypeChartData(),
            'tradesDurationChart' => $this->pnlAnalyticsService->getTradesDurationChart(),
        ];
    }

    public function name(): ?string
    {
        return 'Глобальный отчет';
    }

    public function description(): ?string
    {
        return 'Общая статистика по всем торговым сделкам';
    }

    public function layout(): array
    {
        return [
            Layout::metrics([
                'Общий результат' => 'metrics.netResult',
                'Всего сделок' => 'metrics.tradesCount',
                'Винрейт' => 'metrics.winRate',
                'Соотношение Short/Long' => 'metrics.ratio',
            ]),

//            new HighchartsChart(
//                $this->query()['chartData']['graph']
//            ),

            Layout::columns([
                new HighchartsChart(
                    $this->query()['dealTypeChartData']['graph']
                ),

                new HighchartsChart(
                    $this->query()['topProfitableTradesChart']['graph']
                ),
            ]),

            Layout::columns([
                new HighchartsChart(
                    $this->query()['currencyTypeChartData']['graph']
                ),

                new HighchartsChart(
                    $this->query()['tradesDurationChart']['graph']
                ),
            ]),

            Layout::table('topTrades', [
                TD::make('id', 'ID')
                    ->width(20)
                    ->render(fn(Trade $trade) => Link::make((string)$trade->id)
                        ->rawClick()
                        ->target('_blank')
                        ->route('platform.trading.deal.edit', ['trade' => $trade])
                    ),
                TD::make('created_at', 'Дата'),
                TD::make('position_type', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn(Trade $trade) => $trade->currency_name_format),
                TD::make('entry_price', 'Цена входа')
                    ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->entry_price)),
                TD::make('exit_price', 'Цена выхода')
                    ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->exit_price)),
                TD::make('realized_pnl', 'PNL')
                    ->render(fn(Trade $trade) => number_format($trade->realized_pnl, 2) . '$'),
                TD::make('notes', 'Комментарий'),
            ])->title('Топ 10 лучших сделок'),

            Layout::table('lossTrades', [
                TD::make('id', 'ID')
                    ->width(20)
                    ->render(fn(Trade $trade) => Link::make((string)$trade->id)
                        ->rawClick()
                        ->target('_blank')
                        ->route('platform.trading.deal.edit', ['trade' => $trade])
                    ),
                TD::make('created_at', 'Дата'),
                TD::make('position_type', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn(Trade $trade) => $trade->currency->code),
                TD::make('entry_price', 'Цена входа')
                    ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->entry_price)),
                TD::make('exit_price', 'Цена выхода')
                    ->render(fn(Trade $trade) => MathHelper::formatNumber($trade->exit_price)),
                TD::make('pnl', 'PNL')
                    ->render(fn(Trade $trade) => number_format($trade->realized_pnl, 2) . '$'),
                TD::make('notes', 'Комментарий'),
            ])->title('Убыточные сделки'),
        ];
    }
}
