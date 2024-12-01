<div class="bg-white rounded shadow-sm p-4">
    @if(isset($technicalAnalysis))
        <div class="row">
            <div class="col-md-6">
                <h4>Технические индикаторы</h4>
                <div class="table-responsive">
                    <table class="table">
                        <tbody>
                            <tr>
                                <td>RSI (14)</td>
                                <td>{{ $technicalAnalysis['rsi'] }}</td>
                            </tr>
                            <tr>
                                <td>MACD</td>
                                <td>{{ $technicalAnalysis['macd'] }}</td>
                            </tr>
                            <tr>
                                <td>MA (50)</td>
                                <td>{{ $technicalAnalysis['ma50'] }}</td>
                            </tr>
                            <tr>
                                <td>MA (200)</td>
                                <td>{{ $technicalAnalysis['ma200'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Сигналы</h4>
                <div class="alert alert-{{ $technicalAnalysis['signal_type'] }}">
                    {{ $technicalAnalysis['signal_message'] }}
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Технический анализ недоступен
        </div>
    @endif
</div> 