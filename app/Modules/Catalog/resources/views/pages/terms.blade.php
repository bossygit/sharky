@extends('catalog::layouts.app')

@section('title', 'Conditions générales de vente | '.config('app.name'))
@section('meta_description', 'Conditions de vente nasande.cg : commandes, livraison à Brazzaville, paiement et responsabilités.')

@section('content')
<section class="container legal-page">
    <h1>Conditions générales de vente</h1>
    <p>Les présentes conditions s’appliquent aux commandes passées sur nasande.cg, boutique exploitée à Brazzaville, République du Congo.</p>

    <h2>Commande</h2>
    <p>Une commande est enregistrée lorsque vous validez le checkout avec vos coordonnées. Vous recevez un numéro de commande (NAS-…) et, si l’email est joignable, une confirmation. Un devis WhatsApp n’est pas une commande tant qu’elle n’apparaît pas sur le site.</p>

    <h2>Prix et paiement</h2>
    <p>Les prix sont en francs CFA d’Afrique centrale (XAF), toutes taxes comprises le cas échéant. Le paiement à la livraison est le mode standard. Les options Airtel Money et MTN MoMo affichent des instructions de paiement manuel (USSD) ; le paiement n’est confirmé qu’après vérification par nasande.cg.</p>

    <h2>Livraison</h2>
    <p>Livraison à Brazzaville uniquement, moyennant les frais indiqués au checkout. Les délais sont confirmés par téléphone ou WhatsApp après réception de la commande. Nous pouvons refuser une adresse hors zone ou incomplète.</p>

    <h2>Disponibilité</h2>
    <p>Un article indiqué « en stock » peut être épuisé entre la commande et la préparation. Dans ce cas nous vous contactons pour un remplacement, un avoir, ou une annulation partielle.</p>

    <h2>Données personnelles</h2>
    <p>Nom, email, téléphone et adresse servent uniquement à traiter et livrer la commande, et à vous recontacter. Ils ne sont pas vendus à des tiers.</p>

    <h2>Contact</h2>
    <p>Pour toute réclamation : <a href="{{ route('pages.contact') }}">page Contact</a>.</p>
</section>
@endsection
