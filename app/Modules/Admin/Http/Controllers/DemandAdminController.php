<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Behavior\Domain\Models\VisitorSession;
use App\Modules\Demand\Domain\Models\DemandCluster;
use App\Modules\Demand\Domain\Models\DemandSignal;
use App\Modules\Search\Domain\Models\SearchLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class DemandAdminController extends Controller
{
    public function index(): View
    {
        $clusters = DemandCluster::query()
            ->with(['signals' => fn ($q) => $q->where('status', 'open')->orderByDesc('frequency_7d')])
            ->get()
            ->filter(fn (DemandCluster $c) => $c->signals->isNotEmpty())
            ->sortByDesc(fn (DemandCluster $c) => $c->frequency7d() * $c->urgencyScore())
            ->take(50)
            ->values();

        $stats = [
            'open_signals' => DemandSignal::where('status', 'open')->count(),
            'new_clusters_week' => DemandCluster::where('created_at', '>=', now()->subDays(7))->count(),
            'viral_unsourced' => DemandSignal::where('status', 'open')->where('urgency', 'viral')->count(),
            'zero_searches_7d' => SearchLog::where('hits_count', 0)
                ->where('occurred_at', '>=', now()->subDays(7))
                ->count(),
        ];

        return view('admin::demand.index', compact('clusters', 'stats'));
    }

    public function show(DemandCluster $cluster): View
    {
        $cluster->load(['signals' => fn ($q) => $q->orderByDesc('frequency_7d')]);

        $keywords = $cluster->signals->pluck('keyword')->all();

        $recentLogs = SearchLog::query()
            ->where('hits_count', 0)
            ->whereIn('query_normalized', $keywords)
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        $sessionIds = $recentLogs->pluck('session_id')->filter()->unique()->values();
        $sessions = VisitorSession::query()
            ->whereIn('id', $sessionIds)
            ->get()
            ->keyBy('id');

        return view('admin::demand.show', compact('cluster', 'recentLogs', 'sessions'));
    }

    public function source(DemandCluster $cluster): RedirectResponse
    {
        DemandSignal::query()
            ->where('cluster_id', $cluster->id)
            ->update(['status' => 'sourced']);

        return back()->with('success', 'Cluster marqué comme sourcé.');
    }

    public function reject(DemandCluster $cluster, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DemandSignal::query()
            ->where('cluster_id', $cluster->id)
            ->update([
                'status' => 'rejected',
                'notes' => $data['notes'] ?? null,
            ]);

        return redirect()
            ->route('admin.demand.index')
            ->with('success', 'Cluster rejeté.');
    }

    public function refresh(): RedirectResponse
    {
        Artisan::call('demand:project-clusters');

        return back()->with('success', 'Projection demande recalculée.');
    }
}
