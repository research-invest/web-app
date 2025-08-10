<?php

namespace App\Orchid\Screens\TradingView;

use App\Models\TradingViewWebhook;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Illuminate\Http\Request;

class WebhookListScreen extends Screen
{
    public function query(): array
    {
        return [
            'webhooks' => TradingViewWebhook::filters()
                ->defaultSort('created_at', 'desc')
                ->paginate(25),
        ];
    }

    public function name(): ?string
    {
        return 'TradingView Вебхуки';
    }

    public function description(): ?string
    {
        return 'Список всех полученных вебхуков от TradingView';
    }

    public function commandBar(): array
    {
        return [
            Button::make('Очистить старые')
                ->icon('trash')
                ->method('clearOld')
                ->type(Color::DANGER)
                ->confirm('Вы уверены, что хотите удалить вебхуки старше 30 дней?'),

            Button::make('Обновить')
                ->icon('refresh')
                ->method('refresh')
                ->type(Color::INFO),
        ];
    }

    public function layout(): array
    {
        return [
            Layout::rows([
                Input::make('filter[symbol]')
                    ->title('Символ')
                    ->placeholder('BTC/USDT, ETH/USDT...')
                    ->value(request('filter.symbol')),

                Select::make('filter[action]')
                    ->title('Действие')
                    ->empty('Все действия')
                    ->options([
                        'buy' => 'Покупка',
                        'sell' => 'Продажа',
                        'close' => 'Закрытие',
                        'alert' => 'Алерт',
                    ])
                    ->value(request('filter.action')),

                Input::make('filter[strategy]')
                    ->title('Стратегия')
                    ->placeholder('Название стратегии')
                    ->value(request('filter.strategy')),

                Button::make('Фильтровать')
                    ->type(Color::PRIMARY)
                    ->icon('filter')
                    ->method('applyFilter'),

                Button::make('Сбросить')
                    ->type(Color::LIGHT)
                    ->icon('refresh')
                    ->method('resetFilter'),
            ])->title('Фильтры'),

            Layout::table('webhooks', [
                TD::make('id', 'ID')
                    ->sort()
                    ->render(fn(TradingViewWebhook $webhook) => "<span class='badge badge-light'>#{$webhook->id}</span>"
                    ),

                TD::make('symbol', 'Символ')
                    ->sort()
                    ->render(fn(TradingViewWebhook $webhook) => "<strong class='text-primary'>{$webhook->symbol}</strong>"
                    ),

                TD::make('action', 'Действие')
                    ->sort()
                    ->render(function (TradingViewWebhook $webhook) {
                        $colors = [
                            'buy' => 'success',
                            'sell' => 'danger',
                            'close' => 'warning',
                            'alert' => 'info'
                        ];
                        $color = $colors[$webhook->action] ?? 'secondary';
                        return "<span class='badge badge-{$color}'>{$webhook->action}</span>";
                    }),

                TD::make('strategy', 'Стратегия')
                    ->sort()
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->strategy ? "<em>{$webhook->strategy}</em>" : '-'
                    ),

                TD::make('price', 'Цена')
                    ->sort()
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->price ? number_format($webhook->price, 8) : '-'
                    ),

                TD::make('timeframe', 'Таймфрейм')
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->timeframe ? "<code>{$webhook->timeframe}</code>" : '-'
                    ),

                TD::make('exchange', 'Биржа')
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->exchange ? ucfirst($webhook->exchange) : '-'
                    ),

                TD::make('source_ip', 'IP')
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->source_ip ? "<small><code>{$webhook->source_ip}</code></small>" : '-'
                    ),

                TD::make('created_at', 'Получен')
                    ->sort()
                    ->render(fn(TradingViewWebhook $webhook) => $webhook->created_at->format('d.m.Y H:i:s')
                    ),

                TD::make('actions', 'Действия')
                    ->render(function (TradingViewWebhook $webhook) {

                        return ModalToggle::make('Детали')
                            ->modal('webhookDetailModal')
                            ->icon('eye')
                            ->asyncParameters(['webhook' => $webhook->id])
//                            ->method('addOrder')
                            ->class('btn btn-primary');

                        return Button::make('Детали')
                            ->icon('eye')
                            ->type(Color::LINK)
                            ->modal('webhookDetailModal')
                            ->modalTitle('Детали вебхука #' . $webhook->id)
                            ->async('getWebhookDetails')
                            ->asyncParameters(['webhook' => $webhook->id]);
                    }),
            ]),

            Layout::modal('webhookDetailModal', Layout::rows([
                Input::make('webhook.id')->title('ID'),
                Input::make('webhook.symbol')->title('Символ'),
                Input::make('webhook.action')->title('Действие'),
                Input::make('webhook.strategy')->title('Стратегия'),
                Input::make('webhook.price')->title('Цена'),
                Input::make('webhook.timeframe')->title('Таймфрейм'),
                Input::make('webhook.exchange')->title('Биржа'),
                Input::make('webhook.source_ip')->title('IP адрес'),
                Input::make('webhook.created_at')->title('Дата получения'),
            ]))
                ->async('asyncGetWebhookDetails')
                ->title('Полные данные вебхука')
                ->applyButton('Закрыть'),
        ];
    }

    /**
     * Применить фильтры
     */
    public function applyFilter(Request $request)
    {
        // Просто перенаправляем с параметрами фильтра
        return redirect()->route('platform.tradingview.webhooks', $request->get('filter', []));
    }

    /**
     * Сбросить фильтры
     */
    public function resetFilter()
    {
        return redirect()->route('platform.tradingview.webhooks');
    }

    /**
     * Обновить страницу
     */
    public function refresh()
    {
        return redirect()->route('platform.tradingview.webhooks');
    }

    /**
     * Очистить старые вебхуки
     */
    public function clearOld()
    {
        $deleted = TradingViewWebhook::where('created_at', '<', now()->subDays(30))->delete();

        return redirect()->route('platform.tradingview.webhooks')
            ->with('success', "Удалено {$deleted} старых вебхуков");
    }

    /**
     * Получить детали вебхука для модального окна
     */
    public function asyncGetWebhookDetails(TradingViewWebhook $webhook): array
    {
        return [
            'webhook' => [
                'id' => $webhook->id,
                'symbol' => $webhook->symbol,
                'action' => $webhook->action,
                'strategy' => $webhook->strategy,
                'price' => $webhook->price,
                'timeframe' => $webhook->timeframe,
                'exchange' => $webhook->exchange,
                'source_ip' => $webhook->source_ip,
                'created_at' => $webhook->created_at->format('d.m.Y H:i:s'),
                'raw_data' => json_encode($webhook->raw_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]
        ];
    }
}
