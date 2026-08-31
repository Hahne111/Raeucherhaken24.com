
const SMOKY_FALLBACK = {
  forelle:'Für klassische Forellen ist der Räucherhaken Standard ein guter Ausgangspunkt. Bei größeren Fischen kann ein Doppeldorn zusätzlichen Halt geben.',
  material:'V2A ist die solide rostfreie Allround-Wahl. V4A ist bei Salz, Feuchtigkeit und häufiger Nutzung besonders widerstandsfähig. VA ist die einfachere Basisausführung.',
  holz:'Buche ist der vielseitige Klassiker, Erle eher mild für Fisch, Eiche kräftiger für Fleisch und Schinken und Kirsche mild-fruchtig.'
};
function smokyFallback(q){
 q=(q||'').toLowerCase();
 if(q.includes('forell'))return SMOKY_FALLBACK.forelle;
 if(q.includes('va')||q.includes('v2a')||q.includes('v4a')||q.includes('material'))return SMOKY_FALLBACK.material;
 if(q.includes('räuchermehl')||q.includes('holz')||q.includes('lachs'))return SMOKY_FALLBACK.holz;
 return 'Beschreibe bitte kurz, was du räuchern möchtest, ungefähr Größe oder Gewicht und ob du ein mildes, klassisches, kräftiges oder fruchtiges Aroma möchtest.';
}
async function smokyLiveQuestion(question, context=''){
 const r=await fetch('smoky-api.php',{
   method:'POST',
   headers:{'Content-Type':'application/json'},
   body:JSON.stringify({question,context,page:location.pathname.split('/').pop()||'index.html'})
 });
 if(!r.ok)throw new Error('Smoky API nicht erreichbar');
 const data=await r.json();
 if(!data.answer)throw new Error(data.error||'Keine Antwort');
 return data.answer;
}
async function smokyAsk(preset){
 const input=document.getElementById('smokyInput');
 const answer=document.getElementById('smokyAnswer');
 const q=(preset||(input?input.value:'')).trim();
 if(!q||!answer)return;
 if(input&&preset)input.value=preset;
 answer.innerHTML='<span class="smokyThinking">Smoky recherchiert und formuliert deine Antwort …</span>';
 try{
   answer.textContent=await smokyLiveQuestion(q,'Startseiten-Beratung zu Räucherbedarf, Fisch, Fleisch, Räucherhaken, Räuchermehl, Räucherlaugen und Pökeln.');
 }catch(e){
   answer.textContent=smokyFallback(q);
 }
}
function smokySpeak(){
 const answer=document.getElementById('smokyAnswer');
 if(!answer||!window.speechSynthesis)return;
 speechSynthesis.cancel();
 const u=new SpeechSynthesisUtterance(answer.innerText);
 u.lang='de-DE';u.rate=.96;
 const voices=speechSynthesis.getVoices();
 const female=voices.find(v=>/anna|katja|helena|female|siri|google deutsch/i.test(v.name));
 if(female)u.voice=female;
 speechSynthesis.speak(u);
}
document.addEventListener('DOMContentLoaded',()=>{
 const i=document.getElementById('smokyInput');
 if(i)i.addEventListener('keydown',e=>{if(e.key==='Enter')smokyAsk()});
});
