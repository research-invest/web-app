<?php

namespace App\Orchid\Screens\Trading;

use App\Models\Trade;
use App\Models\TradePeriod;
use App\Services\TradingAnalyticsService;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\TD;

class TradingReportScreen extends Screen
{
    public $period;
    private $analyticsService;
    private $analytics;

    public function __construct(TradingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function query(TradePeriod $period): array
    {
        $this->period = $period;
        $this->analytics = $this->analyticsService->analyze($period);

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
        ];
    }

    public function name(): ?string
    {
        return "Отчет по периоду: {$this->period->name}";
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

            Layout::table('topTrades', [
                TD::make('created_at', 'Дата'),
                TD::make('position_type', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn (Trade $trade) => $trade->currency->code),
                TD::make('realized_pnl', 'PNL')
                    ->render(fn (Trade $trade) => number_format($trade->realized_pnl, 2) . '$'),
            ])->title('Топ 5 лучших сделок'),

            Layout::table('lossTrades', [
                TD::make('created_at', 'Дата'),
                TD::make('position_type', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn (Trade $trade) => $trade->currency->code),
                TD::make('pnl', 'PNL')
                    ->render(fn (Trade $trade) => number_format($trade->realized_pnl, 2) . '$'),
            ])->title('Убыточные сделки'),
        ];
    }
}
