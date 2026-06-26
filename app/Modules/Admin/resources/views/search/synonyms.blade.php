@extends('admin::layouts.app')

@section('title', 'Synonymes recherche')

@section('content')
    <h1>Synonymes Meilisearch</h1>
    <p class="muted">Ex : terme « crème », synonymes « creme, creme hydratante »</p>

    <form method="POST" action="{{ route('admin.search.synonyms.store') }}" class="card">
        @csrf
        <label for="term">Terme principal</label>
        <input id="term" name="term" required>
        <label for="synonyms">Synonymes (séparés par des virgules)</label>
        <input id="synonyms" name="synonyms" required placeholder="creme, crème hydratante">
        <button class="btn btn-primary" type="submit" style="margin-top:1rem;">Ajouter</button>
    </form>

    <div class="card">
        <table>
            <thead><tr><th>Terme</th><th>Synonymes</th><th></th></tr></thead>
            <tbody>
            @forelse ($synonyms as $row)
                <tr>
                    <td><code>{{ $row->term }}</code></td>
                    <td>{{ implode(', ', $row->synonyms ?? []) }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.search.synonyms.destroy', $row) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Aucun synonyme.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $synonyms->links() }}
    </div>
@endsection
