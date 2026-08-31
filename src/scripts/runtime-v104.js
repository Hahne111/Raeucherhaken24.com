/* Räucherhaken24 V104 · Duplicate Render Guard
   Keeps exactly one instance of page-level UI shells if legacy runtimes are executed more than once.
   This does not replace content; it only removes duplicate singleton roots. */
(()=>{
  'use strict';
  if(window.__RH24_V104_DUPLICATE_GUARD__) return;
  window.__RH24_V104_DUPLICATE_GUARD__=true;

  const singletonSelectors=[
    '.top',
    '#rh24DynamicNav',
    '.smokyHero',
    '.rh65Hero',
    '.rh65CategoryDock',
    '.rh104Hero',
    '.mainLayout',
    'footer',
    '.rh701HdBand',
    '#overlay',
    '#cartDrawer',
    '#aiPanel',
    '#zoomModal',
    '#toast',
    '#uSearch',
    '#uWish',
    '#uCompare',
    '#cookiePanel',
    '.cookieFab',
    '.backTop',
    '.mobileDock'
  ];

  function dedupeSelector(selector){
    const nodes=Array.from(document.querySelectorAll(selector));
    if(nodes.length<=1) return 0;
    let removed=0;
    // Always preserve the first node in document order. Dynamic runtimes should enhance/replace
    // existing nodes, not append a second page shell.
    nodes.slice(1).forEach(node=>{
      if(node && node.parentNode){ node.parentNode.removeChild(node); removed++; }
    });
    return removed;
  }

  function dedupeIds(){
    const seen=new Set();
    let removed=0;
    document.querySelectorAll('[id]').forEach(node=>{
      const id=node.id;
      if(!id) return;
      if(seen.has(id)){
        // Only auto-remove duplicate IDs belonging to RH24 page chrome/modules.
        if(/^(rh24|rh65|uSearch$|uWish$|uCompare$|cookiePanel$|cartDrawer$|aiPanel$|zoomModal$|overlay$|toast$)/.test(id)){
          node.remove();
          removed++;
        }
      }else{
        seen.add(id);
      }
    });
    return removed;
  }

  function runGuard(){
    let removed=0;
    singletonSelectors.forEach(sel=>{ removed+=dedupeSelector(sel); });
    removed+=dedupeIds();
    document.documentElement.dataset.rh24DuplicateGuard='104';
    if(removed>0){
      console.warn('[RH24 V104] Doppelte Seitenelemente entfernt:',removed);
    }
  }

  let queued=false;
  function queueGuard(){
    if(queued) return;
    queued=true;
    queueMicrotask(()=>{ queued=false; runGuard(); });
  }

  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',runGuard,{once:true});
  }else{
    runGuard();
  }

  // Catch late legacy injections, then disconnect after startup to avoid permanent overhead.
  const observer=new MutationObserver(queueGuard);
  observer.observe(document.documentElement,{childList:true,subtree:true});
  setTimeout(()=>{ runGuard(); observer.disconnect(); },3500);
})();
