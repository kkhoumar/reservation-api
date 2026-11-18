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
docker compose exec app php artisan app:create-super-admin "07 XX XX XX XX"   # ton compte Super Admin
```

> Les données de démo (`db:seed`) sont **refusées quand `APP_ENV=production`**, car leurs comptes utilisent le mot de passe `password`. Pour une démo dans Docker, mets `APP_ENV=local` dans `.env.docker`, puis lance `docker compose exec app php artisan db:seed --force`.

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
php artisan db:seed  # maquis de démo (relançable sans doublon)
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

Restaurant : **Maquis Chez Koffi** (tables et vente rapide, ardoise activée, essai de 30 jours). Mot de passe pour tous les comptes : `password`.

| Rôle | Prénom | Téléphone |
|---|---|---|
| Patron (owner) | Koffi | 0711111111 |
| Gérante (manager) | Mariam | 0744444444 |
| Serveur | Aya | 0722222222 |
| Caissier | Jean | 0733333333 |

Les numéros sont enregistrés au format `0711223344`. À la connexion, `07 11 22 33 44` ou `+225 07 11 22 33 44` fonctionnent aussi.

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
- [x] Connexion limitée à 5 essais par numéro et par IP, puis blocage de 15 minutes.
- [x] Middlewares `role`, `restaurant.access` et `platform_admin` réappliqués aux actions Livewire (`Livewire::addPersistentMiddleware` dans `AppServiceProvider`). ⚠️ Non couvert par les tests automatiques (Livewire ne réapplique pas ces middlewares en test) : à vérifier à la main.
- [x] `UserPolicy` : une gérante gère seulement les caissiers et les serveurs, seul le propriétaire gère les gérants, et personne d'autre que lui (ou le Super Admin) ne modifie le propriétaire.
- [x] Dépenses : catégorie et montant validés.
- [x] La colonne `is_platform_admin` a été ajoutée (non assignable en masse).

**Fiabilité**
- [x] Transactions et verrous (`lockForUpdate`) sur l'ajout et le retrait d'article, le paiement, ainsi que l'ouverture et la clôture de caisse. Les refus métier passent par `App\Exceptions\BusinessRuleException`, dont le message est affiché à l'utilisateur.
- [x] L'inscription libre (`Register`) a été supprimée. La création « maquis + propriétaire » (`App\Actions\CreateRestaurantWithOwner`) se fait dans une transaction.
- [x] `removeItem` remet le stock à jour. Une commande à l'encaissement ou payée ne peut plus être modifiée.
- [x] Numéro de commande dérivé de l'ID (`CMD-00042`) : aucun doublon possible.
- [x] Une seule caisse ouverte à la fois, et une caisse ne se clôture qu'une fois.
- [x] `customer_id` ajouté au `$fillable` de `Order`.
- [x] Le bas de la sidebar affiche le maquis courant (et non plus « Maquis Chez Koffi » en dur).
- [x] `php artisan db:seed` lance `DemoSeeder` : relançable sans doublon, refusé en production.

**Qualité**
- [x] `ActingUserSwitcher` (solution temporaire) et `ash-register-manage.blade.php` (ancien fichier) supprimés.
- [ ] Remplacer les chaînes de statut par des Enums PHP, utiliser `#[Computed]` au lieu de `getXProperty()`, optimiser les requêtes du Dashboard.
- [ ] Remplacer les styles en ligne par des composants Blade (`<x-card>`, `<x-button>`…).
- [x] Tests : isolation entre restaurants, Super Admin, paiement, caisse, stock, connexion, droits, création de maquis, demandes de démo, réglages, données de démo, sacoches, comptoir, ardoise, rapport du soir, Mobile Money, gestes commerciaux, historique des dépenses, écran caisse, tableau de bord et petit maquis à une personne (219 tests).

---

## Décisions produit

Ces choix ont été validés et servent de référence pour le développement.

