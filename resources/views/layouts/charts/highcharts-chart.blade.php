@php
    $idContainer = Illuminate\Support\Str::random(10);
@endphp

<div id="highcharts-container-{{ $idContainer }}" style="width: 100%; min-height: 70vh;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function initializeChart_{{ $idContainer }}() {
            Highcharts.chart('highcharts-container-{{ $idContainer }}', {!! $chartOptions !!});
        }

        initializeChart_{{ $idContainer }}();
    });
</script>
