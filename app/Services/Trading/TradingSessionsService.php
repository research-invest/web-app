<?php

namespace App\Services\Trading;

use Carbon\Carbon;

class TradingSessionsService
{
    public function getChartConfig(): array
    {
        $now = Carbon::now()->setTimezone('Europe/Moscow');
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

        // Азиатская сессия (2:00 - 11:00 UTC) => 5:00 - 14:00 MSK
        $asiaStart = $startOfDay->copy()->addHours(5);
        $asiaEnd = $startOfDay->copy()->addHours(14);

        // Лондонская сессия (8:00 - 16:00 UTC) => 11:00 - 19:00 MSK
        $londonStart = $startOfDay->copy()->addHours(11);
        $londonEnd = $startOfDay->copy()->addHours(19);

        // Нью-Йоркская сессия (13:00 - 21:00 UTC) => 16:00 - 00:00 MSK
        $nyStart = $startOfDay->copy()->addHours(16);
        $nyEnd = $startOfDay->copy()->addHours(24); // 00:00 следующего дня


        return [
            'time' => [
                'timezone' => 'Europe/Moscow',
            ],
            'chart' => [
                'type' => 'line',
                'height' => 200,
            ],
            'title' => [
                'text' => 'Торговые сессии (МСК)'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'min' => $startOfDay->timestamp * 1000,
                'max' => $endOfDay->timestamp * 1000,
                'plotBands' => [
                    [
                        'from' => $asiaStart->timestamp * 1000,
                        'to' => $asiaEnd->timestamp * 1000,
                        'color' => 'rgba(255,69,96,0.2)',
                        'label' => [
                            'text' => 'Азия',
                            'style' => ['color' => '#FF4560', 'fontWeight' => 'bold']
                        ]
                    ],
                    [
                        'from' => $londonStart->timestamp * 1000,
                        'to' => $londonEnd->timestamp * 1000,
                        'color' => 'rgba(0,227,150,0.2)',
                        'label' => [
                            'text' => 'Лондон',
                            'style' => ['color' => '#00E396', 'fontWeight' => 'bold']
                        ]
                    ],
                    [
                        'from' => $nyStart->timestamp * 1000,
                        'to' => $nyEnd->timestamp * 1000,
                        'color' => 'rgba(52,144,220,0.2)',
                        'label' => [
                            'text' => 'Нью-Йорк',
                            'style' => ['color' => '#3490dc', 'fontWeight' => 'bold']
                        ]
                    ],
                ],
                'plotLines' => [
                    [
                        'color' => '#FF0000',
                        'dashStyle' => 'solid',
                        'value' => $now->timestamp * 1000,
                        'width' => 2,
                        'label' => [
                            'text' => 'Сейчас',
                            'style' => [
                                'color' => '#FF0000',
                                'fontWeight' => 'bold'
                            ]
                        ]
                    ]
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => ''
                ],
                'max' => 1,
                'min' => 0,
                'visible' => false
            ],
            'series' => [
                [
                    'name' => 'Время',
                    'data' => []
                ]
            ],
            'legend' => [
                'enabled' => false
            ]
        ];
    }

    public function getKillZonesChartConfig(): array
    {
        $now = Carbon::now()->setTimezone('Europe/Moscow');
        $startOfDay = $now->copy()->startOfDay();
        $endOfDay = $now->copy()->endOfDay();

        // Kill Zones
        $akzStart = $startOfDay->copy()->addHours(9);
        $akzEnd = $startOfDay->copy()->addHours(12);
        $lkzStart = $startOfDay->copy()->addHours(10);
        $lkzEnd = $startOfDay->copy()->addHours(13);
        $nykzStart = $startOfDay->copy()->addHours(15);
        $nykzEnd = $startOfDay->copy()->addHours(18);
        $lunchKzStart = $startOfDay->copy()->addHours(19);
        $lunchKzEnd = $startOfDay->copy()->addHours(20);

        return [
            'time' => [
                'timezone' => 'Europe/Moscow',
            ],
            'chart' => [
                'type' => 'line',
                'height' => 120,
            ],
            'title' => [
                'text' => 'Kill Zones (KZ) (МСК)'
            ],
            'xAxis' => [
                'type' => 'datetime',
                'min' => $startOfDay->timestamp * 1000,
                'max' => $endOfDay->timestamp * 1000,
                'plotBands' => [
                    [
                        'from' => $akzStart->timestamp * 1000,
                        'to' => $akzEnd->timestamp * 1000,
                        'color' => 'rgba(255,193,7,0.4)',
                        'label' => [
                            'text' => 'AKZ',
                            'style' => ['color' => '#bfa800', 'fontWeight' => 'bold']
                        ]
                    ],
                    [
                        'from' => $lkzStart->timestamp * 1000,
                        'to' => $lkzEnd->timestamp * 1000,
                        'color' => 'rgba(255,87,34,0.4)',
                        'label' => [
                            'text' => 'LKZ',
                            'style' => ['color' => '#ff5722', 'fontWeight' => 'bold']
                        ]
                    ],
                    [
                        'from' => $nykzStart->timestamp * 1000,
                        'to' => $nykzEnd->timestamp * 1000,
                        'color' => 'rgba(103,58,183,0.4)',
                        'label' => [
                            'text' => 'NYKZ',
                            'style' => ['color' => '#673ab7', 'fontWeight' => 'bold']
                        ]
                    ],
                    [
                        'from' => $lunchKzStart->timestamp * 1000,
                        'to' => $lunchKzEnd->timestamp * 1000,
                        'color' => 'rgba(96,125,139,0.4)',
                        'label' => [
                            'text' => 'Lunch KZ',
                            'style' => ['color' => '#607d8b', 'fontWeight' => 'bold']
                        ]
                    ],
                ],
            ],
            'yAxis' => [
                'title' => [
                    'text' => ''
                ],
                'max' => 1,
                'min' => 0,
                'visible' => false
            ],
            'series' => [
                [
                    'name' => 'KZ',
                    'data' => []
                ]
            ],
            'legend' => [
                'enabled' => false
            ]
        ];
    }

    public function getSessionsInfo(): array
    {
        return [
            [
                'name' => 'Азия',
                'time' => '05:00 — 14:00 (МСК)',
                'color' => '#FF4560',
                'kz' => [
                    [
                        'name' => 'Asia Kill Zone (AKZ)',
                        'time' => '09:00 — 12:00 (МСК)',
                        'color' => '#ffc107',
                    ]
                ]
            ],
            [
                'name' => 'Лондон',
                'time' => '11:00 — 19:00 (МСК)',
                'color' => '#00E396',
                'kz' => [
                    [
                        'name' => 'London Kill Zone (LKZ)',
                        'time' => '10:00 — 13:00 (МСК)',
                        'color' => '#ff5722',
                    ]
                ]
            ],
            [
                'name' => 'Нью-Йорк',
                'time' => '16:00 — 00:00 (МСК)',
                'color' => '#3490dc',
                'kz' => [
                    [
                        'name' => 'New York Kill Zone (NYKZ)',
                        'time' => '15:00 — 18:00 (МСК)',
                        'color' => '#673ab7',
                    ],
                    [
                        'name' => 'Lunch Kill Zone',
                        'time' => '19:00 — 20:00 (МСК)',
                        'color' => '#607d8b',
                    ]
                ]
            ],
        ];
    }
}
