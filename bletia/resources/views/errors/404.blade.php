@extends('tienda.layout')

@section('title', 'Página no encontrada · ' . \App\Models\Ajuste::get('marca'))
@section('meta_description', 'La página que buscas no existe o fue movida. Explora nuestro catálogo de muebles o usa el buscador.')
@section('body_class', 'is-404')

@php
    $cats404 = \App\Models\Categoria::where('activo', true)->orderBy('orden')->take(8)->get();
@endphp

@section('content')
<section class="err404">
    <div class="err404__wrap">
        <p class="err404__code">404</p>
        <h1 class="err404__title">No encontramos esta página</h1>
        <p class="err404__text">Puede que el enlace esté roto o que el producto ya no esté disponible. Prueba buscar lo que necesitas o vuelve al catálogo.</p>

        <form action="{{ route('tienda.buscar') }}" method="GET" class="err404__search" role="search">
            <input type="search" name="q" class="err404__search-input" placeholder="Buscar muebles, categorías…" autocomplete="off" aria-label="Buscar">
            <button type="submit" class="err404__search-btn" aria-label="Buscar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </button>
        </form>

        <div class="err404__actions">
            <a href="{{ route('tienda.home') }}" class="err404__btn err404__btn--primary">Ir al inicio</a>
            <a href="{{ route('tienda.shop') }}" class="err404__btn">Ver tienda</a>
            <a href="{{ route('blog.index') }}" class="err404__btn">Blog</a>
        </div>

        @if($cats404->isNotEmpty())
        <div class="err404__cats">
            <span class="err404__cats-lbl">Categorías populares</span>
            <div class="err404__cats-list">
                @foreach($cats404 as $c)
                    <a href="{{ route('tienda.categoria', $c->slug) }}" class="err404__cat">{{ $c->nombre }}</a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

<style>
.err404{padding:clamp(48px,9vw,110px) 20px;display:flex;justify-content:center}
.err404__wrap{width:100%;max-width:640px;text-align:center}
.err404__code{font-size:clamp(64px,16vw,132px);line-height:1;font-weight:800;margin:0;color:var(--color-primario,#1a1a1a);opacity:.12;letter-spacing:-.03em}
.err404__title{font-size:clamp(1.6rem,4.5vw,2.4rem);font-weight:700;margin:.2em 0 .35em;color:var(--color-texto,#1a1a1a)}
.err404__text{font-size:1.05rem;line-height:1.6;color:var(--color-muted,#5b6670);margin:0 auto 2rem;max-width:52ch}
.err404__search{display:flex;align-items:center;max-width:440px;margin:0 auto 1.6rem;border:1px solid var(--color-linea,#e3e6ea);border-radius:999px;overflow:hidden;background:#fff;box-shadow:0 1px 3px rgba(16,24,40,.06)}
.err404__search-input{flex:1;border:0;outline:0;padding:14px 20px;font-size:1rem;background:transparent;color:inherit}
.err404__search-btn{border:0;background:var(--color-primario,#1a1a1a);color:#fff;padding:0 20px;height:100%;min-height:50px;cursor:pointer;display:flex;align-items:center;transition:opacity .2s}
.err404__search-btn:hover{opacity:.85}
.err404__actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-bottom:2.6rem}
.err404__btn{display:inline-flex;align-items:center;padding:11px 22px;border-radius:999px;font-weight:600;font-size:.95rem;text-decoration:none;border:1px solid var(--color-linea,#e3e6ea);color:var(--color-texto,#1a1a1a);background:#fff;transition:all .2s}
.err404__btn:hover{border-color:var(--color-primario,#1a1a1a);transform:translateY(-1px)}
.err404__btn--primary{background:var(--color-primario,#1a1a1a);color:#fff;border-color:var(--color-primario,#1a1a1a)}
.err404__btn--primary:hover{opacity:.9}
.err404__cats-lbl{display:block;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--color-muted,#8a949e);margin-bottom:.9rem;font-weight:600}
.err404__cats-list{display:flex;gap:9px;justify-content:center;flex-wrap:wrap}
.err404__cat{padding:7px 15px;border-radius:999px;background:var(--color-fondo-suave,#f5f6f8);color:var(--color-texto,#333);text-decoration:none;font-size:.9rem;transition:background .2s}
.err404__cat:hover{background:var(--color-linea,#e3e6ea)}
</style>
@endsection
