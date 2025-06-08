@php
    /**
    * @var \App\Models\TradeOrder $order
    * @var \App\Models\Trade $trade
     */
@endphp
<div class="bg-white rounded shadow-sm p-4">
    <div class="row">
        {{-- Основные метрики --}}
        <div class="col-md-4">
            <div class="card border-primary mb-3">
                <div class="card-header">Основные параметры</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Тип позиции:</span>
                        <span class="badge {{ $trade->isTypeLong() ? 'bg-success' : 'bg-danger' }}">
                            {{ $trade->isTypeLong() ? 'ЛОНГ' : 'ШОРТ' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Размер позиции:</span>
                        <strong>{{ number_format($trade->position_size, 2) }} USDT</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Плечо:</span>
                        <strong>{{ $trade->leverage }}x</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Статус:</span>
                        <span class="badge
                            @switch($trade->status)
                                @case('open') bg-success @break
                                @case('closed') bg-secondary @break
                                @case('liquidated') bg-danger @break
                            @endswitch
                        ">
                            {{ ucfirst($trade->status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Цены и уровни --}}
        <div class="col-md-4">
            <div class="card border-warning mb-3">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Цены и уровни
                </div>
                <div class="card-body">
                    @php
                        // Расчет средней цены позиции
                        $totalSize = $trade->orders->where('type', '!=', 'exit')->sum('size');
//                        $weightedSum = $trade->orders->where('type', '!=', 'exit')
//                            ->reduce(function ($carry, $order) {
//                                return $carry + ($order->price * $order->size);
//                            }, 0);
//                        $averagePrice = $totalSize > 0 ? $weightedSum / $totalSize : $trade->entry_price;
//
                        $averagePrice = $trade->getAverageEntryPrice();
                        $slDistance = $tpDistance = 0;

                        if($averagePrice){
                            // Расчет расстояния до уровней в процентах
                            $slDistance = abs(($trade->stop_loss_price - $averagePrice) / $averagePrice * 100);
                            $tpDistance = abs(($trade->take_profit_price - $averagePrice) / $averagePrice * 100);
                        }
                    @endphp

                    <div class="d-flex justify-content-between mb-2">
                        <span>Начальная цена входа:</span>
                        <strong>{{ number_format($trade->entry_price, 8) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Средняя цена позиции:</span>
                        <strong class="text-primary">{{ number_format($averagePrice, 8) }}</strong>
                    </div>

                    <div class="border-top my-2"></div>

                    @if($trade->stop_loss_price > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Стоп-лосс:</span>
                            <div class="text-end">
                                <span class="text-danger">{{ number_format($trade->stop_loss_price, 8) }}</span>
                                <br>
                                <small class="text-muted">{{ number_format($slDistance, 2) }}% от средней</small>
                            </div>
                        </div>
                    @endif

                    @if($trade->take_profit_price > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Тейк-профит:</span>
                            <div class="text-end">
                                <span class="text-success">{{ number_format($trade->take_profit_price, 8) }}</span>
                                <br>
                                <small class="text-muted">{{ number_format($tpDistance, 2) }}% от средней</small>
                            </div>
                        </div>
                    @endif

                    @if($trade->exit_price)
                        <div class="border-top my-2"></div>
                        <div class="d-flex justify-content-between">
                            <span>Цена выхода:</span>
                            <strong class="{{ $trade->realized_pnl > 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($trade->exit_price, 8) }}
                            </strong>
                        </div>
                    @endif

                    @if($trade->isStatusOpen())
                        <div class="border-top my-2"></div>
                        <div class="small text-muted">
                            <i class="fas fa-info-circle"></i>
                            Средняя цена учитывает все входы в позицию
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mb-2">
                        <span>Цена ликвидации:</span>
                        <div class="text-end">
                            <span class="text-danger">{{ number_format($trade->getLiquidationPrice(), 8) }}</span>
                            @if($trade->currency->last_price)
                                <br>
                                <small class="text-muted">
                                    До
                                    ликвидации: {{ number_format($trade->getDistanceToLiquidation($trade->currency->last_price), 2) }}
                                    %
                                </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- P&L и результаты --}}
        <div class="col-md-4">
            <div class="card {{ $trade->isStatusOpen() ? 'border-info' : 'border-success' }} mb-3">
                <div class="card-header">{{ $trade->isStatusOpen() ? 'Текущий P&L' : 'Результат' }}</div>
                <div class="card-body">
                    @if($trade->isStatusOpen())
                        @php
                            // Здесь можно добавить расчет текущего P&L
                            $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
//                            $unrealizedPnl = $trade->position_type === 'long'
//                                ? ($currentPrice - $trade->entry_price) * $trade->position_size * $trade->leverage / $trade->entry_price
//                                : ($trade->entry_price - $currentPrice) * $trade->position_size * $trade->leverage / $trade->entry_price;
                            $unrealizedPnl = $trade->isTypeLong()
                                ? ($currentPrice - $averagePrice) * $trade->position_size * $trade->leverage / $averagePrice
                                : ($averagePrice - $currentPrice) * $trade->position_size * $trade->leverage / $averagePrice;
                        @endphp
                        <div class="d-flex justify-content-between mb-2">
                            <span>Текущая цена:</span>
                            <strong>{{ number_format($currentPrice, 8) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Нереализованный P&L:</span>
                            <span class="{{ $unrealizedPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($unrealizedPnl, 2) }} USDT
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>ROE:</span>
                            <span class="{{ $unrealizedPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($unrealizedPnl / $trade->position_size * 100, 2) }}%
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Длительность:</span>
                            <span>{{ $trade->getDurationTime() }}</span>
                        </div>

                        <hr>

                        @if($trade->target_profit_amount)
                            <div class="d-flex justify-content-between">
                                <span>Целевая прибыль:</span>
                                <span>{{ $trade->target_profit_amount }}$</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Необходимая цена:</span>
                                <span class="text-success">{{ $trade->target_profit_price_format }}
                                <small class="text-muted">({{ $trade->target_profit_percent }}%)</small>
                                </span>

                            </div>
                        @endif
                    @endif

                    @if(!$trade->isStatusOpen())
                        <div class="d-flex justify-content-between mb-2">
                            <span>Реализованный P&L:</span>
                            <span class="{{ $trade->realized_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($trade->realized_pnl, 2) }} USDT
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>ROE:</span>
                            <span class="{{ $trade->realized_pnl >= 0 ? 'text-success' : 'text-danger' }}">
                                @if($trade->position_size)
                                    {{ number_format($trade->realized_pnl / $trade->position_size * 100, 2) }}%
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Длительность:</span>
                            <span>{{ $trade->getDurationTime() }}</span>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            @php
                                $unPnl = $trade->getUnrealizedPnL($trade->currency->last_price);
                            @endphp
                            <span title="P&L по текущей цене">FOMO P&L {{ $unPnl >= 0 ? '😄 🥳 👍' : '💩 😭' }}</span>
                            <span class="{{ $unPnl >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($unPnl, 2) }} USDT
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- График или дополнительная информация --}}
    <div class="row mt-3">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                @if($trade->isStatusOpen())
                    Сделка активна. P&L обновляется в реальном времени.
                @elseif($trade->closed_at)
                    Сделка закрыта {{ $trade->closed_at->format('d.m.Y H:i:s') }}
                @endif
            </div>
        </div>
    </div>

    @php
        $riskPercent = 0;
        if($trade->exists) {
            // Расчет риска в USDT (от входа до стоп-лосса)
            $riskAmount = $trade->position_type === 'long'
                ? ($trade->entry_price - $trade->stop_loss_price) * $trade->position_size * $trade->leverage / $trade->entry_price
                : ($trade->stop_loss_price - $trade->entry_price) * $trade->position_size * $trade->leverage / $trade->entry_price;

            // Риск в процентах от депозита
            $riskPercent = abs($riskAmount / $trade->position_size * 100);

            // Расстояние до стопа в процентах
            $stopDistance = abs($trade->entry_price - $trade->stop_loss_price) / $trade->entry_price * 100;
        }
    @endphp

    @if($riskPercent)

        <div class="row mt-3">
            <div class="col-12">
                <div class="alert {{ $riskPercent > 2 ? 'alert-danger' : 'alert-success' }}">
                    <i class="fas fa-shield-alt"></i>
                    @if($riskPercent > 2)
                        <strong>Внимание!</strong> Риск на сделку превышает рекомендуемые 2% от депозита.
                    @else
                        Риск на сделку находится в пределах рекомендуемых значений (до 2% от депозита).
                    @endif
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h5 class="mb-3">Метрики риска</h5>
            </div>

            <div class="col-md-4">
                <div class="card border-danger mb-3">
                    <div class="card-header">Риск на сделку</div>
                    <div class="card-body">


                        <div class="d-flex justify-content-between mb-2">
                            <span>Риск (USDT):</span>
                            <strong class="text-danger">{{ number_format(abs($riskAmount), 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Риск от депозита:</span>
                            <strong class="text-danger">{{ number_format($riskPercent, 2) }}%</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Расстояние до стопа:</span>
                            <strong>{{ number_format($stopDistance, 2) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-success mb-3">
                    <div class="card-header">Соотношение риск/прибыль</div>
                    <div class="card-body">
                        @php
                            // Потенциальная прибыль (от входа до тейк-профита)
                            $potentialProfit = $trade->position_type === 'long'
                                ? ($trade->take_profit_price - $trade->entry_price) * $trade->position_size * $trade->leverage / $trade->entry_price
                                : ($trade->entry_price - $trade->take_profit_price) * $trade->position_size * $trade->leverage / $trade->entry_price;

                            // Соотношение риск/прибыль (RR ratio)
                            $rrRatio = abs($potentialProfit / $riskAmount);

                            // Ожидаемая прибыль в процентах
                            $profitPercent = abs($potentialProfit / $trade->position_size * 100);
                        @endphp

                        <div class="d-flex justify-content-between mb-2">
                            <span>Risk/Reward:</span>
                            <strong>1 : {{ number_format($rrRatio, 2) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Потенциальная прибыль:</span>
                            <strong class="text-success">{{ number_format($potentialProfit, 2) }} USDT</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Прибыль от депозита:</span>
                            <strong class="text-success">{{ number_format($profitPercent, 2) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-warning mb-3">
                    <div class="card-header">Дополнительные метрики</div>
                    <div class="card-body">
                        @php
                            // Эффективное плечо (с учетом расстояния до стопа)
                            $effectiveLeverage = $trade->leverage * (1 / ($stopDistance / 100));

                            // Максимальная просадка
                            $maxDrawdown = $trade->position_type === 'long'
                                ? ($trade->low_price ?? $trade->entry_price) - $trade->entry_price
                                : $trade->entry_price - ($trade->high_price ?? $trade->entry_price);
                            $maxDrawdownPercent = abs($maxDrawdown / $trade->entry_price * 100);
                        @endphp

                        <div class="d-flex justify-content-between mb-2">
                            <span>Эффективное плечо:</span>
                            <strong>{{ number_format($effectiveLeverage, 2) }}x</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Макс. просадка:</span>
                            <strong class="text-danger">{{ number_format($maxDrawdownPercent, 2) }}%</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Вероятность успеха:</span>
                            <strong>{{ number_format(100 * ($rrRatio / ($rrRatio + 1)), 1) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif


</div>
