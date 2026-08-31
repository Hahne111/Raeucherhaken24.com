(()=>{'use strict';
const names={weihnachten:'Weihnachtszeit bei Räucherhaken24',nikolaus:'Nikolaus-Spezial',ostern:'Osterzeit bei Räucherhaken24',advent:'Adventszeit · besondere Räuchermomente',black_week:'Black Week',black_friday:'Black Friday',silvester:'Silvester bei Räucherhaken24',neujahr:'Frohes neues Jahr · Räucherhaken24'};
const allowed=new Set(['standard',...Object.keys(names)]),KEY='rh24_active_theme_v55';
const valid=t=>allowed.has(String(t||''))?String(t):'standard';
function apply(theme,source='server'){const t=valid(theme);document.body.dataset.rh24Theme=t;document.documentElement.dataset.rh24Theme=t;document.querySelectorAll('.rh24ThemeBanner').forEach(x=>x.remove());if(t!=='standard'){const b=document.createElement('div');b.className='rh24ThemeBanner';b.dataset.themeSource=source;b.setAttribute('aria-label','Aktuelles Shopdesign');b.innerHTML='<span>'+String(names[t]||'Räucherhaken24 Saison-Spezial')+'</span>';document.body.insertBefore(b,document.body.firstChild)}return t}
async function serverTheme(){const urls=['public-theme.php?v='+Date.now(),'shop-products.php?v='+Date.now()];for(const u of urls){try{const r=await fetch(u,{cache:'no-store',credentials:'same-origin'}),d=await r.json();if(r.ok&&d&&d.ok&&allowed.has(String(d.theme||'')))return String(d.theme)}catch(e){}}return null}
async function refresh(){let local=null;try{local=localStorage.getItem(KEY)}catch(e){}if(local)apply(local,'local');const remote=await serverTheme();if(remote){apply(remote,'server');try{localStorage.setItem(KEY,remote)}catch(e){}}else if(!local)apply('standard','fallback');return remote||local||'standard'}
window.RH24Theme={apply,refresh,key:KEY,allowed:[...allowed]};
window.addEventListener('storage',e=>{if(e.key===KEY&&e.newValue)apply(e.newValue,'live')});
document.readyState==='loading'?document.addEventListener('DOMContentLoaded',refresh):refresh();
})();