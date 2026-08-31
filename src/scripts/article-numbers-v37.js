(()=>{
'use strict';
const PAGE_MAP={"raeucherhaken-standard.html":"std","raeucherhaken-standard-aal.html":"aal","raeucherhaken-ultra.html":"ultra","raeucherhaken-kralle.html":"kralle","raeucherhaken-filet.html":"filet","raeucherhaken-doppeldorn.html":"doppel","fleischerhaken-s-form-5mm.html":"fleisch","raeuchermehl-buche.html":"mehl-buche","raeuchermehl-erle.html":"mehl-erle","raeuchermehl-birke.html":"mehl-birke","raeuchermehl-eiche.html":"mehl-eiche","raeuchermehl-kirsche.html":"mehl-kirsche"};
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function run(){
 try{
  const d=await window.RH24ShopData.get();
  const byId=Object.fromEntries((d.products||[]).map(p=>[p.id,p]));
  const page=location.pathname.split('/').pop()||'index.html',id=PAGE_MAP[page];
  if(id&&byId[id]){
    const host=document.querySelector('.productInfo');
    if(host&&!host.querySelector('.v37ArticleNo')){const h=host.querySelector('h1');if(h)h.insertAdjacentHTML('afterend',`<div class="v37ArticleNo">Art.-Nr. <b>${esc(byId[id].article_no)}</b></div>`);}
  }
  document.querySelectorAll('.laugeCard').forEach(card=>{
    const b=card.querySelector('[onclick*="addLaugeToCart"]'),m=(b?.getAttribute('onclick')||'').match(/addLaugeToCart\('([^']+)'/),p=m?byId[m[1]]:null;
    if(p&&!card.querySelector('.v37ArticleNo')){const h=card.querySelector('h3');if(h)h.insertAdjacentHTML('afterend',`<div class="v37ArticleNo compact">Art.-Nr. <b>${esc(p.article_no)}</b></div>`);}
  });
 }catch(e){}
}
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',run):run();
})();
