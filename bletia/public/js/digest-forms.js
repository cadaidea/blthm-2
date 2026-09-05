(function () {
  'use strict';
  function store(k){try{return window.localStorage.getItem(k);}catch(e){return null;}}
  function remember(k){try{window.localStorage.setItem(k,Date.now());}catch(e){}}
  function seenRecently(id,dias){var v=store('df_seen_'+id);if(!v)return false;return (Date.now()-parseInt(v,10))<(parseInt(dias,10)||7)*86400000;}

  function flash(msg){
    var o=document.createElement('div');
    o.className='df-popup df-msg is-open';
    o.innerHTML='<div class="df-box"><button type="button" class="df-close" aria-label="Cerrar">&times;</button><p class="df-msg-txt">'+msg+'</p></div>';
    document.body.appendChild(o);
    function close(){o.remove();}
    o.addEventListener('click',function(e){if(e.target===o)close();});
    o.querySelector('.df-close').addEventListener('click',close);
    setTimeout(close,6000);
  }

  function handleForm(form, onDone){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var btn=form.querySelector('.df-btn'); if(btn){btn.disabled=true;}
      var fd=new FormData(form);
      fetch(form.getAttribute('action'),{
        method:'POST',
        headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body:fd
      }).then(function(r){return r.json().catch(function(){return {ok:true,message:'Revisa tu correo para confirmar tu suscripción.'};});})
        .then(function(d){ if(onDone) onDone(); flash((d&&d.message)?d.message:'Revisa tu correo para confirmar tu suscripción.'); })
        .catch(function(){ if(onDone) onDone(); flash('Te registramos. Revisa tu correo.'); })
        .finally(function(){ if(btn){btn.disabled=false;} });
    });
  }

  // ===== Pestaña flotante (tab) =====
  Array.prototype.slice.call(document.querySelectorAll('.df-form[data-tipo="tab"]')).forEach(function(el){
    var id=el.getAttribute('data-id'), rep=el.getAttribute('data-repetir')||'7';
    if(seenRecently(id,rep)){ el.style.display='none'; return; }
    var bubble=el.querySelector('.df-tab-bubble'),
        bx=el.querySelector('.df-tab-bubble-x'),
        panel=el.querySelector('.df-tab-panel'),
        px=el.querySelector('.df-tab-panel-x'),
        form=el.querySelector('.df-fields');
    var impreso=false;
    function impresion(){if(impreso||store('df_impr_'+id))return;impreso=true;try{window.localStorage.setItem('df_impr_'+id,'1');}catch(e){}var i=new Image();i.src='/digest/impr?f='+id;}
    function open(){el.classList.add('is-open');panel.setAttribute('aria-hidden','false');impresion();}
    function collapse(){el.classList.remove('is-open');panel.setAttribute('aria-hidden','true');}     // vuelve al círculo
    function dismiss(){el.style.display='none';remember(id);}                                          // se va por N días
    bubble.addEventListener('click',function(e){ if(e.target===bx)return; open(); });
    bubble.addEventListener('keydown',function(e){ if(e.key==='Enter'||e.key===' '){e.preventDefault();open();} });
    if(bx) bx.addEventListener('click',function(e){ e.stopPropagation(); dismiss(); });
    if(px) px.addEventListener('click',collapse);
    if(form) handleForm(form, dismiss);
    impresion(); // el círculo visible ya cuenta como impresión
  });

  // ===== popup / slide / bar =====
  Array.prototype.slice.call(document.querySelectorAll('.df-form[data-tipo]')).forEach(function(el){
    var tipo=el.getAttribute('data-tipo'); if(tipo==='tab')return;
    var id=el.getAttribute('data-id'),trig=el.getAttribute('data-trigger')||'delay',
        val=parseInt(el.getAttribute('data-valor')||'5',10),rep=el.getAttribute('data-repetir')||'7',shown=false;
    function impresion(){if(store('df_impr_'+id))return;try{window.localStorage.setItem('df_impr_'+id,'1');}catch(e){}var img=new Image();img.src='/digest/impr?f='+id;}
    function show(){if(shown)return;if((tipo==='popup'||tipo==='slide_in')&&seenRecently(id,rep))return;shown=true;el.classList.add('is-open');el.setAttribute('aria-hidden','false');impresion();if(tipo==='popup'||tipo==='slide_in')remember('df_seen_'+id);}
    function close(){el.classList.remove('is-open');el.setAttribute('aria-hidden','true');remember('df_seen_'+id);}
    Array.prototype.slice.call(el.querySelectorAll('.df-close,.df-bar-close')).forEach(function(b){b.addEventListener('click',close);});
    if(tipo==='popup')el.addEventListener('click',function(e){if(e.target===el)close();});
    var form=el.querySelector('.df-fields'); if(form) handleForm(form, close);
    if(tipo==='bar_top'||tipo==='bar_bottom'){show();return;}
    if(trig==='delay'){setTimeout(show,val*1000);}
    else if(trig==='scroll'){var os=function(){var p=(window.scrollY+window.innerHeight)/document.documentElement.scrollHeight*100;if(p>=val){show();window.removeEventListener('scroll',os);}};window.addEventListener('scroll',os,{passive:true});}
    else if(trig==='exit'){var ol=function(e){if(e.clientY<=0){show();document.removeEventListener('mouseleave',ol);}};document.addEventListener('mouseleave',ol);setTimeout(show,30000);}
  });

  Array.prototype.slice.call(document.querySelectorAll('.df-inline .df-fields')).forEach(function(form){ handleForm(form, null); });
})();
