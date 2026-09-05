<button type="button" class="t-card-share" data-url="{{ route('tienda.producto', $producto->slug) }}" data-nombre="{{ $producto->nombre }}" aria-label="Compartir">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
        <path d="M8.6 10.5l6.8-3.9M8.6 13.5l6.8 3.9"/>
    </svg>
</button>
<style>
.t-share-pop{position:fixed;z-index:9999;background:#fff;border-radius:2px;box-shadow:0 24px 60px -12px rgba(22,25,33,.28);padding:8px;min-width:200px;font-family:var(--font);opacity:0;transform:translateY(-6px);transition:opacity .2s,transform .2s}
.t-share-pop.is-open{opacity:1;transform:translateY(0)}
.t-share-pop a,.t-share-pop button{display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border:0;background:none;text-align:left;font-size:14px;color:var(--brand,#161921);cursor:pointer;border-radius:2px;font-family:inherit;text-decoration:none}
.t-share-pop a:hover,.t-share-pop button:hover{background:#f7f5ef}
.t-share-backdrop{position:fixed;inset:0;z-index:9998;background:transparent}
</style>
<script>
(function(){
    if (window.__tShareBound) return;
    window.__tShareBound = true;

    function cerrarPopup() {
        var p = document.getElementById('t-share-pop');
        var bd = document.getElementById('t-share-backdrop');
        if (p) p.remove();
        if (bd) bd.remove();
    }

    function abrirPopup(btn) {
        cerrarPopup();
        var url = btn.dataset.url;
        var nombre = btn.dataset.nombre || '';
        var texto = encodeURIComponent(nombre);
        var urlEnc = encodeURIComponent(url);

        var bd = document.createElement('div');
        bd.id = 't-share-backdrop';
        bd.className = 't-share-backdrop';
        document.body.appendChild(bd);

        var pop = document.createElement('div');
        pop.id = 't-share-pop';
        pop.className = 't-share-pop';
        pop.innerHTML =
            '<a href="https://wa.me/?text=' + texto + '%20' + urlEnc + '" target="_blank" rel="noopener">WhatsApp</a>' +
            '<a href="https://www.facebook.com/sharer/sharer.php?u=' + urlEnc + '" target="_blank" rel="noopener">Facebook</a>' +
            '<a href="mailto:?subject=' + texto + '&body=' + urlEnc + '">Correo</a>' +
            '<button type="button" id="t-share-copiar">Copiar enlace</button>';
        document.body.appendChild(pop);

        var rect = btn.getBoundingClientRect();
        var top = rect.bottom + 8;
        var left = Math.min(rect.left, window.innerWidth - 216);
        pop.style.top = top + 'px';
        pop.style.left = Math.max(8, left) + 'px';
        requestAnimationFrame(function(){ pop.classList.add('is-open'); });

        bd.addEventListener('click', cerrarPopup);
        document.getElementById('t-share-copiar').addEventListener('click', function(){
            navigator.clipboard.writeText(url).then(function(){
                document.getElementById('t-share-copiar').textContent = '¡Enlace copiado!';
                setTimeout(cerrarPopup, 900);
            });
        });
    }

    document.addEventListener('click', function(e){
        var btn = e.target.closest('.t-card-share');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            abrirPopup(btn);
            return;
        }
        if (!e.target.closest('#t-share-pop')) cerrarPopup();
    });
})();
</script>
