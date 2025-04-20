<div class="bg-white p-4 rounded shadow mt-4">
    <h3>Интерпретация данных</h3>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Общий тренд активности</h5>
                </div>
                <div class="card-body">
                    <p>Этот график показывает количество кошельков с ростом и падением баланса. Когда зеленая область (рост)
                        превышает красную (падение), киты в целом накапливают BTC.</p>
                    <p>Длительный период преобладания зеленой области часто бывает бычьим сигналом.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Соотношение рост/падение</h5>
                </div>
                <div class="card-body">
                    <p>Показывает соотношение между количеством растущих и падающих кошельков.</p>
                    <p>Значения > 1 означают, что рост преобладает над падением.</p>
                    <p>Резкие скачки соотношения часто предшествуют движениям цены.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Динамика общего баланса</h5>
                </div>
                <div class="card-body">
                    <p>Отображает общий объем BTC на отслеживаемых кошельках.</p>
                    <p>Устойчивый рост говорит о накоплении, что обычно сигнализирует о долгосрочной уверенности.</p>
                    <p>Резкое падение может указывать на массовую распродажу или перемещение в холодные кошельки.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Корреляция с ценой BTC</h5>
                </div>
                <div class="card-body">
                    <p>Сравнивает активность китов с движением цены BTC.</p>
                    <p>Важно обращать внимание на расхождения — когда киты действуют противоположно рынку.</p>
                    <p>Часто киты начинают накапливать до роста цены и распродавать до начала падения.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-3">
        <h5>Аналитические выводы</h5>
        <ul>
            @php
                $lastReports = collect($reports)->sortByDesc('report_date')->take(7);
                $recentGrowth = $lastReports->sum('grown_wallets_count');
                $recentDrop = $lastReports->sum('dropped_wallets_count');
                $trend = $recentGrowth > $recentDrop ? 'накопления' : 'распределения';
                $firstBalance = $reports->first()->total_balance ?? 0;
                $lastBalance = $reports->last()->total_balance ?? 0;
                $balanceChange = $lastBalance - $firstBalance;
                $balancePercent = $firstBalance > 0 ? round(($balanceChange / $firstBalance) * 100, 2) : 0;
            @endphp

            <li>За последнюю неделю киты проявляют тенденцию к <strong>{{ $trend }}</strong> BTC.</li>
            <li>Соотношение растущих к падающим кошелькам: <strong>{{ $recentDrop > 0 ? round($recentGrowth / $recentDrop, 2) : 'N/A' }}</strong></li>
            <li>За выбранный период общий баланс изменился на <strong>{{ $balanceChange > 0 ? '+' : '' }}{{ number_format($balanceChange, 2) }} BTC ({{ $balancePercent }}%)</strong></li>
        </ul>
    </div>
</div>
