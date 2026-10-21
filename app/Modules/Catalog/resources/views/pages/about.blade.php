@extends('catalog::layouts.app')

@section('title', 'Notre histoire — Nasande Vins')
@section('meta_description', 'Nasande Vins, la cave en ligne de Brazzaville : une sélection de champagnes, grands crus et vins choisis avec soin, livrés avec conseil.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Notre histoire</li>
                </ol>
            </nav>
            <h1>La cave de Brazzaville</h1>
            <p class="lede">Une maison de vins pensée pour ici : les grandes bouteilles, les bons accords et le service qui va avec.</p>
        </div>
    </section>

    <section class="container prose">
        <p>
            Nasande Vins est née d'une conviction simple : à Brazzaville, on aime le vin, on aime recevoir,
            mais trouver la bonne bouteille — et savoir quoi mettre sur la table avec — reste souvent un parcours du combattant.
        </p>
        <p>
            Nous avons donc construit une cave en ligne volontairement resserrée. Pas de catalogue infini :
            des champagnes, des rouges, des blancs, des rosés et quelques vins doux, choisis pour leur qualité,
            leur histoire et leur capacité à accompagner la cuisine d'ici comme les grands dîners.
        </p>

        <h2>Ce qui nous guide</h2>
        <ul>
            <li><strong>La sélection avant le volume.</strong> Chaque référence est goûtée ou validée par la maison avant d'entrer dans la cave.</li>
            <li><strong>Le conseil fait partie du service.</strong> Un poisson braisé, un poulet moambe, un anniversaire : dites-nous ce que vous préparez, nous proposons la bouteille.</li>
            <li><strong>Une livraison fiable.</strong> Bouteilles protégées, transportées debout, livrées à Brazzaville avec appel avant passage.</li>
            <li><strong>Des prix lisibles.</strong> En francs CFA, sans surprise, avec un paiement adapté aux usages locaux.</li>
        </ul>

        <h2>Comment nous choisissons</h2>
        <p>
            Nous travaillons avec des maisons et des importateurs de confiance, en privilégiant les millésimes disponibles
            et les vins prêts à boire. Une bouteille de garde est proposée seulement si elle peut être conservée correctement ;
            sinon, nous préférons un vin de plaisir immédiat.
        </p>
        <p>
            Les notes de dégustation et les accords sont rédigés pour être utiles, pas pour impressionner :
            ce que vous allez sentir, ce que vous allez servir avec, et comment conserver la bouteille.
        </p>

        <blockquote>
            Le bon vin ne s'improvise pas. Il se choisit, se raconte et se partage.
        </blockquote>

        <h2>La maison Nasande</h2>
        <p>
            Nasande Vins fait partie de l'écosystème Nasande, construit à Brazzaville autour du commerce en ligne
            et du service local. La cave est le volet dédié au vin de cette aventure.
        </p>
        <p>
            <a href="{{ route('wines.index') }}">Découvrir la cave</a> ·
            <a href="{{ route('pages.contact') }}">Nous écrire</a>
        </p>
    </section>
@endsection
