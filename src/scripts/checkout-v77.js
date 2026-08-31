(async()=>{
const C={
 "prototype-project":{name:"Prototypenentwicklung Räucherhaken",base:149.00,article_no:"90001"},
 "std":{name:"Räucherhaken Standard – 10er-Set",base:12.90,article_no:"10001"},
 "aal":{name:"Räucherhaken Standard Aal – 10er-Set",base:12.90,article_no:"10002"},
 "ultra":{name:"Räucherhaken Ultra – 10er-Set",base:19.90,article_no:"10003"},
 "kralle":{name:"Räucherhaken Kralle – 10er-Set",base:18.90,article_no:"10004"},
 "filet":{name:"Räucherhaken Filet – 10er-Set",base:15.90,article_no:"10005"},
 "doppel":{name:"Räucherhaken Doppeldorn – 10er-Set",base:15.90,article_no:"10006"},
 "fleisch":{name:"Fleischerhaken S-Form 5 mm",base:7.90,article_no:"10007"},
 "mehl-buche":{name:"Räuchermehl Buche – 500 g",base:4.95,article_no:"11001"},
 "mehl-erle":{name:"Räuchermehl Erle – 500 g",base:4.95,article_no:"11002"},
 "mehl-birke":{name:"Räuchermehl Birke – 500 g",base:4.95,article_no:"11003"},
 "mehl-eiche":{name:"Räuchermehl Eiche – 500 g",base:4.95,article_no:"11004"},
 "mehl-kirsche":{name:"Räuchermehl Kirsche – 500 g",base:6.95,article_no:"11005"},
 "lauge-forelle-0":{name:"Räucherlauge Forelle – 500 g",base:4.95,article_no:"12001"},
 "lauge-forelle-1":{name:"Räucherlauge Forelle Classic – 500 g",base:4.95,article_no:"12002"},
 "lauge-forelle-2":{name:"Räucherlauge Forelle Chili – 500 g",base:4.95,article_no:"12003"},
 "lauge-forelle-3":{name:"Räucherlauge Forelle RED – 500 g",base:6.95,article_no:"12004"},
 "lauge-forelle-4":{name:"Räucherlauge Forelle Kräuter – 500 g",base:4.95,article_no:"12005"},
 "lauge-forelle-5":{name:"Räucherlauge Forelle Knoblauch – 500 g",base:4.95,article_no:"12006"},
 "lauge-forelle-6":{name:"Räucherlauge Forelle Zitronenpfeffer – 500 g",base:4.95,article_no:"12007"},
 "lauge-forelle-7":{name:"Räucherlauge Forelle Delikat – 500 g",base:4.95,article_no:"12008"},
 "lauge-forelle-8":{name:"Räucherlauge Forelle EL PASO – 500 g",base:4.95,article_no:"12009"},
 "lauge-forelle-9":{name:"Räucherlauge Forelle Kansas – 500 g",base:4.95,article_no:"12010"},
 "lauge-aal-0":{name:"Räucherlauge Aal – 500 g",base:4.95,article_no:"12101"},
 "lauge-aal-1":{name:"Räucherlauge Aal Pfeffer – 500 g",base:4.95,article_no:"12102"},
 "lauge-aal-2":{name:"Räucherlauge Aal Delikat – 500 g",base:4.95,article_no:"12103"}
};
let SHOP={shipping_threshold:39,shipping_cost:7,vat_rate:19};try{const d=await window.RH24ShopData.get();{(d.products||[]).forEach(p=>C[p.id]={name:p.name,base:Number(p.price),article_no:p.article_no||''});SHOP={...SHOP,...(d.shop||{})}}}catch(e){}
const LP={std:{'12 cm':12.90,'14 cm':14.80,'18 cm':15.80,'20 cm':16.80,'24 cm':18.80},aal:{'12 cm':12.90,'14 cm':14.80,'18 cm':15.80,'20 cm':16.80,'24 cm':18.80},kralle:{'18 cm':18.90,'20 cm':19.90,'24 cm':21.90},filet:{'12 cm':15.90,'14 cm':17.80,'18 cm':18.80,'20 cm':19.80,'24 cm':21.80},doppel:{'12 cm':15.90,'14 cm':17.80,'18 cm':18.80,'20 cm':19.80,'24 cm':21.80},ultra:{'20 cm':19.90,'22 cm':23.90,'24 cm':24.90}};
const money=v=>Number(v).toLocaleString('de-DE',{style:'currency',currency:'EUR'}),esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function rawCart(){let a=[];try{a=JSON.parse(localStorage.getItem('rh24cart')||'[]')}catch(e){};if(!Array.isArray(a))a=[];try{const legacy=JSON.parse(localStorage.getItem('cart')||'[]');if(Array.isArray(legacy)&&legacy.some(x=>x.id==='prototype-project')&&!a.some(x=>x.id==='prototype-project'))a.push({id:'prototype-project',qty:1})}catch(e){}return a.filter(x=>C[x.id]&&Number(x.qty)>0)}
const cart=rawCart();
function price(x){const m=x.meta||{};let p=LP[x.id]?.[m.length]??C[x.id].base;if(LP[x.id]){p+=({VA:0,V2A:3.99,V4A:7.99}[m.material||'VA']||0);if(m.tip==='extra')p+=2}return +p.toFixed(2)}
function meta(x){const m=x.meta||{};return [m.length,m.material,m.tip==='extra'?'Extra scharf':m.tip==='standard'?'Standard geschärft':''].filter(Boolean).join(' · ')}
function render(){const items=document.getElementById('checkoutItems'),tot=document.getElementById('checkoutTotals'),btn=document.getElementById('submitOrder');if(!cart.length){items.innerHTML='<div class="empty">Ihr Warenkorb ist leer.</div>';tot.innerHTML='';btn.disabled=true;return}items.innerHTML=cart.map(x=>`<div class="item"><div><b>${Number(x.qty)} × ${esc(C[x.id].name)}</b>${C[x.id].article_no?`<small>Art.-Nr. ${esc(C[x.id].article_no)}</small>`:''}${meta(x)?`<small>${esc(meta(x))}</small>`:''}</div><strong>${money(price(x)*Number(x.qty))}</strong></div>`).join('');const sub=cart.reduce((s,x)=>s+price(x)*Number(x.qty),0),threshold=Number(SHOP.shipping_threshold||39),ship=sub>=threshold?0:Number(SHOP.shipping_cost||0),gross=sub+ship,rate=Number(SHOP.vat_rate||19),net=gross/(1+rate/100),vat=gross-net;tot.innerHTML=`<div class="totals"><div><span>Warenwert</span><b>${money(sub)}</b></div><div><span>Versand & Verpackung</span><b>${ship?money(ship):'0,00 €'}</b></div><div><span>inkl. ${rate.toLocaleString('de-DE')} % MwSt.</span><b>${money(vat)}</b></div><div class="grand"><span>Gesamt</span><b>${money(gross)}</b></div><p class="vat">Versandkostenfrei ab ${money(threshold)} Warenwert innerhalb Deutschlands.</p></div>`}
render();
const form=document.getElementById('checkoutForm'),err=document.getElementById('checkoutError'),ok=document.getElementById('checkoutSuccess'),btn=document.getElementById('submitOrder');
form.addEventListener('submit',async e=>{e.preventDefault();if(!cart.length||!form.reportValidity())return;err.style.display='none';btn.disabled=true;btn.textContent='Bestellung wird übermittelt …';const fd=new FormData(form),customer=Object.fromEntries(['name','company','email','phone','street','zip','city'].map(k=>[k,String(fd.get(k)||'').trim()]));let prototype_meta={};try{prototype_meta=JSON.parse(localStorage.getItem('prototypeProjectPending')||'{}').meta||{}}catch(e){};try{const r=await fetch('shop-order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({customer,note:String(fd.get('note')||''),website:String(fd.get('website')||''),items:cart,prototype_meta})}),d=await r.json();if(!r.ok||!d.ok)throw new Error(d.error||'Bestellung konnte nicht übermittelt werden.');localStorage.removeItem('rh24cart');localStorage.removeItem('cart');localStorage.removeItem('prototypeProjectPending');form.style.display='none';ok.style.display='block';ok.innerHTML=`<b>Bestellung erfolgreich übermittelt.</b><br>Ihre Bestellnummer: <strong>${esc(d.order_no)}</strong><br>Gesamtbetrag: <strong>${money(d.gross)}</strong>${d.invoice_no?`<br>Rechnungsnummer: <strong>${esc(d.invoice_no)}</strong>`:''}<br><br>${d.documents_sent?'Ihre Rechnung und Ihr Lieferschein wurden als PDF an Ihre E-Mail-Adresse gesendet.':'Ihre Bestellung liegt zur Bearbeitung vor. Rechnung und Lieferschein werden im Kundenbereich bereitgestellt, sobald das Rechnungstool vollständig eingerichtet/freigegeben ist.'}<br><br><a class="btn primary" href="kundenlogin.html?order=${encodeURIComponent(d.order_no)}#bestellfortschritt">Bestellfortschritt & Dokumente ansehen</a>`;document.getElementById('checkoutItems').innerHTML='<div class="empty">Bestellung übermittelt.</div>';document.getElementById('checkoutTotals').innerHTML='';window.scrollTo({top:0,behavior:'smooth'})}catch(ex){err.textContent=ex.message;err.style.display='block';btn.disabled=false;btn.textContent='Zahlungspflichtig bestellen'}});
})();
