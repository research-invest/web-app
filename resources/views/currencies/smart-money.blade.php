<div class="bg-white rounded shadow-sm p-4">
    @if(isset($smartMoney))
        <div class="row">
            <div class="col-md-6">
                <h4>Smart Money Индикаторы</h4>
                <div class="card">
                    <div class="card-body">
                        <p><strong>Накопление/Распределение:</strong> {{ $smartMoney['accumulation'] }}</p>
                        <p><strong>Объем дельта:</strong> {{ $smartMoney['volume_delta'] }}</p>
                        <p><strong>Тренд:</strong> {{ $smartMoney['trend'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h4>Рекомендации</h4>
                <div class="alert alert-{{ $smartMoney['recommendation_type'] }}">
                    {{ $smartMoney['recommendation'] }}
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            Smart Money анализ недоступен
        </div>
    @endif
</div> 