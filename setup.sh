#!/usr/bin/env bash
#
# setup.sh — Bootstrap de l'environnement local nasande (Herd + Homebrew).
#
# • PHP 8.3+ et Composer fournis par Laravel Herd (déjà installé).
# • PostgreSQL 16, Redis, Meilisearch, Mailpit installés via Homebrew (Herd free
#   ne les fournit pas ; Herd Pro oui).
# • Bootstrap Laravel 12 à la racine en préservant ARCHITECTURE.md, out/, tools/.
#
# Idempotent : ré-exécutable sans dégât.
#
# Usage :
#   chmod +x setup.sh
#   ./setup.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

bold() { printf "\033[1m%s\033[0m\n" "$*"; }
ok()   { printf "  \033[32m✓\033[0m %s\n" "$*"; }
warn() { printf "  \033[33m!\033[0m %s\n" "$*"; }
err()  { printf "  \033[31m✗\033[0m %s\n" "$*" >&2; }

# ─── 0. Herd : ajoute PHP & Composer au PATH ─────────────────────────────────
HERD_BIN="$HOME/Library/Application Support/Herd/bin"
if [[ ! -x "$HERD_BIN/php" ]]; then
    err "Laravel Herd introuvable à $HERD_BIN"
    err "Installe-le depuis https://herd.laravel.com puis relance ce script."
    exit 1
fi
export PATH="$HERD_BIN:$PATH"

PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
COMPOSER_VERSION="$(composer --version 2>&1 | awk '{print $3}')"
bold "0/8  Runtime Herd"
ok "PHP $PHP_VERSION"
ok "Composer $COMPOSER_VERSION"

# ─── 1. Homebrew permissions ─────────────────────────────────────────────────
bold "1/8  Vérification des permissions Homebrew"
if ! brew list >/dev/null 2>&1; then
    warn "Permissions Homebrew incorrectes. Réparation (sudo demandé une fois)…"
    sudo chown -R "$(whoami)" \
        "/Users/$(whoami)/Library/Logs/Homebrew" \
        /usr/local/Cellar /usr/local/Frameworks /usr/local/Homebrew \
        /usr/local/bin /usr/local/etc /usr/local/include /usr/local/lib \
        /usr/local/opt /usr/local/sbin /usr/local/share /usr/local/var 2>/dev/null || true
    ok "Permissions réparées"
else
    ok "Permissions Homebrew OK"
fi

# ─── 2. Installation des services Homebrew ───────────────────────────────────
bold "2/8  Installation des services (Postgres, Redis, Meilisearch, Mailpit)"
FORMULAS=(postgresql@16 redis meilisearch mailpit)
for f in "${FORMULAS[@]}"; do
    if brew list "$f" >/dev/null 2>&1; then
        ok "$f déjà installé"
    else
        bold "    → brew install $f"
        brew install "$f"
    fi
done

# Linker postgresql@16 dans le PATH
brew link --force --overwrite postgresql@16 2>/dev/null || true

# ─── 3. Démarrage des services ───────────────────────────────────────────────
bold "3/8  Démarrage des services"
for s in postgresql@16 redis meilisearch mailpit; do
    if brew services list | grep -q "^$s.*started"; then
        ok "$s déjà démarré"
    else
        brew services start "$s" >/dev/null
        ok "$s démarré"
    fi
done

# Attendre que Postgres soit prêt
PG_BIN="$(brew --prefix postgresql@16)/bin"
for i in {1..20}; do
    if "$PG_BIN/pg_isready" -h 127.0.0.1 -p 5432 >/dev/null 2>&1; then
        ok "PostgreSQL prêt"
        break
    fi
    sleep 1
done

# ─── 4. Création de la base & du user ────────────────────────────────────────
bold "4/8  Création de la base PostgreSQL"
DB_USER="nasande"
DB_NAME="nasande"
PG_SUPERUSER="$(whoami)"

PSQL="$PG_BIN/psql"

if ! "$PSQL" -h 127.0.0.1 -U "$PG_SUPERUSER" -d postgres -tc \
    "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" 2>/dev/null | grep -q 1; then
    "$PSQL" -h 127.0.0.1 -U "$PG_SUPERUSER" -d postgres \
        -c "CREATE ROLE $DB_USER LOGIN PASSWORD 'nasande_local';" >/dev/null
    ok "Rôle $DB_USER créé"
else
    ok "Rôle $DB_USER déjà existant"
fi

if ! "$PSQL" -h 127.0.0.1 -U "$PG_SUPERUSER" -d postgres -tc \
    "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" 2>/dev/null | grep -q 1; then
    "$PSQL" -h 127.0.0.1 -U "$PG_SUPERUSER" -d postgres \
        -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;" >/dev/null
    ok "Base $DB_NAME créée"
else
    ok "Base $DB_NAME déjà existante"
fi

