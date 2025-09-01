<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Deals;

use App\Models\Trade;
use App\Orchid\Filters\Deals\FiltersLayout;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Orchid\Layouts\Trading\Deals\ListLayout;
use App\Services\Trading\TradingSessionsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;
use Orchid\Support\Facades\Layout;

class DealsListScreen extends Screen
{
    private TradingSessionsService $tradingSessionsService;
    public array $todayMetrics = [];

    public function __construct(TradingSessionsService $tradingSessionsService)
    {
        $this->tradingSessionsService = $tradingSessionsService;
    }

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $this->todayMetrics = $this->tradingSessionsService->getTodayMetrics();

        return [
            'trades' => Trade::filters(FiltersLayout::class)
                ->byCreator()
                ->latest()
                ->paginate(25),
            'today_metrics' => $this->todayMetrics,
            'metrics' => [
                'today_pnl' => [
                    'value' => number_format($this->todayMetrics['today_pnl'], 2) . '$',
                ],
                'max_today_pnl' => [
                    'value' => ($this->todayMetrics['max_today_pnl']) . '$',
                ],
                'trades_count' => [
                    'value' => (string)$this->todayMetrics['trades_count'],
                ],
                'best_roi' => [
                    'value' => $this->todayMetrics['best_trade'] ? number_format($this->todayMetrics['best_trade']['roi'], 2) . '%' : 'N/A',
                ],
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Сделки';
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
            Link::make('Add')
                ->icon('plus')
                ->rawClick()
                ->route('platform.trading.deal.create')
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
            Layout::metrics([
                'PnL за сегодня' => 'metrics.today_pnl',
                'Макс. PnL за день' => 'metrics.max_today_pnl',
                'Сделок сегодня' => 'metrics.trades_count',
                'Лучшая сделка (ROI)' => 'metrics.best_roi',
            ]),
//
//            Layout::block([
//                Layout::view('trading.today-metrics', [
//                    'todayMetrics' => $this->todayMetrics
//                ])
//            ])->title('Детализация по сделкам за сегодня'),

            FiltersLayout::class,
            ListLayout::class,
            Layout::view('trading.sessions-info', [
                'sessions' => $this->tradingSessionsService->getSessionsInfo()
            ]),
            new HighchartsChart(
                $this->tradingSessionsService->getChartConfig()
            ),
            new HighchartsChart(
                $this->tradingSessionsService->getKillZonesChartConfig()
            ),
        ];
    }

    public function remove(Request $request)
    {
        Trade::findOrFail($request->get('id'))->delete();

        Toast::success('Сделка удалена');

        return redirect()->route('platform.trading.deals');
    }
}
