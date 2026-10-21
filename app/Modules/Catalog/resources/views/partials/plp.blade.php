@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $products */
    /** @var \Illuminate\Support\Collection $categories */
    $sortOptions = [
        'selection' => 'Notre sélection',
        'nouveautes' => 'Nouveautés',
        'prix-asc' => 'Prix croissant',
        'prix-desc' => 'Prix décroissant',
    ];
    $pillUrl = fn (?string $slug = null) => route('wines.index', array_filter([
        'categorie' => $slug,
        'tri' => $sort !== 'selection' ? $sort : null,
    ]));
@endphp

<div class="plp-toolbar">
    <div class="filter-pills" aria-label="Filtrer par catégorie">
        <a class="filter-pill {{ ! $activeCategory ? 'is-active' : '' }}" href="{{ $pillUrl() }}">Tout</a>
        @foreach ($categories as $cat)
            <a class="filter-pill {{ $activeCategory && $activeCategory->id === $cat->id ? 'is-active' : '' }}"
               href="{{ $pillUrl($cat->slug) }}">{{ $cat->name }}</a>
        @endforeach
    </div>

    <form class="sort-form" method="GET" action="{{ route('wines.index') }}">
        @if ($activeCategory)
            <input type="hidden" name="categorie" value="{{ $activeCategory->slug }}">
        @endif
        <label for="tri">Trier</label>
        <select id="tri" name="tri" onchange="this.form.submit()">
            @foreach ($sortOptions as $value => $label)
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-outline-dark" type="submit" style="padding:.45rem .8rem;">OK</button></noscript>
    </form>
</div>

@if ($products->isEmpty())
    <div class="empty-state mt-2">
        <h2>Aucune bouteille dans cette sélection</h2>
        <p>Essayez une autre catégorie, ou revenez bientôt : la cave s'enrichit chaque mois.</p>
        <p><a class="link-arrow" href="{{ route('wines.index') }}">Voir toute la cave</a></p>
    </div>
@else
    <div class="wine-grid plp-grid">
        @foreach ($products as $product)
            @include('catalog::partials.product-card', ['product' => $product])
        @endforeach
    </div>
    <div class="plp-foot">
        {{ $products->links('catalog::partials.pagination') }}
    </div>
@endif
