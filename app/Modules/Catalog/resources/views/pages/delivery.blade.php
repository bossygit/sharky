@extends('catalog::layouts.app')

@section('title', 'Livraison & paiement — Nasande Vins')
@section('meta_description', 'Livraison de vins à Brazzaville sous 24 h, paiement Airtel Money, MTN MoMo ou à la livraison. Conditions et zones desservies.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Livraison & paiement</li>
                </ol>
            </nav>
            <h1>Livraison & paiement</h1>
            <p class="lede">Simple, local, sans surprise : nous livrons à {{ config('commerce.delivery.city', 'Brazzaville') }} et vous payez comme vous voulez.</p>
        </div>
    </section>

    <section class="container prose">
        <h2>Livraison</h2>
        <p>
            Nous livrons à <strong>{{ config('commerce.delivery.city', 'Brazzaville') }}</strong>, du lundi au samedi.
            Les commandes validées avant 15h sont généralement livrées le jour ouvré suivant.
            Le livreur vous appelle avant de passer.
        </p>
        <ul>
            <li>Frais de livraison : <strong>{{ number_format((int) config('commerce.delivery.fee_minor', 1500), 0, ',', ' ') }} FCFA</strong> par commande.</li>
            <li>Bouteilles transportées debout, protégées, vérifiées avant départ.</li>
            <li>Une adresse précise (quartier, rue, repère) et un numéro joignable sont indispensables.</li>
        </ul>
        <p>
            Hors de Brazzaville ? Écrivez-nous : nous étudions les envois à Pointe-Noire et dans les autres villes
            au cas par cas, avec un transporteur partenaire.
        </p>

        <h2>Paiement</h2>
        <p>Trois moyens au checkout :</p>
        <ul>
            <li><strong>Airtel Money</strong> — instructions USSD affichées après validation ; le paiement est confirmé après vérification.</li>
            <li><strong>MTN Mobile Money</strong> — mêmes principes, instructions USSD au checkout.</li>
            <li><strong>Paiement à la livraison</strong> — en espèces, au moment de la remise de la commande.</li>
        </ul>
        <p>
            Les prix sont en francs CFA d'Afrique centrale (XAF), toutes taxes comprises.
            Le montant du panier inclut la livraison avant validation.
        </p>

        <h2>Vérification de l'âge</h2>
        <p>
            La vente d'alcool est interdite aux personnes de moins de 18 ans. Le livreur peut demander une pièce d'identité
            à la remise ; une commande peut être refusée en cas de doute.
        </p>

        <h2>Retours</h2>
        <p>
            Bouteille cassée, erreur de millésime, bouchon défectueux : signalez-le à la livraison ou sous 48 h.
            Nous remplaçons la bouteille ou nous vous remboursons. Voir la <a href="{{ route('pages.returns') }}">politique de retours</a>.
        </p>

        <h2>Une question ?</h2>
        <p>
            <a href="{{ route('pages.contact') }}">Contactez la cave</a> — nous répondons plus vite sur WhatsApp.
        </p>
    </section>
@endsection
