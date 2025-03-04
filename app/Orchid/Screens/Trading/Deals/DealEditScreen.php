<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Trading\Deals;


use App\Helpers\UserHelper;
use App\Models\Currency;
use App\Models\Strategy;
use App\Models\Trade;
use App\Models\TradeOrder;
use App\Models\TradePeriod;
use App\Orchid\Layouts\Charts\HighchartsChart;
use App\Services\PnlAnalyticsService;
use App\Services\RiskManagement\PositionCalculator;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\RadioButtons;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;
use Orchid\Screen\Fields\Upload;
use App\Models\CheckListItem;
use App\Models\TradeCheckListItem;

class DealEditScreen extends Screen
{

    /**
     * @var Trade
     */
    public $trade;

    public function name(): ?string
    {
        if ($this->trade->exists) {
            return sprintf('Сделка: %s [%s]', $this->trade->currency->name, $this->trade->position_size);
        }

        return 'Новая сделка';
    }

    public function query(Trade $trade): iterable
    {
        $this->trade = $trade;

        // Загружаем существующие чек-листы для сделки
        $checklist = [];
        if ($trade->exists) {
            $trade->load('checkListItems.checkListItem');
            foreach ($trade->checkListItems as $item) {
                $checklist[$item->check_list_item_id] = [
                    'is_completed' => $item->is_completed,
                    'notes' => $item->notes,
                ];
            }
        }

        return [
            'trade' => $trade,
            'checklist' => $checklist,
        ];
    }

    public function commandBar(): iterable
    {
        return [

            Button::make('Сохранить')
                ->icon('save')
                ->method('save')
                ->class('btn btn-default'),

            Link::make('Калькулятор')
                ->icon('calculator')
                ->target('_blank')
                ->canSee($this->trade->exists)
                ->route('platform.trading.futures-calculator', ['trade_id' => $this->trade->id])
                ->class('btn btn-info'),

            Link::make('Валюта')
                ->icon('bitcoin')
                ->target('_blank')
                ->canSee($this->trade->exists)
                ->route('platform.currencies.edit', ['currency' => $this->trade->currency_id ?? '-'])
                ->class('btn btn-default'),

            ModalToggle::make('Добавить ордер')
                ->modal('addOrderModal')
                ->method('addOrder')
                ->icon('plus')
                ->class('btn btn-primary')
                ->canSee($this->trade->exists && $this->trade->status === 'open'),


            Button::make('Закрыть сделку')
                ->icon('check')
                ->method('closeTrade')
                ->class('btn btn-warning')
                ->canSee($this->trade->exists && $this->trade->status === 'open'),

            Link::make('TV')
                ->icon('grid')
                ->target('_blank')
                ->canSee($this->trade->exists)
                ->href($this->trade->currency->getTVLink())
                ->class('btn btn-default'),
        ];
    }

