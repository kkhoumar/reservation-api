/*
 * Maquis Express — service worker (application installable)
 *
 * - Ouvre l'application plus vite en gardant en mémoire les icônes,
 *   les polices et le script Livewire.
 * - Sans réseau, affiche une page claire au lieu de l'erreur du navigateur.
 *
 * Les pages et les données (commandes, caisse…) ne sont JAMAIS mises en
 * cache : elles viennent toujours du serveur, pour qu'aucun chiffre ne soit
 * périmé et qu'aucune donnée d'un maquis ne reste sur un téléphone partagé.
 */
const VERSION = 'me-v2';
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/favicon-64.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

/** Fichiers statiques sûrs à garder : icônes, polices, script Livewire. */
function isStaticAsset(url) {
    if (url.origin === self.location.origin) {
        // Script Livewire : /livewire/… ou /livewire-xxxx/… selon la version
        return url.pathname.startsWith('/icons/') || /^\/livewire(-[a-z0-9]+)?\/.*\.js$/.test(url.pathname);
    }
    return url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Envois de formulaires et actions Livewire : toujours en direct
    if (request.method !== 'GET') return;

    // Pages : toujours le serveur ; sans réseau, la page « pas de connexion »
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    const url = new URL(request.url);

    // Fichiers statiques : réponse immédiate depuis la mémoire, mise à jour en fond
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(request);
                const network = fetch(request)
                    .then((response) => {
                        if (response && (response.ok || response.type === 'opaque')) {
                            cache.put(request, response.clone());
                        }
                        return response;
                    })
                    .catch(() => cached);
                return cached || network;
            })
        );
    }
    // Tout le reste (exports, données) : comportement normal du navigateur
});
