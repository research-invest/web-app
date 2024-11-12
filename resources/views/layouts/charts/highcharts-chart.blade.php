@php
    $idContainer = Illuminate\Support\Str::random(10);
@endphp

<div id="highcharts-container-{{ $idContainer }}" style="width: 100%;height: 900px;"></div>

<script>
    if (typeof window.highchartsLoaded === 'undefined') {
        window.highchartsLoaded = true;
        var script = document.createElement('script');
        script.src = "/assets/highcharts/js/highcharts-min.js";
        script.onload = function() {
            initializeChart_{{ $idContainer }}();
        };
        document.head.appendChild(script);
    } else {
        initializeChart_{{ $idContainer }}();
    }

    function initializeChart_{{ $idContainer }}() {
        Highcharts.chart('highcharts-container-{{ $idContainer }}', {!! $chartOptions !!});
    }
</script>