### Ouverture des comptes : gérée par la plateforme
Pas d'inscription libre.
1. Le **Super Admin** crée l'espace du maquis (nom, téléphone, commune, formule, fin d'essai) et le compte du **patron**.
2. Le patron reçoit ses accès (mot de passe provisoire ou lien par SMS/WhatsApp) et doit **changer son mot de passe à la première connexion**.
3. Le patron crée son personnel selon ses besoins : personne, une seule gérante, ou une équipe complète. Seul le patron crée des gérants. Un gérant ne crée que des serveurs et des caissiers.

Mis en place :
- **`/demande-demo`** : le prospect laisse son nom, son maquis, son numéro et sa commune. Protections : champ piège anti-robot, 3 demandes par heure et par IP, pas de doublon pour un même numéro. `/register` redirige vers cette page.
- **Super Admin → Établissements** : les demandes en attente s'affichent en haut (appel, WhatsApp, « Contacté », « Sans suite »). « Créer le maquis » ouvre le formulaire pré-rempli.
- **Super Admin → + Nouveau maquis** : le maquis et le patron sont créés ensemble, avec un mot de passe provisoire facile à dicter (`Kp7m-Wx3r`) et un message WhatsApp prêt à envoyer.
- **Première connexion** : l'écran `/mot-de-passe` est obligatoire tant que le mot de passe provisoire n'est pas changé. La même règle s'applique au personnel dont le mot de passe a été fixé par le patron ou la gérante.

### Un seul modèle pour tous les maquis, du petit au grand
L'appli ne repose pas sur des profils ni sur de nombreux réglages : chaque réglage ajoute des combinaisons à tester, donc des risques d'erreur sur la caisse. **Le fonctionnement s'adapte au personnel créé.**

**Le modèle « sacoche » :**
- Chaque personne qui encaisse a sa propre **sacoche** dans l'appli. L'argent qu'elle encaisse y entre.
- Quand elle remet l'argent à la caisse ou à la gérante, c'est un **versement** enregistré.
- **Caisse attendue** = fonds de départ + ventes encaissées directement + versements reçus + remboursements d'ardoise − dépenses.
- **Petit maquis** (patron + gérante) : une seule sacoche, celle de la gérante, aucun versement.
- **Grand maquis** : une sacoche par serveuse, et l'écran montre qui doit encore reverser combien.

Mis en place :
- Un paiement encaissé par une **serveuse** est marqué `payments.in_sacoche`. Ce marqueur est figé au moment du paiement : changer son rôle plus tard ne réécrit pas l'historique. L'argent n'entre pas dans la caisse.
- **Sacoches** (`/sacoches`, réservé au patron, à la gérante et à la caissière) : ce que chaque serveuse a encaissé, versé et a encore sur elle. Le bouton « Recevoir » enregistre un versement (`cash_remittances`), complet ou partiel. Le versement est rattaché à la caisse ouverte.
- **Refus** : un versement supérieur au contenu de la sacoche, un montant nul, une personne qui n'est pas serveuse de ce maquis, ou une caisse fermée.
- **Caisse** : le détail affiche « + Versements des serveuses », et la clôture rappelle les **sacoches non vidées**.
- La serveuse voit **« Ma sacoche : X FCFA »** en haut de l'écran.
- Le solde d'une sacoche se reporte d'un jour à l'autre : ce qui n'est pas versé reste dû.
- Petit maquis : tant que personne n'a le rôle de serveuse, rien ne change. Tout va directement en caisse.
- La sacoche ne concerne que le **paiement à la fin**. En paiement avant service, la serveuse n'encaisse pas : le menu Sacoches est masqué.

### Paiement avant service : le bon de comptoir (par défaut)
C'est l'habitude des maquis : la serveuse apporte l'argent au comptoir, puis on sert. **Pas d'argent, pas de boisson.**
1. **La serveuse** prend la tournée et clique sur **« Envoyer au comptoir »**. Le bon passe en `awaiting_payment` et la table reste occupée.
2. **Le comptoir** (`/comptoir`, réservé au patron, à la gérante et à la caissière) voit les bons arriver, rafraîchis toutes les 5 secondes : table, serveuse, articles, total.
3. **« Encaisser »**, puis on saisit le montant reçu et la monnaie s'affiche. **« Encaissé, servir »** fait entrer l'argent directement en caisse.
4. **Chaque tournée est un nouveau bon** sur la même table.
5. **Quand les clients partent**, « Libérer » remet la table à libre. C'est refusé tant qu'un bon n'est pas réglé.
6. **Annuler le bon** (le client renonce) remet les articles en stock. Un bon payé ne s'annule pas.
7. **« Apporté aujourd'hui »** : le nombre de bons et le montant apporté par chaque serveuse.

