<?php

namespace App\Orchid\Screens\Statistics\BtcWallets;

use App\Models\BtcWallets\Wallet;
use App\Orchid\Layouts\Charts\HighchartsChart;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class BtcWalletShowScreen extends Screen
{
    /**
     * @var Wallet
     */
    public $wallet;

    public function query(Wallet $wallet): iterable
    {
        return [
            'wallet' => $wallet,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->wallet->address;
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'График баланса кошелька' => [
                    new HighchartsChart(
                        $this->getBalancesChart()
                    ),
                ],
            ]),
        ];
    }

    private function getBalancesChart(): array
    {
        $balances = [];

        foreach ($this->wallet->balances as $balance) {

            $timestamp = $balance->created_at->timestamp * 1000;

            $balances[] = [
                $timestamp,
                (float)$balance->balance,
            ];
        }

        return [
            'chart' => [
                'height' => 600,
                'type' => 'line'
            ],
            'title' => [
                'text' => 'Динамика изменений баланса'
            ],
            'rangeSelector' => [
                'enabled' => false,
            ],
            'navigator' => [
                'enabled' => true,
            ],
            'scrollbar' => [
                'enabled' => true
            ],
            'xAxis' => [
                'type' => 'datetime',
                'labels' => [
                    'format' => '{value:%Y-%m-%d %H:%M}'  //  '{value:%d.%m %H:%M}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Изменение'
                ],
                'labels' => [
                    'format' => '{value}'
                ]
            ],
            'series' => [
                [
                    'name' => 'Изменение баланса',
                    'data' => $balances,
                    'color' => '#7cb5ec',
                    'tooltip' => [
                        'valueSuffix' => '',
                        'valueDecimals' => 2
                    ]
                ],
            ],
            'plotOptions' => [
                'series' => [
                    'marker' => [
                        'enabled' => true,
                        'radius' => 3
                    ]
                ]
            ]
        ];
    }
}
