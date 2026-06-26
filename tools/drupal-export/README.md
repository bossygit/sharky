# Export Drupal Commerce — nasande.cg

Script d'export **complet et reproductible** des produits, variations, prix et images
du site Drupal Commerce <https://nasande.cg> vers un format JSON exploitable pour
reconstruire un nouveau site (Next.js, Nuxt, Astro, etc.) **en gardant exactement
les mêmes URLs**.

## 📊 Résultat de l'export actuel (`out/`)

| Élément                          | Quantité     |
| -------------------------------- | ------------ |
| Produits                         | **741**      |
| Variations (SKU + prix)          | **756**      |
| Images référencées               | **753**      |
| Images téléchargées localement   | **671** (93 Mo) |
| Images orphelines côté serveur (404) | **82**   |
| Marques (taxonomie)              | **70**       |
| Catégories                       | **47**       |

Les 82 images en 404 sont **réellement absentes du serveur Drupal** (références
en base mais fichiers physiques perdus). Elles sont marquées `available: false`
dans `products.json` — le nouveau site peut les ignorer ou afficher un placeholder.

## 1. Pré-requis

- Node.js ≥ 18 (le projet utilise le `fetch` natif). Testé avec Node 26.
- Aucune dépendance npm : le script est volontairement *standalone*.

## 2. Lancer l'export

```bash
npm run export
# ou en mode verbeux (logs de retry HTTP)
npm run export:verbose
```

Variables d'environnement utiles :

| Variable      | Défaut                | Description           |
| ------------- | --------------------- | --------------------- |
| `BASE_URL`    | `https://nasande.cg`  | Domaine source        |
| `OUT_DIR`     | `./out`               | Dossier de sortie     |
| `VERBOSE`     | *(off)*               | Logs détaillés        |

Le script est **idempotent** : relancer ne re-télécharge pas les images déjà
présentes, et écrase proprement les JSON.

## 3. Fichiers produits

Tout va dans `./out/` :

| Fichier                | Contenu                                                                |
| ---------------------- | ---------------------------------------------------------------------- |
| `products.json`        | Tous les produits avec variations, prix, images, brand, catégories     |
| `url-aliases.json`     | Liste compacte `{drupal_id, id, title, path, canonical_url}` pour le routage |
| `brands.json`          | Marques uniques (taxonomie `product_brand`)                            |
| `categories.json`      | Catégories uniques (taxonomie `product_category`)                      |
| `origines.json`        | Origines (taxonomie `origine`)                                         |
| `images/`              | Toutes les images téléchargées (nommées `<fid>_<filename>`)            |
| `export-report.json`   | Statistiques + liste détaillée des 404 images                          |

### Schéma d'un produit (`products.json`)

```jsonc
{
  "id": "72d19fe9-b73b-49e5-8b6f-6e68bb843d39", // UUID Drupal
  "drupal_id": 25,                               // ID interne (utilisé dans l'URL)
  "langcode": "fr",
  "title": "Acm Novophane Lotion Antichute",
  "status": true,
  "created": "2020-07-04T06:34:47+00:00",
  "changed": "2020-08-14T11:25:26+00:00",
  "path": "/25/acm-novophane-lotion-antichute",      // ← URL EXACTE à conserver
  "canonical_url": "https://nasande.cg/25/acm-novophane-lotion-antichute",
  "featured": false,
  "body": { "html": "<p>...</p>", "summary": null, "format": "basic_html" },
  "short_description": { "html": "<p>...</p>", "format": "basic_html" },
  "brand":      { "id": "...", "drupal_id": 50, "name": "ACM",   "path": null },
  "categories": [ { "id": "...", "drupal_id": 12, "name": "Cheveux", "path": null } ],
  "origine": null,
  "age": null,
  "level": null,
  "variations": [
    {
      "id": "a460c392-9deb-45ad-9a7e-c9cbf4076250",
      "drupal_id": 35,
      "sku": "9000349",
      "title": "Acm Novophane Lotion Antichute",
      "status": true,
      "price":      { "number": "12500.000000", "currency_code": "XAF", "formatted": "12 500 FCFA" },
      "list_price": null,
      "stock": 10,
      "weight": { "number": "100.000000", "unit": "g" },
      "image":  { /* premier élément de "images" pour compatibilité */ },
      "images": [
        {
          "drupal_id": 484,
          "filename": "novophane-lotion-anti-chute.png",
          "filemime": "image/png",
          "filesize": 203463,
          "width": 800,
          "height": 800,
          "alt": "accm",
          "title": "",
          "original_url": "https://nasande.cg/sites/default/files/products/novophane-lotion-anti-chute.png",
          "local_file": "images/484_novophane-lotion-anti-chute.png",
          "available": true
        }
      ],
      "attributes": { "color": null, "size": null, "volume": null },
      "product_id": "72d19fe9-b73b-49e5-8b6f-6e68bb843d39"
    }
  ],
  "default_variation_id": "a460c392-9deb-45ad-9a7e-c9cbf4076250",
  "images": [ /* toutes les images des variations, dédoublonnées */ ],
  "primary_image": { /* première image disponible */ }
}
```

