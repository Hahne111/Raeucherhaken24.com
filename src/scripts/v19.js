
(function(){
 /* V19 · Kaufinformationen an Produktkarten und Spitzen-Auswahl im
    Konfigurator. Bereinigt: Die frühere progress()-Funktion (suchte
    nie vorhandene .cartPanel/.cartBox-Container und veraltete
    localStorage-Schlüssel) und das homeBanner() (Zielelement <header>
    existiert im aktuellen Aufbau nicht) waren wirkungslos und wurden
    entfernt. Der Versandhinweis im Warenkorb kommt aus app-v12.js. */
 function commerceInfo(){
   document.querySelectorAll('.productInfo').forEach(info=>{
     if(info.querySelector('.v19CommerceInfo'))return;
     const box=document.createElement('div');box.className='v19CommerceInfo';
     box.innerHTML='<div class="vat">inkl. 19 % MwSt. · zzgl. Versand</div><div class="stock">● Sofort verfügbar · Lieferzeit 1–3 Werktage</div><div class="ship">🚚 Versandkostenfrei ab 39 € Warenwert innerhalb Deutschlands</div>';
     const price=info.querySelector('.price,.productPrice,.product-price');
     (price||info.firstElementChild)?.insertAdjacentElement('afterend',box);
   });
 }
 function tipSelector(){
   const cfg=document.getElementById('uConfigurator'); if(!cfg||cfg.querySelector('.v19TipWrap'))return;
   const w=document.createElement('div');w.className='v19TipWrap';
   w.innerHTML='<label>Spitzenausführung</label><select id="cfgTip"><option value="standard">Standard geschärft – inklusive</option><option value="extra">Extra scharf geschliffen – +2,00 € je 10er-Set</option></select>';
   const grid=cfg.querySelector('.configGrid');(grid||cfg).appendChild(w);
 }
 const run=()=>{commerceInfo();tipSelector();};
 document.addEventListener('DOMContentLoaded',()=>{run();setTimeout(run,500)});
 /* Gedrosselter Beobachter: höchstens ein Lauf pro Frame statt bei jeder
    einzelnen DOM-Änderung – gleiche Wirkung, deutlich weniger Arbeit. */
 let pending=false;
 new MutationObserver(()=>{
   if(pending)return;pending=true;
   requestAnimationFrame(()=>{pending=false;run();});
 }).observe(document.documentElement,{childList:true,subtree:true});
})();
