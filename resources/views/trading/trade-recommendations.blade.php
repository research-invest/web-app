<div class="bg-white rounded shadow-sm p-4">
    @if(count($recommendations) > 0)
        @foreach($recommendations as $recommendation)
            <div class="alert alert-{{ $recommendation['type'] }} mb-3">
                <h4 class="alert-heading">{{ $recommendation['title'] }}</h4>
                <p>{{ $recommendation['description'] }}</p>
                
                @if($recommendation['action'] === 'reduce_position')
                    <div class="mt-3">
                        <button class="btn btn-warning" 
                                data-controller="modal" 
                                data-modal-target="addOrderModal">
                            Уменьшить позицию
                        </button>
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="alert alert-success">
            <h4 class="alert-heading">Всё в порядке</h4>
            <p>На данный момент нет специальных рекомендаций по этой сделке.</p>
        </div>
    @endif
</div> 