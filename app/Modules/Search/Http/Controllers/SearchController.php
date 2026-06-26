<?php

declare(strict_types=1);

namespace App\Modules\Search\Http\Controllers;

use App\Modules\Behavior\Application\VisitorSessionService;
use App\Modules\Search\Application\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $search,
        private VisitorSessionService $sessions,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $started = microtime(true);
        $products = $q !== '' ? $this->search->search($q, 48) : collect();
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        if ($q !== '') {
            $this->search->logSearch(
                query: $q,
                hitsCount: $products->count(),
                sessionId: $this->sessions->currentId() ?? $this->sessions->resolve()->id,
                durationMs: $durationMs,
            );
        }

        return view('search::index', [
            'query' => $q,
            'products' => $products,
            'count' => $products->count(),
        ]);
    }

    public function suggest(Request $request): View|Response
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response('', 204);
        }

        $products = $this->search->search($q, 5);

        return view('search::partials.suggest', [
            'query' => $q,
            'products' => $products,
        ]);
    }
}
