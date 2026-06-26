<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Charge automatiquement les ressources (migrations, routes, vues) de chaque
 * module sous app/Modules/. Voir ARCHITECTURE.md §5.
 *
 * Conventions par module Foo :
 *   - database/migrations/modules/foo/*.php   ← migrations
 *   - app/Modules/Foo/routes/web.php          ← routes web (middleware 'web')
 *   - app/Modules/Foo/routes/api.php          ← routes api (middleware 'api')
 *   - app/Modules/Foo/resources/views/        ← vues Blade namespacées 'foo::'
 */
class ModuleServiceProvider extends ServiceProvider
{
    /** Ordre de chargement des routes web (les catch-all du catalogue en dernier). */
    private const WEB_ROUTE_ORDER = [
        'SEO',
        'Commerce',
        'Search',
        'Admin',
        'Catalog',
        'Behavior',
        'Demand',
        'Enrichment',
        'Observability',
    ];

    /** Liste des modules connus. Doit correspondre à app/Modules/. */
    private const MODULES = [
        'Catalog',
        'Search',
        'Behavior',
        'Demand',
        'Enrichment',
        'SEO',
        'Commerce',
        'Admin',
        'Observability',
    ];

    public function register(): void {}

    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleRoutes();
        $this->loadModuleViews();
        $this->loadModuleCommands();
    }

    private function loadModuleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }
        foreach (self::MODULES as $module) {
            $cmdDir = app_path("Modules/{$module}/Application");
            if (! is_dir($cmdDir)) {
                continue;
            }
            foreach (glob($cmdDir.'/*Command.php') ?: [] as $file) {
                $class = "App\\Modules\\{$module}\\Application\\".basename($file, '.php');
                if (class_exists($class) && is_subclass_of($class, \Illuminate\Console\Command::class)) {
                    $this->commands([$class]);
                }
            }
        }
    }

    private function loadModuleMigrations(): void
    {
        $base = database_path('migrations/modules');
        if (! is_dir($base)) {
            return;
        }
        foreach (scandir($base) ?: [] as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }
            $path = $base.DIRECTORY_SEPARATOR.$dir;
            if (is_dir($path)) {
                $this->loadMigrationsFrom($path);
            }
        }
    }

    private function loadModuleRoutes(): void
    {
        foreach (self::WEB_ROUTE_ORDER as $module) {
            $this->registerWebRoutes($module);
        }
        foreach (self::MODULES as $module) {
            $this->registerApiRoutes($module);
        }
    }

    private function registerWebRoutes(string $module): void
    {
        $webRoutes = app_path("Modules/{$module}/routes/web.php");
        if (is_file($webRoutes)) {
            Route::middleware('web')
                ->namespace("App\\Modules\\{$module}\\Http\\Controllers")
                ->group($webRoutes);
        }
    }

    private function registerApiRoutes(string $module): void
    {
        $apiRoutes = app_path("Modules/{$module}/routes/api.php");
        if (is_file($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api')
                ->namespace("App\\Modules\\{$module}\\Http\\Controllers")
                ->group($apiRoutes);
        }
    }

    private function loadModuleViews(): void
    {
        foreach (self::MODULES as $module) {
            $views = app_path("Modules/{$module}/resources/views");
            if (is_dir($views)) {
                $this->loadViewsFrom($views, strtolower($module));
            }
        }
    }
}
