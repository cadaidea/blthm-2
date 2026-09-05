@php($df_ctx = (request()->is('blog') || request()->is('blog/*')) ? 'blog' : ((request()->is('/') || request()->is('producto/*') || request()->is('categoria/*') || request()->is('carrito*') || request()->is('checkout*') || request()->is('tienda*')) ? 'tienda' : 'paginas'))
@php($df_forms = \App\Models\Formulario::where('estado','activo')->whereIn('tipo',['popup','slide_in','tab','bar_top','bar_bottom'])->get()->filter(fn ($f) => in_array(($f->ambito ?: 'todo'), ['todo', $df_ctx], true)))
@if($df_forms->count())
<link rel="stylesheet" href="{{ asset('css/digest-forms.css') }}?v={{ @filemtime(public_path('css/digest-forms.css')) }}">
@foreach($df_forms as $f)
    @php($o = (array) ($f->opciones ?? []))
    @if($f->tipo === 'tab')
        @php($img = $f->imagen ? \Illuminate\Support\Facades\Storage::disk('public')->url($f->imagen) : null)
        @php($tc = $o['tab_color'] ?? '')
        @php($tc = $tc ?: '#ffffff')
        @php($hex = ltrim($tc, '#'))
        @php($hex = strlen($hex) === 3 ? $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2] : $hex)
        @php($lum = (0.299*hexdec(substr($hex,0,2)) + 0.587*hexdec(substr($hex,2,2)) + 0.114*hexdec(substr($hex,4,2))))
        @php($txt = $lum > 150 ? '#161921' : '#ffffff')
        <div class="df-form df-tab" id="df-{{ $f->id }}" data-id="{{ $f->id }}" data-tipo="tab" data-repetir="{{ $o['repetir_dias'] ?? 7 }}">
            <div class="df-tab-bubble" role="button" tabindex="0" style="background:{{ $tc }};color:{{ $txt }};border:1px solid {{ $lum > 230 ? 'var(--df-line,#e3e3e3)' : 'transparent' }}">
                <span class="df-tab-bubble-txt">{{ $o['tab_label'] ?? 'Únete' }}</span>
                <span class="df-tab-bubble-x" role="button" aria-label="Cerrar">&times;</span>
            </div>
            <div class="df-tab-panel" aria-hidden="true">
                <button type="button" class="df-tab-panel-x" aria-label="Cerrar">&times;</button>
                <div class="df-tab-grid {{ $img ? '' : 'no-media' }}">
                    <div class="df-tab-body">
                        @if($f->titulo)<h3 class="df-title">{{ $f->titulo }}</h3>@endif
                        @if($f->descripcion)<p class="df-desc">{{ $f->descripcion }}</p>@endif
                        <form method="post" action="{{ route('digest.subscribe') }}" class="df-fields">
                            @csrf
                            <input type="hidden" name="form_id" value="{{ $f->id }}">
                            <input type="text" name="website" tabindex="-1" autocomplete="off" class="df-hp" aria-hidden="true">
                            @include('tienda.partials.digest-campos', ['f' => $f])
                            <button type="submit" class="df-btn">{{ $f->boton_texto ?: 'Suscribirme' }}</button>
                        </form>
                    </div>
                    @if($img)<div class="df-tab-media" style="background-image:url('{{ $img }}')"></div>@endif
                </div>
            </div>
        </div>
    @else
        <div class="df-form df-{{ $f->tipo }}" id="df-{{ $f->id }}" aria-hidden="true"
             data-id="{{ $f->id }}" data-tipo="{{ $f->tipo }}"
             data-trigger="{{ $o['trigger'] ?? 'delay' }}" data-valor="{{ $o['valor'] ?? 5 }}" data-repetir="{{ $o['repetir_dias'] ?? 7 }}">
            <div class="df-box">
                @if(in_array($f->tipo, ['popup', 'slide_in']))<button type="button" class="df-close" aria-label="Cerrar">&times;</button>@endif
                @if($f->titulo)<h3 class="df-title">{{ $f->titulo }}</h3>@endif
                @if($f->descripcion)<p class="df-desc">{{ $f->descripcion }}</p>@endif
                <form method="post" action="{{ route('digest.subscribe') }}" class="df-fields">
                    @csrf
                    <input type="hidden" name="form_id" value="{{ $f->id }}">
                    <input type="text" name="website" tabindex="-1" autocomplete="off" class="df-hp" aria-hidden="true">
                    @include('tienda.partials.digest-campos', ['f' => $f])
                    <button type="submit" class="df-btn">{{ $f->boton_texto ?: 'Suscribirme' }}</button>
                </form>
                <button type="button" class="df-bar-close" aria-label="Cerrar">&times;</button>
            </div>
        </div>
    @endif
@endforeach
<script src="{{ asset('js/digest-forms.js') }}?v={{ @filemtime(public_path('js/digest-forms.js')) }}"></script>
@endif
