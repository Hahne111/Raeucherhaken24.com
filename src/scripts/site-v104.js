/* Räucherhaken24 V104 · Premium Light Storefront Runtime
   Replaces the legacy V82 header/navigation presentation without changing shop business logic. */
(()=>{
  'use strict';
  if(window.__RH24_V104_SITE__) return;
  window.__RH24_V104_SITE__=true;

  const pages=[
    {t:'Räucherhaken Standard',d:'Klassiker für Forelle und Makrele',u:'raeucherhaken-standard.html',g:'Räucherhaken'},
    {t:'Räucherhaken Standard Aal',d:'Für Aal und schlanke Fische',u:'raeucherhaken-standard-aal.html',g:'Räucherhaken'},
    {t:'Räucherhaken Ultra',d:'Für große und schwere Fische',u:'raeucherhaken-ultra.html',g:'Räucherhaken'},
    {t:'Räucherhaken Kralle',d:'Mehrpunkt-Halt für schwere Fische',u:'raeucherhaken-kralle.html',g:'Räucherhaken'},
    {t:'Räucherhaken Filet',d:'Für Filets und flache Räucherstücke',u:'raeucherhaken-filet.html',g:'Räucherhaken'},
    {t:'Räucherhaken Doppeldorn',d:'Sicherer Halt mit zwei Dornen',u:'raeucherhaken-doppeldorn.html',g:'Räucherhaken'},
    {t:'Fleischerhaken S-Form',d:'Für Schinken und schwere Fleischstücke',u:'fleischerhaken-s-form-5mm.html',g:'Räucherhaken'},
    {t:'Räucherlaugen',d:'Forelle, Aal und Geschmacksvarianten',u:'raeucherlaugen.html',g:'Räuchern'},
    {t:'Räucherlauge Forelle',d:'Alle Forellen-Varianten',u:'raeucherlauge-forelle.html',g:'Räuchern'},
    {t:'Räucherlauge Aal',d:'Alle Aal-Varianten',u:'raeucherlauge-aal.html',g:'Räuchern'},
    {t:'Räuchermehl Buche',d:'Klassischer Allrounder',u:'raeuchermehl-buche.html',g:'Räuchern'},
    {t:'Räuchermehl Erle',d:'Mild und besonders gut für Fisch',u:'raeuchermehl-erle.html',g:'Räuchern'},
    {t:'Räuchermehl Birke',d:'Fein und mild',u:'raeuchermehl-birke.html',g:'Räuchern'},
    {t:'Räuchermehl Eiche',d:'Kräftig für Fleisch und Schinken',u:'raeuchermehl-eiche.html',g:'Räuchern'},
    {t:'Räuchermehl Kirsche',d:'Mild-fruchtiges Raucharoma',u:'raeuchermehl-kirsche.html',g:'Räuchern'},
    {t:'Naturgewürze',d:'Kräuter, Gewürze und Zutaten',u:'naturgewuerze.html',g:'Räuchern'},
    {t:'Thermometer',d:'Räucherofen, Grill und Kerntemperatur',u:'thermometer.html',g:'Zubehör'},
    {t:'Räucherfisch',d:'Fischarten, Vorbereitung und Ablauf',u:'raeucherfisch.html',g:'Wissen'},
    {t:'Schinken selber machen',d:'Pökeln, Trocknen, Räuchern und Reifen',u:'schinken.html',g:'Wissen'},
    {t:'Rezepte & Anleitungen',d:'Rezepte und Schritt-für-Schritt-Wissen',u:'rezepte-anleitungen.html',g:'Wissen'},
    {t:'Sonderanfertigung & Prototyp',d:'Individuelle Hakenprojekte',u:'sonderanfertigung-prototyp.html',g:'Service'},
    {t:'Kontakt',d:'Beratung und Kontaktmöglichkeiten',u:'kontakt.html',g:'Service'},
    {t:'Zahlung & Versand',d:'Versand- und Zahlungsinformationen',u:'zahlung-versand.html',g:'Service'},
    {t:'Kundenlogin',d:'Persönlicher Kundenbereich',u:'kundenlogin.html',g:'Service'},
    {t:'Händlerlogin',d:'Zugang für Händler',u:'haendlerlogin.html',g:'Service'}
  ];
  /* V2026.4 · Hauptnavigation exakt nach Gestaltungsvorlage:
       Räucherhaken · Räucherholz · Sets · Zubehör · Wissen · Über uns
     Jeder Punkt führt ausschliesslich auf Seiten, die es gibt.
       – "Räucherholz" sind die fünf Räuchermehl-Sorten.
       – "Sets" sind die tatsächlich als 10er-Set verkauften Haken
         (alle Fisch-Räucherhaken sind 10-Stück-Sets, siehe Shop).
       – "Zubehör" bündelt Laugen, Naturgewürze und Thermometer.
     Es wird kein Menüpunkt erfunden. */
  const HOLZ = ['raeuchermehl-buche.html','raeuchermehl-erle.html','raeuchermehl-birke.html','raeuchermehl-eiche.html','raeuchermehl-kirsche.html'];
  const SETS = ['raeucherhaken-standard.html','raeucherhaken-standard-aal.html','raeucherhaken-ultra.html','raeucherhaken-kralle.html','raeucherhaken-filet.html','raeucherhaken-doppeldorn.html'];
  const ZUBEHOER = ['raeucherlaugen.html','raeucherlauge-forelle.html','raeucherlauge-aal.html','naturgewuerze.html','thermometer.html'];
  const setTitle = t => t.replace(/^Räucherhaken\s+/,'') + ' · 10er-Set';
  const groups={
    'Räucherhaken':{desc:'Nach Fisch, Gewicht und Einsatzzweck auswählen',items:pages.filter(x=>x.g==='Räucherhaken')},
    'Räucherholz':{desc:'Fünf Holzarten – vom milden Aroma bis kräftig',items:pages.filter(x=>HOLZ.includes(x.u))},
    'Sets':{desc:'Alle Fisch-Räucherhaken als 10-Stück-Set',items:pages.filter(x=>SETS.includes(x.u)).map(x=>({...x,t:setTitle(x.t)}))},
    'Zubehör':{desc:'Laugen, Naturgewürze und Thermometer',items:pages.filter(x=>ZUBEHOER.includes(x.u))},
    'Räuchern':{desc:'Lauge, Räuchermehl und Naturgewürze',items:pages.filter(x=>x.g==='Räuchern')},
    'Wissen':{desc:'Praxiswissen von Fisch bis Schinken',items:pages.filter(x=>x.g==='Wissen')},
    'Service':{desc:'Kontakt, Prototypen und Kundenbereiche',items:pages.filter(x=>x.g==='Service')},
    /* "Über uns" bündelt die Seiten über den Betrieb selbst.
       Die beiden Login-Seiten stehen – wie in der Vorlage – rechts
       hinter dem Kontosymbol, nicht im Menü. */
    'Über uns':{desc:'Fachbetrieb, Beratung und Sonderanfertigung',
      items:pages.filter(x=>x.g==='Service'&&!/login/i.test(x.u))}
  };
  const current=(location.pathname.split('/').pop()||'index.html').toLowerCase();
  const esc=s=>String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  const cartCount=()=>{const n=document.querySelector('[data-cart-count]');return n?(n.textContent||'0').trim()||'0':'0';};

  function renderHeader(){
    const top=document.querySelector('.top');
    if(!top||top.dataset.v104==='1') return;

    // V2026.1: Im alten Kopfbereich stehen Telefonnummer und E-Mail-Adresse.
    // Bisher wurden sie beim Neuaufbau der Kopfzeile ersatzlos überschrieben.
    // Sie werden jetzt vorher ausgelesen und in der Vertrauensleiste als
    // anklickbare Kontaktmöglichkeit erhalten.
    let phoneHref='', phoneText='', mailHref='';
    try{
      const card=top.querySelector('.consultationCard,.phone');
      if(card){
        const tel=card.querySelector('a[href^="tel:"]');
        const mail=card.querySelector('a[href^="mailto:"]');
        const num=card.querySelector('.consultationNumber');
        if(tel) phoneHref=tel.getAttribute('href')||'';
        if(mail) mailHref=mail.getAttribute('href')||'';
        phoneText=(num?num.textContent:'').trim();
        if(!phoneHref && phoneText){
          const digits=phoneText.replace(/[^0-9+]/g,'');
          if(digits.length>5) phoneHref='tel:'+digits;
        }
      }
    }catch(e){}

    top.dataset.v104='1';
    top.className='top rh104HeaderRoot';
    /* Telefon und E-Mail wandern in die Fusszeile (buildFooter) –
       die Vertrauensleiste bleibt wie in der Vorlage eine ruhige,
       zentrierte Zeile mit drei Aussagen und Punkt-Trennern. */
    window.__rh24Contact = { phoneHref, phoneText, mailHref };
    /* V2026.4 · Vertrauensleiste exakt im Aufbau der Vorlage
       (drei Aussagen · zentriert · Punkt-Trenner). Die Inhalte sind
       die BELEGTEN Werte dieses Shops:
         · Versandkostenfrei ab 39 €  → Warenkorb, Checkout, Produktkarten
         · 14 Tage Widerrufsrecht     → widerruf.html (gesetzliche Frist)
         · Handgefertigt in Deutschland → Produktseiten & Hero-Motiv
       Die Zahlen der Vorlage (69 €, 30 Tage, 10.000+ Kunden) wären hier
       falsche Angaben und werden bewusst nicht übernommen. */
    top.innerHTML='<div class="rh104TrustBar"><div class="wrap rh104TrustInner">'+
      '<span><b>Versandkostenfrei ab 39 €</b></span>'+
      '<i class="rhDot" aria-hidden="true">·</i>'+
      '<span><b>14 Tage Widerrufsrecht</b></span>'+
      '<i class="rhDot" aria-hidden="true">·</i>'+
      '<span><b>Handgefertigt in Deutschland</b></span>'+
      '</div></div>';
  }

  /* SVG-Liniensymbole für die Kopfzeile – gleiche Strichlogik wie die
     übrigen Icons der Seite (stroke, round caps). */
  const NAVICON = {
    search:'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="6.2"></circle><path d="M15.6 15.6 20 20"></path></svg>',
    user:'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="8.2" r="3.4"></circle><path d="M5.4 19.4c.9-3.2 3.5-4.9 6.6-4.9s5.7 1.7 6.6 4.9"></path></svg>',
    bag:'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6.2 8.4h11.6l-.9 10.2a1.8 1.8 0 0 1-1.8 1.6H8.9a1.8 1.8 0 0 1-1.8-1.6L6.2 8.4z"></path><path d="M9 10.4V6.8a3 3 0 0 1 6 0v3.6"></path></svg>'
  };

  function navGroup(name){
    const g=groups[name];
    return '<div class="rh24NavDrop" data-rh24-drop><button class="rh24DropBtn" type="button" aria-expanded="false">'+esc(name)+' <span class="chev">⌄</span></button><div class="rh24Mega"><div class="rh24MegaHead"><b>'+esc(name)+'</b><span>'+esc(g.desc)+'</span></div>'+g.items.map(i=>'<a href="'+esc(i.u)+'"><b>'+esc(i.t)+'</b><small>'+esc(i.d)+'</small></a>').join('')+'</div></div>';
  }

  function buildNav(){
    const top=document.querySelector('.top');
    if(!top||document.getElementById('rh24DynamicNav')) return;
    const shell=document.createElement('div');
    shell.className='rh24NavShell'; shell.id='rh24DynamicNav';
    /* V2026.4 · Kopfzeile im Aufbau der Vorlage:
         links Wortmarke · Mitte sechs Punkte · rechts drei Symbole.
       Die Wortmarke ist reiner Text in Sperrsatz – kein Logobild.
       Kundendaten-Zugänge liegen hinter dem Kontosymbol. */
    shell.innerHTML='<div class="wrap rh24Nav">'+
      '<button class="rh24MobileMenuBtn" id="rh24MobileMenu" type="button" aria-expanded="false" aria-label="Menü öffnen">☰</button>'+
      '<a class="rh104Brand" href="index.html" aria-label="Räucherhaken24 Startseite"><span class="rh104BrandText">RÄUCHERHAKEN<strong>24</strong></span></a>'+
      '<div class="rh24NavLinks">'+
        navGroup('Räucherhaken')+
        navGroup('Räucherholz')+
        navGroup('Sets')+
        navGroup('Zubehör')+
        navGroup('Wissen')+
        navGroup('Über uns')+
      '</div>'+
      '<div class="rh24NavTools">'+
        '<div class="rh24NavDrop rh24AccountDrop" data-rh24-drop>'+
          '<button class="rh104NavIcon rh24DropBtn rhIconBtn" type="button" aria-expanded="false" aria-label="Konto"><span class="ico">'+NAVICON.user+'</span></button>'+
          '<div class="rh24Mega rh24AccountMega">'+
            '<div class="rh24MegaHead"><b>Konto</b><span>Persönliche Bereiche</span></div>'+
            '<a href="kundenlogin.html"><b>Kundenlogin</b><small>Persönlicher Kundenbereich</small></a>'+
            '<a href="haendlerlogin.html"><b>Händlerlogin</b><small>Zugang für Händler</small></a>'+
          '</div>'+
        '</div>'+
        '<button class="rh104NavIcon rh104Cart rhIconBtn" id="rh24NavCart" type="button" aria-label="Warenkorb öffnen"><span class="ico">'+NAVICON.bag+'</span><span class="rh24NavCartCount">'+esc(cartCount())+'</span></button>'+
      '</div></div>'+
      '<div class="wrap rh24SearchDock"><form class="rh104Search rh104SearchDockForm" id="rh104Search" role="search"><label class="rh104SearchLabel" for="rh104SearchInput">Produktsuche</label><input id="rh104SearchInput" type="search" placeholder="Produkte, Kategorien oder Wissen suchen …" autocomplete="off"><button type="submit" aria-label="Suchen">⌕</button></form></div>';
    top.insertAdjacentElement('afterend',shell);

    shell.querySelectorAll('a[href]').forEach(a=>{const u=(a.getAttribute('href')||'').split('#')[0].toLowerCase();if(u===current)a.classList.add('active');});
    const menuBtn=shell.querySelector('#rh24MobileMenu');
    menuBtn?.addEventListener('click',()=>{const open=shell.classList.toggle('mobileOpen');menuBtn.setAttribute('aria-expanded',String(open));menuBtn.textContent=open?'×':'☰';});
    const setDrop=(drop,open)=>{
      shell.querySelectorAll('[data-rh24-drop]').forEach(x=>{
        const btn=x.querySelector('.rh24DropBtn');
        x.classList.toggle('open', x===drop && open);
        if(btn) btn.setAttribute('aria-expanded', String(x===drop && open));
      });
    };
    const desktopMedia=window.matchMedia('(min-width: 901px)');
    let dropCloseTimer=0;
    const cancelDropClose=()=>{ if(dropCloseTimer){ clearTimeout(dropCloseTimer); dropCloseTimer=0; } };
    const closeDropDelayed=drop=>{
      cancelDropClose();
      dropCloseTimer=window.setTimeout(()=>{
        dropCloseTimer=0;
        // Keep the submenu open while the pointer or keyboard focus is inside it.
        if(drop.matches(':hover') || drop.contains(document.activeElement)) return;
        setDrop(drop,false);
      },260);
    };
    shell.querySelectorAll('[data-rh24-drop]').forEach(drop=>{
      const b=drop.querySelector('.rh24DropBtn');
      const mega=drop.querySelector('.rh24Mega');
      if(!b) return;
      b.addEventListener('click',e=>{
        e.stopPropagation();
        cancelDropClose();
        setDrop(drop,!drop.classList.contains('open'));
      });
      b.addEventListener('keydown',e=>{
        if(e.key==='Escape'){cancelDropClose();setDrop(drop,false);b.blur();}
      });
      drop.addEventListener('mouseenter',()=>{
        cancelDropClose();
        if(desktopMedia.matches) setDrop(drop,true);
      });
      drop.addEventListener('mouseleave',()=>{
        if(desktopMedia.matches) closeDropDelayed(drop);
      });
      drop.addEventListener('focusin',()=>{
        cancelDropClose();
        if(desktopMedia.matches) setDrop(drop,true);
      });
      drop.addEventListener('focusout',()=>{
        if(desktopMedia.matches) closeDropDelayed(drop);
      });
      mega?.addEventListener('mouseenter',cancelDropClose);
      mega?.addEventListener('click',e=>e.stopPropagation());
    });
    document.addEventListener('click',e=>{if(!shell.contains(e.target))setDrop(null,false);});
    window.addEventListener('scroll',()=>shell.classList.toggle('isScrolled',window.scrollY>20),{passive:true});

    const form=shell.querySelector('#rh104Search'), input=shell.querySelector('#rh104SearchInput');
    form?.addEventListener('submit',e=>{
      e.preventDefault();
      const q=(input.value||'').trim().toLowerCase();
      if(!q){ if(typeof window.openUltraSearch==='function') window.openUltraSearch(); else location.href='shop.html'; return; }
      const found=pages.find(p=>(p.t+' '+p.d+' '+p.g).toLowerCase().includes(q));
      location.href=found?found.u:'shop.html';
    });
    shell.querySelector('#rh24NavCart')?.addEventListener('click',()=>{if(typeof window.openCart==='function')window.openCart();else location.href='checkout.html';});
    const syncCart=()=>{const target=shell.querySelector('.rh24NavCartCount');if(target)target.textContent=cartCount();};
    syncCart();
    const source=document.querySelector('[data-cart-count]');
    if(source&&window.MutationObserver)new MutationObserver(syncCart).observe(source,{childList:true,subtree:true,characterData:true});
  }

  /* -----------------------------------------------------------------
     V2026.4 · Fusszeile zentral aufbauen (wie die Kopfzeile).
     Bisher trug jede Seite ihre eigene, sehr schmale Fusszeile;
     artikel.php hatte gar keine. Ab jetzt entsteht auf jeder Seite
     dieselbe helle 5-Spalten-Fusszeile nach Gestaltungsvorlage.
     Alle Verweise führen auf vorhandene Seiten; der Kasten rechts
     nennt ausschliesslich belegte Eigenschaften dieses Shops
     (keine erfundenen Bewertungen, keine fremden Zahlungslogos).
     Der Behälter [data-rh1072-socials] bleibt erhalten – ihn füllt
     weiterhin footer-v1072.js.
  ----------------------------------------------------------------- */
  function buildFooter(){
    let foot=document.querySelector('footer');
    if(!foot){ foot=document.createElement('footer'); document.body.appendChild(foot); }
    if(foot.dataset.rh2026==='1') return;
    foot.dataset.rh2026='1';
    const c=window.__rh24Contact||{};
    const col=(t,links)=>'<nav class="rhFootCol" aria-label="'+esc(t)+'"><h4>'+esc(t)+'</h4>'+
      links.map(l=>'<a href="'+esc(l[1])+'">'+esc(l[0])+'</a>').join('')+'</nav>';
    foot.className='rhFooter';
    foot.innerHTML='<div class="wrap">'+
      '<div class="rhFootGrid rh1072FooterGrid">'+
        '<div class="rhFootBrand rh1072FooterBrand">'+
          '<div class="footerPlainBrand">RÄUCHERHAKEN<strong>24</strong></div>'+
          '<p>Premium-Räucherzubehör für höchste Ansprüche. Handgefertigt in Deutschland – für Fisch, Fleisch und Schinken.</p>'+
          '<div class="socials v17Socials" data-rh1072-socials></div>'+
          ((c.phoneHref||c.mailHref)?('<div class="rhFootContact">'+
            (c.phoneHref?'<a href="'+esc(c.phoneHref)+'">'+esc(c.phoneText||'Telefonische Beratung')+'</a>':'')+
            (c.mailHref?'<a href="'+esc(c.mailHref)+'">'+esc((c.mailHref||'').replace('mailto:',''))+'</a>':'')+
          '</div>'):'')+
        '</div>'+
        col('Shop',[
          ['Räucherhaken','shop.html#raeucherhaken'],
          ['Räucherholz','shop.html#raeuchermehl-shop'],
          ['Räucherlaugen','raeucherlaugen.html'],
          ['Naturgewürze','naturgewuerze.html'],
          ['Zubehör','thermometer.html']])+
        col('Wissen',[
          ['Ratgeber & Rezepte','rezepte-anleitungen.html'],
          ['Fisch räuchern','raeucherfisch.html'],
          ['Schinken selber machen','schinken.html'],
          ['Sonderanfertigung','sonderanfertigung-prototyp.html']])+
        col('Service',[
          ['Kontakt','kontakt.html'],
          ['Zahlung & Versand','zahlung-versand.html'],
          ['Widerruf & Rückgabe','widerruf.html'],
          ['Kundenlogin','kundenlogin.html'],
          ['Händler werden','haendlerlogin.html']])+
        col('Rechtliches',[
          ['Impressum','impressum.html'],
          ['AGB','agb.html'],
          ['Datenschutz','datenschutz.html'],
          ['Widerruf','widerruf.html']])+
        '<aside class="rhFootTrust" aria-label="Sicher einkaufen">'+
          '<b>Sicher einkaufen</b>'+
          '<span>Kauf auf Rechnung</span>'+
          '<span>14 Tage Widerrufsrecht</span>'+
          '<span>Versand mit DHL &amp; DPD</span>'+
        '</aside>'+
      '</div>'+
      '<div class="rhFootBar">'+
        '<small>© '+new Date().getFullYear()+' Räucherhaken24. Alle Rechte vorbehalten.</small>'+
        '<div class="rhFootPays" aria-label="Bestell- und Versandarten">'+
          '<span>Kauf auf Rechnung</span><span>DHL</span><span>DPD</span>'+
        '</div>'+
      '</div>'+
    '</div>';
  }

  function enhanceSidebar(){
    document.querySelectorAll('.sideBox').forEach(box=>{
      box.querySelectorAll('a[href]').forEach(a=>{const u=(a.getAttribute('href')||'').split('#')[0].toLowerCase();if(u===current)a.classList.add('active');});
      const children=Array.from(box.children),mains=children.filter(el=>el.classList.contains('sideMain'));
      mains.forEach((main,idx)=>{
        if(main.closest('.rh24SideGroup'))return;
        const group=document.createElement('div');group.className='rh24SideGroup';box.insertBefore(group,main);group.appendChild(main);
        const links=document.createElement('div');links.className='rh24SideLinks';group.appendChild(links);
        let node=group.nextSibling;
        while(node){const next=node.nextSibling;if(node.nodeType===1&&node.classList.contains('sideMain'))break;if(node.nodeType===1&&node.classList.contains('sideLink'))links.appendChild(node);else if(node.nodeType===3&&String(node.textContent).trim()==='')node.remove();else break;node=next;}
        main.setAttribute('role','button');main.setAttribute('tabindex','0');main.setAttribute('aria-expanded','false');
        if(links.querySelector('.active')||idx===0){group.classList.add('open');main.setAttribute('aria-expanded','true');}
        const toggle=()=>{const open=group.classList.toggle('open');main.setAttribute('aria-expanded',String(open));};
        main.addEventListener('click',toggle);main.addEventListener('keydown',e=>{if(e.key==='Enter'||e.key===' '){e.preventDefault();toggle();}});
      });
    });
    document.querySelectorAll('.mainLayout').forEach(layout=>{
      const side=layout.querySelector(':scope > .sidebar');if(!side||layout.querySelector('.rh24CategoryMobileToggle'))return;
      const btn=document.createElement('button');btn.type='button';btn.className='rh24CategoryMobileToggle';btn.textContent='Kategorien & Schnellnavigation';btn.setAttribute('aria-expanded','false');
      layout.insertBefore(btn,side);btn.addEventListener('click',()=>{const open=side.classList.toggle('mobileOpen');btn.setAttribute('aria-expanded',String(open));btn.textContent=open?'Kategorien schließen':'Kategorien & Schnellnavigation';});
    });
  }

  function reveal(){
    const els=Array.from(document.querySelectorAll('.card,.knowledgeCard,.featurePanel,.wizard,.moduleBand,.woodShopCard,.shopCategoryStrip a,.productPage,.trustModule,.rh104Category,.rh104Product,.rh104Article,.rh104TrustCard')).filter(el=>!el.classList.contains('rh24Reveal'));
    els.forEach(el=>el.classList.add('rh24Reveal'));
    if(!('IntersectionObserver' in window)||matchMedia('(prefers-reduced-motion: reduce)').matches){els.forEach(el=>el.classList.add('isVisible'));return;}
    const io=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('isVisible');io.unobserve(e.target);}}),{threshold:.06,rootMargin:'0px 0px -20px 0px'});
    els.forEach(el=>io.observe(el));
  }



  function enhanceProductMedia(root=document){
    const cards=root.querySelectorAll ? root.querySelectorAll('.rh104Product,.rh66ProductCard,.woodShopCard,.dbProductCard,.natureCard,.laugeCard,.thermoCard,.dynCard,.productPage') : [];
    cards.forEach(card=>{
      if(card.dataset.rh104ProductEnhanced==='1') return;
      const media=card.querySelector('.rh104ProductImg,.imgBox,.dbProductImage,.natureImage,.laugeVisual,.woodProductImage,.productImage,.thermoImage,a:first-child');
      const img=media?.querySelector?.('img');
      if(!media||!img) return;
      card.dataset.rh104ProductEnhanced='1';
      card.classList.add('rh104UnifiedProduct');
      media.classList.add('rh104UnifiedMedia');
      img.classList.add('rh104UnifiedProductImage');
      const existing=media.querySelector('.zoom')||card.querySelector(':scope > .zoom');
      if(existing){
        existing.classList.add('rh104ProductZoom');
        existing.setAttribute('aria-label','Produktbild vergrößern');
        existing.setAttribute('title','Produktbild vergrößern');
        existing.innerHTML='<span aria-hidden="true">⌕</span><small>Vergrößern</small>';
        return;
      }
      const z=document.createElement('button');
      z.type='button';
      z.className='rh104ProductZoom';
      z.setAttribute('aria-label','Produktbild vergrößern');
      z.setAttribute('title','Produktbild vergrößern');
      z.innerHTML='<span aria-hidden="true">⌕</span><small>Vergrößern</small>';
      z.addEventListener('click',e=>{
        e.preventDefault();e.stopPropagation();
        const src=img.currentSrc||img.getAttribute('src')||'';
        const name=img.getAttribute('alt')||card.querySelector('h3,h2')?.textContent||'Produktbild';
        if(typeof window.openZoom==='function') window.openZoom(src,name);
        else if(typeof window.zoom==='function') window.zoom(src,name);
      });
      card.appendChild(z);
    });
  }

  function watchProductMedia(){
    enhanceProductMedia(document);
    if(!window.MutationObserver) return;
    const mo=new MutationObserver(muts=>muts.forEach(m=>m.addedNodes.forEach(n=>{if(n.nodeType===1) enhanceProductMedia(n.matches?.('.rh104Product,.rh66ProductCard,.woodShopCard,.dbProductCard,.natureCard,.laugeCard,.thermoCard,.dynCard,.productPage')?{querySelectorAll:()=>[n]}:n);})));
    mo.observe(document.body,{childList:true,subtree:true});
  }

  function hideMarketplace(){
    document.querySelectorAll('.marketNavLink,[href="ankauf-verkauf.php"],a[href$="ankauf-verkauf.php"],footer a[href="ankauf-verkauf.php"]').forEach(el=>{
      const row=(el.closest('.rh104Category')||el.closest('.rh24Mega a')||el.closest('a')||el);
      row.style.display='none';
      row.setAttribute('aria-hidden','true');
    });
  }

  function cleanup(){
    document.querySelectorAll('img').forEach(img=>{if(!img.getAttribute('loading')&&!img.closest('.rh104Hero'))img.setAttribute('loading','lazy');if(!img.getAttribute('decoding'))img.setAttribute('decoding','async');});
    document.querySelectorAll('a[target="_blank"]').forEach(a=>{if(!a.rel)a.rel='noopener noreferrer';});
    document.documentElement.classList.remove('rh82Boot');document.documentElement.classList.add('rh104Ready');
  }

  function shopTitle(){
    if(current==='shop.html'){
      const h=document.querySelector('.hero h1');if(h)h.textContent='Alles fürs perfekte Räuchern';
      const p=document.querySelector('.hero p');if(p)p.textContent='Handgefertigte Räucherhaken, Räuchermehl, Räucherlaugen, Naturgewürze und Fachwissen – übersichtlich, hochwertig und verständlich.';
    }
  }

  function init(){renderHeader();buildNav();buildFooter();enhanceSidebar();hideMarketplace();watchProductMedia();shopTitle();reveal();cleanup();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
})();
