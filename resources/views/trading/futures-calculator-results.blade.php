@if(!empty($result))
<div class="bg-white p-4 rounded shadow-sm">
    <h3 class="mb-4">Результаты расчета</h3>
    
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label class="font-weight-bold">Средняя цена входа:</label>
                <div>{{ $result['average_price'] }}</div>
            </div>
            
            <div class="mb-3">
                <label class="font-weight-bold">Общий размер позиции:</label>
                <div>{{ $result['total_position_size'] }} USDT</div>
            </div>
            
            <div class="mb-3">
                <label class="font-weight-bold">Размер контракта:</label>
                <div>{{ $result['total_contracts'] }} USDT</div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="mb-3 text-danger">
                <label class="font-weight-bold">Цена ликвидации:</label>
                <div>{{ $result['liquidation_price'] }}</div>
            </div>
            
            <div class="mb-3 text-danger">
                <label class="font-weight-bold">Стоп-лосс:</label>
                <div>{{ $result['stop_loss_price'] }} ({{ $result['potential_loss'] }} USDT)</div>
            </div>
            
            <div class="mb-3 text-success">
                <label class="font-weight-bold">Тейк-профит:</label>
                <div>{{ $result['take_profit_price'] }} ({{ $result['potential_profit'] }} USDT)</div>
            </div>
            
            <div class="mb-3">
                <label class="font-weight-bold">Соотношение риск/прибыль:</label>
                <div>1:{{ $result['risk_reward_ratio'] }}</div>
            </div>
        </div>
    </div>
</div>
@endif 