    public function layout(): iterable
    {
        return [
            // Модальное окно для добавления ордера
            Layout::modal('addOrderModal', [
                Layout::rows([
                    Input::make('price')
                        ->title('Цена')
                        ->type('number')
                        ->step('0.00000001')
                        ->required(),

                    Input::make('size')
                        ->title('Размер (USDT)')
                        ->type('number')
                        ->required(),

                    Select::make('type')
                        ->title('Тип ордера')
                        ->options([
                            TradeOrder::TYPE_ADD => 'Дополнительный вход',
                            TradeOrder::TYPE_EXIT => 'Частичное закрытие',
                        ])
                        ->required(),

                    TextArea::make('notes')
                        ->title('Комментарий')
                        ->rows(3),
                ])
            ])->title('Добавить ордер'),

            Layout::tabs([
                'Основная информация' => [
                    Layout::rows([
//                        Select::make('trade.currency_id')
//                            ->fromModel(Currency::class, 'code', 'id')
//                            ->title('Валюта')
//                            ->required(),

                        Relation::make('trade.currency_id')
                            ->fromModel(Currency::class, 'code', 'id')
                            ->applyScope('spot')
                            ->displayAppend('namePrice')
                            ->required()
                            ->title('Выберите валюту'),

                        RadioButtons::make('trade.position_type')
                            ->title('Тип позиции')
                            ->options([
                                Trade::POSITION_TYPE_LONG => 'Лонг',
                                Trade::POSITION_TYPE_SHORT => 'Шорт'
                            ])
                            ->required(),

                        Group::make([
                            CheckBox::make('trade.is_fake')
                                ->placeholder('Фейковая сделка')
                                ->help('Не учитывается в статистике, можно проверять гипотезы')
                                ->canSee(!$this->trade->exists)
                                ->sendTrueOrFalse()
                                ->value(0),

                            CheckBox::make('trade.is_spot')
                                ->placeholder('Спотовая cделка')
                                ->canSee(!$this->trade->exists)
                                ->sendTrueOrFalse()
                                ->value(0),

                            CheckBox::make('trade.is_notify')
                                ->placeholder('Уведомления PNL')
                                ->sendTrueOrFalse()
                                ->value(1),
                        ]),

                        Group::make([
                            Input::make('trade.entry_price')
                                ->title('Цена входа')
                                ->type('number')
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.position_size')
                                ->title('Размер позиции (USDT)')
                                ->value($this->trade->exists ? $this->trade->position_size : 400)
                                ->type('number')
                                ->step('0.1')
                                ->required(),

                            Input::make('trade.leverage')
                                ->title('Плечо')
                                ->type('number')
                                ->value($this->trade->exists ? $this->trade->leverage : 5)
                                ->min(1)
                                ->max(200)
                                ->required(),
                        ]),

                        Group::make([
                            Input::make('trade.stop_loss_price')
                                ->title('Стоп-лосс')
                                ->type('number')
                                ->value($this->trade->exists ? $this->trade->stop_loss_price : 1)
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.take_profit_price')
                                ->title('Тейк-профит')
                                ->type('number')
                                ->value($this->trade->exists ? $this->trade->take_profit_price : 10)
                                ->step('0.00000001')
                                ->required(),

                            Input::make('trade.target_profit_amount')
                                ->value($this->trade->exists ? $this->trade->target_profit_amount : 100)
                                ->title('Целевая прибыль ($)')
                                ->type('number'),
                        ]),

                        Group::make([
                            Select::make('trade.status')
                                ->title('Статус')
                                ->options(Trade::getStatuses())
                                ->required(),

                            Relation::make('trade.strategy_id')
                                ->fromModel(Strategy::class, 'name', 'id')
                                ->applyScope('byCreator', UserHelper::getId())
                                ->title('Стратегия'),
                        ]),

                        Group::make([
                            Input::make('trade.commission_open')
                                ->title('Комиссия за открытие сделки')
                                ->canSee($this->trade->exists)
                                ->type('number'),

                            Input::make('trade.commission_close')
                                ->title('Комиссия за закрытие сделки')
                                ->canSee($this->trade->exists)
                                ->type('number'),

                            Input::make('trade.commission_finance')
                                ->title('Комиссия за финансирование')
                                ->canSee($this->trade->exists)
                                ->type('number'),
                        ]),

                        Input::make('trade.realized_pnl')
                            ->title('Реализованный PnL')
                            ->canSee($this->trade->exists && $this->trade->realized_pnl),

                        TextArea::make('trade.notes')
                            ->title('Заметки')
                            ->rows(3),
                    ])
                ],

                'Чек-лист' => [
                    Layout::view('trading.header_check_list', [
                        'title' => 'Проверка перед открытием сделки',
                        'description' => 'Убедитесь, что все пункты проверены перед открытием позиции'
                    ]),

                    // Динамически формируем чек-лист
                    Layout::rows(
                        $this->getCheckListFields(),
                    ),
                ],

                'Ордера' => [
                    Layout::view('trading.trade-orders', ['trade' => $this->trade])
                ],

                'Статистика' => [
                    Layout::view('trading.trade-stats', ['trade' => $this->trade])
                ],

                'P&L' => [
                    new HighchartsChart(
                        $this->getRiskManagementChart()
                    ),

                    new HighchartsChart(
                        $this->getPnlHistoryChart()
                    ),

                    new HighchartsChart(
                        $this->getPnlHistoryVolumeChart()
                    ),

//                    new HighchartsChart(
//                        $this->getPnlHistoryFundingRateChart()
//                    ),

                    Layout::view('trading.trade-potential-pnl', [
                        'trade' => $this->trade,
                        'steps' => $this->calculatePnLSteps($this->trade)
                    ]),
                ],

                'Изображения' => [
                    Layout::rows([
                        Upload::make('trade.attachment')
                            ->title('Добавить изображение')
                            ->acceptedFiles('image/*')
                            ->maxFileSize(10)
                            ->multiple()
                            ->help('Загрузите скриншот графика или другое изображение'),
                    ]),

                    Layout::view('trading.trade-images', [
                        'trade' => $this->trade
                    ]),
                ],
            ])
        ];
    }

