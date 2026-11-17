# Maquis Express

Logiciel de gestion en ligne (SaaS) pour les **maquis et restaurants en Côte d'Ivoire** : prise de commande, encaissement, caisse, dépenses et tableau de bord du patron.

- **Web (desktop / tablette)** : Laravel + Livewire, pour la caisse, le gérant et le patron.
- **Mobile (prévu)** : application Flutter, pour les serveuses et le patron, branchée sur une API Laravel.
- **Multi-établissements** : chaque maquis a ses propres données (`restaurant_id`), ses utilisateurs et, à terme, son logo et ses couleurs.

---

## Stack

| Couche | Technologie |
|---|---|
| Backend | PHP 8.3+ (8.4 dans Docker), Laravel 13 |
| Interface web | Livewire 4, Blade, Vite, Tailwind 4 |
| Base de données | MySQL 8.4 (Docker) ou SQLite (développement local) |
| Cache, sessions, files d'attente | Redis (Docker) ou base de données (local) |
| Mobile (prévu) | Flutter + API REST `/api/v1` (Sanctum) |

---

## Fonctionnalités actuelles

- **Comptes** : inscription d'un établissement (14 jours d'essai), connexion par numéro de téléphone.
- **Rôles** : `owner`, `manager`, `cashier`, `server`, plus un Super Admin de la plateforme.
- **Tables et zones** : plan des tables avec leur statut (libre, occupée, en attente de paiement).
- **Commandes** : sur place ou à emporter, notes par article, envoi en cuisine, contrôle du stock.
- **Encaissement** en espèces, avec calcul de la monnaie à rendre.
- **Caisse** : ouverture, solde théorique détaillé, clôture avec écart (une justification est demandée au-delà de 500 FCFA), historique.
- **Dépenses** par catégorie, rattachées à la session de caisse ouverte.
- **Ventes du jour.**
- **Tableau de bord** : chiffre d'affaires, commandes, dépenses, bénéfice estimé, tables occupées, CA des 7 derniers jours, alertes de stock bas.
- **Gestion** des utilisateurs et des produits/catégories par le patron ou le gérant.
- **Super Admin** : liste des restaurants, suspension et réactivation.
- **Crédit client (« ardoise »)** : modèles `Customer` et `CreditTransaction` en place, interface à faire.

---

## Installation

### Option A : Docker (recommandé, proche de la production)

Prérequis : Docker Desktop.

```bash
cp .env.docker.example .env.docker
# Remplir APP_KEY (php artisan key:generate --show) et changer les mots de passe

docker compose up -d --build
docker compose exec app php artisan db:seed --class=DemoSeeder --force   # données de démo (optionnel)
```

L'application est disponible sur **http://localhost:8080**. Le port se change avec `APP_PORT`.

| Conteneur | Rôle |
|---|---|
| `web` | Nginx, sert les fichiers statiques et transmet le reste à PHP |
| `app` | PHP-FPM 8.4 avec Laravel. Lance `migrate --force` au démarrage |
| `queue` | `queue:work` (notifications, rapports) |
| `scheduler` | `schedule:work` (tâches planifiées) |
| `mysql` | MySQL 8.4, volume `mysql-data` |
| `redis` | Redis 7.4, volume `redis-data` |

Commandes utiles :

```bash
docker compose up -d --build            # reconstruire après une modification du code
docker compose logs -f app              # logs
docker compose exec app php artisan ... # commande artisan
docker compose down                     # arrêter (les volumes sont conservés)
```

> Les images Docker sont faites pour la production : le code est copié dans l'image, il faut donc relancer `--build` après chaque modification. Pour développer au quotidien, utilise l'option B.

Fichiers Docker : [`Dockerfile`](Dockerfile), [`docker-compose.yml`](docker-compose.yml), [`docker/`](docker/) (configuration Nginx, PHP et script de démarrage).

