// Service worker for the CLSU Moto-Track driver app.
//
// Scope is registered as /driver/ (see driver-location-tracker.blade.php),
// so this only ever controls the driver-facing pages, never the
// admin/employee/VP parts of MPIS.
//
// Intentionally minimal: this exists to satisfy PWA installability
// (a registered service worker + fetch handler is required for
// `beforeinstallprompt` to fire) and to avoid an ugly browser error
// screen when a driver briefly loses signal. It deliberately does NOT
// cache trip tickets, location pings, or any API/data response —
// tracking data must always be live.

const CACHE_NAME = 'motrack-driver-shell-v1';
const OFFLINE_URL = '/driver-offline.html';
const APP_SHELL = [
    OFFLINE_URL,
    '/manifest.json',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(APP_SHELL))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Only ever handle safe, same-origin GET requests. Everything else
    // (POST location updates, PATCH/PUT status changes, cross-origin map
    // tiles, etc.) goes straight to the network untouched.
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) {
        return;
    }

    // Page navigations: always try the network first so drivers see
    // current trip data; only fall back to the offline page if the
    // network is truly unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }

    // Static app-shell assets (icons, manifest): cache-first, since these
    // never change per-request and this keeps the install prompt/icon
    // available even on a flaky connection.
    if (APP_SHELL.some((path) => request.url.endsWith(path))) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request))
        );
    }
});
