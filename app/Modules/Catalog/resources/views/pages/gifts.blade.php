@extends('catalog::layouts.app')

@section('title', 'Cadeaux & coffrets — Nasande Vins')
@section('meta_description', 'Coffrets de vins et champagnes composés à Brazzaville : anniversaire, dîner d’affaires, départ. Emballage soigné et carte manuscrite.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Cadeaux & coffrets</li>
                </ol>
            </nav>
            <h1>Offrir une bouteille, offrir un moment.</h1>
            <p class="lede">
                Un coffret bois, un étui, une carte manuscrite : nous composons le cadeau avec vous,
                selon l'occasion, le budget et le goût de la personne.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="offer-grid">
                <article class="offer-card">
                    <p class="eyebrow">Pour deux</p>
                    <h2>Le duo</h2>
                    <p>Deux bouteilles qui se répondent : un rouge et un blanc, ou deux champagnes de maisons différentes.</p>
                    <ul>
                        <li>2 bouteilles au choix</li>
                        <li>Coffret bois ou étui</li>
                        <li>Carte manuscrite</li>
                    </ul>
                    <p class="offer-price">Sur devis</p>
                </article>

                <article class="offer-card">
                    <p class="eyebrow">Pour la table</p>
                    <h2>Le dîner</h2>
                    <p>Trois vins pour accompagner un repas complet, de l'apéritif au dessert.</p>
                    <ul>
                        <li>1 champagne ou effervescent</li>
                        <li>1 blanc et 1 rouge</li>
                        <li>Fiches d'accords imprimées</li>
                    </ul>
                    <p class="offer-price">Sur devis</p>
                </article>

                <article class="offer-card">
                    <p class="eyebrow">Pour marquer</p>
                    <h2>Le prestige</h2>
                    <p>Un grand cru, un champagne millésimé ou une bouteille rare, présentée dans un écrin.</p>
                    <ul>
                        <li>Bouteille sélectionnée avec vous</li>
                        <li>Écrin et ruban de la maison</li>
                        <li>Livraison prioritaire à Brazzaville</li>
                    </ul>
                    <p class="offer-price">Sur devis</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section section-cream">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Comment ça marche</p>
                    <h2 class="section-title">Trois étapes, zéro prise de tête</h2>
                </div>
            </div>
            <ol class="steps">
                <li>
                    <span class="step-number">01</span>
                    <h3>Vous racontez</h3>
                    <p>L'occasion, le budget, ce que la personne aime boire — ou pas du tout.</p>
                </li>
                <li>
                    <span class="step-number">02</span>
                    <h3>Nous composons</h3>
                    <p>Une proposition de bouteilles, de coffret et de carte, avec un prix clair en FCFA.</p>
                </li>
                <li>
                    <span class="step-number">03</span>
                    <h3>Nous livrons</h3>
                    <p>Le coffret part protégé, avec un appel avant passage à Brazzaville.</p>
                </li>
            </ol>

            <div class="team-actions" style="justify-content:flex-start; margin-top:2.5rem;">
                @if ($contact->hasWhatsapp())
                    <a class="btn" href="{{ $contact->whatsappHref('Bonjour, je souhaite composer un coffret cadeau.') }}">Composer mon coffret</a>
                @endif
                <a class="btn btn-outline-dark" href="{{ route('wines.category', 'champagnes') }}">Voir les champagnes</a>
            </div>
        </div>
    </section>
@endsection
