@extends('admin::layouts.app')

@section('title', 'Cluster — '.$cluster->label)

@section('content')
    <p><a href="{{ route('admin.demand.index') }}">← Retour demande</a></p>

    <div class="actions" style="justify-content:space-between; margin:1rem 0;">
        <div>
            <h1 style="margin:0;">{{ $cluster->label }}</h1>
            <p class="muted">{{ $cluster->member_count }} requête(s) · {{ $cluster->frequency7d() }} recherche(s) sur 7 jours</p>
        </div>
        <div class="actions">
            <form method="POST" action="{{ route('admin.demand.clusters.source', $cluster) }}">
                @csrf
                <button class="btn btn-ok" type="submit">Sourcer ce produit</button>
            </form>
            <form method="POST" action="{{ route('admin.demand.clusters.reject', $cluster) }}">
                @csrf
                <button class="btn btn-danger" type="submit">Rejeter</button>
            </form>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Signaux du cluster</h2>
        <table>
            <thead>
            <tr><th>Requête normalisée</th><th>Exemples</th><th>7j</th><th>30j</th><th>Sessions 7j</th><th>Urgence</th><th>Statut</th></tr>
            </thead>
            <tbody>
            @foreach ($cluster->signals as $signal)
                <tr>
                    <td><code>{{ $signal->keyword }}</code></td>
                    <td>{{ implode(', ', $signal->example_queries ?? []) }}</td>
                    <td>{{ $signal->frequency_7d }}</td>
                    <td>{{ $signal->frequency_30d }}</td>
                    <td>{{ $signal->unique_sessions_7d }}</td>
                    <td><span class="badge badge-urgency-{{ $signal->urgency }}">{{ $signal->urgency }}</span></td>
                    <td>{{ $signal->status }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Recherches récentes</h2>
        @if ($recentLogs->isEmpty())
            <p class="muted">Aucune recherche enregistrée.</p>
        @else
            <table>
                <thead><tr><th>Requête</th><th>Session</th><th>Appareil</th><th>Date</th></tr></thead>
                <tbody>
                @foreach ($recentLogs as $log)
                    @php $session = $sessions->get($log->session_id); @endphp
                    <tr>
                        <td>{{ $log->query }}</td>
                        <td><code>{{ Str::limit($log->session_id ?? '—', 8, '') }}</code></td>
                        <td>{{ $session?->device ?? '—' }} · {{ $session?->country ?? '—' }}</td>
                        <td>{{ $log->occurred_at?->diffForHumans() }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
