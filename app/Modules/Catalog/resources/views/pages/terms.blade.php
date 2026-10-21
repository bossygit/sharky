@extends('catalog::layouts.app')

@section('title', 'Conditions générales de vente — Nasande Vins')
@section('meta_description', 'Conditions de vente Nasande Vins : commandes, prix en FCFA, livraison à Brazzaville, paiement, âge légal et retours.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Conditions générales</li>
                </ol>
            </nav>
            <h1>Conditions générales de vente</h1>
            <p class="lede">Les règles qui encadrent une commande sur Nasande Vins, cave en ligne exploitée à Brazzaville, République du Congo.</p>
        </div>
    </section>

    <section class="container prose">
        <h2>1. Objet</h2>
        <p>
            Les présentes conditions s'appliquent à toute commande passée sur le site Nasande Vins.
            En validant une commande, vous déclarez avoir lu et accepté ces conditions.
        </p>

        <h2>2. Âge légal</h2>
        <p>
            La vente d'alcool est réservée aux personnes âgées de 18 ans et plus. En commandant, vous confirmez avoir 18 ans révolus.
            Le livreur peut demander une pièce d'identité à la remise et refuser la commande en cas de doute.
        </p>

        <h2>3. Commande</h2>
        <p>
            Une commande est enregistrée lorsque vous validez le checkout avec vos coordonnées.
            Vous recevez un numéro de commande et, si l'email est joignable, une confirmation.
            Un devis ou un échange WhatsApp ne vaut pas commande tant qu'elle n'est pas enregistrée sur le site.
        </p>

        <h2>4. Prix et paiement</h2>
        <p>
            Les prix sont exprimés en francs CFA d'Afrique centrale (XAF), toutes taxes comprises.
            Le paiement à la livraison est accepté. Les options Airtel Money et MTN MoMo affichent des instructions
            de paiement manuel (USSD) ; le paiement n'est réputé confirmé qu'après vérification par Nasande Vins.
            Les frais de livraison sont indiqués au checkout avant validation.
        </p>

        <h2>5. Livraison</h2>
        <p>
            Livraison à {{ config('commerce.delivery.city', 'Brazzaville') }}, du lundi au samedi, avec appel avant passage.
            Les délais sont confirmés par téléphone ou WhatsApp après réception de la commande.
            Nous pouvons refuser une adresse hors zone, incomplète ou inexacte.
        </p>

        <h2>6. Disponibilité</h2>
        <p>
            Une bouteille indiquée « en stock » peut être épuisée entre la commande et la préparation.
            Dans ce cas, nous vous contactons pour un remplacement, un avoir ou une annulation partielle.
        </p>

        <h2>7. Retours et remboursements</h2>
        <p>
            Les bouteilles non ouvertes peuvent être retournées dans les 48 heures suivant la livraison, dans leur état d'origine.
            Les bouteilles ouvertes ne sont reprises qu'en cas de défaut avéré (bouchon, oxydation), après vérification.
            Une bouteille cassée ou une erreur de préparation est remplacée ou remboursée.
            Voir la <a href="{{ route('pages.returns') }}">politique de retours</a>.
        </p>

        <h2>8. Responsabilité</h2>
        <p>
            L'abus d'alcool est dangereux pour la santé. À consommer avec modération.
            Nasande Vins ne peut être tenue responsable d'un dommage résultant d'une consommation excessive,
            d'un stockage inadapté après livraison ou d'un usage contraire à la réglementation locale.
        </p>

        <h2>9. Données personnelles</h2>
        <p>
            Nom, email, téléphone et adresse servent uniquement à traiter et livrer la commande, et à vous recontacter.
            Ces données ne sont ni vendues ni cédées à des tiers en dehors des prestataires nécessaires à la commande.
        </p>

        <h2>10. Droit applicable et contact</h2>
        <p>
            Les présentes conditions sont soumises au droit congolais. Pour toute réclamation,
            écrivez-nous via la <a href="{{ route('pages.contact') }}">page Contact</a>.
        </p>
    </section>
@endsection
