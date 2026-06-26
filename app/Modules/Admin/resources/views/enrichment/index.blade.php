@extends('admin::layouts.app')

@section('title', 'Enrichissement IA')

@section('content')
    <h1>Enrichissement IA</h1>
    <p class="muted">Revue humaine obligatoire avant publication des contenus générés.</p>

    <div class="grid" style="margin:1.5rem 0;">
        <div class="card"><div class="stat">{{ $stats['awaiting_review'] }}</div><div class="muted">En attente de revue</div></div>
        <div class="card"><div class="stat">{{ $stats['approved'] }}</div><div class="muted">Approuvés</div></div>
        <div class="card"><div class="stat">{{ $stats['failed_jobs'] }}</div><div class="muted">Jobs échoués</div></div>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr><th>Produit</th><th>Marque</th><th>Confiance IA</th><th>Champs</th><th></th></tr>
            </thead>
            <tbody>
            @forelse ($products as $product)
                <tr>
                    <td>{{ $product->title }}</td>
                    <td>{{ $product->brand?->name ?? '—' }}</td>
                    <td>{{ $product->ai_confidence ?? '—' }}</td>
                    <td>{{ $product->enrichmentDrafts->count() }}</td>
                    <td><a href="{{ route('admin.enrichment.show', $product) }}">Revoir</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Aucun brouillon en attente.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $products->links() }}
    </div>
@endsection
