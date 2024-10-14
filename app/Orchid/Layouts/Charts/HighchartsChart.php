<?php

namespace App\Orchid\Layouts\Charts;

use Orchid\Screen\Layout;
use Orchid\Screen\Repository;

class HighchartsChart extends Layout
{
    /**
     * @var string
     */
    protected $template = 'layouts.charts.highcharts-chart';

    /**
     * @var array
     */
    protected $chartOptions;

    /**
     * HighchartsChart constructor.
     *
     * @param array $chartOptions
     */
    public function __construct(array $chartOptions)
    {
        $this->chartOptions = $chartOptions;
    }

    /**
     * @param Repository $repository
     *
     * @return mixed|void
     */
    public function build(Repository $repository)
    {
        return view($this->template, [
            'chartOptions' => json_encode($this->chartOptions),
        ]);
    }
}
