
document.addEventListener('DOMContentLoaded',()=>{
 const state={len:'20',mat:'VA',tip:'standard',qty:10};
 const base={'20':19.90,'22':23.90,'24':24.90};
 const matAdd={'VA':0,'V2A':3.99,'V4A':7.99};
 const tipAdd={standard:0,extra:2};
 const bind=(id,key)=>{
   document.querySelectorAll(`#${id} button`).forEach(btn=>{
     btn.addEventListener('click',()=>{
       document.querySelectorAll(`#${id} button`).forEach(x=>x.classList.remove('active'));
       btn.classList.add('active');
       state[key]=key==='qty'?parseInt(btn.dataset.value,10):btn.dataset.value;
       update();
     });
   });
 };
 bind('ultraLenChoices','len');bind('ultraMatChoices','mat');bind('ultraTipChoices','tip');bind('ultraQtyChoices','qty');
 function money(v){return v.toLocaleString('de-DE',{style:'currency',currency:'EUR'})}
 function update(){
   const set=(base[state.len]||19.90)+(matAdd[state.mat]||0)+(tipAdd[state.tip]||0);
   const sets=state.qty/10;
   const total=set*sets;
   document.getElementById('ultraCfgText').textContent=`${state.len} cm · ${state.mat}${state.mat==='V4A'?' lebensmittelecht':''} · ${state.tip==='extra'?'Extra scharf':'Standard geschärft'} · ${state.qty} Stück`;
   document.getElementById('ultraCfgUnit').textContent=`${money(set/10)} pro Räucherhaken`;
   document.getElementById('ultraCfgPrice').textContent=money(total);
 }
 document.getElementById('ultraCfgCart')?.addEventListener('click',()=>{
   // Keep existing cart logic; catalog unit is a 10-piece set.
   const sets=state.qty/10;
   if(typeof addToCart==='function'){
     for(let i=0;i<sets;i++) addToCart('ultra');
   }
   if(typeof toast==='function') toast(`${state.qty} Räucherhaken Ultra wurden in den Warenkorb gelegt`);
 });
 update();
});
