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
    private const int LIMIT = 5;

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

        $longVolumes = $longHistory->pluck('volume')->toArray();
        $btcVolumes = $shortHistory->pluck('volume_btc')->toArray();
        $ethVolumes = $shortHistory->pluck('volume_eth')->toArray();

        $analysis = $oscillator->analyze($longPnl, $shortPnl, $longVolumes, $btcVolumes, $ethVolumes);

        // Формируем сообщение
        $currentOscillator = end($chartData)['score'];
        $message = sprintf(
            "📊 <b>Осциллятор: %d%%</b> %s\n",
            $currentOscillator,
            $currentOscillator > 0 ? "🟢" : ($currentOscillator < 0 ? "🔴" : "⚪")
        );

        $message .= $this->formatAnalysisMessage($analysis);

//        $this->telegram->sendMessage($message);
//        dd($message);

        // Отправляем в Telegram
        if ($this->telegram->sendPhoto($message, $chartImage)) {
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
        // Основные показатели
        $message = "📊 <b>Анализ рынка</b>\n\n";

        // Тренд и корреляция (основная информация)
        $message .= sprintf(
            "🎯 <b>Тренд:</b> %+.1f%% %s\n",
            $analysis['market_trend'],
            $analysis['market_trend'] > 0 ? "📈" : "📉"
        );

        // Сила позиций (компактно)
        $message .= sprintf(
            "💪 <b>LONG/SHORT:</b> %+.1f%%/%+.1f%% %s\n",
            $analysis['long_strength'],
            $analysis['short_strength'],
            $analysis['long_strength'] > abs($analysis['short_strength']) ? "🟢" : "🔴"
        );

        // Корреляция движения позиций
        $message .= sprintf(
            "🔄 <b>Корреляция:</b> %.1f%% %s\n",
            $analysis['correlation'],
            $analysis['correlation'] > 80 ? "⚡" : ($analysis['correlation'] < -80 ? "⚠️" : "➖")
        );

        // Объемы и их корреляции
        $message .= "\n📊 <b>Анализ объемов:</b>\n";

        // Общий тренд объемов
        $message .= sprintf(
            "📈 Тренд: %+.1f%% %s\n",
            $analysis['weighted_volume_trend'],
            abs($analysis['weighted_volume_trend']) > 50 ?
                ($analysis['weighted_volume_trend'] > 0 ? "🔥" : "❄️") : "➖"
        );

        // Корреляции объемов (компактно)
        $volCorr = $analysis['volume_correlations'];
        $message .= sprintf(
            "BTC: %.1f%% | ETH: %.1f%%\n",
            $volCorr['asset_btc'],
            $volCorr['asset_eth']
        );

        // Корреляция цены и объема
        $priceVolCorr = $analysis['price_volume_correlations'];
        if (abs($priceVolCorr['long']) > 20 || abs($priceVolCorr['short']) > 20) {
            $message .= sprintf(
                "📊 Цена/Объем: L%.1f%% | S%.1f%%\n",
                $priceVolCorr['long'],
                $priceVolCorr['short']
            );
        }

        // Итоговый вывод
        $message .= "\n📝 <b>Вывод:</b> ";

        // Определяем силу тренда
        if (abs($analysis['market_trend']) > 50) {
            $message .= $analysis['market_trend'] > 0
                ? "Сильный бычий тренд"
                : "Сильный медвежий тренд";
        } elseif (abs($analysis['market_trend']) > 20) {
            $message .= $analysis['market_trend'] > 0
                ? "Умеренный бычий тренд"
                : "Умеренный медвежий тренд";
        } else {
            $message .= "Нейтральный рынок";
        }

        // Добавляем информацию об объемах, если есть явный тренд
        if (abs($analysis['weighted_volume_trend']) > 50) {
            $message .= sprintf(
                "\n💡 Объемы %s тренд",
                $analysis['weighted_volume_trend'] > 0 ? "поддерживают" : "противоречат"
            );
        }

        // Если есть сильная корреляция с основными криптовалютами
        if ($volCorr['asset_btc'] > 90 || $volCorr['asset_eth'] > 90) {
            $message .= "\n💫 Высокая корреляция с BTC/ETH";
        }

        return $message;
    }
}
