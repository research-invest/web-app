<?php

namespace App\Services;

use App\Models\Trade;
use App\Services\Api\Tickers;
use Carbon\Carbon;

class TradeRecommendationService
{
    private Trade $trade;
    private array $recommendations = [];

    public function __construct(Trade $trade)
    {
        $this->trade = $trade;
    }

    public function analyze(): array
    {
        if (!$this->trade->exists || !$this->trade->isStatusOpen()) {
            return [];
        }

        $this->checkPnlTrend();
        $this->checkTimeInTrade();
        $this->checkTargetProgress();
//        $this->checkVolatility();
        $this->checkHistoricalPatterns();
        $this->checkPortfolioState();

        return $this->recommendations;
    }

    private function checkPnlTrend(): void
    {
        // Проверяем PnL за последние 24 часа
        $dayAgo = now()->subDay();
        $pnlHistory = $this->trade->pnlHistory()
            ->where('created_at', '>=', $dayAgo)
            ->orderBy('created_at')
            ->get();

        if ($pnlHistory->count() > 0) {
            $negativeCount = $pnlHistory->where('unrealized_pnl', '<', 0)->count();
            $totalCount = $pnlHistory->count();

            if ($negativeCount / $totalCount > 0.8) { // Если 80% времени в минусе
                $this->recommendations[] = [
                    'type' => 'warning',
                    'title' => 'Устойчивый отрицательный PnL',
                    'description' => 'Сделка находится в минусе более 80% времени за последние 24 часа. Рекомендуется рассмотреть частичное закрытие позиции для минимизации потерь.',
                    'action' => 'reduce_position',
                ];
            }
        }
    }

    private function checkTimeInTrade(): void
    {
        $daysInTrade = $this->trade->created_at->diffInDays(now());
        $initialRiskReward = abs(($this->trade->take_profit_price - $this->trade->entry_price) /
            ($this->trade->entry_price - $this->trade->stop_loss_price));

        if ($daysInTrade > 7 && $this->trade->unrealized_pnl < 0) {
            $this->recommendations[] = [
                'type' => 'danger',
                'title' => 'Длительная убыточная позиция',
                'description' => "Сделка находится в минусе более {$daysInTrade} дней. При отсутствии сильных причин удерживать позицию, рекомендуется закрытие.",
                'action' => 'close_position',
            ];
        }

        // Если риск/награда был > 2 и прошло больше 3 дней без достижения цели
        if ($initialRiskReward > 2 && $daysInTrade > 3 && $this->trade->unrealized_pnl <= 0) {
            $this->recommendations[] = [
                'type' => 'warning',
                'title' => 'Не достигнута целевая прибыль',
                'description' => 'Несмотря на хорошее соотношение риск/награда, цель не достигнута за 3 дня. Рассмотрите пересмотр целевых уровней.',
                'action' => 'revise_targets',
            ];
        }
    }

    private function checkTargetProgress(): void
    {
        if ($this->trade->target_profit_amount) {
            $progressPercent = ($this->trade->unrealized_pnl / $this->trade->target_profit_amount) * 100;

            if ($progressPercent < -50) {
                $this->recommendations[] = [
                    'type' => 'danger',
                    'title' => 'Значительное отклонение от цели',
                    'description' => 'Убыток превышает 50% от целевой прибыли. Рекомендуется немедленный пересмотр позиции.',
                    'action' => 'emergency_review',
                ];
            }
        }
    }

    private function checkVolatility(): void
    {
        $tickerService = new Tickers();
        $data30m = $tickerService->getTickers($this->trade->currency->code, 1800);

        // Рассчитываем волатильность (стандартное отклонение)
        $prices = array_column($data30m, 'last_price');
        $volatility = $this->calculateVolatility($prices);

        // Получаем среднюю волатильность за последнюю неделю
        $avgWeekVolatility = $this->trade->currency->priceHistory()
            ->where('created_at', '>=', now()->subWeek())
            ->get()
            ->pipe(function ($collection) {
                return $this->calculateVolatility($collection->pluck('price')->toArray());
            });

        // Если текущая волатильность значительно выше средней
        if ($volatility > $avgWeekVolatility * 1.5) {
            $this->recommendations[] = [
                'type' => 'warning',
                'title' => 'Повышенная волатильность',
                'description' => 'Наблюдается повышенная волатильность. Рекомендуется уменьшить размер позиции или временно выйти из рынка.',
                'action' => 'reduce_position',
            ];
        }
    }

    private function checkHistoricalPatterns(): void
    {
        // Получаем похожие исторические сделки
        $similarTrades = Trade::where('currency_id', $this->trade->currency_id)
            ->where('position_type', $this->trade->position_type)
            ->where('status', 'closed')
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        if ($similarTrades->count() > 0) {
            // Анализируем успешность похожих сделок
            $successfulTrades = $similarTrades->filter(fn($trade) => $trade->realized_pnl > 0);
            $successRate = $successfulTrades->count() / $similarTrades->count();

            // Анализируем среднее время до успешного закрытия
            $avgSuccessTime = $successfulTrades
                ->avg(fn($trade) => $trade->created_at->diffInHours($trade->closed_at));

            $currentTradeTime = $this->trade->created_at->diffInHours(now());

            if ($successRate < 0.3 && $currentTradeTime > $avgSuccessTime) {
                $this->recommendations[] = [
                    'type' => 'danger',
                    'title' => 'Неблагоприятная статистика',
                    'description' => 'Исторически только ' . round($successRate * 100) . '% похожих сделок были успешными. Рекомендуется пересмотреть позицию.',
                    'action' => 'review_position',
                ];
            }
        }
    }

    private function checkPortfolioState(): void
    {
        // Получаем все открытые сделки пользователя
        $openTrades = Trade::where('user_id', $this->trade->user_id)
            ->where('status', 'open')
            ->get();

        // Рассчитываем общий PnL портфеля
        $totalPnl = $openTrades->sum('unrealized_pnl');
        $totalRisk = $openTrades->sum(function ($trade) {
            return abs($trade->position_size * ($trade->entry_price - $trade->stop_loss_price) / $trade->entry_price);
        });

        // Проверяем долю риска текущей сделки
        $tradeRisk = abs($this->trade->position_size *
            ($this->trade->entry_price - $this->trade->stop_loss_price) /
            $this->trade->entry_price);

        $riskShare = $tradeRisk / $totalRisk;

        if ($riskShare > 0.4) { // Если риск сделки превышает 40% от общего риска
            $this->recommendations[] = [
                'type' => 'warning',
                'title' => 'Высокая концентрация риска',
                'description' => 'Данная сделка составляет ' . round($riskShare * 100) . '% от общего риска портфеля. Рекомендуется диверсификация.',
                'action' => 'reduce_position',
            ];
        }

        // Если общий PnL портфеля отрицательный и эта сделка тоже в минусе
        if ($totalPnl < 0 && $this->trade->unrealized_pnl < 0) {
            $this->recommendations[] = [
                'type' => 'info',
                'title' => 'Общее состояние портфеля',
                'description' => 'Портфель находится в просадке. Рекомендуется сократить размер позиций и пересмотреть стратегию управления рисками.',
                'action' => 'review_risk_management',
            ];
        }
    }

    private function calculateVolatility(array $prices): float
    {
        $mean = array_sum($prices) / count($prices);
        $variance = array_reduce($prices, static function ($carry, $price) use ($mean) {
                return $carry + (($price - $mean) ** 2);
            }, 0) / count($prices);

        return sqrt($variance);
    }
}
