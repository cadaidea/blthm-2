@php($img = $producto->imagen_principal)
@php($img2 = optional($producto->imagenes->slice(1)->first())->url)
@php($colores = $producto->variantes->pluck('color')->filter()->unique()->take(6)->values())
<a class="t-card" href="{{ route('tienda.producto', $producto->slug) }}">
    <div class="t-card-img">
        @if($img)
            <img class="t-card-i1" src="{{ $img }}" alt="{{ $producto->nombre }}" loading="lazy">
            @if($img2)<img class="t-card-i2" src="{{ $img2 }}" alt="" loading="lazy" aria-hidden="true">@endif
        @else
            <div class="t-card-noimg">Sin imagen</div>
        @endif
        <div class="t-card-actions">
            @include('tienda.partials.wish-btn', ['producto' => $producto])
            @include('tienda.partials.share-btn', ['producto' => $producto])
        </div>
        @if($colores->count())
            <div class="t-card-sw">
                @foreach($colores as $c)<span class="t-card-sw-dot" style="background:{{ $c }}"></span>@endforeach
            </div>
        @endif
    </div>
    <div class="t-card-body">
        <h3 class="t-card-name">{{ $producto->nombre }}</h3>
        <p class="t-card-price">${{ number_format($producto->precio, 2) }}<span class="t-card-iva">Incluido IVA</span></p>
    </div>
</a>
