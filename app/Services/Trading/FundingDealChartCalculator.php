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
        $highData = $this->prepareHighData();
        $lowData = $this->prepareLowData();

        return [
            'chart' => [
                'type' => 'line',
                'height' => 400,
            ],
            'title' => [
                'text' => "Сделка фандинга {$this->deal->currency->code}"
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
        foreach ($this->deal->price_history as $point) {
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
        foreach ($this->deal->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)($point['high'] ?? 0)
            ];
        }
        return $data;
    }

    private function prepareLowData(): array
    {
        $data = [];
        foreach ($this->deal->price_history as $point) {
            $data[] = [
                Carbon::createFromTimestamp($point['timestamp'])->getPreciseTimestamp(3),
                (float)($point['low'] ?? 0)
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
