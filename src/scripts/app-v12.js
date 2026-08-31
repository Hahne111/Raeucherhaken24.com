const CATALOG=[
{id:"prototype-project",article_no:"90001",name:"Prototypenentwicklung Räucherhaken",price:149.00,img:"assets/smoky-hilfe-button.png",url:"sonderanfertigung-prototyp.html",unit:"Projekt"},
{id:"std",article_no:"10001",name:"Räucherhaken Standard – 10er-Set",price:12.90,img:"assets/standard.png",url:"raeucherhaken-standard.html",unit:"10er-Set"},
{id:"aal",article_no:"10002",name:"Räucherhaken Standard Aal – 10er-Set",price:12.90,img:"assets/standard-aal-weiss.png",url:"raeucherhaken-standard-aal.html",unit:"10er-Set"},
{id:"ultra",article_no:"10003",name:"Räucherhaken Ultra – 10er-Set",price:19.90,img:"assets/ultra-original-korrekt.png",url:"raeucherhaken-ultra.html",unit:"10er-Set"},
{id:"kralle",article_no:"10004",name:"Räucherhaken Kralle – 10er-Set",price:18.90,img:"assets/kralle.png",url:"raeucherhaken-kralle.html",unit:"10er-Set"},
{id:"filet",article_no:"10005",name:"Räucherhaken Filet – 10er-Set",price:15.90,img:"assets/filet.png",url:"raeucherhaken-filet.html",unit:"10er-Set"},
{id:"doppel",article_no:"10006",name:"Räucherhaken Doppeldorn – 10er-Set",price:15.90,img:"assets/doppeldorn.png",url:"raeucherhaken-doppeldorn.html",unit:"10er-Set"},
{id:"fleisch",article_no:"10007",name:"Fleischerhaken S-Form 5 mm",price:7.90,img:"assets/fleischer.jpeg",url:"fleischerhaken-s-form-5mm.html",unit:"Stück"},
{id:"mehl-buche",article_no:"11001",name:"Räuchermehl Buche – 500 g",price:4.95,img:"assets/raeuchermehl-buche-produkt.jpg",url:"raeuchermehl-buche.html",unit:"500 g"},
{id:"mehl-erle",article_no:"11002",name:"Räuchermehl Erle – 500 g",price:4.95,img:"assets/raeuchermehl-erle-produkt.jpg",url:"raeuchermehl-erle.html",unit:"500 g"},
{id:"mehl-birke",article_no:"11003",name:"Räuchermehl Birke – 500 g",price:4.95,img:"assets/raeuchermehl-birke-produkt.jpg",url:"raeuchermehl-birke.html",unit:"500 g"},
{id:"mehl-eiche",article_no:"11004",name:"Räuchermehl Eiche – 500 g",price:4.95,img:"assets/raeuchermehl-eiche-produkt.jpg",url:"raeuchermehl-eiche.html",unit:"500 g"},
{id:"mehl-kirsche",article_no:"11005",name:"Räuchermehl Kirsche – 500 g",price:6.95,img:"assets/raeuchermehl-kirsche-produkt.jpg",url:"raeuchermehl-kirsche.html",unit:"500 g"},
{id:"lauge-forelle-0",article_no:"12001",name:"Räucherlauge Forelle – 500 g",price:4.95,img:"assets/lauge-standard.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-1",article_no:"12002",name:"Räucherlauge Forelle Classic – 500 g",price:4.95,img:"assets/lauge-delikat.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-2",article_no:"12003",name:"Räucherlauge Forelle Chili – 500 g",price:4.95,img:"assets/lauge-chili.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-3",article_no:"12004",name:"Räucherlauge Forelle RED – 500 g",price:6.95,img:"assets/lauge-red.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-4",article_no:"12005",name:"Räucherlauge Forelle Kräuter – 500 g",price:4.95,img:"assets/lauge-kraeuter.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-5",article_no:"12006",name:"Räucherlauge Forelle Knoblauch – 500 g",price:4.95,img:"assets/lauge-knoblauch.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-6",article_no:"12007",name:"Räucherlauge Forelle Zitronenpfeffer – 500 g",price:4.95,img:"assets/lauge-zitronenpfeffer.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-7",article_no:"12008",name:"Räucherlauge Forelle Delikat – 500 g",price:4.95,img:"assets/lauge-gartenkraeuter.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-8",article_no:"12009",name:"Räucherlauge Forelle EL PASO – 500 g",price:4.95,img:"assets/lauge-elpaso.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-forelle-9",article_no:"12010",name:"Räucherlauge Forelle Kansas – 500 g",price:4.95,img:"assets/lauge-kansas.png",url:"raeucherlauge-forelle.html",unit:"500 g"},
{id:"lauge-aal-0",article_no:"12101",name:"Räucherlauge Aal – 500 g",price:4.95,img:"assets/lauge-aal_standard.png",url:"raeucherlauge-aal.html",unit:"500 g"},
{id:"lauge-aal-1",article_no:"12102",name:"Räucherlauge Aal Pfeffer – 500 g",price:4.95,img:"assets/lauge-aal_pfeffer.png",url:"raeucherlauge-aal.html",unit:"500 g"},
{id:"lauge-aal-2",article_no:"12103",name:"Räucherlauge Aal Delikat – 500 g",price:4.95,img:"assets/lauge-aal_delikat.png",url:"raeucherlauge-aal.html",unit:"500 g"}
];
/* Versandkonditionen: Standardwerte entsprechen AGB, „Zahlung & Versand"
   und der Kasse (7,00 € · frei ab 39 €). Sobald die Produktdatenbank
   erreichbar ist, werden die dort gepflegten Werte übernommen – damit
   zeigen Warenkorb, Paket-Empfehlung und Kasse identische Beträge. */
