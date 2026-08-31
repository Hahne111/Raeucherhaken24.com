(()=>{
  'use strict';
  const VERSION='56';
  const API='smoky-api.php';
  const productPage=/raeucherhaken-|raeuchermehl-|raeucherlauge-|fleischerhaken-/i.test(location.pathname);

  const escText=s=>String(s||'')
    .replace(/\[([^\]]+)\]\((?:https?:\/\/|www\.)[^)]+\)/gi,'$1')
    .replace(/https?:\/\/\S+|www\.\S+/gi,'')
    .replace(/cite[^]*/g,'')
    .replace(/【[^】]*(?:Quelle|source|cite)[^】]*】/gi,'')
    .replace(/\n{3,}/g,'\n\n').trim();

  function pageContext(){
    const title=(document.querySelector('h1')?.textContent||document.title||'').trim();
    const product=(document.querySelector('.productInfo')?.innerText||'').replace(/\s+/g,' ').trim().slice(0,1800);
    const main=(document.querySelector('main')?.innerText||'').replace(/\s+/g,' ').trim().slice(0,1200);
    return `Seitentitel: ${title}. ${product||main}`.slice(0,2800);
  }

  function fallback(q){
    q=(q||'').toLowerCase();
    if(q.includes('marktplatz')||q.includes('anzeige')||q.includes('verkaufen')||q.includes('verschenken')||q.includes('jahreszugang')) return 'Im Bereich An- & Verkaufen registrierst du dich mit deiner E-Mail-Adresse. Der Jahreszugang kostet 19,99 € inkl. MwSt. und gilt 1 Jahr ab Freischaltung. Du kannst gleichzeitig bis zu 10 Anzeigen einstellen – zum Verkaufen, Suchen oder Verschenken. Verkäufer-E-Mails werden nicht öffentlich angezeigt.';
    if(q.includes('forell')) return 'Für Forelle ist der Standardhaken meist die einfachste Wahl. Für größere oder schwerere Fische bietet ein Doppeldorn mehr Halt. Buche oder Erle passen als klassisches Räuchermehl.';
    if(q.includes('v4a')||q.includes('v2a')||q.includes('material')) return 'V2A ist die robuste Allround-Wahl. V4A ist sinnvoll bei viel Feuchtigkeit, Salz und häufiger Nutzung. Wenn du unsicher bist, nimm V2A.';
    if(q.includes('räuchermehl')||q.includes('holz')) return 'Buche ist der vielseitige Klassiker. Erle ist mild für Fisch. Eiche ist kräftiger für Fleisch und Schinken. Kirsche wirkt mild und leicht fruchtig.';
    if(q.includes('pökel')||q.includes('schinken')) return 'Beim Pökeln ist die Dosierung auf der konkreten Mischung verbindlich. Halte dich genau an Packungsangabe, Pökelzeit und Temperatur. Wenn du mir Fleischart und Gewicht nennst, kann ich den Ablauf verständlich einordnen.';
    return 'Sag mir kurz, was du räuchern möchtest und ungefähr Größe oder Gewicht. Ich gebe dir dann eine klare Empfehlung.';
  }

  async function liveAnswer(question){
    const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},cache:'no-store',body:JSON.stringify({question,context:pageContext(),page:location.pathname.split('/').pop()||'index.html'})});
    let data={};
    try{data=await r.json()}catch(e){}
    if(!r.ok||!data.answer) throw new Error(data.error||'Live-Beratung nicht erreichbar');
    return escText(data.answer);
  }

  function injectIfMissing(){
    if(!document.querySelector('.ai')){
      const b=document.createElement('button'); b.className='ai'; b.type='button'; b.setAttribute('aria-label','Smoky KI-Berater öffnen'); document.body.appendChild(b);
    }
    if(!document.getElementById('aiPanel')){
      const p=document.createElement('div'); p.className='aiPanel'; p.id='aiPanel'; p.innerHTML=`
        <div class="aiHead"><div class="smokyAiTitle"><img src="assets/smoky-hilfe-button.png" alt=""><div><b>Smoky – KI-Räucherberater</b><br><small>Kurze Antworten · Live-Recherche im Hintergrund</small></div></div><button class="smokyClose" type="button" aria-label="Schließen">×</button></div>
        <div class="aiMsgs" id="msgs"><div class="msg bot">Hallo! Was möchtest du räuchern oder welches Produkt suchst du?</div></div>
        <div class="smokyQuick"><button data-smoky-q="Welcher Räucherhaken passt zu meinem Fisch?">Haken wählen</button><button data-smoky-q="Welches Räuchermehl passt zu meinem Vorhaben?">Räuchermehl</button><button data-smoky-q="Erkläre mir kurz den Unterschied zwischen V2A und V4A.">Material</button></div>
        <div class="smokyInputArea"><label class="voiceOpt"><input type="checkbox" id="voiceOut"> Antwort vorlesen</label><div class="aiForm"><input id="q" autocomplete="off" enterkeyhint="send" placeholder="Frage an Smoky …"><button class="smokyMic" type="button" aria-label="Spracheingabe">🎤</button><button class="send" type="button">Senden</button></div><div class="smokyPrivacy">Smoky zeigt keine fremden Links. Antworten werden kurz und eigenständig formuliert.</div></div>`;
      document.body.appendChild(p);
    }
  }

  function normaliseExisting(){
    document.querySelectorAll('.smokyLauncher,.smokyHelp').forEach(x=>x.remove());
    const b=document.querySelector('.ai');
    if(b){b.innerHTML='<img src="assets/smoky-hilfe-button.png" alt=""><span>Smoky fragen</span>';b.setAttribute('aria-label','Smoky KI-Berater öffnen');b.removeAttribute('onclick');b.onclick=null;}
    const p=document.getElementById('aiPanel');
    if(!p)return;
    const head=p.querySelector('.aiHead');
    if(head) head.innerHTML='<div class="smokyAiTitle"><img src="assets/smoky-hilfe-button.png" alt=""><div><b>Smoky – KI-Räucherberater</b><br><small>Kurze Antworten · Live-Recherche im Hintergrund</small></div></div><button class="smokyClose" type="button" aria-label="Schließen">×</button>';
    const msgs=p.querySelector('#msgs');
    if(msgs && !msgs.dataset.v56){msgs.dataset.v56='1';msgs.innerHTML='<div class="msg bot">Hallo! Was möchtest du räuchern oder welches Produkt suchst du?</div>';}
    let inputArea=p.querySelector('.smokyInputArea');
    if(!inputArea){
      const oldBottom=p.lastElementChild;
      if(oldBottom) oldBottom.outerHTML='<div class="smokyInputArea"><label class="voiceOpt"><input type="checkbox" id="voiceOut"> Antwort vorlesen</label><div class="aiForm"><input id="q" autocomplete="off" enterkeyhint="send" placeholder="Frage an Smoky …"><button class="smokyMic" type="button" aria-label="Spracheingabe">🎤</button><button class="send" type="button">Senden</button></div><div class="smokyPrivacy">Smoky zeigt keine fremden Links. Antworten werden kurz und eigenständig formuliert.</div></div>';
    }
    if(!p.querySelector('.smokyQuick')){
      const quick=document.createElement('div');quick.className='smokyQuick';quick.innerHTML='<button data-smoky-q="Welcher Räucherhaken passt zu meinem Fisch?">Haken wählen</button><button data-smoky-q="Welches Räuchermehl passt zu meinem Vorhaben?">Räuchermehl</button><button data-smoky-q="Erkläre mir kurz den Unterschied zwischen V2A und V4A.">Material</button>';
      (p.querySelector('.smokyInputArea')||p.lastElementChild).before(quick);
    }
  }

  function addMsg(text,cls){
    const box=document.getElementById('msgs'); if(!box)return null;
    const d=document.createElement('div');d.className='msg '+cls;d.textContent=text;box.appendChild(d);box.scrollTop=box.scrollHeight;return d;
  }

  let busy=false;
  window.toggleAI=function(force){
    const p=document.getElementById('aiPanel');if(!p)return;
    const open=typeof force==='boolean'?force:!p.classList.contains('open');
    p.classList.toggle('open',open);document.body.classList.toggle('smoky-open',open);
    if(open)setTimeout(()=>document.getElementById('q')?.focus({preventScroll:true}),120);
  };

  window.ask=async function(preset){
    if(busy)return;
    const i=document.getElementById('q');const q=String(preset||(i?.value||'')).trim();if(!q)return;
    addMsg(q,'user');if(i)i.value='';
    const wait=addMsg('Smoky prüft das kurz …','bot smokyPending');busy=true;
    try{
      const text=await liveAnswer(q); wait.textContent=text||fallback(q);
    }catch(e){wait.textContent=fallback(q);}
    wait.classList.remove('smokyPending');busy=false;
    const voice=document.getElementById('voiceOut');if(voice?.checked && window.speechSynthesis){speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(wait.textContent);u.lang='de-DE';u.rate=.97;speechSynthesis.speak(u);}
  };

  function wire(){
    document.querySelector('.ai')?.addEventListener('click',()=>window.toggleAI());
    document.querySelector('.smokyClose')?.addEventListener('click',()=>window.toggleAI(false));
    document.querySelector('.aiForm .send')?.addEventListener('click',()=>window.ask());
    document.querySelector('.smokyMic')?.addEventListener('click',()=>window.voice?.());
    document.getElementById('q')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();window.ask();}});
    document.querySelectorAll('[data-smoky-q]').forEach(b=>b.addEventListener('click',()=>window.ask(b.dataset.smokyQ)));
    document.addEventListener('keydown',e=>{if(e.key==='Escape')window.toggleAI(false)});
  }

  function mobileDock(){
    const dock=document.querySelector('.mobileDock');if(!dock)return;
    const wish=[...dock.querySelectorAll('button,a')].find(x=>/Merkliste/i.test(x.textContent||''));
    if(wish){wish.outerHTML='<button class="mobileSmoky" type="button" onclick="toggleAI(true)"><span>💬</span>Smoky</button>';}
  }

  function mobileCategories(){
    const side=document.querySelector('.sidebar');if(!side||document.querySelector('.mobileCategoryToggle'))return;
    const btn=document.createElement('button');btn.className='mobileCategoryToggle';btn.type='button';btn.innerHTML='<span>☰</span> Kategorien & Bereiche <b>öffnen</b>';
    side.before(btn);side.classList.add('mobileCollapsed');
    btn.addEventListener('click',()=>{const open=side.classList.toggle('mobileOpen');btn.querySelector('b').textContent=open?'schließen':'öffnen';});
  }

  function productHint(){
    if(!productPage)return;
    const advisor=document.querySelector('.productAiAdvisor');
    if(advisor) advisor.classList.add('v56Unified');
  }

  function init(){
    injectIfMissing();normaliseExisting();wire();mobileDock();mobileCategories();productHint();
    document.documentElement.dataset.smokyVersion=VERSION;
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>setTimeout(init,0));else setTimeout(init,0);
})();
