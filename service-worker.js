const CACHE_NAME = 'app-mymica-v1.130';
const urlsToCache = [
  '/css/sb-admin-2.css',
  '/vendor/fontawesome-free/css/all.min.css',
  '/vendor/datatables/dataTables.bootstrap4.min.css',
  '/img/logo.png',
  '/img/logoMini.png',
  '/img/roundLogo.png'
];

// Installa il service worker e cachea le risorse
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        return cache.addAll(urlsToCache);
    })
  );
});

// Gestisce le richieste di rete
self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => {
        // Cache hit - ritorna la risposta dal cache
        if (response) {
          return response;
        }
        return fetch(event.request);
    })
  );
});
