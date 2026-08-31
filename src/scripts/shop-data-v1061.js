(()=>{
  'use strict';
  if(window.RH24ShopData && window.RH24ShopData.version==='108.3') return;
  let cache=null;
  const get=()=>{
    if(!cache){
      cache=fetch('shop-products.php?v=108.3&_='+Date.now(),{cache:'no-store',credentials:'same-origin',headers:{'Accept':'application/json'}})
        .then(async r=>{const d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'Produktdaten konnten nicht geladen werden');return d;})
        .catch(e=>{cache=null;throw e});
    }
    return cache;
  };
  window.RH24ShopData={version:'108.3',get,reset:()=>{cache=null;}};
})();