Protections :
- **La serveuse ne peut pas encaisser**, ni par le bouton ni par l'adresse de la page.
- **Un bon ne s'encaisse qu'une fois**, même si deux personnes cliquent en même temps.
- **On ne change pas de mode** tant que des bons attendent au comptoir.

Le patron, la gérante et la caissière peuvent aussi **« Encaisser tout de suite »** une vente qu'ils prennent eux-mêmes.

**Le stock** est réservé dès que l'article est ajouté au bon, ce qui évite de vendre deux fois la dernière bouteille. Il est rendu si le bon est annulé.

**Seulement 3 choix par maquis :**
- **Vente rapide (comptoir)** et/ou **service à table** ;
- **Paiement avant service** (par défaut) ou **à la fin** (`restaurants.payment_timing`) ;
- **Ardoise** activée ou non.

Le reste se déduit automatiquement : pas de compte cuisine, pas de ticket cuisine ; pas de serveuses, pas d'écran de versement ; pas de tables, vente au comptoir. C'est la plateforme qui configure le maquis à l'installation.

Mis en place : colonnes `restaurants.service_mode` (`tables`, `quick`, `both` ; `both` par défaut) et `restaurants.credit_enabled`.
- Choix à la création par le Super Admin, puis modifiables par le **propriétaire** dans **Paramètres** (`/parametres`).
- En mode `quick` : le plan des tables redirige vers la vente rapide, et le menu affiche « Vente rapide ». En mode `tables` : la vente sans table est refusée.
- On ne peut pas passer en `quick` tant qu'une commande de table n'est pas payée.
- On ne peut pas désactiver l'ardoise tant que des clients doivent de l'argent.

### Moyens de paiement : espèces, Mobile Money, mixte
À l'encaissement (au comptoir ou sur l'écran d'encaissement), on choisit **Espèces**, **Wave**, **Orange Money**, **MTN MoMo** ou **Moov Money**.
- **Mobile Money** : le montant est pré-rempli avec le total et la référence de la transaction est facultative.
- **Paiement mixte** : si le montant Mobile Money est inférieur au total, l'écran demande les espèces pour le reste et calcule la monnaie.

Il y a **un seul paiement par bon**, ce qui évite de le compter deux fois :
- `payment_method` vaut l'opérateur, ou `cash` ;
- `mobile_amount` est la part payée en Mobile Money ;
- `amount_received` et `change_given` concernent les espèces.

**Seule la part en espèces entre dans la caisse** ou dans la sacoche. Le Mobile Money s'affiche à part dans le détail de la caisse (« pas dans le tiroir »), par opérateur, et doit être vérifié sur les téléphones du maquis.

**Les ventes** (`App\Support\SalesSummary`, utilisé par le Tableau de bord, les Ventes du jour et le rapport du soir) comptent tout ce qui a été réglé :
- les espèces ;
- le Mobile Money, par opérateur ;
- l'ardoise.

Les Ventes du jour listent aussi les bons mis sur l'ardoise, avec le nom du client.

### Petit maquis : une seule personne fait tout
L'écran s'adapte au personnel créé, sans aucun réglage (`Restaurant::hasActiveServers()`).
- **Celui qui prend la commande a le droit d'encaisser** (patron, gérante, caissière) : le gros bouton est **« Encaisser »**.
- **Aucune serveuse active** : pas de menu *Comptoir* ni *Sacoches*, ni de bouton « Envoyer au comptoir ». Le Comptoir reste visible tant qu'un bon y attend.
- **Au moins une serveuse active** : le Comptoir réapparaît tout seul. La serveuse ne voit que « Envoyer au comptoir ». Les autres gardent « Encaisser » en gros et « Envoyer au comptoir » en petit.
- **Après un paiement** : « **Nouvelle tournée — Table X** » ou « **Nouvelle vente** », pour enchaîner sans repasser par le plan des tables.

