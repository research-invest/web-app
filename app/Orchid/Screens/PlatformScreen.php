<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Models\TradePeriod;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\PnlAnalyticsService;
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
        $analyticsService = new PnlAnalyticsService();

        $periods = TradePeriod::query()
            ->oldest()->get();

        $this->period = TradePeriod::query()
            ->when(
                (int)request()->get('period_id'),
                fn($query) => $query->where('id', (int)request()->get('period_id')),
                fn($query) => $query->isActive()->latest()
            )
            ->firstOrFail();

        return [
            'chartData' => $analyticsService->getChartData($this->period->id),
            'period' => $this->period,
            'periods' => $periods,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Торговый период: ' . $this->period->name;
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
        return [
            Layout::rows([
                Select::make('period_id')
                    ->set('id', 'select-periods')
                    ->title('Выберите период')
                    ->options($this->query()['periods']->pluck('name', 'id')->toArray())
                    ->value($this->period->id)
                    ->help('Выберите торговый период для отображения данных'),
            ])->title('Фильтр по периоду'),

            Layout::view('dashboard.pnl-chart'),

            new HighchartsChart(
                $this->query()['chartData']['graph']
            ),
        ];
    }
}
