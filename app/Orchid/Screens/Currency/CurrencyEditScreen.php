<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Currency;

use App\Models\Currency;
use App\Models\CurrencyFavorite;
use App\Orchid\Filters\Statistics\Normalize\IntervalsFilter;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\Analyze\TechnicalAnalysis;
use App\Services\Api\Tickers;
use App\Services\Strategy\SmartMoneyStrategy;
use App\Services\Trading\TradingStatsService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;

class CurrencyEditScreen extends Screen
{
    /**
     * @var Currency
     */
    public $currency;

    private $technicalAnalysisService;
    private $smartMoneyService;
    private $tradingStatsService;
    private $candles = [];

    public function __construct(
        TechnicalAnalysis   $technicalAnalysisService,
        SmartMoneyStrategy  $smartMoneyService,
        TradingStatsService $tradingStatsService
    )
    {
        $this->technicalAnalysisService = $technicalAnalysisService;
        $this->smartMoneyService = $smartMoneyService;
        $this->tradingStatsService = $tradingStatsService;
    }

    public function query(Currency $currency): iterable
    {
        $isFavorite = CurrencyFavorite::where('user_id', auth()->id())
            ->where('currency_id', $currency->id)
            ->exists();

        $this->candles = (new Tickers())->getTickers($currency->code, (int)request()->get('interval', 1800));

//        "symbol" => "BTCUSDT"
//  "open" => 99150.01
//  "high" => 100421.8
//  "low" => 94150.05
//  "close" => 97504.01
//  "quote_volume" => 22210196918.803
//  "timestamp" => "2024-12-10T06:30:00Z"
//  "last_price" => 97441.8975
//  "volume" => 227777.19732


        return [
            'currency' => $currency,
            'isFavorite' => $isFavorite,
            'tradingStats' => $this->tradingStatsService->getStats($currency),
            'technicalAnalysis' => $this->technicalAnalysisService->analyzeV2($this->candles),
            'smartMoney' => $this->smartMoneyService->analyzeV2($this->candles),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return $this->currency->name;
    }

    public function commandBar(): iterable
    {
        $isFav = $this->currency->isFavorite();

        return [
            Button::make($isFav ? 'Удалить из избранного' : 'Добавить в избранное')
                ->method('toggleFavorite')
                ->icon('heart')
                ->type($isFav ? Color::DANGER : Color::DEFAULT)
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::selection([
                IntervalsFilter::class,
            ]),

            Layout::tabs([
                'Основная информация' => Layout::view('currencies.info'),
                'График цены' => [
                    new HighchartsChart(
                        $this->getPriceChart()
                    ),
                    Layout::view('currencies.candlestick-legend'),
                ],
                'Объем торгов' => [
                    new HighchartsChart(
                        $this->getVolumeChart()
                    ),

                    new HighchartsChart(
                        $this->getDeltaVolumeChart()
                    ),
                ],
                'Статистика сделок' => Layout::view('currencies.trading-stats'),
                'Технический анализ' => Layout::view('currencies.technical-analysis'),
                'Smart Money' => Layout::view('currencies.smart-money'),
            ]),
        ];
    }

    public function toggleFavorite(Request $request, Currency $currency)
    {
        $favorite = CurrencyFavorite::where('user_id', auth()->id())
            ->where('currency_id', $currency->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            Toast::info('Удалено из избранного');
        } else {
            CurrencyFavorite::create([
                'user_id' => auth()->id(),
                'currency_id' => $currency->id
            ]);
            Toast::info('Добавлено в избранное');
        }

        return redirect()->back();
    }

    private function getPriceChart(): array
    {
        $candleData = [];

        foreach ($this->candles as $candle) {

            $timestamp = strtotime($candle['timestamp']) * 1000;

            $candleData[] = [
                $timestamp,
                (float)$candle['open'],
                (float)$candle['high'],
                (float)$candle['low'],
                (float)$candle['close']
            ];
        }

        return [
            'chart' => [
                'type' => 'hollowcandlestick',
                'height' => 600
            ],
            'title' => [
                'text' => 'График ' . $this->currency->name
            ],
            'rangeSelector' => [
                'enabled' => true,
                'buttons' => [
                    [
                        'type' => 'hour',
                        'count' => 1,
                        'text' => '1ч'
                    ],
                    [
                        'type' => 'hour',
                        'count' => 4,
                        'text' => '4ч'
                    ],
                    [
                        'type' => 'day',
                        'count' => 1,
                        'text' => '1д'
                    ],
                    [
                        'type' => 'all',
                        'text' => 'Все'
                    ]
                ]
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
                    'format' => '{value:%H:%M}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Цена'
                ]
            ],
            'series' => [[
                'type' => 'hollowcandlestick',
                'name' => $this->currency->name,
                'data' => $candleData,
                'tooltip' => [
                    'pointFormat' =>
                        '<b>Открытие:</b> {point.open}<br/>' .
                        '<b>Максимум:</b> {point.high}<br/>' .
                        '<b>Минимум:</b> {point.low}<br/>' .
                        '<b>Закрытие:</b> {point.close}<br/>'
                ]
            ]]
        ];
    }
    private function getVolumeChart(): array
    {
        $candleData = [];

        foreach ($this->candles as $candle) {

            $timestamp = strtotime($candle['timestamp']) * 1000;

            $candleData[] = [
                $timestamp,
                (float)$candle['quote_volume'],
            ];
        }

        return [
            'chart' => [
                'height' => 600
            ],
            'title' => [
                'text' => 'График объемов ' . $this->currency->name
            ],
            'rangeSelector' => [
                'enabled' => true,
                'buttons' => [
                    [
                        'type' => 'hour',
                        'count' => 1,
                        'text' => '1ч'
                    ],
                    [
                        'type' => 'hour',
                        'count' => 4,
                        'text' => '4ч'
                    ],
                    [
                        'type' => 'day',
                        'count' => 1,
                        'text' => '1д'
                    ],
                    [
                        'type' => 'all',
                        'text' => 'Все'
                    ]
                ]
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
                    'format' => '{value:%H:%M}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Объем'
                ]
            ],
            'series' => [[
                'type' => 'spline',
                'name' => 'Объем торгов',
                'data' => $candleData,
                'tooltip' => [
                    'valueDecimals' => 2
                ]
            ]]
        ];
    }
    private function getDeltaVolumeChart(): array
    {
        $candleData = [];

        foreach ($this->candles as $candle) {
            $timestamp = strtotime($candle['timestamp']) * 1000;
            $volumeDelta = $candle['close'] > $candle['open'] ? $candle['volume'] : -$candle['volume'];

            $candleData[] = [
                $timestamp,
                $volumeDelta,
            ];
        }

        return [
            'chart' => [
                'type' => 'column',
                'height' => 600
            ],
            'title' => [
                'text' => 'Volume Delta ' . $this->currency->name
            ],
            'rangeSelector' => [
                'enabled' => true,
                'buttons' => [
                    [
                        'type' => 'hour',
                        'count' => 1,
                        'text' => '1ч'
                    ],
                    [
                        'type' => 'hour',
                        'count' => 4,
                        'text' => '4ч'
                    ],
                    [
                        'type' => 'day',
                        'count' => 1,
                        'text' => '1д'
                    ],
                    [
                        'type' => 'all',
                        'text' => 'Все'
                    ]
                ]
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
                    'format' => '{value:%H:%M}'
                ]
            ],
            'yAxis' => [
                'title' => [
                    'text' => 'Volume Delta'
                ]
            ],
            'series' => [[
                'type' => 'column',
                'name' => 'Volume Delta',
                'data' => $candleData,
                'color' => 'green',
                'negativeColor' => 'red',
                'tooltip' => [
                    'valueDecimals' => 2
                ]
            ]]
        ];
    }
}
