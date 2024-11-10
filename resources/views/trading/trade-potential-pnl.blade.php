@php
    /**
     * @var \App\Models\Trade $trade
     */
@endphp
<div class="bg-white rounded shadow-sm p-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Таблица показывает потенциальный P&L при различных ценах выхода из позиции.
                Средняя цена входа: <strong>{{ number_format($trade->getAverageEntryPrice(), 8) }}</strong>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Цена</th>
                    <th>Изменение цены</th>
                    <th>P&L (USDT)</th>
                    <th>ROE (%)</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                @foreach($steps as $step)
                    <tr class="
                        @if($step['is_tp']) table-success @endif
                        @if($step['is_sl']) table-danger @endif
                        @if($step['is_current']) table-primary @endif
                    ">
                        <td>
                            <strong>{{ number_format($step['price'], 8) }}</strong>
                        </td>
                        <td>
                            <span class="{{ $step['price_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($step['price_change'], 2) }}%
                            </span>
                        </td>
                        <td>
                            <span class="{{ $step['pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                <strong>{{ number_format($step['pnl'], 2) }}</strong>
                            </span>
                        </td>
                        <td>
                            <span class="{{ $step['roe'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($step['roe'], 2) }}%
                            </span>
                        </td>
                        <td>
                            @if($step['is_tp'])
                                <span class="badge bg-success">Take Profit</span>
                            @elseif($step['is_sl'])
                                <span class="badge bg-danger">Stop Loss</span>
                            @elseif($step['is_current'])
                                <span class="badge bg-primary">Текущая цена</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card border-info">
                <div class="card-header">Ключевые уровни</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Безубыток:</span>
                        <strong>{{ number_format($trade->getAverageEntryPrice(), 8) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Take Profit:</span>
                        <strong class="text-success">{{ number_format($trade->take_profit_price, 8) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Stop Loss:</span>
                        <strong class="text-danger">{{ number_format($trade->stop_loss_price, 8) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
