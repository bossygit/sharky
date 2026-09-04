@extends('catalog::layouts.app')

@section('title', 'À propos | '.config('app.name'))
@section('meta_description', 'nasande.cg, parapharmacie et cosmétique en ligne à Brazzaville : catalogue, livraison locale, paiement à la livraison.')

@section('content')
<section class="container legal-page">
    <h1>À propos de nasande.cg</h1>
    <p>nasande.cg est une boutique en ligne de parapharmacie, cosmétique et soin, basée à Brazzaville. On y trouve des marques de soins, d’hygiène, de maternité et de bébé, livrées dans la capitale.</p>
    <p>Le catalogue vient d’une boutique déjà connue localement : les fiches, les prix en francs CFA et les références sont celles que vous pouvez commander aujourd’hui, avec un paiement à la livraison ou par Mobile Money selon les instructions affichées au checkout.</p>
    <p>Pas de compte à créer pour commander : nom, email, téléphone et adresse à Brazzaville suffisent.</p>
    @if ($contact->hasWhatsapp() || $contact->hasPhone())
        <p><a href="{{ route('pages.contact') }}">Nous écrire</a> si un produit manque ou si vous voulez confirmer un stock avant de payer.</p>
    @endif
</section>
@endsection
