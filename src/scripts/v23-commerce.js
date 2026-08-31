
document.addEventListener('DOMContentLoaded',()=>{
 const selector='.card .body,.woodShopCard>div,.laugeCard .body,.laugenCard .body,.productInfo';
 document.querySelectorAll(selector).forEach(box=>{
   if(box.querySelector('.v23CommerceLine'))return;
   const price=box.querySelector('.price,strong');
   if(!price)return;
   const d=document.createElement('div');d.className='v23CommerceLine';
   d.innerHTML='<span>inkl. 19 % MwSt.</span><b>● Sofort verfügbar · 1–3 Werktage</b><em>🚚 Versandkostenfrei ab 39 € innerhalb Deutschlands</em>';
   price.insertAdjacentElement('afterend',d);
 });
});
