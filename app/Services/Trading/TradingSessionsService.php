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
//                'dateTimeLabelFormats' => [
//                    'day' => '%e of %b',
//                    'minute' => '%I:%M',
//                    'hour' => '%I:%M',
//                ],
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

    public function getSessionsInfo(): array
    {
        return [
            [
                'name' => 'Азия',
                'time' => '05:00 — 14:00 (МСК)',
                'color' => '#FF4560',
            ],
            [
                'name' => 'Лондон',
                'time' => '11:00 — 19:00 (МСК)',
                'color' => '#00E396',
            ],
            [
                'name' => 'Нью-Йорк',
                'time' => '16:00 — 00:00 (МСК)',
                'color' => '#3490dc',
            ],
        ];
    }
}
