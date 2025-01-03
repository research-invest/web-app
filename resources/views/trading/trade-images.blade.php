<div class="bg-white rounded shadow-sm p-4 mb-4">
    @if($trade->attachment->isEmpty())
        <p class="text-muted">Нет загруженных изображений</p>
    @else
        <div class="row">
            @foreach($trade->attachment as $image)
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <img src="{{ $image->url() }}" class="card-img-top" alt="Trade Image">
                        <div class="card-body">
                            <p class="card-text text-muted small">
                                Загружено: {{ $image->created_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div> 