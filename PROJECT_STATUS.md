# PROJECT STATUS - NASANDE MIGRATION

## Global Status
- [x] **Local Stable Build — COMPLETED** ✅
- [ ] Production Ready

## 🛠 Technical Health (all green)
| Service | Status |
|---|---|
| PostgreSQL 16 | ✅ OK |
| Redis | ✅ OK |
| Meilisearch | ✅ OK |
| Mailpit | ✅ OK |
| Artisan Serve | ✅ OK (port 8000) |
| DB Migrations | ✅ OK |

## 📦 Catalog Audit
- Validated URLs: 860 (0 errors)
- Products indexed (Meilisearch): 742 documents
- Legacy SEO URLs: ✅ All preserved

## 🧩 Module Validation

### Catalog ✅
- Canonical product pages: OK
- Legacy Drupal redirects (301): OK
- JSON-LD schema: Present

### SEO ✅
- robots.txt: 200 OK
- sitemap.xml: 200 OK
- llms.txt: 200 OK

### Commerce ✅
- Cart page: Renders correctly
- CartController: Robust validation
- OTP/Checkout services: In place

### Search ✅
- Meilisearch indexing: Fixed (was SCOUT_QUEUE=true without worker)
- HTMX suggestions: Working
- Synonym groups: 3 synced

### Behavior ✅
- TrackingMiddleware: Registered globally
- JS beacon (nasande-track.js): Present
- API endpoint: /api/behavior/beacon

### Demand ✅
- demand:project-clusters command: Working
- DemandCaptureService: Captures zero-result searches
- DemandCluster pipeline: Operational

### Enrichment ✅
- 11 jobs processed → 55 drafts generated
- Pipeline: Research → LLM → Validator → Draft
- Bug found: Redis queue worker not consuming enrichment jobs (workaround: direct orchestrator call)

### Admin ✅
- Login: Working (ne pas documenter le mot de passe)
- Dashboard: Renders correctly
- Product CRUD: Accessible
- Full sidebar navigation: Products, Brands, Categories, Orders, Commerce Settings, Search Synonyms, Demand Clusters, Enrichment Review

## 🐞 Bugs Found & Fixed
1. **Meilisearch empty index**: SCOUT_QUEUE=true enqueued jobs but no worker. Fixed by running SCOUT_QUEUE=false import.
2. **Enrichment jobs not processed**: Redis queue worker not consuming EnrichProductJob. Workaround: direct orchestrator processing.
3. **CSRF token not sent via curl**: Login required _token extraction from form. Not a code bug, test-environment issue.

## ⚠️ Known Issues (non-blocking)
- Redis queue worker needs investigation for enrichment job consumption
- Enrichment uses StubLLMClient (no real AI in local env)

## 📊 Stats
| Metric | Value |
|---|---|
| Products in catalog | 860 URLs validated |
| Meilisearch documents | 742 |
| Enrichment drafts | 55 |
| Admin users | 1 |
| Modules validated | 9/9 |

## 🚀 Next: Production Readiness
État au 2026-07-23 (voir aussi deploy/README.md) :
- [x] **Harnais prod ciblé** : MySQL + queue "database" + cache "file" + Scout "database" (pas de Redis/Meili en prod)
- [x] **.env.production.example** créé (clés API, DB, paiements, mail, sécu)
- [x] **public/.htaccess hardening** : www→non-www (301), HTTP→HTTPS (301), HSTS, CSP, X-Frame-Options, cache, compression
- [x] **Worker de queue** : deploy/supervisor-nasande-worker.conf + deploy/cron-worker.sh (corrige le bug « jobs non consommés »)
- [x] **CI/CD** : .github/workflows/deploy.yml (Pint + PHPUnit + rsync + post-deploy migrate/cache/scout:import/queue:restart)
- [x] **Runbook** : deploy/README.md
- [ ] **Clés API réelles** à fournir (DeepSeek, Brave, Airtel Money, MTN MoMo) → à saisir dans .env serveur
- [ ] **Staging** déployé et testé (tunnel achat + OTP + enrichissement)
- [ ] **Paiements réels** activés (sandbox → live après validation opérateur)

### Note sur l'ancien bug Redis d'enrichment
Le `PROJECT_STATUS` initial citait un worker Redis qui ne consommait pas les jobs.
En configuration PROD (QUEUE_CONNECTION=database), le souci n'est plus Redis mais
**l'absence de worker `queue:work`** — résolu par deploy/supervisor-nasande-worker.conf
(via Supervisor) ou deploy/cron-worker.sh (via cron sur mutualisé).
