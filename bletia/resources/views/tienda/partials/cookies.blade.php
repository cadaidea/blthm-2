<div id="ck-banner" class="ck-banner" hidden aria-live="polite" role="dialog" aria-label="Aviso de cookies">
    <div class="ck-card">
        <div class="ck-main">
            <h4 class="ck-title">Tu privacidad</h4>
            <p class="ck-txt">Usamos cookies para mejorar tu experiencia, analizar el tráfico y personalizar contenido. Puedes aceptar todas, rechazarlas o elegir tus preferencias. Más en nuestra <a href="{{ url('/cookies') }}">Política de cookies</a> y <a href="{{ url('/privacy') }}">Privacidad</a>.</p>
        </div>
        <div class="ck-prefs" hidden>
            <label class="ck-row"><span><strong>Necesarias</strong><small>Imprescindibles para el sitio.</small></span><input type="checkbox" checked disabled></label>
            <label class="ck-row"><span><strong>Analíticas</strong><small>Nos ayudan a entender el uso.</small></span><input type="checkbox" id="ck-analiticas"></label>
            <label class="ck-row"><span><strong>Marketing</strong><small>Contenido y anuncios relevantes.</small></span><input type="checkbox" id="ck-marketing"></label>
        </div>
        <div class="ck-acts">
            <button type="button" class="ck-btn ck-ghost" data-ck="prefs">Preferencias</button>
            <button type="button" class="ck-btn ck-ghost" data-ck="reject">Rechazar</button>
            <button type="button" class="ck-btn ck-solid" data-ck="accept">Aceptar todas</button>
            <button type="button" class="ck-btn ck-solid" data-ck="save" hidden>Guardar preferencias</button>
        </div>
    </div>
</div>
<style>
.ck-banner{position:fixed;left:0;right:0;bottom:0;z-index:9000;display:flex;justify-content:flex-start;padding:18px;pointer-events:none;}
.ck-banner[hidden]{display:none;}
.ck-card{pointer-events:auto;max-width:480px;width:100%;background:#fff;color:var(--brand,#161921);border:1px solid var(--line,#ececef);box-shadow:0 18px 60px rgba(22,25,33,.18);padding:24px;}
.ck-title{font-family:var(--font-title,'Geomanist');font-weight:300;font-size:1.15rem;margin:0 0 8px;letter-spacing:.02em;}
.ck-txt{font-size:.9rem;line-height:1.55;color:#444;margin:0;}
.ck-txt a{color:var(--accent,#0499FC);text-decoration:none;border-bottom:1px solid currentColor;}
.ck-prefs{margin-top:16px;display:flex;flex-direction:column;gap:2px;border-top:1px solid var(--line,#ececef);padding-top:8px;}
.ck-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 0;border-bottom:1px solid var(--line,#ececef);cursor:pointer;}
.ck-row:last-child{border-bottom:0;}
.ck-row span{display:flex;flex-direction:column;}
.ck-row small{color:var(--muted,#8a8f98);font-size:.78rem;margin-top:2px;}
.ck-row input{width:20px;height:20px;accent-color:var(--accent,#0499FC);flex:none;}
.ck-acts{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;}
.ck-btn{cursor:pointer;border-radius:999px;padding:11px 20px;font:inherit;font-size:.85rem;letter-spacing:.03em;border:1px solid var(--brand,#161921);transition:.2s ease;}
.ck-ghost{background:transparent;color:var(--brand,#161921);}
.ck-ghost:hover{background:var(--brand,#161921);color:#fff;}
.ck-solid{background:var(--brand,#161921);color:#fff;border-color:var(--brand,#161921);margin-left:auto;}
.ck-solid:hover{background:var(--accent,#0499FC);border-color:var(--accent,#0499FC);}
@media(max-width:560px){.ck-banner{padding:0;}.ck-card{max-width:none;border-left:0;border-right:0;border-bottom:0;}.ck-acts{gap:8px;}.ck-btn{flex:1;padding:12px 12px;text-align:center;}.ck-solid{margin-left:0;flex-basis:100%;}}
</style>
<script>
(function(){
  var KEY='bletia_consent', b=document.getElementById('ck-banner'); if(!b) return;
  var prefs=b.querySelector('.ck-prefs'), btnSave=b.querySelector('[data-ck="save"]');
  function read(){ try{ return JSON.parse(localStorage.getItem(KEY)||'null'); }catch(e){ return null; } }
  function emit(c){ try{ window.dispatchEvent(new CustomEvent('bletia-consent',{detail:c})); }catch(e){} }
  function store(c){ c.ts=Date.now(); c.v=1; try{ localStorage.setItem(KEY,JSON.stringify(c)); }catch(e){}
    document.cookie='bletia_consent='+(c.analiticas?'a':'')+(c.marketing?'m':'n')+';path=/;max-age=15552000;samesite=lax';
    emit(c); b.setAttribute('hidden',''); }
  function show(){ b.removeAttribute('hidden'); }
  b.addEventListener('click', function(e){
    var a=e.target.closest('[data-ck]'); if(!a) return; var k=a.getAttribute('data-ck');
    if(k==='accept'){ store({necesarias:1,analiticas:1,marketing:1}); }
    else if(k==='reject'){ store({necesarias:1,analiticas:0,marketing:0}); }
    else if(k==='prefs'){ prefs.hidden=false; a.hidden=true; b.querySelector('[data-ck="reject"]').hidden=true; btnSave.hidden=false; }
    else if(k==='save'){ store({necesarias:1, analiticas:document.getElementById('ck-analiticas').checked?1:0, marketing:document.getElementById('ck-marketing').checked?1:0}); }
  });
  var existing=read();
  if(existing && existing.v){ emit(existing); } else { show(); }
  window.bletiaCookies={ open:show, get:read };
})();
</script>
