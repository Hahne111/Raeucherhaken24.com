const ULTRA_PRODUCTS=[
 {id:'std',name:'Räucherhaken Standard',cat:'Räucherhaken',price:12.90,img:'assets/standard.png',url:'raeucherhaken-standard.html',desc:'Universeller Klassiker für Forelle, Makrele und ähnliche Fische.',traits:['Standard','VA/V2A/V4A','Fisch']},
 {id:'aal',name:'Räucherhaken Standard Aal',cat:'Räucherhaken',price:12.90,img:'assets/standard-aal-weiss.png',url:'raeucherhaken-standard-aal.html',desc:'Kleiner Hakenbogen für Aal, Hornhecht und andere schlanke Fische.',traits:['Aal','Hornhecht','VA/V2A/V4A']},
 {id:'ultra',name:'Räucherhaken Ultra',cat:'Räucherhaken',price:19.90,img:'assets/ultra-original-korrekt.png',url:'raeucherhaken-ultra.html',desc:'Extra stabil für große und schwere Fische.',traits:['Ultra','2 Dornen','große Fische']},
 {id:'kralle',name:'Räucherhaken Kralle',cat:'Räucherhaken',price:18.90,img:'assets/kralle.png',url:'raeucherhaken-kralle.html',desc:'Mehrpunkt-Halt für große und schwere Fische.',traits:['Kralle','VA/V2A/V4A','große Fische']},
 {id:'filet',name:'Räucherhaken Filet',cat:'Räucherhaken',price:15.90,img:'assets/filet.png',url:'raeucherhaken-filet.html',desc:'Spezialform für Filets und flache Räucherstücke.',traits:['Filet','VA/V2A/V4A','Lachs']},
 {id:'doppel',name:'Räucherhaken Doppeldorn',cat:'Räucherhaken',price:15.90,img:'assets/doppeldorn.png',url:'raeucherhaken-doppeldorn.html',desc:'Zwei Haltepunkte für mehr Stabilität.',traits:['Doppeldorn','VA/V2A/V4A','größere Fische']},
 {id:'fleisch',name:'Fleischerhaken S-Form 5 mm',cat:'Fleischerhaken',price:7.90,img:'assets/fleischer.jpeg',url:'fleischerhaken-s-form-5mm.html',desc:'Massive S-Form für Schinken und schwere Fleischstücke.',traits:['5 mm','S-Form','Schinken']}
];
const UKEY='rh24_ultra_';
function uload(k,d=[]){try{return JSON.parse(localStorage.getItem(UKEY+k)||JSON.stringify(d))}catch{return d}}
function usave(k,v){localStorage.setItem(UKEY+k,JSON.stringify(v))}
let uwish=uload('wish',[]),ucompare=uload('compare',[]),urecent=uload('recent',[]);
function ultraProduct(id){return ULTRA_PRODUCTS.find(p=>p.id===id)||domProduct(id)}
function fmt(v){return v.toLocaleString('de-DE',{style:'currency',currency:'EUR'})}
function parseEuroText(txt){const s=String(txt||'').replace(/[^0-9,.-]/g,'').replace(/\./g,'').replace(',', '.');const n=parseFloat(s);return Number.isFinite(n)?n:0}
function domProduct(id){const card=document.querySelector(`[data-product-id=\"${CSS.escape(id)}\"]`);if(!card)return null;const img=card.querySelector('img')?.getAttribute('src')||'assets/smoky-hilfe-button.png';const a=card.querySelector('a[href]');const name=(card.querySelector('h3,h2')?.textContent||id).trim();const desc=(card.querySelector('.sub,p')?.textContent||'').trim();const cat=(card.closest('#dbProductSection')?'Weitere Artikel':'Räucherhaken');const price=parseEuroText(card.querySelector('.promoCurrentPrice,.price,strong,.dbProductBuy strong')?.textContent||'');const traits=[...card.querySelectorAll('.list>div,.productFeatures li,.dynFeatures li')].map(x=>x.textContent.trim()).filter(Boolean).slice(0,3);return {id,name,cat,price,img,url:a?.getAttribute('href')||('#'+id),desc,traits};}
function ensureUltraUI(){
 if(document.getElementById('uSearch')||document.querySelector('.mobileDock')) return;
 document.body.insertAdjacentHTML('beforeend',`
 <div class="searchOverlay" id="uSearch"><div class="searchBox"><div class="searchTop"><input id="uSearchInput" placeholder="Produkt, Fischart, Holz oder Thema suchen …"><button onclick="closeUltraSearch()">×</button></div><div class="searchResults" id="uSearchResults"></div></div></div>
 <aside class="moduleDrawer" id="uWish"><div class="drawerHead"><h2>Merkliste</h2><button onclick="toggleUDrawer('uWish',false)">×</button></div><div id="uWishBody"></div></aside>
 <aside class="moduleDrawer" id="uCompare"><div class="drawerHead"><h2>Produktvergleich</h2><button onclick="toggleUDrawer('uCompare',false)">×</button></div><div id="uCompareBody"></div></aside>
 <button class="cookieFab" onclick="document.getElementById('cookiePanel').classList.toggle('open')">Cookie-Einstellungen</button>
 <div class="cookiePanel" id="cookiePanel"><h3>Datenschutz-Einstellungen</h3><p class="small muted">In dieser Demo werden nur technisch notwendige lokale Speicherfunktionen verwendet. Optionale Kategorien können für die spätere Live-Version vorbereitet werden.</p><div class="cookieRow"><span>Technisch notwendig</span><b>immer aktiv</b></div><div class="cookieRow"><span>Komfort</span><input type="checkbox" id="cookieComfort"></div><div class="cookieRow"><span>Statistik</span><input type="checkbox" id="cookieStats"></div><button class="btn primary" style="margin-top:12px" onclick="saveCookiePrefs()">Speichern</button></div>
 <button class="backTop" title="Nach oben" onclick="scrollTo({top:0,behavior:'smooth'})">↑</button>
 <nav class="mobileDock"><a href="index.html"><span>⌂</span>Start</a><a href="shop.html"><span>▦</span>Shop</a><button onclick="openUltraSearch()"><span>⌕</span>Suche</button><button onclick="openWishlist()"><span>♡</span>Merkliste</button><button onclick="openCart()"><span>🛒</span>Warenkorb</button></nav>`);
 document.getElementById('uSearchInput').addEventListener('input',e=>renderUltraSearch(e.target.value));
 document.getElementById('uSearchInput').addEventListener('keydown',e=>{if(e.key==='Escape')closeUltraSearch()});
 document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();openUltraSearch()}});
 injectUtilities(); injectProductActions(); injectConfigurator(); recordRecent(); injectRecentlyViewed(); injectNewsletter(); injectFaq();
}
function injectUtilities(){const c=document.querySelector('.content');if(!c)return;const rail=document.createElement('div');rail.className='utilityRail';rail.innerHTML=`<button class="utilityBtn" onclick="openUltraSearch()">⌕ Suche <small>(Ctrl/⌘ K)</small></button><button class="utilityBtn" onclick="openWishlist()">♡ Merkliste (${uwish.length})</button><button class="utilityBtn" onclick="openCompare()">⇄ Vergleichen (${ucompare.length}/3)</button><button class="utilityBtn" onclick="showOrderTracker()">◴ Bestellung verfolgen</button><button class="utilityBtn" onclick="shareWhatsApp()">↗ Seite teilen</button>`;c.prepend(rail);
}
function openUltraSearch(){document.getElementById('uSearch').classList.add('open');let i=document.getElementById('uSearchInput');i.focus();renderUltraSearch(i.value)}
function closeUltraSearch(){document.getElementById('uSearch').classList.remove('open')}
function renderUltraSearch(q=''){q=q.toLowerCase().trim();const knowledge=[{name:'Räuchermehl Buche',desc:'Klassischer Allrounder',url:'raeuchermehl-buche.html'},{name:'Räuchermehl Erle',desc:'Mild und fischfreundlich',url:'raeuchermehl-erle.html'},{name:'Räucherfisch-Guide',desc:'Fischarten, Haken und Holz',url:'raeucherfisch.html'},{name:'Schinken selber machen',desc:'Pökeln, Räuchern und Reifen',url:'schinken.html'},{name:'Räucherlaugen',desc:'Forelle und Aal – Geschmacksvarianten',url:'raeucherlaugen.html'}];let rows=ULTRA_PRODUCTS.filter(p=>!q||(`${p.name} ${p.desc} ${p.traits.join(' ')}`).toLowerCase().includes(q)).map(p=>`<a class="searchResult" href="${p.url}"><img src="${p.img}"><div><b>${p.name}</b><br><small>${p.desc}</small></div><strong>${fmt(p.price)}</strong></a>`);rows.push(...knowledge.filter(k=>!q||(`${k.name} ${k.desc}`).toLowerCase().includes(q)).map(k=>`<a class="searchResult" href="${k.url}"><div style="width:64px;height:64px;border-radius:10px;background:#fff4e8;display:grid;place-items:center;color:var(--brown);font-size:25px">✦</div><div><b>${k.name}</b><br><small>${k.desc}</small></div></a>`));document.getElementById('uSearchResults').innerHTML=rows.join('')||'<p style="padding:18px">Keine Treffer. Versuchen Sie z. B. „Forelle“, „V4A“ oder „Buche“.</p>'}
function toggleUDrawer(id,on){document.getElementById(id).classList.toggle('open',on)}
function openWishlist(){renderWishlist();toggleUDrawer('uWish',true)}
function toggleWish(id){uwish=uwish.includes(id)?uwish.filter(x=>x!==id):[...uwish,id];usave('wish',uwish);injectProductActions(true);toast(uwish.includes(id)?'Zur Merkliste hinzugefügt':'Von der Merkliste entfernt')}
function renderWishlist(){const b=document.getElementById('uWishBody');if(!uwish.length){b.innerHTML='<p>Noch keine Produkte gemerkt.</p>';return}b.innerHTML=uwish.map(id=>{let p=ultraProduct(id);return `<div class="moduleItem"><img src="${p.img}"><div><b>${p.name}</b><br><small>${fmt(p.price)}</small></div><button onclick="toggleWish('${id}');renderWishlist()">Entfernen</button></div>`}).join('')}
function openCompare(){renderCompare();toggleUDrawer('uCompare',true)}
function toggleCompare(id){if(ucompare.includes(id))ucompare=ucompare.filter(x=>x!==id);else if(ucompare.length<3)ucompare.push(id);else{toast('Maximal 3 Produkte vergleichen');return}usave('compare',ucompare);injectProductActions(true);toast('Vergleich aktualisiert')}
function renderCompare(){const b=document.getElementById('uCompareBody');if(!ucompare.length){b.innerHTML='<p>Wählen Sie bis zu 3 Produkte zum Vergleichen.</p>';return}const ps=ucompare.map(ultraProduct);const rows=[['Produkt',...ps.map(p=>`<b>${p.name}</b>`)],['Preis',...ps.map(p=>fmt(p.price))],['Kategorie',...ps.map(p=>p.cat)],['Merkmale',...ps.map(p=>p.traits.join(', '))],['Einsatz',...ps.map(p=>p.desc)]];b.innerHTML=`<div class="compareGrid">${rows.flatMap((r,ri)=>r.map((x,ci)=>`<div class="${ri===0||ci===0?'head':''}">${x}</div>`)).join('')}</div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">${ps.map(p=>`<button class="miniAction" onclick="toggleCompare('${p.id}');renderCompare()">${p.name} entfernen</button>`).join('')}</div>`}
function detectCurrentProduct(){const f=location.pathname.split('/').pop();return ULTRA_PRODUCTS.find(p=>p.url===f)}
function injectProductActions(reset=false){document.querySelectorAll('.productActions').forEach(x=>x.remove());const current=detectCurrentProduct();if(current){const target=document.querySelector('.productInfo');if(target){let d=document.createElement('div');d.className='productActions';d.innerHTML=`<button class="miniAction ${uwish.includes(current.id)?'active':''}" onclick="toggleWish('${current.id}')">♡ Merken</button><button class="miniAction ${ucompare.includes(current.id)?'active':''}" onclick="toggleCompare('${current.id}')">⇄ Vergleichen</button><button class="miniAction" onclick="copyLink()">↗ Produkt teilen</button>`;target.appendChild(d)}}document.querySelectorAll('.card,.dbProductCard').forEach(card=>{let id=card.dataset?.productId||'';if(!id){let btn=card.querySelector('button[onclick*="addToCart"]');const m=btn?.getAttribute('onclick')?.match(/addToCart\('([^']+)'\)/);if(m)id=m[1];}if(!id){let a=card.querySelector('a[href]');let p=ULTRA_PRODUCTS.find(x=>a&&a.getAttribute('href')===x.url);if(p)id=p.id;}if(!id)return;let p=ultraProduct(id)||{id};let body=card.querySelector('.body,.cardBody,.dbProductBody');if(!body)return;let d=document.createElement('div');d.className='productActions';d.innerHTML=`<button class="miniAction ${uwish.includes(p.id)?'active':''}" onclick="toggleWish('${p.id}')">♡ Merken</button><button class="miniAction ${ucompare.includes(p.id)?'active':''}" onclick="toggleCompare('${p.id}')">⇄ Vergleichen</button>`;body.appendChild(d)})}
function injectConfigurator(){
 const p=detectCurrentProduct();if(!p||p.id==='ultra')return;
 const info=document.querySelector('.productInfo');if(!info||document.getElementById('uConfigurator'))return;
 const isMeat=p.id==='fleisch';
 let lengths=isMeat?['120 mm','160 mm','200 mm','240 mm']:(p.id==='kralle'?['18 cm','20 cm','24 cm']:['12 cm','14 cm','18 cm','20 cm','24 cm']);
 let mats=isMeat?['Edelstahl – Produktausführung']:['VA','V2A','V4A – lebensmittelecht'];
 const qtyOptions=isMeat
   ? `<option value="1">1 Stück</option><option value="5">5 Stück</option><option value="10">10 Stück</option>`
   : `<option value="10" selected>10 Stück</option><option value="20">20 Stück</option><option value="30">30 Stück</option><option value="50">50 Stück</option>`;
 const el=document.createElement('div');el.className='configurator';el.id='uConfigurator';
 el.innerHTML=`<h3>Variante konfigurieren</h3>
 <div class="configGrid">
  <label>Länge<select id="cfgLen">${lengths.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('')}</select></label>
  <label>Material<select id="cfgMat">${mats.map((x,i)=>`<option ${i===0?'selected':''}>${x}</option>`).join('')}</select></label>
  ${isMeat?'':`<label>Spitzenausführung<select id="cfgTip"><option value="standard">Standard geschärft – inklusive</option><option value="extra">Extra scharf geschliffen – +2,00 €</option></select></label>`}
  <label>Menge<select id="cfgQty">${qtyOptions}</select></label>
 </div>
 <div class="configSummary"><div><small>Ihre Auswahl</small><br><b id="cfgText"></b><div id="cfgUnitPrice" class="cfgUnitPrice"></div></div><strong id="cfgPrice"></strong></div>
 <button class="cartAddBtn v17CartButton" style="margin-top:10px;width:100%" onclick="addConfigured('${p.id}')">In den Warenkorb legen</button>`;
 info.appendChild(el);
 ['cfgLen','cfgMat','cfgTip','cfgQty'].forEach(id=>document.getElementById(id)?.addEventListener('change',()=>updateConfig(p)));
 updateConfig(p);
}
function updateConfig(p){
 const len=document.getElementById('cfgLen')?.value||'',
       mat=document.getElementById('cfgMat')?.value||'',
       tip=document.getElementById('cfgTip')?.value||'standard',
       qty=Math.max(1,+document.getElementById('cfgQty')?.value||1);
 if(p.id==='fleisch'){
   const unit=p.price,total=unit*qty;
   document.getElementById('cfgText').textContent=`${len} · ${mat} · ${qty} Stück`;
   document.getElementById('cfgUnitPrice').textContent=`Einzelpreis ${fmt(unit)}`;
   document.getElementById('cfgPrice').textContent=fmt(total);return;
 }
 let setPrice=p.price;
 // Existing standard-family length logic. Kralle starts at 18 cm; its displayed base price remains p.price.
 if(p.id==='kralle'){
   const a={'18 cm':0,'20 cm':1.00,'24 cm':3.00}; setPrice+=(a[len]||0);
 }else{
   const a={'12 cm':0,'14 cm':1.90,'18 cm':2.90,'20 cm':3.90,'24 cm':5.90}; setPrice+=(a[len]||0);
 }
 if(mat.startsWith('V2A'))setPrice+=3.99;
 if(mat.startsWith('V4A'))setPrice+=7.99;
 if(tip==='extra')setPrice+=2.00;
 const unitPrice=setPrice/10,total=unitPrice*qty;
 document.getElementById('cfgText').textContent=`${len} · ${mat}${tip==='extra'?' · Extra scharf':' · Standard geschärft'} · ${qty} Stück`;
 document.getElementById('cfgUnitPrice').textContent=`Preis pro Stück: ${fmt(unitPrice)}`;
 document.getElementById('cfgPrice').textContent=fmt(total);
}
function addConfigured(id){
 const qty=Math.max(1,+document.getElementById('cfgQty')?.value||1);
 const sets=id==='fleisch'?qty:Math.max(1,Math.ceil(qty/10));
 for(let i=0;i<sets;i++)addToCart(id);
 toast(`${qty} Stück wurden in den Warenkorb gelegt`);
}
function recordRecent(){const p=detectCurrentProduct();if(!p)return;urecent=[p.id,...urecent.filter(x=>x!==p.id)].slice(0,4);usave('recent',urecent)}
function injectRecentlyViewed(){if(!urecent.length)return;const c=document.querySelector('.content');if(!c)return;let sec=document.createElement('section');sec.className='moduleBand';sec.innerHTML=`<h3>Zuletzt angesehen</h3><div class="moduleCards">${urecent.map(id=>{let p=ultraProduct(id);return `<div class="moduleCard"><b>${p.name}</b><p>${p.desc}</p><a href="${p.url}">Wieder ansehen →</a></div>`}).join('')}</div>`;c.appendChild(sec)}
function injectNewsletter(){const c=document.querySelector('.content');if(!c||document.querySelector('.newsletterModule'))return;let n=document.createElement('div');n.className='newsletterModule';n.innerHTML=`<div><b>Räucherwissen statt Werbeflut</b><p>Rezepte, Materialwissen und relevante Neuheiten – kompakt per E-Mail.</p></div><div class="newsletterForm"><input id="nlMail" type="email" placeholder="E-Mail-Adresse"><button onclick="newsletterSignup()">Anmelden</button></div>`;c.appendChild(n)}
function newsletterSignup(){let v=document.getElementById('nlMail')?.value.trim();if(!v||!v.includes('@')){toast('Bitte gültige E-Mail-Adresse eingeben');return}localStorage.setItem(UKEY+'newsletter',v);toast('Newsletter-Anmeldung für Live-System vorgemerkt')}
function injectFaq(){const p=detectCurrentProduct();if(!p)return;const c=document.querySelector('.content');let s=document.createElement('section');s.className='featurePanel faqModule';s.innerHTML=`<h3>Häufige Fragen zu ${p.name}</h3><details><summary>Für wen ist dieses Produkt geeignet?</summary><p>${p.desc}</p></details><details><summary>Welche Materialausführung sollte ich wählen?</summary><p>V2A ist der Allrounder für viele Anwendungen. V4A ist die hochwertigere Wahl für salzhaltige und dauerhaft feuchte Bedingungen. VA ist die preisbewusste Basisausführung.</p></details><details><summary>Kann ich das Produkt auch als Anfänger verwenden?</summary><p>Ja. Die Produktseite zeigt die wichtigsten Merkmale und der KI-Berater kann die Auswahl zusätzlich nach Fischart oder Vorhaben eingrenzen.</p></details>`;c.appendChild(s)}
function showOrderTracker(){let code=prompt('Bestellnummer eingeben:');if(!code)return;location.href='kundenlogin.html?order='+encodeURIComponent(code.trim().toUpperCase())+'#bestellfortschritt'}
function saveCookiePrefs(){usave('cookiePrefs',{comfort:document.getElementById('cookieComfort').checked,stats:document.getElementById('cookieStats').checked});document.getElementById('cookiePanel').classList.remove('open');toast('Datenschutz-Einstellungen gespeichert')}
if(!window.__RH24_MODULES_V12_UI_BOUND__){window.__RH24_MODULES_V12_UI_BOUND__=true;document.addEventListener('DOMContentLoaded',ensureUltraUI);}
