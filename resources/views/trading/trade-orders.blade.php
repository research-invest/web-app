<div class="bg-white rounded shadow-sm p-4">
    <div class="mb-3">
        <h4>История ордеров</h4>
        <div class="small text-muted mb-3">
            Общий размер позиции: {{ number_format($trade->position_size, 2) }} USDT
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Тип</th>
                <th>Цена</th>
                <th>Размер</th>
                <th>Влияние на позицию</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach($trade->orders()->orderBy('executed_at')->get() as $order)
                <tr>
                    <td>{{ $order->executed_at ? $order->executed_at->format('d.m.Y H:i:s') : 'Не исполнен' }}</td>
                    <td>
                        @switch($order->type)
                            @case('entry')
                                <span class="badge bg-primary">Вход</span>
                                @break
                            @case('add')
                                <span class="badge bg-info">Доп. вход</span>
                                @break
                            @case('fixed')
                                <span class="badge bg-warning">Частичный выход</span>
                                @break
                            @case('exit')
                                <span class="badge bg-success">Закрытие сделки</span>
                                @break
                        @endswitch
                    </td>
                    <td>{{ number_format($order->price, 8) }}</td>
                    <td>{{ number_format($order->size, 2) }} USDT</td>
                    <td>
                        @if($order->type === 'entry' || $order->type === 'add')
                            <span class="text-success">+{{ number_format($order->size, 2) }}</span>
                        @else
                            <span class="text-danger">-{{ number_format($order->size, 2) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($order->type !== 'entry' && $trade->status === 'open')
{{--                            <button class="btn btn-sm btn-danger"--}}
{{--                                    onclick="document.getElementById('remove-order-{{ $order->id }}').submit();">--}}
{{--                                <i class="fas fa-trash"></i>--}}
{{--                            </button>--}}
{{--                            <form id="remove-order-{{ $order->id }}" --}}
{{--                                  action="{{ route('platform.trades.orders.remove', ['trade' => $trade->id, 'order' => $order->id]) }}" --}}
{{--                                  method="POST" --}}
{{--                                  style="display: none;">--}}
{{--                                @csrf--}}
{{--                                @method('DELETE')--}}
{{--                            </form>--}}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($trade->status === 'open')
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Используйте кнопку "Добавить ордер" для регистрации дополнительных входов или частичных выходов из позиции.
        </div>
    @endif
</div>
