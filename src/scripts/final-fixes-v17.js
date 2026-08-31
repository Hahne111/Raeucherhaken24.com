
(function(){
 const CART_SVG=`<svg class="v17CartSvg" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2.1 9.1a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 1.9-1.4L20 8H7"/><circle cx="9.5" cy="19" r="1.4"/><circle cx="17.5" cy="19" r="1.4"/></svg>`;
 function upgradeCartButtons(root=document){
   root.querySelectorAll('button,a').forEach(el=>{
     const t=(el.textContent||'').trim().toLowerCase();
     if(t.includes('in den warenkorb') || t.includes('konfiguration in den warenkorb')){
       el.classList.add('v17CartButton');
       if(!el.querySelector('.v17CartSvg')){
         el.innerHTML=CART_SVG+`<span>In den Warenkorb legen</span>`;
       }
     }
   });
 }
 function cleanRevoke(){
   document.querySelectorAll('.revokeFab').forEach(x=>x.remove());
 }
 function footerBrand(){
   document.querySelectorAll('footer .classicLogo, footer .premiumLogo').forEach(x=>{
     const d=document.createElement('div');d.className='footerPlainBrand';d.textContent='Räucherhaken24';x.replaceWith(d);
   });
 }
 function socialButtons(){
   document.querySelectorAll('.socialBar').forEach(bar=>{
     bar.innerHTML=`<span>Folge uns auf & teile Räucherhaken24</span>
     <div class="socials v17Socials">
       <button onclick="rhFacebook()">f <b>Facebook</b></button>
       <button onclick="rhNativeShare('TikTok','https://www.tiktok.com/')">♪ <b>TikTok</b></button>
       <button onclick="rhNativeShare('Instagram','https://www.instagram.com/')">◎ <b>Instagram</b></button>
       <button onclick="rhX()">𝕏 <b>X</b></button>
       <button onclick="rhWhatsApp()">◉ <b>WhatsApp</b></button>
     </div>`;
   });
 }
 window.rhFacebook=()=>window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href),'_blank','noopener,noreferrer');
 window.rhX=()=>window.open('https://twitter.com/intent/tweet?url='+encodeURIComponent(location.href)+'&text='+encodeURIComponent('Räucherhaken24'),'_blank','noopener,noreferrer');
 window.rhWhatsApp=()=>window.open('https://wa.me/4917620204188?text='+encodeURIComponent('Hallo, ich habe eine Frage zu Räucherhaken24: '+location.href),'_blank','noopener,noreferrer');
 window.rhNativeShare=async(name,fallback)=>{
   if(navigator.share){try{await navigator.share({title:'Räucherhaken24',url:location.href});return}catch(e){}}
   try{await navigator.clipboard.writeText(location.href)}catch(e){}
   window.open(fallback,'_blank','noopener,noreferrer');
 };
 // Override generic floating AI with the same live backend.
 window.ask=async function(){
   const i=document.getElementById('q'), q=(i?.value||'').trim();if(!q)return;
   if(typeof add==='function')add(q,'user');if(i)i.value='';
   try{
     const ans=await smokyLiveQuestion(q,'Allgemeine Räucherhaken24-Beratung auf '+(location.pathname.split('/').pop()||'Startseite'));
     if(typeof add==='function')add(ans,'bot');
   }catch(e){
     if(typeof add==='function')add(smokyFallback(q),'bot');
   }
 };
 document.addEventListener('DOMContentLoaded',()=>{
   cleanRevoke();footerBrand();socialButtons();upgradeCartButtons();
   const smokyImg=document.querySelector('.smokyVisual img');
   if(smokyImg)smokyImg.src='assets/smoky-original-scharf.jpg';
   const mo=new MutationObserver(m=>m.forEach(x=>x.addedNodes.forEach(n=>{if(n.nodeType===1)upgradeCartButtons(n)})));
   mo.observe(document.body,{childList:true,subtree:true});
 });
})();

window.addLaugeToCart=function(id,name,price,img){
 if(!CATALOG.find(p=>p.id===id)) CATALOG.push({id,name,price,img,url:'raeucherlaugen.html',unit:'500-g-Beutel'});
 let x=cart.find(i=>i.id===id);x?x.qty++:cart.push({id,qty:1});saveCart();toast(name+' wurde in den Warenkorb gelegt');
};
