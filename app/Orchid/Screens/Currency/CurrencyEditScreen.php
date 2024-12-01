<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Currency;

use App\Models\Currency;
use App\Models\CurrencyFavorite;
use App\Services\Analyze\TechnicalAnalysis;
use App\Services\Api\Tickers;
use App\Services\Strategy\SmartMoneyStrategy;
use App\Services\Trading\TradingStatsService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Color;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;

class CurrencyEditScreen extends Screen
{
    public $currency;

    private $technicalAnalysisService;
    private $smartMoneyService;
    private $tradingStatsService;

    public function __construct(
        TechnicalAnalysis $technicalAnalysisService,
        SmartMoneyStrategy $smartMoneyService,
        TradingStatsService $tradingStatsService
    ) {
        $this->technicalAnalysisService = $technicalAnalysisService;
        $this->smartMoneyService = $smartMoneyService;
        $this->tradingStatsService = $tradingStatsService;
    }

    public function query(Currency $currency): iterable
    {
        $isFavorite = CurrencyFavorite::where('user_id', auth()->id())
            ->where('currency_id', $currency->id)
            ->exists();

        $candles = (new Tickers())->getTickers($currency->code, 1800);

        return [
            'currency' => $currency,
            'isFavorite' => $isFavorite,
            'priceChart' => $this->getPriceChartData($currency),
            'volumeChart' => $this->getVolumeChartData($currency),
            'tradingStats' => $this->tradingStatsService->getStats($currency),
//            'technicalAnalysis' => $this->technicalAnalysisService->analyze($currency),
//            'smartMoney' => $this->smartMoneyService->analyze($candles),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Основная информация' => Layout::view('currencies.info'),
                'График цены' => Layout::view('currencies.price-chart'),
                'Объем торгов' => Layout::view('currencies.volume-chart'),
                'Статистика сделок' => Layout::view('currencies.trading-stats'),
                'Технический анализ' => Layout::view('currencies.technical-analysis'),
                'Smart Money' => Layout::view('currencies.smart-money'),
            ]),

            Layout::rows([
                Button::make('Добавить в избранное')
                    ->method('toggleFavorite')
                    ->icon('heart')
                    ->type($this->currency->isFavorite() ? Color::DANGER : Color::DEFAULT)
            ])
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

    private function getPriceChartData(Currency $currency)
    {
        return [];

    }

    private function getVolumeChartData(Currency $currency)
    {
        return [];
    }
}
