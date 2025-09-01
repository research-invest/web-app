@php
//dd($todayMetrics);
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">📈 Лучшая сделка за сегодня</h5>
            </div>
            <div class="card-body">
                @if(isset($todayMetrics['best_trade']))
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1">
                                +{{ number_format($todayMetrics['best_trade']['pnl'], 2) }}$
                                ({{ number_format($todayMetrics['best_trade']['roi'], 2) }}%)
                            </h6>
                            <small class="text-muted">
                                Размер: {{ number_format($todayMetrics['best_trade']['size'], 2) }}$
                            </small>
                            <br>
                            <small class="text-muted">
                                Валюта: {{ $todayMetrics['best_trade']['trade']->currency->code ?? 'N/A' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success">ROI: {{ number_format($todayMetrics['best_trade']['roi'], 2) }}%</span>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">Нет закрытых сделок за сегодня</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">📉 Худшая сделка за сегодня</h5>
            </div>
            <div class="card-body">
                @if(isset($todayMetrics['worst_trade']))
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-danger mb-1">
                                {{ number_format($todayMetrics['worst_trade']['pnl'], 2) }}$
                                ({{ number_format($todayMetrics['worst_trade']['roi'], 2) }}%)
                            </h6>
                            <small class="text-muted">
                                Размер: {{ number_format($todayMetrics['worst_trade']['size'], 2) }}$
                            </small>
                            <br>
                            <small class="text-muted">
                                Валюта: {{ $todayMetrics['worst_trade']['trade']->currency->code ?? 'N/A' }}
                            </small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-danger">ROI: {{ number_format($todayMetrics['worst_trade']['roi'], 2) }}%</span>
                        </div>
                    </div>
                @else
                    <p class="text-muted mb-0">Нет закрытых сделок за сегодня</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">📊 Сводка за сегодня</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="mb-1 {{ $todayMetrics['today_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($todayMetrics['today_pnl'], 2) }}$
                            </h4>
                            <small class="text-muted">Общий PnL</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="mb-1 text-info">
                                {{ number_format($todayMetrics['max_today_pnl'], 2) }}$
                            </h4>
                            <small class="text-muted">Макс. PnL</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border-end">
                            <h4 class="mb-1 text-primary">
                                {{ $todayMetrics['trades_count'] }}
                            </h4>
                            <small class="text-muted">Сделок</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div>
                            @if($todayMetrics['best_trade'] && $todayMetrics['worst_trade'])
                                @php
                                    $roiDiff = $todayMetrics['best_trade']['roi'] - $todayMetrics['worst_trade']['roi'];
                                @endphp
                                <h4 class="mb-1 {{ $roiDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($roiDiff, 2) }}%
                                </h4>
                                <small class="text-muted">Разброс ROI</small>
                            @else
                                <h4 class="mb-1 text-muted">-</h4>
                                <small class="text-muted">Разброс ROI</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
