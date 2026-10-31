/*
 * Service worker de KalanNet (web).
 *
 *  - Pages : toujours demandées au réseau, jamais gardées en cache (elles contiennent des données d'élèves, de
 *    paiements… qui ne doivent pas rester sur un ordinateur partagé). Sans réseau : page « Hors connexion ».
 *  - Fichiers techniques (styles, scripts, images, polices) : réseau d'abord, copie en cache en secours.
 *
 * Changer CACHE_NAME vide les anciens caches (la version 1 gardait aussi les pages : elle est supprimée).
 */
const CACHE_NAME = 'kalannet-v2';
const OFFLINE_URL = '/offline.html';

const STATIC_ASSETS = [
    OFFLINE_URL,
    '/manifest.json',
    '/assets/css/bootstrap.min.css',
    '/assets/css/style.css',
    '/assets/css/icons.css',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/jquery.min.js',
];

const STATIC_PATH = /^\/(assets|css|js|fonts|build|vendor)\/|\.(css|js|png|jpe?g|webp|svg|gif|ico|woff2?|ttf)$/i;

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            // La page hors connexion est indispensable ; le reste est facultatif.
            return cache.add(OFFLINE_URL).then(function () {
                return Promise.all(STATIC_ASSETS.slice(1).map(function (url) {
                    return cache.add(url).catch(function () {});
                }));
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
    const request = event.request;
    if (request.method !== 'GET' || !request.url.startsWith(self.location.origin)) return;

    // Navigation (ouverture d'une page) : réseau seulement, page « Hors connexion » en cas d'échec.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function () {
                return caches.match(OFFLINE_URL);
            })
        );
        return;
    }

    const path = new URL(request.url).pathname;
    if (!STATIC_PATH.test(path)) return; // Données (AJAX, téléchargements…) : jamais en cache.

    event.respondWith(
        fetch(request)
            .then(function (response) {
                if (response && response.status === 200 && response.type === 'basic') {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(request, clone);
                    });
                }
                return response;
            })
            .catch(function () {
                return caches.match(request);
            })
    );
});
