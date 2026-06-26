@extends('admin::layouts.app')

@php $isEdit = $category->exists; @endphp

@section('title', $isEdit ? 'Éditer catégorie' : 'Nouvelle catégorie')

@section('content')
    <h1>{{ $isEdit ? 'Éditer la catégorie' : 'Nouvelle catégorie' }}</h1>
    <form method="POST" action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="card">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <label for="name">Nom *</label>
        <input id="name" name="name" value="{{ old('name', $category->name) }}" required>
        <label for="parent_id">Catégorie parente</label>
        <select id="parent_id" name="parent_id">
            <option value="">— Racine —</option>
            @foreach ($parents as $parent)
                <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id)==$parent->id)>{{ $parent->name }}</option>
            @endforeach
        </select>
        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description', $category->description) }}</textarea>
        <label for="sort_order">Ordre</label>
        <input id="sort_order" type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order ?? 0) }}">
        <div class="actions" style="margin-top:1rem;">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn" href="{{ route('admin.categories.index') }}">Annuler</a>
        </div>
    </form>
@endsection
