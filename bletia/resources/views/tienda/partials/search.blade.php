<div class="t-search" id="t-search" aria-hidden="true">
    <div class="t-search-box">
        <form action="{{ url('/buscar') }}" method="get">
            <input type="search" name="q" id="t-search-input" placeholder="Buscar productos, blog, páginas…" autocomplete="off">
        </form>
        <button class="t-search-close" id="t-close-search" aria-label="Cerrar">✕</button>
        <div id="t-search-results" class="t-search-results"></div>
        <p class="t-search-hint">Escribe y presiona Enter para ver todos los resultados.</p>
    </div>
</div>
