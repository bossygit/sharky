@extends('admin::layouts.app')

@section('title', 'Marques')

@section('content')
    <div class="actions" style="justify-content:space-between; margin-bottom:1rem;">
        <h1 style="margin:0;">Marques</h1>
        <a class="btn btn-primary" href="{{ route('admin.brands.create') }}">+ Nouvelle marque</a>
    </div>
    <div class="card">
        <table>
            <thead><tr><th>Nom</th><th>Slug</th><th>Produits</th><th></th></tr></thead>
            <tbody>
            @foreach ($brands as $brand)
                <tr>
                    <td>{{ $brand->name }}</td>
                    <td><code>/brand/{{ $brand->slug }}</code></td>
                    <td>{{ $brand->products_count }}</td>
                    <td><a href="{{ route('admin.brands.edit', $brand) }}">Éditer</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $brands->links() }}
    </div>
@endsection
