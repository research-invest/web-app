<div class="bg-white rounded shadow-sm p-4">
    <div class="row">
        {{-- Основные метрики --}}
        <div class="col-md-4">
            <div class="card border-primary mb-3">
                <div class="card-header">Основные параметры</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Тип позиции:</span>
                        <span class="badge {{ $trade->position_type === 'long' ? 'bg-success' : 'bg-danger' }}">
                            {{ $trade->position_type === 'long' ? 'ЛОНГ' : 'ШОРТ' }}
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
                <div class="card-header">Цены и уровни</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Цена входа:</span>
                        <strong>{{ number_format($trade->entry_price, 8) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Стоп-лосс:</span>
                        <span class="text-danger">{{ number_format($trade->stop_loss_price, 8) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Тейк-профит:</span>
                        <span class="text-success">{{ number_format($trade->take_profit_price, 8) }}</span>
                    </div>
                    @if($trade->exit_price)
                    <div class="d-flex justify-content-between">
                        <span>Цена выхода:</span>
                        <strong>{{ number_format($trade->exit_price, 8) }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- P&L и результаты --}}
        <div class="col-md-4">
            <div class="card {{ $trade->status === 'open' ? 'border-info' : 'border-success' }} mb-3">
                <div class="card-header">{{ $trade->status === 'open' ? 'Текущий P&L' : 'Результат' }}</div>
                <div class="card-body">
                    @if($trade->status === 'open')
                        @php
                            // Здесь можно добавить расчет текущего P&L
                            $currentPrice = $trade->currency->last_price ?? $trade->entry_price;
                            $unrealizedPnl = $trade->position_type === 'long'
                                ? ($currentPrice - $trade->entry_price) * $trade->position_size * $trade->leverage / $trade->entry_price
                                : ($trade->entry_price - $currentPrice) * $trade->position_size * $trade->leverage / $trade->entry_price;
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
                    @else
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
                        <div class="d-flex justify-content-between">
                            <span>Длительность:</span>
                            @if($trade->created_at)
                                <span>{{ $trade->closed_at->diffForHumans($trade->created_at, true) }}</span>
                            @endif

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
                @if($trade->status === 'open')
                    Сделка активна. P&L обновляется в реальном времени.
                @elseif($trade->closed_at)
                    Сделка закрыта {{ $trade->closed_at->format('d.m.Y H:i:s') }}
                @endif
            </div>
        </div>
    </div>
</div>