### Format des URLs produit

Drupal sert les produits sur des paths du type :

```
/{drupal_id}/{slug}                            ex. /25/acm-novophane-lotion-antichute
/{drupal_id}/{brand_id}/{slug}                 ex. /38/143/bioderma-atoderm-pp-baume
```

→ Le champ `path` contient la valeur exacte à utiliser pour le routage.

## 4. Conserver les mêmes URLs dans le nouveau site

### 4.1 Next.js — App Router avec catch-all route

```ts
// app/[...slug]/page.tsx
import { notFound } from "next/navigation";
import aliases from "@/data/url-aliases.json";
import products from "@/data/products.json";

export async function generateStaticParams() {
  return aliases.map((a) => ({
    slug: a.path.replace(/^\//, "").split("/"),
  }));
}

export default function ProductPage({ params }: { params: { slug: string[] } }) {
  const path = "/" + params.slug.join("/");
  const product = products.find((p) => p.path === path);
  if (!product) notFound();
  return <ProductView data={product} />;
}
```

### 4.2 Variante DB-seed

Crée une table `products` avec la colonne `path` indexée :

```sql
CREATE TABLE products (
  id           uuid PRIMARY KEY,
  drupal_id    int  UNIQUE,
  path         text UNIQUE NOT NULL,        -- ex. /25/acm-novophane-lotion-antichute
  title        text NOT NULL,
  body_html    text,
  brand_id     uuid,
  data         jsonb NOT NULL              -- le produit complet
);
```

Puis seed avec :

```js
import products from "./out/products.json" assert { type: "json" };
for (const p of products) {
  await db.products.upsert({ where: { id: p.id }, create: {
    id: p.id, drupal_id: p.drupal_id, path: p.path, title: p.title,
    body_html: p.body.html, data: p,
  }});
}
```

### 4.3 Sitemap & robots.txt

`url-aliases.json` permet de régénérer un sitemap XML proprement :

```js
const urls = aliases.filter(a => a.status).map(a => `<url><loc>https://nasande.cg${a.path}</loc></url>`);
```

## 5. Gestion des images

- Toutes les images sont dans `out/images/` avec un nom `<fid>_<filename>` (unique).
- Le champ `local_file` pointe vers `images/...` relatif à `out/`.
- Les images en **404 serveur** sont marquées `available: false` et `local_file: null`
  dans `products.json`. La liste détaillée est dans `export-report.json` → `image_failures`.

Pour le nouveau site :

1. Copier `out/images/` vers `public/images/` (ou push sur Supabase Storage / Cloudinary).
2. Si tu changes de host, réécrire les `local_file` par un mapping simple.
3. Au rendu, ignorer les images `available: false` ou afficher un placeholder.

## 6. Checklist avant bascule DNS

- [x] `npm run export` se termine sans erreur fatale.
- [x] `export-report.json` lu — vérifier la liste `image_failures` (82 fichiers).
- [ ] Importer `products.json` dans la nouvelle DB.
- [ ] Uploader `out/images/` vers le storage du nouveau site.
- [ ] Tester quelques URLs de `url-aliases.json` sur le nouveau site (en preview).
- [ ] Mettre l'ancien site sur `old.nasande.cg` (sous-domaine) en backup.
- [ ] Régénérer le sitemap XML à partir de `url-aliases.json`.
- [ ] Bascule DNS → nouveau site.

## 7. Structure du projet

```
.
├── README.md                    ← ce fichier
├── package.json
├── export-drupal-products.js    ← script d'export (Node 18+)
└── out/                         ← généré par npm run export
    ├── products.json            ← 741 produits complets
    ├── url-aliases.json         ← 741 mappings URL
    ├── brands.json              ← 70 marques
    ├── categories.json          ← 47 catégories
    ├── origines.json
    ├── export-report.json
    └── images/                  ← 671 images (93 Mo)
```
