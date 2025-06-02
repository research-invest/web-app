<?php

namespace App\Services\Trading;

use App\Models\Funding\FundingSimulation;
use Carbon\Carbon;

class FundingSimulationChartCalculator
{
    private FundingSimulation $simulation;

    public function __construct(FundingSimulation $simulation)
    {
        $this->simulation = $simulation;
    }

    public function getChartConfig(): array
    {
        $priceData = $this->preparePriceData();
        $highData = $this->prepareHighData();
        $lowData = $this->prepareLowData();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400,
            ],
            'title' => [
                'text' => "Симуляция фандинга {$this->simulation->currency->code}"
            ],
            'xAxis' => [
                'type' => 'datetime',
                'plotLines' => [
                    [
                        'color' => '#775DD0',
                        'dashStyle' => 'dash',
                        'value' => $this->simulation->funding_time->getPreciseTimestamp(3),
                        'width' => 2,
                        'label' => [
                            'text' => 'Время фандинга',
                            'style' => [
                                'color' => '#775DD0'
                            ]
                        ]
                    ]
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Цена'
                ],
                'plotLines' => $this->preparePricePlotLines()
            ],

            'series' => [
                [
                    'name' => 'Цена',
                    'data' => $priceData,
                    'color' => '#3490dc'
                ],
                [
                    'name' => 'Максимум',
                    'data' => $highData,
                    'color' => '#00E396',
                    'dashStyle' => 'dash'
                ],
                [
                    'name' => 'Минимум',
                    'data' => $lowData,
                    'color' => '#FF4560',
                    'dashStyle' => 'dash'
                ]
            ]
        ];
    }

    private function preparePriceData(): array
    {
        $data = [];
        foreach ($this->simulation->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)$point['price']
            ];
        }
        return $data;
    }

    private function prepareHighData(): array
    {
        $data = [];
        foreach ($this->simulation->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)$point['high']
            ];
        }
        return $data;
    }

    private function prepareLowData(): array
    {
        $data = [];
        foreach ($this->simulation->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)$point['low']
            ];
        }
        return $data;
    }

    private function preparePricePlotLines(): array
    {
        $plotLines = [];

        // Добавляем линию цены входа
        if ($this->simulation->entry_price) {
            $plotLines[] = [
                'color' => '#00E396',
                'dashStyle' => 'solid',
                'value' => $this->simulation->entry_price,
                'width' => 2,
                'label' => [
                    'text' => 'Цена входа: ' . $this->simulation->entry_price,
                    'style' => [
                        'color' => '#00E396'
                    ]
                ]
            ];
        }

        // Добавляем линию цены выхода
        if ($this->simulation->exit_price) {
            $plotLines[] = [
                'color' => '#FEB019',
                'dashStyle' => 'solid',
                'value' => $this->simulation->exit_price,
                'width' => 2,
                'label' => [
                    'text' => 'Цена выхода: ' . $this->simulation->exit_price,
                    'style' => [
                        'color' => '#FEB019'
                    ]
                ]
            ];
        }

        return $plotLines;
    }
}
