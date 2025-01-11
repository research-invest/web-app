<div class="bg-white rounded shadow-sm p-4">
    @if(isset($tradingStats))
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Общая статистика</h5>
                        <p>Всего сделок: <a href="{{ route('platform.trading.deals', ['currency_id' => $currency->id]) }}" target="_blank">{{ $tradingStats['total_trades'] }}</a></p>
                        <p>Успешных: {{ $tradingStats['successful_trades'] }}</p>
                        <p>Прибыльность: {{ $tradingStats['success_rate'] }}%</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Прибыль/Убыток</h5>
                        <p>Общая P&L: {{ $tradingStats['total_pnl'] }}</p>
                        <p>Средний P&L: {{ $tradingStats['average_pnl'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5>Временные показатели</h5>
                        <p>Средняя длительность: {{ $tradingStats['average_duration'] }}</p>
                        <p>Макс. просадка: {{ $tradingStats['max_drawdown'] }}%</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Статистика торгов отсутствует
        </div>
    @endif
</div>
