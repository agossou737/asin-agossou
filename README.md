# Suivi des demandes d'actes — API Laravel

Étude de cas **DEP/ASIN 2026** — Développeur(se) junior(e).

Les usagers déposent en ligne des demandes d'actes administratifs (**acte de naissance**, **casier judiciaire**, **certificat de résidence**) ; un agent les traite ensuite. Ce dépôt contient :

- une **API REST JSON** (Laravel 13, MySQL, identifiants UUID) ;
- une **interface publique** (dépôt et suivi d'une demande) et un **espace administrateur sécurisé** (traitement des demandes), en thème Boron, jQuery, **AJAX + SweetAlert2** ;
- des **notifications par email** ;
- des **tests automatisés** (Pest) des règles de gestion et de la sécurité.

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Installation](#2-installation)
3. [Démarrer l'application](#3-démarrer-lapplication)
4. [Scénario de recette rapide](#4-scénario-de-recette-rapide)
5. [Interface web](#5-interface-web)
6. [API](#6-api)
7. [Emails et PDF](#7-emails-et-pdf)
8. [Rappels automatiques (scheduler)](#8-rappels-automatiques-scheduler)
9. [Sécurité](#9-sécurité)
10. [Configuration (`.env`)](#10-configuration-env)
11. [Tests automatisés](#11-tests-automatisés)
12. [Conception](#12-conception)
13. [État d'avancement](#13-état-davancement)

---

## 1. Prérequis

| Outil | Version |
|---|---|
| PHP | 8.3 ou plus, avec les extensions `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `dom`, `ctype`, `json` (`dom` et `mbstring` sont nécessaires à la génération des PDF) |
| Composer | 2.x |
| MySQL | 8.x (ou MariaDB 10.6+) |

> Les PDF sont générés avec la bibliothèque **dompdf** (`dompdf/dompdf`), installée par `composer install`.
>
> Aucun Node.js / npm n'est nécessaire : les assets du thème sont déjà dans `public/assets`.

## 2. Installation

```bash
# 1. Récupérer le projet puis installer les dépendances
git clone <URL_DU_DEPOT> asin-test
cd asin-test
composer install

# 2. Créer le fichier d'environnement et la clé d'application
cp .env.example .env          # Windows (cmd) : copy .env.example .env
php artisan key:generate
```

**3. Créer la base de données MySQL** (client MySQL, phpMyAdmin, HeidiSQL…) :

```sql
CREATE DATABASE asin_demandes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**4. Renseigner la connexion dans `.env`** (les valeurs par défaut conviennent à un MySQL local sans mot de passe) :

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asin_demandes
DB_USERNAME=root
DB_PASSWORD=
```

**5. Créer les tables, le compte administrateur et des données de démonstration** :

```bash
php artisan migrate
php artisan db:seed
```

`db:seed` crée :

- le **compte administrateur** (`ADMIN_EMAIL` / `ADMIN_PASSWORD` du `.env`). Si `ADMIN_PASSWORD` est vide, un mot de passe aléatoire est **affiché dans la console** : notez-le ;
- six demandes de démonstration pour les NPI `0123456789` et `9876543210`.

> Pour repartir d'une base vide : `php artisan migrate:fresh --seed`.
> Pour créer seulement le compte admin : `php artisan db:seed --class=AdminSeeder`.

## 3. Démarrer l'application

```bash
php artisan serve
```

Puis ouvrir <http://localhost:8000> (redirige vers le formulaire de dépôt).

Avec **Laragon** / Apache : placer le projet dans `www` et pointer le *document root* sur le dossier `public/` (ou utiliser le *virtual host* automatique `http://asin-test.test`).

### Dépannage

| Symptôme | Solution |
|---|---|
| `SQLSTATE[HY000] [1049] Unknown database` | La base n'existe pas : étape 3. |
| `SQLSTATE[HY000] [1045] Access denied` | Mauvais `DB_USERNAME` / `DB_PASSWORD` dans `.env`. |
| `Base table or view not found` | Les migrations n'ont pas été jouées : `php artisan migrate`. |
| `No application encryption key has been specified` | `php artisan key:generate`. |
| Page sans style | Le dossier `public/assets` doit être présent ; vider le cache : `php artisan optimize:clear`. |
| Modification de `.env` sans effet | `php artisan config:clear`. |
| « Une erreur technique est survenue » à l'écran | Le détail est dans `storage/logs/laravel.log` (volontairement jamais affiché à l'utilisateur). |

## 4. Scénario de recette rapide

1. **Déposer** : ouvrir `/demandes/nouvelle`, saisir un NPI (10 chiffres), un type d'acte, un nombre de copies (1 à 5) ; l'email est facultatif. Un message SweetAlert affiche le **numéro de suivi** (ex. `DEM-20261006-7K3QX9`).
2. **Suivre** : ouvrir `/demandes`, saisir le NPI **ou** le numéro de demande.
3. **Traiter** : se connecter sur `/admin/connexion` (voir [§5](#accès-administrateur)), ouvrir *Les demandes*, cliquer sur **Prendre en charge** → la page de détails s'ouvre → **Valider** ou **Rejeter** (motif obligatoire) → retour à la liste.
4. **Vérifier** : retourner sur `/demandes` : le nouveau statut (et le motif en cas de rejet) est visible ; les emails sont dans `storage/logs/laravel.log`.

> ⚠️ Une demande **non rejetée** du même type bloque un nouveau dépôt pour le même NPI : pour rejouer le scénario, utiliser un autre NPI ou un autre type d'acte, rejeter la demande existante, ou lancer `php artisan migrate:fresh --seed`.

## 5. Interface web

### Pages publiques (aucun lien vers l'espace admin)

| Page | URL | Rôle |
|---|---|---|
| Déposer une demande | `/demandes/nouvelle` | Formulaire NPI / email (facultatif) / type d'acte / copies ; validation côté client puis serveur. |
| Suivre ma demande | `/demandes` | Recherche par **NPI** (toutes les demandes, filtre par statut, compteurs, pagination) ou par **numéro de demande** (une demande). Pour une demande **validée**, un bouton **Télécharger** donne le PDF récapitulatif. |

### Accès administrateur

L'espace admin n'est **référencé nulle part** sur le site public : son adresse n'est communiquée que dans ce document.

- **Connexion** : `http://localhost:8000/admin/connexion`
- **Compte** : email `ADMIN_EMAIL` et mot de passe `ADMIN_PASSWORD` du `.env` (créés par `php artisan db:seed`).

| Page | URL | Rôle |
|---|---|---|
| Connexion | `/admin/connexion` | Authentification (AJAX + SweetAlert). |
| Tableau de bord | `/admin` | Compteurs par statut et par type d'acte ; accès direct à chaque statut. |
| Les demandes | `/admin/demandes` | **Boutons d'accès par statut** (Toutes / Déposée / En cours / Validée / Rejetée, avec compteurs), filtres (numéro, NPI, type d'acte), pagination. Le menu latéral propose les mêmes accès. |
| Détails d'une demande | `/admin/demandes/{uuid}` | Informations, **historique** et **traitement** (valider / rejeter) ; bouton **Télécharger le PDF** si la demande est validée. |

**Parcours de traitement**

1. Sur la liste, **Prendre en charge** fait passer la demande de *déposée* à *en cours* puis ouvre sa **page de détails**.
2. Sur cette page, l'administrateur peut encore changer le statut : **valider** ou **rejeter** (le motif est obligatoire).
3. Dès qu'une demande est **validée ou rejetée** (statut définitif), l'administrateur est **ramené sur la liste complète**.

Toutes les validations, erreurs et succès sont affichés avec **SweetAlert2** ; les échanges avec le serveur se font en **AJAX** (jQuery).

## 6. API

Base : `/api` — réponses en JSON. Ajouter `Accept: application/json`.

- **Public** : dépôt, suivi par NPI, suivi par numéro. L'email, s'il existe, est renvoyé **masqué** (`u***@exemple.com`).
- **Administrateur** : le traitement d'une demande exige le jeton `Authorization: Bearer <ADMIN_API_TOKEN>` (valeur dans `.env`).

### Règles de gestion

- Le **NPI** comporte exactement **10 chiffres**.
- Le **type d'acte** est l'un de : `acte_naissance`, `casier_judiciaire`, `certificat_residence`.
- Le **nombre de copies** est compris entre **1 et 5**.
- L'**email** est **facultatif** : s'il est renseigné, l'usager reçoit son numéro de suivi puis les décisions.
- Chaque demande reçoit un **identifiant UUID** (`id`) et un **numéro de suivi** unique (`numero`, ex. `DEM-20261006-7K3QX9`). L'usager suit sa demande avec ce numéro **ou** avec son NPI.
- **Pas de doublon** : tant qu'une demande de ce type existe pour ce NPI à l'état *déposée*, *en cours* ou *validée*, une nouvelle demande est refusée (`409`, message clair). **Après un rejet, l'usager peut redéposer** une demande du même type (nouveau numéro de suivi ; l'ancienne demande rejetée est conservée).
- **Cycle de vie** : `deposee` → `en_cours` → `validee` **ou** `rejetee`. Une demande validée ou rejetée ne change plus.
- Un **rejet** doit toujours avoir un **motif**.
- Une saisie invalide (`422`) ou une action interdite (`409`) est refusée avec un message clair.

### Endpoints

| Méthode | URL | Accès | Description |
|---|---|---|---|
| `POST` | `/api/demandes` | public | Déposer une demande. |
| `GET` | `/api/usagers/{npi}/demandes` | public | Demandes d'un usager, de la plus récente à la plus ancienne (filtre `statut`, pagination, compteurs). |
| `GET` | `/api/suivi/{numero}` | public | Suivi d'une demande par son numéro. |
| `GET` | `/api/suivi/{numero}/pdf` | public | Télécharger le **PDF** d'une demande **validée** (`409` sinon). |
| `PATCH` | `/api/demandes/{uuid}/statut` | **jeton Bearer** | Faire avancer le traitement. |
| `GET` | `/api/demandes/{uuid}` | **jeton Bearer** | Détail d'une demande + historique. |

L'interface administrateur utilise ses propres routes JSON sous `/admin/api/*` (session + CSRF) ; elles ne sont pas destinées à un usage externe.

#### `POST /api/demandes` — déposer

```bash
curl -X POST http://localhost:8000/api/demandes \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"npi":"0123456789","email":"usager@exemple.com","type_acte":"acte_naissance","nombre_copies":2}'
```

Réponse **201** (`email` est facultatif dans la requête ; `null` dans la réponse s'il est absent) :

```json
{
  "data": {
    "id": "0199f3a2-7c1e-7b52-9d41-2f6a8c3e5b10",
    "numero": "DEM-20261006-7K3QX9",
    "npi": "0123456789",
    "email": "u***@exemple.com",
    "type_acte": { "code": "acte_naissance", "libelle": "Acte de naissance" },
    "nombre_copies": 2,
    "statut": { "code": "deposee", "libelle": "Déposée", "couleur": "secondary" },
    "motif_rejet": null,
    "transitions_possibles": ["en_cours"],
    "created_at": "2026-10-06T10:15:00+01:00",
    "updated_at": "2026-10-06T10:15:00+01:00"
  },
  "message": "Votre demande a été déposée avec succès."
}
```

Conserver l'`id` (UUID) : il sert à traiter la demande via `PATCH`.

#### `GET /api/usagers/{npi}/demandes` — suivre par NPI

Paramètres facultatifs : `statut` (`deposee`, `en_cours`, `validee`, `rejetee`), `page`, `per_page` (1 à 20, **20 par défaut**).

```bash
curl "http://localhost:8000/api/usagers/0123456789/demandes?statut=en_cours&page=1" -H "Accept: application/json"
```

Réponse **200** : `data` (liste, plus récente d'abord), `links` et `meta` (pagination), et `compteurs` (**nombre de demandes par statut**, indépendamment du filtre) :

```json
{ "compteurs": { "deposee": 2, "en_cours": 1, "validee": 0, "rejetee": 3 } }
```

#### `GET /api/suivi/{numero}` — suivre par numéro de demande

```bash
curl http://localhost:8000/api/suivi/DEM-20261006-7K3QX9 -H "Accept: application/json"
```

Réponse **200** : la demande (`data`). `422` si le format du numéro est invalide, `404` si aucune demande ne correspond.

#### `PATCH /api/demandes/{uuid}/statut` — traiter (jeton Bearer requis)

Sans jeton valide : `401`. Valeur du jeton de démonstration : `ADMIN_API_TOKEN` dans `.env` (`asin-demo-token-a-changer` dans `.env.example`).

```bash
# Prendre en charge
curl -X PATCH http://localhost:8000/api/demandes/<UUID>/statut \
  -H "Authorization: Bearer asin-demo-token-a-changer" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"statut":"en_cours"}'

# Valider
curl -X PATCH http://localhost:8000/api/demandes/<UUID>/statut \
  -H "Authorization: Bearer asin-demo-token-a-changer" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"statut":"validee"}'

# Rejeter (motif obligatoire)
curl -X PATCH http://localhost:8000/api/demandes/<UUID>/statut \
  -H "Authorization: Bearer asin-demo-token-a-changer" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"statut":"rejetee","motif_rejet":"Pièces justificatives illisibles."}'
```

### Codes de réponse

| Code | Cas |
|---|---|
| `200` / `201` | Succès. |
| `401` | Authentification requise (jeton ou session admin absent ou invalide). |
| `403` | Compte connecté mais non administrateur. |
| `404` | Demande ou route inexistante. |
| `409` | Doublon (même NPI et même type d'acte) ou action interdite par le cycle de vie (ex. valider une demande seulement « déposée », modifier une demande déjà validée ou rejetée). |
| `422` | Saisie invalide (NPI, email, type, copies, statut, motif manquant…). |
| `429` | Trop de requêtes (limitation de débit). |
| `500` | Erreur technique inattendue : message générique, détail dans `storage/logs/laravel.log`. |

Exemple d'erreur `422` :

```json
{
  "message": "Le NPI doit comporter exactement 10 chiffres.",
  "errors": { "npi": ["Le NPI doit comporter exactement 10 chiffres."] }
}
```

Exemple d'erreur `409` (cycle de vie) :

```json
{ "message": "Transition interdite : une demande « déposée » ne peut pas passer à « validée ». Statut autorisé : « en cours de traitement »." }
```

Exemple d'erreur `409` (doublon) :

```json
{ "message": "Une demande de « acte de naissance » est déjà en cours pour ce NPI (demande DEM-20261006-7K3QX9, statut : déposée). Vous pourrez en déposer une nouvelle si elle est rejetée." }
```

## 7. Emails et PDF

| Événement | Destinataire | Contenu |
|---|---|---|
| Dépôt d'une demande | Usager (si email renseigné) | Accusé de réception avec le **numéro de suivi**. |
| Dépôt d'une demande | Administrateur (`ADMIN_EMAIL`) | « Nouvelle demande à traiter ». |
| Demande validée | Usager (si email renseigné) | Notification de validation **avec le PDF récapitulatif en pièce jointe**. |
| Demande rejetée | Usager (si email renseigné) | Notification de rejet **avec le motif**. |
| 8h00 et 16h00 chaque jour | Administrateurs | [Rappel des demandes à traiter](#8-rappels-automatiques-scheduler). |

Par défaut `MAIL_MAILER=log` : aucun serveur SMTP n'est requis, **les emails sont écrits dans `storage/logs/laravel.log`** (la pièce jointe y apparaît encodée). Pour un envoi réel : `MAIL_MAILER=smtp` et variables `MAIL_*` dans `.env`. Un échec d'envoi est journalisé mais **n'annule jamais** la demande.

### PDF d'une demande validée

Le PDF (généré avec **dompdf**) contient : numéro de suivi, NPI, type d'acte, nombre de copies, statut, dates de dépôt et de validation, et l'historique du traitement. Il est :

- **joint à l'email de validation** (si la génération échoue, l'email part quand même sans pièce jointe et l'erreur est journalisée) ;
- **téléchargeable par l'usager** depuis la page `/demandes` (bouton **Télécharger**, visible seulement pour une demande validée) ou via `GET /api/suivi/{numero}/pdf` ;
- **téléchargeable par l'administrateur** depuis la page de détails de la demande.

```bash
curl -OJ http://localhost:8000/api/suivi/DEM-20261006-7K3QX9/pdf
```

## 8. Rappels automatiques (scheduler)

La commande `php artisan demandes:rappel-admin` envoie aux **administrateurs** (`ADMIN_EMAIL` + tous les comptes administrateurs) un email listant les demandes **en attente** (déposées) et **en cours** de traitement, de la plus ancienne à la plus récente (50 lignes au maximum, avec un lien vers chaque demande). Si aucune demande n'est à traiter, aucun email n'est envoyé.

Elle est planifiée **chaque jour à 8h00 et à 16h00** (fuseau `APP_TIMEZONE`, `Africa/Porto-Novo` par défaut) dans `routes/console.php`.

```bash
php artisan demandes:rappel-admin   # exécution manuelle (test)
php artisan schedule:list           # vérifier la planification
```

**Activer le scheduler** : une seule entrée cron, exécutée chaque minute, lance les tâches au bon moment.

```cron
* * * * * cd /chemin/vers/asin-test && php artisan schedule:run >> /dev/null 2>&1
```

- **Développement local** : `php artisan schedule:work` (laisser le terminal ouvert).
- **Windows / Laragon** : planificateur de tâches Windows, toutes les minutes : `php.exe C:\chemin\asin-test\artisan schedule:run`.

## 9. Sécurité

| Mesure | Détail |
|---|---|
| Authentification admin | Session Laravel (chiffrée), mots de passe hachés, identifiant de session régénéré à la connexion, session invalidée à la déconnexion. |
| Contrôle d'accès | `is_admin` vérifié par middleware sur tout `/admin` et `/admin/api` (`401` si non connecté, `403` si non admin) ; `is_admin` n'est jamais modifiable par mass-assignment. |
| CSRF | Jeton CSRF sur toutes les requêtes AJAX et formulaires de l'espace admin. |
| API de traitement | Jeton Bearer comparé en temps constant (`hash_equals`) ; désactivée si `ADMIN_API_TOKEN` est vide. |
| Force brute / spam | Connexion : 5 essais/min par email+IP ; dépôt : 10/min ; consultation : 30/min (contre l'énumération de NPI). |
| Message de connexion unique | « Email ou mot de passe incorrect » : on ne révèle jamais si un compte existe. |
| Confidentialité | Email masqué dans les réponses publiques ; aucun lien vers l'admin côté public ; pages admin `noindex` et sans cache. |
| Identifiants | UUID (v7) pour les demandes, l'historique et les utilisateurs : non devinables, pas d'énumération par numéros séquentiels. |
| Erreurs | **Jamais de détail technique côté utilisateur** (SQL, chemins, exceptions) : message générique, détail dans `storage/logs/laravel.log`, même avec `APP_DEBUG=true`. |
| Concurrence | Transactions avec `lockForUpdate` au dépôt (doublon) et au changement de statut (pas de double traitement). |
| En-têtes | CSP, `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS en HTTPS. |
| Injection / XSS | Requêtes via Eloquent (paramétrées), sorties Blade échappées, rendu JavaScript échappé. |
| Audit | Table `demande_historiques` : qui (admin / API / usager), quand, ancien et nouveau statut, motif. |
| Production | `APP_DEBUG=false`, HTTPS, `SESSION_SECURE_COOKIE=true`, **changer `ADMIN_PASSWORD` et `ADMIN_API_TOKEN`**. |

## 10. Configuration (`.env`)

| Variable | Rôle | Défaut (`.env.example`) |
|---|---|---|
| `DB_*` | Connexion MySQL | base `asin_demandes`, utilisateur `root` |
| `APP_DEBUG` | Mode debug (jamais de détail technique côté utilisateur, même à `true`) | `false` |
| `APP_LOCALE`, `APP_TIMEZONE` | Langue et fuseau | `fr`, `Africa/Porto-Novo` |
| `ADMIN_EMAIL` | Compte administrateur **et** destinataire des alertes « nouvelle demande » et des rappels | `admin@asin.bj` |
| `ADMIN_PASSWORD` | Mot de passe du compte admin créé par le seeder (vide = aléatoire, affiché) | vide |
| `ADMIN_API_TOKEN` | Jeton Bearer de l'API de traitement (vide = API désactivée) | `asin-demo-token-a-changer` |
| `MAIL_MAILER` + `MAIL_*` | Envoi des emails | `log` |
| `SESSION_LIFETIME` | Durée de session (minutes) | `60` |

## 11. Tests automatisés

```bash
php artisan test
```

Les tests (Pest) couvrent :

- **règles de gestion** : NPI, type d'acte, copies, email facultatif, numéro de demande, doublons, tri, filtre, pagination, compteurs, cycle de vie complet, états finaux, motif de rejet obligatoire ;
- **emails** : accusé de réception, alerte admin, validation (avec PDF joint), rejet, échec d'envoi sans impact ;
- **PDF** : génération, téléchargement public (validée uniquement) et admin, protection des routes ;
- **rappels** : commande `demandes:rappel-admin`, destinataires, absence d'envoi à vide, planification 8h00 / 16h00 ;
- **sécurité** : accès admin (`401`/`403`), connexion, limitation de débit, jeton API, absence de lien admin côté public, absence de fuite d'erreurs techniques, UUID.

Ils utilisent une base **SQLite en mémoire** (voir `phpunit.xml`) : ils ne touchent pas à la base MySQL (extension `pdo_sqlite`, activée par défaut avec PHP).

## 12. Conception

```
app/
├── Enums/                 TypeActe, StatutDemande (transitions autorisées, états finaux)
├── Console/Commands/      RappelDemandesAdmin (demandes:rappel-admin, planifiée à 8h et 16h)
├── Exceptions/            TransitionInterditeException, DemandeDejaExistanteException, DocumentIndisponibleException (409), ErreurTraitementException (500)
├── Http/
│   ├── Controllers/
│   │   ├── Api/           DemandeActeController (API publique + API à jeton)
│   │   └── Admin/         AuthController, DashboardController, DemandeController (JSON), DemandePageController (détails)
│   ├── Middleware/        EnsureUserIsAdmin, VerifierTokenAdminApi, SecurityHeaders
│   ├── Requests/          DeposerDemandeRequest, ListerDemandesRequest, SuiviDemandeRequest, ChangerStatutRequest, AdminListerDemandesRequest
│   └── Resources/         DemandeActeResource (public, email masqué), AdminDemandeActeResource (complet + historique)
├── Mail/                  5 emails (accusé de réception, alerte admin, validée + PDF, rejetée, rappel admin)
├── Models/                DemandeActe, DemandeHistorique, User
└── Services/              DemandeService (dépôt, suivi, liste, statut), NotificationDemandeService (emails, rappels), PdfDemandeService (dompdf)
bootstrap/app.php          Middleware et filet global : aucune erreur technique n'est renvoyée à l'utilisateur
database/                  migrations (UUID), factories, seeders (AdminSeeder, démo)
resources/views/           layouts (app, auth, partials), demandes/*, admin/*, emails/*, pdf/*, errors/*
public/assets/js/          demandes-common.js (AJAX + SweetAlert), pages publiques, pages admin
tests/                     Feature/DemandeActeTest.php, AdminSecuriteTest.php, PdfEtRappelTest.php ; Unit/StatutDemandeTest.php
```

Choix principaux :

- **Table `demandes_actes`** : `id` UUID, `numero` unique, `npi` CHAR(10), `email` facultatif, `type_acte`, `nombre_copies`, `statut`, `motif_rejet`, horodatages ; index `(npi, statut, created_at)` pour la consultation par usager, le filtre par statut et le tri.
- **Enums PHP** : les règles du cycle de vie sont définies une seule fois (`StatutDemande::transitionsPossibles()`) et réutilisées par l'API **et** par l'interface (boutons proposés).
- **Validation dans des FormRequest**, logique métier dans un **service**, contrôleurs minces.
- **Transactions + `lockForUpdate`** au dépôt et au changement de statut ; `try/catch` sur les erreurs base de données (journalisées, message propre) et sur chaque envoi d'email.
- Tri stable : `created_at` décroissant, puis `id` décroissant.

## 13. État d'avancement

### Réalisé

- **Socle** : dépôt (identifiant + statut « déposée »), consultation triée avec filtre facultatif par statut, avancement du cycle de vie.
- **Toutes les règles de gestion** : NPI, type, copies, cycle de vie, états finaux, rejet motivé, messages clairs.
- **Bonus** : pagination (20 max), nombre de demandes par statut, tests automatisés, écran de liste des demandes d'un usager.
- **Au-delà du sujet** : espace administrateur sécurisé (connexion, tableau de bord, accès par statut, détails, historique), API de traitement protégée par jeton, numéro de suivi, emails de notification, **PDF joint et téléchargeable pour les demandes validées**, **rappels automatiques à 8h00 et 16h00**, refus des doublons, UUID, aucune erreur technique affichée à l'utilisateur.

### Non réalisé / limites (et pourquoi)

- **Tables techniques de Laravel** (`jobs`, `cache`) : elles gardent leurs identifiants par défaut ; toutes les tables métier (demandes, historique, utilisateurs) utilisent des UUID.
- **Un seul rôle** (« administrateur ») et pas de création de comptes depuis l'interface : le compte est créé par le seeder. Suffisant pour le périmètre ; à étendre en production.
- **Pas de vérification que le NPI existe** dans un référentiel national : aucune donnée n'est fournie, seul le format est contrôlé.
- **Le suivi public ne demande que le NPI ou le numéro de demande** : il n'existe pas de compte usager. Aucune donnée sensible n'est exposée (email masqué).
- **Redépôt après une demande validée** : refusé (l'acte a déjà été accordé) ; seul un rejet permet de redéposer.
- **Le scheduler dépend du cron du serveur** : sans l'entrée `schedule:run` (ou `schedule:work` en local), les rappels de 8h00 et 16h00 ne partent pas ; la commande reste utilisable à la main.
- **Le PDF est un récapitulatif** (pas un acte officiel signé ni authentifié par QR code / signature électronique).
- **Le jeton API de démonstration** est volontairement documenté pour permettre la recette : à remplacer en production.
