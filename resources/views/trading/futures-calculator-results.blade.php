<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.search.includes('trade_id')) {
            let url = new URL(window.location.href);
            url.searchParams.delete('trade_id');
            window.history.replaceState({}, '', url);
        }
    });
</script>

@if(!empty($result))
    <div class="bg-white rounded shadow-sm p-4 mb-3">
        <div class="row">
            <div class="col-md-12 mb-3">
                <h4 class="text-center">
                    Результаты расчета
                    <span class="badge {{ $result['position_type'] === 'long' ? 'bg-success' : 'bg-danger' }}">
                        {{ $result['position_type'] === 'long' ? 'ЛОНГ' : 'ШОРТ' }}
                    </span>
                </h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card border-primary mb-3">
                    <div class="card-header">Основные параметры</div>
                    <div class="card-body">
                        <p><strong>Средняя цена входа:</strong> {{ $result['average_price'] }}</p>
                        <p><strong>Размер позиции:</strong> {{ $result['total_contracts'] }}</p>
                        <p><strong>Кредитное плечо:</strong> {{ $result['leverage'] }}x</p>
                        <p><strong>Маржа:</strong> {{ $result['margin'] }}</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-warning mb-3">
                    <div class="card-header">Уровни риска</div>
                    <div class="card-body">
                        <p><strong>Стоп-лосс:</strong>
                            <span class="text-danger">{{ $result['stop_loss_price'] }}</span>
                        </p>
                        <p><strong>Потенциальный убыток:</strong>
                            <span class="text-danger">{{ $result['potential_loss'] }}</span>
                        </p>
                        <p><strong>Цена ликвидации:</strong>
                            <span class="text-danger fw-bold">{{ $result['liquidation_price'] }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-success mb-3">
                    <div class="card-header">Целевые уровни</div>
                    <div class="card-body">
                        <p><strong>Тейк-профит (по %):</strong>
                            <span class="text-success">{{ $result['take_profit_price'] }}</span>
                        </p>
                        <p><strong>Потенциальная прибыль:</strong>
                            <span class="text-success">{{ $result['potential_profit'] }}$</span>
                        </p>
                        @if($result['target_profit_amount'] > 0)
                            <hr>
                            <p><strong>Целевая прибыль:</strong>
                                <span class="text-success">{{ $result['target_profit_amount'] }}$</span>
                            </p>
                            <p><strong>Необходимая цена:</strong>
                                <span class="text-success">{{ $result['target_profit_price'] }}</span>
                                <small class="text-muted">({{ $result['target_profit_percent'] }}%)</small>
                            </p>
                        @endif
                        <p><strong>Соотношение R/R:</strong>
                            {{ round($result['potential_profit'] / $result['potential_loss'], 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Все расчеты приблизительные. Реальные значения могут отличаться в зависимости от условий рынка и комиссий биржи.
                </div>
            </div>
        </div>
    </div>
@endif

@if(!empty($result['technical_analysis']))
    <div class="bg-white rounded shadow-sm p-4 mb-3">
        <div class="row">
            <div class="col-md-12 mb-3">
                <h4 class="text-center">Технический анализ
                    <span class="badge bg-primary">{{ $result['currency'] }}</span>
                </h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card border-info mb-3">
                    <div class="card-header">
                        <i class="fas fa-signal"></i> Рекомендация
                    </div>
                    <div class="card-body">
                        @php
                            $recommendationClass = match($result['technical_analysis']['recommendation']['action']) {
                                'buy' => 'success',
                                'sell' => 'danger',
                                default => 'warning'
                            };
                        @endphp
                        <div class="text-center mb-3">
                            <span class="badge bg-{{ $recommendationClass }} p-2">
                                {{ match($result['technical_analysis']['recommendation']['action']) {
                                    'buy' => 'ПОКУПКА',
                                    'sell' => 'ПРОДАЖА',
                                    default => 'НЕЙТРАЛЬНО'
                                } }}
                            </span>
                        </div>
                        <p class="mb-2"><strong>Уверенность:</strong>
                            <div class="progress">
                                <div class="progress-bar bg-{{ $recommendationClass }}"
                                     role="progressbar"
                                     style="width: {{ $result['technical_analysis']['recommendation']['confidence'] * 50 }}%"
                                     aria-valuenow="{{ $result['technical_analysis']['recommendation']['confidence'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="2">
                                    {{ number_format($result['technical_analysis']['recommendation']['confidence'], 2) }}
                                </div>
                            </div>
                        </p>
                        <p class="text-muted small">
                            {{ $result['technical_analysis']['recommendation']['description'] }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-primary mb-3">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Индикаторы
                    </div>
                    <div class="card-body">
                        @if(!empty($result['technical_analysis']['signals']['bollinger']))
                            <div class="mb-2">
                                <strong>Полосы Боллинджера:</strong>
                                <span class="badge bg-{{ $result['technical_analysis']['signals']['bollinger']['data']['band'] === 'upper' ? 'danger' : 'success' }}">
                                    {{ $result['technical_analysis']['signals']['bollinger']['data']['band'] === 'upper' ? 'Верхняя граница' : 'Нижняя граница' }}
                                </span>
                                <div class="small text-muted">
                                    Отклонение: {{ number_format($result['technical_analysis']['signals']['bollinger']['data']['deviation'], 2) }}%
                                </div>
                            </div>
                        @endif

                        @if(!empty($result['technical_analysis']['signals']['volume']))
                            <div class="mb-2">
                                <strong>Объемный уровень:</strong>
                                <div class="small">
                                    Цена: {{ number_format($result['technical_analysis']['signals']['volume']['data']['level_price'], 2) }}
                                    <br>
                                    Сила: {{ number_format($result['technical_analysis']['signals']['volume']['data']['strength'], 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-secondary mb-3">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i> Дополнительно
                    </div>
                    <div class="card-body">
                        <p><strong>Текущая цена:</strong>
                            {{ number_format($result['technical_analysis']['current_price'], 2) }}
                        </p>
                        <p><strong>Общая сила сигналов:</strong>
                            <div class="progress">
                                <div class="progress-bar bg-info"
                                     role="progressbar"
                                     style="width: {{ $result['technical_analysis']['total_strength'] * 33 }}%"
                                     aria-valuenow="{{ $result['technical_analysis']['total_strength'] }}"
                                     aria-valuemin="0"
                                     aria-valuemax="3">
                                    {{ number_format($result['technical_analysis']['total_strength'], 2) }}
                                </div>
                            </div>
                        </p>
                        <p class="small text-muted">
                            Последнее обновление: {{ \Carbon\Carbon::createFromTimestamp($result['technical_analysis']['timestamp'])->format('H:i:s') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Технический анализ носит рекомендательный характер. Всегда проводите собственный анализ перед принятием торговых решений.
                </div>
            </div>
        </div>
    </div>
@endif
