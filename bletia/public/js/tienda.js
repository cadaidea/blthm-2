(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
  /* Header transparente -> sólido (home) */
  var hd = $('#t-hd');
  if (hd && hd.dataset.home) {
    var onScroll = function () { hd.classList.toggle('is-scrolled', window.scrollY > 24); };
    window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  }
  /* Menú móvil (drawer popup) */
  var burger = $('#t-open-menu'), drawer = $('#t-drawer'), closeM = $('#t-close-menu');
  function openDrawer() { if (drawer) { drawer.classList.add('is-open'); drawer.setAttribute('aria-hidden', 'false'); document.body.classList.add('t-noscroll'); } }
  function closeDrawer() { if (drawer) { drawer.classList.remove('is-open'); drawer.setAttribute('aria-hidden', 'true'); document.body.classList.remove('t-noscroll'); } }
  if (burger) burger.addEventListener('click', openDrawer);
  if (closeM) closeM.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
  /* Búsqueda popup */
  var search = $('#t-search'), openS = $('#t-open-search'), closeS = $('#t-close-search'), input = $('#t-search-input'), results = $('#t-search-results');
  if (openS && search) {
    openS.addEventListener('click', function () { search.classList.add('is-open'); if (input) input.focus(); });
    closeS && closeS.addEventListener('click', function () { search.classList.remove('is-open'); });
    search.addEventListener('click', function (e) { if (e.target === search) search.classList.remove('is-open'); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') search.classList.remove('is-open'); });
  }
  if (input && results) {
    var t;
    input.addEventListener('input', function () {
      var q = input.value.trim(); clearTimeout(t);
      if (q.length < 2) { results.innerHTML = ''; return; }
      t = setTimeout(function () {
        fetch('/buscar/api?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            var html = '';
            function grupo(titulo, arr, conImg) {
              if (!arr || !arr.length) return '';
              var h = '<div class="t-sr-group"><div class="t-sr-title">' + titulo + '</div>';
              h += arr.map(function (p) {
                var img = (conImg && p.img) ? '<img src="' + p.img + '" alt="">' : '';
                var meta = p.precio ? '<br>$' + p.precio : '';
                return '<a class="t-sr-item" href="' + p.url + '">' + img + '<span><strong>' + p.nombre + '</strong>' + meta + '</span></a>';
              }).join('');
              return h + '</div>';
            }
            html += grupo('Productos', data.productos, true);
            html += grupo('Categorías', data.categorias, false);
            html += grupo('Artículos', data.articulos, false);
            html += grupo('Blog · Categorías', data.blog_categorias, false);
            html += grupo('Etiquetas', data.etiquetas, false);
            html += grupo('Autores', data.autores, false);
            html += grupo('Páginas', data.paginas, false);
            results.innerHTML = html || '<p class="t-search-hint">Sin resultados.</p>';
          });
      }, 250);
    });
  }
  /* ===== Lightbox: zoom (wheel/click) + paneo (arrastrar) ===== */
  var lb = $('#t-lightbox'), lbImg = $('#t-lb-img'), lbClose = $('.t-lb-close');
  var scale = 1, tx = 0, ty = 0, drag = false, sx = 0, sy = 0;
  function apply() { lbImg.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')'; lbImg.style.cursor = scale > 1 ? 'grab' : 'zoom-in'; }
  function abrir(src) { if (!lb || !lbImg) return; lbImg.src = src; scale = 1; tx = ty = 0; apply(); lb.classList.add('is-open'); lb.setAttribute('aria-hidden', 'false'); }
  function cerrar() { if (lb) { lb.classList.remove('is-open'); lb.setAttribute('aria-hidden', 'true'); } }
  $$('[data-zoom]').forEach(function (el) { el.addEventListener('click', function () { abrir(el.getAttribute('data-zoom') || el.src); }); });
  if (lb) {
    lbClose && lbClose.addEventListener('click', cerrar);
    lb.addEventListener('click', function (e) { if (e.target === lb) cerrar(); });
    lb.addEventListener('wheel', function (e) { e.preventDefault(); scale = Math.min(6, Math.max(1, scale + (e.deltaY < 0 ? 0.2 : -0.2))); if (scale === 1) { tx = ty = 0; } apply(); }, { passive: false });
    lbImg.addEventListener('click', function (e) { e.stopPropagation(); if (scale === 1) { scale = 2.5; } else { scale = 1; tx = ty = 0; } apply(); });
    lbImg.addEventListener('pointerdown', function (e) { if (scale <= 1) return; drag = true; sx = e.clientX - tx; sy = e.clientY - ty; lbImg.style.cursor = 'grabbing'; lbImg.setPointerCapture(e.pointerId); });
    lbImg.addEventListener('pointermove', function (e) { if (!drag) return; tx = e.clientX - sx; ty = e.clientY - sy; apply(); });
    lbImg.addEventListener('pointerup', function () { drag = false; lbImg.style.cursor = 'grab'; });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrar(); });
  }
  /* ===== Selector de combinación (producto) ===== */
  var vForm = document.getElementById('t-prod-form');
  if (vForm) {
    var main = document.getElementById('t-prod-main'), mainWrap = document.getElementById('t-prod-main-wrap');
    var priceEl = document.getElementById('t-prod-price'), ivaEl = document.getElementById('t-prod-iva');
    var hint = document.getElementById('t-sw-hint'), buyBtn = document.getElementById('t-buy-btn');
    var hidden = document.getElementById('t-variante-id');
    var ivaRate = parseFloat(vForm.getAttribute('data-iva') || '0');
    var nAttrs = parseInt(vForm.getAttribute('data-nattrs') || '0', 10);
    var vdata = [];
    try { vdata = JSON.parse(document.getElementById('t-vdata').textContent || '[]'); } catch (e) { vdata = []; }
    var selected = {};
    function money(n) { return '$' + n.toFixed(2); }
    function setPrice(pvp) {
      if (priceEl) priceEl.textContent = money(pvp);
      if (ivaEl) { var iva = pvp - pvp / (1 + ivaRate / 100); ivaEl.textContent = 'Incluido IVA (' + (ivaRate % 1 === 0 ? ivaRate : ivaRate) + '%): ' + money(iva); }
    }
    function sameKeys(op) {
      var k = Object.keys(op);
      if (k.length !== Object.keys(selected).length) return false;
      for (var i = 0; i < k.length; i++) { if (String(selected[k[i]]) !== String(op[k[i]])) return false; }
      return true;
    }
    function tryMatch() {
      if (nAttrs === 0) { if (buyBtn) buyBtn.disabled = false; return; }
      if (Object.keys(selected).length < nAttrs) {
        if (hidden) hidden.value = '';
        if (buyBtn) buyBtn.disabled = true;
        if (hint) { hint.style.display = 'block'; hint.textContent = 'Selecciona las opciones para ver el precio.'; }
        return;
      }
      var found = null;
      for (var i = 0; i < vdata.length; i++) { if (sameKeys(vdata[i].op || {})) { found = vdata[i]; break; } }
      if (found) {
        if (hidden) hidden.value = found.id;
        setPrice(parseFloat(found.pvp));
        if (found.foto && main) { main.src = found.foto; if (mainWrap) mainWrap.setAttribute('data-zoom', found.foto); }
        if (buyBtn) buyBtn.disabled = false;
        if (hint) hint.style.display = 'none';
      } else {
        if (hidden) hidden.value = '';
        if (buyBtn) buyBtn.disabled = true;
        if (hint) { hint.style.display = 'block'; hint.textContent = 'Esa combinación no está disponible.'; }
      }
    }
    // swatches
    Array.prototype.forEach.call(vForm.querySelectorAll('.t-sw-group'), function (group) {
      var aid = group.getAttribute('data-attr');
      var sel = document.getElementById('sel-' + aid);
      Array.prototype.forEach.call(group.querySelectorAll('.t-sw'), function (sw) {
        sw.addEventListener('click', function () {
          Array.prototype.forEach.call(group.querySelectorAll('.t-sw'), function (o) { o.classList.remove('is-active'); });
          sw.classList.add('is-active');
          selected[aid] = sw.getAttribute('data-opt');
          if (sel) sel.textContent = sw.getAttribute('data-valor') || '';
          var foto = sw.getAttribute('data-foto');
          if (foto && main) { main.src = foto; if (mainWrap) mainWrap.setAttribute('data-zoom', foto); }
          tryMatch();
        });
      });
      var dd = group.querySelector('.t-sw-select');
      if (dd) dd.addEventListener('change', function () {
        if (dd.value) { selected[aid] = dd.value; if (sel) sel.textContent = dd.options[dd.selectedIndex].text; }
        else { delete selected[aid]; if (sel) sel.textContent = ''; }
        tryMatch();
      });
    });
    if (buyBtn && nAttrs > 0) buyBtn.disabled = true;
    tryMatch();
  }
  /* ===== Artículo: índice (TOC), ver más, compartir ===== */
  var body = $('#t-article-body'), toc = $('#t-toc-list');
  if (body && toc) {
    var hs = $$('h2, h3', body), items = [];
    hs.forEach(function (h, i) {
      if (!h.id) h.id = 'sec-' + i;
      items.push({ id: h.id, text: h.textContent, sub: h.tagName === 'H3' });
    });
    if (items.length) {
      toc.innerHTML = items.map(function (it, i) {
        return '<a href="#' + it.id + '" class="t-toc-link ' + (it.sub ? 't-toc-sub' : '') + (i >= 3 ? ' t-toc-hidden' : '') + '">' + it.text + '</a>';
      }).join('');
      var more = $('#t-toc-more');
      if (items.length > 3 && more) {
        more.style.display = 'inline';
        more.addEventListener('click', function () {
          var hidden = $$('.t-toc-hidden', toc);
          var show = hidden.length && hidden[0].style.display !== 'block';
          $$('.t-toc-hidden', toc).forEach(function (a) { a.style.display = show ? 'block' : 'none'; });
          more.textContent = show ? 'Ver menos' : 'Ver más';
        });
      }
    } else { var box = $('#t-toc'); if (box) box.style.display = 'none'; }
  }
  var shareBtn = $('#t-share-btn'), sharePop = $('#t-share-pop'), copyBtn = $('#t-copy-link');
  if (shareBtn && sharePop) {
    shareBtn.addEventListener('click', function (e) { e.stopPropagation(); sharePop.classList.toggle('is-open'); });
    document.addEventListener('click', function () { sharePop.classList.remove('is-open'); });
    sharePop.addEventListener('click', function (e) { e.stopPropagation(); });
  }
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      navigator.clipboard.writeText(window.location.href).then(function () {
        var o = copyBtn.textContent; copyBtn.textContent = '¡Copiado!'; setTimeout(function () { copyBtn.textContent = o; }, 1500);
      });
    });
  }
  /* ===== Modal login/registro ===== */
  var authModal = $('#t-auth-modal'), openAuth = $('#t-open-auth'), closeAuth = $('#t-auth-close');
  function abrirAuth() { if (authModal) { authModal.classList.add('is-open'); authModal.setAttribute('aria-hidden', 'false'); } }
  function cerrarAuth() { if (authModal) { authModal.classList.remove('is-open'); authModal.setAttribute('aria-hidden', 'true'); } }
  if (openAuth) openAuth.addEventListener('click', abrirAuth);
  if (closeAuth) closeAuth.addEventListener('click', cerrarAuth);
  if (authModal) {
    authModal.addEventListener('click', function (e) { if (e.target === authModal) cerrarAuth(); });
    $$('.t-modal-tab', authModal).forEach(function (tab) {
      tab.addEventListener('click', function () {
        $$('.t-modal-tab', authModal).forEach(function (t) { t.classList.remove('is-active'); });
        $$('.t-modal-pane', authModal).forEach(function (p) { p.classList.remove('is-active'); });
        tab.classList.add('is-active');
        var pane = $('#pane-' + tab.getAttribute('data-tab'));
        if (pane) pane.classList.add('is-active');
      });
    });
    if (authModal.getAttribute('data-open') === '1') abrirAuth();
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarAuth(); });
  }
})();
