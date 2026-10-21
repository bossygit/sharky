@php
    /** @var \App\Modules\Catalog\Domain\Models\Product $product */
    $img = $product->primaryImage();
    $variant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();
    $price = $variant?->formatted_price ?? $product->formattedPrice();
    $spec = is_array($product->specifications) ? $product->specifications : [];
    $house = $product->brand?->name;
    $name = $product->title;
    if ($house && \Illuminate\Support\Str::startsWith(mb_strtolower($name), mb_strtolower($house))) {
        $name = trim(mb_substr($name, mb_strlen($house)));
    }
    $name = $name !== '' ? $name : $product->title;
    $metaParts = array_filter([
        $spec['region'] ?? null,
        $spec['millesime'] ?? null,
    ]);
    $score = isset($spec['note']) && is_numeric($spec['note']) ? (int) $spec['note'] : null;
@endphp
<article class="wine-card" itemscope itemtype="https://schema.org/Product">
    <div class="wine-card-media">
        <a href="{{ $product->canonical_path }}" aria-label="{{ $product->title }}" tabindex="-1">
            @if ($img)
                <img src="{{ $img->url }}"
                     alt="{{ $img->alt_text ?: $product->title }}"
                     loading="lazy" decoding="async" itemprop="image"
                     width="{{ $img->width ?: 600 }}" height="{{ $img->height ?: 800 }}">
            @else
                <img src="{{ asset('images/vins/placeholder.svg') }}" alt="" loading="lazy" width="600" height="800" aria-hidden="true">
            @endif
        </a>

        @if ($score)
            <span class="wine-badge score" title="Note {{ $spec['note_source'] ?? 'critique' }}">{{ $score }} · {{ $spec['note_source'] ?? 'Sélection' }}</span>
        @elseif ($product->featured)
            <span class="wine-badge">Sélection</span>
        @endif

        @if ($variant && $variant->isInStock())
            <form class="wine-quick" method="POST" action="{{ route('cart.add') }}">
                @csrf
                <input type="hidden" name="variant_id" value="{{ $variant->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit">Ajouter au panier</button>
            </form>
        @endif
    </div>

    <div class="wine-card-body">
        @if ($house)
            <span class="wine-card-house" itemprop="brand">{{ $house }}</span>
        @endif
        <h3 class="wine-card-name">
            <a href="{{ $product->canonical_path }}" itemprop="url">
                <span itemprop="name">{{ $name }}</span>
            </a>
        </h3>
        @if ($metaParts)
            <p class="wine-card-meta">{{ implode(' · ', $metaParts) }}</p>
        @endif
        <div class="wine-card-foot">
            @if ($price)
                <span class="wine-card-price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                    <span itemprop="price" content="{{ $variant?->price_minor ?? $product->priceMinor() }}">{{ $price }}</span>
                    <meta itemprop="priceCurrency" content="{{ $variant?->currency_code ?? 'XAF' }}">
                </span>
            @endif
            <span class="wine-card-format">{{ $spec['format'] ?? '75 cl' }}</span>
        </div>
    </div>
</article>
