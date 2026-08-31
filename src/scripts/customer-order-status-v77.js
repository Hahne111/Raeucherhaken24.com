(()=>{
  const form=document.getElementById('orderStatusForm');
  if(!form)return;
  const result=document.getElementById('orderStatusResult');
  const orderInput=document.getElementById('statusOrderNo');
  const emailInput=document.getElementById('statusEmail');
  const submit=document.getElementById('statusSubmit');
  const esc=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const fmtDate=v=>{if(!v)return '–';const d=new Date(v);return Number.isNaN(d.getTime())?'–':new Intl.DateTimeFormat('de-DE',{dateStyle:'medium',timeStyle:'short'}).format(d)};
  const money=v=>new Intl.NumberFormat('de-DE',{style:'currency',currency:'EUR'}).format(Number(v)||0);
  const progressColor=p=>{const v=Math.max(0,Math.min(100,Number(p)||0));const hue=Math.round(4+(v/100)*126);return `hsl(${hue} 68% ${v>70?35:43}%)`};
  const iconFor=p=>p>=100?'✓':p>=80?'◆':p>0?'⚙':'●';
  function renderError(message){result.hidden=false;result.innerHTML=`<div class="orderStatusError" role="alert">${esc(message)}</div>`}
  function renderOrder(o){
    const p=Math.max(0,Math.min(100,Number(o.progress)||0));
    const c=progressColor(p);
    const items=(o.items||[]).map(it=>`<div class="customerItem"><div><b>${esc(it.name)}</b><small>${esc(it.article_no?`Art.-Nr. ${it.article_no}`:'')}${(it.meta||[]).length?`${it.article_no?' · ':''}${esc(it.meta.join(' · '))}`:''}</small></div><strong>${Number(it.qty)||1} ×</strong></div>`).join('');
    const timeline=(o.timeline||[]).map(x=>`<div class="customerTimelineStep ${x.done?'done':''}">${esc(x.label)}</div>`).join('');
    const due=o.production_due_at?`Geplant bis ${fmtDate(o.production_due_at)}`:(o.production_finished_at?'Fertigung abgeschlossen':'Termin wird eingeplant');
    result.hidden=false;
    const docs=o.documents||{},docHtml=(docs.invoice||docs.delivery_note)?`<section class="customerDocuments"><div><small>RECHNUNG & LIEFERSCHEIN</small><h4>Ihre PDF-Dokumente</h4><p>Dokumente können jederzeit erneut heruntergeladen und anschließend gedruckt werden.</p></div><div class="customerDocumentActions">${docs.invoice?`<button type="button" data-order-document="invoice">Rechnung PDF<br><small>${esc(docs.invoice.document_no||'')}</small></button>`:''}${docs.delivery_note?`<button type="button" data-order-document="delivery_note">Lieferschein PDF<br><small>${esc(docs.delivery_note.document_no||'')}</small></button>`:''}</div></section>`:'';
    result.innerHTML=`<article class="orderStatusCard"><header class="orderStatusHead"><div><small>BESTELLFORTSCHRITT</small><h3>${esc(o.order_no)}</h3></div><div class="orderStatusPercent">${p}%</div></header><div class="orderStatusBody"><div class="customerProgressTop"><b>${esc(o.phase)}</b><span>${esc(due)}</span></div><div class="customerProgressTrack" role="progressbar" aria-label="Bestellfortschritt" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${p}"><div class="customerProgressFill" style="width:${p}%;--status-color:${c}"></div></div><div class="customerStatusMessage"><i>${iconFor(p)}</i><div><b>${esc(o.phase)}</b><p>${esc(o.message)}</p></div></div><div class="customerOrderFacts"><div><small>Bestellt am</small><b>${fmtDate(o.created_at)}</b></div><div><small>Letzte Aktualisierung</small><b>${fmtDate(o.updated_at)}</b></div><div><small>Auftragswert</small><b>${money(o.gross)}</b></div></div><div class="customerTimeline" aria-label="Statusschritte">${timeline}</div>${docHtml}<section class="customerItems"><h4>Ihre Bestellung</h4>${items||'<p class="small muted">Keine Artikeldetails verfügbar.</p>'}</section>${o.tracking?`<div class="customerTracking"><b>Versand: ${esc(o.carrier||'Paketdienst')}</b>Sendungsnummer: ${esc(o.tracking)}</div>`:''}</div></article>`;
    result.querySelectorAll('[data-order-document]').forEach(btn=>btn.addEventListener('click',()=>downloadDocument(o.order_no,btn.dataset.orderDocument,btn)));
  }
  async function downloadDocument(order_no,type,btn){
    const email=emailInput.value.trim().toLowerCase();if(!email)return renderError('Bitte Bestell-E-Mail eingeben.');const old=btn.innerHTML;btn.disabled=true;btn.textContent='PDF wird geladen …';
    try{const r=await fetch('order-document.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/pdf'},credentials:'same-origin',body:JSON.stringify({order_no,email,type})});if(!r.ok){const d=await r.json().catch(()=>({error:'Dokument konnte nicht geladen werden.'}));throw new Error(d.error||'Dokument konnte nicht geladen werden.')}const blob=await r.blob(),url=URL.createObjectURL(blob),a=document.createElement('a');a.href=url;a.download=(type==='invoice'?'Rechnung-':'Lieferschein-')+order_no+'.pdf';document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),3000)}catch(err){renderError(err?.message||'Dokument konnte nicht geladen werden.')}finally{btn.disabled=false;btn.innerHTML=old}}
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    const order_no=orderInput.value.trim().toUpperCase();
    const email=emailInput.value.trim().toLowerCase();
    if(!order_no||!email)return renderError('Bitte Bestellnummer und Bestell-E-Mail eingeben.');
    submit.disabled=true;submit.textContent='Status wird geladen …';
    try{
      const r=await fetch('order-status.php',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},credentials:'same-origin',body:JSON.stringify({order_no,email})});
      const d=await r.json().catch(()=>({ok:false,error:'Die Serverantwort konnte nicht gelesen werden.'}));
      if(!r.ok||!d.ok)throw new Error(d.error||'Bestellstatus konnte nicht geladen werden.');
      renderOrder(d.order);
    }catch(err){renderError(err?.message||'Bestellstatus konnte nicht geladen werden.')}finally{submit.disabled=false;submit.textContent='Fortschritt anzeigen'}
  });
  const params=new URLSearchParams(location.search);const preset=params.get('order');if(preset)orderInput.value=preset.toUpperCase();
  if(location.hash==='#bestellfortschritt'){
    const tab=document.querySelector('[data-status-tab]');if(tab&&typeof window.showAccountTab==='function')window.showAccountTab('orderstatus',tab);
  }
})();
