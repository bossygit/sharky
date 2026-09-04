@php
    /** @var \App\Modules\Catalog\Domain\Models\Product $product */
    $img = $product->primaryImage();
    $price = $product->formattedPrice();
@endphp
<article class="product-card" itemscope itemtype="https://schema.org/Product">
    <a href="{{ $product->canonical_path }}" class="thumb" aria-label="{{ $product->title }}">
        @if ($img)
            <img src="{{ $img->url }}" srcset="{{ $img->srcset() }}" sizes="{{ $img->sizes('card') }}"
                 alt="{{ $img->alt_text ?: $product->title }}"
                 loading="lazy" decoding="async" itemprop="image"
                 width="{{ $img->width ?: 320 }}" height="{{ $img->height ?: 320 }}">
        @else
            <div aria-hidden="true" style="color: var(--c-muted); font-size: .9rem;">Pas d'image</div>
        @endif
    </a>
    <div class="body">
        @if ($product->brand)
            <span class="brand-name" itemprop="brand" itemscope itemtype="https://schema.org/Brand">
                <span itemprop="name">{{ $product->brand->name }}</span>
            </span>
        @endif
        <h3 class="title">
            <a href="{{ $product->canonical_path }}" itemprop="url">
                <span itemprop="name">{{ $product->title }}</span>
            </a>
        </h3>
        @if ($price)
            <p class="price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
                <span itemprop="price" content="{{ $product->priceMinor() }}">{{ $price }}</span>
                <meta itemprop="priceCurrency" content="XAF">
            </p>
        @endif
    </div>
</article>
