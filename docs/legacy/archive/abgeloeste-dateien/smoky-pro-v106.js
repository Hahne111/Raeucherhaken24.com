(()=>{
  'use strict';
  if(window.__RH24_SMOKY_PRO_V106__) return;
  window.__RH24_SMOKY_PRO_V106__=true;

  const VERSION='106.0';
  const API='smoky-api.php';
  const STORE_KEY='rh24_smoky_pro_v106';
  const CART_KEY='rh24cart';
  const SHIPPING_FREE=39;

  const FALLBACK_CATALOG={
    std:{id:'std',name:'Räucherhaken Standard – 10er-Set',price:12.90,img:'assets/standard.png',url:'raeucherhaken-standard.html',kind:'hook'},
    aal:{id:'aal',name:'Räucherhaken Standard Aal – 10er-Set',price:12.90,img:'assets/standard-aal-weiss.png',url:'raeucherhaken-standard-aal.html',kind:'hook'},
    ultra:{id:'ultra',name:'Räucherhaken Ultra – 10er-Set',price:19.90,img:'assets/ultra-original-korrekt.png',url:'raeucherhaken-ultra.html',kind:'hook'},
    kralle:{id:'kralle',name:'Räucherhaken Kralle – 10er-Set',price:18.90,img:'assets/kralle.png',url:'raeucherhaken-kralle.html',kind:'hook'},
    filet:{id:'filet',name:'Räucherhaken Filet – 10er-Set',price:15.90,img:'assets/filet.png',url:'raeucherhaken-filet.html',kind:'hook'},
    doppel:{id:'doppel',name:'Räucherhaken Doppeldorn – 10er-Set',price:15.90,img:'assets/doppeldorn.png',url:'raeucherhaken-doppeldorn.html',kind:'hook'},
    fleisch:{id:'fleisch',name:'Fleischerhaken S-Form 5 mm',price:7.90,img:'assets/fleischer.jpeg',url:'fleischerhaken-s-form-5mm.html',kind:'hook'},
    'mehl-buche':{id:'mehl-buche',name:'Räuchermehl Buche – 500 g',price:4.95,img:'assets/raeuchermehl-buche-produkt.jpg',url:'raeuchermehl-buche.html',kind:'wood'},
    'mehl-erle':{id:'mehl-erle',name:'Räuchermehl Erle – 500 g',price:4.95,img:'assets/raeuchermehl-erle-produkt.jpg',url:'raeuchermehl-erle.html',kind:'wood'},
    'mehl-eiche':{id:'mehl-eiche',name:'Räuchermehl Eiche – 500 g',price:4.95,img:'assets/raeuchermehl-eiche-produkt.jpg',url:'raeuchermehl-eiche.html',kind:'wood'},
    'mehl-kirsche':{id:'mehl-kirsche',name:'Räuchermehl Kirsche – 500 g',price:6.95,img:'assets/raeuchermehl-kirsche-produkt.jpg',url:'raeuchermehl-kirsche.html',kind:'wood'},
    'lauge-forelle-0':{id:'lauge-forelle-0',name:'Räucherlauge Forelle – 500 g',price:4.95,img:'assets/lauge-standard.png',url:'raeucherlauge-forelle.html',kind:'brine'},
    'lauge-forelle-2':{id:'lauge-forelle-2',name:'Räucherlauge Forelle Chili – 500 g',price:4.95,img:'assets/lauge-chili.png',url:'raeucherlauge-forelle.html',kind:'brine'},
    'lauge-forelle-6':{id:'lauge-forelle-6',name:'Räucherlauge Forelle Zitronenpfeffer – 500 g',price:4.95,img:'assets/lauge-zitronenpfeffer.png',url:'raeucherlauge-forelle.html',kind:'brine'},
    'lauge-forelle-7':{id:'lauge-forelle-7',name:'Räucherlauge Forelle Delikat – 500 g',price:4.95,img:'assets/lauge-gartenkraeuter.png',url:'raeucherlauge-forelle.html',kind:'brine'},
    'lauge-aal-0':{id:'lauge-aal-0',name:'Räucherlauge Aal – 500 g',price:4.95,img:'assets/lauge-aal_standard.png',url:'raeucherlauge-aal.html',kind:'brine'},
    'lauge-aal-1':{id:'lauge-aal-1',name:'Räucherlauge Aal Pfeffer – 500 g',price:4.95,img:'assets/lauge-aal_pfeffer.png',url:'raeucherlauge-aal.html',kind:'brine'},
    'lauge-aal-2':{id:'lauge-aal-2',name:'Räucherlauge Aal Delikat – 500 g',price:4.95,img:'assets/lauge-aal_delikat.png',url:'raeucherlauge-aal.html',kind:'brine'}
  };
  const catalog={...FALLBACK_CATALOG};

  const FOOD={
    forelle:{label:'Forelle',icon:'🐟'},
    aal:{label:'Aal',icon:'〰'},
    makrele:{label:'Makrele',icon:'🐟'},
    lachs:{label:'Lachs / Filet',icon:'◫'},
    schinken:{label:'Schinken / Fleisch',icon:'🥩'}
  };
  const SIZE={
    s:{label:'bis 1 kg',short:'bis 1 kg'},
    m:{label:'1–3 kg',short:'1–3 kg'},
    l:{label:'3–5 kg',short:'3–5 kg'},
    xl:{label:'über 5 kg',short:'über 5 kg'}
  };
  const TASTE={
    mild:{label:'Mild',icon:'○'},
    classic:{label:'Klassisch',icon:'◎'},
    spicy:{label:'Würzig / pikant',icon:'✦'},
    fruity:{label:'Mild-fruchtig',icon:'◇'},
    strong:{label:'Kräftig',icon:'◆'}
  };
  const EXP={
    beginner:{label:'Ich fange gerade an',icon:'1'},
    advanced:{label:'Ich habe Erfahrung',icon:'✓'}
  };

  const state={mode:'guide',step:0,food:'',size:'',taste:'',exp:'',lastResult:null,busy:false};

  const euro=n=>Number(n||0).toLocaleString('de-DE',{style:'currency',currency:'EUR'});
  const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const cleanText=s=>String(s||'').replace(/\[([^\]]+)\]\((?:https?:\/\/|www\.)[^)]+\)/gi,'$1').replace(/https?:\/\/\S+|www\.\S+/gi,'').replace(/cite[^]*/g,'').replace(/【[^】]*(?:Quelle|source|cite)[^】]*】/gi,'').trim();

  function saveState(){
    try{localStorage.setItem(STORE_KEY,JSON.stringify({food:state.food,size:state.size,taste:state.taste,exp:state.exp}));}catch(e){}
  }
  function loadState(){
    try{const x=JSON.parse(localStorage.getItem(STORE_KEY)||'{}');['food','size','taste','exp'].forEach(k=>{if(typeof x[k]==='string')state[k]=x[k]});}catch(e){}
  }
  function cartItems(){
    try{const x=JSON.parse(localStorage.getItem(CART_KEY)||'[]');return Array.isArray(x)?x:[];}catch(e){return []}
  }
  function cartBaseId(x){return String(x?.id||'').trim();}

  async function syncCatalog(){
    try{
      if(window.RH24ShopData?.get){
        const data=await window.RH24ShopData.get();
        (data.products||[]).forEach(p=>{
          if(!p?.id)return;
          const old=catalog[p.id]||{};
          catalog[p.id]={...old,id:p.id,name:p.name||old.name||p.id,price:Number.isFinite(Number(p.price))?Number(p.price):(old.price||0),img:p.image||old.img||'assets/smoky-hilfe-button.png',url:p.url||old.url||('artikel.php?id='+encodeURIComponent(p.id)),kind:old.kind||guessKind(p.id,p.name)};
        });
      }
    }catch(e){}
  }
  function guessKind(id,name=''){
    const s=(id+' '+name).toLowerCase();
    if(/mehl|holz/.test(s))return 'wood';
    if(/lauge|lake/.test(s))return 'brine';
    if(/haken/.test(s))return 'hook';
    return 'other';
  }
  function prod(id){return catalog[id]||null;}

  function currentPageProduct(){
    const page=(location.pathname.split('/').pop()||'').toLowerCase();
    const p=Object.values(catalog).find(x=>(x.url||'').toLowerCase()===page);
    return p||null;
  }

  function pageContext(){
    const title=(document.querySelector('h1')?.textContent||document.title||'').trim();
    const product=(document.querySelector('.productInfo')?.innerText||'').replace(/\s+/g,' ').trim().slice(0,1600);
    const main=(document.querySelector('main')?.innerText||'').replace(/\s+/g,' ').trim().slice(0,900);
    const guided=state.lastResult?`Smoky-Beratung: ${FOOD[state.food]?.label||state.food}, ${SIZE[state.size]?.label||state.size}, ${TASTE[state.taste]?.label||state.taste}, ${EXP[state.exp]?.label||state.exp}. Empfohlen: ${state.lastResult.products.map(x=>x.name).join(', ')}.`:'';
    const cart=cartItems().map(x=>prod(cartBaseId(x))?.name||cartBaseId(x)).filter(Boolean).join(', ');
    return `Seitentitel: ${title}. ${product||main}. ${guided} Warenkorb: ${cart||'leer'}.`.slice(0,3000);
  }

  async function liveAnswer(question){
    const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({question,context:pageContext(),page:location.pathname.split('/').pop()||'index.html'})});
    let data={};try{data=await r.json()}catch(e){}
    if(!r.ok||!data.answer)throw new Error(data.error||'Live-Beratung nicht erreichbar');
    return cleanText(data.answer);
  }

  function fallbackAnswer(q){
    const s=String(q||'').toLowerCase();
    if(/empfehl|brauche|paket|set/.test(s) && state.lastResult)return `Für deine Auswahl empfehle ich ${state.lastResult.products.map(x=>x.name).join(', ')}. ${state.lastResult.advice}`;
    if(/forell/.test(s))return 'Für klassische Forellen bis etwa 3 kg ist der Räucherhaken Standard der unkomplizierte Einstieg. Bei größeren Fischen sind Doppeldorn, Kralle oder Ultra je nach Gewicht sinnvoll. Buche ist klassisch, Erle milder.';
    if(/aal/.test(s))return 'Für Aal ist der Standard-Aal-Haken wegen seines kleineren Hakenbogens die gezieltere Wahl. Dazu passt eine Aal-Lauge nach Packungsangabe und als Rauchprofil Buche oder Erle.';
    if(/v4a|v2a|material/.test(s))return 'V2A ist die robuste Allround-Wahl. V4A lohnt sich besonders bei häufigem Kontakt mit Salz, Feuchtigkeit und intensiver Nutzung. Länge und Ausführung solltest du passend zum Räuchergut wählen.';
    if(/räuchermehl|holz|buche|erle|eiche|kirsche/.test(s))return 'Buche ist der klassische Allrounder. Erle ist milder für Fisch, Eiche kräftiger für Fleisch und Schinken, Kirsche mild-fruchtig. Wenn du mir Räuchergut und gewünschten Geschmack nennst, wähle ich konkret aus.';
    if(/pökel|schinken/.test(s))return 'Beim Pökeln sind die Angaben der konkreten Mischung verbindlich. Ich kann dir den Ablauf und passende Räucherhaken beziehungsweise das Rauchholz einordnen, aber keine Dosierung frei erfinden.';
    if(/warenkorb|fehlt/.test(s))return 'Öffne den Reiter „Warenkorb-Check“. Dort prüfe ich die vorhandenen Artikel und zeige nur sinnvolle Ergänzungen.';
    return 'Nenne mir bitte kurz, was du räuchern möchtest, ungefähr das Gewicht und ob du ein mildes, klassisches, würziges oder fruchtiges Aroma möchtest. Oder starte die 30-Sekunden-Beratung.';
  }

  function recommendations(){
    const food=state.food,size=state.size,taste=state.taste,exp=state.exp;
    let hook='std',brine=null,wood='mehl-buche';
    let hookWhy='Unkomplizierte Allround-Wahl für klassisches Räuchergut.';

    if(food==='aal'){
      hook='aal';hookWhy='Der kleinere Hakenbogen ist gezielt für Aal und andere schlanke Fische gedacht.';
    }else if(food==='lachs'){
      hook='filet';hookWhy='Die flache Bauform passt besonders zu Filets und flacheren Räucherstücken.';
    }else if(food==='schinken'){
      hook='fleisch';hookWhy='Die massive S-Form ist für Schinken und schwere Fleischstücke ausgelegt.';
    }else if(size==='xl'){
      hook='ultra';hookWhy='Für sehr großes und schweres Räuchergut ist die stabilere Ultra-Ausführung die sinnvollere Reserve.';
    }else if(size==='l'){
      hook='kralle';hookWhy='Mehrere Haltepunkte geben größeren Fischen zusätzlichen Halt.';
    }else if(food==='makrele' || size==='m'){
      hook=food==='makrele'?'doppel':'std';
      hookWhy=food==='makrele'?'Zwei Haltepunkte geben kräftigeren Fischen mehr Stabilität.':'Für diesen Gewichtsbereich ist der Standardhaken die einfache, passende Grundwahl.';
    }

    if(taste==='mild')wood='mehl-erle';
    else if(taste==='fruity')wood='mehl-kirsche';
    else if(taste==='strong')wood='mehl-eiche';
    else wood='mehl-buche';

    if(food==='forelle' || food==='makrele'){
      if(taste==='spicy')brine='lauge-forelle-2';
      else if(taste==='fruity')brine='lauge-forelle-6';
      else if(taste==='mild')brine='lauge-forelle-7';
      else brine='lauge-forelle-0';
    }else if(food==='aal'){
      if(taste==='spicy')brine='lauge-aal-1';
      else if(taste==='mild')brine='lauge-aal-2';
      else brine='lauge-aal-0';
    }

    const products=[];
    const hp=prod(hook);if(hp)products.push({...hp,why:hookWhy});
    if(brine){const bp=prod(brine);if(bp)products.push({...bp,why:'Passende Würzrichtung für dein ausgewähltes Räuchergut. Dosierung und Einlegezeit immer nach der konkreten Packungsangabe.'});}
    const wp=prod(wood);if(wp)products.push({...wp,why:taste==='mild'?'Mildes Rauchprofil, das den Eigengeschmack weniger überdeckt.':taste==='fruity'?'Mild-fruchtiger Rauchakzent.':taste==='strong'?'Kräftigeres Rauchprofil für aromatisch robustes Räuchergut.':'Klassischer, vielseitiger Rauchgeschmack.'});

    const material=food==='schinken'?'Bei salzhaltiger Umgebung ist eine korrosionsbeständige Edelstahlwahl sinnvoll. V2A ist die robuste Allround-Wahl; V4A ist für häufige Salz-/Feuchtebelastung die hochwertigere Option.':'Für den Einstieg ist V2A eine robuste Allround-Wahl. Bei häufiger Salz- und Feuchtebelastung ist V4A die hochwertigere Option.';
    const beginner=exp==='beginner'?'Als Einsteiger: Temperatur kontrollieren, Räuchergut gut trocknen lassen und Dosierungen nicht frei schätzen.':'Mit Erfahrung kannst du Rauchintensität und Holzart gezielter auf dein gewünschtes Aroma abstimmen.';
    const special=food==='lachs'?'Für Lachs/Filet empfehle ich hier bewusst keine pauschale Lake, weil Zubereitungsart und Rezept stärker variieren.':food==='schinken'?'Pökelmischung und Dosierung müssen zum konkreten Rezept beziehungsweise Produkt passen; deshalb füge ich keine pauschale Mischung hinzu.':'';
    return {products,advice:`${material} ${beginner}${special?' '+special:''}`.trim()};
  }

  function buildShell(){
    document.querySelector('.productAiAdvisor')?.remove();
    document.querySelectorAll('.smokyLauncher,.smokyHelp,.smokyNudge').forEach(x=>x.remove());

    const oldLauncher=document.querySelector('.ai');
    const launcher=document.createElement('button');
    launcher.type='button';launcher.className='ai smokyProLauncher';launcher.setAttribute('aria-label','Smoky Beratung öffnen');
    launcher.innerHTML='<img src="assets/smoky-hilfe-button.png" alt=""><span><b>Smoky</b><small>Beratung</small></span><i aria-hidden="true">→</i>';
    if(oldLauncher)oldLauncher.replaceWith(launcher);else document.body.appendChild(launcher);

    document.getElementById('aiPanel')?.remove();
    const panel=document.createElement('aside');
    panel.id='aiPanel';panel.className='aiPanel smokyProPanel';panel.setAttribute('aria-label','Smoky Räucherberater');
    panel.innerHTML=`
      <header class="smokyProHead">
        <div class="smokyProIdentity"><img src="assets/smoky-hilfe-button.png" alt="Smoky"><div><span class="smokyProStatus"><i></i> Beratung bereit</span><b>Smoky – dein Räucherberater</b><small>Produkte finden · Warenkorb prüfen · Fragen klären</small></div></div>
        <button type="button" class="smokyProClose" aria-label="Smoky schließen">×</button>
      </header>
      <nav class="smokyProTabs" aria-label="Smoky Funktionen">
        <button type="button" data-smoky-tab="guide" class="active"><span>✦</span>30-Sek.-Beratung</button>
        <button type="button" data-smoky-tab="cart"><span>🛒</span>Warenkorb-Check</button>
        <button type="button" data-smoky-tab="chat"><span>💬</span>Frage stellen</button>
      </nav>
      <div class="smokyProBody">
        <section class="smokyProPane active" data-smoky-pane="guide"></section>
        <section class="smokyProPane" data-smoky-pane="cart"></section>
        <section class="smokyProPane" data-smoky-pane="chat"></section>
      </div>`;
    document.body.appendChild(panel);

    buildChat();renderGuide();renderCartCheck();wireShell();
  }

  function wireShell(){
    const panel=document.getElementById('aiPanel');
    document.querySelector('.smokyProLauncher')?.addEventListener('click',()=>toggle(true));
    panel.querySelector('.smokyProClose')?.addEventListener('click',()=>toggle(false));
    panel.querySelectorAll('[data-smoky-tab]').forEach(b=>b.addEventListener('click',()=>setMode(b.dataset.smokyTab)));
    document.addEventListener('keydown',e=>{if(e.key==='Escape'&&panel.classList.contains('open'))toggle(false)});
  }

  function toggle(force){
    const p=document.getElementById('aiPanel');if(!p)return;
    const open=typeof force==='boolean'?force:!p.classList.contains('open');
    p.classList.toggle('open',open);document.body.classList.toggle('smoky-open',open);
    if(open){renderCartCheck();setTimeout(()=>p.querySelector('button, input')?.focus({preventScroll:true}),100);}
  }
  window.toggleAI=toggle;

  function setMode(mode){
    state.mode=mode;
    document.querySelectorAll('[data-smoky-tab]').forEach(b=>b.classList.toggle('active',b.dataset.smokyTab===mode));
    document.querySelectorAll('[data-smoky-pane]').forEach(p=>p.classList.toggle('active',p.dataset.smokyPane===mode));
    if(mode==='cart')renderCartCheck();
    if(mode==='chat')setTimeout(()=>document.getElementById('smokyProInput')?.focus(),50);
  }

  function progressHtml(step){
    return `<div class="smokyGuideProgress"><div><span>30-Sekunden-Beratung</span><b>${Math.min(step+1,4)} von 4</b></div><div class="smokyGuideTrack"><i style="width:${Math.min((step+1)*25,100)}%"></i></div></div>`;
  }

  function optionButton(key,label,icon=''){return `<button type="button" class="smokyChoice" data-choice="${esc(key)}"><span>${esc(icon)}</span><b>${esc(label)}</b><i>→</i></button>`;}

  function renderGuide(){
    const host=document.querySelector('[data-smoky-pane="guide"]');if(!host)return;
    if(state.step>=4 && state.food && state.size && state.taste && state.exp){renderResult(host);return;}
    let title='',sub='',options={};
    if(state.step===0){title='Was möchtest du räuchern?';sub='Ich stelle dir nur vier kurze Fragen und stelle danach ein passendes Paket zusammen.';options=FOOD;}
    else if(state.step===1){title=state.food==='schinken'?'Wie schwer ist das Fleischstück ungefähr?':'Wie schwer ist dein Räuchergut ungefähr?';sub='Das Gewicht entscheidet vor allem über die passende Hakenform und Stabilität.';options=SIZE;}
    else if(state.step===2){title='Welches Aroma möchtest du?';sub='Ich passe Räuchermehl und – wenn sinnvoll – die Lake daran an.';options=state.food==='schinken'?{mild:TASTE.mild,classic:TASTE.classic,strong:TASTE.strong,fruity:TASTE.fruity}:{mild:TASTE.mild,classic:TASTE.classic,spicy:TASTE.spicy,fruity:TASTE.fruity};}
    else {title='Wie viel Erfahrung hast du?';sub='So passe ich die Empfehlung und die Erklärung an.';options=EXP;}
    host.innerHTML=`${progressHtml(state.step)}<div class="smokyGuideQuestion"><span class="smokyKicker">SMOKY EMPFIEHLT NICHT BLIND</span><h3>${esc(title)}</h3><p>${esc(sub)}</p></div><div class="smokyChoices">${Object.entries(options).map(([k,v])=>optionButton(k,v.label,v.icon)).join('')}</div>${state.step>0?'<button type="button" class="smokyBack">← Zurück</button>':''}`;
    host.querySelectorAll('[data-choice]').forEach(b=>b.addEventListener('click',()=>{
      const val=b.dataset.choice;
      if(state.step===0)state.food=val;else if(state.step===1)state.size=val;else if(state.step===2)state.taste=val;else state.exp=val;
      state.step++;saveState();renderGuide();
    }));
    host.querySelector('.smokyBack')?.addEventListener('click',()=>{state.step=Math.max(0,state.step-1);renderGuide();});
  }

  function recommendationCard(p){
    return `<article class="smokyRecCard" data-product-id="${esc(p.id)}"><div class="smokyRecMedia"><img src="${esc(p.img)}" alt="${esc(p.name)}"></div><div class="smokyRecInfo"><span class="smokyRecTag">Empfohlen</span><h4>${esc(p.name)}</h4><p>${esc(p.why)}</p><div class="smokyRecBottom"><strong>${euro(p.price)}</strong><div><a href="${esc(p.url)}">Details</a><button type="button" data-smoky-add="${esc(p.id)}">+ Hinzufügen</button></div></div></div></article>`;
  }

  function renderResult(host){
    const result=recommendations();state.lastResult=result;saveState();
    const subtotal=result.products.reduce((s,p)=>s+Number(p.price||0),0);
    const missing=Math.max(0,SHIPPING_FREE-subtotal);
    host.innerHTML=`
      <div class="smokyResultHead"><span class="smokyResultOk">✓ Empfehlung fertig</span><h3>Das passt zu deiner Auswahl.</h3><p>${esc(FOOD[state.food]?.label)} · ${esc(SIZE[state.size]?.label)} · ${esc(TASTE[state.taste]?.label)} · ${esc(EXP[state.exp]?.label)}</p></div>
      <div class="smokyRecList">${result.products.map(recommendationCard).join('')}</div>
      <div class="smokyResultSummary"><div><span>Empfohlener Warenwert</span><strong>${euro(subtotal)}</strong></div>${missing>0?`<p>Noch ${euro(missing)} bis zur aktuellen Versandkostenfrei-Grenze von ${euro(SHIPPING_FREE)}.</p>`:'<p class="ok">✓ Versandkostenfrei-Grenze erreicht.</p>'}</div>
      <div class="smokyAdvice"><b>Smokys Hinweis</b><p>${esc(result.advice)}</p></div>
      <div class="smokyResultActions"><button type="button" class="smokyPrimary" data-smoky-add-bundle>Empfohlenes Paket in den Warenkorb</button><button type="button" class="smokySecondary" data-smoky-restart>Beratung neu starten</button></div>
      <small class="smokyFine">Bei konfigurierbaren Räucherhaken wird die Grundausführung hinzugefügt. Länge, Material und Spitzenausführung kannst du auf der Produktseite gezielt anpassen.</small>`;
    host.querySelectorAll('[data-smoky-add]').forEach(b=>b.addEventListener('click',()=>addProduct(b.dataset.smokyAdd,b)));
    host.querySelector('[data-smoky-add-bundle]')?.addEventListener('click',()=>addBundle(result.products.map(x=>x.id)));
    host.querySelector('[data-smoky-restart]')?.addEventListener('click',()=>{state.step=0;state.food=state.size=state.taste=state.exp='';state.lastResult=null;saveState();renderGuide();});
  }

  function addProduct(id,button){
    if(typeof window.addToCart==='function'){
      window.addToCart(id);button?.classList.add('added');if(button)button.textContent='✓ Hinzugefügt';renderCartCheck();
    }else location.href=prod(id)?.url||'shop.html';
  }
  function addBundle(ids){
    const unique=[...new Set(ids.filter(Boolean))];
    if(typeof window.addToCart==='function'){
      unique.forEach(id=>window.addToCart(id));renderCartCheck();if(typeof window.openCart==='function')window.openCart();
    }else location.href='shop.html';
  }

  function cartSuggestions(items){
    const ids=new Set(items.map(cartBaseId));
    const hasHook=[...ids].some(id=>catalog[id]?.kind==='hook');
    const hasWood=[...ids].some(id=>catalog[id]?.kind==='wood');
    const hasBrine=[...ids].some(id=>catalog[id]?.kind==='brine');
    const aal=ids.has('aal');const meat=ids.has('fleisch');
    const suggestions=[];
    if(!hasHook)return {complete:false,suggestions:[],message:'Ich sehe noch keinen Räucherhaken im Warenkorb. Damit ich nicht blind empfehle, starte bitte zuerst die 30-Sekunden-Beratung.'};
    if(!hasWood)suggestions.push(prod(meat?'mehl-eiche':aal?'mehl-erle':'mehl-buche'));
    if(!hasBrine&&!meat)suggestions.push(prod(aal?'lauge-aal-0':'lauge-forelle-0'));
    return {complete:hasHook&&hasWood&&(hasBrine||meat),suggestions:suggestions.filter(Boolean),message:suggestions.length?'Diese Ergänzungen sind für ein klassisches Setup sinnvoll. Du musst sie nicht zwingend mitbestellen.':'Für ein klassisches Setup wirkt dein Warenkorb vollständig.'};
  }

  function renderCartCheck(){
    const host=document.querySelector('[data-smoky-pane="cart"]');if(!host)return;
    const items=cartItems();
    if(!items.length){host.innerHTML='<div class="smokyEmpty"><span>🛒</span><h3>Dein Warenkorb ist noch leer.</h3><p>Starte die 30-Sekunden-Beratung. Smoky stellt dir danach ein passendes Paket zusammen.</p><button type="button" class="smokyPrimary" data-start-guide>Beratung starten</button></div>';host.querySelector('[data-start-guide]')?.addEventListener('click',()=>setMode('guide'));return;}
    const enriched=items.map(x=>({raw:x,p:prod(cartBaseId(x))})).filter(x=>x.p);
    const audit=cartSuggestions(items);
    host.innerHTML=`<div class="smokyCartHead"><span class="smokyKicker">WARENKORB-CHECK</span><h3>${audit.complete?'Sieht gut aus.':'Da fehlt möglicherweise noch etwas.'}</h3><p>${esc(audit.message)}</p></div>
      <div class="smokyCartExisting">${enriched.map(x=>`<div><img src="${esc(x.p.img)}" alt=""><span><b>${esc(x.p.name)}</b><small>${x.raw.qty||1} × ${euro(x.p.price)}</small></span><i>✓</i></div>`).join('')}</div>
      ${audit.suggestions.length?`<div class="smokyCartSuggest"><h4>Sinnvolle Ergänzungen</h4>${audit.suggestions.map(p=>`<article><img src="${esc(p.img)}" alt=""><div><b>${esc(p.name)}</b><span>${euro(p.price)}</span></div><button type="button" data-cart-add="${esc(p.id)}">+ Hinzufügen</button></article>`).join('')}</div>`:''}
      <div class="smokyCartActions"><button type="button" class="smokySecondary" data-cart-guide>Beratung anpassen</button><button type="button" class="smokyPrimary" data-open-cart>Warenkorb öffnen</button></div>`;
    host.querySelectorAll('[data-cart-add]').forEach(b=>b.addEventListener('click',()=>addProduct(b.dataset.cartAdd,b)));
    host.querySelector('[data-cart-guide]')?.addEventListener('click',()=>setMode('guide'));
    host.querySelector('[data-open-cart]')?.addEventListener('click',()=>{toggle(false);if(typeof window.openCart==='function')window.openCart();});
  }

  function buildChat(){
    const host=document.querySelector('[data-smoky-pane="chat"]');if(!host)return;
    const pageProduct=currentPageProduct();
    host.innerHTML=`
      <div class="smokyChatIntro"><span class="smokyKicker">FREIE FRAGE</span><h3>Frag Smoky wie im Fachgeschäft.</h3><p>Kurze, verständliche Antworten. Produktempfehlungen orientieren sich an den Daten im Shop.</p></div>
      <div class="smokyChatQuick">${pageProduct?`<button type="button" data-chat-q="Passt ${esc(pageProduct.name)} zu meinem Vorhaben?">Passt dieses Produkt?</button>`:''}<button type="button" data-chat-q="Welcher Räucherhaken passt zu meinem Räuchergut?">Haken wählen</button><button type="button" data-chat-q="Welches Räuchermehl passt zu meinem Vorhaben?">Räuchermehl wählen</button><button type="button" data-chat-q="Erkläre mir V2A und V4A einfach.">V2A oder V4A?</button></div>
      <div class="smokyChatMessages" id="smokyProMessages"><div class="smokyChatMsg bot">Hallo! Wenn du eine konkrete Kaufempfehlung möchtest, nenne mir Räuchergut, Gewicht und gewünschten Geschmack.</div></div>
      <div class="smokyChatComposer"><label><input type="checkbox" id="smokyProVoiceOut"> Antworten vorlesen</label><div><input id="smokyProInput" type="text" autocomplete="off" placeholder="Deine Frage an Smoky …"><button type="button" id="smokyProMic" aria-label="Spracheingabe">🎤</button><button type="button" id="smokyProSend">Senden</button></div><small>Die Produktempfehlung funktioniert auch ohne Live-KI. Freie Fragen nutzen die Server-KI, wenn sie aktiviert ist.</small></div>`;
    host.querySelectorAll('[data-chat-q]').forEach(b=>b.addEventListener('click',()=>askChat(b.dataset.chatQ)));
    host.querySelector('#smokyProSend')?.addEventListener('click',()=>askChat());
    host.querySelector('#smokyProInput')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();askChat();}});
    wireMic();
  }

  function addChat(text,who){
    const box=document.getElementById('smokyProMessages');if(!box)return null;
    const d=document.createElement('div');d.className='smokyChatMsg '+who;d.textContent=text;box.appendChild(d);box.scrollTop=box.scrollHeight;return d;
  }
  async function askChat(preset){
    if(state.busy)return;
    const input=document.getElementById('smokyProInput');const q=String(preset||(input?.value||'')).trim();if(!q)return;
    addChat(q,'user');if(input)input.value='';
    const wait=addChat('Smoky prüft das kurz …','bot pending');state.busy=true;
    try{wait.textContent=await liveAnswer(q);}catch(e){wait.textContent=fallbackAnswer(q);}
    wait.classList.remove('pending');state.busy=false;
    if(document.getElementById('smokyProVoiceOut')?.checked)speak(wait.textContent);
  }
  window.ask=askChat;

  function speak(text){
    if(!window.speechSynthesis)return;
    speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(text);u.lang='de-DE';u.rate=.96;
    const voices=speechSynthesis.getVoices();const de=voices.find(v=>/de-DE/i.test(v.lang)&&/anna|katja|helena|female|siri|google/i.test(v.name))||voices.find(v=>/de/i.test(v.lang));if(de)u.voice=de;speechSynthesis.speak(u);
  }
  function wireMic(){
    const mic=document.getElementById('smokyProMic');if(!mic)return;
    const SR=window.SpeechRecognition||window.webkitSpeechRecognition;
    if(!SR){mic.disabled=true;mic.title='Spracheingabe wird von diesem Browser nicht unterstützt';return;}
    mic.addEventListener('click',()=>{
      try{const r=new SR();r.lang='de-DE';r.interimResults=false;r.maxAlternatives=1;mic.classList.add('listening');r.onresult=e=>{const input=document.getElementById('smokyProInput');if(input){input.value=e.results[0][0].transcript;input.focus();}};r.onend=()=>mic.classList.remove('listening');r.onerror=()=>mic.classList.remove('listening');r.start();}catch(e){}
    });
  }
  window.voice=function(){document.getElementById('smokyProMic')?.click();};

  function nudge(){
    try{if(sessionStorage.getItem('rh24_smoky_nudge_v106'))return;sessionStorage.setItem('rh24_smoky_nudge_v106','1');}catch(e){}
    const launcher=document.querySelector('.smokyProLauncher');if(!launcher)return;
    const d=document.createElement('div');d.className='smokyNudge';d.innerHTML='<button type="button" aria-label="Hinweis schließen">×</button><b>Unsicher bei Haken, Lake oder Räuchermehl?</b><span>Ich stelle dir 4 kurze Fragen.</span><a href="#">30-Sekunden-Beratung starten →</a>';
    launcher.before(d);d.querySelector('button').addEventListener('click',()=>d.remove());d.querySelector('a').addEventListener('click',e=>{e.preventDefault();d.remove();setMode('guide');toggle(true);});
    setTimeout(()=>d.classList.add('show'),50);setTimeout(()=>d.remove(),12000);
  }

  async function init(){
    loadState();await syncCatalog();
    // Always start with a fresh guided flow; stored values are kept only as context until the user restarts.
    state.step=0;state.lastResult=null;
    buildShell();
    document.documentElement.dataset.smokyVersion=VERSION;
    setTimeout(nudge,18000);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
