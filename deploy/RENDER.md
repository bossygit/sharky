# Nasande Vins — déploiement Render

Service web Laravel + base PostgreSQL, déployés depuis la branche `feat/nasande-vins`.

## État du déploiement (21/10/2026)

- **URL** : https://nasande-vins.onrender.com
- **Service** : `srv-dbc89k1srm7s73fvlevg` — Docker, plan gratuit, région Virginie
- **Base** : base `nasande_vins` dans l'instance `epsv-db`, rôle dédié `nasande_user`
- **Branche déployée** : `feat/nasande-vins`
- **Admin** : `admin@nasande-vins.cg` (mot de passe généré, communiqué hors dépôt)

> Le plan gratuit met le service en veille après inactivité (premier chargement ~30 s)
> et la base gratuite `epsv-db` **expire le 13 novembre 2026**. Pour une boutique en
> production, passer le service en `starter` et la base en plan payant avant cette date.

## 1. Ce que crée le blueprint

- `nasande-vins-db` : PostgreSQL (plan gratuit pour la démo, à passer en payant pour la production).
- `nasande-vins` : service web PHP, branché sur `feat/nasande-vins`, qui exécute les migrations et le
  seeding du catalogue à chaque démarrage (idempotent).

Le blueprint `render.yaml` est à la racine du dépôt. Il peut être appliqué depuis le dashboard
Render (**New → Blueprint**) ou recréé via la CLI (voir plus bas).

## 2. Variables d'environnement

| Clé | Valeur |
|---|---|
| `APP_NAME` | `Nasande Vins` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | générée par Render (`generateValue`) |
| `APP_URL` | `https://nasande-vins.onrender.com` (ou le domaine final) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | connection string Render (`fromDatabase`) |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `SCOUT_DRIVER` | `database` |
| `SCOUT_QUEUE` | `false` |
| `MAIL_MAILER` | `log` (à remplacer par `smtp` + identifiants) |
| `LOG_CHANNEL` | `stderr` |

### Envoi d'emails

`MAIL_MAILER=log` évite qu'une panne SMTP bloque une commande. Pour activer les confirmations :

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.exemple.cg
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@nasande-vins.cg
MAIL_FROM_NAME="Nasande Vins"
```

Le code de commande est déjà tolérant : si l'email échoue, la commande reste enregistrée et
l'erreur est journalisée (`storage/logs` / flux Render).

## 3. Créer le service via la CLI Render

Render ne propose pas de runtime PHP natif : le service est construit depuis le `Dockerfile`
(PHP 8.3 + Apache), avec le `PORT` fourni par Render.

```bash
# Base PostgreSQL (plan gratuit pour la démo)
render postgres create --name nasande-vins-db --plan free --region frankfurt --confirm

# Service web Docker
render services create \
  --name nasande-vins \
  --type web_service \
  --repo https://github.com/bossygit/sharky \
  --branch feat/nasande-vins \
  --runtime docker \
  --plan free \
  --region frankfurt \
  --health-check-path /up \
  --env-var APP_NAME="Nasande Vins" \
  --env-var APP_ENV=production \
  --env-var APP_DEBUG=false \
  --env-var APP_URL=https://nasande-vins.onrender.com \
  --env-var DB_CONNECTION=pgsql \
  --env-var DB_SSLMODE=require \
  --env-var SESSION_DRIVER=database \
  --env-var SESSION_SECURE_COOKIE=true \
  --env-var CACHE_STORE=database \
  --env-var QUEUE_CONNECTION=sync \
  --env-var SCOUT_DRIVER=database \
  --env-var SCOUT_QUEUE=false \
  --env-var MAIL_MAILER=log \
  --env-var LOG_CHANNEL=stderr \
  --confirm
```

Puis renseigner `APP_KEY` et `DB_URL` (dashboard → Environment) :

```bash
php artisan key:generate --show          # en local, copier la valeur
# DB_URL : dashboard Render → base → Connect → Internal Database URL
```

## 4. Premier administrateur

Le compte admin est créé au démarrage du conteneur par `docker/start.sh`, à partir des
variables `ADMIN_EMAIL`, `ADMIN_PASSWORD` et `ADMIN_NAME` (idempotent : le compte n'est
pas modifié s'il existe déjà). Ces variables sont positionnées dans Render, jamais dans
le dépôt.

Pour créer un autre admin ou réinitialiser un mot de passe :

```bash
php artisan vins:admin autre@exemple.cg --name="Prénom Nom"
php artisan vins:admin admin@nasande-vins.cg --force   # nouveau mot de passe généré
```

Sur Render, exécuter ces commandes via le Shell du dashboard (SSH nécessite d'ajouter
votre clé publique au compte Render).

## 5. Vérifications après déploiement

```bash
curl -I https://nasande-vins.onrender.com/            # 200
curl -I https://nasande-vins.onrender.com/vins         # 200
curl -I https://nasande-vins.onrender.com/up           # 200
```

Parcours à tester dans le navigateur :

1. accueil, hero, carrousels et images ;
2. `/vins` + filtres de catégorie et tri ;
3. fiche produit → ajout au panier ;
4. panier → checkout → commande enregistrée (`/commande/{uuid}`) ;
5. connexion admin (`/admin/login`) → produit → « Détails vin » → enregistrer.

## 6. Domaine et montée en charge

- **Dépôt public** : Render a été connecté via l'URL publique du dépôt, sans GitHub App.
  Si le dépôt repasse en privé, les déploiements échoueront : installer alors la GitHub App
  Render sur le dépôt (Dashboard → Account Settings → GitHub).
- **Auto-deploy** : à vérifier après un push ; sinon déclencher manuellement :
  `render deploys create srv-dbc89k1srm7s73fvlevg --confirm`.
- **Domaine** : Render → Settings → Custom Domains, puis `vins.nasande.cg` (CNAME vers l'URL Render).
  Mettre à jour `APP_URL` et conserver `SESSION_SECURE_COOKIE=true`.
- **Plan** : le plan gratuit met le service en veille et la base gratuite expire le 13/11/2026.
  Pour une boutique réelle, passer le service en `starter` et la base en `0.5c-1g` minimum.
- **Fichiers** : le catalogue utilise des SVG versionnés dans `public/images/vins` (aucun stockage
  persistant requis). Les photos uploadées depuis l'admin vont dans `storage/app/public` : sur Render,
  ajouter un disque persistant monté sur `/var/www/html/storage/app/public` ou brancher S3.
- **File d'attente** : `QUEUE_CONNECTION=sync` suffit pour ce storefront. Pour l'enrichissement IA,
  créer un worker `php artisan queue:work --tries=3` en service background.
