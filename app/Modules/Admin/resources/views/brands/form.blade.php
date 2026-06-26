@extends('admin::layouts.app')

@php $isEdit = $brand->exists; @endphp

@section('title', $isEdit ? 'Éditer marque' : 'Nouvelle marque')

@section('content')
    <h1>{{ $isEdit ? 'Éditer la marque' : 'Nouvelle marque' }}</h1>
    <form method="POST" action="{{ $isEdit ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" class="card">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        <label for="name">Nom *</label>
        <input id="name" name="name" value="{{ old('name', $brand->name) }}" required>
        <label for="description">Description</label>
        <textarea id="description" name="description">{{ old('description', $brand->description) }}</textarea>
        <label for="meta_title">Meta title</label>
        <input id="meta_title" name="meta_title" value="{{ old('meta_title', $brand->meta_title) }}">
        <label for="meta_description">Meta description</label>
        <textarea id="meta_description" name="meta_description">{{ old('meta_description', $brand->meta_description) }}</textarea>
        <div class="actions" style="margin-top:1rem;">
            <button class="btn btn-primary" type="submit">Enregistrer</button>
            <a class="btn" href="{{ route('admin.brands.index') }}">Annuler</a>
        </div>
    </form>
@endsection
