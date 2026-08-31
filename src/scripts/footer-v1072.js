/* V107.2 – echte, skalierbare Social-Media-Symbole im Footer */
(function(){
  'use strict';
  const icon=(view,path)=>`<svg class="rh1072SocialIcon" viewBox="${view}" aria-hidden="true" focusable="false"><path d="${path}"></path></svg>`;
  const icons={
    facebook:icon('0 0 24 24','M13.5 22v-8h2.7l.4-3.1h-3.1V9c0-.9.3-1.5 1.6-1.5h1.7V4.7c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3V14h2.8v8h3.4z'),
    instagram:icon('0 0 24 24','M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.9.3 2.6.6.7.3 1.3.7 1.9 1.3.6.6 1 1.2 1.3 1.9.3.7.5 1.4.6 2.6.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.9-.6 2.6-.3.7-.7 1.3-1.3 1.9-.6.6-1.2 1-1.9 1.3-.7.3-1.4.5-2.6.6-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.9-.3-2.6-.6-.7-.3-1.3-.7-1.9-1.3-.6-.6-1-1.2-1.3-1.9-.3-.7-.5-1.4-.6-2.6C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.3-1.9.6-2.6.3-.7.7-1.3 1.3-1.9.6-.6 1.2-1 1.9-1.3.7-.3 1.4-.5 2.6-.6C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1.1.1-1.7.2-2.1.4-.5.2-.9.5-1.3.9-.4.4-.7.8-.9 1.3-.2.4-.3 1-.4 2.1-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c.1 1.1.2 1.7.4 2.1.2.5.5.9.9 1.3.4.4.8.7 1.3.9.4.2 1 .3 2.1.4 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.5 1.3-.9.4-.4.7-.8.9-1.3.2-.4.3-1 .4-2.1.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.5-.9-.9-1.3-.4-.4-.8-.7-1.3-.9-.4-.2-1-.3-2.1-.4C15.5 4 15.1 4 12 4zm0 3.1a4.9 4.9 0 1 1 0 9.8 4.9 4.9 0 0 1 0-9.8zm0 1.8a3.1 3.1 0 1 0 0 6.2 3.1 3.1 0 0 0 0-6.2zm6.3-2.1a1.15 1.15 0 1 1 0 2.3 1.15 1.15 0 0 1 0-2.3z'),
    tiktok:icon('0 0 24 24','M14.4 3.2c.5 2.3 1.8 3.7 4.1 4.1v3.1c-1.6 0-3-.5-4.1-1.4v6.1a5.6 5.6 0 1 1-4.8-5.5v3.2a2.5 2.5 0 1 0 1.7 2.3V3.2h3.1z'),
    x:icon('0 0 24 24','M4.2 3h4.6l4.1 5.5L17.7 3H20l-6 7 6.7 9H16l-4.6-6.2L6.1 19H3.8l6.5-7.6L4.2 3zm3.5 1.8H6.9l10 12.5h.8L7.7 4.8z'),
    whatsapp:icon('0 0 24 24','M20.5 3.5A11.8 11.8 0 0 0 2 17.7L.6 23l5.4-1.4A11.8 11.8 0 0 0 20.5 3.5zM12 20a9.8 9.8 0 0 1-5-1.4l-.4-.2-3.2.8.9-3.1-.2-.4A9.8 9.8 0 1 1 12 20zm5.4-7.3c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.2-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.6c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.6-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.5 3.8 6 5.3.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.7-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z')
  };
  function open(url){window.open(url,'_blank','noopener,noreferrer')}
  function shareFacebook(){open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(location.href))}
  function shareX(){open('https://twitter.com/intent/tweet?url='+encodeURIComponent(location.href)+'&text='+encodeURIComponent('Räucherhaken24'))}
  function shareWhatsApp(){open('https://wa.me/4917620204188?text='+encodeURIComponent('Hallo, ich habe eine Frage zu Räucherhaken24: '+location.href))}
  async function nativeOrOpen(url){
    if(navigator.share){try{await navigator.share({title:'Räucherhaken24',url:location.href});return}catch(e){}}
    open(url);
  }
  function render(){
    document.querySelectorAll('[data-rh1072-socials]').forEach(box=>{
      box.innerHTML=`
        <button type="button" data-social="facebook" aria-label="Auf Facebook teilen">${icons.facebook}<b>Facebook</b></button>
        <button type="button" data-social="tiktok" aria-label="TikTok öffnen oder teilen">${icons.tiktok}<b>TikTok</b></button>
        <button type="button" data-social="instagram" aria-label="Instagram öffnen oder teilen">${icons.instagram}<b>Instagram</b></button>
        <button type="button" data-social="x" aria-label="Auf X teilen">${icons.x}<b>X</b></button>
        <button type="button" data-social="whatsapp" aria-label="Über WhatsApp teilen">${icons.whatsapp}<b>WhatsApp</b></button>`;
      box.querySelector('[data-social="facebook"]')?.addEventListener('click',shareFacebook);
      box.querySelector('[data-social="tiktok"]')?.addEventListener('click',()=>nativeOrOpen('https://www.tiktok.com/'));
      box.querySelector('[data-social="instagram"]')?.addEventListener('click',()=>nativeOrOpen('https://www.instagram.com/'));
      box.querySelector('[data-social="x"]')?.addEventListener('click',shareX);
      box.querySelector('[data-social="whatsapp"]')?.addEventListener('click',shareWhatsApp);
    });
  }
  document.readyState==='loading'?document.addEventListener('DOMContentLoaded',render):render();
})();
