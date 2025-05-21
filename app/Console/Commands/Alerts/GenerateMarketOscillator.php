<?php
/**
 * php artisan market:oscillator
 */
namespace App\Console\Commands\Alerts;

use App\Models\Trade;
use App\Services\Analyze\MarketOscillator;
use App\Services\ChartGenerator;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMarketOscillator extends Command
{
    protected $signature = 'market:oscillator';
    protected $description = 'Генерирует осциллятор рынка на основе фиксированных лонг и шорт сделок';

    private TelegramService $telegram;
    private ChartGenerator $chartGenerator;
    private const string LONG_TRADE_ID = 'LONG_TRADE_ID';
    private const string SHORT_TRADE_ID = 'SHORT_TRADE_ID';
    private const int LIMIT = 60;

    public function __construct(TelegramService $telegram, ChartGenerator $chartGenerator)
    {
        parent::__construct();
        $this->telegram = $telegram;
        $this->chartGenerator = $chartGenerator;
    }

    public function handle()
    {
        /**
         * @var $longTrade Trade
         * @var $shortTrade Trade
         */
        $longTrade = Trade::find(env(self::LONG_TRADE_ID));
        $shortTrade = Trade::find(env(self::SHORT_TRADE_ID));

        if (!$longTrade || !$shortTrade) {
            $this->error('Не найдены фиксированные сделки. Проверьте настройки в .env');
            return;
        }

        // Получаем историю PNL для обеих сделок
        $longHistory = $longTrade->pnlHistory()->latest()->limit(self::LIMIT)->get()->sortBy('created_at')->values();
        $shortHistory = $shortTrade->pnlHistory()->latest()->limit(self::LIMIT)->get()->sortBy('created_at')->values();

        if ($longHistory->isEmpty() || $shortHistory->isEmpty()) {
            $this->error('Нет истории PNL для одной или обеих сделок');
            return;
        }

        // Формируем данные для графика
        $chartData = [];
        $timestamps = [];

        // Собираем все уникальные временные метки
        foreach ($longHistory as $record) {
            $timestamps[$record->created_at->timestamp] = $record->created_at;
        }
        foreach ($shortHistory as $record) {
            $timestamps[$record->created_at->timestamp] = $record->created_at;
        }
        ksort($timestamps);

        // Для каждой временной метки рассчитываем осциллятор
        $lastLongPnl = 0;
        $lastShortPnl = 0;
        foreach ($timestamps as $timestamp => $date) {
            $longPnl = $longHistory->where('created_at', '<=', $date)->last()?->unrealized_pnl ?? $lastLongPnl;
            $shortPnl = $shortHistory->where('created_at', '<=', $date)->last()?->unrealized_pnl ?? $lastShortPnl;
            $lastLongPnl = $longPnl;
            $lastShortPnl = $shortPnl;

            $maxPnl = max(abs($longPnl), abs($shortPnl), 1); // чтобы не было деления на 0
            $oscillator = (($longPnl - $shortPnl) / $maxPnl) * 100;

            $chartData[] = [
                'timestamp' => $date->format('Y-m-d H:i:s'),
                'long' => round($longPnl, 2),
                'short' => round($shortPnl, 2),
                'score' => round($oscillator, 2)
            ];
        }

        // Генерируем график
        $chartImage = $this->chartGenerator->generateLongShortJpGraph($chartData, '');

        // Сохраняем график в файл
//        $filename = storage_path('app/public/oscillator_' . date('Y-m-d_H-i-s') . '.png');
//        file_put_contents($filename, $chartImage);
//
//        $this->info("График сохранен в файл: {$filename}");
//        return;

        // Анализируем данные
        $oscillator = new MarketOscillator();
        $longPnl = $longHistory->pluck('unrealized_pnl')->toArray();
        $shortPnl = $shortHistory->pluck('unrealized_pnl')->toArray();
        $analysis = $oscillator->analyze($longPnl, $shortPnl);

        // Формируем сообщение
        $currentOscillator = end($chartData)['score'];
        $message = "📊 <b>Осциллятор рынка: {$currentOscillator}</b>\n\n";

        if ($currentOscillator > 0) {
            $message .= "🟢 Преобладает лонг позиция";
        } elseif ($currentOscillator < 0) {
            $message .= "🔴 Преобладает шорт позиция";
        } else {
            $message .= "⚪ Нейтральное состояние";
        }

        $message .= "\n\n" . $this->formatAnalysisMessage($analysis);

        // Отправляем в Telegram
        if ($this->telegram->sendPhoto($chartImage, $message)) {
            $this->info('Осциллятор успешно отправлен');
//            unlink($filename); // удаляем файл после отправки
        } else {
            $this->error('Ошибка при отправке осциллятора');
        }
    }

    /**
     * Форматируем текст для Telegram с анализом
     */
    private function formatAnalysisMessage(array $analysis): string
    {
        $correlation = $analysis['correlation'];
        $marketTrend = $analysis['market_trend'];
        $longStrength = $analysis['long_strength'];
        $shortStrength = $analysis['short_strength'];

        $message = "📊 <b>Анализ рынка</b>\n\n";

        // Корреляция
        $message .= "🔄 <b>Корреляция движения:</b> {$correlation}%\n";
        if ($correlation > 80) {
            $message .= "   ↪️ Сильное согласованное движение\n";
        } elseif ($correlation < -80) {
            $message .= "   ↪️ Сильное противоположное движение\n";
        } elseif (abs($correlation) < 20) {
            $message .= "   ↪️ Независимое движение позиций\n";
        }

        // Тренд рынка
        $message .= "\n📈 <b>Тренд рынка:</b> {$marketTrend}%\n";
        if (abs($marketTrend) < 20) {
            $message .= "   ↪️ Боковое движение\n";
        } else {
            $message .= "   ↪️ " . ($marketTrend > 0 ? "Восходящий тренд" : "Нисходящий тренд") . "\n";
        }

        // Сила позиций
        $message .= "\n💪 <b>Сила позиций:</b>\n";
        $message .= "   📗 Лонг: {$longStrength}%\n";
        $message .= "   📕 Шорт: {$shortStrength}%\n";

        // Общий вывод
        $message .= "\n📝 <b>Вывод:</b> ";
        if (abs($marketTrend) > 50) {
            if ($marketTrend > 0) {
                $message .= "Сильный бычий тренд";
            } else {
                $message .= "Сильный медвежий тренд";
            }
        } elseif (abs($marketTrend) > 20) {
            if ($marketTrend > 0) {
                $message .= "Умеренный бычий тренд";
            } else {
                $message .= "Умеренный медвежий тренд";
            }
        } else {
            $message .= "Нейтральный рынок";
        }

        return $message;
    }
}
