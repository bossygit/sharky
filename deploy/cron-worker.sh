#!/usr/bin/env bash
# cron-worker.sh — Garde un worker de queue Laravel vivant SANS Supervisor.
# Pour hébergements mutualisés où Supervisor n'est pas disponible.
#
# Installer dans crontab (toutes les minutes) :
#   * * * * * /chemin/absolu/vers/nasande/deploy/cron-worker.sh >> /dev/null 2>&1
#
# Le script ne démarre un worker QUE s'il n'en tourne pas déjà un.
set -euo pipefail

DEPLOY_PATH="/home/nasacdka/nasande-site"   # racine Laravel sur le serveur Planethoster
cd "$DEPLOY_PATH"

if pgrep -f "artisan queue:work database" >/dev/null 2>&1; then
  exit 0   # worker déjà actif
fi

# Pas de worker -> on en lance un en arrière-plan (nohup pour survivre au cron)
# PHP 8.3 explicite (ea-php83) car le PATH par défaut résout parfois vers ea-php73.
PHP_BIN="/opt/cpanel/ea-php83/root/usr/bin/php"
nohup "$PHP_BIN" artisan queue:work database --queue=default --tries=3 --max-time=3500 --sleep=3 --timeout=300 \
  >> "$DEPLOY_PATH/storage/logs/worker.log" 2>&1 &
