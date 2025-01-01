<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\TradePeriod;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\PnlAnalyticsService;
use Illuminate\Database\Eloquent\Collection;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PlatformScreen extends Screen
{

    /**
     * @var TradePeriod
     */
    public $period;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        $periodId = (int)request()->get('period_id');

        $this->period = TradePeriod::query()
            ->byCreator()
            ->when(
                $periodId,
                fn($query) => $query->where('id', $periodId),
                fn($query) => $query->isActive()->latest()
            )
            ->first();

        $analyticsService = new PnlAnalyticsService($this->period);
        $periods = TradePeriod::query()->byCreator()->oldest()->get();

        return [
            'chartData' => $analyticsService->getPlanFactChartData(),
            'dealTypeChartData' => $analyticsService->getDealTypeChartData(),
            'topProfitableTradesChart' => $analyticsService->getTopProfitableTradesChart(),
            'currencyTypeChartData' => $analyticsService->getCurrencyTypeChartData(),
            'tradesDurationChart' => $analyticsService->getTradesDurationChart(),
            'period' => $this->period,
            'periods' => $periods,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Торговый период: ' . ($this->period->name ?? 'Нет активного периода');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return '';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        $periodId = (int)request()->get('period_id');

        return [
            Layout::rows([
                Select::make('period_id')
                    ->set('id', 'select-periods')
                    ->title('Выберите период')
                    ->empty('Все периоды')
                    ->options($this->query()['periods']->sortByDesc('id')->pluck('name', 'id')->toArray())
                    ->value($periodId)
                    ->help('Выберите торговый период для отображения данных'),
            ])->title('Фильтр по периоду'),

            Layout::view('dashboard.pnl-chart'),

            new HighchartsChart(
                $this->query()['chartData']['graph']
            ),

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
        ];
    }
}
