(function (global) {
    'use strict';

    const CSS_FLAG = '__linkSearchCssInjected';
    const MARK_ATTR = 'data-ls-pending';

    function injectStyles() {
        if (global[CSS_FLAG]) return;
        global[CSS_FLAG] = true;
        const style = document.createElement('style');
        style.textContent = `
[${MARK_ATTR}]{background:#dbeafe;border-radius:2px}
.ls-float-btn{position:fixed;z-index:99998;display:none;align-items:center;justify-content:center;width:30px;height:30px;background:#161921;color:#fff;border:none;border-radius:7px;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25)}
.ls-float-btn:hover{background:#2a2e38}
.ls-float-btn svg{pointer-events:none}
.ls-popover{position:fixed;z-index:99999;display:none;background:#fff;border:1px solid #e5e5e8;border-radius:8px;box-shadow:0 10px 32px rgba(0,0,0,.18);width:280px;padding:10px;font-family:inherit}
.ls-popover__input{width:100%;box-sizing:border-box;padding:8px 9px;border:1px solid #e0e0e3;border-radius:6px;font-size:13.5px;outline:none}
.ls-popover__input:focus{border-color:#388ae5}
.ls-popover__results{max-height:200px;overflow-y:auto;margin-top:8px}
.ls-result{display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:6px;cursor:pointer;font-size:13px}
.ls-result:hover,.ls-result.is-active{background:#f2f6fb}
.ls-result__badge{flex:none;font-size:10px;text-transform:uppercase;letter-spacing:.04em;font-weight:600;color:#388ae5;background:#e8f1fc;border-radius:4px;padding:2px 6px;line-height:1.4}
.ls-result--url .ls-result__badge{color:#a8710a;background:#fdf1de}
.ls-result__name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ls-empty,.ls-loading{padding:8px 4px;font-size:12.5px;color:#8a8f98;text-align:center}
.ls-popover__footer{display:flex;align-items:center;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid #f0f0f2}
.ls-toggle{display:flex;align-items:center;gap:5px;font-size:11.5px;color:#666;cursor:pointer;user-select:none}
.ls-toggle input{margin:0;cursor:pointer}
.ls-url-preview{position:fixed;z-index:100000;display:none;background:#161921;color:#fff;font-size:11.5px;padding:5px 9px;border-radius:5px;max-width:320px;word-break:break-all;box-shadow:0 4px 14px rgba(0,0,0,.25);pointer-events:none}
        `;
        document.head.appendChild(style);
    }

    function unwrapMark(markEl) {
        if (!markEl || !markEl.isConnected) return;
        const parent = markEl.parentNode;
        if (!parent) return;
        while (markEl.firstChild) parent.insertBefore(markEl.firstChild, markEl);
        parent.removeChild(markEl);
        parent.normalize();
    }

    function linkIconSvg() {
        return `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07l-1.5 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07l1.49-1.49" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
    }

    function initLinkSearch(holderEl, config) {
        injectStyles();
        const cfg = Object.assign({ searchUrl: '/editorjs/content-search', minLength: 2, debounceMs: 250 }, config || {});

        const floatBtn = document.createElement('button');
        floatBtn.type = 'button';
        floatBtn.className = 'ls-float-btn';
        floatBtn.innerHTML = linkIconSvg();
        floatBtn.title = 'Insertar enlace';
        document.body.appendChild(floatBtn);

        const popover = document.createElement('div');
        popover.className = 'ls-popover';
        popover.innerHTML = `
            <input type="text" class="ls-popover__input" placeholder="Buscar producto…" autocomplete="off">
            <div class="ls-popover__results"></div>
            <div class="ls-popover__footer">
                <label class="ls-toggle">
                    <input type="checkbox">
                    Nueva pestaña
                </label>
            </div>
        `;
        document.body.appendChild(popover);

        const urlPreview = document.createElement('div');
        urlPreview.className = 'ls-url-preview';
        document.body.appendChild(urlPreview);

        const input = popover.querySelector('.ls-popover__input');
        const resultsBox = popover.querySelector('.ls-popover__results');
        const toggleInput = popover.querySelector('.ls-toggle input');
        let currentMark = null;
        let debounceTimer = null;
        let abortController = null;
        let currentResults = [];
        let activeIndex = -1;
        let savedRange = null;

        function closePopover(commit) {
            popover.style.display = 'none';
            urlPreview.style.display = 'none';
            if (!commit && currentMark) unwrapMark(currentMark);
            currentMark = null;
            currentResults = [];
            activeIndex = -1;
        }

        function renderResults(results, state) {
            currentResults = results;
            activeIndex = -1;
            resultsBox.innerHTML = '';
            if (state === 'loading') { resultsBox.innerHTML = '<div class="ls-loading">Buscando…</div>'; return; }
            if (state === 'error') { resultsBox.innerHTML = '<div class="ls-empty">Error al buscar.</div>'; return; }
            if (state === 'short') { resultsBox.innerHTML = `<div class="ls-empty">Min. ${cfg.minLength} caracteres.</div>`; return; }
            if (state === 'idle') { resultsBox.innerHTML = ''; return; }
            if (!results.length) { resultsBox.innerHTML = '<div class="ls-empty">Sin resultados.</div>'; return; }

            results.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'ls-result' + (item.type === 'url' ? ' ls-result--url' : '');
                row.addEventListener('mouseenter', () => {
                    urlPreview.textContent = item.name;
                    urlPreview.style.display = 'block';
                    const r = row.getBoundingClientRect();
                    const pv = urlPreview.getBoundingClientRect();
                    let top = r.top - pv.height - 6;
                    if (top < 4) top = r.bottom + 6;
                    let left = r.left;
                    const vw = document.documentElement.clientWidth;
                    if (left + pv.width > vw - 8) left = vw - pv.width - 8;
                    urlPreview.style.top = `${top}px`;
                    urlPreview.style.left = `${left}px`;
                });
                row.addEventListener('mouseleave', () => { urlPreview.style.display = 'none'; });
                const badge = document.createElement('span');
                badge.className = 'ls-result__badge';
                badge.textContent = item.type === 'url' ? 'Usar URL' : (item.label || item.type);
                const path = document.createElement('span');
                path.className = 'ls-result__name';
                try {
                    const u = new URL(item.url, window.location.origin);
                    path.textContent = u.pathname + u.search;
                } catch (e) {
                    path.textContent = item.url;
                }
                row.appendChild(badge);
                row.appendChild(path);
                row.addEventListener('mousedown', (e) => e.preventDefault());
                row.addEventListener('click', () => applyLink(item));
                resultsBox.appendChild(row);
            });
        }

        function applyLink(item) {
            if (!currentMark) { closePopover(false); return; }
            const anchor = document.createElement('a');
            anchor.setAttribute('href', item.url);
            anchor.setAttribute('data-ls-type', item.type);
            anchor.setAttribute('data-ls-id', String(item.id));
            if (toggleInput.checked) {
                anchor.setAttribute('target', '_blank');
                anchor.setAttribute('rel', 'noopener noreferrer');
            }
            while (currentMark.firstChild) anchor.appendChild(currentMark.firstChild);
            currentMark.parentNode.replaceChild(anchor, currentMark);
            currentMark = null;
            closePopover(true);
        }

        function isDirectUrlTerm(v) {
            return /^\//.test(v) || /^https?:\/\//i.test(v);
        }

        function directUrlResult(v) {
            return { id: 'raw', type: 'url', label: 'URL', name: v, url: v };
        }

        input.addEventListener('mousedown', (e) => e.stopPropagation());
        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const raw = input.value.trim();
            const directItem = isDirectUrlTerm(raw) ? directUrlResult(raw) : null;

            // "/" solo (modo explorar) o "/algo" (busqueda con prefijo de ruta):
            // siempre dispara busqueda al backend, sin exigir el minimo de caracteres.
            const isSlashMode = /^\//.test(raw);
            const term = isSlashMode ? raw.slice(1) : raw;

            if (!isSlashMode && term.length < cfg.minLength) {
                renderResults(directItem ? [directItem] : [], term.length > 0 ? 'short' : 'idle');
                return;
            }

            debounceTimer = setTimeout(async () => {
                if (abortController) abortController.abort();
                abortController = new AbortController();
                renderResults(directItem ? [directItem] : [], 'loading');
                try {
                    const url = `${cfg.searchUrl}?q=${encodeURIComponent(raw)}`;
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal: abortController.signal,
                    });
                    if (!response.ok) { renderResults(directItem ? [directItem] : [], 'error'); return; }
                    const data = await response.json();
                    const combined = directItem ? [directItem, ...(data.results || [])] : (data.results || []);
                    renderResults(combined, 'done');
                } catch (err) {
                    if (err.name !== 'AbortError') renderResults(directItem ? [directItem] : [], 'error');
                }
            }, cfg.debounceMs);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { closePopover(false); return; }
            if (!currentResults.length) return;
            const rows = () => resultsBox.querySelectorAll('.ls-result');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, currentResults.length - 1);
                rows().forEach((r, i) => r.classList.toggle('is-active', i === activeIndex));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                rows().forEach((r, i) => r.classList.toggle('is-active', i === activeIndex));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0) rows()[activeIndex].click();
            }
        });

        floatBtn.addEventListener('mousedown', (e) => e.preventDefault());
        floatBtn.addEventListener('click', () => {
            if (!savedRange) return;
            const range = savedRange;
            const btnRect = floatBtn.getBoundingClientRect();
            floatBtn.style.display = 'none';

            const markEl = document.createElement('span');
            markEl.setAttribute(MARK_ATTR, '1');
            try {
                range.surroundContents(markEl);
            } catch (err) {
                const content = range.extractContents();
                markEl.appendChild(content);
                range.insertNode(markEl);
            }

            currentMark = markEl;
            input.value = '';
            toggleInput.checked = false;
            renderResults([], 'idle');

            popover.style.display = 'block';
            const popRect = popover.getBoundingClientRect();
            let top = btnRect.bottom + 6;
            let left = btnRect.left + btnRect.width / 2 - popRect.width / 2;
            const vw = document.documentElement.clientWidth;
            if (left + popRect.width > vw - 10) left = vw - popRect.width - 10;
            if (left < 10) left = 10;
            popover.style.top = `${top}px`;
            popover.style.left = `${left}px`;

            requestAnimationFrame(() => input.focus());
        });

        function updateFloatingButton() {
            if (popover.style.display === 'block') return; // no mover el boton si el popover ya esta abierto
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0 || selection.isCollapsed) {
                floatBtn.style.display = 'none';
                savedRange = null;
                return;
            }
            const range = selection.getRangeAt(0);
            if (!holderEl.contains(range.commonAncestorContainer)) {
                floatBtn.style.display = 'none';
                savedRange = null;
                return;
            }
            savedRange = range.cloneRange();
            const rect = range.getBoundingClientRect();
            if (rect.width === 0 && rect.height === 0) {
                floatBtn.style.display = 'none';
                return;
            }
            floatBtn.style.display = 'flex';
            floatBtn.style.top = `${rect.top - 38}px`;
            floatBtn.style.left = `${rect.left + rect.width / 2 - 15}px`;
        }

        holderEl.addEventListener('mouseup', () => setTimeout(updateFloatingButton, 0));
        holderEl.addEventListener('keyup', (e) => {
            if (['Shift', 'Control', 'Alt', 'Meta'].includes(e.key)) return;
            setTimeout(updateFloatingButton, 0);
        });
        document.addEventListener('mousedown', (e) => {
            if (e.target === floatBtn) return;
            if (popover.contains(e.target)) return;
            if (popover.style.display === 'block') { closePopover(false); return; }
            if (holderEl.contains(e.target)) return;
            floatBtn.style.display = 'none';
        }, true);
    }

    global.initLinkSearch = initLinkSearch;
})(window);