    private function getRiskManagementChart(): array
    {
        if (!$this->trade->exists) {
            return [];
        }

        $calculator = new PositionCalculator(
            trade: $this->trade,
        );

        return $calculator->getChartConfig();
    }

    private function getPnlHistoryChart(): array
    {
        if (!$this->trade->exists) {
            return [];
        }

        return (new PnlAnalyticsService())->getPnlHistoryChart(trade: $this->trade);
    }

    private function getPnlHistoryVolumeChart(): array
    {
        if (!$this->trade->exists) {
            return [];
        }

        return (new PnlAnalyticsService())->getPnlHistoryVolumeChart(trade: $this->trade);
    }

    private function getPnlHistoryFundingRateChart(): array
    {
        if (!$this->trade->exists) {
            return [];
        }

        return (new PnlAnalyticsService())->getPnlHistoryFundingRateChart(trade: $this->trade);
    }

    public function save(Trade $trade, Request $request)
    {
        $data = $request->get('trade');

        // Если это новая сделка
        if (!$trade->exists) {

            $currentPeriod = TradePeriod::isActive()
                ->latest()
                ->byCreator()
                ->first();

            $currency = Currency::where('id', $data['currency_id'])->firstOrFail();

            $trade
                ->fill($data)
                ->fill([
                    'user_id' => UserHelper::getId(),
                    'trade_period_id' => $currentPeriod?->id,
                    'open_currency_volume' => $currency->volume,
                ]);

            $trade->save();

            // Создаем первый ордер при создании сделки
            $trade->orders()->create([
                'price' => $trade->entry_price,
                'size' => $trade->position_size,
                'type' => TradeOrder::TYPE_ENTRY,
                'executed_at' => now()
            ]);

            Toast::success('Сделка создана');
        } else {
            if ($request->has('trade.attachment')) {
                $trade->attachments()->syncWithoutDetaching(
                    $request->input('trade.attachment', [])
                );
            }

            $trade->fill($data)->save();
            Toast::success('Сделка обновлена');
        }

        // Сохраняем чек-лист
        $checklistData = $request->input('checklist', []);

        foreach ($checklistData as $checkListItemId => $data) {
            $item = TradeCheckListItem::updateOrCreate(
                [
                    'trade_id' => $trade->id,
                    'check_list_item_id' => $checkListItemId,
                ],
                [
                    'is_completed' => $data['is_completed'] ?? false,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            // Если есть прикрепленные файлы
            if (isset($data['attachment']) && !empty($data['attachment'])) {
                $trade->attachments()->syncWithoutDetaching(
                    $data['attachment']
                );

                $item->attachments()->syncWithoutDetaching(
                    $data['attachment']
                );
            }
        }

        Toast::success('Сделка сохранена');

        return redirect()->route('platform.trading.deal.edit', $trade->id);
    }

    public function closeTrade(Trade $trade, Request $request)
    {
        return redirect()->route('platform.trading.deal.close', $trade->id);
    }


    /**
     * Добавление нового ордера
     */
    public function addOrder(Trade $trade, Request $request)
    {
        $order = $trade->orders()->create([
            'price' => $request->input('price'),
            'size' => $request->input('size'),
            'type' => $request->input('type'),
            'executed_at' => now(),
        ]);

        // Обновляем общий размер позиции
        if ($request->input('type') === 'add') {
            $trade->position_size += $request->input('size');
        } elseif ($request->input('type') === 'exit') {
            $trade->position_size -= $request->input('size');
        }

        // Если позиция полностью закрыта
        if ($trade->position_size <= 0) {
            $trade->status = 'closed';
            $trade->closed_at = now();

            // Расчет P&L для закрытой части
            $pnl = $this->calculatePartialPnl(
                $trade->position_type,
                $trade->entry_price,
                $request->input('price'),
                $request->input('size'),
                $trade->leverage
            );

            $trade->realized_pnl = ($trade->realized_pnl ?? 0) + $pnl;
        }

        $trade->save();

        Toast::success('Ордер добавлен');
    }

    /**
     * Расчет P&L для частичного закрытия
     */
    private function calculatePartialPnl(
        string $positionType,
        float  $entryPrice,
        float  $exitPrice,
        float  $size,
        int    $leverage
    ): float
    {
        if ($positionType === 'long') {
            return ($exitPrice - $entryPrice) * $size * $leverage / $entryPrice;
        }
        return ($entryPrice - $exitPrice) * $size * $leverage / $entryPrice;
    }

    /**
     * Расчет шагов для таблицы потенциального P&L
     */
    private function calculatePnLSteps(Trade $trade): array
    {
        if (!$trade->exists) {
            return [];
        }

        // Получаем среднюю цену входа
        $averagePrice = $trade->getAverageEntryPrice();

        // Определяем диапазон цен (±50% от средней цены)
        $maxPrice = $averagePrice * 1.5;
        $minPrice = $averagePrice * 0.5;

        // Рассчитываем оптимальное количество шагов
        $steps = 50;
        $stepSize = ($maxPrice - $minPrice) / $steps;

        $results = [];

        // Генерируем строки для таблицы
        for ($i = 0; $i <= $steps; $i++) {
            $price = $trade->isTypeLong() ?
                $minPrice + ($stepSize * $i) :
                $maxPrice - ($stepSize * $i);

            // Расчет P&L для текущей цены
            $pnl = $trade->isTypeLong()
                ? ($price - $averagePrice) * $trade->position_size * $trade->leverage / $averagePrice
                : ($averagePrice - $price) * $trade->position_size * $trade->leverage / $averagePrice;

            // Расчет ROE (Return on Equity)
            $roe = ($pnl / $trade->position_size) * 100;

            $results[] = [
                'price' => $price,
                'pnl' => $pnl,
                'roe' => $roe,
                'price_change' => (($price - $averagePrice) / $averagePrice) * 100,
                'is_current' => false, // будет обновляться в шаблоне
                'is_tp' => abs($price - $trade->take_profit_price) < $stepSize,
                'is_sl' => abs($price - $trade->stop_loss_price) < $stepSize,
            ];
        }

        return $results;
    }

    private function getCheckListFields(): array
    {
        $fields = [];

        $checkListItems = CheckListItem::orderBy('sort_order')
            ->when($this->trade->strategy_id, function ($query) {
                $query->where(function ($q) {
                    $q->where('trade_strategy_id', $this->trade->strategy_id)
                        ->orWhereNull('trade_strategy_id');
                });
            })
            ->get();

        foreach ($checkListItems as $item) {
            // Получаем существующий чек-лист айтем
            $checkListItem = $this->trade->checkListItems
                ->where('check_list_item_id', $item->id)
                ->first();

            // Получаем прикрепленные файлы для этого пункта
            $attachments = [];
            if ($checkListItem) {
                $attachments = $checkListItem->attachments()->get();
            }

            // Добавляем группу с полями
            $fields[] = Group::make([
                CheckBox::make("checklist.{$item->id}.is_completed")
                    ->placeholder($item->title)
                    ->sendTrueOrFalse()
                    ->value($checkListItem?->is_completed ?? false)
                    ->help($item->description),

                TextArea::make("checklist.{$item->id}.notes")
                    ->title('Заметки')
                    ->value($checkListItem?->notes)
                    ->rows(2)
                    ->canSee($checkListItem?->is_completed ?? false),

                Upload::make("checklist.{$item->id}.attachment")
                    ->title('Скриншот')
                    ->maxFiles(5)
                    ->value($attachments)
                    ->targetId()
                    ->groups('trades')
                    ->canSee($checkListItem?->is_completed ?? false),

//                Layout::rows([
//                    Layout::view('trading.check_list_attachments', [
//                        'attachments' => $attachments
//                    ])
//                ]),

            ]);
        }

        return $fields;
    }
}
