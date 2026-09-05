// Bletia · autocompletado de URLs internas en el editor (Trix link dialog y campos url)
(function () {
  var ENDPOINT = '/api/enlaces';
  var box = null, current = null, timer = null;

  function close() { if (box) { box.remove(); box = null; } current = null; }

  function place(input) {
    close();
    current = input;
    box = document.createElement('div');
    box.className = 'bx-enlaces';
    box.style.cssText = 'position:absolute;z-index:99999;background:#fff;border:1px solid #e5e7eb;box-shadow:0 10px 30px rgba(0,0,0,.12);max-height:280px;overflow:auto;min-width:280px;border-radius:8px;font:13px sans-serif';
    document.body.appendChild(box);
    var r = input.getBoundingClientRect();
    box.style.left = (window.scrollX + r.left) + 'px';
    box.style.top = (window.scrollY + r.bottom + 4) + 'px';
    box.style.width = Math.max(r.width, 280) + 'px';
  }

  function render(items) {
    if (!box) return;
    if (!items.length) { box.innerHTML = '<div style="padding:10px;color:#888">Sin resultados</div>'; return; }
    box.innerHTML = items.map(function (i) {
      return '<div class="bx-it" data-url="' + i.url + '" style="padding:8px 10px;cursor:pointer;border-bottom:1px solid #f1f1f1">' +
        '<span style="display:inline-block;min-width:64px;color:#0499FC;font-size:11px;font-weight:600">' + i.tipo + '</span>' +
        '<span style="color:#161921">' + i.label + '</span>' +
        '<div style="color:#9aa;font-size:11px">' + i.url + '</div></div>';
    }).join('');
    Array.prototype.forEach.call(box.querySelectorAll('.bx-it'), function (el) {
      el.addEventListener('mousedown', function (e) {
        e.preventDefault();
        if (current) {
          current.value = el.getAttribute('data-url');
          current.dispatchEvent(new Event('input', { bubbles: true }));
          current.dispatchEvent(new Event('change', { bubbles: true }));
        }
        close();
      });
    });
  }

  function search(input) {
    var q = input.value.trim();
    if (/^https?:\/\//i.test(q) || q.length < 2) { close(); return; }
    clearTimeout(timer);
    timer = setTimeout(function () {
      fetch(ENDPOINT + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (current === input) { place(input); render(d); } })
        .catch(function () { close(); });
    }, 220);
  }

  function isTarget(el) {
    return el && el.tagName === 'INPUT' &&
      (el.name === 'href' /* Trix link dialog */ ||
       el.type === 'url' ||
       (el.getAttribute('placeholder') || '').toLowerCase().indexOf('url') > -1 ||
       el.hasAttribute('data-enlace'));
  }

  document.addEventListener('input', function (e) { if (isTarget(e.target)) search(e.target); }, true);
  document.addEventListener('focusin', function (e) { if (isTarget(e.target) && e.target.value.trim().length >= 2) search(e.target); }, true);
  document.addEventListener('click', function (e) { if (box && !box.contains(e.target) && e.target !== current) close(); }, true);
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); }, true);
})();
