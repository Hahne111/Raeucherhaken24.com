(()=>{
'use strict';
if(window.__RH24_PRODUCT_SYNC_V1083__)return;
window.__RH24_PRODUCT_SYNC_V1083__=true;

const VERSION='108.3';
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const euro=v=>Number(v||0).toLocaleString('de-DE',{style:'currency',currency:'EUR'});
const weight=g=>{g=Number(g||0);if(g<=0)return'noch nicht hinterlegt';return g>=1000?(g/1000).toLocaleString('de-DE',{maximumFractionDigits:3})+' kg':g.toLocaleString('de-DE')+' g'};

const plain=s=>String(s??'').replace(/\s+/g,' ').trim();
const trunc=(s,max)=>{s=plain(s);return s.length>max?s.slice(0,Math.max(1,max-1)).trimEnd()+'…':s};
function ensureUniformStyles(){
  if(document.getElementById('rh24UniformCards1079'))return;
  const style=document.createElement('style');
  style.id='rh24UniformCards1079';
  style.textContent=`
body.rh66ShopOnePage .dbProductGrid,
body.rh66ShopOnePage .rh66ProductGrid{align-items:stretch!important}
body.rh66ShopOnePage .dbProductCard,
body.rh66ShopOnePage .rh66ProductCard{display:flex!important;flex-direction:column!important;height:100%!important;min-width:0!important}
body.rh66ShopOnePage .dbProductImage,
body.rh66ShopOnePage .rh66ProductCard .imgBox{display:grid!important;place-items:center!important;min-height:224px!important}
body.rh66ShopOnePage .dbProductBody,
body.rh66ShopOnePage .rh66ProductCard .body{display:flex!important;flex-direction:column!important;gap:8px!important;flex:1 1 auto!important;min-height:0!important}
body.rh66ShopOnePage .rh66ProductCard .list,
body.rh66ShopOnePage .dbProductCard .list{display:grid!important;gap:6px!important;color:#5d5148!important;font-size:13px!important;line-height:1.35!important;min-height:64px!important;margin-top:2px!important}
body.rh66ShopOnePage .dbProductCard h3,
body.rh66ShopOnePage .rh66ProductCard h3{display:-webkit-box!important;-webkit-box-orient:vertical!important;-webkit-line-clamp:2!important;overflow:hidden!important;min-height:2.5em!important;margin:0!important;line-height:1.25!important}
body.rh66ShopOnePage .dbProductBody>p,
body.rh66ShopOnePage .rh66ProductCard .sub,
body.rh66ShopOnePage .rh1077CardDesc{display:-webkit-box!important;-webkit-box-orient:vertical!important;-webkit-line-clamp:4!important;overflow:hidden!important;min-height:5.5em!important;margin:0!important;color:#6f655d!important;line-height:1.4!important}
body.rh66ShopOnePage .dbProductMeta{display:none!important}
body.rh66ShopOnePage .dbArticleNo{margin:0 0 2px!important;min-height:1.2em!important}
body.rh66ShopOnePage .setBadge{align-self:flex-start!important;margin:0!important}
body.rh66ShopOnePage .productPromoBadges{margin:0!important;min-height:22px!important;align-items:center!important}
body.rh66ShopOnePage .rh1077InfoStack{display:flex!important;flex-direction:column!important;gap:6px!important;margin-top:auto!important;padding-top:2px!important}
body.rh66ShopOnePage .rh1077InfoLine{display:flex!important;align-items:flex-start!important;gap:8px!important;font-size:12px!important;line-height:1.35!important;color:#6e655e!important;min-height:1.25em!important}
body.rh66ShopOnePage .rh1077InfoLine::before{content:"";width:8px;height:8px;border-radius:50%;flex:0 0 8px;margin-top:4px;background:#c59a76}
body.rh66ShopOnePage .rh1077InfoLine.available{color:#246943!important;font-weight:800!important}
body.rh66ShopOnePage .rh1077InfoLine.available::before{background:#2f8556}
body.rh66ShopOnePage .rh1077InfoLine.notice{color:#8b4f24!important;font-weight:700!important}
body.rh66ShopOnePage .rh1077InfoLine.notice::before{background:#c06a32}
body.rh66ShopOnePage .rh1077InfoLine.shipping{color:#5d5148!important}
body.rh66ShopOnePage .dbProductBuy,
body.rh66ShopOnePage .rh66ProductCard .buy{margin-top:10px!important;padding-top:12px!important;border-top:1px solid #eadfd4!important;display:grid!important;grid-template-columns:1fr!important;gap:10px!important;align-items:stretch!important}
body.rh66ShopOnePage .dbProductBuy strong,
body.rh66ShopOnePage .rh66ProductCard .price{display:flex!important;flex-direction:column!important;justify-content:flex-end!important;gap:4px!important;min-height:54px!important;min-width:0!important}
body.rh66ShopOnePage .dbProductBuy>div,
body.rh66ShopOnePage .rh66ProductCard .buy>div:last-child{display:grid!important;grid-template-columns:minmax(92px,.68fr) minmax(0,1.32fr)!important;gap:8px!important;width:100%!important}
body.rh66ShopOnePage .dbProductBuy .detailLink,
body.rh66ShopOnePage .dbProductBuy button,
body.rh66ShopOnePage .rh66ProductCard .detailLink,
body.rh66ShopOnePage .rh66ProductCard .buy button{width:100%!important;justify-content:center!important;text-align:center!important}
body.rh66ShopOnePage .dbProductBuy .detailLink,
body.rh66ShopOnePage .rh66ProductCard .detailLink{white-space:nowrap!important}
body.rh66ShopOnePage .dbProductBuy button,
body.rh66ShopOnePage .rh66ProductCard .buy button{white-space:normal!important;overflow:visible!important;text-overflow:clip!important;line-height:1.15!important;font-size:11px!important;padding:8px 12px!important}
body.rh66ShopOnePage .rh66ProductCard .productActions,
body.rh66ShopOnePage .dbProductCard .productActions{display:grid!important;grid-template-columns:1fr 1fr!important;gap:8px!important;margin-top:8px!important}
body.rh66ShopOnePage .rh66ProductCard .miniAction,
body.rh66ShopOnePage .dbProductCard .miniAction{display:flex!important;align-items:center!important;justify-content:center!important;min-height:40px!important;text-align:center!important;white-space:nowrap!important}
body.rh66ShopOnePage .dbProductCard .promoPriceLine,
body.rh66ShopOnePage .rh66ProductCard .promoPriceLine{display:flex!important;flex-wrap:wrap!important;gap:8px!important;align-items:baseline!important}
body.rh66ShopOnePage .dbProductCard .promoCurrentPrice,
body.rh66ShopOnePage .rh66ProductCard .promoCurrentPrice{font-size:1em!important}
body.rh66ShopOnePage .dbProductCard .unitPriceLine,
body.rh66ShopOnePage .rh66ProductCard .unitPriceLine,
body.rh66ShopOnePage .rh66ProductCard .packLabel{display:block!important;font-size:10px!important;line-height:1.35!important;color:#776c63!important;margin-top:0!important}
@media(max-width:860px){
  body.rh66ShopOnePage .dbProductBuy>div,
  body.rh66ShopOnePage .rh66ProductCard .buy>div:last-child{grid-template-columns:1fr!important}
  body.rh66ShopOnePage .dbProductImage,
  body.rh66ShopOnePage .rh66ProductCard .imgBox{min-height:210px!important}
}
`;
  document.head.appendChild(style);
}
function badgeInfoFromUnit(unit,fallbackLabel='ONLINE'){
  const raw=plain(unit);
  const lower=raw.toLowerCase();
  if(!raw)return{cls:'',bubble:'NEU',label:fallbackLabel};
  if(/(^|\D)10(\D|$)/.test(lower)&&(lower.includes('set')||lower.includes('stück')||lower.includes('stk')||lower.includes('er'))){return{cls:'',bubble:'10×',label:'10ER-SET'};}
  if(lower.includes('stück')||lower==='stk'||lower==='stück'){return{cls:' single',bubble:'1×',label:'STÜCK'};}
  if(lower.includes('gramm')||/\b\d+\s*g\b/.test(lower)){const n=(raw.match(/\d+/)||['500'])[0];return{cls:'',bubble:n,label:'GRAMM'};}
  if(lower.includes('kg')){const n=(raw.match(/\d+/)||['1'])[0];return{cls:'',bubble:n,label:'KG'};}
  const short=raw.toUpperCase();
  return{cls:'',bubble:(raw.match(/\d+/)||['NEU'])[0],label:short.length>14?short.slice(0,14):short};
}
function setBadgeMarkupHtml(p,fallbackLabel='ONLINE'){
  const b=badgeInfoFromUnit(p?.unit,fallbackLabel);
  return `<div class="setBadge${b.cls}"><span>${esc(b.bubble)}</span> ${esc(b.label)}</div>`;
}
function availabilityHtml(p){
  if(Number(p?.stock||0)>0)return '<div class="rh1077InfoLine available">Sofort verfügbar · 1–3 Werktage</div>';
  if(Number(p?.price||0)>0)return '<div class="rh1077InfoLine notice">Verfügbarkeit auf Anfrage</div>';
  return '<div class="rh1077InfoLine notice">Preis und Verfügbarkeit folgen</div>';
}
function shippingHtml(){return '<div class="rh1077InfoLine shipping">Versandkostenfrei ab 39 € innerhalb Deutschlands</div>';}
function cardDesc(p,max=170){return trunc(p?.short_description||p?.description||'',max);}
function packLabelHtml(p){
  if(p?.unit_price?.label)return `<span class="packLabel">${esc(p.unit_price.label)}</span>`;
  const unit=plain(p?.unit);
  if(/stück/i.test(unit)){if(plain(p?.unit_price?.label).toLowerCase().includes('stück'))return '';return '<span class="packLabel">pro Stück</span>';}
  if(/(^|\D)10(\D|$)/.test(unit.toLowerCase())&&(unit.toLowerCase().includes('set')||unit.toLowerCase().includes('stück')||unit.toLowerCase().includes('stk')||unit.toLowerCase().includes('er')))return '<span class="packLabel">10er-Set · Grundausführung</span>';
  if(unit)return `<span class="packLabel">${esc(unit)}</span>`;
  return '';
}
function featureItems(p){
  let arr=Array.isArray(p?.features)?p.features.map(x=>plain(x)).filter(Boolean):[];
  if(!arr.length){
    const desc=plain(p?.description||p?.short_description||'');
    arr=desc.split(/(?:[.!?;•]|\n)+/).map(x=>plain(x)).filter(Boolean);
  }
  if(!arr.length){
    const cat=plain(p?.category||'');
    arr=[cat||'Handgefertigtes Produkt aus Deutschland','Saubere Verarbeitung','Weitere Varianten auf der Detailseite'];
  }
  return arr.slice(0,3);
}
function featureListHtml(p){
  const items=featureItems(p);
  return `<div class="list">${items.map(x=>`<div>${esc(x)}</div>`).join('')}</div>`;
}
function stampUniformCards(root=document){
  root.querySelectorAll?.('.rh66ProductCard,.dbProductCard').forEach(card=>card.classList.add('rh1077UniformCard'));
}
const ROUTES={
 'raeucherhaken-standard.html':'std','raeucherhaken-standard-aal.html':'aal','raeucherhaken-ultra.html':'ultra','raeucherhaken-kralle.html':'kralle','raeucherhaken-filet.html':'filet','raeucherhaken-doppeldorn.html':'doppel','fleischerhaken-s-form-5mm.html':'fleisch',
 'raeuchermehl-buche.html':'mehl-buche','raeuchermehl-erle.html':'mehl-erle','raeuchermehl-birke.html':'mehl-birke','raeuchermehl-eiche.html':'mehl-eiche','raeuchermehl-kirsche.html':'mehl-kirsche'
};
const routeId=href=>{
  if(!href)return'';
  const clean=String(href).split('#')[0].split('?')[0].replace(/^.*\//,'').toLowerCase();
  if(ROUTES[clean])return ROUTES[clean];
  const m=String(href).match(/artikel\.php\?id=([^&#]+)/i);return m?decodeURIComponent(m[1]):'';
};
const onclickId=el=>{const s=el?.getAttribute?.('onclick')||'';const m=s.match(/(?:addToCart|addLaugeToCart)\(['"]([^'"]+)['"]/);return m?m[1]:''};
const imageUrl=p=>{const src=String(p?.image||'').trim();if(!src)return'';const ver=String(p.updated_at||p.catalog_updated_at||'').replace(/\D/g,'').slice(0,14);return ver?src+(src.includes('?')?'&':'?')+'v='+encodeURIComponent(ver):src;};

function badgeHtml(p){const a=[];if(p?.is_new_active)a.push('<span class="newSortimentBadge">Neu im Sortiment</span>');if(p?.is_offer&&(p.sale_active||!Number(p.sale_price)))a.push('<span class="smokyPromoBadge offer"><span><b>ANGEBOT</b></span></span>');if(p?.is_popular)a.push('<span class="smokyPromoBadge popular"><span><b>BELIEBT</b></span></span>');return a.length?'<div class="productPromoBadges">'+a.join('')+'</div>':''}
function priceHtml(p,pack=''){if(Number(p?.price)<=0)return'Preis folgt';const old=p.sale_active&&Number(p.display_old_price)>Number(p.price)?`<span class="promoOldPrice">${euro(p.display_old_price)}</span>`:'';const unitLbl=plain(p?.unit_price?.label);const packText=plain(String(pack||'').replace(/<[^>]*>/g,' '));const finalPack=(unitLbl&&packText&&unitLbl.toLowerCase()===packText.toLowerCase())?'':(pack||'');return `<span class="promoPriceLine">${old}<span class="promoCurrentPrice">${euro(p.price)}</span></span>${unitLbl?`<span class="unitPriceLine">${esc(p.unit_price.label)}</span>`:''}${finalPack}`}

function syncCatalog(products){
  if(typeof CATALOG==='undefined')return;
  products.forEach(p=>{
    let x=CATALOG.find(a=>a.id===p.id);
    const row={id:p.id,article_no:p.article_no||'',name:p.name,price:Number(p.price||0),img:p.image||'assets/smoky-hilfe-button.png',unit:p.unit||'Stück',url:p.url||('artikel.php?id='+encodeURIComponent(p.id)),product_weight_g:Number(p.product_weight_g||0),shipping_weight_g:Number(p.shipping_weight_g||0)};
    if(x)Object.assign(x,row);else CATALOG.push(row);
  });
  if(typeof saveCart==='function')saveCart();
}

function productIdForRoot(root){
  if(!root)return'';
  if(root.dataset?.productId)return root.dataset.productId;
  const b=root.matches?.('button[onclick*="addToCart"],button[onclick*="addLaugeToCart"]')?root:root.querySelector?.('button[onclick*="addToCart"],button[onclick*="addLaugeToCart"]');
  const bid=onclickId(b);if(bid)return bid;
  const a=root.matches?.('a[href]')?root:root.querySelector?.('a[href]');
  return routeId(a?.getAttribute('href'));
}
function setImage(container,p){
  if(!container||!p?.image)return;
  const img=container.matches?.('img')?container:container.querySelector?.('img');if(!img)return;
  const src=imageUrl(p);img.src=src;img.alt=p.name||img.alt||'';
  const zoom=container.querySelector?.('.zoom,.rh104ProductZoom');if(zoom){zoom.onclick=e=>{e.preventDefault();e.stopPropagation();if(typeof window.openZoom==='function')window.openZoom(src,p.name);else if(typeof window.zoom==='function')window.zoom(src,p.name);};}
  if(container.classList?.contains('woodProductImage')||container.classList?.contains('productImage'))container.onclick=()=>{if(typeof window.openZoom==='function')window.openZoom(src,p.name);else if(typeof window.zoom==='function')window.zoom(src,p.name);};
}
function setText(el,text){if(el&&String(text??'').trim()!=='')el.textContent=String(text).trim();}
function setPrice(el,p){if(!el)return;const pack=el.querySelector?.('.packLabel')?.outerHTML||'';el.innerHTML=priceHtml(p,pack);}
function setFeatures(root,p){if(!Array.isArray(p.features)||!p.features.length)return;const list=root.querySelector?.('.list,.dynFeatures,.productFeatures');if(!list)return;if(list.matches('ul'))list.innerHTML=p.features.map(x=>`<li>${esc(x)}</li>`).join('');else list.innerHTML=p.features.map(x=>`<div>${esc(x)}</div>`).join('');}
function syncCommon(root,p){
  if(!root||!p)return;root.dataset.productId=p.id;root.dataset.dbSynced=VERSION;
  const imgBox=root.querySelector?.('.rh104ProductImg,.imgBox,.woodProductImage,.productImage,.dbProductImage,.laugeVisual,.natureImage,.thermoImage,.woodShopCard>a:first-child');if(imgBox)setImage(imgBox,p);
  const h=root.querySelector?.('h3,h2.productName,.productInfo h1,.dynInfo h1');if(h)setText(h,p.name);
  const desc=root.querySelector?.('.sub,.dbProductBody>p,.rh104ProductBody .meta,.productLead,.dynLead');if(desc&&p.short_description)setText(desc,p.short_description);
  const price=root.querySelector?.('.price,.productPrice,.woodPrice,.rh104Price,.dbProductBuy>strong,.dynPrice,.laugePrice b');if(price)setPrice(price,p);
  const art=root.querySelector?.('.dbArticleNo b');if(art)setText(art,p.article_no);
  setFeatures(root,p);
}

function syncHomeCard(card,p){
  syncCommon(card,p);
  const h=card.querySelector('h3');if(h)setText(h,p.name);
  const meta=card.querySelector('.meta');if(meta)setText(meta,p.short_description||p.unit||'');
  const img=card.querySelector('.rh104ProductImg img');if(img&&p.image)img.src=imageUrl(p);
  const link=card.querySelector('.rh104ProductImg,a.productLink');if(link&&p.url)link.href=p.url;
}
function syncHookCard(card,p){syncCommon(card,p);const sub=card.querySelector('.sub');if(sub)setText(sub,cardDesc(p,120)||p.short_description||p.description||'');const badge=card.querySelector('.setBadge');if(badge)badge.outerHTML=setBadgeMarkupHtml(p,'ONLINE');card.classList.add('rh1077UniformCard');}
function syncWoodCard(card,p){
  card.dataset.productId=p.id;card.dataset.dbSynced=VERSION;
  const a=card.querySelector('a[href]');if(a&&p.url)a.href=p.url;
  setImage(a,p);
  const h=card.querySelector('h3');if(h)setText(h,p.name.replace(/^Räuchermehl\s+/i,'').replace(/\s*[–-]\s*\d+\s*g.*$/i,''));
  const d=card.querySelector('p');if(d)setText(d,p.short_description||p.description||'');
  const strong=card.querySelector('strong');if(strong)setPrice(strong,p);
  const small=card.querySelector('small');if(small)setText(small,p.unit||small.textContent);
}
function syncDetailPage(p){
  const current=(location.pathname.split('/').pop()||'').toLowerCase();const pageId=ROUTES[current]||'';if(!pageId||p.id!==pageId)return;
  document.documentElement.dataset.rh24ProductId=p.id;
  if(p.seo_title)document.title=p.seo_title+' | Räucherhaken24';
  let meta=document.querySelector('meta[name="description"]');if(!meta){meta=document.createElement('meta');meta.name='description';document.head.appendChild(meta)}if(p.seo_description)meta.setAttribute('content',p.seo_description);
  if(p.seo_keywords){let kw=document.querySelector('meta[name="keywords"]');if(!kw){kw=document.createElement('meta');kw.name='keywords';document.head.appendChild(kw)}kw.setAttribute('content',p.seo_keywords)}
  let canonical=document.querySelector('link[rel="canonical"]');if(!canonical){canonical=document.createElement('link');canonical.rel='canonical';document.head.appendChild(canonical)}canonical.href=new URL(p.url||location.pathname,location.origin).href;
  const structured={ '@context':'https://schema.org','@type':'Product',name:p.name,description:p.seo_description||p.short_description||p.description||'',sku:p.sku||p.article_no||p.id,image:p.image?[new URL(imageUrl(p),location.href).href]:undefined,offers:{'@type':'Offer',priceCurrency:'EUR',price:Number(p.price||0).toFixed(2),availability:Number(p.stock||0)>0?'https://schema.org/InStock':'https://schema.org/OutOfStock',url:new URL(p.url||location.href,location.origin).href}};
  let ld=document.getElementById('rh24ProductStructuredData');if(!ld){ld=document.createElement('script');ld.id='rh24ProductStructuredData';ld.type='application/ld+json';document.head.appendChild(ld)}ld.textContent=JSON.stringify(structured);
  const heroH=document.querySelector('.hero h1');if(heroH)setText(heroH,p.name);
  const heroP=document.querySelector('.hero p');if(heroP)setText(heroP,p.short_description||p.description||'');
  const root=document.querySelector('.woodProductHero,.productPage,.productDetail,.hookProduct,.content')||document.querySelector('main');
  if(root){
    root.dataset.productId=p.id;
    const image=root.querySelector('.woodProductImage,.productImage,.imgBox');if(image)setImage(image,p);
    const name=root.querySelector('.woodProductBuy h2,.productInfo h1,h1');if(name)setText(name,p.name);
    const price=root.querySelector('.woodPrice,.productPrice,.price');if(price)setPrice(price,p);
    const pack=root.querySelector('.woodPack');if(pack&&p.unit)setText(pack,p.unit);
    let dyn=root.querySelector('.rh24DbDescription');
    if(p.description){if(!dyn){dyn=document.createElement('p');dyn.className='rh24DbDescription';const anchor=root.querySelector('.woodProductBuy h2,.productInfo h1,h1');anchor?.insertAdjacentElement('afterend',dyn);}setText(dyn,p.description);}
    const btn=root.querySelector('button[onclick*="addToCart"]');if(btn)btn.setAttribute('onclick',`addToCart('${p.id}')`);
  }
}
function markUnavailableKnown(productsById){
  const current=(location.pathname.split('/').pop()||'').toLowerCase();const pageId=ROUTES[current];if(pageId&&!productsById[pageId]){
    const btn=document.querySelector('button[onclick*="addToCart"]');if(btn){btn.disabled=true;btn.textContent='Aktuell nicht verfügbar';}
  }
}
function syncLaugeCard(card,p){
  card.dataset.productId=p.id;card.dataset.dbSynced=VERSION;
  const media=card.querySelector('.laugeVisual');if(media)setImage(media,p);
  const h=card.querySelector('h3');if(h)setText(h,p.name);
  const d=card.querySelector('p');if(d)setText(d,p.short_description||p.description||'');
  const pr=card.querySelector('.laugePrice b');if(pr)setPrice(pr,p);
  const unit=card.querySelector('.laugePrice span,.laugeTag');if(unit&&p.unit)setText(unit,p.unit+(p.unit_price?.label?' · '+p.unit_price.label:''));
  const btn=card.querySelector('button[onclick*="addLaugeToCart"],button[onclick*="addToCart"]');if(btn)btn.onclick=()=>{if(typeof window.addToCart==='function')window.addToCart(p.id);};
}
function renderPopular(products){
  const grid=document.querySelector('.rh104Products');if(!grid)return;
  const preferred=products.filter(p=>p.is_popular).slice(0,6);
  const fallbackIds=['std','ultra','filet','lauge-forelle-0','mehl-buche','kralle'];
  const byId=Object.fromEntries(products.map(p=>[p.id,p]));
  const rows=preferred.length?preferred:fallbackIds.map(id=>byId[id]).filter(Boolean);
  if(!rows.length){grid.innerHTML='<p>Aktuell werden Produkte vorbereitet.</p>';return;}
  grid.innerHTML=rows.map(p=>`<article class="rh104Product" data-product-id="${esc(p.id)}"><a class="rh104ProductImg" href="${esc(p.url)}">${p.image?`<img src="${esc(imageUrl(p))}" alt="${esc(p.name)}" loading="lazy" decoding="async">`:'<span>Produktbild folgt</span>'}</a><div class="rh104ProductBody"><a class="productLink" href="${esc(p.url)}"><h3>${esc(p.name)}</h3></a><div class="meta">${esc(p.short_description||p.unit||'')}</div><div class="rh104ProductFoot"><span class="rh104Price">${priceHtml(p)}</span>${Number(p.price)>0?`<button class="rh104CartButton" onclick="addToCart('${esc(p.id)}')" aria-label="${esc(p.name)} in den Warenkorb">🛒</button>`:''}</div></div></article>`).join('');
}
function replaceLegacyImages(products){
  const legacy={
    'std':'assets/standard.png','aal':'assets/standard-aal-weiss.png','ultra':'assets/ultra-original-korrekt.png','kralle':'assets/kralle.png','filet':'assets/filet.png','doppel':'assets/doppeldorn.png','fleisch':'assets/fleischer.jpeg',
    'mehl-buche':'assets/raeuchermehl-buche-produkt.jpg','mehl-erle':'assets/raeuchermehl-erle-produkt.jpg','mehl-birke':'assets/raeuchermehl-birke-produkt.jpg','mehl-eiche':'assets/raeuchermehl-eiche-produkt.jpg','mehl-kirsche':'assets/raeuchermehl-kirsche-produkt.jpg',
    'lauge-forelle-0':'assets/lauge-standard.png','lauge-forelle-1':'assets/lauge-delikat.png','lauge-forelle-2':'assets/lauge-chili.png','lauge-forelle-3':'assets/lauge-red.png','lauge-forelle-4':'assets/lauge-kraeuter.png','lauge-forelle-5':'assets/lauge-knoblauch.png','lauge-forelle-6':'assets/lauge-zitronenpfeffer.png','lauge-forelle-7':'assets/lauge-gartenkraeuter.png','lauge-forelle-8':'assets/lauge-elpaso.png','lauge-forelle-9':'assets/lauge-kansas.png',
    'lauge-aal-0':'assets/lauge-aal_standard.png','lauge-aal-1':'assets/lauge-aal_pfeffer.png','lauge-aal-2':'assets/lauge-aal_delikat.png'
  };
  const byId=Object.fromEntries(products.map(p=>[p.id,p]));
  Object.entries(legacy).forEach(([id,oldSrc])=>{const p=byId[id];if(!p?.image||p.image===oldSrc)return;document.querySelectorAll('img').forEach(img=>{const src=(img.getAttribute('src')||'').split('?')[0];if(src===oldSrc){img.src=imageUrl(p);img.alt=p.name||img.alt;}});});
}
function syncStatic(products){
  const map=Object.fromEntries(products.map(p=>[p.id,p]));
  renderPopular(products);replaceLegacyImages(products);
  document.querySelectorAll('.rh104Product').forEach(card=>{const id=productIdForRoot(card);if(id&&map[id])syncHomeCard(card,map[id]);else if(id&&!map[id])card.hidden=true;});
  document.querySelectorAll('.rh66ProductCard').forEach(card=>{const id=productIdForRoot(card);if(id&&map[id])syncHookCard(card,map[id]);else if(id&&!map[id])card.hidden=true;});
  document.querySelectorAll('.woodShopCard').forEach(card=>{const id=productIdForRoot(card);if(id&&map[id])syncWoodCard(card,map[id]);else if(id&&!map[id])card.hidden=true;});
  document.querySelectorAll('.laugeCard').forEach(card=>{const id=productIdForRoot(card);if(id&&map[id])syncLaugeCard(card,map[id]);else if(id&&!map[id])card.hidden=true;});
  document.querySelectorAll('.dbProductCard,.natureCard,.thermoCard,.dynCard').forEach(card=>{const id=productIdForRoot(card);if(id&&map[id])syncCommon(card,map[id]);});
  Object.values(map).forEach(syncDetailPage);markUnavailableKnown(map);
  // Representative category images should also reflect the current master product image.
  const buche=map['mehl-buche'];if(buche){document.querySelectorAll('a[href="#raeuchermehl-shop"] img,.rh104Category[href="shop.html#raeuchermehl-shop"] img,.rhCatCard[href="shop.html#raeuchermehl-shop"] img').forEach(img=>{img.src=imageUrl(buche);img.alt=buche.name;});}
}
/* -----------------------------------------------------------------
   V2026.3 · FEHLERBEHEBUNG (bestand bereits vor diesem Update)

   Die Kartenvorlage für dynamisch veröffentlichte Räucherhaken rief
   `actionButtonsHtml(p.id)` auf. Diese Funktion war im gesamten
   Projekt nirgends definiert. Der Aufruf löste einen ReferenceError
   aus, der `renderDynamicHookExtras()` und damit die komplette
   Katalogsynchronisierung mitten im Durchlauf abbrach.

   Sichtbare Folge im Alltag:
     · Ein im OrgaBoard NEU angelegter und veröffentlichter Räucher-
       haken erschien NIE im Shop.
     · Weil `init()` vorzeitig endete, blieben auch die nachfolgenden
       Schritte aus: dynamische Artikelliste, Produktdetail-Abgleich
       und die Markierung `data-rh24-catalog-sync`.

   Die Funktion wird hier wiederhergestellt. Sie erfindet bewusst
   keine Bedienelemente: Ohne registrierte Zusatzaktionen liefert sie
   eine leere Zeichenkette – die Karte sieht dann exakt so aus wie die
   übrigen Produktkarten. Module können über
   `window.RH24CardActions` echte Aktionen nachrüsten; jede Aktion
   muss dafür ein Label und eine vorhandene Funktion mitbringen.
------------------------------------------------------------------ */
function actionButtonsHtml(productId){
  const id=String(productId??'');
  if(!id)return'';
  const provider=window.RH24CardActions;
  let items=[];
  try{
    items=typeof provider==='function'?provider(id):(Array.isArray(provider)?provider:[]);
  }catch(e){items=[];}
  if(!Array.isArray(items)||!items.length)return'';
  const buttons=items.filter(a=>a&&a.label&&typeof window[a.handler]==='function').slice(0,2);
  if(!buttons.length)return'';
  return `<div class="productActions">${buttons.map(a=>
    `<button type="button" class="miniAction" onclick="${esc(a.handler)}('${esc(id)}')">${esc(a.label)}</button>`
  ).join('')}</div>`;
}

function isHookProduct(p){const c=String(p?.category||'').toLowerCase();return p?.type==='hook'||c.includes('räucherhaken')||c.includes('raeucherhaken')}
function renderDynamicHookExtras(products,staticIds){
 const head=document.getElementById('raeucherhaken');const grid=head?.nextElementSibling?.classList?.contains('rh66ProductGrid')?head.nextElementSibling:document.querySelector('.rh66ProductGrid');if(!grid)return new Set();
 grid.querySelectorAll('[data-rh24-dynamic-hook="1"]').forEach(x=>x.remove());
 const rows=products.filter(p=>!staticIds.has(p.id)&&isHookProduct(p));const injected=new Set(rows.map(p=>p.id));
 const card=p=>`<article class="card rh66ProductCard rh1077UniformCard" data-product-id="${esc(p.id)}" data-rh24-dynamic-hook="1"><div class="imgBox">${p.image?`<img src="${esc(imageUrl(p))}" alt="${esc(p.name)}" loading="lazy" decoding="async"><button class="zoom" type="button" aria-label="Produktbild vergrößern">⌕</button>`:'<div class="dbNoImage">Produktbild folgt</div>'}</div><div class="body">${badgeHtml(p)}<h3>${esc(p.name)}</h3>${setBadgeMarkupHtml(p,'ONLINE')}<div class="sub">${esc(cardDesc(p,72)||String(p.category||'Räucherhaken'))}</div>${featureListHtml(p)}<div class="buy"><div class="price">${priceHtml(p,packLabelHtml(p))}</div><div style="display:flex;gap:7px"><a class="detailLink" href="${esc(p.url)}">Details</a>${Number(p.price)>0?`<button onclick="addToCart('${esc(p.id)}')">In den Warenkorb</button>`:''}</div></div>${actionButtonsHtml(p.id)}</div></article>`;
 rows.forEach(p=>{const wrap=document.createElement('div');wrap.innerHTML=card(p);const el=wrap.firstElementChild;const zoom=el?.querySelector('.zoom');if(zoom&&p.image)zoom.onclick=e=>{e.preventDefault();e.stopPropagation();const src=imageUrl(p);if(typeof window.openZoom==='function')window.openZoom(src,p.name);else if(typeof window.zoom==='function')window.zoom(src,p.name)};grid.appendChild(el)});
 stampUniformCards(grid);
 return injected;
}
function renderShopExtras(products,staticIds,alreadyInjected=new Set()){
 const grid=document.getElementById('dbProductGrid');if(!grid)return;
 const extra=products.filter(p=>!staticIds.has(p.id)&&!alreadyInjected.has(p.id)&&p.category!=='Naturgewürze');
 const sec=document.getElementById('dbProductSection');
 if(!extra.length){if(sec)sec.style.display='none';return}if(sec)sec.style.display='';
 const card=p=>`<article class="dbProductCard rh1077UniformCard" data-product-id="${esc(p.id)}"><a class="dbProductImage" href="${esc(p.url)}">${p.image?`<img src="${esc(imageUrl(p))}" alt="${esc(p.name)}" loading="lazy" decoding="async">`:'<div class="dbNoImage">Produktbild folgt</div>'}</a><div class="dbProductBody"><div class="dbArticleNo">Art.-Nr. <b>${esc(p.article_no||'')}</b></div>${badgeHtml(p)}<h3>${esc(p.name)}</h3>${setBadgeMarkupHtml(p,'ONLINE')}<p class="rh1077CardDesc">${esc(cardDesc(p,170)||String(p.category||''))}</p><div class="rh1077InfoStack">${availabilityHtml(p)}${shippingHtml()}</div><div class="dbProductBuy"><strong>${priceHtml(p)}</strong><div><a class="detailLink" href="${esc(p.url)}">Details</a>${Number(p.price)>0?`<button onclick="addToCart('${esc(p.id)}')">In den Warenkorb</button>`:''}</div></div></div></article>`;
 grid.innerHTML=extra.map(card).join('');
 stampUniformCards(grid);
}
async function init(){
 try{
   ensureUniformStyles();
   const d=await window.RH24ShopData.get();const products=d.products||[];

   // V108.0: Alte, dynamisch erzeugte Karten zuerst entfernen. Sonst würden sie
   // beim nächsten Refresh irrtümlich als feste Shop-Karten erkannt und danach
   // aus der dynamischen Artikelliste herausgefiltert.
   document.querySelectorAll('[data-rh24-dynamic-hook="1"]').forEach(el=>el.remove());

   syncCatalog(products);syncStatic(products);

   // Nur tatsächlich fest im HTML vorhandene Produktkarten als "statisch" werten.
   // Dynamisch veröffentlichte Produkte (z. B. neu angelegte Räucherhaken) dürfen
   // hier niemals landen, damit sie auch nach Fokuswechsel/Refresh sichtbar bleiben.
   const staticIds=new Set();
   document.querySelectorAll('.rh66ProductCard:not([data-rh24-dynamic-hook="1"]),.woodShopCard,.rh104Product').forEach(root=>{
     const id=productIdForRoot(root);if(id)staticIds.add(id);
   });

   /* V2026.3 · Jeder Renderer wird einzeln abgesichert.
      Bisher hat ein Fehler in EINEM Renderer den gesamten weiteren
      Ablauf beendet (siehe Fehlerbehebung oben). Jetzt läuft der
      Rest weiter und der Shop bleibt vollständig aktuell. */
   let injected=new Set();
   try{injected=renderDynamicHookExtras(products,staticIds)||new Set();}
   catch(e){console.warn('[RH24 V108.3] Dynamische Hakenkarten:',e);}
   try{renderShopExtras(products,staticIds,injected);}
   catch(e){console.warn('[RH24 V108.3] Weitere Artikel:',e);}
   stampUniformCards(document);
   document.documentElement.dataset.rh24CatalogSync=VERSION;
   window.dispatchEvent(new CustomEvent('rh24:catalog-synced',{detail:{version:VERSION,count:products.length,catalogUpdatedAt:d.catalog_updated_at||''}}));
 }catch(e){
   console.warn('[RH24 V108.3] Produkt-Synchronisierung:',e);
   /* Sichtbarer Fehlerzustand: Ohne diesen Block bliebe im Bereich
      „Neue & weitere Artikel" dauerhaft „… werden geladen“ stehen. */
   const grid=document.getElementById('dbProductGrid');
   if(grid&&!grid.querySelector('.dbProductCard')){
     grid.innerHTML='<div class="dbNoImage">Die aktuellen Zusatzartikel sind gerade nicht erreichbar. Das bestehende Sortiment oben ist davon nicht betroffen.</div>';
   }
 }
}
let lastRefresh=0,refreshing=false;
async function refresh(){if(refreshing)return;refreshing=true;try{window.RH24ShopData.reset();await init();lastRefresh=Date.now();}finally{refreshing=false;}}
window.RH24ProductSync={version:VERSION,refresh};
const maybeRefresh=()=>{if(Date.now()-lastRefresh>5000)refresh();};
window.addEventListener('focus',maybeRefresh);
document.addEventListener('visibilitychange',()=>{if(!document.hidden)maybeRefresh();});
window.addEventListener('storage',e=>{if(e.key==='rh24-shop-catalog-refresh')refresh();});
window.addEventListener('rh24:shop-refresh',()=>refresh());

/* -----------------------------------------------------------------
   V2026.3 · Anbindung an die Near-Realtime-Schicht
   (rh-realtime-2026.js · shop-catalog-version.php)

   Die Schicht meldet sich nur, wenn sich die Katalogrevision am Server
   tatsächlich geändert hat. Dann – und nur dann – werden die Produkt-
   daten neu geladen. Es wird kein Seitenreload ausgelöst und keine
   Bildschirmposition verworfen.

   Die vier Ereignisse oben bleiben unverändert bestehen. Sie sind der
   Rückfall, falls rh-realtime-2026.js nicht geladen werden konnte.
------------------------------------------------------------------ */
function announceUpdated(){
  document.documentElement.dataset.rh24CatalogFresh=String(Date.now());
  const host=document.getElementById('rh24CatalogPulse')||(()=>{
    const el=document.createElement('div');
    el.id='rh24CatalogPulse';
    el.setAttribute('role','status');
    el.setAttribute('aria-live','polite');
    el.className='rh24CatalogPulse';
    document.body.appendChild(el);
    return el;
  })();
  host.textContent='Sortiment aktualisiert';
  host.classList.add('is-on');
  clearTimeout(host._tm);
  host._tm=setTimeout(()=>host.classList.remove('is-on'),2400);
}
function bindRealtime(){
  if(!window.RH24Realtime||window.__RH24_SYNC_REALTIME_BOUND__)return false;
  window.__RH24_SYNC_REALTIME_BOUND__=true;
  window.RH24Realtime.onChange(async()=>{
    await refresh();
    announceUpdated();
  });
  return true;
}
if(!bindRealtime()){
  // rh-realtime-2026.js wird mit defer geladen und kann später kommen.
  window.addEventListener('DOMContentLoaded',bindRealtime,{once:true});
  window.addEventListener('load',bindRealtime,{once:true});
}

if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',async()=>{await init();lastRefresh=Date.now();},{once:true});else{init().then(()=>{lastRefresh=Date.now();});}
})();
