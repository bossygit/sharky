@extends('admin::layouts.app')

@section('title', 'Demande non satisfaite')

@section('content')
    <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
        <div>
            <h1 style="margin:0;">Demande non satisfaite</h1>
            <p class="muted">Recherches sans résultat clusterisées (n-gram + Jaccard)</p>
        </div>
        <form method="POST" action="{{ route('admin.demand.refresh') }}">
            @csrf
            <button class="btn btn-primary" type="submit">Recalculer</button>
        </form>
    </div>

    <div class="grid" style="margin-bottom:1.5rem;">
        <div class="card"><div class="stat">{{ $stats['open_signals'] }}</div><div class="muted">Signaux ouverts</div></div>
        <div class="card"><div class="stat">{{ $stats['new_clusters_week'] }}</div><div class="muted">Nouveaux clusters (7j)</div></div>
        <div class="card"><div class="stat">{{ $stats['viral_unsourced'] }}</div><div class="muted">Viraux non sourcés</div></div>
        <div class="card"><div class="stat">{{ $stats['zero_searches_7d'] }}</div><div class="muted">Recherches vides (7j)</div></div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">Top clusters</h2>
        <table>
            <thead>
            <tr>
                <th>Cluster</th>
                <th>Membres</th>
                <th>Freq. 7j</th>
                <th>Urgence max</th>
                <th>Statut</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($clusters as $cluster)
                @php
                    $maxUrgency = $cluster->signals->max('urgency') ?? 'low';
                    $openCount = $cluster->signals->where('status', 'open')->count();
                @endphp
                <tr>
                    <td><strong>{{ $cluster->label }}</strong></td>
                    <td>{{ $cluster->member_count }}</td>
                    <td>{{ $cluster->frequency7d() }}</td>
                    <td><span class="badge badge-urgency-{{ $maxUrgency }}">{{ $maxUrgency }}</span></td>
                    <td>{{ $openCount }} ouvert(s)</td>
                    <td><a href="{{ route('admin.demand.clusters.show', $cluster) }}">Détail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Aucun cluster. Lancez une recherche sans résultat puis « Recalculer ».</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
