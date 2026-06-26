<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application;

use App\Modules\Catalog\Domain\Models\Brand;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

/**
 * Vérifie en interne que chaque URL canonique des produits / catégories / marques
 * publiés répond bien 200. Utile en CI et après import Drupal pour s'assurer
 * que toutes les URLs legacy sont préservées (objectif SEO #1).
 *
 *   php artisan catalog:audit-urls
 *   php artisan catalog:audit-urls --limit=50
 *   php artisan catalog:audit-urls --stop-on-first-error
 */
class AuditUrlsCommand extends Command
{
    protected $signature = 'catalog:audit-urls
        {--limit= : Tester uniquement N produits (échantillon)}
        {--stop-on-first-error : Arrêter au premier échec}';

    protected $description = 'Vérifie que toutes les URLs canoniques répondent 200';

    public function handle(): int
    {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        $urls = [];
        $urls[] = ['/', 'home'];

        $q = Product::query()
            ->where('status', 'published')
            ->whereNotNull('canonical_path')
            ->orderBy('id');
        if ($limit = $this->option('limit')) {
            $q->limit((int) $limit);
        }
        foreach ($q->cursor() as $p) {
            $urls[] = [$p->canonical_path, 'product#'.$p->id];
        }

        foreach (Category::query()->select('slug')->cursor() as $c) {
            $urls[] = ['/product/'.$c->slug, 'category#'.$c->slug];
        }

        foreach (Brand::query()->select('slug')->cursor() as $b) {
            $urls[] = ['/brand/'.$b->slug, 'brand#'.$b->slug];
        }

        $this->info('Audit de '.count($urls).' URLs…');
        $bar = $this->output->createProgressBar(count($urls));

        $errors = [];
        $redirects = 0;
        $ok = 0;
        foreach ($urls as [$url, $label]) {
            $request = Request::create($url, 'GET');
            $response = $kernel->handle($request);
            $status = $response->getStatusCode();

            if ($status === 200) {
                $ok++;
            } elseif (in_array($status, [301, 302], true)) {
                $redirects++;
            } else {
                $errors[] = ['url' => $url, 'label' => $label, 'status' => $status];
                if ($this->option('stop-on-first-error')) {
                    $bar->finish();
                    $this->newLine(2);
                    $this->renderErrors($errors);
                    return self::FAILURE;
                }
            }
            $kernel->terminate($request, $response);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine(2);

        $this->info("✓ {$ok} URLs OK   ⤴ {$redirects} redirections   ✗ ".count($errors).' erreurs');

        if ($errors) {
            $this->renderErrors($errors);
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function renderErrors(array $errors): void
    {
        $this->error('URLs en échec :');
        foreach (array_slice($errors, 0, 30) as $e) {
            $this->line(sprintf('  [%d] %s  (%s)', $e['status'], $e['url'], $e['label']));
        }
        if (count($errors) > 30) {
            $this->line('  … ('.(count($errors) - 30).' autres)');
        }
    }
}
