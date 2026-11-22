/*
 * Maquis Express : carnet de secours (ventes sans internet)
 *
 * - Le catalogue (produits, prix) est copié dans le téléphone quand internet marche.
 * - Une vente de secours est rangée dans le téléphone avec un numéro unique.
 * - Dès qu'internet revient, les ventes partent au serveur. Un envoi répété
 *   n'enregistre jamais deux fois la même vente (le serveur reconnaît le numéro).
 *
 * Chaque compte a son propre carnet (maquis + utilisateur) : deux personnes
 * sur le même téléphone ne mélangent pas leurs ventes.
 */
(function () {
    if (window.MEOffline) return;

    var P = 'me-offline:v1:';

    function read(k) {
        try { var v = localStorage.getItem(P + k); return v ? JSON.parse(v) : null; } catch (e) { return null; }
    }
    function write(k, v) {
        try { localStorage.setItem(P + k, JSON.stringify(v)); return true; } catch (e) { return false; }
    }
    function drop(k) {
        try { localStorage.removeItem(P + k); } catch (e) {}
    }
    function emit(name, detail) {
        try { document.dispatchEvent(new CustomEvent(name, { detail: detail || {} })); } catch (e) {}
    }

    var syncing = null;

    var ME = {
        /** Compte connecté sur ce téléphone : { r: maquis, u: utilisateur, name, canSell } */
        current: function () { return read('current'); },
        setCurrent: function (c) { if (c) write('current', c); else drop('current'); },
        canSell: function () { var c = ME.current(); return !!(c && c.canSell); },

        key: function (what) {
            var c = ME.current();
            return c ? what + ':' + c.r + ':' + c.u : null;
        },

        catalog: function () { var k = ME.key('catalog'); return k ? read(k) : null; },
        queue: function () { var k = ME.key('queue'); return (k && read(k)) || []; },
        saveQueue: function (q) { var k = ME.key('queue'); return k ? write(k, q) : false; },
        sent: function () { var k = ME.key('sent'); return (k && read(k)) || []; },

        /** Ventes encore à envoyer (y compris celles refusées, à vérifier). */
        pending: function () { return ME.queue().length; },

        uuid: function () {
            if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
            var b = new Uint8Array(16);
            crypto.getRandomValues(b);
            b[6] = (b[6] & 0x0f) | 0x40;
            b[8] = (b[8] & 0x3f) | 0x80;
            var h = Array.prototype.map.call(b, function (x) { return ('0' + x.toString(16)).slice(-2); }).join('');
            return h.slice(0, 8) + '-' + h.slice(8, 12) + '-' + h.slice(12, 16) + '-' + h.slice(16, 20) + '-' + h.slice(20);
        },

        /** Copie à jour des produits et des prix (internet nécessaire). */
        refreshCatalog: function () {
            var c = ME.current();
            if (!c || !c.canSell) return Promise.resolve(null);

            return fetch('/hors-ligne/catalogue', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
                .then(function (r) {
                    var type = r.headers.get('content-type') || '';
                    return r.ok && type.indexOf('json') !== -1 ? r.json() : null;
                })
                .then(function (d) {
                    if (!d || !d.restaurant || d.restaurant.id !== c.r || !d.user || d.user.id !== c.u) return null;
                    d.fetched_at = Date.now();
                    write(ME.key('catalog'), d);
                    emit('me-offline:catalog', d);
                    return d;
                })
                .catch(function () { return null; });
        },

        /** Garde la page de secours dans le téléphone (le service worker la met de côté). */
        warmPage: function () {
            var last = read('page-warm') || 0;
            if (Date.now() - last < 10 * 60 * 1000) return;
            fetch('/secours', { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { if (r.ok) write('page-warm', Date.now()); })
                .catch(function () {});
        },

        /** Range une vente dans le carnet du téléphone. */
        addSale: function (sale) {
            var q = ME.queue();
            q.push(sale);
            var ok = ME.saveQueue(q);
            emit('me-offline:queue', { pending: q.length });
            return ok;
        },

        /** Retire une vente refusée par le serveur, après vérification par le comptoir. */
        removeSale: function (uuid) {
            var q = ME.queue().filter(function (s) { return s.uuid !== uuid; });
            ME.saveQueue(q);
            emit('me-offline:queue', { pending: q.length });
        },

        /** Envoie le carnet au serveur. Sans danger si appelé plusieurs fois. */
        sync: function (retried) {
            if (syncing) return syncing;
            var toSend = ME.queue().filter(function (s) { return !s.rejected; }).slice(0, 50);
            if (!toSend.length || navigator.onLine === false) return Promise.resolve({ saved: 0 });

            var cat = ME.catalog() || {};
            var token = ME.token || cat.csrf || '';
            var body = JSON.stringify({ sales: toSend.map(function (s) {
                return { uuid: s.uuid, sold_at: s.sold_at, cash_received: s.cash_received, total: s.total, items: s.items };
            }) });

            emit('me-offline:syncing', { count: toSend.length });

            syncing = fetch('/hors-ligne/ventes', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            }).then(function (r) {
                syncing = null;

                // Jeton de sécurité périmé : on en reprend un et on réessaie une fois
                if (r.status === 419 && !retried) {
                    ME.token = null;
                    return ME.refreshCatalog().then(function (d) {
                        if (d) ME.token = d.csrf;
                        return ME.sync(true);
                    });
                }

                var type = r.headers.get('content-type') || '';
                if (!r.ok || type.indexOf('json') === -1) {
                    var res = { error: r.status === 401 || r.redirected ? 'login' : 'server' };
                    emit('me-offline:synced', res);
                    return res;
                }

                return r.json().then(function (data) {
                    var byId = {};
                    (data.results || []).forEach(function (x) { byId[x.uuid] = x; });

                    var saved = [], waitMsg = null, rejected = 0;
                    // On relit le carnet : une vente a pu être ajoutée pendant l'envoi
                    var q = ME.queue().filter(function (s) {
                        var x = byId[s.uuid];
                        if (!x) return true;
                        if (x.status === 'saved' || x.status === 'duplicate') {
                            saved.push({ uuid: s.uuid, total: s.total, sold_at: s.sold_at, order_number: x.order_number || '', sent_at: new Date().toISOString() });
                            return false;
                        }
                        if (x.status === 'wait') { waitMsg = x.message; return true; }
                        if (x.status === 'rejected') { s.rejected = x.message; rejected++; }
                        return true;
                    });
                    ME.saveQueue(q);

                    if (saved.length) {
                        var k = ME.key('sent');
                        if (k) write(k, saved.concat(ME.sent()).slice(0, 30));
                    }

                    var res = { saved: saved.length, total: saved.reduce(function (a, s) { return a + s.total; }, 0), wait: waitMsg, rejected: rejected, pending: q.length };
                    emit('me-offline:synced', res);
                    emit('me-offline:queue', { pending: q.length });

                    // Il reste des ventes (plus de 50 d'un coup) : on continue
                    if (saved.length && q.filter(function (s) { return !s.rejected; }).length && !waitMsg) {
                        setTimeout(function () { ME.sync(); }, 500);
                    }
                    return res;
                });
            }).catch(function () {
                syncing = null;
                return { error: 'network' };
            });

            return syncing;
        }
    };

    window.MEOffline = ME;

    window.addEventListener('online', function () { setTimeout(function () { ME.sync(); }, 1500); });
})();
