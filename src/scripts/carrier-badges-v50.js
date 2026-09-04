(()=>{
'use strict';
const note=()=>`<div class="productCarrierNote"><span>Versand ausschließlich mit</span><span class="carrierMini dhl">DHL</span><span class="carrierMini dpd">DPD</span></div>`;
function add(el){
  if(!el||el.querySelector?.('.productCarrierNote'))return;
  el.insertAdjacentHTML('beforeend',note());
}

/* V2026.12 · Nur die dynamisch aus dem Orgaboard geladene Karte
   „Räucherhaken Dreifachdorn“ an die bestehende Fleischerhaken-Karte
   angleichen. Andere Produkte und Karten bleiben unverändert. */
function isDreifachdorn(card){
  const title=(card?.querySelector?.('h3')?.textContent||'').trim().toLowerCase();
  return title.includes('dreifachdorn');
}

async function equalizeDreifachdorn(card){
  if(!card||!isDreifachdorn(card))return;
  const body=card.querySelector('.body');
  if(!body)return;

  card.classList.add('rh1077UniformCard');

  /* Steuer-, Verfügbarkeits- und Versandzeilen stehen beim
     Fleischerhaken vor dem Kaufbereich. Für den Dreifachdorn werden
     dieselben Bausteine ergänzt, ohne Preis/Produktdaten zu verändern. */
  if(!body.querySelector('.rh24DreifachStatus')){
    const buy=body.querySelector('.buy');
    if(buy){
      let product=null;
      const id=String(card.dataset.productId||'').trim();
      try{
        if(id&&window.RH24ShopData?.get){
          const data=await window.RH24ShopData.get();
          product=(Array.isArray(data?.products)?data.products:[]).find(p=>String(p?.id||'')===id)||null;
        }
      }catch(e){}
      const available=product?Number(product.stock||0)>0:true;
      const status=document.createElement('div');
      status.className='rh24DreifachStatus';
      status.innerHTML=`<div style="font-size:12px;line-height:1.35;color:#6e655e">inkl. 19 % MwSt.</div><div class="rh1077InfoStack"><div class="rh1077InfoLine ${available?'available':'notice'}">${available?'Sofort verfügbar · 1–3 Werktage':'Verfügbarkeit auf Anfrage'}</div><div class="rh1077InfoLine shipping">Versandkostenfrei ab 39 € innerhalb Deutschlands</div></div>`;
      buy.insertAdjacentElement('beforebegin',status);
    }
  }

  /* Das Merken-/Vergleichen-Modul lief bislang vor dem dynamischen
     Einfügen des Dreifachdorns. Einmalig neu anwenden, damit rechts
     dieselben beiden Register wie beim Fleischerhaken erscheinen. */
  if(card.dataset.rh24DreifachActions!=='1'){
    card.dataset.rh24DreifachActions='1';
    if(typeof window.injectProductActions==='function'){
      window.injectProductActions(true);
    }
  }
}

function run(){
  document.querySelectorAll('.card .body,.dbProductBody,.natureBody,.dynInfo').forEach(add);
  document.querySelectorAll('.productMain,.productInfo,.productDetails').forEach(add);
  document.querySelectorAll('.rh66ProductCard[data-rh24-dynamic-hook="1"]').forEach(card=>{equalizeDreifachdorn(card)});
}

document.readyState==='loading'
  ?document.addEventListener('DOMContentLoaded',()=>{run();setTimeout(run,700)})
  :(run(),setTimeout(run,700));
new MutationObserver(()=>run()).observe(document.documentElement,{childList:true,subtree:true});
})();