Pour une employée seule, on lui donne le rôle **Gérante** (de confiance) ou **Caissière** (sans crédit ni offert), jamais **Serveuse**.

### Tableau de bord (patron et gérante)
- **En ce moment** : la caisse (montant attendu dans le tiroir, qui l'a ouverte), les bons qui attendent au comptoir, les tables occupées, les crédits à récupérer, les sacoches non versées. Chaque case mène à l'écran concerné.
- **Sur la période** (aujourd'hui, cette semaine, ce mois) :
  - le chiffre d'affaires, avec espèces, Mobile Money et crédit, **comparé à la même durée de la période précédente** (« ▲ +50 % par rapport à hier à la même heure ») ;
  - le nombre de bons réglés et le panier moyen ;
  - les dépenses, avec les 3 plus grosses catégories ;
  - **« Reste après dépenses »** : l'argent rentré (espèces + Mobile Money) moins les dépenses. Il remplace l'ancien « bénéfice estimé », qui était trompeur car il ne tenait pas compte du prix d'achat ;
  - le graphique des 7 derniers jours, **qui a vendu**, **ce qui se vend le plus** ;
  - **À surveiller** : offerts et remises (qui, pourquoi), bons annulés, écarts de caisse.
- Calcul commun avec le rapport du soir : `App\Support\PeriodStats` et `App\Support\SalesSummary`.

### Écran Caisse
- En haut : **« Argent qui doit être dans le tiroir maintenant »**, qui a ouvert la caisse et depuis quand, avec des raccourcis Comptoir, + Dépense et Clôturer.
- **Chiffres de la session** : ventes, espèces, Mobile Money (sur les téléphones), vendu à crédit (pas encore payé), dépenses.
- **« Comment on arrive à ce montant »** : chaque ligne du calcul expliquée en clair.
- **« Ce qui n'est PAS dans le tiroir »** : Mobile Money par opérateur, crédits, sacoches non versées.
- **Journal de la caisse** (`CashRegister::journal()`) : chaque mouvement avec l'heure, le bon, la table, la serveuse, qui l'a fait et le montant. Ce qui n'est pas passé par le tiroir est marqué « hors tiroir ».
- **Clôture par billetage** : nombre de billets et de pièces (10 000 à 5 F), total calculé automatiquement, écart expliqué (« il manque… » ou « il y a… de trop »). Le détail est conservé dans `cash_registers.cash_count`. On peut aussi saisir seulement le total.
- Caisse fermée : l'écran rappelle le montant compté à la dernière clôture.

### Dépenses : saisie et historique
- **Saisie** : catégorie, montant et description. La dépense est rattachée à la caisse ouverte, donc déduite du solde théorique. Si aucune caisse n'est ouverte, elle est marquée « hors caisse ».
- **Historique** (patron et gérante) : aujourd'hui, 7 derniers jours, ce mois, mois dernier ou dates choisies, avec un filtre par catégorie. On voit le total, le total par catégorie, et la liste groupée par jour avec qui a saisi chaque dépense (30 par page).
- **Caissière** : elle saisit et ne voit que les dépenses du jour.

### Gestes commerciaux : offert et remise
Le bouton **« Offrir / remise »** est au comptoir, et sur l'écran d'encaissement en paiement à la fin. Il est réservé au **patron et à la gérante**.
- **Offrir un article**, en entier ou en partie (1 bière sur 5) : la ligne est coupée en deux et la partie offerte passe à 0 F (`order_items.is_offered`). On garde **qui** l'a offert et **pourquoi**. Des motifs rapides sont proposés : bon client, tournée du patron, erreur de service, fidélité.
- **Remise** en FCFA sur le bon (`orders.discount_amount`), avec un motif. Elle ne peut pas dépasser le bon, et on peut la retirer.
- **Le stock ne bouge pas** : l'article a bien été consommé.
- **Un bon entièrement offert** se valide à 0 F. Un bon vide reste refusé.
- **Refus** : la caissière, la serveuse, un motif vide, un bon déjà réglé ou annulé.
- **Rapport du soir** : la rubrique « *Offerts et remises* » donne le total et le détail de chaque geste (qui, motif, table). Le reçu affiche les articles offerts et la remise.

Les bons **annulés** ne servent plus qu'aux clients partis sans consommer. Dans le rapport, ils restent un signal à surveiller.

### Rapport du soir
Dès la clôture de la caisse, l'écran « Caisse clôturée » affiche le rapport de la session (`App\Support\RegisterReport`). On le retrouve ensuite dans **Historique caisse → Rapport**.

Contenu du rapport :
- les **ventes** : ce qui a été encaissé, plus ce qui a été mis sur l'ardoise, avec le nombre de bons ;
- les dépenses et les remboursements d'ardoise ;
- la **caisse** : le montant attendu, le montant compté, l'écart (✅ ou ⚠️) et la note de clôture ;
- **par serveuse** : le nombre de bons et le montant ;
- les **3 meilleures ventes** ;
- **À surveiller** : les bons annulés, les sacoches non vidées et le total dû sur l'ardoise.

Pour le partager :
- **« Envoyer au patron sur WhatsApp »** ouvre WhatsApp avec le texte prêt, adressé au numéro du patron. Le bouton est masqué si c'est le patron lui-même qui clôture.
- On peut aussi **partager** le texte vers n'importe quel contact, ou le **copier**.

L'envoi demande un clic, et il est gratuit. Pour un envoi **automatique**, il faudra l'API WhatsApp Business, qui est payante et demande une validation par Meta. C'est prévu à l'étape 4.

### Crédit client (appelé « ardoise » dans le code)
À l'écran, on dit toujours **« crédit »** : menu **Crédits**, boutons **« À crédit »**, rapport « À crédit » et « Crédits clients à récupérer ». Dans le code, la page garde le nom `Ardoise` et les tables `customers` et `credit_transactions`. L'ancienne adresse `/ardoise` redirige vers `/credits`.
Visible seulement si `credit_enabled` est activé.

**Mettre sur l'ardoise : patron et gérante uniquement.**
- Le bouton **« Sur l'ardoise »** est au comptoir, et aussi sur l'écran d'encaissement en paiement à la fin.
- Le bon est réglé sans argent en caisse : un mouvement `charge` est enregistré, avec la personne qui a autorisé le crédit.
- **Plafond facultatif** par client : au-delà, le crédit est refusé.
- **Refus** : la caissière, un bon déjà réglé ou annulé, un client d'un autre maquis.

**Écran Crédits** (`/credits`, réservé au patron, à la gérante et à la caissière) :
- le total dû au maquis et les clients triés par dette, avec une recherche ;
- la fiche du client avec son historique daté : le détail de chaque consommation (articles, table, serveuse, qui a autorisé le crédit) et chaque remboursement (montant, qui l'a encaissé, note) ;
- **« Relancer sur WhatsApp »** avec un message poli déjà rédigé.

**Remboursement** (patron, gérante ou caissière) :
- il ne peut pas dépasser la dette ;
- il est refusé si la caisse est fermée ;
- l'argent entre dans la caisse ouverte (`credit_transactions.cash_register_id`), sur la ligne « + Remboursements d'ardoise ».

**Clients** : seuls le patron et la gérante ajoutent un client et fixent son plafond. Le numéro est normalisé, sans doublon dans un même maquis.

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
- [x] Mobile Money (Wave, Orange Money, MTN MoMo, Moov) en saisie manuelle, avec une référence facultative.
- [x] Paiement mixte (espèces + Mobile Money).
- [ ] Confirmation automatique via un agrégateur (CinetPay ou PayDunya).
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
