@php($o = (array) ($f->opciones ?? []))
@php($campos = ($o['campos'] ?? null) ?: ($f->pedir_nombre ? ['nombre'] : []))
@php($req = (array) ($o['campos_req'] ?? []))
@php($rq = fn($c) => in_array($c, $req) ? 'required' : '')
@php($nom = in_array('nombre', $campos))
@php($ape = in_array('apellido', $campos))

@if($nom && $ape)
<div style="display:flex;gap:8px">
    <input type="text" name="nombre" placeholder="Tu nombre{{ in_array('nombre',$req)?' *':'' }}" class="df-input" style="flex:1;min-width:0" {{ $rq('nombre') }}>
    <input type="text" name="apellido" placeholder="Tu apellido{{ in_array('apellido',$req)?' *':'' }}" class="df-input" style="flex:1;min-width:0" {{ $rq('apellido') }}>
</div>
@else
    @if($nom)<input type="text" name="nombre" placeholder="Tu nombre{{ in_array('nombre',$req)?' *':'' }}" class="df-input" {{ $rq('nombre') }}>@endif
    @if($ape)<input type="text" name="apellido" placeholder="Tu apellido{{ in_array('apellido',$req)?' *':'' }}" class="df-input" {{ $rq('apellido') }}>@endif
@endif
@if(in_array('telefono', $campos))<input type="tel" name="telefono" placeholder="Tu teléfono{{ in_array('telefono',$req)?' *':'' }}" class="df-input" {{ $rq('telefono') }}>@endif
@if(in_array('ciudad', $campos))<input type="text" name="ciudad" placeholder="Tu ciudad{{ in_array('ciudad',$req)?' *':'' }}" class="df-input" {{ $rq('ciudad') }}>@endif
@if(in_array('nacimiento', $campos))
<label style="font-size:12px;color:#888;display:block;margin:2px 0">Cumpleaños{{ in_array('nacimiento',$req)?' *':'' }}</label>
<input type="date" name="nacimiento" class="df-input" {{ $rq('nacimiento') }}>
@endif
<input type="email" name="email" placeholder="Tu correo *" required class="df-input">
@if(\App\Models\Ajuste::get('turnstile_activo') === '1' && \App\Models\Ajuste::get('turnstile_site_key'))
<div class="cf-turnstile" data-sitekey="{{ \App\Models\Ajuste::get('turnstile_site_key') }}" data-theme="light" data-size="flexible"></div>
@endif

@if(!empty($o['elegir_lista']) && !empty($f->lista_ids))
@php($opts = \App\Models\Lista::whereIn('id', (array) $f->lista_ids)->pluck('nombre','id'))
<div class="df-listas">
    <span class="df-listas-lbl">Quiero recibir:</span>
    <div class="df-chips">
        @foreach($opts as $lid => $lnom)
            <label class="df-chip">
                <input type="checkbox" name="listas[]" value="{{ $lid }}" {{ $loop->first ? 'checked' : '' }}>
                <span>{{ $lnom }}</span>
            </label>
        @endforeach
    </div>
</div>
@endif

@if(in_array('acepto', $campos))
<label style="display:flex;gap:8px;align-items:center;font-size:13px;margin:4px 0 10px">
    <input type="checkbox" name="acepto_news" value="1" {{ in_array('acepto',$req)?'required':'' }}> Acepto recibir novedades y promociones{{ in_array('acepto',$req)?' *':'' }}
</label>
@endif
