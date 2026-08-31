
(()=>{
 const page=location.pathname.split('/').pop()||'index.html';
 let KB={};
 async function loadKB(){try{KB=await fetch('ai-knowledge.json',{cache:'no-store'}).then(r=>r.json())}catch(e){}}
 function localAnswer(k,q){
   q=(q||'').toLowerCase();
   if(!k)return 'Beschreibe bitte dein Vorhaben etwas genauer.';
   if(q.includes('material')||q.includes('variante')||q.includes('welche'))return k[3];
   if(q.includes('warum'))return k[2];
   return `${k[0]} ist vor allem für ${k[1]} gedacht. ${k[2]} ${k[3]}`;
 }
 async function init(){
   await loadKB();const k=KB[page];if(!k)return;
   const host=document.querySelector('.productInfo,.content,.productPage');if(!host)return;
   const box=document.createElement('section');box.className='productAiAdvisor';
   box.innerHTML=`<div class="productAiHead">
     <img class="productSmokyAvatar" src="assets/smoky-original-scharf.jpg" alt="Smoky">
     <div><b>Frag Smoky</b><span>KI-Beratung speziell zu ${k[0]}</span></div>
   </div>
   <div class="productAiBody">
     <div class="productAiAnswer" id="paiAnswer">Frag mich kurz zu Einsatz, Auswahl oder Material. Ich gebe dir eine klare Empfehlung.</div>
     <div class="productAiQuick">
       <button data-q="Passt dieses Produkt zu meinem Vorhaben?">Passt das zu meinem Vorhaben?</button>
       <button data-q="Warum ist dieses Produkt sinnvoll?">Warum dieses Produkt?</button>
       <button data-q="Welche Material- und Längenvariante soll ich wählen?">Welche Variante?</button>
       <button data-q="Erkläre mir die richtige Anwendung Schritt für Schritt.">Anwendung erklären</button>
     </div>
     <div class="productAiAsk"><input id="paiInput" placeholder="Smoky deine Frage stellen …"><button class="productAiSend">Fragen</button><button class="productAiSpeak">🔊 Vorlesen</button></div>
     <div class="productAiFoot">Kurz erklärt, ohne fremde Links. Produktetikett und Herstellerangaben haben Vorrang.</div>
   </div>`;
   host.appendChild(box);
   const answer=box.querySelector('#paiAnswer'),input=box.querySelector('#paiInput');
   async function respond(q){
     if(!q)return;
     answer.innerHTML='<span class="smokyThinking">Smoky prüft das kurz …</span>';
     try{
       const r=await fetch('smoky-api.php',{method:'POST',headers:{'Content-Type':'application/json'},
         body:JSON.stringify({question:q,context:`Produkt: ${k[0]}. Einsatz: ${k[1]}. Interne Produktinfo: ${k[2]} ${k[3]}`,page})});
       const data=await r.json();if(!r.ok||!data.answer)throw new Error();
       answer.textContent=data.answer;
     }catch(e){answer.textContent=localAnswer(k,q)}
   }
   box.querySelectorAll('[data-q]').forEach(b=>b.onclick=()=>respond(b.dataset.q));
   box.querySelector('.productAiSend').onclick=()=>respond(input.value.trim());
   input.onkeydown=e=>{if(e.key==='Enter')respond(input.value.trim())};
   box.querySelector('.productAiSpeak').onclick=()=>{
     speechSynthesis.cancel();const u=new SpeechSynthesisUtterance(answer.innerText);u.lang='de-DE';u.rate=.96;
     const voices=speechSynthesis.getVoices();const female=voices.find(v=>/anna|katja|helena|female|siri|google deutsch/i.test(v.name));
     if(female)u.voice=female;speechSynthesis.speak(u);
   };
 }
 document.addEventListener('DOMContentLoaded',init);
})();