"$PSQL" -h 127.0.0.1 -U "$PG_SUPERUSER" -d "$DB_NAME" \
    -c "CREATE EXTENSION IF NOT EXISTS pgcrypto;" >/dev/null
ok "Extension pgcrypto activée"

# ─── 5. Bootstrap Laravel 12 ─────────────────────────────────────────────────
bold "5/8  Bootstrap Laravel 12"
if [[ -f artisan ]]; then
    ok "Projet Laravel déjà bootstrappé"
else
    TMP_DIR="$(mktemp -d)"
    composer create-project laravel/laravel "$TMP_DIR/laravel" "^12.0" \
        --prefer-dist --no-interaction
    # rsync sans écraser ARCHITECTURE.md, out/, tools/, setup.sh
    rsync -a \
        --exclude='.git' \
        --exclude='ARCHITECTURE.md' \
        --exclude='out/' \
        --exclude='tools/' \
        --exclude='setup.sh' \
        "$TMP_DIR/laravel/" "$ROOT/"
    rm -rf "$TMP_DIR"
    ok "Laravel 12 installé"
fi

# ─── 6. Configuration .env ───────────────────────────────────────────────────
bold "6/8  Configuration de .env"
cat > .env <<'EOF'
APP_NAME="nasande"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_TIMEZONE=Africa/Brazzaville

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nasande
DB_USERNAME=nasande
DB_PASSWORD=nasande_local

BROADCAST_CONNECTION=log
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@nasande.cg
MAIL_FROM_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=true
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

FILESYSTEM_DISK=public

LLM_PROVIDER=deepseek
DEEPSEEK_API_KEY=
DEEPSEEK_BASE_URL=https://api.deepseek.com/v1
DEEPSEEK_MODEL_CHAT=deepseek-chat
DEEPSEEK_MODEL_REASONING=deepseek-reasoner

WEBSEARCH_PROVIDER=brave
BRAVE_SEARCH_API_KEY=

EMBEDDINGS_PROVIDER=none
OPENAI_API_KEY=

RESEND_API_KEY=

DRUPAL_EXPORT_DIR=./out
EOF

php artisan key:generate --ansi >/dev/null
ok ".env configuré"

# ─── 7. Structure modulaire DDD ──────────────────────────────────────────────
bold "7/8  Création des modules DDD"
MODULES=(Catalog Search Behavior Demand Enrichment SEO Commerce Admin Observability)
for m in "${MODULES[@]}"; do
    mkdir -p "app/Modules/$m"/{Domain/{Models,Services,Events},Application,Infrastructure,Http/{Controllers,Resources},Contracts}
    mkdir -p "database/migrations/modules/$(echo "$m" | tr '[:upper:]' '[:lower:]')"
    if [[ ! -f "app/Modules/$m/README.md" ]]; then
        cat > "app/Modules/$m/README.md" <<EOF
# Module $m

Voir [ARCHITECTURE.md](../../../ARCHITECTURE.md) section 5.
EOF
    fi
done
# .gitkeep dans les dossiers vides pour git
find app/Modules -type d -empty -exec touch {}/.gitkeep \;
ok "Modules : ${MODULES[*]}"

# ─── 8. Migration & link storage ─────────────────────────────────────────────
bold "8/8  Migrations initiales"
php artisan storage:link 2>/dev/null || ok "storage déjà linké"
php artisan migrate --no-interaction
ok "Migrations Laravel par défaut OK"

if [[ ! -d node_modules ]]; then
    npm install
    ok "Dépendances npm installées (Vite)"
else
    ok "node_modules déjà présent"
fi

# ─── Fin ─────────────────────────────────────────────────────────────────────
echo
bold "═══════════════════════════════════════════════════════════════"
bold "  Setup terminé."
bold "═══════════════════════════════════════════════════════════════"
echo
echo "  Démarre le serveur Laravel :"
echo "    php artisan serve"
echo
echo "  OU tout-en-un (serve + queue + logs + vite) :"
echo "    composer dev"
echo "    (requiert npm install — fait automatiquement par setup.sh)"
echo
echo "  OU lie le site dans Herd (URL .test plus jolie) :"
echo "    herd link nasande"
echo "    → puis ouvre http://nasande.test"
echo
echo "  Démarre le worker Horizon (plus tard quand on aura des jobs) :"
echo "    php artisan queue:work"
echo
echo "  Mailpit (inbox dev) :   http://localhost:8025"
echo "  Meilisearch UI :        http://localhost:7700"
echo "  Postgres :              psql -h 127.0.0.1 -U nasande -d nasande"
echo
echo "  Note PATH : si 'php' n'est pas dispo dans ton terminal habituel,"
echo "  ajoute cette ligne à ~/.zshrc :"
echo "    export PATH=\"\$HOME/Library/Application Support/Herd/bin:\$PATH\""
echo
