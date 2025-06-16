<div class="mb-4 p-4 bg-white rounded shadow-sm text-center">
    <div class="row justify-content-center align-items-center">
        @foreach($sessions as $session)
            <div class="col-md-3 col-12 mb-2 mb-md-0">
                <div style="border-radius: 12px; border: 2px solid {{ $session['color'] }}; padding: 18px 10px; margin: 0 8px; background: rgba(0,0,0,0.01);">
                    <div style="font-size: 1.5rem; font-weight: bold; color: {{ $session['color'] }};">
                        {{ $session['name'] }}
                    </div>
                    <div style="font-size: 1.2rem; color: #222; margin-top: 8px; font-weight: bold;">
                        {{ $session['time'] }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3" style="font-size: 1.1rem; font-weight: bold; color: #333;">
        Время указано по МСК (UTC+3)
    </div>
</div> 