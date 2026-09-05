<!doctype html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ \App\Models\Ajuste::get('color_primario') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">

    @php($A = fn($k, $d = null) => \App\Models\Ajuste::get($k) ?: $d)
    @php($marca = $A('marca', config('tienda.marca')))
    @php($eslogan = $A('eslogan', config('tienda.eslogan')))
    @php($telefono = $A('telefono', config('tienda.telefono')))
    @php($ciudad = $A('ciudad', config('tienda.ciudad')))
    @php($provincia = $A('provincia'))
    @php($pais = $A('pais', config('tienda.pais')))
    @php($logoRaw = $A('logo') ?: $A('favicon'))
    @php($logoUrl = $logoRaw ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoRaw) : null)
    @php($ogRaw = $A('og_image'))
    @php($ogDefault = $ogRaw ? \Illuminate\Support\Facades\Storage::disk('public')->url($ogRaw) : $logoUrl)
    @php($sameas = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $A('sameas', ''))))))

    <title>@yield('title', $marca . ($eslogan ? ' · ' . $eslogan : ''))</title>
    <meta name="description" content="@yield('meta_description', $A('meta_home', $eslogan))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $marca }}">
    <meta property="og:title" content="@yield('title', $marca . ($eslogan ? ' · ' . $eslogan : ''))">
    <meta property="og:description" content="@yield('meta_description', $A('meta_home', $eslogan))">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @elseif($ogDefault)
        <meta property="og:image" content="{{ $ogDefault }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="robots" content="index, follow, max-image-preview:large">

    @php($fav = $A('favicon'))
    <link rel="icon" href="{{ $fav ? \Illuminate\Support\Facades\Storage::disk('public')->url($fav) : '/favicon.ico' }}" sizes="any">

    @php($ldOrg = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        '@id' => url('/') . '#org',
        'name' => $marca,
        'url' => url('/'),
        'logo' => $logoUrl,
        'image' => $ogDefault,
        'telephone' => $telefono ?: null,
        'address' => array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => $ciudad,
            'addressRegion' => $provincia ?: null,
            'addressCountry' => $pais,
        ]),
        'sameAs' => $sameas ?: null,
    ]))
    @php($ldWeb = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $marca,
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/buscar') . '?q={search_term_string}'],
            'query-input' => 'required name=search_term_string',
        ],
    ])
    <script type="application/ld+json">
    {!! json_encode($ldOrg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($ldWeb, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @stack('jsonld')

    <link rel="stylesheet" href="{{ asset('css/tienda.css') }}?v={{ filemtime(public_path('css/tienda.css')) ?? 1 }}">
    <link rel="stylesheet" href="{{ asset('css/editorjs-field.css') }}?v={{ filemtime(public_path('css/editorjs-field.css')) ?? 1 }}">
    <style>:root{
        --brand: {{ \App\Models\Ajuste::get('color_primario') }};
        --accent: {{ \App\Models\Ajuste::get('color_secundario') }};
        --footer-bg: {{ \App\Models\Ajuste::get('color_footer') }};
    }</style>
    @stack('styles')

    @php($gtm = $A('gtm_id', 'GTM-WQD5352P'))
    @php($ga = $A('ga_id', 'G-V4N0950V9K'))
    @if($ga || $gtm)
    <!-- Consent Mode v2: por defecto denegado hasta que el usuario elija en el banner de cookies -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      (function(){
        var c = null;
        try { c = JSON.parse(localStorage.getItem('bletia_consent') || 'null'); } catch(e){}
        var analiticas = c && c.analiticas ? 'granted' : 'denied';
        var marketing = c && c.marketing ? 'granted' : 'denied';
        gtag('consent', 'default', {
          'ad_storage': marketing,
          'ad_user_data': marketing,
          'ad_personalization': marketing,
          'analytics_storage': analiticas
        });
      })();
      window.addEventListener('bletia-consent', function(e){
        var c = e.detail || {};
        var analiticas = c.analiticas ? 'granted' : 'denied';
        var marketing = c.marketing ? 'granted' : 'denied';
        gtag('consent', 'update', {
          'ad_storage': marketing,
          'ad_user_data': marketing,
          'ad_personalization': marketing,
          'analytics_storage': analiticas
        });
      });
    </script>
    @endif
    @if($ga)
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
    <script>
      gtag('js', new Date());
      gtag('config', '{{ $ga }}');
    </script>
    @endif
    @if($gtm)
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtm }}');</script>
    <!-- End Google Tag Manager -->
    @endif
</head>
<body class="@yield('body_class')">
    @if($gtm)
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtm }}"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @endif
    @php($esHome = request()->routeIs('tienda.home'))
    @include('tienda.partials.header', ['esHome' => $esHome, 'tipo' => $headerTipo ?? 'tienda', 'hdata' => $headerData ?? []])
    @include('tienda.partials.search')

    <main class="t-main @if($esHome) t-main--home @else t-wrap @endif">
        @if(session('ok'))
        <div class="t-flash" id="t-flash-msg">
            <span class="t-flash-close" onclick="document.getElementById('t-flash-msg').remove()">&times;</span>
            <span class="t-flash-label">Confirmado</span>
            <span class="t-flash-txt">{{ session('ok') }}</span>
        </div>
        <script>setTimeout(function(){ var el = document.getElementById('t-flash-msg'); if (el) el.style.display = 'none'; }, 6000);</script>
        @endif
        @yield('content')
    </main>

    @include('tienda.partials.footer')
    @if(!session('cliente_id'))@include('tienda.partials.auth-modal')@endif

    <div id="t-lightbox" class="t-lightbox" aria-hidden="true">
        <span class="t-lb-close">&times;</span>
        <img id="t-lb-img" src="" alt="">
    </div>

    <script src="{{ asset('js/tienda.js') }}?v={{ filemtime(public_path('js/tienda.js')) ?? 1 }}"></script>
    @stack('scripts')
    @include('tienda.partials.digest-forms')
    @include('tienda.partials.cookies')
</body>
</html>
