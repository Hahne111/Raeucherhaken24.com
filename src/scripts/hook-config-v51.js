
(()=>{
 const page=location.pathname.split('/').pop()||'';
 const hookPages={
  'raeucherhaken-standard.html':{id:'std',name:'Räucherhaken Standard',lengths:[['12 cm',12.90],['14 cm',14.80],['18 cm',15.80],['20 cm',16.80],['24 cm',18.80]]},
  'raeucherhaken-standard-aal.html':{id:'aal',name:'Räucherhaken Standard Aal',lengths:[['12 cm',12.90],['14 cm',14.80],['18 cm',15.80],['20 cm',16.80],['24 cm',18.80]]},
  'raeucherhaken-kralle.html':{id:'kralle',name:'Räucherhaken Kralle',lengths:[['18 cm',18.90],['20 cm',19.90],['24 cm',21.90]]},
  'raeucherhaken-filet.html':{id:'filet',name:'Räucherhaken Filet',lengths:[['12 cm',15.90],['14 cm',17.80],['18 cm',18.80],['20 cm',19.80],['24 cm',21.80]]},
  'raeucherhaken-doppeldorn.html':{id:'doppel',name:'Räucherhaken Doppeldorn',lengths:[['12 cm',15.90],['14 cm',17.80],['18 cm',18.80],['20 cm',19.80],['24 cm',21.80]]},
  'raeucherhaken-ultra.html':{id:'ultra',name:'Räucherhaken Ultra',lengths:[['20 cm',19.90],['22 cm',23.90],['24 cm',24.90]]}
 };
 const cfg=hookPages[page]; if(!cfg)return;

 const state={length:cfg.lengths[0][0],material:'VA',tip:'standard',qty:10};
 const matAdd={VA:0,V2A:3.99,V4A:7.99};
 const tipAdd={standard:0,extra:2};
 let promoDelta=0;

 const money=v=>v.toLocaleString('de-DE',{style:'currency',currency:'EUR'});
 function setPrice(){
   const base=(cfg.lengths.find(x=>x[0]===state.length)||cfg.lengths[0])[1];
   return Math.max(0,base+promoDelta)+matAdd[state.material]+tipAdd[state.tip];
 }
 function total(){return setPrice()*(state.qty/10)}

 function buttons(items,key,labels){
   return items.map((v,i)=>{
     const val=Array.isArray(v)?v[0]:v;
     const text=labels?labels[val]:(Array.isArray(v)?v[0]:v);
     const sub=Array.isArray(v)?money(v[1]) : '';
     return `<button type="button" class="${i===0?'active':''}" data-key="${key}" data-value="${val}">${text}${sub?`<small>${sub} / 10 Stück</small>`:''}</button>`;
   }).join('');
 }

 function mount(){
   const host=document.querySelector('.productInfo')||document.querySelector('.productPage');
   if(!host||document.getElementById('rhHookConfig'))return;

   // Hide any legacy configurators so every hook has exactly one consistent module.
   document.querySelectorAll('#uConfigurator,.configurator,#ultraConfiguratorV22').forEach(x=>x.style.display='none');

   const box=document.createElement('section');box.className='rhHookConfig';box.id='rhHookConfig';
   box.innerHTML=`
    <h3>Variante konfigurieren</h3>
    <p class="rhHookConfigIntro">Länge, Material, Spitzenausführung und Menge auswählen. Der Preis wird sofort aktualisiert.</p>
    <div class="rhHookConfigGrid">
      <div class="rhHookGroup"><label>Länge</label><div class="rhHookChoices">${buttons(cfg.lengths,'length')}</div></div>
      <div class="rhHookGroup"><label>Material</label><div class="rhHookChoices">
        <button type="button" class="active" data-key="material" data-value="VA">VA<small>ohne Aufpreis</small></button>
        <button type="button" data-key="material" data-value="V2A">V2A<small>+3,99 € / 10 Stück</small></button>
        <button type="button" data-key="material" data-value="V4A">V4A<small>+7,99 € / 10 Stück · lebensmittelecht</small></button>
      </div></div>
      <div class="rhHookGroup"><label>Spitzenausführung</label><div class="rhHookChoices">
        <button type="button" class="active" data-key="tip" data-value="standard">Standard geschärft<small>inklusive</small></button>
        <button type="button" data-key="tip" data-value="extra">Extra scharf geschliffen<small>+2,00 € / 10 Stück</small></button>
      </div></div>
      <div class="rhHookGroup"><label>Menge</label><div class="rhHookChoices">
        <button type="button" class="active" data-key="qty" data-value="10">10 Stück<small>1 Set</small></button>
        <button type="button" data-key="qty" data-value="20">20 Stück<small>2 Sets</small></button>
        <button type="button" data-key="qty" data-value="30">30 Stück<small>3 Sets</small></button>
        <button type="button" data-key="qty" data-value="50">50 Stück<small>5 Sets</small></button>
      </div></div>
    </div>
    <div class="rhHookSummary">
      <div><small>Ihre Auswahl</small><strong id="rhHookChoice"></strong><span class="rhHookUnit" id="rhHookUnit"></span></div>
      <div class="rhHookPrice" id="rhHookPrice"></div>
    </div>
    <div class="rhHookMeta"><span>● Sofort verfügbar · Lieferzeit 1–3 Werktage</span><span>🚚 Versandkostenfrei ab 39 € innerhalb Deutschlands</span></div>
    <button type="button" class="v17CartButton rhHookCart" id="rhHookCart">In den Warenkorb legen</button>`;
   host.appendChild(box);

   box.querySelectorAll('button[data-key]').forEach(btn=>btn.addEventListener('click',()=>{
      const key=btn.dataset.key;
      box.querySelectorAll(`button[data-key="${key}"]`).forEach(x=>x.classList.remove('active'));
      btn.classList.add('active');
      state[key]=key==='qty'?+btn.dataset.value:btn.dataset.value;
      update();
   }));
   box.querySelector('#rhHookCart').addEventListener('click',add);
   update();
 }
 function update(){
   const p=setPrice(),t=total();
   const matText=state.material==='V4A'?'V4A lebensmittelecht':state.material;
   document.getElementById('rhHookChoice').textContent=`${state.length} · ${matText} · ${state.tip==='extra'?'Extra scharf':'Standard geschärft'} · ${state.qty} Stück`;
   document.getElementById('rhHookUnit').textContent=`${money(p/10)} pro Räucherhaken · Setpreis ${money(p)}`;
   document.getElementById('rhHookPrice').textContent=money(t);
 }
 function add(){
   const sets=state.qty/10;
   if(typeof addToCart==='function'){
     addToCart(cfg.id,{length:state.length,material:state.material,tip:state.tip,unitPrice:setPrice()},sets);
     if(typeof toast==='function')toast(`${state.qty} Stück ${cfg.name} wurden in den Warenkorb gelegt`);
   }else{
     alert(`${state.qty} Stück ${cfg.name} – ${money(total())}`);
   }
 }
 async function loadPromo(){try{const d=await window.RH24ShopData.get(),p=(d.products||[]).find(x=>x.id===cfg.id);if(p&&p.sale_active)promoDelta=Number(p.price||0)-Number(p.normal_price||0)}catch(e){}}
 document.addEventListener('DOMContentLoaded',()=>{loadPromo().finally(()=>setTimeout(mount,350))});
})();
