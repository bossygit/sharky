<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Enrichment\Application\ApplyEnrichmentService;
use App\Modules\Enrichment\Domain\Models\EnrichmentDraft;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EnrichmentReviewController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->where('ai_enrichment_status', 'review')
            ->with(['brand', 'enrichmentDrafts' => fn ($q) => $q->whereIn('decision', ['pending', 'edited'])])
            ->latest('updated_at')
            ->paginate(20);

        $stats = [
            'awaiting_review' => Product::where('ai_enrichment_status', 'review')->count(),
            'approved' => Product::where('ai_enrichment_status', 'approved')->count(),
            'failed_jobs' => \App\Modules\Enrichment\Domain\Models\EnrichmentJob::where('status', 'failed')->count(),
        ];

        return view('admin::enrichment.index', compact('products', 'stats'));
    }

    public function show(Product $product): View
    {
        $product->load(['brand', 'images']);
        $drafts = EnrichmentDraft::query()
            ->where('product_id', $product->id)
            ->whereIn('decision', ['pending', 'edited'])
            ->orderBy('field')
            ->get();

        $job = \App\Modules\Enrichment\Domain\Models\EnrichmentJob::query()
            ->where('product_id', $product->id)
            ->latest('id')
            ->first();

        return view('admin::enrichment.show', compact('product', 'drafts', 'job'));
    }

    public function approve(Product $product, ApplyEnrichmentService $apply, Request $request): RedirectResponse
    {
        $apply->approveAll($product, (int) $request->user()?->id);

        return redirect()
            ->route('admin.enrichment.index')
            ->with('success', 'Enrichissement appliqué au produit.');
    }

    public function reject(Product $product, ApplyEnrichmentService $apply, Request $request): RedirectResponse
    {
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:2000']]);
        $apply->rejectAll($product, (int) $request->user()?->id, $data['notes'] ?? null);

        return redirect()
            ->route('admin.enrichment.index')
            ->with('success', 'Enrichissement rejeté.');
    }

    public function updateDraft(EnrichmentDraft $draft, ApplyEnrichmentService $apply, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'value' => ['required', 'string'],
        ]);

        $value = $draft->field === 'features' || $draft->field === 'faqs'
            ? json_decode($data['value'], true) ?? $data['value']
            : $data['value'];

        $apply->updateDraft($draft, $value, (int) $request->user()?->id);

        return back()->with('success', 'Proposition modifiée.');
    }
}
