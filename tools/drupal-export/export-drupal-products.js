#!/usr/bin/env node
/**
 * Export complet des produits Drupal Commerce de https://nasande.cg
 *
 * Stratégie en 3 fetches indépendants (plus fiable que de tout inclure d'un coup) :
 *   1. /jsonapi/commerce_product/default          (+ taxonomies produit)
 *   2. /jsonapi/commerce_product_variation/default (+ field_image + attributs)
 *   3. (optionnel) re-fetch d'images orphelines via /jsonapi/file/file/{id}
 *
 * Sorties (dossier ./out) :
 *  - products.json      → tous les produits avec variations, prix, images, path alias
 *  - url-aliases.json   → liste compacte { drupal_id, title, path, canonical_url }
 *  - categories.json    → taxonomie product_category déduite des produits
 *  - brands.json        → taxonomie product_brand déduite des produits
 *  - images/            → toutes les images téléchargées localement
 *  - export-report.json → statistiques et erreurs
 *
 * Pas de dépendances externes : Node 18+ (fetch + WritableStream natifs).
 */

import fs from "node:fs";
import path from "node:path";
import { pipeline } from "node:stream/promises";
import { Readable } from "node:stream";

// ---------- Configuration ----------
const BASE_URL = process.env.BASE_URL || "https://nasande.cg";
const OUT_DIR = path.resolve(process.env.OUT_DIR || "./out");
const IMAGES_DIR = path.join(OUT_DIR, "images");
const PAGE_LIMIT = 50;
const IMAGE_CONCURRENCY = 8;
const FETCH_RETRIES = 3;
const VERBOSE = !!process.env.VERBOSE;

const HEADERS = {
  Accept: "application/vnd.api+json",
  "User-Agent": "nasande-export/1.0 (+offline migration)",
};

// ---------- Helpers ----------
fs.mkdirSync(IMAGES_DIR, { recursive: true });

function log(...args) {
  console.log(...args);
}
function vlog(...args) {
  if (VERBOSE) console.log(...args);
}

