# Architecture — Plateforme e-commerce intelligente nasande.cg

> Document de référence (decision log + plan de construction).  
> Lecture obligatoire avant tout commit dans la nouvelle plateforme Laravel.  
> Tout choix marqué **[Décision]** doit être contesté explicitement avant d'être changé.

---

## Sommaire

1. [Résumé exécutif](#1-résumé-exécutif)
2. [Audit factuel des données Drupal](#2-audit-factuel-des-données-drupal)
3. [Décisions stratégiques (avec contre-arguments)](#3-décisions-stratégiques-avec-contre-arguments)
4. [Stratégie de préservation SEO & URL](#4-stratégie-de-préservation-seo--url)
5. [Architecture applicative — modules DDD](#5-architecture-applicative--modules-ddd)
6. [Schéma de base de données](#6-schéma-de-base-de-données)
7. [Pipeline d'enrichissement IA](#7-pipeline-denrichissement-ia)
8. [Intelligence comportementale & capture de demande](#8-intelligence-comportementale--capture-de-demande)
9. [Recherche intelligente](#9-recherche-intelligente)
10. [Observabilité & Business Intelligence](#10-observabilité--business-intelligence)
11. [Accessibilité & Semantic Web](#11-accessibilité--semantic-web)
12. [Roadmap phasée](#12-roadmap-phasée)
13. [Risques & questions ouvertes](#13-risques--questions-ouvertes)

---

## 1. Résumé exécutif

**Cible** : remplacer Drupal Commerce par un système Laravel 12 / PHP 8.3 hébergé sur le même
domaine, qui :

- Préserve à 100 % les URLs existantes (`/25/acm-novophane-lotion-antichute`, etc.).
- Sert du HTML sémantique SSR — pas de SPA hydratée — pour maximiser la lisibilité crawler/LLM.
- Capture toute interaction utilisateur en *event sourcing léger*, projeté vers des tables BI.
- Enrichit le catalogue par un agent IA contrôlé par revue humaine, jamais publié à chaud.
- Détecte la **demande non satisfaite** (recherches vides, requêtes répétées) et la rend actionnable.

**Trois piliers** :

1. **Catalogue rigoureux** — DDD modules, normalisation forte, slugs immuables.
2. **Observation continue** — chaque clic, recherche, abandon est un événement structuré.
3. **Boucle d'apprentissage** — l'IA propose, l'humain valide, le système enrichit le catalogue
   et oriente l'inventaire.

**Le piège à éviter** : empiler la complexité IA avant la stabilité du socle. La V1 doit être
**ennuyeuse, rapide et indexable**. L'intelligence s'ajoute en V2/V3 sur des fondations propres.

---

## 2. Audit factuel des données Drupal

Données issues de l'export (`out/products.json`, 741 produits) :

### 2.1 Structure d'URL

| Pattern                              | Nombre | Exemple                                       |
| ------------------------------------ | ------ | --------------------------------------------- |
| `/{drupal_id}/{slug}`                | 56     | `/25/acm-novophane-lotion-antichute`          |
| `/{drupal_id}/{brand_drupal_id}/{slug}` | 685 | `/38/143/bioderma-atoderm-pp-baume`           |

Les deux patterns **coexistent** dans la base. Le `drupal_id` du produit est toujours le **premier segment**.
C'est **la clé immuable** dont dépend tout l'historique SEO.

Catégories : `/product/{slug}` (47 termes, ex: `/product/hygiene`).  
Marques : `/brand/{slug}` (présent pour 2 marques sur 70 — à reconstruire).  
Origines : `/origine/{slug}` (1 seule : `/origine/congo`).

### 2.2 Catalogue

| Indicateur                              | Valeur |
| --------------------------------------- | ------ |
| Produits                                | 741    |
| Variations                              | 756 (727 × 1, 13 × 2, 1 × 3) |
| Variations avec attribut color/size/volume | 30 sur 756 (4 %) |
| Produits avec marque                    | 635 (86 %) |
| Produits avec ≥ 1 catégorie             | 685 (92 %) |
| Produits avec plusieurs catégories      | 0 |
| Produits actifs                         | 741 (100 %) |
| Produits featured                       | 6 |
| Slugs dupliqués (distingués par drupal_id) | 2 |

### 2.3 Prix & stock

- Devise unique : **XAF** (Franc CFA d'Afrique Centrale, 0 décimale en pratique).
- Plage de prix : 0 → 116 580 XAF (présence de prix à 0 à investiguer).
- Stock : 6 OOS, 161 stock faible (< 5), 517 OK, 72 NULL.

### 2.4 Médias

- 753 références d'images, **671 téléchargées**, **82 fichiers orphelins serveur (404)**.
- 89 produits n'ont aucune image utilisable → priorité IA d'enrichissement.

### 2.5 Implications

| Constat | Implication |
| --- | --- |
| `drupal_id` dans l'URL | Conserver comme colonne `legacy_drupal_id` indexée unique. |
| 96 % des produits sont mono-SKU | Modèle Product/Variant standard, mais UI peut masquer la variation par défaut. |
| Devise et langue uniques | Pas de table `currencies` / `locales` pour le MVP. Préparer mais ne pas implémenter. |
| Origines = 1 seul terme | Supprimer cette taxonomie. Mort-née. |
| 89 produits sans image | Pipeline d'enrichissement IA doit gérer la génération/recherche d'image. |
| 14 % de produits sans marque | Marque = optional en DB. |

---

## 3. Décisions stratégiques (avec contre-arguments)

### 3.1 Blade SSR vs Inertia + Vue → **[Décision] Blade + Alpine.js + HTMX**

**Choix** : Blade pour la totalité du front public. Alpine.js pour micro-interactivité.
HTMX pour les patterns "fragment swap" (panier, filtres, search-as-you-type).

**Pourquoi pas Inertia/Vue** :

- Inertia sérialise les props comme JSON dans l'attribut `data-page` → bruit pour les LLM crawlers
  qui n'exécutent pas JS et qui voient du JSON dans le DOM au lieu de prose sémantique.
- Le projet impose explicitement *"minimal unnecessary JavaScript"* et *"SSR preferred for SEO"*.
- L'audience cible est en **Congo** (bandwidth contraint, devices low-end). Un bundle Vue de
  200 ko TTI = 1.5 s de plus sur un mobile 3G.
- Le contenu est principalement déclaratif (fiches produit, catalogue). Pas d'app SPA-like.

**Trade-off accepté** : transitions de page moins fluides. Compensé par HTMX `hx-boost` qui donne
du SPA-feel avec 14 ko de JS et zéro JSON dans le DOM.

**Quand reconsidérer** : si une zone admin devient très interactive (drag-drop, dashboards
temps réel) — on peut ouvrir une zone Inertia/Vue isolée sous `/admin` sans contaminer le front public.

### 3.2 MySQL vs PostgreSQL → **[Décision] PostgreSQL 16**

- `JSONB` indexable et requêtable (≠ MySQL `JSON` qui est texte indexable seulement partiellement).
- `pg_trgm` pour FTS de secours quand Meilisearch est indisponible.
- `pgvector` (extension officielle) pour stocker les embeddings produits / demande clustering — utile
  pour la phase 3 sans changer de stack.
- Sémaphores avancés (`SELECT FOR UPDATE SKIP LOCKED`) pour queues et stock concurrent.

**Trade-off** : moins répandu chez les hébergeurs PHP low-cost. À provisionner en managed
(Supabase, Neon, ou Postgres sur le VPS si l'on auto-héberge).

### 3.3 Search → **[Décision] Meilisearch en V1, hybrid search reporté à V3**

- Meilisearch v1.10+ a un mode "hybrid search" (BM25 + vecteurs) natif et coûte 0 € en self-host.
- Typesense est aussi excellent mais Meilisearch a un meilleur DX Laravel (driver Scout officiel).
- V1/V2 : BM25 + typo tolerance + dictionnaire de synonymes manuel en admin → suffisant pour 741 produits.
- V3 (semantic) : embeddings via OpenAI `text-embedding-3-small` ou `bge-m3` local
  — décidé au moment de l'activation (cf. §13.3 question ouverte).

### 3.4 Architecture event-driven → **[Décision] Event sourcing léger, pas full ES**

- **Pas** d'event sourcing complet (state = fold(events)) : trop coûteux à raisonner pour ce
  domaine. On garde l'état mutable classique pour `products`, `orders`.
- **Mais** une table `domain_events` append-only où chaque action métier est journalisée, et
  des **projections** asynchrones via Horizon vers des tables agrégées (`product_metrics`,
  `search_metrics`, `demand_signals`).
- Avantages : audit total, rejouabilité partielle, base saine pour la BI sans coupler le code domaine.

### 3.5 Tracking côté serveur vs client → **[Décision] Server-first, augmenté côté client**

- Tracker côté serveur tout ce qui passe par une route (`UserViewedProduct`, `UserSearched`,
  `CartAbandoned`...) → fiable, indépendant des adblockers.
- Côté client : seulement *scroll depth*, *rage clicks*, *time on page* via un script minimal
  (5 ko) qui POST sur `/api/telemetry/event` debounced.
- Tout passe par la même table `behavior_events` (forme normalisée).

### 3.6 IA — où la mettre et où NE PAS la mettre

| Cas | IA ? | Pourquoi |
| --- | --- | --- |
| Enrichissement contenu produit | **Oui, en draft + review** | Catalogue lacunaire = besoin réel |
| Recherche sémantique (intent) | **V3 seulement** | Pas d'embeddings DeepSeek ; report stratégique |
| Génération de réponses FAQ | **Oui, sur revue** | Permet du long-tail SEO |
| Réponse client en chat | **Non en V1** | Risque hallucination ; humain d'abord |
| Génération automatique de prix | **Non, jamais** | Domaine régulé, contexte local |
| Classement automatique en catégorie | **Oui, suggestion humaine** | Pas d'action sans validation |

**Règle d'or** : aucune sortie IA ne touche directement le HTML servi sans étape `human_approved_at`.

### 3.7 LLM provider → **[Décision] DeepSeek primaire, OpenAI optionnel pour embeddings**

**Génération de contenu** : `deepseek-chat` (API compatible OpenAI). Endpoint `https://api.deepseek.com/v1`.

- Coût ≈ $0.27 / M tokens input, $1.10 / M tokens output (cache miss).
- Budget catalogue complet (741 produits × 5k tokens entrée + 1k sortie) ≈ **2 USD one-shot**.
- Compatible drop-in via le client OpenAI PHP avec `base_uri` override.

**Embeddings** : DeepSeek **n'a pas** d'API embeddings publique. Conséquences :

- V1/V2 : pas d'embeddings du tout. La recherche utilise Meilisearch BM25 + synonymes + typo tolerance. Le clustering de demande utilise n-gram + Jaccard.
- V3 : si on veut ajouter du sémantique, deux options :
  - **OpenAI `text-embedding-3-small`** (0.02 USD / M tokens, ~0.10 USD pour tout réindexer une fois). Le plus simple.
  - **Microservice Python** avec `sentence-transformers/all-MiniLM-L6-v2` ou `bge-small-fr`. Gratuit mais nouveau service à opérer.

**Architecture découplée** :

```php
interface LLMClient {
    public function complete(LLMRequest $req): LLMResponse;
}
interface EmbeddingClient {  // optionnelle, NULL en V1/V2
    public function embed(string $text): array;
}
```

Le bon implémentation est injectée via le `LLMServiceProvider` selon la config. On peut basculer
DeepSeek → OpenAI → Anthropic sans toucher au domaine.

**Configuration `.env`** :

```
LLM_PROVIDER=deepseek
DEEPSEEK_API_KEY=sk-...
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL_CHAT=deepseek-chat
DEEPSEEK_MODEL_REASONING=deepseek-reasoner

EMBEDDINGS_PROVIDER=none   # ou 'openai', 'local'
OPENAI_API_KEY=
```

### 3.8 URL des nouveaux produits → **[Décision] Pattern `/produits/{slug}`**

Les anciens produits gardent leur URL Drupal (`/25/acm-novophane-...`, `/38/143/bioderma-...`).  
Les **nouveaux** produits (uploadés via le backoffice) reçoivent une URL au pattern :

```
/produits/{slug}
```

Pourquoi pas `/product/{slug}` ? Conflit avec les catégories Drupal (`/product/hygiene`).  
Pourquoi pas `/{slug}` direct ? Risque de collision avec routes statiques (`/panier`, `/recherche`).  
Pourquoi pas continuer la séquence `drupal_id` ? Le nom devient menteur, on perd la distinction
entre legacy et nouveau, et l'ID est une fuite d'implémentation dans l'URL.

`/produits/{slug}` est clair, en français, SEO-friendly, et non-ambigu. Le slug doit être unique
parmi les produits **non-legacy** (les produits legacy gardent leur path complet).

**Conséquence DB** : la colonne `legacy_path` devient `canonical_path` — pour TOUS les produits
(legacy ou nouveaux). Le champ `legacy_drupal_id` reste NULL pour les nouveaux.

---

## 4. Stratégie de préservation SEO & URL

### 4.1 Conservation à 100 % du legacy

Source de vérité : `out/url-aliases.json` → 741 paths qui doivent répondre **HTTP 200**
au lendemain de la bascule DNS.

#### Routage Laravel

Routes dans `routes/web.php`, ordre **critique** (du plus spécifique au plus générique) :

```php
// 1. Routes nouvelles fonctionnelles — déclarées EN PREMIER
Route::middleware('web')->group(function () {
    Route::get('/panier',     [CartController::class, 'show'])->name('cart');
    Route::post('/recherche', [SearchController::class, 'submit'])->name('search.submit');
    Route::get('/recherche',  [SearchController::class, 'index'])->name('search');
    // ...
});

// 2. Pattern NOUVEAUX produits — préfixe explicite, pas de collision possible
Route::get('/produits/{slug}', NewProductController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('product.canonical');

// 3. Catégories & marques (existaient déjà dans Drupal)
Route::get('/product/{slug}', CategoryController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('category');

Route::get('/brand/{slug}', BrandController::class)
    ->where('slug', '[a-z0-9-]+')
    ->name('brand');

// 4. Routes LEGACY Drupal — catch-all SEO en DERNIER
Route::get('/{drupalId}/{slug}', LegacyProductController::class)
    ->where('drupalId', '[0-9]+')
    ->where('slug', '[a-z0-9-]+')
    ->name('product.legacy.short');

Route::get('/{drupalId}/{brandId}/{slug}', LegacyProductController::class)
    ->where('drupalId', '[0-9]+')
    ->where('brandId', '[0-9]+')
    ->where('slug', '[a-z0-9-]+')
    ->name('product.legacy.full');
```

`LegacyProductController` et `NewProductController` peuvent partager le même `ShowProduct`
use-case ; seule la résolution diffère (par `legacy_drupal_id` vs par `slug`).

`LegacyProductController` résout le produit **uniquement** via `drupal_id` (jamais via slug). Le
slug est vérifié : s'il diverge du slug canonique courant, on **redirige 301** vers la version
canonique. Cela protège contre :

- les changements de slug futurs (typo corrigée → ancien lien continue de marcher),
- les URL polluées par des paramètres ou casses différentes.

#### Table de redirections

Une table `redirects` (source → destination, code 301/302, hits) gérée en admin :

- Pré-remplie à la migration : aucune redirection nécessaire en V1 puisque tout legacy est servi 1:1.
- Utilisée en V2 pour les évolutions de structure (`/brand/avene` → `/marques/avene`).

### 4.2 Meta & structured data

Chaque page produit doit servir :

1. `<title>` ≤ 60 chars, `<meta name="description">` ≤ 160 chars (gérés par un trait `HasSeo`).
2. `<link rel="canonical">` toujours = URL legacy actuelle.
3. **OpenGraph** + **Twitter Card** + **JSON-LD `Product`** avec :
   - `name`, `description`, `sku`, `brand`, `image[]`, `offers.price`, `offers.priceCurrency`,
     `offers.availability` (`InStock`/`OutOfStock`/`LimitedAvailability`),
     `aggregateRating` (si V3 avec reviews).
4. JSON-LD `BreadcrumbList` (Accueil → Catégorie → Produit).
5. JSON-LD `Organization` au layout global (`@type: OnlineStore`, `priceRange`, `currenciesAccepted`,
   `address`, `contactPoint`).
6. JSON-LD `FAQPage` si le produit a une section FAQ enrichie.

### 4.3 Sitemap & robots

- `sitemap.xml` (root) dynamique → produits + catégories + marques + pages statiques. Lastmod
  = `updated_at` Carbon de chaque entité.
- `sitemap_index.xml` si > 5 000 URLs (anticipation V2).
- `robots.txt` : autorise tout sauf `/admin`, `/api/internal`, `/panier/...`. Pointe vers le sitemap.

### 4.4 Performance

Objectif **Core Web Vitals "Good"** :

- LCP < 2.5 s mobile (Congo / 4G médian).
- INP < 200 ms.
- CLS < 0.1.

Moyens :

- Images servies en WebP/AVIF responsive via `<picture>` (job de conversion à l'enrichissement).
- HTTP cache `Cache-Control: public, max-age=300, s-maxage=86400` sur fiches produit avec
  `ETag` calculé sur `updated_at`.
- Edge cache via Cloudflare ou Bunny.net (free tier suffisant en V1).
- Critical CSS inline pour la fiche produit (Tailwind purge + `vite-plugin-critical`).
- Pas de WebFonts custom en V1 (system fonts).

---

## 5. Architecture applicative — modules DDD

Pas de packages séparés (over-engineering). On utilise les **dossiers domaine** sous `app/Modules/`.
Chaque module a sa structure interne complète et expose des **events publics** + un **service applicatif**.

```
app/
├── Modules/
│   ├── Catalog/            ← Product, Variant, Brand, Category, Attribute
│   │   ├── Domain/
│   │   │   ├── Models/Product.php
│   │   │   ├── Models/Variant.php
│   │   │   ├── Models/Brand.php
│   │   │   ├── Models/Category.php
│   │   │   ├── Models/Media.php
│   │   │   ├── Services/SlugResolver.php
│   │   │   └── Events/ProductPublished.php
│   │   ├── Application/
│   │   │   ├── ListProducts.php       (query / use case)
│   │   │   ├── ShowProduct.php
│   │   │   └── ImportFromDrupal.php
│   │   ├── Infrastructure/
│   │   │   ├── Eloquent/ProductRepository.php   (si justifié)
│   │   │   └── Persistence/migrations/
│   │   └── Http/
│   │       ├── Controllers/
│   │       └── Resources/
│   ├── Search/             ← Meilisearch driver, indexers, intent detection
│   ├── Behavior/           ← Tracking events, projections, sessions
│   ├── Demand/             ← Capture des recherches sans résultat, signals
│   ├── Enrichment/         ← AI agent, sources, review workflow
│   ├── SEO/                ← Meta generators, sitemaps, redirects, schema.org
│   ├── Commerce/           ← Cart, Order, Payment (V2)
│   └── Observability/      ← Logging, metrics, BI projections
├── Http/
│   ├── Middleware/
│   └── Kernel.php
└── Providers/
    └── ModuleServiceProvider.php   ← bootstrap des routes/migrations de chaque module
```

### 5.1 Règles inter-modules

1. **Pas d'import direct** entre modules : Catalog n'importe **jamais** un namespace de Behavior.
2. La communication passe par :
   - les **événements** (`event(new ProductViewed($id, $sessionId))`),
   - les **services applicatifs publics** d'un module (interface en `Modules/X/Contracts/`).
3. Tests unitaires par module, tests d'intégration aux frontières.

### 5.2 Repository pattern — quand et seulement quand

- **Oui** pour `ProductRepository` : la requête de listing combine FTS + filtres + faceting +
  projection metrics → complexité justifie un repository qui expose `searchPublished(Filters)`.
- **Non** pour `BrandRepository` : un `Brand::query()` Eloquent suffit. Ne pas indirecter inutilement.

### 5.3 Backoffice administrateur — upload de produits

Module **`Modules/Admin`** dédié au CRUD catalogue (et aux dashboards Demand/Enrichment plus tard).

#### Stack admin

- Routes sous le préfixe `/admin`, middleware `auth.admin` + `can:manage-catalog`.
- Layout Blade séparé (`layouts/admin.blade.php`), HTMX pour les interactions (édition inline,
  upload progressif, drag-drop ordering).
- Auth admin via email + password (séparé des futurs comptes clients) + 2FA TOTP.

#### Workflow de création d'un nouveau produit

```
[Admin] /admin/products/create
    │
    ├── Étape 1 — Titre + marque + prix + catégorie(s)
    │   ↓
    │   Le slug est suggéré automatiquement (Str::slug) avec vérif unicité côté
    │   nouveaux produits (origin <> 'drupal_migration'). Admin peut overrider.
    │
    ├── Étape 2 — Description courte + description longue + stock
    │
    ├── Étape 3 — Upload d'images (drag-drop, multi, max 8 par produit)
    │   • Validation : mime image/*, ≤ 5 MB, dim ≥ 600x600 recommandé
    │   • Conversion automatique → WebP + AVIF (job différé)
    │   • Génération de variantes responsive (320, 640, 1024, 1600)
    │   • Stockage : storage/app/public/products/{yyyy}/{mm}/{uuid}.{ext}
    │
    └── Étape 4 — Sauvegarder
        • status = 'draft' par défaut
        • canonical_path = '/produits/' . slug calculé
        • Émet ProductDrafted (event)
        • Propose : "Publier maintenant" / "Demander enrichissement IA d'abord"

[Admin] /admin/products/{id}/edit
    • Mêmes champs, plus la possibilité de :
      - Déclencher un EnrichProductJob (file Horizon)
      - Reorganiser images par drag-drop
      - Marquer une image comme primary
      - Changer status → 'published' (set published_at)
      - Soft delete (status = 'archived')
```

#### Modèle Eloquent — Product

Méthodes clés :

```php
class Product extends Model {
    public function canonicalUrl(): string { /* canonical_path absolutisé */ }
    public function isLegacy(): bool { return $this->origin === 'drupal_migration'; }
    public function publish(): void {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
        event(new ProductPublished($this));
    }
    public function generateSlugIfMissing(): void { /* Str::slug + collision check */ }
    public function generateCanonicalPathIfMissing(): void {
        if (!$this->canonical_path) {
            $this->canonical_path = '/produits/' . $this->slug;
        }
    }
}
```

#### Upload d'images — pipeline

```
HTTP multipart upload  
    ↓ controller stores tmp file
    ↓ insert media row (status='uploaded', is_available=true)
    ↓ dispatch MediaProcessingJob
        - ImageOptimizer (Spatie/image-optimizer)
        - WebP + AVIF generation
        - Responsive variants
        - blurhash / lqip pour placeholder
        - alt text suggéré par IA (DeepSeek vision n/a → utiliser titre produit comme fallback)
    ↓ MediaProcessed event → Product.search_index_dirty=true
```

Sécurité :

- Vérification du **magic bytes** (pas que `mime_type` du header), via `finfo`.
- Renommage systématique en UUID, ne jamais conserver le nom de fichier client.
- Pas d'exécution PHP dans le dossier `storage/products` (config nginx / `.htaccess`).
- Validation dimension max 8192×8192 pour bloquer les pixel bombs.

#### Permissions

Roles minimum (table `roles` + `permissions` via spatie/laravel-permission) :

- `admin` : tout
- `catalog-editor` : CRUD produits, déclencher enrichissement, valider drafts IA
- `catalog-viewer` : read-only

#### Bulk import (V2)

CSV import + dry-run preview, mêmes validations que CRUD individuel. Reporting d'erreurs ligne par ligne.

### 5.4 Events publiés (catalogue minimal V1)

| Module | Event | Quand | Consommateurs |
| --- | --- | --- | --- |
| Catalog | `ProductPublished` | Save admin | Search (réindex), SEO (sitemap), Observability |
| Catalog | `ProductDepublished` | Soft-delete | Search, SEO |
| Search | `SearchPerformed` | Chaque /search | Behavior, Demand |
| Search | `SearchReturnedZeroResults` | Hits = 0 | Demand (capture) |
| Behavior | `ProductViewed` | Hit fiche produit | Observability |
| Behavior | `CartUpdated` | Add/remove cart | Observability, Demand |
| Behavior | `CheckoutAbandoned` | Pas de paiement après N min | Observability, futur Marketing |
| Enrichment | `EnrichmentDraftReady` | Job AI terminé | Notification admin |
| Enrichment | `EnrichmentApproved` | Bouton "Publier" | Catalog (apply) |

Tous matérialisés dans `domain_events` AVANT d'être dispatchés à leurs listeners → audit absolu.

---

## 6. Schéma de base de données

PostgreSQL 16. Notation simplifiée — chaque table a `id BIGSERIAL`, `created_at`, `updated_at`, `deleted_at`.

### 6.1 Catalog

```sql
CREATE TABLE brands (
  id              BIGSERIAL PRIMARY KEY,
  legacy_drupal_id INTEGER UNIQUE,           -- pour matcher dans /{id}/{brand_id}/{slug}
  name            TEXT NOT NULL,
  slug            TEXT NOT NULL UNIQUE,
  description     TEXT,
  logo_media_id   BIGINT REFERENCES media(id),
  meta_title      TEXT,
  meta_description TEXT,
  published_at    TIMESTAMPTZ
);

CREATE TABLE categories (
  id              BIGSERIAL PRIMARY KEY,
  legacy_drupal_id INTEGER UNIQUE,
  parent_id       BIGINT REFERENCES categories(id),  -- prêt pour hiérarchie
  name            TEXT NOT NULL,
  slug            TEXT NOT NULL UNIQUE,
  description     TEXT,
  meta_title      TEXT,
  meta_description TEXT,
  sort_order      INTEGER DEFAULT 0
);

CREATE TABLE products (
  id              BIGSERIAL PRIMARY KEY,
  uuid            UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),

  -- Identifiants legacy : NULL pour les produits créés via backoffice
  legacy_drupal_id INTEGER UNIQUE,
  origin          TEXT NOT NULL CHECK (origin IN ('drupal_migration','backoffice','api_import'))
                  DEFAULT 'backoffice',

  -- URL canonique servie : conservée à l'identique pour les legacy, /produits/{slug} pour les nouveaux
  canonical_path  TEXT NOT NULL UNIQUE,      -- ex: /25/acm-... ou /produits/nouveau-produit
  slug            TEXT NOT NULL,

  brand_id        BIGINT REFERENCES brands(id),
  title           TEXT NOT NULL,
  short_description_html TEXT,
  description_html TEXT,
  features        JSONB,                     -- liste structurée enrichie par IA
  specifications  JSONB,                     -- key/value table technique
  faqs            JSONB,                     -- [{q, a, confidence, source_id}]
  meta_title      TEXT,
  meta_description TEXT,
  status          TEXT CHECK (status IN ('draft','published','archived')) DEFAULT 'draft',
  published_at    TIMESTAMPTZ,
  created_by_user_id BIGINT REFERENCES users(id),  -- NULL pour migration drupal
  search_index_dirty BOOLEAN DEFAULT TRUE,
  ai_enrichment_status TEXT CHECK (ai_enrichment_status IN ('pending','draft','review','approved','rejected')) DEFAULT 'pending',
  ai_confidence   NUMERIC(3,2)
);

CREATE INDEX idx_products_legacy_drupal_id ON products(legacy_drupal_id) WHERE legacy_drupal_id IS NOT NULL;
CREATE INDEX idx_products_status_published ON products(status, published_at);
CREATE INDEX idx_products_slug ON products(slug) WHERE origin <> 'drupal_migration';

-- Unicité du slug parmi les NOUVEAUX produits uniquement (les legacy peuvent avoir des slugs dupliqués)
CREATE UNIQUE INDEX uniq_products_new_slug ON products(slug)
  WHERE origin <> 'drupal_migration';

CREATE TABLE product_categories (
  product_id  BIGINT REFERENCES products(id) ON DELETE CASCADE,
  category_id BIGINT REFERENCES categories(id) ON DELETE CASCADE,
  PRIMARY KEY (product_id, category_id)
);

CREATE TABLE variants (
  id              BIGSERIAL PRIMARY KEY,
  product_id      BIGINT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
  legacy_drupal_id INTEGER UNIQUE,
  sku             TEXT NOT NULL UNIQUE,
  title           TEXT,
  price_minor     BIGINT NOT NULL,           -- XAF en unité mineure (≡ unité ici)
  currency_code   CHAR(3) NOT NULL DEFAULT 'XAF',
  list_price_minor BIGINT,
  stock           INTEGER,                   -- NULL = non tracké
  weight_grams    INTEGER,
  options         JSONB,                     -- {color, size, volume} libre, déjà rare ici
  is_default      BOOLEAN DEFAULT FALSE,
  status          TEXT CHECK (status IN ('active','inactive')) DEFAULT 'active'
);

CREATE TABLE media (
  id              BIGSERIAL PRIMARY KEY,
  legacy_drupal_fid INTEGER UNIQUE,
  disk            TEXT NOT NULL DEFAULT 'public',
  path            TEXT NOT NULL,             -- ex: products/484_novophane.png
  mime_type       TEXT,
  width           INTEGER,
  height          INTEGER,
  bytes           BIGINT,
  alt_text        TEXT,
  is_available    BOOLEAN DEFAULT TRUE       -- false pour les 82 orphelins
);

CREATE TABLE product_media (
  product_id BIGINT REFERENCES products(id) ON DELETE CASCADE,
  media_id   BIGINT REFERENCES media(id) ON DELETE CASCADE,
  position   SMALLINT DEFAULT 0,
  role       TEXT DEFAULT 'gallery',          -- 'primary','gallery','lifestyle','spec'
  PRIMARY KEY (product_id, media_id)
);
```

### 6.2 Redirects

```sql
CREATE TABLE redirects (
  id        BIGSERIAL PRIMARY KEY,
  source    TEXT NOT NULL UNIQUE,            -- ex: /old/path
  target    TEXT NOT NULL,                   -- ex: /new/path
  code      SMALLINT NOT NULL DEFAULT 301,
  hits      BIGINT DEFAULT 0,
  last_hit_at TIMESTAMPTZ
);
```

### 6.3 Behavior / Demand / Search

```sql
CREATE TABLE sessions (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id         BIGINT REFERENCES users(id),
  ip_hash         TEXT,                       -- SHA256(ip + daily_salt) — anonymisation RGPD-friendly
  user_agent_hash TEXT,
  locale          TEXT,
  country         TEXT,
  device          TEXT,
  started_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  last_seen_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  intent_score    NUMERIC(4,3),               -- agrégé en temps réel (proj 8)
  flags           JSONB                        -- {high_intent:true, bot:false, ...}
);

CREATE TABLE behavior_events (
  id              BIGSERIAL PRIMARY KEY,
  session_id      UUID REFERENCES sessions(id),
  user_id         BIGINT REFERENCES users(id),
  type            TEXT NOT NULL,              -- 'product_viewed','search','rage_click',...
  url             TEXT,
  product_id      BIGINT REFERENCES products(id),
  payload         JSONB,
  occurred_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_behavior_session_time ON behavior_events(session_id, occurred_at);
CREATE INDEX idx_behavior_type_time ON behavior_events(type, occurred_at);
CREATE INDEX idx_behavior_payload_gin ON behavior_events USING GIN (payload);

CREATE TABLE search_logs (
  id              BIGSERIAL PRIMARY KEY,
  session_id      UUID REFERENCES sessions(id),
  query           TEXT NOT NULL,
  query_normalized TEXT NOT NULL,             -- lowercase, accents stripped
  filters         JSONB,
  hits_count      INTEGER NOT NULL,
  clicked_product_id BIGINT REFERENCES products(id),  -- NULL si rien cliqué
  duration_ms     INTEGER,
  occurred_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_search_query_norm ON search_logs(query_normalized);
CREATE INDEX idx_search_zero ON search_logs(hits_count) WHERE hits_count = 0;

CREATE TABLE demand_signals (                  -- agrégat journalier
  id              BIGSERIAL PRIMARY KEY,
  keyword         TEXT NOT NULL,
  cluster_id      BIGINT REFERENCES demand_clusters(id),
  frequency_7d    INTEGER DEFAULT 0,
  frequency_30d   INTEGER DEFAULT 0,
  intent_score    NUMERIC(4,3),
  urgency         TEXT CHECK (urgency IN ('low','medium','high','viral')),
  example_queries TEXT[],
  first_seen_at   TIMESTAMPTZ,
  last_seen_at    TIMESTAMPTZ,
  status          TEXT CHECK (status IN ('open','sourced','rejected','converted')) DEFAULT 'open',
  notes           TEXT,
  UNIQUE(keyword)
);

CREATE TABLE demand_clusters (                 -- groupes sémantiques
  id              BIGSERIAL PRIMARY KEY,
  label           TEXT NOT NULL,                -- "chargeurs iPhone"
  centroid_embedding vector(1536),              -- pgvector
  member_count    INTEGER DEFAULT 0
);
```

### 6.4 Enrichment

```sql
CREATE TABLE enrichment_jobs (
  id              BIGSERIAL PRIMARY KEY,
  product_id      BIGINT NOT NULL REFERENCES products(id),
  status          TEXT CHECK (status IN ('queued','running','draft','approved','rejected','failed')) DEFAULT 'queued',
  attempts        INTEGER DEFAULT 0,
  started_at      TIMESTAMPTZ,
  completed_at    TIMESTAMPTZ,
  error           TEXT
);

CREATE TABLE enrichment_drafts (
  id              BIGSERIAL PRIMARY KEY,
  job_id          BIGINT REFERENCES enrichment_jobs(id),
  product_id      BIGINT NOT NULL REFERENCES products(id),
  field           TEXT NOT NULL,                -- 'description','features','faqs','meta_title'...
  value_proposed  JSONB NOT NULL,
  value_current   JSONB,                        -- snapshot avant proposition
  confidence      NUMERIC(3,2),
  sources         JSONB,                        -- [{url, title, fetched_at, excerpt}]
  reviewer_id     BIGINT REFERENCES users(id),
  reviewed_at     TIMESTAMPTZ,
  decision        TEXT CHECK (decision IN ('pending','approved','rejected','edited'))
);

CREATE TABLE domain_events (                    -- journal append-only
  id              BIGSERIAL PRIMARY KEY,
  aggregate_type  TEXT NOT NULL,
  aggregate_id    TEXT NOT NULL,
  type            TEXT NOT NULL,
  payload         JSONB NOT NULL,
  metadata        JSONB,
  occurred_at     TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_de_aggregate ON domain_events(aggregate_type, aggregate_id, occurred_at);
CREATE INDEX idx_de_type_time ON domain_events(type, occurred_at);
```

---

## 7. Pipeline d'enrichissement IA

### 7.1 Flux

```
Catalog edit              EnrichmentRequested (event)
       │                          │
       ▼                          ▼
ProductEnrichmentJob ──► [1] Identité produit (titre + marque + image)
                         [2] Web search (Brave / SerpAPI / Tavily) → 5-10 sources
                         [3] Content extraction (Readability) + dedup
                         [4] LLM call structuré (JSON schema strict)
                         [5] Validation : confidence, citations obligatoires
                         [6] Persistance enrichment_drafts (status='pending')
                         [7] Notification admin
                                │
                                ▼
                       Admin reviews ──► approved/edited/rejected
                                │
                                ▼
              ApplyEnrichmentJob ──► Catalog (UPDATE products) + ProductPublished event
```

### 7.2 Garde-fous anti-hallucination

1. **Sources obligatoires** : chaque champ enrichi a au moins 1 `source.url` accessible.
2. **Schema JSON strict** côté LLM (`response_format` JSON Schema avec champ `evidence`).
3. **Confidence scorer** :
   - +0.2 si fabricant officiel cité,
   - +0.2 si ≥ 2 sources cohérentes,
   - −0.4 si claims numériques non sourcés,
   - −0.3 si LLM utilise "probablement", "généralement" → marqueurs de flou.
4. **Validators automatisés** :
   - `meta_title` ≤ 60 chars,
   - `meta_description` 120-160 chars,
   - `features` = 3-7 items courts,
   - pas de markdown dans `description_html` (purifié via HtmlPurifier),
   - pas d'URL sortantes injectées sauf whitelist.
5. **Sanity checks domaine** :
   - Si l'IA propose un poids < 1 g ou > 50 kg → flag.
   - Si la marque détectée ≠ marque assignée → flag avant approbation.
6. **Detect duplicates** : `pgvector` similarity sur embedding(title + brand) — si > 0.92, alerte
   admin "produit similaire existant ?".

### 7.3 Services

```
Modules/Enrichment/
├── Application/
│   ├── EnrichProductJob.php
│   ├── ApplyEnrichmentJob.php
│   └── DetectDuplicatesJob.php
├── Domain/
│   ├── Services/
│   │   ├── ProductResearchService.php   ← orchestration web search + extraction
│   │   ├── AIContentValidator.php       ← validators ci-dessus
│   │   ├── SourceConfidenceScorer.php
│   │   ├── EvidenceExtractor.php        ← Readability + dedup
│   │   └── EnrichmentPromptBuilder.php  ← templates DRY de prompts
│   └── Contracts/
│       ├── LLMClient.php                ← interface
│       └── WebSearchClient.php          ← interface
└── Infrastructure/
    ├── WebSearch/BraveSearchClient.php
    ├── WebSearch/TavilyClient.php
    └── LLM/
        ├── DeepSeekClient.php            ← PRIMAIRE
        ├── OpenAIClient.php              ← fallback configurable
        └── AnthropicClient.php           ← fallback secondaire
```

### 7.4 Coût et throughput

**Avec DeepSeek (`deepseek-chat`) comme provider primaire** :

- Tarification publique (à confirmer dans le `.env` avec les vrais tarifs courants) :
  - Input  : ~$0.27 / M tokens (cache miss), ~$0.07 / M tokens (cache hit)
  - Output : ~$1.10 / M tokens

- Coût par produit enrichi (5k tokens entrée + 1k sortie) ≈ **$0.003**.
- **741 produits ≈ $2.2 USD one-shot** pour enrichir tout le legacy.
- Coût mensuel récurrent attendu (nouveaux produits + re-enrichissements) : **< $5 USD/mois**.

Throughput cible : 60 jobs/heure (DeepSeek a une bonne tolérance + une politique de cache qui
réduit le coût quand on réutilise des prompts système).

**Embeddings reportés** : la table `demand_clusters.centroid_embedding vector(1536)` reste créée
mais inutilisée en V1/V2. Le clustering V1 utilise n-grams + Jaccard sur les requêtes
normalisées, ce qui marche raisonnablement bien jusqu'à ~5 000 signaux distincts.

Retry automatique exponentiel sur 5xx / rate limit (jitter 0-30s, max 5 tentatives).

### 7.5 Format du prompt DeepSeek (extrait)

```text
SYSTEM:
Tu es un assistant qui structure des informations produit pour un site e-commerce
en français au Congo (devise XAF). Tu réponds UNIQUEMENT en JSON conforme au schéma
fourni. Chaque champ enrichi doit s'appuyer sur au moins une source citée dans
"evidence". Si tu n'as pas d'information fiable, mets null. N'invente JAMAIS de
caractéristique numérique, de poids, de dimension ou d'ingrédient.

USER:
PRODUIT : {{title}}
MARQUE  : {{brand}}
PRIX    : {{price_formatted}}
DESCRIPTION_ACTUELLE: {{description_html_stripped}}

SOURCES COLLECTÉES (extraits) :
{{#sources}}
[{{index}}] {{url}}
{{excerpt}}
---
{{/sources}}

SCHÉMA DE SORTIE :
{ "description_html": string|null, "features": [string],
  "specifications": {string: string}, "faqs": [{q:string,a:string}],
  "meta_title": string, "meta_description": string,
  "evidence": [{ "field": string, "source_index": number, "confidence": 0..1 }] }
```

Le `response_format: { type: "json_object" }` est utilisé côté API (DeepSeek le supporte
en mode `deepseek-chat`).

---

## 8. Intelligence comportementale & capture de demande

### 8.1 Modèle de tracking

Chaque visiteur a une `session.id` (cookie httpOnly, 30 jours, secure). Pas de "user_id" tant
que pas connecté.

Pipeline :

```
HTTP request ──► middleware TrackingMiddleware
                     │
                     ├──► insert behavior_events (server-side)
                     └──► event() dispatch via Horizon ──► Listeners
                                                              │
                                                              ├──► UpdateProductMetrics
                                                              ├──► UpdateSessionIntentScore
                                                              ├──► DetectRageClick
                                                              └──► CheckHighIntentThreshold
```

Côté client (script de 4 ko, vanilla, pas de framework) :

- `scrollDepth` (max %),
- `rageClicks` (3+ clics au même endroit < 1 s),
- `hesitation` (cursor stationary > 5 s),
- `timeOnPage` (visible time, pas idle),
- batché toutes les 10 s, posté en `sendBeacon()` au unload.

### 8.2 Intent Score (V2)

Score 0..1 par session, mis à jour à chaque event :

| Signal | Poids |
| --- | --- |
| > 3 produits vus | +0.15 |
| > 2 ajouts panier | +0.30 |
| Visite page paiement | +0.40 |
| Recherche avec filtre (prix, marque) | +0.10 |
| Retour < 24h | +0.10 |
| Time on product > 90 s | +0.05 |
| Rage click | −0.15 |
| Bounce immédiat | −0.30 |

Seuil `high_intent` = 0.65 → flag en session + notif possible (ex: chat assistant).

### 8.3 Demand capture

Quand `SearchReturnedZeroResults` ou `Search → clic → bounce immédiat` :

1. Normaliser la requête (lowercase, accents, stemming léger FR).
2. Calculer l'embedding (`text-embedding-3-small`, 1536d).
3. Trouver le cluster le plus proche (`ORDER BY embedding <-> centroid LIMIT 1`).
4. Si distance < 0.15 → ajouter au cluster, sinon nouveau cluster.
5. UPSERT `demand_signals` avec `frequency_7d/30d`, `last_seen_at`, exemples de requêtes.
6. Calcul `urgency` :
   - `frequency_7d ≥ 20` → high,
   - `frequency_7d ≥ 50` → viral,
   - `> 5 sessions différentes` → boost.

### 8.4 Dashboard "Produits demandés"

Page admin `/admin/demand` avec :

- Top 50 clusters par fréquence 7j × urgency.
- Drill-down → liste des queries, sessions, géo.
- Boutons : "Sourcer ce produit" (crée un brief), "Rejeter" (cluster status = rejected).
- KPI : "12 nouveaux clusters cette semaine", "3 clusters viraux non sourcés".

---

## 9. Recherche intelligente

### 9.1 V1 — Meilisearch + Scout

- Index `products` synchronisé via `Searchable` trait Laravel.
- Champs indexés : `title^3`, `brand.name^2`, `categories.name`, `description_text` (HTML stripped),
  `sku`, `tags`.
- Filtres : `brand_id`, `category_id`, `price_minor`, `availability`.
- Facettes : `brand`, `category`, `price_range`.

### 9.2 Typo tolerance & synonymes

- Meilisearch native typo : 1 typo si ≥ 5 chars, 2 si ≥ 9 chars.
- Dict synonymes éditable en admin : `chargeur ↔ adaptateur`, `bébé ↔ bebe ↔ baby`, `crème ↔ creme`.

### 9.3 V2 — Recherche sémantique (hybrid)

- Embeddings produits stockés dans `products.embedding vector(1536)`.
- Meilisearch v1.10 hybrid search : `?hybrid={embedder, semanticRatio:0.5}`.
- Effet : `"chargeur samsung"` → trouve "Adaptateur USB-C Samsung Fast Charging" même sans
  match lexical.

### 9.4 Search-as-you-type

- Endpoint `/api/search/suggest` (POST, debounced 150ms côté client HTMX).
- Top 5 produits + top 3 catégories + top 3 marques.
- Si 0 résultat → enregistré dans `search_logs` avec `hits_count=0` (vital pour la demande).

### 9.5 Search intent classifier (V3)

- Train un mini-modèle (logistic regression sur features) qui prédit l'intention :
  `informational | navigational | transactional | comparative`.
- Sert à reranker (un user "transactional" voit en premier les produits en stock).

---

## 10. Observabilité & Business Intelligence

### 10.1 Stack

| Domaine | Outil | Coût |
| --- | --- | --- |
| Logs applicatifs | Laravel `log` channel `stack` → fichier rotaté + Loki (optionnel) | 0 € |
| Erreurs | Sentry (free tier 5k events/mois) | 0 € |
| APM | Laravel Telescope (admin) + Pulse | 0 € |
| Métriques | Prometheus exporter via `arquivei/laravel-prometheus-exporter` | 0 € |
| Dashboards | Grafana sur les métriques + Metabase sur PostgreSQL | 0 € |

### 10.2 Projections BI (Horizon jobs périodiques)

Schedule `app/Console/Kernel.php` :

```
*/5  → ProjectProductMetricsJob      (views, atc, conversion par produit)
*/10 → ProjectSearchMetricsJob       (top queries, zero-result rate)
hourly → ProjectDemandClustersJob    (re-clustering)
hourly → ProjectFunnelMetricsJob     (cart → checkout → paid)
daily → ProjectChurnSignalsJob       (sessions répétées sans conversion)
```

Toutes ces projections écrivent dans des tables `metrics_*` lues par Metabase.

### 10.3 Dashboards minimaux V1

1. **Demand** : produits demandés non vendus (cf §8.4).
2. **Funnel** : visites → vues produit → ajout panier → checkout → paiement.
3. **SEO** : URLs 404, redirects hit, top pages organic landings.
4. **Catalog health** : produits sans image, sans description, sans prix, sans stock.
5. **AI Enrichment** : drafts en attente, taux d'approbation, coût mensuel.

---

## 11. Accessibilité & Semantic Web

### 11.1 Garanties WCAG 2.2 AA

- **Contraste** : audit en CI via `pa11y-ci` sur 20 URLs représentatives, échec si violation.
- **Navigation clavier** : tous interactifs `tabindex` cohérent, focus visible (Tailwind
  `focus-visible:ring-2`).
- **Skip link** `<a href="#main">Aller au contenu</a>` en premier dans `<body>`.
- **Formulaires** : `<label for>` obligatoire, `aria-describedby` pour les erreurs, validation
  inline non-bloquante.
- **Images** : `alt` non vide pour les images significatives, `alt=""` pour décoratives. Pour les
  produits, `alt = "{title} — {brand}"` par défaut.
- **Live regions** : `aria-live="polite"` pour le mini-cart, `aria-live="assertive"` pour erreurs critiques.

### 11.2 Structure HTML5 imposée

```html
<body>
  <a href="#main" class="skip-link">…</a>
  <header role="banner">
    <nav aria-label="Navigation principale">…</nav>
    <nav aria-label="Fil d'Ariane">…</nav>
  </header>
  <main id="main">
    <article itemscope itemtype="https://schema.org/Product">
      <h1 itemprop="name">…</h1>
      <section aria-labelledby="desc-heading">
        <h2 id="desc-heading">Description</h2>
        …
      </section>
      <aside aria-label="Informations achat">
        <!-- prix, ajout panier, stock -->
      </aside>
    </article>
  </main>
  <footer role="contentinfo">…</footer>
</body>
```

### 11.3 Microdata redondante avec JSON-LD ?

Non. **Une seule** source structurée par page : JSON-LD. Les attributs `itemprop` ci-dessus
sont à supprimer pour éviter la duplication contradictoire. Garder uniquement le JSON-LD
dans `<head>`.

### 11.4 AI-readable mode

- Toutes les fiches produit servent par défaut du HTML sémantique + JSON-LD. C'est déjà l'AI-mode.
- Endpoint additionnel `/.well-known/products/{drupal_id}.json` qui retourne le produit en
  JSON canonique (mêmes données que la fiche, sans HTML). Utile pour les agents qui veulent
  scraper structuré.
- Headers : `Link: </.well-known/products/123.json>; rel="alternate"; type="application/json"`.

### 11.5 robots.txt et `llms.txt`

- `robots.txt` standard.
- `llms.txt` (proposition Anthropic) à la racine, listant les chemins "ground truth" pour les LLM :

  ```
  # nasande.cg — guide pour modèles IA
  Sitemap: https://nasande.cg/sitemap.xml
  Products: https://nasande.cg/.well-known/products.jsonl
  About: https://nasande.cg/a-propos
  ```

---

## 12. Roadmap phasée

### Phase 0 — Préparation (3-5 j)

- [ ] Provisionner staging : `staging.nasande.cg` (sous-domaine) avec Postgres + Meilisearch + Redis.
- [ ] Pipeline CI (GitHub Actions) : phpstan level 8, pint, pa11y, Pest.
- [ ] Bootstrap Laravel 12, structure modulaire, dotenv, Sentry.
- [ ] Décider du domaine de bascule : `old.nasande.cg` pour ancien site pendant la transition.

### Phase 1 — Socle catalogue + SEO (2 semaines)

Objectif : **toutes les 741 URLs legacy répondent 200 sur staging, contenu identique à l'œil nu**.

- [ ] Migrations DB (sections 6.1, 6.2).
- [ ] Commande `php artisan migrate:from-drupal` qui ingère `out/products.json` + `out/url-aliases.json`.
  - origin = 'drupal_migration', canonical_path = legacy path, status = 'published'.
- [ ] Téléversement de `out/images/` vers `storage/app/public/products/legacy/` + symlink.
- [ ] Modèles Eloquent + relations.
- [ ] Controllers `LegacyProductController`, `NewProductController`, `CategoryController`, `BrandController`.
- [ ] Layouts Blade : layout global, partials header/footer/breadcrumb.
- [ ] Templates produit / catégorie / marque / homepage.
- [ ] `sitemap.xml`, `robots.txt`, `llms.txt`, JSON-LD Product/Breadcrumb/Organization.
- [ ] Tests : Pest test pour chaque URL legacy → assert 200 + title contient `product.title`.

**Critère de bascule** : 100 % des URLs `url-aliases.json` répondent 200, audit Lighthouse mobile
≥ 90 sur 5 fiches produit échantillonnées.

### Phase 1.5 — Backoffice catalogue (1.5 semaine)

Objectif : **un admin peut créer un nouveau produit de zéro, l'enrichir, le publier**.

- [ ] Module `Modules/Admin` + middleware `auth.admin`.
- [ ] Auth admin (email + password + 2FA TOTP, table `users` avec role).
- [ ] CRUD `/admin/products` (list / create / edit / archive).
- [ ] Upload d'images : multi, drag-drop HTMX, validation magic-bytes, optim async.
- [ ] CRUD `/admin/brands`, `/admin/categories`.
- [ ] Permissions via `spatie/laravel-permission` (admin / catalog-editor / catalog-viewer).
- [ ] Dashboard accueil admin : KPIs catalogue (produits sans image, sans description, drafts).
- [ ] Tests E2E Dusk : workflow upload complet d'un produit.

**Critère** : un nouveau produit créé dans `/admin/products/create` est accessible sur
`/produits/{slug}` avec JSON-LD complet, sitemap mis à jour, dans les 30 secondes après
publication.

### Phase 2 — Commerce minimum (2 semaines)

- [ ] Cart (session-based, persistance Redis), middleware d'agrégation behavior_events.
- [ ] Checkout, livraison **Brazzaville uniquement** (frais fixes configurable en admin).
- [ ] Interface `PaymentProvider` + premières implémentations :
  - `AirtelMoneyProvider`
  - `MtnMomoProvider`
  - `CashOnDeliveryProvider` (paiement à la livraison, fallback)
- [ ] Orders + Order status workflow (`pending → paid → preparing → out_for_delivery → delivered`).
- [ ] Compte utilisateur léger pour les commandes (email/OTP, pas de mot de passe — **création nouvelle**, pas de migration).
- [ ] Notifications email transactionnelles (Postmark ou Resend).
- [ ] Webhooks paiement → signature + idempotency keys.

### Phase 3 — Search + Behavior tracking (1.5 semaine)

- [ ] Meilisearch index + Scout (BM25 + typo + synonymes manuels en admin).
- [ ] Search UI (HTMX search-as-you-type).
- [ ] `behavior_events`, `sessions`, `search_logs` + TrackingMiddleware.
- [ ] Script JS client (scroll, rage, hesitation) — vanilla 4 ko.
- [ ] `domain_events` table active partout dans le code.

**Bascule production** envisageable ici (Phase 0 → Phase 3).

### Phase 4 — Demand intelligence (1 semaine)

- [ ] `demand_signals`, `demand_clusters`, projections horaires.
- [ ] **Clustering V1/V2 sans embeddings** : n-gram + Jaccard sur requêtes normalisées
      (suffisant jusqu'à ~5 000 signaux). Migration vers embeddings = Phase 6.
- [ ] Dashboard admin `/admin/demand` (top demande non satisfaite, urgency, drill-down).

### Phase 5 — AI enrichment (2 semaines)

- [ ] `enrichment_jobs`, `enrichment_drafts`.
- [ ] `ProductResearchService` + clients WebSearch (Tavily ou Brave — au choix selon clé dispo).
- [ ] `LLMClient` (DeepSeek primaire) avec retry exponentiel + jitter.
- [ ] UI admin de review : diff visuel, sources cliquables, boutons approve/edit/reject.
- [ ] Bouton "Enrichir avec IA" dans la fiche admin d'un nouveau produit (Phase 1.5).
- [ ] Backfill : enqueue tous les produits avec `description_html IS NULL` ou `media_count = 0`.

### Phase 6 — Sémantique avancée (V3, à la demande)

- [ ] Choix embeddings provider (OpenAI vs local sentence-transformers).
- [ ] `pgvector` + embeddings produits.
- [ ] Meilisearch hybrid search.
- [ ] Intent classifier session.
- [ ] Recommandations cross-sell ("vu ensemble") via co-occurrence behavior_events.
- [ ] Système d'avis clients (`reviews`).

---

## 13. Risques & questions ouvertes

### 13.1 Risques techniques

| Risque | Probabilité | Impact | Mitigation |
| --- | --- | --- | --- |
| Drupal arrête de répondre pendant la migration | Moyen | Élevé | Export déjà fait, snapshot disponible offline (`out/`). |
| Mobile Money APIs Congo instables | Élevé | Moyen | Fallback "paiement à la livraison", queue de retry, monitoring. |
| Bandwidth visiteurs limité | Élevé | Moyen | LCP-first, images responsive, edge cache. |
| 82 produits sans image après IA aussi | Moyen | Faible | UI placeholder + tag "image en cours". |
| LLM hallucination publiée | Faible (review obligatoire) | Élevé | Revue humaine obligatoire avant `approved`. |
| Crawl bots qui flooderaient `behavior_events` | Moyen | Moyen | UA-based bot detection + `robots.txt` strict + sampling 1:10 si bot suspecté. |

### 13.2 Décisions métier validées

1. **Paiement** ✅ — Modulaire. V1 implémente Airtel Money + MTN MoMo + Cash on Delivery (fallback).
   Interface `PaymentProvider` permet d'ajouter Wave, Visa, etc. plus tard sans refacto.
2. **Livraison** ✅ — Brazzaville seulement en V1. Frais fixe configurable en admin. Zones
   multi-villes reportées (la table `shipping_zones` n'est pas créée en V1, on ajoute un simple
   champ `delivery_address_text` sur les commandes).
3. **Compte client** ✅ — Pas de migration des comptes Drupal. Tout client recrée son compte
   (email + OTP) au premier checkout.
4. **Reviews / avis** ✅ — V3, après stabilisation comportement + demande.
5. **Langue** ✅ — Français uniquement. Pas de prefix de locale dans les routes. Si EN un jour,
   on ajoutera un sous-domaine `en.nasande.cg` plutôt qu'un prefix `/en/...`.
6. **Catégorie hiérarchique** ✅ — Liste plate conservée. La colonne `parent_id` reste dans la
   table `categories` (nullable) pour rester future-proof, mais on ne l'utilise pas en V1.
7. **Origines** ✅ — Taxonomie supprimée. 1 seul terme, valeur nulle. Pas de table `origines`.
8. **Budget IA** ✅ — DeepSeek. Coût catalogue ~$2 USD one-shot, ~$5/mois récurrent.
9. **Backoffice upload produits** ✅ — Phase 1.5 (entre socle SEO et commerce). CRUD complet.

### 13.3 Choix d'infrastructure validés

1. **Environnement de développement** ✅ — **Local d'abord** via Laravel Sail (Docker Compose).
   Décision de production reportée à la fin de Phase 3.
2. **Web search agent IA** ✅ — **Brave Search free tier** (2 000 req/mois → largement suffisant
   pour les 741 produits + nouveaux).
3. **Email transactionnel** ✅ — **Resend** (driver Symfony Mailer compatible Laravel, free 3 000
   emails/mois, bonne deliverability).
4. **Stockage médias** ✅ — **Local** (`storage/app/public/products/...` + symlink via
   `php artisan storage:link`). Migration vers object storage (R2 / S3) à reconsidérer
   quand on franchit ~50 GB ou qu'on bascule en production multi-serveur.
5. **2FA admin** ✅ — **TOTP** (Google Authenticator / 1Password / Authy). Pas de SMS (coût +
   dépendance opérateur).
6. **Tarifs DeepSeek** — Confirmés au moment du provisionnement (peuvent évoluer). Configurables
   dans `.env`.

### 13.4 Stack locale (Herd + binaires portables, zero-sudo)

Permissions Homebrew bloquantes sur la machine cible (write denied `/usr/local/Cellar`).
Pivot vers une stack **100 % installable sans `sudo`** :

| Composant     | Source                          | Phase | Rôle                                       |
| ------------- | ------------------------------- | ----- | ------------------------------------------ |
| PHP 8.4+      | **Herd** (déjà installé)        | 1+    | Runtime                                    |
| Composer 2.x  | **Herd**                        | 1+    | Package manager PHP                        |
| nginx         | **Herd** (sites `.test`)        | 1+    | Serveur dev (alt. à `php artisan serve`)   |
| **SQLite 3**  | natif macOS                     | **1** | Base de données (V1)                       |
| Driver `database` Laravel | natif                  | 1     | Queue + cache + session                    |
| Meilisearch   | binaire portable `./bin/`       | 3     | Index produits                             |
| Mailpit       | binaire portable `./bin/`       | 2     | Inbox dev SMTP                             |
| PostgreSQL 16 | Postgres.app *(manuel)* ou brew | **4** | Migration depuis SQLite quand JSONB + pgvector nécessaires |
| Redis         | optionnel                       | 3+    | Si charge le justifie (sinon `database`)   |
| Node 26+      | déjà présent                    | 1+    | Vite + Tailwind                            |

**Trade-off SQLite → PostgreSQL différé** :

- **Pro SQLite V1** : démarrage immédiat, zero install, code Eloquent identique.
- **Contre SQLite V1** : pas de `JSONB` requêtable (les champs JSON sont stockés en TEXT,
  requêtables uniquement via `JSON_EXTRACT()` plus lent), pas de `pgvector`, pas de
  `SELECT FOR UPDATE SKIP LOCKED`.
- **Migration prévue** en Phase 4 (Demand intelligence) où la performance JSONB devient
  significative pour le clustering de demande. Laravel rend la migration triviale :
  on change `.env` puis `php artisan migrate:fresh --seed` sur Postgres.
- Pour les migrations : on garde une syntaxe compatible (pas d'index `GIN`, pas de
  `vector(1536)`, pas d'`USING gen_random_uuid()` en V1 — on utilise `Str::uuid()` PHP).

Pas de Docker. Tout reste dans `~/Library/Application Support/Herd/` et `./bin/`.

Variables `.env` injectées :

```
APP_ENV=local
APP_URL=http://localhost:8000
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nasande
DB_USERNAME=nasande
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@nasande.cg

FILESYSTEM_DISK=public

LLM_PROVIDER=deepseek
DEEPSEEK_API_KEY=
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL_CHAT=deepseek-chat

WEBSEARCH_PROVIDER=brave
BRAVE_SEARCH_API_KEY=

EMBEDDINGS_PROVIDER=none
```

### 13.5 Hypothèses à challenger

- *"Tous les produits doivent avoir une fiche enrichie"* — Faux. La fiche actuelle est suffisante
  pour 80 % des cas. Prioriser l'enrichissement sur les produits **vus + non convertis**.
- *"On doit tracker tout"* — Faux. On tracke ce qui informe une décision. Tout le reste pollue.
- *"L'IA va remplacer la curation humaine"* — Faux. L'IA réduit le temps de curation par 5 ; elle
  ne l'élimine pas, surtout pas sur un catalogue santé/cosmétique où la responsabilité est légale.
- *"Inertia/Vue ferait gagner du temps"* — Faux pour ce projet : surface d'interactivité faible,
  audience bandwidth-limité, exigence SEO/crawler maximale.

---

## Annexes

### A. Glossaire

- **drupal_id** : ID interne Drupal d'un produit. Présent dans les URLs legacy. Immuable.
- **canonical_path** : URL canonique servie pour un produit. Pour les produits issus de Drupal,
  c'est l'URL héritée (`/25/acm-novophane-lotion-antichute`). Pour les nouveaux,
  c'est `/produits/{slug}`.
- **origin** : `'drupal_migration'` (legacy) ou `'backoffice'` (créé via admin) ou `'api_import'`.
- **Intent score** : score 0..1 calculé par session reflétant la probabilité de conversion.
- **Demand cluster** : groupe sémantique de requêtes utilisateur (ex: tous les variants de "chargeur iPhone").
- **Enrichment draft** : proposition IA non publiée, en attente de revue.

### B. Sources de référence

- WCAG 2.2 : <https://www.w3.org/TR/WCAG22/>
- Schema.org Product : <https://schema.org/Product>
- Laravel 12 docs : <https://laravel.com/docs/12.x>
- Meilisearch hybrid search : <https://www.meilisearch.com/docs/learn/experimental/vector_search>
- llms.txt proposal : <https://llmstxt.org/>
