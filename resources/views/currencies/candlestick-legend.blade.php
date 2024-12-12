@push('scripts')
    <script src="/assets/highcharts/js/highstock.js"></script>
    <script src="/assets/highcharts/js/hollowcandlestick.js"></script>
    {{--    <script src="/assets/highcharts/js/data.js"></script>--}}
    {{--    <script src="/assets/highcharts/js/exporting.js"></script>--}}
    {{--    <script src="/assets/highcharts/js/export-data.js"></script>--}}
@endpush
<div class="bg-white p-4 rounded-lg shadow mb-4">
    <h3 class="text-lg font-semibold mb-3">Как читать график Hollow Candlestick</h3>

    <div class="grid gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 border-2 border-green-500 bg-transparent"></div>
            <div class="flex-1">
                <span class="font-medium text-green-500">Сильный рост</span>
                <p class="text-gray-600 text-sm">Цена закрытия выше цены открытия и выше предыдущего закрытия</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 border-2 border-green-500 bg-green-500"></div>
            <div class="flex-1">
                <span class="font-medium text-green-500">Слабый рост</span>
                <p class="text-gray-600 text-sm">Цена закрытия выше цены открытия, но ниже предыдущего закрытия</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 border-2 border-red-500 bg-transparent"></div>
            <div class="flex-1">
                <span class="font-medium text-red-500">Слабое падение</span>
                <p class="text-gray-600 text-sm">Цена закрытия ниже цены открытия, но выше предыдущего закрытия</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-12 h-12 border-2 border-red-500 bg-red-500"></div>
            <div class="flex-1">
                <span class="font-medium text-red-500">Сильное падение</span>
                <p class="text-gray-600 text-sm">Цена закрытия ниже цены открытия и ниже предыдущего закрытия</p>
            </div>
        </div>
    </div>
</div>
