#!/bin/sh
set -e

# Render fournit PORT (10000 par défaut). Apache doit écouter dessus.
PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Base de données : migrations puis catalogue de démonstration (idempotent,
# le seeder s'arrête si des produits existent déjà, sauf VINS_SEED_FORCE=1).
php artisan migrate --force
php artisan db:seed --class=VinsCatalogSeeder --force

# Compte admin initial (idempotent) : uniquement si les variables sont fournies.
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    php artisan vins:admin "$ADMIN_EMAIL" \
        --password="$ADMIN_PASSWORD" \
        --name="${ADMIN_NAME:-Admin Nasande Vins}"
fi

# Caches de production (config, routes, vues).
php artisan optimize

exec apache2-foreground
