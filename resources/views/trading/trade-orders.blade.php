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
                            @case('exit')
                                <span class="badge bg-warning">Частичный выход</span>
                                @break
                        @endswitch
                    </td>
                    <td>{{ number_format($order->price) }}</td>
                    <td>{{ number_format($order->size, 2) }} USDT</td>
                    <td>
                        @if($order->type === 'entry' || $order->type === 'add')
                            <span class="text-success">+{{ number_format($order->size, 2) }}</span>
                        @else
                            <span class="text-danger">-{{ number_format($order->size, 2) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($order->type !== 'entry')
                            <button class="btn btn-sm btn-danger delete-order-btn"
                                    data-trade-id="{{ $trade->id }}"
                                    data-order-id="{{ $order->id }}">
                                <i class="fas fa-trash"></i> Удалить
                            </button>
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

@push('scripts')
    <script>
        document.querySelectorAll('.delete-order-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите удалить этот ордер?')) {
                    const tradeId = this.dataset.tradeId;
                    const orderId = this.dataset.orderId;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `/trading/deals/${tradeId}/orders/${orderId}`;

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';

                    const methodField = document.createElement('input');
                    methodField.type = 'hidden';
                    methodField.name = '_method';
                    methodField.value = 'DELETE';

                    form.appendChild(csrfToken);
                    form.appendChild(methodField);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });
    </script>
@endpush
