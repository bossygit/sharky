@if ($products->isEmpty())
    <p class="muted" style="padding:.5rem 0;">Aucune suggestion</p>
@else
    <ul role="listbox" aria-label="Suggestions pour {{ $query }}" style="list-style:none; padding:0; margin:0;">
        @foreach ($products as $product)
            <li>
                <a href="{{ $product->canonical_path }}" style="display:block; padding:.5rem .25rem;">
                    {{ $product->title }}
                    @if ($product->formattedPrice())
                        <span class="muted">— {{ $product->formattedPrice() }}</span>
                    @endif
                </a>
            </li>
        @endforeach
        <li style="margin-top:.35rem;">
            <a href="{{ route('search', ['q' => $query]) }}">Voir tous les résultats →</a>
        </li>
    </ul>
@endif
