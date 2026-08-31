(()=>{
  'use strict';
  const P={
    menu:'<path d="M4 7h16M4 12h16M4 17h16"/>',
    close:'<path d="M6 6l12 12M18 6L6 18"/>',
    user:'<circle cx="12" cy="8" r="3.2"/><path d="M5.5 20c.8-4.2 3-6.2 6.5-6.2s5.7 2 6.5 6.2"/>',
    dealer:'<path d="M4 9h16l-1-4H5L4 9z"/><path d="M5 9v10h14V9M9 19v-5h6v5"/>',
    cart:'<path d="M3.5 5h2l1.6 9h9.8l2-6H7"/><circle cx="9" cy="18" r="1.5"/><circle cx="17" cy="18" r="1.5"/>',
    search:'<circle cx="10.5" cy="10.5" r="5.5"/><path d="M15 15l5 5"/>',
    zoom:'<circle cx="10.5" cy="10.5" r="5.5"/><path d="M15 15l5 5M10.5 8v5M8 10.5h5"/>',
    shield:'<path d="M12 3l7 3v5c0 4.6-2.9 7.9-7 10-4.1-2.1-7-5.4-7-10V6l7-3z"/><path d="M8.6 12.1l2.2 2.2 4.7-4.7"/>',
    steel:'<path d="M12 3l6.5 3.7v7.6L12 21l-6.5-6.7V6.7L12 3z"/><path d="M8 9.2h8M8 12h8M8 14.8h5.5"/>',
    handwork:'<path d="M5 18l8.8-8.8M12.5 5.5l2-2 4 4-2 2M4 19l3.5-.8-2.7-2.7L4 19z"/><path d="M12 12l6 6"/>',
    chat:'<path d="M7.5 5.5h9A2.5 2.5 0 0 1 19 8v5a2.5 2.5 0 0 1-2.5 2.5H12l-4 3v-3h-.5A2.5 2.5 0 0 1 5 13V8a2.5 2.5 0 0 1 2.5-2.5z"/><path d="M9 9.5h6M9 12.5h4"/>',
    truck:'<path d="M3 7h10v8H3z"/><path d="M13 10h3.5l2.5 2.5V15h-6"/><circle cx="7" cy="17" r="1.8"/><circle cx="17" cy="17" r="1.8"/>',
    check:'<path d="M5 12.5l4.2 4.2L19 7"/>',
    quality:'<path d="M12 3l2.3 4.6 5.1.7-3.7 3.6.9 5.1-4.6-2.4L7.4 17l.9-5.1-3.7-3.6 5.1-.7L12 3z"/>',
    leaf:'<path d="M19 5C12 5 6 8.5 5 15.5c4.5.8 8.3-.4 10.8-3.3C18 9.7 19 7 19 5z"/><path d="M6 18c2.5-4.2 5.6-6.8 9.6-8.5"/>',
    mic:'<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M6.5 10.5a5.5 5.5 0 0 0 11 0M12 16v4M9 20h6"/>',
    location:'<path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11z"/><circle cx="12" cy="10" r="2"/>',
    home:'<path d="M3 11l9-7 9 7"/><path d="M5.5 10v10h13V10M10 20v-6h4v6"/>',
    grid:'<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
    heart:'<path d="M12 20s-7-4.2-7-10a4.2 4.2 0 0 1 7-3.1A4.2 4.2 0 0 1 19 10c0 5.8-7 10-7 10z"/>',
    compare:'<path d="M7 7h11M15 4l3 3-3 3M17 17H6M9 14l-3 3 3 3"/>',
    clock:'<circle cx="12" cy="12" r="8"/><path d="M12 7v5l3 2"/>',
    share:'<path d="M13 5h6v6M19 5l-8 8"/><path d="M17 13v5H5V7h5"/>',
    sparkle:'<path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z"/><path d="M18.5 14.5l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2z"/>',
    fish:'<path d="M5 12c3.2-4.2 7.2-5.5 11-3.4l3-2v10.8l-3-2C12.2 17.5 8.2 16.2 5 12z"/><circle cx="14.7" cy="10.7" r=".7"/><path d="M5 12l-2.5-2v4L5 12z"/>',
    meat:'<path d="M7 17c-2.7-2.1-3.1-6-.8-8.6 2.5-2.8 6.8-2.8 9.5-.3 2.4 2.2 2.5 6 .4 8.4-2.2 2.5-6.4 2.8-9.1.5z"/><circle cx="10" cy="11" r="2"/>',
    flavor:'<circle cx="12" cy="12" r="7"/><path d="M8 12h8M12 8v8"/>',
    flame:'<path d="M12 21c-4 0-7-2.7-7-6.6 0-3.2 2-5.1 4-7.4.2 2.2 1.3 3.5 2.5 4.3.2-3.3 1.8-6 4.2-8.3.4 3.7 3.3 5.4 3.3 9.3 0 5.1-3.1 8.7-7 8.7z"/><path d="M12 18c-1.7 0-3-1.1-3-2.8 0-1.4.8-2.3 1.8-3.4.1 1 .6 1.6 1.2 2 .1-1.5.8-2.7 1.8-3.8.2 1.7 1.5 2.4 1.5 4.2 0 2.3-1.4 3.8-3.3 3.8z"/>',
    info:'<circle cx="12" cy="12" r="8"/><path d="M12 10v6M12 7.2h.01"/>'
  };
  const svg=n=>`<svg class="rh1076Svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">${P[n]||P.info}</svg>`;
  const stripLead=s=>String(s||'').replace(/^\s*(?:🛒|🚚|☎|✓|◇|◒|✦|✹|⬡|⌕|⌖|🎤|💬|🔥|✿|♙|♢|☰|×|⌂|▦|♡|⇄|◴|↗)\s*/u,'');
  const firstText=n=>Array.from(n.childNodes).find(x=>x.nodeType===3&&x.nodeValue.trim());
  const setWhole=(el,name)=>{if(!el)return; if(el.querySelector(':scope > .rh1076Svg'))return; el.innerHTML=svg(name);};
  const prependIcon=(el,name,cls='rh1076StatusInline')=>{if(!el||el.querySelector(':scope > .'+cls))return; const t=firstText(el);if(t)t.nodeValue=stripLead(t.nodeValue);el.insertAdjacentHTML('afterbegin',`<span class="${cls}">${svg(name)}</span>`);};
  const labelIcon=(el,name)=>{if(!el)return; const t=firstText(el);if(t)t.nodeValue=stripLead(t.nodeValue);if(!el.querySelector(':scope > .rh1076HeroIcon'))el.insertAdjacentHTML('afterbegin',`<span class="rh1076HeroIcon rh1076IconBox">${svg(name)}</span>`);};
  const textKey=s=>String(s||'').toLowerCase();

  function normalizeHeader(){
    setWhole(document.querySelector('.rh104NavIcon.customer .ico'),'user');
    setWhole(document.querySelector('.rh104NavIcon.dealer .ico'),'dealer');
    setWhole(document.querySelector('.rh104NavIcon.rh104Cart .ico'),'cart');
    setWhole(document.querySelector('.rh104Search button'),'search');
    document.querySelectorAll('.rh24MobileMenuBtn').forEach(b=>setWhole(b,b.getAttribute('aria-expanded')==='true'?'close':'menu'));
    document.querySelectorAll('.top .usp').forEach(u=>{const k=textKey(u.textContent);let name=k.includes('versand')?'truck':k.includes('handarbeit')?'handwork':k.includes('regional')?'location':k.includes('marktplatz')?'shield':'steel';const t=firstText(u);if(t)t.nodeValue=stripLead(t.nodeValue);if(!u.querySelector(':scope > .rh1076UspIcon'))u.insertAdjacentHTML('afterbegin',`<span class="rh1076UspIcon rh1076IconBox">${svg(name)}</span>`);});
    document.querySelectorAll('.rh104TrustInner span').forEach(s=>{const i=s.querySelector(':scope > i');if(!i)return;const k=textKey(s.textContent);setWhole(i,k.includes('versand')?'truck':k.includes('handgefertigt')?'handwork':'chat');});
    document.querySelectorAll('.consultationIcon').forEach(x=>setWhole(x,'chat'));
  }
  function normalizeHero(){
    document.querySelectorAll('.heroFeatures b').forEach((b,i)=>{const k=textKey(b.textContent);labelIcon(b,k.includes('verständ')?'shield':k.includes('hochwert')?'quality':k.includes('beratung')?'chat':i===0?'shield':i===1?'quality':'chat');});
    document.querySelectorAll('.rh104TrustCard .rh104TrustIcon').forEach((x,i)=>{const k=textKey(x.parentElement?.textContent);setWhole(x,k.includes('handwerk')?'handwork':k.includes('produktwahl')?'steel':k.includes('beratung')?'chat':k.includes('bestell')?'shield':['handwork','steel','chat','shield'][i%4]);});
    document.querySelectorAll('.natureCategory').forEach(x=>{if(!x.querySelector('.rh1076Svg')){x.textContent=stripLead(x.textContent);x.insertAdjacentHTML('afterbegin',svg('leaf'));}});
  }
  function normalizeControls(){
    document.querySelectorAll('.rh104CartButton').forEach(b=>setWhole(b,'cart'));
    document.querySelectorAll('button').forEach(b=>{const k=textKey(b.textContent);if(k.includes('in den warenkorb')&&!b.classList.contains('rh104CartButton'))prependIcon(b,'cart','rh1076ButtonIcon');});
    document.querySelectorAll('.cartBtn').forEach(b=>prependIcon(b,'cart','rh1076CartFloating'));
    document.querySelectorAll('.zoom').forEach(b=>setWhole(b,'zoom'));
    document.querySelectorAll('.rh104HeroZoom span').forEach(s=>setWhole(s,'zoom'));
    document.querySelectorAll('.aiForm button:not(.send),.smokyMic,#smokyProMic').forEach(b=>setWhole(b,'mic'));
    document.querySelectorAll('.drawerClose,.zoomModal .close,.aiHead>button,.smokyProClose').forEach(b=>setWhole(b,'close'));
  }
  function normalizeMarketplace(){
    document.querySelectorAll('.policyCard>span:first-child').forEach(x=>setWhole(x,'shield'));
    document.querySelectorAll('.marketSearch>span,.shopSearchIcon').forEach(x=>setWhole(x,'search'));
    document.querySelectorAll('.marketLocationBtn').forEach(b=>prependIcon(b,'location','rh1076StatusInline'));
  }
  function normalizeModules(){
    const dockMap=['home','grid','search','heart','cart'];
    document.querySelectorAll('.mobileDock a>span,.mobileDock button>span').forEach((s,i)=>setWhole(s,dockMap[i%dockMap.length]));
    document.querySelectorAll('.utilityBtn').forEach(b=>{const k=textKey(b.textContent);const n=k.includes('suche')?'search':k.includes('merkliste')?'heart':k.includes('vergleich')?'compare':k.includes('bestellung')?'clock':'share';prependIcon(b,n,'rh1076UtilityIcon');});
  }
  function normalizeSmoky(){
    document.querySelectorAll('.smokyProTabs [data-smoky-tab]').forEach(b=>{const s=b.querySelector('span');if(!s)return;setWhole(s,b.dataset.smokyTab==='cart'?'cart':b.dataset.smokyTab==='chat'?'chat':'sparkle');});
    document.querySelectorAll('.smokyChoice').forEach(b=>{const s=b.querySelector(':scope > span');if(!s)return;const k=b.dataset.choice||'';let n=['forelle','aal','makrele','lachs'].includes(k)?'fish':k==='schinken'?'meat':k==='spicy'?'flame':k==='fruity'||k==='mild'?'leaf':k==='advanced'?'check':k==='beginner'?'sparkle':k==='strong'?'steel':'flavor';setWhole(s,n);});
    document.querySelectorAll('.smokyEmpty>span').forEach(s=>setWhole(s,'cart'));
    document.querySelectorAll('.smokyResultOk').forEach(x=>prependIcon(x,'check','rh1076ResultIcon'));
    document.querySelectorAll('.smokyCartExisting i').forEach(x=>setWhole(x,'check'));
  }
  function normalizeStatus(){
    document.querySelectorAll('.rhHookMeta span,.ship,.v19ShipBanner,.recShip,.rhPurchaseBadge').forEach(x=>{const k=textKey(x.textContent);if(k.includes('versand')||k.includes('liefer'))prependIcon(x,k.includes('kostenlos')?'check':'truck');else if(k.includes('verifiziert')||k.includes('passend'))prependIcon(x,'check');});
  }
  function apply(){normalizeHeader();normalizeHero();normalizeControls();normalizeMarketplace();normalizeModules();normalizeSmoky();normalizeStatus();}
  let queued=false;const queue=()=>{if(queued)return;queued=true;requestAnimationFrame(()=>{queued=false;apply();});};
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',apply,{once:true});else apply();
  const mo=new MutationObserver(queue);mo.observe(document.documentElement,{subtree:true,childList:true,attributes:true,attributeFilter:['aria-expanded','class']});
})();
