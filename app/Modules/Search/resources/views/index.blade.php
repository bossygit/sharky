@extends('catalog::layouts.app')

@section('title', ($query ? 'Recherche : '.$query : 'Recherche').' | '.config('app.name'))

@section('content')
<section class="container" style="padding:2rem 0;">
    <h1>Recherche</h1>

    <form method="GET" action="{{ route('search') }}" role="search" style="margin:1rem 0;">
        <label for="q" class="sr-only">Rechercher un produit</label>
        <input id="q" name="q" type="search" value="{{ $query }}" placeholder="Ex : crème solaire, ACM, bébé…"
               style="width:100%; max-width:480px; padding:.65rem .75rem; border:1px solid var(--c-border); border-radius:8px;"
               autocomplete="off">
    </form>

    @if ($query === '')
        <p class="muted">Saisissez un terme pour explorer le catalogue.</p>
    @elseif ($count === 0)
        <p>Aucun résultat pour « {{ $query }} ». Essayez un autre mot-clé.</p>
    @else
        <p class="muted">{{ $count }} résultat{{ $count > 1 ? 's' : '' }} pour « {{ $query }} »</p>
        <div class="product-grid" style="margin-top:1.5rem;">
            @foreach ($products as $product)
                @include('catalog::partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</section>
@endsection
