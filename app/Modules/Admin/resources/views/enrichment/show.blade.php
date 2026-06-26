@extends('admin::layouts.app')

@php
    $format = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        return (string) ($value ?? '');
    };
@endphp

@section('title', 'Revue IA — '.$product->title)

@section('content')
    <p><a href="{{ route('admin.enrichment.index') }}">← Retour enrichissement</a></p>

    <div class="actions" style="justify-content:space-between; margin:1rem 0;">
        <div>
            <h1 style="margin:0;">{{ $product->title }}</h1>
            <p class="muted">
                Confiance moyenne : {{ $product->ai_confidence ?? '—' }}
                @if ($job?->error)
                    — <span style="color:#b45309;">Avertissements : {{ $job->error }}</span>
                @endif
            </p>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('admin.enrichment.approve', $product) }}">
                @csrf
                <button class="btn btn-ok" type="submit">Tout approuver</button>
            </form>
            <form method="POST" action="{{ route('admin.enrichment.reject', $product) }}">
                @csrf
                <button class="btn btn-danger" type="submit">Rejeter</button>
            </form>
        </div>
    </div>

    @foreach ($drafts as $draft)
        <div class="card">
            <div class="actions" style="justify-content:space-between;">
                <h2 style="margin:0;">{{ $draft->field }}</h2>
                <span class="badge">confiance {{ $draft->confidence }}</span>
            </div>

            <div class="row" style="margin-top:1rem;">
                <div>
                    <h3 class="muted">Actuel</h3>
                    <pre class="diff-box">{{ $format($draft->currentScalar()) ?: '— vide —' }}</pre>
                </div>
                <div>
                    <h3 class="muted">Proposé</h3>
                    <pre class="diff-box diff-new">{{ $format($draft->proposedScalar()) }}</pre>
                </div>
            </div>

            @if ($draft->sources)
                <h3 class="muted" style="margin-top:1rem;">Sources</h3>
                <ul>
                    @foreach ($draft->sources as $source)
                        <li>
                            <a href="{{ $source['url'] ?? '#' }}" target="_blank" rel="noopener">{{ $source['title'] ?? $source['url'] }}</a>
                            @if (!empty($source['excerpt']))
                                <div class="muted" style="font-size:.85rem;">{{ Str::limit($source['excerpt'], 160) }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('admin.enrichment.drafts.update', $draft) }}" style="margin-top:1rem;">
                @csrf @method('PUT')
                <label>Modifier la proposition</label>
                <textarea name="value" style="min-height:100px;">{{ $format($draft->proposedScalar()) }}</textarea>
                <button class="btn" type="submit" style="margin-top:.5rem;">Enregistrer l'édition</button>
            </form>
        </div>
    @endforeach
@endsection

@push('head')
    <style>
        .diff-box { background:#f9fafb; border:1px solid var(--border); border-radius:6px; padding:.75rem; white-space:pre-wrap; font-size:.85rem; max-height:240px; overflow:auto; }
        .diff-new { background:#ecfdf5; border-color:#a7f3d0; }
    </style>
@endpush
