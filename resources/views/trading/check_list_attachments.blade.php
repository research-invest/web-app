@if($attachments->isNotEmpty())
    <div class="bg-light p-3 rounded mt-3">
        <h6 class="mb-3">Прикрепленные файлы:</h6>
        <div class="row">
            @foreach($attachments as $attachment)
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <img src="{{ $attachment->url() }}"
                             class="card-img-top"
                             alt="{{ $attachment->alt ?? 'Screenshot' }}">
                        @if($attachment->alt)
                            <div class="card-body">
                                <p class="card-text small">{{ $attachment->alt }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
