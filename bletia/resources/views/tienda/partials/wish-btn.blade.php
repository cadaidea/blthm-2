@php
    $clienteWishId = session('cliente_id');
    $yaGuardado = $clienteWishId
        ? \Illuminate\Support\Facades\DB::table('guardados')->where('cliente_id', $clienteWishId)->where('producto_id', $producto->id)->exists()
        : false;
@endphp
<button type="button" class="t-wish {{ $yaGuardado ? 'is-on' : '' }}"
        data-url="{{ route('guardar.toggle', $producto->slug) }}"
        data-login="{{ route('cuenta.login') }}"
        data-auth="{{ $clienteWishId ? '1' : '0' }}"
        aria-pressed="{{ $yaGuardado ? 'true' : 'false' }}"
        aria-label="Guardar producto">
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>
    </svg>
</button>
<style>
.t-wish{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;padding:0;border:1px solid var(--color-linea,#e3e6ea);border-radius:999px;background:#fff;color:var(--color-texto,#333);cursor:pointer;transition:all .18s}
.t-wish:hover{border-color:var(--accent);color:var(--accent)}
.t-wish.is-on{border-color:var(--accent);color:var(--accent)}
.t-wish.is-on svg{fill:var(--accent)}
.t-wish svg{transition:fill .18s}
</style>
<script>
(function(){
    if (window.__tWishBound) return;
    window.__tWishBound = true;

    function actualizarBadgeHeader(total) {
        var badge = document.getElementById('t-wish-header-badge');
        if (!badge) return;
        badge.textContent = total;
        badge.style.display = total > 0 ? '' : 'none';
    }

    document.addEventListener('click', function(e){
        var b = e.target.closest('.t-wish');
        if (!b) return;
        e.preventDefault();
        e.stopPropagation();
        if (b.dataset.busy) return;
        if (b.dataset.auth !== '1'){ window.location = b.dataset.login; return; }
        b.dataset.busy = '1';
        fetch(b.dataset.url, {
            method:'POST',
            headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}
        }).then(function(r){ return r.json(); }).then(function(d){
            delete b.dataset.busy;
            if (!d.ok){ if(d.auth===false) window.location=b.dataset.login; return; }
            var on = d.guardado;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on ? 'true':'false');
            if (typeof d.total !== 'undefined') actualizarBadgeHeader(d.total);
        }).catch(function(){ delete b.dataset.busy; });
    });
})();
</script>
