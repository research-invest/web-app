<div class="container py-4">

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h3 class="card-title mb-3">Статистика по конфигу: <span class="text-primary">
                    <a target="_blank" href="{{ route('platform.trading.funding_deals', $config->id) }}">{{ $config->name }}</a>
                </span></h3>
            <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item">Всего сделок: <b>{{ $totalDeals }}</b></li>
                <li class="list-group-item">Суммарная прибыль: <b class="text-success">+{{ $sumProfit }}</b></li>
                <li class="list-group-item">Суммарные убытки: <b class="text-danger">{{ $sumLoss }}</b></li>
                <li class="list-group-item">Суммарно: <b class="{{ $totalProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $totalProfit }}</b></li>
                <li class="list-group-item">Средняя прибыль: <b>{{ $averageProfit }}</b></li>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Топ-5 прибыльных сделок</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($topDeals as $deal)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
        <span>
            Name: <a href="{{ route('platform.trading.funding_deal.edit', $deal->id) }}"><b>{{ $deal->currency->name }}</b></a>
        </span>
                            <span class="{{ $deal->total_pnl >= 0 ? 'text-success' : 'text-danger' }}">
            {{ $deal->total_pnl >= 0 ? '+' : '' }}{{ $deal->total_pnl }}
        </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Нет данных</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Топ-5 убыточных сделок</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($worstDeals as $deal)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
        <span>
            Name: <a href="{{ route('platform.trading.funding_deal.edit', $deal->id) }}"><b>{{ $deal->currency->name }}</b></a>
        </span>
                            <span class="{{ $deal->total_pnl >= 0 ? 'text-success' : 'text-danger' }}">
            {{ $deal->total_pnl >= 0 ? '+' : '' }} {{ $deal->total_pnl }}
        </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Нет данных</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Топ-5 прибыльных монеты</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($topCoins as $coin)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $coin['coin'] }} ({{ $coin['count'] }} сделок)
                        </span>
                            <span class="{{ $coin['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
            {{ $coin['total_profit'] >= 0 ? '+' : '' }}{{ $coin['total_profit'] }}
        </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Нет данных</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Топ-5 убыточных монеты</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($worstCoins as $coin)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $coin['coin'] }} ({{ $coin['count'] }} сделок)</span>
                            <span class="text-danger">{{ $coin['total_profit'] }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Нет данных</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
