<div class="bg-white rounded shadow-sm p-4 mb-4">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-title">Торговых дней</h6>
                    <h4>{{ $chartData['summary']['tradingDays'] }} [{{ $chartData['summary']['totalDays'] }}]</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="card-title">Общий P&L</h6>
                    <h4>${{ number_format($chartData['summary']['totalPnl'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="card-title">Целевой P&L</h6>
                    <h4>${{ number_format($chartData['summary']['targetPnl'], 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $chartData['summary']['difference'] >= 0 ? 'border-success' : 'border-danger' }}">
                <div class="card-body">
                    <h6 class="card-title">Разница</h6>
                    <h4>${{ number_format($chartData['summary']['difference'], 2) }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.getElementById('select-periods').addEventListener('change', function () {
        const selectedPeriod = this.value;
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('period_id', selectedPeriod);
        window.location.href = currentUrl.toString();
    });
</script>

