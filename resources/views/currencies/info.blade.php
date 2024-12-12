@push('scripts')
    <script src="/assets/highcharts/js/highstock.js"></script>
    <script src="/assets/highcharts/js/data.js"></script>
    <script src="/assets/highcharts/js/exporting.js"></script>
    <script src="/assets/highcharts/js/export-data.js"></script>
@endpush
<div class="bg-white rounded shadow-sm p-4">
    <div class="row">
        <div class="col-md-6">
            <h3>{{ $currency->name }} ({{ $currency->code }})</h3>
            <div class="mt-3">
                <p><strong>Текущая цена:</strong> {{ $currency->last_price }}</p>
                <p><strong>Рыночная капитализация:</strong> {{ $currency->market_cap }}</p>
                <p><strong>Объем за 24ч:</strong> {{ $currency->volume_24h }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Изменение цены</h5>
                    <p>24ч: <span class="{{ $currency->price_change_24h >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $currency->price_change_24h }}%
                    </span></p>
                    <p>7д: <span class="{{ $currency->price_change_7d >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $currency->price_change_7d }}%
                    </span></p>
                </div>
            </div>
        </div>
    </div>
</div>