async function fetchJson(url, attempt = 1) {
  try {
    const res = await fetch(url, { headers: HEADERS });
    if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText} on ${url}`);
    return await res.json();
  } catch (err) {
    if (attempt < FETCH_RETRIES) {
      const wait = 500 * attempt;
      vlog(`  retry ${attempt}/${FETCH_RETRIES - 1} in ${wait}ms : ${err.message}`);
      await new Promise((r) => setTimeout(r, wait));
      return fetchJson(url, attempt + 1);
    }
    throw err;
  }
}

/** Récupère toutes les pages d'un endpoint JSON:API et accumule data + included. */
async function fetchAllPages(endpoint, params = {}) {
  const search = new URLSearchParams({ "page[limit]": String(PAGE_LIMIT), ...params });
  let url = `${BASE_URL}${endpoint}?${search.toString()}`;
  const allData = [];
  const includedById = new Map();
  let pageIdx = 0;

  while (url) {
    pageIdx += 1;
    const json = await fetchJson(url);
    const data = json.data || [];
    allData.push(...data);
    for (const inc of json.included || []) {
      includedById.set(`${inc.type}:${inc.id}`, inc);
    }
    log(`  ↳ page ${pageIdx} (${data.length} items, total ${allData.length})`);
    url = json.links?.next?.href || null;
  }
  return { data: allData, included: Array.from(includedById.values()) };
}

function makeIncludedIndex(included) {
  const idx = new Map();
  for (const inc of included) idx.set(`${inc.type}:${inc.id}`, inc);
  return idx;
}

function relData(rel) {
  if (!rel || !("data" in rel)) return null;
  return rel.data;
}

async function runWithConcurrency(items, worker, concurrency) {
  const results = new Array(items.length);
  let cursor = 0;
  const runners = Array.from({ length: Math.min(concurrency, items.length) }, async () => {
    while (true) {
      const i = cursor++;
      if (i >= items.length) return;
      try {
        results[i] = { ok: true, value: await worker(items[i], i) };
      } catch (err) {
        results[i] = { ok: false, error: err.message, item: items[i] };
      }
    }
  });
  await Promise.all(runners);
  return results;
}

function sanitizeFilename(name) {
  return name.replace(/[^A-Za-z0-9._-]+/g, "_").slice(0, 180);
}

async function downloadImage(absoluteUrl, destPath) {
  if (fs.existsSync(destPath) && fs.statSync(destPath).size > 0) {
    return { skipped: true, bytes: fs.statSync(destPath).size };
  }
  const res = await fetch(absoluteUrl, { headers: { "User-Agent": HEADERS["User-Agent"] } });
  if (!res.ok || !res.body) {
    throw new Error(`HTTP ${res.status} on ${absoluteUrl}`);
  }
  await pipeline(Readable.fromWeb(res.body), fs.createWriteStream(destPath));
  return { skipped: false, bytes: fs.statSync(destPath).size };
}

function absolutize(url) {
  if (!url) return null;
  if (/^https?:\/\//i.test(url)) return url;
  return `${BASE_URL}${url.startsWith("/") ? "" : "/"}${url}`;
}

function taxonomyTerm(inc) {
  if (!inc) return null;
  return {
    id: inc.id,
    type: inc.type,
    drupal_id: inc.attributes?.drupal_internal__tid,
    name: inc.attributes?.name,
    description: inc.attributes?.description?.value || null,
    path: inc.attributes?.path?.alias || null,
  };
}

function fileEntity(inc, imagesToDownload, meta = {}) {
  if (!inc) return null;
  const uri = inc.attributes?.uri?.url || inc.attributes?.uri?.value || null;
  const absolute = absolutize(uri);
  if (!absolute) return null;
  const filename = sanitizeFilename(
    `${inc.attributes?.drupal_internal__fid || inc.id}_${
      inc.attributes?.filename || path.basename(absolute)
    }`,
  );
  imagesToDownload.set(absolute, filename);
  return {
    drupal_id: inc.attributes?.drupal_internal__fid,
    filename: inc.attributes?.filename,
    filemime: inc.attributes?.filemime,
    filesize: inc.attributes?.filesize,
    width: meta.width ?? null,
    height: meta.height ?? null,
    alt: meta.alt ?? null,
    title: meta.title ?? null,
    original_url: absolute,
    local_file: `images/${filename}`,
  };
}

// ---------- Phase 1 : produits ----------
async function fetchProducts() {
  log("📦 Phase 1 : produits + taxonomies (brand, category, origine, age, level)...");
  const include = [
    "field_product_brand",
    "field_product_category",
    "field_origine",
    "field_age",
    "field_level",
  ].join(",");
  const { data, included } = await fetchAllPages("/jsonapi/commerce_product/default", {
    include,
  });
  log(`✔ ${data.length} produits, ${included.length} taxonomies incluses.`);
  return { products: data, productIncluded: makeIncludedIndex(included) };
}

// ---------- Phase 2 : variations + images ----------
async function fetchVariations(imagesToDownload) {
  log("🧩 Phase 2 : variations + images + attributs...");
  const include = ["field_image", "attribute_color", "attribute_size", "attribute_volume"].join(",");
  const { data, included } = await fetchAllPages(
    "/jsonapi/commerce_product_variation/default",
    { include },
  );
  log(`✔ ${data.length} variations, ${included.length} entités incluses (files + attrs).`);
  const variationIncluded = makeIncludedIndex(included);

  const variationById = new Map();
  for (const v of data) {
    const a = v.attributes || {};
    const r = v.relationships || {};

    // field_image est une relation 1..N → toujours un array (avec meta alt/width/height)
    const imgRefRaw = r.field_image?.data;
    const imgRefs = Array.isArray(imgRefRaw) ? imgRefRaw : imgRefRaw ? [imgRefRaw] : [];
    const images = imgRefs
      .map((ref) => {
        const inc = variationIncluded.get(`${ref.type}:${ref.id}`);
        return fileEntity(inc, imagesToDownload, ref.meta || {});
      })
      .filter(Boolean);

    const colorRef = relData(r.attribute_color);
    const sizeRef = relData(r.attribute_size);
    const volumeRef = relData(r.attribute_volume);

    variationById.set(v.id, {
      id: v.id,
      drupal_id: a.drupal_internal__variation_id,
      sku: a.sku,
      title: a.title,
      status: a.status,
      list_price: a.list_price,
      price: a.price,
      stock: a.field_stock ?? null,
      weight: a.weight ?? null,
      created: a.created,
      changed: a.changed,
      images,
      image: images[0] || null,
      attributes: {
        color: colorRef ? taxonomyTerm(variationIncluded.get(`${colorRef.type}:${colorRef.id}`)) : null,
        size: sizeRef ? taxonomyTerm(variationIncluded.get(`${sizeRef.type}:${sizeRef.id}`)) : null,
        volume: volumeRef
          ? taxonomyTerm(variationIncluded.get(`${volumeRef.type}:${volumeRef.id}`))
          : null,
      },
      product_id: relData(r.product_id)?.id || null,
    });
  }
  return variationById;
}

// ---------- Assemblage ----------
function assembleProducts(products, productIncluded, variationById) {
  const productsOut = [];
  const brands = new Map();
  const categories = new Map();
  const origines = new Map();

  for (const p of products) {
    const a = p.attributes || {};
    const r = p.relationships || {};

    const brand = taxonomyTerm(
      productIncluded.get(
        relData(r.field_product_brand)
          ? `${relData(r.field_product_brand).type}:${relData(r.field_product_brand).id}`
          : "",
      ),
    );
    if (brand?.id) brands.set(brand.id, brand);

    const categoryRefs = relData(r.field_product_category);
    const cats = (Array.isArray(categoryRefs) ? categoryRefs : categoryRefs ? [categoryRefs] : [])
      .map((ref) => taxonomyTerm(productIncluded.get(`${ref.type}:${ref.id}`)))
      .filter(Boolean);
    for (const c of cats) categories.set(c.id, c);

    const origine = taxonomyTerm(
      productIncluded.get(
        relData(r.field_origine)
          ? `${relData(r.field_origine).type}:${relData(r.field_origine).id}`
          : "",
      ),
    );
    if (origine?.id) origines.set(origine.id, origine);

    const age = taxonomyTerm(
      productIncluded.get(
        relData(r.field_age) ? `${relData(r.field_age).type}:${relData(r.field_age).id}` : "",
      ),
    );
    const level = taxonomyTerm(
      productIncluded.get(
        relData(r.field_level) ? `${relData(r.field_level).type}:${relData(r.field_level).id}` : "",
      ),
    );

    const variationRefs = relData(r.variations) || [];
    const variations = (Array.isArray(variationRefs) ? variationRefs : [variationRefs])
      .map((ref) => variationById.get(ref.id))
      .filter(Boolean);

    const defaultRef = relData(r.default_variation);
    const defaultVariationId = defaultRef?.id || null;

    // Dédup d'images entre variations (chaque variation peut en avoir plusieurs)
    const seenImg = new Set();
    const allImages = [];
    for (const v of variations) {
      for (const img of v.images || []) {
        if (img && !seenImg.has(img.original_url)) {
          seenImg.add(img.original_url);
          allImages.push(img);
        }
      }
    }

    productsOut.push({
      id: p.id,
      drupal_id: a.drupal_internal__product_id,
      langcode: a.langcode,
      title: a.title,
      status: a.status,
      created: a.created,
      changed: a.changed,
      path: a.path?.alias || null,
      canonical_url: a.path?.alias ? `${BASE_URL}${a.path.alias}` : null,
      featured: !!a.field_featured_product,
      body: {
        html: a.body?.value || null,
        summary: a.body?.summary || null,
        format: a.body?.format || null,
      },
      short_description: {
        html: a.field_short_description?.value || null,
        format: a.field_short_description?.format || null,
      },
      brand,
      categories: cats,
      origine,
      age,
      level,
      variations,
      default_variation_id: defaultVariationId,
      images: allImages,
      primary_image: allImages[0] || null,
    });
  }

  return {
    products: productsOut,
    brands: Array.from(brands.values()),
    categories: Array.from(categories.values()),
    origines: Array.from(origines.values()),
  };
}

// ---------- Téléchargement d'images ----------
async function downloadAllImages(imagesMap) {
  const entries = Array.from(imagesMap.entries());
  log(`🖼  Phase 3 : téléchargement de ${entries.length} images (concurrence ${IMAGE_CONCURRENCY})...`);
  let done = 0;
  let skipped = 0;
  let bytes = 0;
  const failures = [];

  const results = await runWithConcurrency(
    entries,
    async ([url, filename]) => {
      const dest = path.join(IMAGES_DIR, filename);
      const r = await downloadImage(url, dest);
      done += 1;
      if (r.skipped) skipped += 1;
      bytes += r.bytes;
      if (done % 25 === 0 || done === entries.length) {
        log(`  · ${done}/${entries.length} (${skipped} déjà présentes)`);
      }
      return r;
    },
    IMAGE_CONCURRENCY,
  );

  for (let i = 0; i < results.length; i += 1) {
    if (!results[i].ok)
      failures.push({ url: entries[i][0], file: entries[i][1], error: results[i].error });
  }
  if (failures.length) log(`⚠ ${failures.length} images en échec.`);
  return { downloaded: done, skipped, bytes, failures };
}

// ---------- Main ----------
async function main() {
  const t0 = Date.now();
  log(`🚀 Export depuis ${BASE_URL}`);
  log(`📂 Dossier de sortie : ${OUT_DIR}`);

  const imagesToDownload = new Map();

  const { products, productIncluded } = await fetchProducts();
  const variationById = await fetchVariations(imagesToDownload);

  log(`🧷 Assemblage de ${products.length} produits × ${variationById.size} variations...`);
  const { products: productsOut, brands, categories, origines } = assembleProducts(
    products,
    productIncluded,
    variationById,
  );

  fs.writeFileSync(path.join(OUT_DIR, "products.json"), JSON.stringify(productsOut, null, 2));
  fs.writeFileSync(path.join(OUT_DIR, "brands.json"), JSON.stringify(brands, null, 2));
  fs.writeFileSync(path.join(OUT_DIR, "categories.json"), JSON.stringify(categories, null, 2));
  fs.writeFileSync(path.join(OUT_DIR, "origines.json"), JSON.stringify(origines, null, 2));

  const aliases = productsOut.map((p) => ({
    drupal_id: p.drupal_id,
    id: p.id,
    title: p.title,
    path: p.path,
    canonical_url: p.canonical_url,
    status: p.status,
  }));
  fs.writeFileSync(path.join(OUT_DIR, "url-aliases.json"), JSON.stringify(aliases, null, 2));

  const imgStats = await downloadAllImages(imagesToDownload);

  // Marquer les images en échec (404 serveur) comme indisponibles dans le JSON final
  const failedUrls = new Set(imgStats.failures.map((f) => f.url));
  function markImage(img) {
    if (img && failedUrls.has(img.original_url)) {
      img.available = false;
      img.local_file = null;
    } else if (img) {
      img.available = true;
    }
    return img;
  }
  for (const p of productsOut) {
    p.images = p.images.map(markImage).filter(Boolean);
    p.primary_image = p.images.find((i) => i.available) || p.images[0] || null;
    for (const v of p.variations) {
      v.images = (v.images || []).map(markImage).filter(Boolean);
      v.image = v.images.find((i) => i.available) || v.images[0] || null;
    }
  }
  fs.writeFileSync(path.join(OUT_DIR, "products.json"), JSON.stringify(productsOut, null, 2));

  const report = {
    base_url: BASE_URL,
    generated_at: new Date().toISOString(),
    duration_ms: Date.now() - t0,
    counts: {
      products: productsOut.length,
      variations: productsOut.reduce((acc, p) => acc + p.variations.length, 0),
      products_without_image: productsOut.filter((p) => !p.primary_image).length,
      images_unique: imagesToDownload.size,
      images_available_locally: imgStats.downloaded,
      images_skipped_existing: imgStats.skipped,
      images_failed: imgStats.failures.length,
      brands: brands.length,
      categories: categories.length,
      origines: origines.length,
      total_bytes_images: imgStats.bytes,
    },
    image_failures: imgStats.failures,
  };
  fs.writeFileSync(path.join(OUT_DIR, "export-report.json"), JSON.stringify(report, null, 2));

  log("\n────────────── RÉSUMÉ ──────────────");
  log(JSON.stringify(report.counts, null, 2));
  log(`📁 Tout est dans : ${OUT_DIR}`);
  log(`⏱  Terminé en ${(report.duration_ms / 1000).toFixed(1)}s`);
}

main().catch((err) => {
  console.error("❌ Échec de l'export :", err);
  process.exit(1);
});
