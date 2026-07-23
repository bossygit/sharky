# Runbook de déploiement — Nasande (prod)

Hébergement cible : **Linux + Apache + MySQL** (mutualisé ou VPS). Pas de Redis/Meilisearch
en prod : la recherche utilise le driver **Scout "database"** et la queue le driver **"database"**.

## 0. Prérequis serveur
- PHP 8.2+ avec extensions : mbstring, intl, sqlite3 (pour tests), gd, curl, bcmath, xml, zip.
- MySQL 5.7+ (ou 8) ; Apache avec `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_deflate`/`brotli`.
- Composer, Node 20+, accès SSH.
- Domaine `nasande.cg` pointant sur le serveur ; SSL (Let's Encrypt) **obligatoire** (HSTS posé).

## 1. Premier déploiement
```bash
# 1. Récupérer le code (ou laisser le CI/rsync le faire)
git clone <repo> /var/www/nasande
cd /var/www/nasande

# 2. Fichier d'environnement
cp .env.production.example .env
#   -> remplir TOUTES les valeurs (DB, MAIL, DEEPSEEK, BRAVE, paiements)

# 3. Clé d'application (À NE PAS RÉUTILISER la locale)
php artisan key:generate --force

# 4. Dépendances
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 5. Base de données
php artisan migrate --force
#   (importer le catalogue Drupal si besoin : php artisan catalog:migrate-from-drupal)

# 6. Storage + caches
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Recherche (Scout database)
php artisan scout:import "App\\Modules\\Catalog\\Domain\\Models\\Product"

# 8. Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 9. Worker de queue (voir §3)
```

## 2. Apache — DocumentRoot
Pointer `DocumentRoot` sur `/var/www/nasande/public`. Le `public/.htaccess` (livré)
gère déjà : front-controller, **www→non-www (301)**, **HTTP→HTTPS (301)**, headers
sécurité (HSTS, CSP, X-Frame-Options…), cache navigateur, compression.

## 3. Worker de queue (CRITIQUE — sinon l'enrichissement ne tourne pas)
`EnrichProductJob` est `ShouldQueue` : il atterrit dans la table `jobs` MySQL et
nécessite un worker continu. Deux options :

**Option A — Supervisor (recommandé, VPS)**
```bash
sudo cp deploy/supervisor-nasande-worker.conf /etc/supervisor/conf.d/nasande-worker.conf
sudo sed -i 's#/chemin/absolu/vers/nasande#/var/www/nasande#' /etc/supervisor/conf.d/nasande-worker.conf
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status nasande-worker
```

**Option B — Cron (mutualisé sans Supervisor)**
```bash
# crontab -e  ->  * * * * * /var/www/nasande/deploy/cron-worker.sh
```

Vérifier : `php artisan queue:work database --once` doit consommer un job.

## 4. Déploiements suivants (via CI/CD)
Le push sur `main` déclenche `.github/workflows/deploy.yml` :
lint (Pint) + tests (PHPUnit) → rsync → post-deploy (composer, npm build, migrate,
cache, scout:import, `queue:restart`). Aucun downtime manuel nécessaire.

## 5. Paiements mobiles (Airtel Money / MTN MoMo)
Providers présents (`MtnMomoProvider`, `AirtelMoneyProvider`). Renseigner les clés
dans `.env` (voir `.env.production.example`). Commencer en `MTN_MOMO_ENVIRONMENT=sandbox`,
passer en `live` après validation opérateur. Le fallback **Cash on delivery** reste actif.

## 6. Rollback
- `git revert <commit>` puis relancer le déploy, OU
- `php artisan down` (maintenance) + restaurer dump SQL précédent.

## 7. Variables secrets (GitHub, pour le CI)
`SSH_HOST`, `SSH_USER`, `SSH_KEY` (clé privée déploy), `SSH_KNOWN_HOSTS`.
Jamais de `.env` complet dans GitHub.