const RH24_SHIPPING={cost:7.00,threshold:39};
const LEGACY_PRODUCT_IDS={mehlBuche:'mehl-buche',mehlErle:'mehl-erle',mehlBirke:'mehl-birke',mehlEiche:'mehl-eiche',mehlKirsche:'mehl-kirsche',lauForelle:'lauge-forelle-0',lauForelleChili:'lauge-forelle-2',lauForelleRed:'lauge-forelle-3',lauAal:'lauge-aal-0'};let cart=JSON.parse(localStorage.getItem('rh24cart')||'[]');if(Array.isArray(cart)){cart=cart.map(x=>{const old=x.id,n=LEGACY_PRODUCT_IDS[old]||old;if(n!==old){x={...x,id:n};if(typeof x.key==='string'&&x.key.startsWith(old))x.key=n+x.key.slice(old.length)}return x})}else cart=[];const euro=v=>v.toLocaleString('de-DE',{style:'currency',currency:'EUR'});function cartKey(x){return x.key||x.id}function saveCart(){localStorage.setItem('rh24cart',JSON.stringify(cart));document.querySelectorAll('[data-cart-count]').forEach(e=>e.textContent=cart.reduce((s,x)=>s+(+x.qty||0),0));}function addToCart(id,meta=null,amount=1){amount=Math.max(1,+amount||1);const key=meta?id+'|'+encodeURIComponent(JSON.stringify({length:meta.length||'',material:meta.material||'',tip:meta.tip||''})):id;let x=cart.find(i=>cartKey(i)===key);x?x.qty+=amount:cart.push({id,key,qty:amount,...(meta?{meta}:{})});saveCart();toast('Zum Warenkorb hinzugefügt')}function openCart(){renderCart();document.getElementById('overlay').classList.add('open');document.getElementById('cartDrawer').classList.add('open')}function closeCart(){document.getElementById('overlay').classList.remove('open');document.getElementById('cartDrawer').classList.remove('open')}function renderCart(){
 let b=document.getElementById('cartItems');if(!b)return;
 if(!cart.length){b.innerHTML='<p>Ihr Warenkorb ist leer.</p>';document.getElementById('cartTotal').textContent=euro(0);return}
 b.innerHTML=cart.map(x=>{let p=CATALOG.find(a=>a.id===x.id);if(!p)return '';let unit=+(x.meta?.unitPrice??p.price),m=x.meta||{},meta=[m.length,m.material,m.tip==='extra'?'Extra scharf':m.tip==='standard'?'Standard geschärft':''].filter(Boolean).join(' · '),k=cartKey(x);return `<div class="cartItem"><img src="${p.img}"><div><b>${p.name}</b>${p.article_no?`<br><small>Art.-Nr. ${p.article_no}</small>`:''}${meta?`<br><small>${meta}</small>`:''}<br><span>${euro(unit)} / ${p.unit}</span><div style="display:flex;gap:6px;align-items:center;margin-top:6px"><button class="miniAction" onclick="changeCartQty('${k}',-1)">−</button><b>${x.qty}</b><button class="miniAction" onclick="changeCartQty('${k}',1)">+</button><button class="miniAction" onclick="removeCartItem('${k}')">Entfernen</button></div></div><b>${euro(unit*x.qty)}</b></div>`}).join('');
 let subtotal=cart.reduce((s,x)=>{let p=CATALOG.find(a=>a.id===x.id);return s+(p?(+(x.meta?.unitPrice??p.price))*x.qty:0)},0);
 let shipping=subtotal>=RH24_SHIPPING.threshold?0:RH24_SHIPPING.cost;
 document.getElementById('cartTotal').textContent=euro(subtotal+shipping);
 const hint=subtotal>=RH24_SHIPPING.threshold?'✓ Kostenloser Versand freigeschaltet':`Noch ${euro(RH24_SHIPPING.threshold-subtotal)} bis zum kostenlosen Versand`;
 b.insertAdjacentHTML('beforeend',`<div class="v23CartSummary"><div><span>Zwischensumme</span><b>${euro(subtotal)}</b></div><div><span>Versand & Verpackung</span><b>${shipping===0?'0,00 €':euro(shipping)}</b></div><div class="${subtotal>=RH24_SHIPPING.threshold?'free':''}">${hint}</div><small>Alle Preise inkl. 19 % MwSt.</small></div>`);
}function changeCartQty(key,d){let x=cart.find(i=>cartKey(i)===key);if(!x)return;x.qty+=d;if(x.qty<=0)cart=cart.filter(i=>cartKey(i)!==key);saveCart();renderCart()}function removeCartItem(key){cart=cart.filter(i=>cartKey(i)!==key);saveCart();renderCart()}function checkout(){if(!cart.length){toast('Ihr Warenkorb ist leer.');return}location.href='checkout.html'}function openZoom(src,name='Produktbild'){return zoom(src,name)}function zoom(src,name){document.getElementById('zoomImg').src=src;document.getElementById('zoomImg').alt=name;document.getElementById('zoomModal').classList.add('open')}function closeZoom(){document.getElementById('zoomModal').classList.remove('open')}function toggleAI(){document.getElementById('aiPanel').classList.toggle('open')}function ask(){let i=document.getElementById('q'),q=i.value.trim();if(!q)return;add(q,'user');i.value='';setTimeout(()=>add(answer(q),'bot'),160)}function add(t,c){let d=document.createElement('div');d.className='msg '+c;d.textContent=t;let b=document.getElementById('msgs');b.appendChild(d);b.scrollTop=b.scrollHeight;if(c==='bot'&&document.getElementById('voiceOut')?.checked)speak(t)}function answer(q){q=q.toLowerCase();if(q.includes('forelle'))return 'Für Forelle ist der Standardhaken der unkomplizierte Einstieg. Bei größeren Fischen bietet der Doppeldorn mehr Halt. Als Räuchermehl passen Buche und Erle sehr gut.';if(q.includes('v4a'))return 'V4A ist die Premium-Wahl bei viel Feuchtigkeit und salzhaltiger Umgebung. V2A ist für die meisten Hobbyräucherer der Allrounder.';if(q.includes('räuchermehl')||q.includes('holz'))return 'Buche ist der Allrounder, Erle mild und fischfreundlich, Birke sehr dezent, Eiche kräftig für Fleisch und Schinken, Kirsche mild-fruchtig.';if(q.includes('schinken')||q.includes('pökel'))return 'Beim Schinken muss die Pökelmischung exakt nach Produktdosierung verwendet werden. Fleischstück, Pökelzeit, Durchbrennen, Trocknung und Rauchführung müssen zusammenpassen.';return 'Nennen Sie Fischart, Fleischstück oder Ziel. Dann kann ich die Empfehlung genauer eingrenzen.'}function speak(t){if(!('speechSynthesis' in window))return;speechSynthesis.cancel();let u=new SpeechSynthesisUtterance(t);u.lang='de-DE';u.rate=.95;speechSynthesis.speak(u)}function voice(){const SR=window.SpeechRecognition||window.webkitSpeechRecognition;if(!SR){toast('Spracheingabe wird von diesem Browser nicht unterstützt.');return}let r=new SR();r.lang='de-DE';r.onresult=e=>{document.getElementById('q').value=e.results[0][0].transcript;ask()};r.start()}function wizard(){
 const food=document.getElementById('wFood')?.value||'Forelle',
 method=document.getElementById('wMethod')?.value||'Heißräuchern',
 taste=document.getElementById('wTaste')?.value||'Klassisch',
 amount=document.getElementById('wAmount')?.value||'Kleine Menge',
 exp=document.getElementById('wExp')?.value||'Anfänger';
 let hook={id:'std',name:'Räucherhaken Standard – 10 Stück',img:'assets/standard.png',price:12.90,url:'raeucherhaken-standard.html',why:'Unkomplizierte und sichere Universalwahl für klassische Räucherfische.'};
 let lake={id:'lauge-forelle-0',name:'Räucherlauge Forelle – 500 g',img:'assets/lauge-standard.png',price:4.95,url:'raeucherlauge-forelle.html',why:'Abgestimmte Salz-Gewürz-Mischung für gleichmäßigen Geschmack.'};
 let wood={id:'mehl-buche',name:'Räuchermehl Buche – 500 g',img:'assets/raeuchermehl-buche-produkt.jpg',price:4.95,url:'raeuchermehl-buche.html',why:'Klassischer Allrounder mit ausgewogenem Raucharoma.'};
 if(food==='Aal'){
   hook={id:'aal',name:'Räucherhaken Standard Aal – 10 Stück',img:'assets/standard-aal-weiss.png',price:12.90,url:'raeucherhaken-standard-aal.html',why:'Der kleine Hakenbogen passt besonders gut zu Aal, Hornhecht und anderen schlanken Fischen.'};
   lake={id:'lauge-aal-0',name:'Räucherlauge Aal – 500 g',img:'assets/lauge-aal_standard.png',price:4.95,url:'raeucherlauge-aal.html',why:'Auf Aal abgestimmte Würzung für das klassische Heißräuchern.'};
 }else if(food==='Lachs'){
   hook={id:'filet',name:'Räucherhaken Filet – 10 Stück',img:'assets/filet.png',price:15.90,url:'raeucherhaken-filet.html',why:'Die Form ist für Filets und flachere Räucherstücke ausgelegt.'};
   wood=taste==='Fruchtig'
     ? {id:'mehl-kirsche',name:'Räuchermehl Kirsche – 500 g',img:'assets/raeuchermehl-kirsche-produkt.jpg',price:6.95,url:'raeuchermehl-kirsche.html',why:'Mild-fruchtige Rauchnote, die gut zu Lachs passt.'}
     : {id:'mehl-erle',name:'Räuchermehl Erle – 500 g',img:'assets/raeuchermehl-erle-produkt.jpg',price:4.95,url:'raeuchermehl-erle.html',why:'Mildes Rauchprofil, das den Eigengeschmack des Lachses schont.'};
 }else if(food==='Makrele'){
   hook={id:'doppel',name:'Räucherhaken Doppeldorn – 10 Stück',img:'assets/doppeldorn.png',price:15.90,url:'raeucherhaken-doppeldorn.html',why:'Zwei Haltepunkte sorgen für zusätzliche Stabilität bei kräftigeren Fischen.'};
 }else if(food==='Schinken'){
   hook={id:'fleisch',name:'Fleischerhaken S-Form 5 mm',img:'assets/fleischer.jpeg',price:7.90,url:'fleischerhaken-s-form-5mm.html',why:'Massive Aufhängung für Schinken und schwere Fleischstücke.'};
   lake={id:null,name:'Passende Pökelmischung',img:'assets/lauge-standard.png',price:null,url:'schinken.html',why:'Die Mischung muss zum Schinkentyp und zur vorgesehenen Pökelmethode passen.'};
   wood=taste==='Kräftig'
     ? {id:null,name:'Räuchermehl Eiche – 500 g',img:'assets/raeuchermehl-eiche-produkt.jpg',price:4.95,url:'raeuchermehl-eiche.html',why:'Kräftigeres Rauchprofil für Fleisch und Schinken.'}
     : wood;
 }
 if(taste==='Pikant'&&food==='Forelle')lake={id:'lauge-forelle-2',name:'Räucherlauge Forelle Chili – 500 g',img:'assets/lauge-chili.png',price:4.95,url:'raeucherlauge-forelle.html',why:'Pikante Würzung für Kunden, die mehr Schärfe wünschen.'};
 if(taste==='Fruchtig'&&food==='Forelle')wood={id:'mehl-kirsche',name:'Räuchermehl Kirsche – 500 g',img:'assets/raeuchermehl-kirsche-produkt.jpg',price:6.95,url:'raeuchermehl-kirsche.html',why:'Mild-fruchtige Rauchnote als Alternative zu klassischer Buche.'};
 if(taste==='Mild'&&food==='Forelle')wood={id:'mehl-erle',name:'Räuchermehl Erle – 500 g',img:'assets/raeuchermehl-erle-produkt.jpg',price:4.95,url:'raeuchermehl-erle.html',why:'Milde Holzart, die den Fischgeschmack nicht überdeckt.'};
 const accessory={id:null,name:method==='Kalträuchern'?'Räucherschnecke / Sparbrand prüfen':'Räucherthermometer empfohlen',img:null,price:null,url:'shop.html',why:method==='Kalträuchern'?'Körnung 1 ist für geeignete Räucherschnecken besonders interessant.':'Hilft Anfängern, die Temperatur kontrolliert zu führen.'};
 const products=[hook,lake,wood];
 const subtotal=products.reduce((sum,p)=>sum+(typeof p.price==='number'?p.price:0),0);
 const sh=subtotal>=RH24_SHIPPING.threshold?0:RH24_SHIPPING.cost;
 const card=(p,n)=>`<div class="recProduct">
   <div class="recNum">${n}</div>${p.img?`<img src="${p.img}" alt="${p.name}">`:'<div class="recPlaceholder">+</div>'}
   <div class="recBody"><b>${p.name}</b><p>${p.why}</p>
   <div class="recMeta">${typeof p.price==='number'?`<strong>${euro(p.price)}</strong><span>inkl. 19 % MwSt.</span>`:'<strong>nach Auswahl</strong>'}<span class="recStock">● Sofort verfügbar</span></div>
   <a href="${p.url}">Produkt ansehen →</a></div></div>`;
 const extra=exp==='Anfänger'?'Für Einsteiger: Schritt für Schritt vorgehen und Temperatur kontrollieren.':'Für erfahrene Anwender: Aroma und Rauchdauer gezielt anpassen.';
 const html=`<div class="recPackHead"><div><span class="recBadge">✓ Passend zu Ihrer Auswahl</span><h3>Ihr empfohlenes Räucherpaket</h3><p>${food} · ${method} · ${taste} · ${amount} · ${exp}</p></div></div>
 <div class="recPackLayout"><div class="recProducts">${card(hook,1)}${card(lake,2)}${card(wood,3)}${card(accessory,4)}</div>
 <aside class="recSummary"><h4>Paket-Zusammenfassung</h4><div><span>Produkte mit Preis</span><b>${euro(subtotal)}</b></div><div><span>Versand & Verpackung</span><b>${sh===0?'0,00 €':euro(sh)}</b></div><div class="recTotal"><span>Gesamt</span><b>${euro(subtotal+sh)}</b></div><small>inkl. 19 % MwSt. · Versandkostenfrei ab 39 € Warenwert innerhalb Deutschlands</small>
 <div class="recShip ${subtotal>=RH24_SHIPPING.threshold?'free':''}">${subtotal>=RH24_SHIPPING.threshold?'✓ Versandkostenfrei':'Noch '+euro(RH24_SHIPPING.threshold-subtotal)+' bis zum kostenlosen Versand'}</div>
 <button class="v17CartButton recAddAll" onclick="addWizardPackage(${JSON.stringify(products.filter(p=>p.id).map(p=>p.id))})">Komplettes Paket in den Warenkorb</button>
 <button class="recSecondary" onclick="document.querySelector('.recProducts')?.scrollIntoView({behavior:'smooth'})">Produkte einzeln auswählen</button></aside></div>
 <div class="recAdvice"><b>Warum dieses Paket?</b><span>${hook.why} ${wood.why} ${extra}</span></div>`;
 const r=document.getElementById('wizardResult');if(r){r.innerHTML=html;r.style.display='block';r.scrollIntoView({behavior:'smooth',block:'start'});}
}
function addWizardPackage(ids){ids.forEach(id=>addToCart(id));openCart();}function shareFacebook(){window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href),'_blank','noopener')}function shareWhatsApp(){window.open('https://wa.me/?text='+encodeURIComponent(document.title+' '+location.href),'_blank','noopener')}function shareMail(){location.href='mailto:?subject='+encodeURIComponent(document.title)+'&body='+encodeURIComponent(location.href)}async function copyLink(){try{await navigator.clipboard.writeText(location.href);toast('Link kopiert')}catch(e){toast('Link konnte nicht kopiert werden')}}function toast(t){let x=document.getElementById('toast');x.textContent=t;x.classList.add('show');setTimeout(()=>x.classList.remove('show'),1800)}document.addEventListener('DOMContentLoaded',()=>{saveCart();document.getElementById('zoomModal')?.addEventListener('click',e=>{if(e.target.id==='zoomModal')closeZoom()})});
function showAccountTab(id,btn){document.querySelectorAll('.accountPane').forEach(x=>x.classList.remove('active'));document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));document.getElementById(id)?.classList.add('active');btn?.classList.add('active')}
function localRegister(dealer){const name=document.getElementById('regName')?.value.trim(),email=document.getElementById('regEmail')?.value.trim(),pass=document.getElementById('regPass')?.value;if(!name||!email||!pass){toast('Bitte alle Felder ausfüllen');return}toast(dealer?'Händlerantrag erfasst – Serverfreigabe erforderlich':'Registrierung vorbereitet – Serveranbindung erforderlich')}
function localLogin(dealer){const email=document.getElementById('loginEmail')?.value.trim(),pass=document.getElementById('loginPass')?.value;if(!email||!pass){toast('Bitte E-Mail und Passwort eingeben');return}toast('Login-Oberfläche funktioniert – sichere Serverauthentifizierung erforderlich')}

