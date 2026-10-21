@extends('catalog::layouts.app')

@php
    /** @var \App\Modules\Catalog\Domain\Models\Product $product */
    /** @var \App\Modules\Catalog\Domain\Models\Variant|null $variant */
    $primaryImage = $product->primaryImage();
    $images = $product->images->where('is_available', true);
    $spec = is_array($product->specifications) ? $product->specifications : [];
    $metaDesc = $product->meta_description
        ?: \Illuminate\Support\Str::limit(strip_tags((string) ($product->short_description_html ?: (string) $product->description_html)), 160);
    $metaDesc = $metaDesc !== '' ? $metaDesc : trim($product->title.' — '.($spec['millesime'] ?? '').' '.($spec['region'] ?? '').'. Livré à Brazzaville par Nasande Vins.');
    $title = trim(($product->meta_title ?: $product->title).' | Nasande Vins');
    $score = isset($spec['note']) && is_numeric($spec['note']) ? (int) $spec['note'] : null;
    $stock = $variant?->stock;
    $inStock = $variant?->isInStock() ?? false;
    $contact = \App\Modules\Commerce\Application\StorefrontContact::current();
    $house = $product->brand?->name;
    $firstCat = $product->categories->first();
    $galleryImages = $images->take(8);
    $buyable = $variant && $inStock;
    $maxQty = $stock !== null && $stock > 0 ? min(99, $stock) : 99;
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

