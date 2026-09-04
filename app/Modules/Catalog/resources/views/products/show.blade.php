@extends('catalog::layouts.app')

@php
    /** @var \App\Modules\Catalog\Domain\Models\Product $product */
    /** @var \App\Modules\Catalog\Domain\Models\Variant|null $variant */
    $primaryImage = $product->primaryImage();
    $images = $product->images->where('is_available', true);
    $title = trim(($product->meta_title ?: $product->title).' | '.config('app.name'));
    $metaDesc = $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $product->short_description_html ?: (string) $product->description_html), 160);
@endphp

@section('title', $title)
@section('meta_description', $metaDesc)
@section('canonical', url($product->canonical_path))
@section('og_type', 'product')
@section('og_title', $product->title)
@section('og_description', $metaDesc)
@if ($primaryImage)
    @section('og_image', $primaryImage->url)
@endif

@section('breadcrumb')
    <nav class="container breadcrumb" aria-label="Fil d'Ariane">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="{{ route('home') }}"><span itemprop="name">Accueil</span></a>
                <meta itemprop="position" content="1">
            </li>
            @if ($firstCat = $product->categories->first())
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a itemprop="item" href="/product/{{ $firstCat->slug }}"><span itemprop="name">{{ $firstCat->name }}</span></a>
                    <meta itemprop="position" content="2">
                </li>
            @endif
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                <span itemprop="name">{{ $product->title }}</span>
                <meta itemprop="position" content="{{ $product->categories->isNotEmpty() ? 3 : 2 }}">
            </li>
        </ol>
    </nav>
@endsection

@section('content')
    <article class="container product-detail" itemscope itemtype="https://schema.org/Product">

        <div class="product-gallery">
            @if ($primaryImage)
                <img src="{{ $primaryImage->url }}"
                     srcset="{{ $primaryImage->srcset() }}"
                     sizes="{{ $primaryImage->sizes('detail') }}"
                     alt="{{ $primaryImage->alt_text ?: $product->title }}"
                     width="{{ $primaryImage->width ?: 600 }}"
                     height="{{ $primaryImage->height ?: 600 }}"
                     itemprop="image" fetchpriority="high">
            @endif
            @if ($images->count() > 1)
                <div class="thumbs" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; margin-top: .5rem;">
                    @foreach ($images->take(8) as $img)
                        <img src="{{ $img->url }}" srcset="{{ $img->srcset() }}" sizes="120px"
                             alt="{{ $img->alt_text ?: $product->title }}"
                             loading="lazy" decoding="async"
                             style="aspect-ratio: 1; object-fit: contain; background: #fafafa; border: 1px solid var(--c-border); border-radius: var(--r-sm); padding: .25rem;">
                    @endforeach
                </div>
            @endif
        </div>

        <div class="product-meta">
            @if ($product->brand)
                <p class="brand-name" itemprop="brand" itemscope itemtype="https://schema.org/Brand">
                    <span itemprop="name">{{ $product->brand->name }}</span>
                </p>
            @endif

            <h1 itemprop="name">{{ $product->title }}</h1>

            @if ($variant && $variant->sku)
                <p style="color: var(--c-muted); font-size: .9rem;">
                    Référence : <span itemprop="sku">{{ $variant->sku }}</span>
                </p>
            @endif

            @if ($product->short_description_html)
                <div class="short-description" itemprop="description">
                    {!! $product->short_description_html !!}
                </div>
            @endif

            @if ($variant)
                <div class="price-block" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span itemprop="price" content="{{ $variant->price_minor }}">{{ $variant->formatted_price }}</span>
                    <meta itemprop="priceCurrency" content="{{ $variant->currency_code }}">
                    <meta itemprop="availability" content="https://schema.org/{{ $variant->availability() }}">
                    <meta itemprop="url" content="{{ url($product->canonical_path) }}">
                    @if ($variant->isInStock())
                        <span class="badge">En stock</span>
                    @else
                        <span class="badge out">Indisponible</span>
                    @endif
                </div>

                @if ($variant->isInStock())
                    <form method="POST" action="{{ route('cart.add') }}" style="margin-top:1rem;">
                        @csrf
                        <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                        <label for="qty" style="font-size:.9rem;">Quantité</label>
                        <input id="qty" type="number" name="quantity" value="1" min="1" max="99" style="width:4rem; margin:0 .5rem;">
                        <button type="submit" style="padding:.65rem 1.25rem; background:var(--c-primary); color:#fff; border:0; border-radius:8px; cursor:pointer;">
                            Ajouter au panier
                        </button>
                    </form>
                @endif
            @endif

            @php
                $contact = \App\Modules\Commerce\Application\StorefrontContact::current();
            @endphp
            @if ($contact->hasWhatsapp())
                <p style="margin-top:1.25rem;">
                    <a class="btn-whatsapp" href="{{ $contact->whatsappHref('Bonjour, je souhaite des infos sur : '.$product->title.' '.url($product->canonical_path)) }}">
                        Commander ou poser une question sur WhatsApp
                    </a>
                </p>
            @endif

            @if ($product->description_html)
                <section style="margin-top: 2rem;">
                    <h2>Description</h2>
                    <div class="long-description">
                        {!! $product->description_html !!}
                    </div>
                </section>
            @endif

            @if ($product->categories->isNotEmpty())
                <p style="margin-top: 1.5rem; color: var(--c-muted); font-size: .9rem;">
                    Catégories :
                    @foreach ($product->categories as $c)
                        <a href="/product/{{ $c->slug }}">{{ $c->name }}</a>{{ ! $loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            @endif
        </div>
    </article>
@endsection

@push('jsonld')
    <script type="application/ld+json">
    @php
        $jsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'sku' => $variant?->sku,
            'url' => url($product->canonical_path),
            'description' => $metaDesc,
        ];
        if ($product->brand) {
            $jsonld['brand'] = ['@type' => 'Brand', 'name' => $product->brand->name];
        }
        if ($primaryImage) {
            $jsonld['image'] = $images->pluck('url')->filter()->values()->all();
        }
        if ($variant) {
            $jsonld['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => $variant->currency_code,
                'price' => (string) $variant->price_minor,
                'availability' => 'https://schema.org/'.$variant->availability(),
                'url' => url($product->canonical_path),
                'seller' => ['@type' => 'Organization', 'name' => 'nasande.cg'],
            ];
        }
    @endphp
    {!! json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    {{-- BreadcrumbList JSON-LD --}}
    <script type="application/ld+json">
    @php
        $breadcrumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')]];
        $pos = 2;
        if ($firstCat = $product->categories->first()) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $firstCat->name, 'item' => url('/product/'.$firstCat->slug)];
        }
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product->title, 'item' => url($product->canonical_path)];
        $breadcrumbJsonld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbs];
    @endphp
    {!! json_encode($breadcrumbJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
