@php
    use App\Models\Categoria;
    $cols = Categoria::where('activo', true)->orderBy('orden')->take(6)->get();
    $lista = function ($clave) {
        $raw = \App\Models\Ajuste::get($clave);
        $arr = $raw ? json_decode($raw, true) : null;
        return is_array($arr) ? array_filter($arr, fn ($i) => !empty($i['titulo']) && !empty($i['url'])) : [];
    };
    $nosotros = $lista('footer_nosotros');
    $legal    = $lista('footer_legal');
    $recursos = $lista('footer_recursos');
    $texto    = \App\Models\Ajuste::get('footer_texto') ?: 'Mobiliario y decoración de autor. Piezas seleccionadas para espacios con carácter.';
@endphp
@php($footerImg = \App\Models\Ajuste::get('footer_img'))
@php($footerImgUrl = $footerImg ? \Illuminate\Support\Facades\Storage::disk('public')->url($footerImg) : null)
@php($footerBg = \App\Models\Ajuste::get('footer_bg'))
@php($footerTxt = \App\Models\Ajuste::get('footer_text'))
@php($ftStyle = ($footerBg ? 'background:'.$footerBg.';' : '') . ($footerTxt ? 'color:'.$footerTxt.';' : ''))
<footer class="t-footer {{ $footerTxt ? 't-footer--ctext' : '' }}" style="{{ $ftStyle }}">
    <div class="t-wrap">
        <div class="t-footer-grid">
            <div class="t-footer-brand">
                @if($footerImgUrl)<img class="t-footer-logo" src="{{ $footerImgUrl }}" alt="{{ config('tienda.marca') }}" loading="lazy">@else<span class="t-brand">{{ config('tienda.marca') }}</span>@endif
                <p>{{ $texto }}</p>
                <form method="post" action="{{ route('newsletter') }}" class="t-news">
                    @csrf
                    <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                    <input type="email" name="email" placeholder="Tu correo" required>
                    @if(\App\Models\Ajuste::get('turnstile_activo') === '1' && \App\Models\Ajuste::get('turnstile_site_key'))
                    <div class="cf-turnstile" data-sitekey="{{ \App\Models\Ajuste::get('turnstile_site_key') }}" data-theme="light" data-size="flexible"></div>
                    @endif
                    <button type="submit" class="t-btn t-btn--footer">Suscribirme</button>
                </form>
                @if(\App\Models\Ajuste::get('turnstile_activo') === '1' && \App\Models\Ajuste::get('turnstile_site_key'))
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                @endif
            </div>
            @if(count($nosotros))
            <div>
                <h4>Nosotros</h4>
                <ul>@foreach($nosotros as $i)<li><a href="{{ $i['url'] }}">{{ $i['titulo'] }}</a></li>@endforeach</ul>
            </div>
            @endif
            <div>
                <h4>Colecciones</h4>
                <ul>@foreach($cols as $c)<li><a href="{{ route('tienda.categoria', $c->slug) }}">{{ $c->nombre }}</a></li>@endforeach</ul>
            </div>
            @if(count($recursos))
            <div>
                <h4>Recursos</h4>
                <ul>@foreach($recursos as $i)<li><a href="{{ $i['url'] }}">{{ $i['titulo'] }}</a></li>@endforeach</ul>
            </div>
            @endif
        </div>
        <div class="t-footer-bottom">
            <span>&copy; {{ date('Y') }} {{ config('tienda.marca') }} · {{ config('tienda.ciudad') }}, Ecuador</span>
            @if(count($legal))
            <nav class="t-footer-legal">@foreach($legal as $i)<a href="{{ $i['url'] }}">{{ $i['titulo'] }}</a>@endforeach</nav>
            @endif
            <span>Hecho con cariño en Cuenca</span>
        </div>
    </div>
</footer>
