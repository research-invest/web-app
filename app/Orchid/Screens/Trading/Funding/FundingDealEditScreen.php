<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Funding;

use App\Models\Funding\FundingDeal;
use App\Models\Funding\FundingSimulation;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Orchid\Layouts\Trading\Deals\Funding\InfoBlock;
use App\Services\Trading\FundingSimulationChartCalculator;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class FundingDealEditScreen extends Screen
{

    /**
     * @var FundingDeal
     */
    public $deal;

    public function name(): ?string
    {
        return sprintf('Сделка: %s', $this->deal->currency->name);
    }

    public function query(FundingDeal $deal): iterable
    {
        $this->deal = $deal;

        return [
            'deal' => $deal,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('TV')
                ->icon('grid')
                ->target('_blank')
                ->canSee($this->deal->exists)
                ->href($this->deal->currency->getTVLink())
                ->class('btn btn-default'),

            Link::make('Exchange')
                ->icon('grid')
                ->target('_blank')
                ->canSee($this->deal->exists)
                ->href($this->deal->currency->getExchangeLink())
                ->class('btn btn-default'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::block(InfoBlock::class)->vertical(),

//            new HighchartsChart(
//                $this->getFundingSimulationsChart()
//            ),

        ];
    }

    private function getFundingSimulationsChart(): array
    {
        $calculator = new FundingSimulationChartCalculator(
            simulation: $this->deal
        );

        return $calculator->getChartConfig();
    }

}
