<?php

namespace App\Services\Trading;

use App\Models\Funding\FundingDeal;
use Carbon\Carbon;

class FundingDealChartCalculator
{
    private FundingDeal $deal;

    public function __construct(FundingDeal $deal)
    {
        $this->deal = $deal;
    }

    public function getChartConfig(): array
    {
        $priceData = $this->preparePriceData();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400,
            ],
            'title' => [
                'text' => "Симуляция фандинга {$this->deal->currency->code}"
            ],
            'xAxis' => [
                'type' => 'datetime',
                'plotLines' => [
                    [
                        'color' => '#775DD0',
                        'dashStyle' => 'dash',
                        'value' => $this->deal->funding_time->getPreciseTimestamp(3),
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
                [
                    'title' => [
                        'text' => 'Цена'
                    ],
                    'plotLines' => $this->preparePricePlotLines()
                ],
                [
                    'title' => [
                        'text' => 'Время запроса (мс)',
                        'style' => [
                            'color' => '#FF9800'
                        ]
                    ],
                    'opposite' => true,
                    'gridLineWidth' => 0,
                ]
            ],
            'series' => [
                [
                    'name' => 'Цена',
                    'data' => $priceData,
                    'color' => '#3490dc',
                    'yAxis' => 0
                ],
                [
                    'name' => 'Время запроса',
                    'data' => $this->prepareExecutionTimeData(),
                    'color' => '#FF9800',
                    'yAxis' => 1,
                    'type' => 'line'
                ]
            ]
        ];
    }

    private function preparePriceData(): array
    {
        $data = [];
        foreach ($this->deal->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)$point['price']
            ];
        }
        return $data;
    }

    private function prepareExecutionTimeData(): array
    {
        $data = [];
        foreach ($this->deal->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)($point['execution_time'] ?? 0)
            ];
        }
        return $data;
    }

    private function preparePricePlotLines(): array
    {
        $plotLines = [];

        // Добавляем линию цены входа
        if ($this->deal->entry_price) {
            $plotLines[] = [
                'color' => '#00E396',
                'dashStyle' => 'solid',
                'value' => $this->deal->entry_price,
                'width' => 2,
                'label' => [
                    'text' => 'Цена входа: ' . $this->deal->entry_price,
                    'style' => [
                        'color' => '#00E396'
                    ]
                ]
            ];
        }

        // Добавляем линию цены выхода
        if ($this->deal->exit_price) {
            $plotLines[] = [
                'color' => '#FEB019',
                'dashStyle' => 'solid',
                'value' => $this->deal->exit_price,
                'width' => 2,
                'label' => [
                    'text' => 'Цена выхода: ' . $this->deal->exit_price,
                    'style' => [
                        'color' => '#FEB019'
                    ]
                ]
            ];
        }

        return $plotLines;
    }
}