function submitRevocation(e){
  e.preventDefault();
  const name=document.getElementById('revName').value.trim();
  const order=document.getElementById('revOrder').value.trim();
  const email=document.getElementById('revEmail').value.trim();
  const scope=document.getElementById('revScope').value;
  const note=document.getElementById('revNote').value.trim();
  if(!name||!order||!email){toast('Bitte Name, Bestellnummer und E-Mail angeben');return false}
  const stamp=new Date().toLocaleString('de-DE');
  const subject=`Widerruf Bestellung ${order}`;
  const body=`Vertrag widerrufen\n\nName: ${name}\nBestellnummer: ${order}\nE-Mail: ${email}\nUmfang: ${scope}\nHinweis: ${note||'-'}\nZeitpunkt: ${stamp}`;
  const c=document.getElementById('revConfirm');
  if(c){c.innerHTML=`<b>Widerruf erfasst.</b><br>Bestellnummer: ${order}<br>Zeitpunkt: ${stamp}<br><br>Die Testversion öffnet jetzt Ihr E-Mail-Programm. Im Live-Shop muss diese Erklärung zusätzlich serverseitig gespeichert und die Eingangsbestätigung automatisch per E-Mail versendet werden.`;c.style.display='block';}
  window.location.href='mailto:service@raeucherhaken24.com?subject='+encodeURIComponent(subject)+'&body='+encodeURIComponent(body);
  return false;
}


async function syncDbCatalogV35(){try{const d=await window.RH24ShopData.get();if(d&&d.shop){const c=Number(d.shop.shipping_cost),t=Number(d.shop.shipping_threshold);if(Number.isFinite(c)&&c>=0)RH24_SHIPPING.cost=c;if(Number.isFinite(t)&&t>0)RH24_SHIPPING.threshold=t;}(d.products||[]).forEach(p=>{const x=CATALOG.find(a=>a.id===p.id);if(x){x.name=p.name;x.price=Number(p.price);x.img=p.image||x.img;x.url=p.url||x.url;x.unit=p.unit||x.unit}else CATALOG.push({id:p.id,article_no:p.article_no||'',name:p.name,price:Number(p.price),img:p.image||'assets/smoky-hilfe-button.png',url:p.url||('artikel.php?id='+encodeURIComponent(p.id)),unit:p.unit||'Stück'})});}catch(e){}}
syncDbCatalogV35();
