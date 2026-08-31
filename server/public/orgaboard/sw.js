const CACHE='rh24-orgaboard-static-v107-0-shop-publish-fix';
const STATIC=[
  './offline.html',
  './pos-demo.html',
  './manifest.webmanifest',
  './assets/admin.css?v=107.0',
  './assets/earnings-v97.css?v=97.0',
  './assets/vehicle-v98.css?v=99.1',
  './assets/trip-receipts-v99.css?v=99.1',
  './assets/appointments-v92.css?v=92.0',
  './assets/finance-v91.css?v=91.1',
  './assets/pos-v95.css?v=95.0',
  './assets/labels-v966.css?v=105.4',
  './assets/labels-v967.css?v=105.4',
  './assets/labels-v1053.css?v=105.4',
  './assets/product-builder-v83.css?v=87',
  './assets/product-ai-v1062.css?v=107.0',
  './assets/light-pro-v102.css?v=105.4',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png',
  './assets/icons/icon-maskable-512.png',
  './assets/icons/apple-touch-icon.png'
];
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(STATIC)).then(()=>self.skipWaiting()))});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE).map(k=>caches.delete(k)))).then(()=>self.clients.claim()))});
self.addEventListener('fetch',event=>{
  const req=event.request;
  if(req.method!=='GET') return;
  const url=new URL(req.url);
  if(url.origin!==location.origin) return;
  // Sensitive/data endpoints are always network-only and are never written to Cache Storage.
  if(/\/(api|setup|product-image|passwort|document-pdf|termin-ics|fahrtenbuch-print|fahrtenbuch-export|terminplaner-print|finance-api|finance-export|finance-receipt|finance-dunning|pos-api|pos-receipt|pos-zreport|pos-export|pos-diagnose|label-api|label-print|trip-receipt|product-ai|ai-settings)\.php$/.test(url.pathname)) return;
  if(/\/orgaboard\/admin-v[0-9.-]+\.js$/.test(url.pathname)) return;
  if(req.mode==='navigate'){
    event.respondWith(fetch(req).catch(()=>caches.match('./offline.html')));
    return;
  }
  const isStatic=url.pathname.includes('/orgaboard/assets/')||url.pathname.endsWith('/orgaboard/manifest.webmanifest')||url.pathname.endsWith('/orgaboard/offline.html');
  if(!isStatic) return;
  event.respondWith(caches.match(req,{ignoreSearch:true}).then(hit=>hit||fetch(req).then(resp=>{const copy=resp.clone();caches.open(CACHE).then(c=>c.put(req,copy));return resp;})));
});
