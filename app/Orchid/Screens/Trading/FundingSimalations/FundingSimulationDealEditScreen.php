<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\FundingSimalations;

use App\Models\FundingSimulation;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\Trading\FundingSimulationChartCalculator;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;

class FundingSimulationDealEditScreen extends Screen
{

    /**
     * @var FundingSimulation
     */
    public $trade;

    public function name(): ?string
    {
        return sprintf('Сделка: %s', $this->trade->currency->name);
    }

    public function query(FundingSimulation $trade): iterable
    {
        $this->trade = $trade;

        return [
            'trade' => $trade,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('TV')
                ->icon('grid')
                ->target('_blank')
                ->canSee($this->trade->exists)
                ->href($this->trade->currency->getTVLink())
                ->class('btn btn-default'),

            Link::make('Exchange')
                ->icon('grid')
                ->target('_blank')
                ->canSee($this->trade->exists)
                ->href($this->trade->currency->getExchangeLink())
                ->class('btn btn-default'),
        ];
    }

    public function layout(): iterable
    {
        return [

            new HighchartsChart(
                $this->getFundingSimulationsChart()
            ),

        ];
    }

    private function getFundingSimulationsChart(): array
    {
        $calculator = new FundingSimulationChartCalculator(
            simulation: $this->trade
        );

        return $calculator->getChartConfig();
    }

}
