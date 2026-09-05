@php($f = isset($form) ? $form : \App\Models\Formulario::find($id ?? 0))
@if($f && $f->estado === 'activo')
<div class="df-form df-inline is-open" data-id="{{ $f->id }}">
    <div class="df-box">
        @if($f->titulo)<h3 class="df-title">{{ $f->titulo }}</h3>@endif
        @if($f->descripcion)<p class="df-desc">{{ $f->descripcion }}</p>@endif
        <form method="post" action="{{ route('digest.subscribe') }}" class="df-fields">
            @csrf
            <input type="hidden" name="form_id" value="{{ $f->id }}">
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="df-hp" aria-hidden="true">
            @include('tienda.partials.digest-campos', ['f' => $f])
            @if(!empty($premarcado))
                <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin:4px 0 10px">
                    <input type="checkbox" name="acepto_news" value="1" checked> Quiero recibir novedades y promociones
                </label>
            @endif
            <button type="submit" class="df-btn">{{ $f->boton_texto ?: 'Suscribirme' }}</button>
        </form>
    </div>
</div>
@endif
