(()=>{
'use strict';
const PAGE_MAP={"raeucherhaken-standard.html":"std","raeucherhaken-standard-aal.html":"aal","raeucherhaken-ultra.html":"ultra","raeucherhaken-kralle.html":"kralle","raeucherhaken-filet.html":"filet","raeucherhaken-doppeldorn.html":"doppel","fleischerhaken-s-form-5mm.html":"fleisch","raeuchermehl-buche.html":"mehl-buche","raeuchermehl-erle.html":"mehl-erle","raeuchermehl-birke.html":"mehl-birke","raeuchermehl-eiche.html":"mehl-eiche","raeuchermehl-kirsche.html":"mehl-kirsche"};
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const weight=g=>{g=Number(g||0);if(g<=0)return'noch nicht hinterlegt';return g>=1000?(g/1000).toLocaleString('de-DE',{maximumFractionDigits:3})+' kg':g.toLocaleString('de-DE')+' g'};
const block=p=>`<div class="v49ProductWeight"><span><small>Produktgewicht</small><b>${esc(weight(p.product_weight_g))}</b></span><span><small>Versandgewicht</small><b>${esc(weight(p.shipping_weight_g))}</b></span></div>`;
async function run(){
 try{
  const d=await window.RH24ShopData.get();
  const byId=Object.fromEntries((d.products||[]).map(p=>[p.id,p]));
  const page=location.pathname.split('/').pop()||'index.html',id=PAGE_MAP[page],p=id?byId[id]:null;
  if(p){const host=document.querySelector('.productInfo');if(host&&!host.querySelector('.v49ProductWeight')){const no=host.querySelector('.v37ArticleNo')||host.querySelector('h1');if(no)no.insertAdjacentHTML('afterend',block(p));}}
  document.querySelectorAll('.laugeCard').forEach(card=>{const b=card.querySelector('[onclick*="addLaugeToCart"]'),m=(b?.getAttribute('onclick')||'').match(/addLaugeToCart\('([^']+)'/),x=m?byId[m[1]]:null;if(x&&!card.querySelector('.v49ProductWeight')){const no=card.querySelector('.v37ArticleNo')||card.querySelector('h3');if(no)no.insertAdjacentHTML('afterend',block(x));}});
 }catch(e){}
}
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',run):run();
})();