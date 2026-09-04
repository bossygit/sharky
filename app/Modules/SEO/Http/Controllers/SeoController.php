<?php

declare(strict_types=1);

namespace App\Modules\SEO\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [];

        $urls[] = ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'];

        foreach (['/contact', '/a-propos', '/cgv', '/retours'] as $path) {
            $urls[] = ['loc' => url($path), 'changefreq' => 'monthly', 'priority' => '0.4'];
        }

        Product::query()
            ->where('status', 'published')
            ->whereNotNull('canonical_path')
            ->select('canonical_path', 'updated_at')
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (&$urls) {
                foreach ($chunk as $p) {
                    $urls[] = [
                        'loc' => url($p->canonical_path),
                        'lastmod' => optional($p->updated_at)->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        foreach (Category::query()->select('slug', 'updated_at')->cursor() as $c) {
            $urls[] = [
                'loc' => url('/product/'.$c->slug),
                'lastmod' => optional($c->updated_at)->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        foreach (Brand::query()->select('slug', 'updated_at')->cursor() as $b) {
            $urls[] = [
                'loc' => url('/brand/'.$b->slug),
                'lastmod' => optional($b->updated_at)->toIso8601String(),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $body = "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * llms.txt : guide pour les LLM crawlers (OpenAI, Anthropic, Perplexity, etc.).
     * Spec : https://llmstxt.org/
     */
    public function llms(): Response
    {
        $productCount = Product::where('status', 'published')->count();
        $brandCount = Brand::count();
        $categoryCount = Category::count();

        $body = "# nasande.cg\n\n";
        $body .= "> Boutique en ligne de parapharmacie, cosmétique et soin à Brazzaville (République du Congo).\n\n";
        $body .= "## À propos\n\n";
        $body .= "nasande.cg est une boutique en ligne basée à Brazzaville proposant {$productCount} produits ";
        $body .= 'de parapharmacie, cosmétique, soin, hygiène, maternité et bébé. ';
        $body .= "Catalogue de {$brandCount} marques et {$categoryCount} catégories.\n\n";
        $body .= "## Livraison et paiement\n\n";
        $body .= "- Livraison : Brazzaville uniquement.\n";
        $body .= "- Paiement : Airtel Money, MTN Mobile Money, à la livraison.\n";
        $body .= "- Devise : franc CFA d'Afrique centrale (XAF / FCFA).\n";
        $body .= "- Langue : français.\n\n";
        $body .= "## Ressources structurées\n\n";
        $body .= '- [Plan du site](sitemap.xml) — liste complète des URLs canoniques.'."\n";
        $body .= "- Les pages produit utilisent JSON-LD schema.org/Product.\n";
        $body .= "- Les pages utilisent un balisage sémantique HTML5 (article, nav, breadcrumb).\n";
        $body .= '- [À propos]('.url('/a-propos').") — boutique, livraison Brazzaville, paiement.\n";
        $body .= '- [Contact]('.url('/contact').") — téléphone et WhatsApp.\n";
        $body .= '- [CGV]('.url('/cgv').') et [retours]('.url('/retours').").\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
