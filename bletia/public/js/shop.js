(function () {
  'use strict';
  var grid = document.getElementById('shopGrid');
  if (!grid) return;

  var items = Array.prototype.slice.call(grid.querySelectorAll('.t-shop-item'));
  var tabs = Array.prototype.slice.call(document.querySelectorAll('#shopTabs .t-tab'));
  var swatches = Array.prototype.slice.call(document.querySelectorAll('.t-swatch'));
  var search = document.getElementById('shopSearch');
  var sort = document.getElementById('shopSort');
  var rMin = document.getElementById('rMin');
  var rMax = document.getElementById('rMax');
  var pvMin = document.getElementById('pvMin');
  var pvMax = document.getElementById('pvMax');
  var countEl = document.getElementById('shopCount');
  var vacio = document.getElementById('shopVacio');
  var resetBtn = document.getElementById('shopReset');

  var state = { cat: 'all', attrs: {}, q: '', pmin: rMin ? +rMin.min : 0, pmax: rMax ? +rMax.max : 999999 };

  function fmt(n) { return '$' + Number(n).toLocaleString('es-EC'); }

  function matches(it) {
    if (state.cat !== 'all' && it.getAttribute('data-cat') !== state.cat) return false;
    var precio = parseFloat(it.getAttribute('data-precio')) || 0;
    if (precio < state.pmin || precio > state.pmax) return false;
    if (state.q) {
      var nom = it.getAttribute('data-nombre') || '';
      if (nom.indexOf(state.q) === -1) return false;
    }
    for (var aid in state.attrs) {
      if (!state.attrs.hasOwnProperty(aid)) continue;
      var sel = state.attrs[aid];
      if (!sel.length) continue;
      var vals = (it.getAttribute('data-attr-' + aid) || '').split('|').filter(Boolean);
      var ok = sel.some(function (v) { return vals.indexOf(v) !== -1; });
      if (!ok) return false;
    }
    return true;
  }

  function apply() {
    var visibles = [];
    items.forEach(function (it) {
      if (matches(it)) { it.style.display = ''; visibles.push(it); }
      else { it.style.display = 'none'; }
    });

    // ordenar
    var val = sort ? sort.value : 'destacado';
    visibles.sort(function (a, b) {
      if (val === 'precio_asc') return (parseFloat(a.dataset.precio) || 0) - (parseFloat(b.dataset.precio) || 0);
      if (val === 'precio_desc') return (parseFloat(b.dataset.precio) || 0) - (parseFloat(a.dataset.precio) || 0);
      if (val === 'nombre') return (a.dataset.nombre || '').localeCompare(b.dataset.nombre || '');
      return (parseInt(b.dataset.destacado) || 0) - (parseInt(a.dataset.destacado) || 0);
    });
    visibles.forEach(function (it) { grid.appendChild(it); });

    if (countEl) countEl.textContent = visibles.length;
    if (vacio) vacio.style.display = visibles.length ? 'none' : '';
  }

  tabs.forEach(function (t) {
    t.addEventListener('click', function () {
      tabs.forEach(function (x) { x.classList.remove('is-active'); });
      t.classList.add('is-active');
      state.cat = t.getAttribute('data-cat');
      apply();
    });
  });

  swatches.forEach(function (s) {
    s.addEventListener('click', function () {
      var aid = s.getAttribute('data-attr');
      var valor = s.getAttribute('data-valor');
      if (!state.attrs[aid]) state.attrs[aid] = [];
      var i = state.attrs[aid].indexOf(valor);
      if (i === -1) { state.attrs[aid].push(valor); s.classList.add('is-active'); }
      else { state.attrs[aid].splice(i, 1); s.classList.remove('is-active'); }
      apply();
    });
  });

  if (search) search.addEventListener('input', function () { state.q = search.value.trim().toLowerCase(); apply(); });
  if (sort) sort.addEventListener('change', apply);

  function syncPrecio() {
    var a = +rMin.value, b = +rMax.value;
    if (a > b) { var t = a; a = b; b = t; }
    state.pmin = a; state.pmax = b;
    if (pvMin) pvMin.textContent = fmt(a);
    if (pvMax) pvMax.textContent = fmt(b);
    apply();
  }
  if (rMin) rMin.addEventListener('input', syncPrecio);
  if (rMax) rMax.addEventListener('input', syncPrecio);

  if (resetBtn) resetBtn.addEventListener('click', function () {
    state.cat = 'all'; state.attrs = {}; state.q = '';
    tabs.forEach(function (x) { x.classList.toggle('is-active', x.getAttribute('data-cat') === 'all'); });
    swatches.forEach(function (x) { x.classList.remove('is-active'); });
    if (search) search.value = '';
    if (sort) sort.value = 'destacado';
    if (rMin) rMin.value = rMin.min;
    if (rMax) rMax.value = rMax.max;
    syncPrecio();
  });

  // Panel movil
  var aside = document.getElementById('shopAside');
  var btn = document.getElementById('shopFiltrosBtn');
  var close = document.getElementById('shopAsideClose');
  if (btn && aside) btn.addEventListener('click', function () { aside.classList.add('is-open'); });
  if (close && aside) close.addEventListener('click', function () { aside.classList.remove('is-open'); });

  apply();
})();
