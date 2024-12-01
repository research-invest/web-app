<div class="bg-white rounded shadow-sm p-4">
    <div class="chart-container" style="height: 400px;">
        @if(isset($priceChart))
            <canvas id="priceChart"></canvas>
        @else
            <div class="alert alert-info">
                Данные графика недоступны
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('priceChart')) {
            const ctx = document.getElementById('priceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: @json($priceChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }
    });
</script>
@endpush 