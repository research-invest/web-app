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