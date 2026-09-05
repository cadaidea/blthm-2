@if(($posts ?? null) && $posts->count())
<section class="t-wrap t-section t-home-posts">
    <div class="t-section-head">
        <h2 class="t-h2" style="margin:0">Diario</h2>
        <a class="t-btn" href="{{ route('blog.index') }}">Ver todos</a>
    </div>
    <div class="t-posts-grid">
        @foreach($posts as $post)
            <a class="t-post-card" href="{{ $post->url }}">
                <div class="t-post-img">@if($post->imagen_url)<img src="{{ $post->imagen_url }}" alt="{{ $post->titulo }}" loading="lazy">@endif</div>
                <div class="t-post-body">
                    @if($post->categoria)<span class="t-post-cat">{{ $post->categoria->nombre }}</span>@endif
                    <h3 class="t-post-title">{{ $post->titulo }}</h3>
                    @if($post->extracto)<p class="t-post-ex">{{ \Illuminate\Support\Str::limit($post->extracto, 110) }}</p>@endif
                    <span class="t-post-meta">{{ $post->minutos_lectura }} min de lectura</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif
