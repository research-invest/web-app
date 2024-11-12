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

    <div class="row">
        <div class="col-12">
            <h4>P&L График</h4>
            <div style="height: 400px;">
                <canvas id="pnlChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="/assets/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('pnlChart').getContext('2d');
    const chartData = @json($chartData);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Плановый vs Фактический P&L'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' USD';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' USD';
                        }
                    }
                }
            }
        }
    });
});
</script>
