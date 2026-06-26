<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Behavior\Application\BehaviorTracker;
use App\Modules\Catalog\Domain\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackingMiddleware
{
    public function __construct(private BehaviorTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldTrack($request)) {
            return $response;
        }

        $productId = $this->resolveProductId($request);
        $type = $productId ? 'product_viewed' : 'page_view';

        $this->tracker->track(
            type: $type,
            productId: $productId,
            payload: [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
            ],
            url: $request->fullUrl(),
        );

        return $response;
    }

    private function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }
        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }
        if (str_starts_with($request->path(), 'admin')) {
            return false;
        }
        if (in_array($request->path(), ['up', 'webhooks'], true) || str_starts_with($request->path(), 'webhooks/')) {
            return false;
        }

        return true;
    }

    private function resolveProductId(Request $request): ?int
    {
        $route = $request->route()?->getName();
        if (! in_array($route, ['product.legacy.short', 'product.legacy.full', 'product.canonical'], true)) {
            return null;
        }

        if ($route === 'product.canonical') {
            $slug = $request->route('slug');

            return Product::query()->where('slug', $slug)->value('id');
        }

        $drupalId = $request->route('drupalId');

        return Product::query()->where('legacy_drupal_id', $drupalId)->value('id');
    }
}