### Option B : développement local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed --class=DemoSeeder
composer dev        # serveur Laravel + Vite
```

### Lancer les tests

Les tests tournent dans Docker, dans une base SQLite en mémoire :

```bash
docker compose run --rm tests
```

La première fois, si l'autoload est périmé : `docker compose run --rm tests composer dump-autoload`.

> **Règle d'isolation :** tout nouveau modèle qui a un `restaurant_id` doit utiliser le trait `BelongsToRestaurant`, et tout nouvel écran doit avoir son test A/B dans `RestaurantIsolationTest`. Une règle de validation `exists` ou `unique` ne passe pas par ce filtre : il faut la limiter au restaurant à la main.

### Créer le compte Super Admin (propriétaire de la plateforme)

```bash
docker compose exec app php artisan app:create-super-admin "+225 07 XX XX XX XX" --first=Prénom --last=Nom
# Le mot de passe (12 caractères minimum) est demandé de façon masquée
```

Le Super Admin :
- arrive sur **/admin/restaurants**, la liste de tous les maquis ;
- clique sur **Entrer** pour ouvrir un maquis et voir ou gérer toutes ses données. Un bandeau « Mode Super Admin » rappelle dans quel maquis il se trouve, avec un bouton **Quitter ce maquis** ;
- **verrouille** un maquis qui n'a pas payé, ou le **réactive**. Le personnel d'un maquis verrouillé voit la page « Accès suspendu ». Les données sont conservées, et le Super Admin garde l'accès ;
- chacune de ces actions est enregistrée dans les logs.

Un maquis en essai dont la date `trial_ends_at` est dépassée est verrouillé automatiquement.

### Comptes de démo (`DemoSeeder`)

Restaurant : **Maquis Chez Koffi**. Mot de passe pour tous les comptes : `password`.

| Rôle | Téléphone |
|---|---|
| Patron (owner) | +225 07 11 11 11 11 |
| Serveur | +225 07 22 22 22 22 |
| Caissier | +225 07 33 33 33 33 |

---

## Organisation du code

```
app/
├── Http/Middleware/      EnsureRole (role:owner,manager…), EnsurePlatformAdmin
├── Livewire/             un composant par écran (NewOrder, TakePayment, CashRegisterManage…)
│   └── Admin/            écrans du Super Admin
└── Models/               Restaurant, User, Zone, RestaurantTable, Category, Product,
                          Order, OrderItem, Payment, CashRegister, Expense,
                          Customer, CreditTransaction
resources/views/
├── components/layouts/   app (sidebar), guest, marketing
└── livewire/             vues des composants
docker/                   configuration Nginx, PHP, entrypoint
```

> ⚠️ Le dossier s'appelle bien **`app/Livewire`**, avec un L majuscule. Le serveur Linux distingue majuscules et minuscules.

---

## Problèmes connus (à corriger en priorité)

Ces problèmes ont été relevés lors de la revue de code et **ne sont pas encore corrigés**.

**Sécurité**
- [x] **Isolation entre restaurants** : traits `BelongsToRestaurant` et `BelongsToRestaurantThroughOrder` (dans `app/Models/Concerns`), qui filtrent automatiquement chaque requête par restaurant. `#[Locked]` sur les IDs sensibles des composants, règle `exists` limitée au restaurant. Couvert par `tests/Feature/RestaurantIsolationTest.php`.
- [x] La `quantity` de `NewOrder` est validée (de 1 à 100), ainsi que les montants (reçu, fonds de caisse, montant compté).
- [x] Une commande ne peut plus être payée deux fois (commande verrouillée et vérification du statut et des paiements existants).
- [x] Comptes désactivés et maquis verrouillés : connexion refusée si `status != active`, déconnexion immédiate pendant la session, maquis suspendu ou en fin d'essai bloqué (middleware `restaurant.access`).
- [ ] Pas de limite de tentatives de connexion.
- [x] Middlewares `role`, `restaurant.access` et `platform_admin` réappliqués aux actions Livewire (`Livewire::addPersistentMiddleware` dans `AppServiceProvider`). ⚠️ Non couvert par les tests automatiques (Livewire ne réapplique pas ces middlewares en test) : à vérifier à la main. Il manque aussi les Policies par action.
- [x] La colonne `is_platform_admin` a été ajoutée (non assignable en masse).

**Fiabilité**
- [x] Transactions et verrous (`lockForUpdate`) sur l'ajout et le retrait d'article, le paiement, ainsi que l'ouverture et la clôture de caisse. Les refus métier passent par `App\Exceptions\BusinessRuleException`, dont le message est affiché à l'utilisateur.
- [ ] L'inscription (`Register`) n'est pas transactionnelle. Ce sera traité lors de son remplacement par la création de maquis côté Super Admin.
- [x] `removeItem` remet le stock à jour. Une commande à l'encaissement ou payée ne peut plus être modifiée.
- [x] Numéro de commande dérivé de l'ID (`CMD-00042`) : aucun doublon possible.
- [x] Une seule caisse ouverte à la fois, et une caisse ne se clôture qu'une fois.
- [x] `customer_id` ajouté au `$fillable` de `Order`.
- [x] Le bas de la sidebar affiche le maquis courant (et non plus « Maquis Chez Koffi » en dur).
- [ ] `DatabaseSeeder` crée un utilisateur avec une colonne `name` qui n'existe plus, donc `php artisan db:seed` échoue. Utiliser `--class=DemoSeeder`.

