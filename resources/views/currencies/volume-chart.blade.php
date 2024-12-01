<div class="bg-white rounded shadow-sm p-4">
    <div class="chart-container" style="height: 400px;">
        @if(isset($volumeChart))
            <canvas id="volumeChart"></canvas>
        @else
            <div class="alert alert-info">
                Данные об объемах торгов недоступны
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('volumeChart')) {
            const ctx = document.getElementById('volumeChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: @json($volumeChart),
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>
@endpush 