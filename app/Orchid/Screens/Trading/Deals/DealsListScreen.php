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
        return [
            'trades' => Trade::filters(FiltersLayout::class)
                ->byCreator()
                ->latest()
                ->paginate(25),
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
            FiltersLayout::class,
            ListLayout::class,
            Layout::view('trading.sessions-info', [
                'sessions' => $this->tradingSessionsService->getSessionsInfo()
            ]),
            new HighchartsChart(
                $this->tradingSessionsService->getChartConfig()
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
