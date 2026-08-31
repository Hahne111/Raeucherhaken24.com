
(()=>{
 const page=location.pathname.split('/').pop()||'index.html';
 const productPages=/^(raeucherhaken-|raeuchermehl-|raeucherlauge-|fleischerhaken-).+\.html$/;
 if(!productPages.test(page))return;

 const productName=()=>document.querySelector('.productInfo h1,.productPage h1')?.textContent?.trim()||document.title.split('|')[0].trim();
 const key='rh24_reviews_'+page;
 const helpfulKey='rh24_helpful_'+page;

 const sampleSets={
  haken:[
   {name:'Martin K.',stars:5,title:'Sehr sauber verarbeitet',text:'Die Form wirkt stabil und die Spitze ist sauber ausgeführt. Für die Produktdarstellung im Shop ein gutes Beispiel, wie eine echte Kundenbewertung später aussehen kann.'},
   {name:'Petra S.',stars:5,title:'Auswahl gut verständlich',text:'Länge, Material und Ausführung lassen sich übersichtlich wählen. Diese Bewertung ist ein gekennzeichneter Platzhalter für die Vorschau.'},
   {name:'Thomas R.',stars:4,title:'Übersichtliche Produktseite',text:'Die technischen Unterschiede sind schnell zu erkennen. Für eine echte Veröffentlichung sollten später nur reale Kundenbewertungen verwendet werden.'}
  ],
  mehl:[
   {name:'Sven H.',stars:5,title:'Aroma gut erklärt',text:'Die Beschreibung macht schnell klar, wofür die Holzart gedacht ist. Beispielbewertung für die Shop-Vorschau.'},
   {name:'Ute B.',stars:4,title:'Körnung verständlich',text:'Gut finde ich die Erklärung zur passenden Körnung. Diese Bewertung dient nur als Demo-Platzhalter.'},
   {name:'Jan W.',stars:5,title:'Einsteigerfreundlich',text:'Die Produkthinweise sind verständlich und nicht unnötig kompliziert. Gekennzeichnete Beispielbewertung.'}
  ],
  lauge:[
   {name:'Heike M.',stars:5,title:'Klare Anwendungshinweise',text:'Die Mischung und der Einsatzzweck werden verständlich erklärt. Diese Bewertung ist als Demo gekennzeichnet.'},
   {name:'Ralf P.',stars:4,title:'Gute Produktübersicht',text:'Man sieht schnell, welche Variante wofür gedacht ist. Platzhalterbewertung für die Vorschau.'},
   {name:'Nina L.',stars:5,title:'Für Anfänger gut erklärt',text:'Die Hinweise zur Anwendung sind kurz und hilfreich. Demo-Bewertung, noch keine echte Kundenrezension.'}
  ]
 };
 const samples=page.includes('raeuchermehl')?sampleSets.mehl:page.includes('raeucherlauge')?sampleSets.lauge:sampleSets.haken;
 const today=new Date();
 function defaultReviews(){return samples.map((x,i)=>({...x,date:new Date(today.getTime()-(i+2)*86400000).toLocaleDateString('de-DE'),demo:true,verified:false,helpful:0,id:'demo'+i}))}
 function read(){try{return JSON.parse(localStorage.getItem(key))||[]}catch(e){return []}}
 function write(v){localStorage.setItem(key,JSON.stringify(v))}
 function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]))}
 function starString(n){return '★'.repeat(n)+'☆'.repeat(5-n)}
 function allReviews(){return [...defaultReviews(),...read()]}
 function average(rs){return rs.length?rs.reduce((a,b)=>a+b.stars,0)/rs.length:0}
 function dist(rs,n){return rs.filter(r=>r.stars===n).length}
 function mount(){
  const host=document.querySelector('.productPage')||document.querySelector('main');
  if(!host||document.getElementById('rhReviews'))return;
  const sec=document.createElement('section');sec.id='rhReviews';sec.className='rhReviews';
  host.appendChild(sec);render();

  const info=document.querySelector('.productInfo');
  if(info&&!info.querySelector('.rhMiniRating')){
    const mini=document.createElement('div');mini.className='rhMiniRating';
    const rs=allReviews(),avg=average(rs);
    mini.innerHTML=`<span class="rhStars">${starString(Math.round(avg))}</span><a href="#rhReviews">${avg.toFixed(1).replace('.',',')} / 5 · ${rs.length} Bewertungen</a>`;
    const h=info.querySelector('h1'); if(h)h.insertAdjacentElement('afterend',mini);
  }
 }
 function render(){
  const sec=document.getElementById('rhReviews');if(!sec)return;
  const rs=allReviews(),avg=average(rs), count=rs.length;
  const realCount=read().length;
  sec.innerHTML=`<div class="rhReviewsHead">
   <div><h2>Kundenbewertungen</h2><p>Erfahrungen und Rückmeldungen direkt am Produkt.</p></div>
   <div class="rhRatingSummary"><div class="rhRatingScore">${avg.toFixed(1).replace('.',',')}</div><div><div class="rhStars">${starString(Math.round(avg))}</div><span class="rhRatingCount">${count} Bewertungen · davon ${realCount} über dieses Formular</span></div></div>
  </div>
  <div class="rhReviewGrid">
   <aside class="rhDistribution">
    ${[5,4,3,2,1].map(n=>{const c=dist(rs,n),pct=count?Math.round(c/count*100):0;return `<div class="rhBarRow"><span>${n} ★</span><div class="rhBar"><i style="width:${pct}%"></i></div><b>${c}</b></div>`}).join('')}
    <div class="rhVerifiedInfo"><b>Transparenz:</b><br>Die bereits eingefügten Bewertungen sind deutlich als <b>Demo/Platzhalter</b> markiert und dürfen nicht als echte Kundenstimmen ausgegeben werden. Neu abgegebene Bewertungen werden lokal im Browser gespeichert.</div>
   </aside>
   <div class="rhReviewsList">${rs.slice().reverse().map(card).join('')}</div>
  </div>
  <div class="rhReviewFormWrap"><h3>Produkt bewerten</h3><p>Teilen Sie Ihre Erfahrung mit ${esc(productName())}.</p><div id="rhReviewMessage"></div>
   <form class="rhReviewForm" id="rhReviewForm">
    <label>Name / Kürzel<input name="name" maxlength="40" required placeholder="z. B. M. Hansen"></label>
    <label>Bewertung<select name="stars" required><option value="5">5 Sterne – ausgezeichnet</option><option value="4">4 Sterne – sehr gut</option><option value="3">3 Sterne – gut</option><option value="2">2 Sterne</option><option value="1">1 Stern</option></select></label>
    <label class="full">Überschrift<input name="title" maxlength="80" required placeholder="Kurz zusammenfassen"></label>
    <label class="full">Ihre Bewertung<textarea name="text" maxlength="1200" required placeholder="Was hat Ihnen gefallen? Wie haben Sie das Produkt eingesetzt?"></textarea></label>
    <div class="rhReviewNotice">Mit dem Absenden bestätigen Sie, dass die Bewertung auf Ihrer eigenen Erfahrung beruht. In dieser Testversion wird die Bewertung nur im Browser gespeichert. Für den Live-Shop sollte die Freigabe serverseitig mit Moderation und optionaler Bestellprüfung umgesetzt werden.</div>
    <button class="rhReviewSubmit" type="submit">Bewertung abgeben</button>
   </form>
  </div>`;
  document.getElementById('rhReviewForm')?.addEventListener('submit',submit);
 }
 function card(r){
  return `<article class="rhReviewCard"><div class="rhReviewTop"><div><span class="rhStars">${starString(r.stars)}</span><span class="rhReviewer">${esc(r.name)}</span>${r.demo?'<span class="rhDemoBadge">DEMO / PLATZHALTER</span>':''}</div><span class="rhReviewDate">${esc(r.date)}</span></div>
  <div class="rhReviewTitle">${esc(r.title)}</div><p class="rhReviewText">${esc(r.text)}</p>
  ${r.verified?'<span class="rhPurchaseBadge">✓ Verifizierter Kauf</span>':''}
  <div class="rhReviewActions"><button class="rhHelpful" data-help="${esc(r.id)}">Hilfreich (${r.helpful||0})</button><span></span></div></article>`
 }
 function submit(e){
  e.preventDefault();const fd=new FormData(e.currentTarget);
  const r={id:'user_'+Date.now(),name:fd.get('name'),stars:+fd.get('stars'),title:fd.get('title'),text:fd.get('text'),date:new Date().toLocaleDateString('de-DE'),demo:false,verified:false,helpful:0};
  const current=read();current.push(r);write(current);render();
  const msg=document.getElementById('rhReviewMessage');if(msg){msg.className='rhReviewSuccess';msg.textContent='Danke. Ihre Bewertung wurde in dieser Testversion lokal gespeichert.'}
 }
 document.addEventListener('click',e=>{
  const b=e.target.closest('[data-help]');if(!b)return;
  const id=b.dataset.help;
  if(id.startsWith('user_')){
   const rs=read(),r=rs.find(x=>x.id===id);if(r){r.helpful=(r.helpful||0)+1;write(rs);render()}
  }
 });
 document.addEventListener('DOMContentLoaded',mount);
})();
