<a class="t-art-card" href="{{ $a->url }}">
    <div class="t-art-card-img">
        @if($a->imagen_url)<img src="{{ $a->imagen_url }}" alt="{{ $a->titulo }}" loading="lazy">@endif
    </div>
    @if($a->categoria)<p class="t-art-card-cat">{{ $a->categoria->nombre }}</p>@endif
    <h3 class="t-art-card-tit">{{ $a->titulo }}</h3>
    @if($a->extracto)<p class="t-art-card-ex">{{ \Illuminate\Support\Str::limit($a->extracto, 110) }}</p>@endif
    <p class="t-art-meta">{{ optional($a->publicado_at)->format('d M Y') }}@if($a->autor) · {{ $a->autor }}@endif</p>
</a>
