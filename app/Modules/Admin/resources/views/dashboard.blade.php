@extends('admin::layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <h1>Tableau de bord</h1>
    <p class="muted">Vue d'ensemble du catalogue</p>

    <div class="grid" style="margin:1.5rem 0;">
        <div class="card"><div class="stat">{{ $stats['products_total'] }}</div><div class="muted">Produits total</div></div>
        <div class="card"><div class="stat">{{ $stats['products_published'] }}</div><div class="muted">Publiés</div></div>
        <div class="card"><div class="stat">{{ $stats['products_draft'] }}</div><div class="muted">Brouillons</div></div>
        <div class="card"><div class="stat">{{ $stats['products_backoffice'] }}</div><div class="muted">Créés via admin</div></div>
        <div class="card"><div class="stat">{{ $stats['products_no_image'] }}</div><div class="muted">Publiés sans image</div></div>
        <div class="card"><div class="stat">{{ $stats['products_no_description'] }}</div><div class="muted">Sans description</div></div>
    </div>

    <div class="card">
        <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
            <h2 style="margin:0;">Brouillons récents</h2>
            <a class="btn btn-primary" href="{{ route('admin.products.create') }}">+ Nouveau produit</a>
        </div>
        @if ($recentDrafts->isEmpty())
            <p class="muted">Aucun brouillon backoffice.</p>
        @else
            <table>
                <thead><tr><th>Titre</th><th>Créé</th><th></th></tr></thead>
                <tbody>
                @foreach ($recentDrafts as $p)
                    <tr>
                        <td>{{ $p->title }}</td>
                        <td>{{ $p->created_at?->diffForHumans() }}</td>
                        <td><a href="{{ route('admin.products.edit', $p) }}">Éditer</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
