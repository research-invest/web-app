@push('scripts')
    <script src="/assets/highcharts/js/highstock.js"></script>
    <script src="/assets/highcharts/js/hollowcandlestick.js"></script>
    {{--    <script src="/assets/highcharts/js/data.js"></script>--}}
    {{--    <script src="/assets/highcharts/js/exporting.js"></script>--}}
    {{--    <script src="/assets/highcharts/js/export-data.js"></script>--}}
@endpush
<div class="card mb-3">
    <div class="card-body">
        <h3 class="card-title h4 mb-4">Как читать график Hollow Candlestick</h3>

        <div class="list-group">
            <div class="list-group-item border-0">
                <div class="d-flex align-items-center">
                    <div style="width: 48px; height: 48px;" class="border border-2 border-success me-3"></div>
                    <div>
                        <h5 class="mb-1 text-success">Зеленая пустая свеча (прозрачная с зеленым контуром)</h5>
                        <p class="mb-0 text-muted">Цена закрытия выше цены открытия и выше предыдущего закрытия (сильный рост)</p>
                    </div>
                </div>
            </div>

            <div class="list-group-item border-0">
                <div class="d-flex align-items-center">
                    <div style="width: 48px; height: 48px;" class="border border-2 border-success bg-success me-3"></div>
                    <div>
                        <h5 class="mb-1 text-success">Зеленая заполненная свеча</h5>
                        <p class="mb-0 text-muted">Цена закрытия выше цены открытия, но ниже предыдущего закрытия (слабый рост)</p>
                    </div>
                </div>
            </div>

            <div class="list-group-item border-0">
                <div class="d-flex align-items-center">
                    <div style="width: 48px; height: 48px;" class="border border-2 border-danger me-3"></div>
                    <div>
                        <h5 class="mb-1 text-danger">Красная пустая свеча</h5>
                        <p class="mb-0 text-muted">Цена закрытия ниже цены открытия, но выше предыдущего закрытия (слабое падение)</p>
                    </div>
                </div>
            </div>

            <div class="list-group-item border-0">
                <div class="d-flex align-items-center">
                    <div style="width: 48px; height: 48px;" class="border border-2 border-danger bg-danger me-3"></div>
                    <div>
                        <h5 class="mb-1 text-danger">Красная заполненная свеча</h5>
                        <p class="mb-0 text-muted">Цена закрытия ниже цены открытия и ниже предыдущего закрытия (сильное падение)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
