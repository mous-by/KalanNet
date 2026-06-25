const CACHE_NAME = 'kalannet-v1';

const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/assets/css/bootstrap.min.css',
    '/assets/css/style.css',
    '/assets/css/icons.css',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/jquery.min.js',
    '/assets/images/icons/icon-192x192.png',
    '/assets/images/icons/icon-512x512.png',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS).catch(function () {
                // Ignore individual cache failures
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    // Only handle GET requests for same-origin
    if (event.request.method !== 'GET') return;
    if (!event.request.url.startsWith(self.location.origin)) return;

    // Network-first strategy: try network, fall back to cache
    event.respondWith(
        fetch(event.request)
            .then(function (response) {
                if (response && response.status === 200 && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(event.request, clone);
                    });
                }
                return response;
            })
            .catch(function () {
                return caches.match(event.request);
            })
    );
});