@section('content')
    <div class="container">
        <nav class="breadcrumb" aria-label="Fil d'Ariane" style="padding-top:1.25rem;">
            <ol>
                <li><a href="{{ route('home') }}">Accueil</a></li>
                <li><a href="{{ route('wines.index') }}">La cave</a></li>
                @if ($firstCat)
                    <li><a href="{{ route('wines.category', $firstCat->slug) }}">{{ $firstCat->name }}</a></li>
                @endif
                <li aria-current="page">{{ $product->title }}</li>
            </ol>
        </nav>

        <article class="pdp" itemscope itemtype="https://schema.org/Product">
            <div class="pdp-media">
                <div class="pdp-stage">
                    @if ($primaryImage)
                        <img src="{{ $primaryImage->url }}"
                             alt="{{ $primaryImage->alt_text ?: $product->title }}"
                             width="{{ $primaryImage->width ?: 600 }}"
                             height="{{ $primaryImage->height ?: 800 }}"
                             itemprop="image" fetchpriority="high" id="pdp-main-image">
                    @else
                        <img src="{{ asset('images/vins/placeholder.svg') }}" alt="{{ $product->title }}" width="600" height="800">
                    @endif
                </div>
                @if ($galleryImages->count() > 1)
                    <div class="pdp-thumbs">
                        @foreach ($galleryImages as $img)
                            <img src="{{ $img->url }}"
                                 alt="{{ $img->alt_text ?: $product->title }}"
                                 loading="lazy" decoding="async"
                                 data-full="{{ $img->url }}"
                                 width="120" height="120">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="pdp-info">
                @if ($house)
                    <p class="pdp-house" itemprop="brand">{{ $house }}</p>
                @endif
                <h1 class="pdp-title" itemprop="name">{{ $product->title }}</h1>

                <div class="pdp-sub">
                    @if ($spec['millesime'] ?? null)<span>{{ $spec['millesime'] }}</span><span class="dot"></span>@endif
                    @if ($spec['region'] ?? null)<span>{{ $spec['region'] }}</span><span class="dot"></span>@endif
                    @if ($spec['format'] ?? null)<span>{{ $spec['format'] }}</span>@endif
                </div>

                @if ($score)
                    <p style="margin-top:1rem;">
                        <span class="score-badge">
                            <strong>{{ $score }}</strong> / 100
                            <span>{{ $spec['note_source'] ?? 'critique' }}</span>
                        </span>
                    </p>
                @endif

                @if ($product->short_description_html)
                    <div class="pdp-intro" style="margin-top:1.25rem; color:var(--ink-soft); font-size:.94rem;" itemprop="description">
                        {!! $product->short_description_html !!}
                    </div>
                @endif

                @if ($variant)
                    <div class="pdp-price-row" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                        <p class="pdp-price">
                            <span itemprop="price" content="{{ $variant->price_minor }}">{{ $variant->formatted_price }}</span>
                            <meta itemprop="priceCurrency" content="{{ $variant->currency_code }}">
                            <meta itemprop="availability" content="https://schema.org/{{ $variant->availability() }}">
                            <meta itemprop="url" content="{{ url($product->canonical_path) }}">
                            <small>Prix TTC · livraison calculée au panier</small>
                        </p>
                        @if ($inStock && $stock !== null && $stock <= 5)
                            <span class="stock low">Plus que {{ $stock }} bouteille{{ $stock > 1 ? 's' : '' }}</span>
                        @elseif ($inStock)
                            <span class="stock in">En stock</span>
                        @else
                            <span class="stock out">Épuisé</span>
                        @endif
                    </div>

                    @if ($buyable)
                        <form class="pdp-buy" method="POST" action="{{ route('cart.add') }}">
                            @csrf
                            <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                            <div class="qty">
                                <button type="button" data-qty="-1" aria-label="Diminuer la quantité">−</button>
                                <label for="qty" class="sr-only">Quantité</label>
                                <input id="qty" type="number" name="quantity" value="1" min="1" max="{{ $maxQty }}" inputmode="numeric">
                                <button type="button" data-qty="1" aria-label="Augmenter la quantité">+</button>
                            </div>
                            <button class="btn" type="submit">Ajouter au panier</button>
                        </form>
                    @else
                        <p style="margin:1.25rem 0;">
                            <button class="btn" type="button" disabled>Indisponible</button>
                        </p>
                    @endif
                @endif

                <p class="pdp-wa">
                    Un doute, un accord, un cadeau ?
                    @if ($contact->hasWhatsapp())
                        <a href="{{ $contact->whatsappHref('Bonjour, je souhaite des conseils sur : '.$product->title) }}">Écrivez-nous sur WhatsApp</a>
                    @else
                        <a href="{{ route('pages.contact') }}">Contactez la cave</a>
                    @endif
                </p>

                <ul class="pdp-reassure">
                    <li>Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }} sous 24 h ouvrées.</li>
                    <li>Paiement Airtel Money, MTN MoMo ou à la livraison.</li>
                    <li>Bouteille transportée debout, protégée et vérifiée avant départ.</li>
                </ul>

                <div class="accordion">
                    @if ($spec['notes_degustation'] ?? null)
                        <details open>
                            <summary>Notes de dégustation</summary>
                            <div class="acc-body"><p>{{ $spec['notes_degustation'] }}</p></div>
                        </details>
                    @endif

                    @if ($spec['accords'] ?? null)
                        <details>
                            <summary>À table</summary>
                            <div class="acc-body"><p>{{ $spec['accords'] }}</p></div>
                        </details>
                    @endif

                    <details>
                        <summary>La bouteille</summary>
                        <div class="acc-body">
                            <dl class="spec-list">
                                @foreach ([
                                    'Maison' => $house,
                                    'Type' => $spec['type'] ?? null,
                                    'Millésime' => $spec['millesime'] ?? null,
                                    'Région' => $spec['region'] ?? null,
                                    'Pays' => $spec['pays'] ?? null,
                                    'Appellation' => $spec['appellation'] ?? null,
                                    'Cépage' => $spec['cepage'] ?? null,
                                    'Alcool' => $spec['alcool'] ?? null,
                                    'Format' => $spec['format'] ?? null,
                                    'Service' => $spec['service'] ?? null,
                                    'Garde' => $spec['garde'] ?? null,
                                    'Référence' => $variant?->sku,
                                ] as $label => $value)
                                    @if ($value)
                                        <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                                    @endif
                                @endforeach
                            </dl>
                        </div>
                    </details>

                    @if ($product->description_html)
                        <details>
                            <summary>L'histoire du vin</summary>
                            <div class="acc-body">{!! $product->description_html !!}</div>
                        </details>
                    @endif

                    <details>
                        <summary>Livraison & retours</summary>
                        <div class="acc-body">
                            <p>Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }} sous 24 h ouvrées. Le livreur vous appelle avant de passer.</p>
                            <p>Une bouteille cassée ou un millésime erroné ? Signalez-le à la livraison ou sous 48 h : nous remplaçons la bouteille ou nous vous remboursons.</p>
                            <p><a href="{{ route('pages.delivery') }}">Tout savoir sur la livraison et le paiement</a></p>
                        </div>
                    </details>
                </div>
            </div>
        </article>
    </div>

    @if ($related->isNotEmpty())
        <section class="section section-cream">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">Dans la même veine</p>
                        <h2 class="section-title">Vous aimerez aussi</h2>
                    </div>
                    @if ($firstCat)
                        <a class="link-arrow" href="{{ route('wines.category', $firstCat->slug) }}">Tout {{ $firstCat->name }}</a>
                    @endif
                </div>
                <div class="wine-grid">
                    @foreach ($related as $relatedProduct)
                        @include('catalog::partials.product-card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('qty');
            if (!input) return;
            document.querySelectorAll('[data-qty]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var step = parseInt(btn.getAttribute('data-qty'), 10);
                    var min = parseInt(input.min || '1', 10);
                    var max = parseInt(input.max || '99', 10);
                    var next = (parseInt(input.value || '1', 10) || 1) + step;
                    input.value = Math.max(min, Math.min(max, next));
                });
            });

            document.querySelectorAll('.pdp-thumbs img[data-full]').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    var main = document.getElementById('pdp-main-image');
                    if (main) main.src = thumb.getAttribute('data-full');
                });
            });
        })();
    </script>
@endpush

@push('jsonld')
    @php
        $jsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'sku' => $variant?->sku,
            'url' => url($product->canonical_path),
            'description' => $metaDesc,
            'brand' => $house ? ['@type' => 'Brand', 'name' => $house] : null,
            'category' => $firstCat?->name,
            'image' => $primaryImage ? $images->pluck('url')->filter()->values()->all() : null,
        ];
        if ($variant) {
            $jsonld['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => $variant->currency_code,
                'price' => (string) $variant->price_minor,
                'availability' => 'https://schema.org/'.$variant->availability(),
                'url' => url($product->canonical_path),
                'seller' => ['@type' => 'Organization', 'name' => 'Nasande Vins'],
            ];
        }
        $jsonld = array_filter($jsonld, fn ($v) => $v !== null);

        $breadcrumbs = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')]];
        $pos = 2;
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => 'La cave', 'item' => route('wines.index')];
        if ($firstCat) {
            $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $firstCat->name, 'item' => route('wines.category', $firstCat->slug)];
        }
        $breadcrumbs[] = ['@type' => 'ListItem', 'position' => $pos, 'name' => $product->title, 'item' => url($product->canonical_path)];
        $breadcrumbJsonld = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbs];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode($breadcrumbJsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush
