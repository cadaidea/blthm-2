@php
if (!function_exists('bloque_str')) {
    function bloque_str($v) {
        if (is_array($v)) { $o = ''; foreach ($v as $x) $o .= ' ' . bloque_str($x); return trim($o); }
        return (string) ($v ?? '');
    }
}
@endphp
@foreach(($bloques ?? []) as $b)
    @php($t = $b['type'] ?? null)
    @php($d = $b['data'] ?? [])
    @php($full = !empty($d['full']) ? 'is-full' : '')
    @php($tsz = !empty($d['texto_size']) ? intval($d['texto_size']) : 18)
    @php($hsz = !empty($d['h_size']) ? intval($d['h_size']) : 25)
    @php($istyle = (!empty($d['alto']) ? 'height:'.intval($d['alto']).'px;object-fit:cover;' : '') . ((isset($d['radio']) && (int)$d['radio'] > 0) ? 'border-radius:'.intval($d['radio']).'px;' : ''))
    @php($talign = ['izq' => 'left', 'centro' => 'center', 'der' => 'right'][$d['align'] ?? 'izq'] ?? 'left')
    @if($t === 'titulo')
        @if(($d['nivel'] ?? 'h2') === 'h3')<h3 style="font-size:{{ $hsz }}px;text-align:{{ $talign }}">{{ bloque_str($d['titulo'] ?? '') }}</h3>@else<h2 style="font-size:{{ $hsz }}px;text-align:{{ $talign }}">{{ bloque_str($d['titulo'] ?? '') }}</h2>@endif
    @elseif($t === 'texto')
        <div class="t-block t-block-texto {{ $full }}" style="font-size:{{ $tsz }}px;text-align:{{ $talign }}">{!! is_array($d['texto'] ?? null) ? \App\Support\EditorJsRenderer::render($d['texto']) : bloque_str($d['texto'] ?? '') !!}</div>
    @elseif($t === 'cita')
        <blockquote class="t-block-cita t-block {{ $full }}" style="font-size:{{ $tsz }}px">{{ bloque_str($d['texto'] ?? '') }}</blockquote>
    @elseif($t === 'imagen')
        @php($u = !empty($d['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($d['imagen']) : null)
        @php($fstyle = !empty($d['ancho_px']) ? 'max-width:'.intval($d['ancho_px']).'px;' : '')
        @if($u)<figure class="t-block t-block-img--{{ $d['ancho'] ?? 'completa' }} t-block-img--{{ $d['align'] ?? 'centro' }} {{ $full ? 'is-full is-bleed' : '' }}" style="{{ $fstyle }}"><img src="{{ $u }}" alt="{{ bloque_str($d['alt'] ?? '') }}" style="{{ $istyle }}">@if(!empty($d['pie']))<figcaption class="t-iva" style="text-align:center;margin-top:6px">{{ bloque_str($d['pie']) }}</figcaption>@endif</figure>@endif
    @elseif($t === 'imagen_texto')
        @php($u = !empty($d['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($d['imagen']) : null)
        <div class="t-block t-block-it {{ ($d['posicion'] ?? 'izq') === 'der' ? 'is-der' : '' }} {{ $full ? 'is-full is-bleed' : '' }}">
            <div>@if($u)<img src="{{ $u }}" alt="{{ bloque_str($d['alt'] ?? '') }}" style="{{ $istyle }}">@endif</div>
            <div class="t-it-txt" style="font-size:{{ $tsz }}px">{!! bloque_str($d['texto'] ?? '') !!}</div>
        </div>
    @elseif($t === 'imagen_borde')
        @php($u = !empty($d['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($d['imagen']) : null)
        <div class="t-block t-imgborde {{ ($d['posicion'] ?? 'izq') === 'der' ? 'is-der' : '' }}">
            <div class="t-imgborde-img">@if($u)<img src="{{ $u }}" alt="" style="{{ $istyle }}">@endif</div>
            <div class="t-imgborde-txt"><div class="t-imgborde-in" style="font-size:{{ $tsz }}px">@if(!empty($d['titulo']))<h2 style="font-size:{{ $hsz }}px">{{ bloque_str($d['titulo']) }}</h2>@endif{!! bloque_str($d['texto'] ?? '') !!}</div></div>
        </div>
    @elseif($t === 'galeria')
        @php($items = array_values(array_filter($d['items'] ?? [], fn($i) => !empty($i['imagen']))))
        @if(count($items) > 0)
        <div class="t-block t-galeria {{ $full ? 'is-full is-bleed' : '' }}">
            <div class="t-galeria-head">@if(!empty($d['eyebrow']))<span class="t-eyebrow">{{ $d['eyebrow'] }}</span>@endif@if(!empty($d['titulo']))<div class="t-galeria-tit" style="font-size:{{ $hsz }}px">{!! bloque_str($d['titulo']) !!}</div>@endif</div>
            <div class="t-galeria-grid">
                @foreach($items as $it)
                    @php($iu = \Illuminate\Support\Facades\Storage::disk('public')->url($it['imagen']))
                    <div class="t-galeria-img"><img src="{{ $iu }}" alt="{{ $it['alt'] ?? '' }}" style="{{ $istyle }}"></div>
                @endforeach
            </div>
        </div>
        @endif
    @elseif($t === 'video')
        @php($vu = trim((string)($d['video_url'] ?? '')))
        @php($prov = $d['proveedor'] ?? 'youtube')
        @php($vid = $vu)
        @php(($prov === 'youtube' && preg_match('~(?:v=|youtu\.be/|embed/|shorts/)([A-Za-z0-9_-]{6,})~', $vu, $m)) ? ($vid = $m[1]) : null)
        @php(($prov === 'vimeo' && preg_match('~vimeo\.com/(?:video/)?(\d+)~', $vu, $m)) ? ($vid = $m[1]) : null)
        @php(($prov === 'dailymotion' && preg_match('~dailymotion\.com/(?:video/|embed/video/)?([A-Za-z0-9]+)~', $vu, $m)) ? ($vid = $m[1]) : null)
        @php($embed = $prov === 'youtube' ? 'https://www.youtube.com/embed/'.$vid.'?autoplay=1&mute=1&loop=1&playlist='.$vid.'&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1' : ($prov === 'vimeo' ? 'https://player.vimeo.com/video/'.$vid.'?autoplay=1&muted=1&loop=1&background=1' : 'https://www.dailymotion.com/embed/video/'.$vid.'?autoplay=1&mute=1&loop=1&controls=0&ui-logo=0'))
        @php($vstyle = (!empty($d['alto']) ? 'min-height:'.intval($d['alto']).'px;' : '') . ((isset($d['radio']) && (int)$d['radio'] > 0) ? 'border-radius:'.intval($d['radio']).'px;overflow:hidden;' : ''))
        <div class="t-block t-vbg t-vbg--{{ $d['tono'] ?? 'claro' }} ph-{{ $d['pos_h'] ?? 'centro' }} pv-{{ $d['pos_v'] ?? 'centro' }} {{ $full ? 'is-full is-bleed' : '' }}" style="{{ $vstyle }}">
            <div class="t-vbg-media"><iframe src="{{ $embed }}" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy" title="video"></iframe><span class="t-vbg-ov"></span></div>
            <div class="t-vbg-in">
                @if(!empty($d['titulo']))<h2 style="font-size:{{ $hsz }}px">{{ bloque_str($d['titulo']) }}</h2>@endif
                @if(!empty($d['subtitulo']))<p class="t-vbg-sub" style="font-size:{{ $tsz }}px">{{ bloque_str($d['subtitulo']) }}</p>@endif
                @if(!empty($d['texto']))<p style="font-size:{{ $tsz }}px">{{ $d['texto'] }}</p>@endif
                @if((!empty($d['b1_texto']) && !empty($d['b1_url'])) || (!empty($d['b2_texto']) && !empty($d['b2_url'])))
                <div class="t-vbg-btns">
                    @if(!empty($d['b1_texto']) && !empty($d['b1_url']))<a class="t-vbg-btn is-solid" href="{{ $d['b1_url'] }}">{{ $d['b1_texto'] }}</a>@endif
                    @if(!empty($d['b2_texto']) && !empty($d['b2_url']))<a class="t-vbg-btn" href="{{ $d['b2_url'] }}">{{ $d['b2_texto'] }}</a>@endif
                </div>
                @endif
            </div>
        </div>
    @elseif($t === 'slider')
        @php($slides = array_values(array_filter($d['slides'] ?? [], fn($s) => !empty($s['imagen']))))
        @php($iv = (int)($d['intervalo'] ?? 5))
        @php($sstyle = (!empty($d['alto']) ? 'min-height:'.intval($d['alto']).'px;' : '') . ((isset($d['radio']) && (int)$d['radio'] > 0) ? 'border-radius:'.intval($d['radio']).'px;overflow:hidden;' : ''))
        @if(count($slides) > 0)
        <div class="t-block t-slider {{ $full ? 'is-full is-bleed' : '' }}" data-interval="{{ $iv * 1000 }}" style="{{ $sstyle }}">
            @foreach($slides as $i => $s)
                @php($su = \Illuminate\Support\Facades\Storage::disk('public')->url($s['imagen']))
                <div class="t-slide t-vbg--{{ $s['tono'] ?? 'claro' }} ph-{{ $s['pos_h'] ?? 'izq' }} pv-{{ $s['pos_v'] ?? 'abajo' }} {{ $i === 0 ? 'is-active' : '' }}" style="background-image:url('{{ $su }}')">
                    <div class="t-slide-in">
                        @if(!empty($s['titulo']))<h2 style="font-size:{{ $hsz }}px">{{ bloque_str($s['titulo']) }}</h2>@endif
                        @if(!empty($s['subtitulo']))<p class="t-vbg-sub" style="font-size:{{ $tsz }}px">{{ bloque_str($s['subtitulo']) }}</p>@endif
                        @if(!empty($s['texto']))<p style="font-size:{{ $tsz }}px">{{ bloque_str($s['texto']) }}</p>@endif
                        @if((!empty($s['b1_texto']) && !empty($s['b1_url'])) || (!empty($s['b2_texto']) && !empty($s['b2_url'])))
                        <div class="t-vbg-btns">
                            @if(!empty($s['b1_texto']) && !empty($s['b1_url']))<a class="t-vbg-btn is-solid" href="{{ $s['b1_url'] }}">{{ $s['b1_texto'] }}</a>@endif
                            @if(!empty($s['b2_texto']) && !empty($s['b2_url']))<a class="t-vbg-btn" href="{{ $s['b2_url'] }}">{{ $s['b2_texto'] }}</a>@endif
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
            @if(count($slides) > 1)
            <button type="button" class="t-slider-prev" aria-label="Anterior">&#8249;</button>
            <button type="button" class="t-slider-next" aria-label="Siguiente">&#8250;</button>
            <div class="t-slider-dots">@foreach($slides as $i => $s)<button type="button" class="t-slider-dot {{ $i === 0 ? 'is-active' : '' }}" aria-label="Ir a {{ $i + 1 }}"></button>@endforeach</div>
            @endif
        </div>
        @endif
    @elseif($t === 'productos')
        @php($ids = array_values(array_filter($d['productos'] ?? [])))
        @php($lim = max(1, (int)($d['limite'] ?? 6)))
        @php($q = \App\Models\Producto::where('activo', true))
        @php(!empty($ids) ? $q->whereIn('id', $ids) : (!empty($d['categoria_id']) ? $q->where('categoria_id', $d['categoria_id']) : null))
        @php($prods = $q->take($lim)->get())
        @if($prods->count() > 0)
        <div class="t-block t-prodrec">
            @if(!empty($d['titulo']))<h3 class="t-prodrec-tit">{{ bloque_str($d['titulo']) }}</h3>@endif
            <div class="t-prodrec-row">
                @foreach($prods as $p)
                    <a class="t-prodrec-card" href="{{ route('tienda.producto', $p->slug) }}">
                        <div class="t-prodrec-img">@if($p->imagen_principal)<img src="{{ $p->imagen_principal }}" alt="{{ $p->nombre }}" loading="lazy">@endif</div>
                        <div class="t-prodrec-name">{{ $p->nombre }}</div>
                        <div class="t-prodrec-price">${{ number_format($p->precio, 2) }}</div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    @elseif($t === 'tabla')
        @php($filasRaw = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', trim((string)($d['contenido'] ?? '')))), fn($x) => $x !== '')))
        @if(count($filasRaw) > 0)
        <div class="t-block t-block-tabla {{ $full }}">
            <div class="t-tabla-scroll">
            <table>
                <thead><tr>@foreach(explode('|', $filasRaw[0]) as $c)<th>{{ trim($c) }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach(array_slice($filasRaw, 1) as $fila)
                    <tr>@foreach(explode('|', $fila) as $c)<td>{{ trim($c) }}</td>@endforeach</tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif
    @elseif($t === 'columnas')
        @php($cant = (int)($d['cantidad'] ?? 2))
        <div class="t-block t-cols t-cols--{{ $cant }} {{ $full }}" style="font-size:{{ $tsz }}px">
            @foreach(($d['items'] ?? []) as $col)
                @php($cu = !empty($col['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($col['imagen']) : null)
                <div class="t-col">
                    @if($cu)<img src="{{ $cu }}" alt="" style="{{ $istyle }}">@endif
                    <div>{!! bloque_str($col['texto'] ?? '') !!}</div>
                </div>
            @endforeach
        </div>
    @elseif($t === 'texto_imagen')
        @php($tu = !empty($d['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($d['imagen']) : null)
        @php($bg = !empty($d['fondo']) ? 'background:'.$d['fondo'].';' : '')
        <div class="t-block t-ti {{ ($d['posicion'] ?? 'izq') === 'der' ? 'is-der' : '' }} {{ !empty($d['fondo']) ? 'has-bg' : '' }} {{ $full ? 'is-full is-bleed' : '' }}" style="{{ $bg }}">
            <div class="t-ti-img">@if($tu)<img src="{{ $tu }}" alt="" style="{{ $istyle }}">@endif</div>
            <div class="t-ti-txt" style="font-size:{{ $tsz }}px">{!! bloque_str($d['texto'] ?? '') !!}</div>
        </div>
    @elseif($t === 'hero')
        @php($hu = !empty($d['imagen']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($d['imagen']) : null)
        @php($hbg = $hu ? "background-image:linear-gradient(rgba(0,0,0,.28),rgba(0,0,0,.28)),url('".$hu."');background-size:cover;background-position:center;" : (!empty($d['fondo']) ? 'background:'.$d['fondo'].';' : 'background:#161921;'))
        @php($hstyle = $hbg . (!empty($d['alto']) ? 'min-height:'.intval($d['alto']).'px;' : '') . ((isset($d['radio']) && (int)$d['radio'] > 0) ? 'border-radius:'.intval($d['radio']).'px;overflow:hidden;' : ''))
        <div class="t-block t-bhero t-bhero--{{ $d['tono'] ?? 'claro' }} ph-{{ $d['pos_h'] ?? 'izq' }} pv-{{ $d['pos_v'] ?? 'abajo' }} {{ $full ? 'is-full' : '' }}" style="{{ $hstyle }}">
            <div class="t-bhero-in">
                @if(!empty($d['titulo']))<h2 style="font-size:{{ $hsz }}px">{{ bloque_str($d['titulo']) }}</h2>@endif
                @if(!empty($d['texto']))<p style="font-size:{{ $tsz }}px">{{ $d['texto'] }}</p>@endif
                @if(!empty($d['boton_texto']) && !empty($d['boton_url']))<a class="t-bhero-btn" href="{{ $d['boton_url'] }}">{{ $d['boton_texto'] }}</a>@endif
            </div>
        </div>
    @elseif($t === 'formulario_contacto')
        @include('tienda.partials.formulario-contacto', ['slug' => $d['formulario_slug'] ?? 'contacto'])
    @elseif($t === 'botones')
        @php($bs = array_values($d['items'] ?? []))
        @php($n = count($bs))
        @if($n > 0)
        <div class="t-block t-ctas t-ctas--{{ $n }} {{ $full }}">
            @foreach($bs as $bt)
                <div class="t-cta">
                    @if(!empty($bt['titulo']))<h4>{{ bloque_str($bt['titulo']) }}</h4>@endif
                    @if(!empty($bt['texto']))<p>{{ bloque_str($bt['texto']) }}</p>@endif
                    @if(!empty($bt['boton_texto']) && !empty($bt['url']))<a class="t-cta-btn" href="{{ $bt['url'] }}">{{ $bt['boton_texto'] }}</a>@endif
                </div>
            @endforeach
        </div>
        @endif
    @endif
@endforeach
@once
@push('scripts')
<script>
(function(){
  Array.prototype.forEach.call(document.querySelectorAll('.t-slider'), function(sl){
    var slides = Array.prototype.slice.call(sl.querySelectorAll('.t-slide'));
    if (slides.length < 2) return;
    var dots = Array.prototype.slice.call(sl.querySelectorAll('.t-slider-dot'));
    var i = 0, iv = parseInt(sl.getAttribute('data-interval'), 10) || 0, timer = null;
    function go(n){ slides[i].classList.remove('is-active'); if(dots[i]) dots[i].classList.remove('is-active'); i = (n + slides.length) % slides.length; slides[i].classList.add('is-active'); if(dots[i]) dots[i].classList.add('is-active'); }
    function reset(){ if(timer){ clearInterval(timer); timer = null; } if(iv > 0){ timer = setInterval(function(){ go(i + 1); }, iv); } }
    var bn = sl.querySelector('.t-slider-next'), bp = sl.querySelector('.t-slider-prev');
    if(bn) bn.addEventListener('click', function(){ go(i + 1); reset(); });
    if(bp) bp.addEventListener('click', function(){ go(i - 1); reset(); });
    dots.forEach(function(dt, k){ dt.addEventListener('click', function(){ go(k); reset(); }); });
    sl.addEventListener('mouseenter', function(){ if(timer){ clearInterval(timer); timer = null; } });
    sl.addEventListener('mouseleave', reset);
    reset();
  });
})();
</script>
@endpush
@endonce