**Qualité**
- [ ] Supprimer `ActingUserSwitcher` (solution temporaire) et `app/Livewire/ash-register-manage.blade.php` (ancien fichier).
- [ ] Remplacer les chaînes de statut par des Enums PHP, utiliser `#[Computed]` au lieu de `getXProperty()`, optimiser les requêtes du Dashboard.
- [ ] Remplacer les styles en ligne par des composants Blade (`<x-card>`, `<x-button>`…).
- [ ] Écrire des tests : isolation entre restaurants, paiement, solde de caisse, stock.

---

## Décisions produit

Ces choix ont été validés et servent de référence pour le développement.

### Ouverture des comptes : gérée par la plateforme
Pas d'inscription libre.
1. Le **Super Admin** crée l'espace du maquis (nom, téléphone, commune, formule, fin d'essai) et le compte du **patron**.
2. Le patron reçoit ses accès (mot de passe provisoire ou lien par SMS/WhatsApp) et doit **changer son mot de passe à la première connexion**.
3. Le patron crée son personnel selon ses besoins : personne, une seule gérante, ou une équipe complète. Seul le patron crée des gérants. Un gérant ne crée que des serveurs et des caissiers.

À faire : remplacer `/register` par « Demander une démo », ajouter « Créer un maquis + son patron » dans l'espace Super Admin, forcer le changement de mot de passe.

### Un seul modèle pour tous les maquis, du petit au grand
L'appli ne repose pas sur des profils ni sur de nombreux réglages : chaque réglage ajoute des combinaisons à tester, donc des risques d'erreur sur la caisse. **Le fonctionnement s'adapte au personnel créé.**

**Le modèle « sacoche » :**
- Chaque personne qui encaisse a sa propre **sacoche** dans l'appli. L'argent qu'elle encaisse y entre.
- Quand elle remet l'argent à la caisse ou à la gérante, c'est un **versement** enregistré.
- **Caisse attendue** = fonds de départ + ventes encaissées directement + versements reçus + remboursements d'ardoise − dépenses.
- **Petit maquis** (patron + gérante) : une seule sacoche, celle de la gérante, aucun versement.
- **Grand maquis** : une sacoche par serveuse, et l'écran montre qui doit encore reverser combien.

**Seulement 2 choix par maquis :**
- **Vente rapide (comptoir)** et/ou **service à table** ;
- **Ardoise** activée ou non.

Le reste se déduit automatiquement : pas de compte cuisine, pas de ticket cuisine ; pas de serveuses, pas d'écran de versement ; pas de tables, vente au comptoir. C'est la plateforme qui configure le maquis à l'installation.

**Rôles :** une gérante de petit maquis peut tout faire, sauf ce qui est réservé au patron (bénéfice, marges et, au choix du patron, les prix). Le patron peut travailler seul.

