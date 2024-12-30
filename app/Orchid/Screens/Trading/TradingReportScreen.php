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

    public function __construct(TradingAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function query(TradePeriod $period): array
    {
        $this->period = $period;
        return $this->analyticsService->analyze($period);
    }

    public function name(): ?string
    {
        return "Отчет по периоду: {$this->period->name}";
    }

    public function layout(): array
    {
        return [
            Layout::metrics([
                'Общий результат' => number_format($this->query($this->period)['summary']['netResult'], 2) . '$',
                'Всего сделок' => $this->query($this->period)['summary']['tradesCount'],
                'Винрейт' => number_format($this->query($this->period)['summary']['winRate'], 2) . '%',
                'Соотношение Short/Long' => $this->query($this->period)['positions']['ratio'],
            ]),

            Layout::table('topTrades', [
                TD::make('created_at', 'Дата'),
                TD::make('position_type', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn (Trade $trade) => $trade->currency->code),
                TD::make('realized_pnl', 'PNL')
                    ->render(fn ($trade) => number_format($trade->pnl, 2) . '$'),
            ])->title('Топ 5 лучших сделок'),

            Layout::table('lossTrades', [
                TD::make('created_at', 'Дата'),
                TD::make('position', 'Позиция'),
                TD::make('symbol', 'Валюта')
                    ->render(fn (Trade $trade) => $trade->currency->code),
                TD::make('pnl', 'PNL')
                    ->render(fn ($trade) => number_format($trade->pnl, 2) . '$'),
            ])->title('Убыточные сделки'),
        ];
    }
}
