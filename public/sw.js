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
const VERSION = 'me-v4';
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = '/offline.html';
// Vente de secours : la page est gardée pour marcher sans internet (elle ne contient
// aucune donnée du maquis : produits et ventes sont dans le téléphone de l'utilisateur)
const RESCUE_URL = '/secours';

// Internet très faible : au-delà de ce délai, on arrête d'attendre le serveur
const PAGE_TIMEOUT = 12000;
const RESCUE_TIMEOUT = 5000;

/** Abandonne l'attente si le serveur ne répond pas à temps. */
function withTimeout(promise, ms) {
    return new Promise((resolve, reject) => {
        const timer = setTimeout(() => reject(new Error('timeout')), ms);
        promise.then((r) => { clearTimeout(timer); resolve(r); }, (e) => { clearTimeout(timer); reject(e); });
    });
}

/** Si même la page « pas de connexion » manque : un message simple plutôt que l'erreur du navigateur. */
function lastResort() {
    return new Response(
        '<!DOCTYPE html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        + '<title>Pas de connexion</title><body style="font-family:sans-serif;padding:32px;text-align:center">'
        + '<h1>Pas de connexion internet</h1><p>Vérifiez internet puis réessayez.</p>'
        + '<button onclick="location.reload()" style="padding:14px 24px;font-size:16px">Réessayer</button></body>',
        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
}

const PRECACHE = [
    OFFLINE_URL,
    '/js/offline-sales.js',
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
        // La page de secours déjà gardée passe dans la nouvelle version : le
        // téléphone reste prêt sans devoir être repréparé après une mise à jour
        caches.match(RESCUE_URL)
            .then((page) => page && caches.open(STATIC_CACHE).then((cache) => cache.match(RESCUE_URL).then((mine) => mine || cache.put(RESCUE_URL, page))))
            .catch(() => {})
            .then(() => caches.keys())
            .then((keys) => Promise.all(keys.filter((k) => !k.startsWith(VERSION)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

/** Fichiers statiques sûrs à garder : icônes, polices, script Livewire. */
function isStaticAsset(url) {
    if (url.origin === self.location.origin) {
        // Script Livewire : /livewire/… ou /livewire-xxxx/… selon la version
        return url.pathname.startsWith('/icons/') || url.pathname.startsWith('/js/') || /^\/livewire(-[a-z0-9]+)?\/.*\.js$/.test(url.pathname);
    }
    return url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Envois de formulaires et actions Livewire : toujours en direct
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Page de secours : le serveur d'abord (copie mise de côté), sinon la copie
    if (url.origin === self.location.origin && url.pathname === RESCUE_URL) {
        event.respondWith(
            withTimeout(fetch(request), RESCUE_TIMEOUT)
                .then((response) => {
                    if (response.ok && !response.redirected) {
                        const copy = response.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(RESCUE_URL, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(RESCUE_URL).then((r) => r || caches.match(OFFLINE_URL)))
        );
        return;
    }

    // Pages : toujours le serveur ; sans réseau (ou réseau qui ne répond pas),
    // la page « pas de connexion », qui mène à la vente de secours.
    // Les exports (fichiers longs à préparer) ne sont jamais coupés.
    if (request.mode === 'navigate') {
        const slowAllowed = url.pathname.startsWith('/exports');
        const network = slowAllowed ? fetch(request) : withTimeout(fetch(request), PAGE_TIMEOUT);
        event.respondWith(network.catch(() => caches.match(OFFLINE_URL).then((r) => r || lastResort())));
        return;
    }

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