**Formules envisagées :** Basic (2 à 3 utilisateurs) · Pro (jusqu'à 8, Mobile Money, rapport WhatsApp) · Premium (illimité, cuisine, plusieurs services, plusieurs établissements). Un maquis qui grandit change de formule sans rien perdre.

### Tableau de bord de la gérante
- **Accueil** : ventes du jour, montant encaissé (par mode de paiement), ardoises en cours, état de la caisse, plus des alertes (table ouverte depuis longtemps, stock bas, ardoise ancienne, versement en attente).
- **Ventes** : jour (par serveuse, par mode de paiement), semaine (jour par jour comparé à la semaine précédente, heures de pointe, top produits, produits qui ne se vendent pas), mois.
- **Ardoise** : liste des clients qui doivent (montant, ancienneté), remboursement partiel, détail des commandes, relance WhatsApp en un clic, plafond par client fixé par le patron. Une vente à crédit compte dans le chiffre d'affaires mais n'entre en caisse qu'au remboursement.
- **Tirer la caisse** : récupérer les versements des serveuses → billetage (comptage par coupure) → vérification du Mobile Money → résumé et écart (justification obligatoire au-delà du seuil) → fonds laissé pour le lendemain → **résumé envoyé au patron sur WhatsApp**.
- **Réservé au patron** : bénéfice, marges, historique de toutes les caisses, annulation d'une vente encaissée (ou code du patron).

### Principes de fiabilité
1. **Un seul calcul central** pour l'argent (solde de caisse, sacoches), utilisé par le web, l'API et les rapports.
2. **Rien ne s'efface** : une vente encaissée s'annule avec une trace, une caisse clôturée ne se modifie plus.
3. **Tout ou rien** : chaque opération d'argent se fait dans une transaction.
4. **Le serveur a le dernier mot** : il recalcule prix, totaux et stock, et vérifie les droits. Le téléphone ne fait que proposer.
5. **Des tests automatiques** pour chaque scénario d'argent (petit et grand maquis, ardoise, versements).
6. **Des sauvegardes testées** chaque mois.
7. **Des pilotes** : un petit et un grand maquis pendant un mois, avec comparaison chaque soir entre la caisse de l'appli et l'argent réel.

### Sécurité de l'application mobile (Flutter)
- **Appareil autorisé + PIN** : le patron ou le gérant enregistre l'appareil, puis le personnel s'y connecte avec un PIN à 4 chiffres.
- Un jeton Sanctum par appareil, **révocable** par le patron (appareil perdu ou volé, employé parti).
- Jeton rangé dans le stockage sécurisé du téléphone, **base locale chiffrée** et limitée aux données du jour, effacée à la révocation.
- **Hors ligne** : commande, envoi au bar et encaissement en espèces autorisés. Annulation, remise, changement de prix et clôture interdits. À la synchronisation, le serveur revalide tout, chaque opération porte un identifiant unique et apparaît dans le journal d'audit.
- HTTPS uniquement (avec épinglage du certificat avant les clients payants), aucun secret dans l'appli, obfuscation, mise à jour forcée possible.

---

## Feuille de route

### 1. Consolider la base
Corriger les problèmes ci-dessus. Sortir la logique métier des composants Livewire vers des **Actions** (`app/Actions/...`), pour que Livewire et l'API Flutter utilisent le même code.

### 2. API et application mobile Flutter
- API REST `/api/v1` avec Sanctum.
- **Mode Serveur** : prise de commande à la table, **hors ligne d'abord** (SQLite local, UUID générés sur le téléphone, clé d'idempotence), impression Bluetooth ESC/POS (58 mm).
- **Mode Patron** : chiffre d'affaires en direct, alertes, plusieurs établissements.
- Temps réel (tables, commandes envoyées au bar ou en cuisine) avec Laravel Reverb.

### 3. Paiements
- Mobile Money : Wave, Orange Money, MTN MoMo, Moov. D'abord en saisie manuelle de la référence, puis via un agrégateur (CinetPay ou PayDunya).
- Paiement mixte (espèces + Mobile Money).
- Crédit client (ardoise) avec remboursements partiels et rappels.
- Versement des serveuses au caissier en fin de service, avances sur salaire.

### 4. Abonnement SaaS et suspension
- Tables `plans`, `subscriptions` et `subscription_payments`. Paiement par Mobile Money avec un webhook signé et sans doublon.
- Parcours : essai de 14 jours → rappels à J-3 et J-1 → **5 jours de grâce** → suspension.
- Une fois suspendu, le compte passe en **lecture seule** : on ne coupe jamais en plein service et aucune donnée n'est supprimée.
- Commande quotidienne `subscriptions:check`, et un middleware appliqué au web comme à l'API.

### 5. Personnalisation par maquis (white-label)
- Logo (png, jpg ou webp uniquement, **pas de SVG**), nom, slogan.
- Couleur principale et couleur d'accent, avec les nuances calculées automatiquement (`Restaurant::themeVariables()`) et injectées dans les variables CSS du layout. Couleurs validées par regex.
- Sidebar construite depuis `config/menu.php`, filtrée par rôle, modules activés et formule.
- Endpoint `/api/v1/branding` pour le thème Flutter. Logo imprimé sur les tickets.

### 6. Spécificités des maquis
- Stock en **casiers et bouteilles**, gestion des consignes et emballages vides, fournisseurs et prix d'achat pour calculer la marge.
- Vente au poids ou à prix libre (poulet, poisson braisé) avec un plafond fixé par le gérant, accompagnements.
- Tickets séparés pour le bar et la cuisine, prix par zone (terrasse, VIP), happy hour.
- **Anti-vol** : journal d'audit, code gérant pour annuler un article, rapport WhatsApp quotidien au patron, alertes d'écart de caisse.
- Inventaire du soir, pertes et offerts, pointage du personnel.

### 7. Design
Création d'un système de design (couleurs, typographie, composants) et de maquettes des écrans clés : connexion, tables, commande, encaissement, tableau de bord, personnalisation. Contraintes : fort contraste pour l'usage en terrasse, mode sombre, gros boutons, Android d'entrée de gamme.

### Avant la mise en production
- [ ] Mots de passe forts dans `.env.docker`, `APP_DEBUG=false`
- [ ] HTTPS (Caddy ou Traefik devant Nginx)
- [ ] Sauvegarde quotidienne de MySQL
