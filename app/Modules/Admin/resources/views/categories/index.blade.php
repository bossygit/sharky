@extends('admin::layouts.app')

@section('title', 'Catégories')

@section('content')
    <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
        <h1 style="margin:0;">Catégories</h1>
        <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">+ Nouvelle catégorie</a>
    </div>
    <div class="card">
        <table>
            <thead><tr><th>Nom</th><th>Slug</th><th>Produits</th><th></th></tr></thead>
            <tbody>
            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td><code>/product/{{ $category->slug }}</code></td>
                    <td>{{ $category->products_count }}</td>
                    <td><a href="{{ route('admin.categories.edit', $category) }}">Éditer</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $categories->links() }}
    </div>
@endsection
