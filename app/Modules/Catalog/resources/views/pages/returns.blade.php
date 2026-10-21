@extends('catalog::layouts.app')

@section('title', 'Retours et échanges — Nasande Vins')
@section('meta_description', 'Politique de retours Nasande Vins : bouteilles cassées, bouchons défectueux, erreurs de millésime. Délais et conditions à Brazzaville.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Retours</li>
                </ol>
            </nav>
            <h1>Retours et échanges</h1>
            <p class="lede">Une bouteille abîmée, un bouchon qui ne sent pas bon, un millésime erroné : on s'en occupe.</p>
        </div>
    </section>

    <section class="container prose">
        <h2>Ce que nous prenons en charge</h2>
        <ul>
            <li>Bouteille cassée ou colis manifestement endommagé à la livraison, signalé au livreur ou le jour même.</li>
            <li>Erreur de préparation de notre part (mauvais vin, mauvais millésime, mauvaise quantité).</li>
            <li>Bouteille non ouverte présentée dans les 48 heures, dans son état d'origine.</li>
            <li>Bouchon défectueux ou vin oxydé : goût anormal constaté à l'ouverture, signalé sous 48 h avec photo ou retour de la bouteille.</li>
        </ul>

        <h2>Ce que nous ne reprenons pas</h2>
        <ul>
            <li>Bouteilles ouvertes et consommées, sauf défaut avéré constaté avec nous.</li>
            <li>Bouteilles mal conservées après livraison (exposition à la chaleur, debout/couché inadapté, lumière directe).</li>
            <li>Bouteilles achetées puis revendues ou transportées par un tiers non mandaté.</li>
        </ul>

        <h2>Comment faire</h2>
        <p>
            Contactez la cave via <a href="{{ route('pages.contact') }}">WhatsApp, téléphone ou email</a> avec :
            le numéro de commande, une photo de la bouteille ou du colis, et une description du problème.
            Nous décidons ensemble du remplacement, de l'avoir ou du remboursement.
        </p>
        <p>
            Les frais de retour d'une bouteille non ouverte sont à votre charge, sauf erreur de notre part ou défaut avéré.
        </p>
    </section>
@endsection
