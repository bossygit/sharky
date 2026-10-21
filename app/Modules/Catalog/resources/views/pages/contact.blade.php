@extends('catalog::layouts.app')

@section('title', 'Contact — Nasande Vins')
@section('meta_description', 'Contacter Nasande Vins à Brazzaville : téléphone, WhatsApp et email pour un conseil, une commande ou un coffret cadeau.')

@section('content')
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Fil d'Ariane">
                <ol>
                    <li><a href="{{ route('home') }}">Accueil</a></li>
                    <li aria-current="page">Contact</li>
                </ol>
            </nav>
            <h1>Parlons vin</h1>
            <p class="lede">Un accord à trouver, un stock à confirmer, un coffret à composer ? La maison vous répond.</p>
        </div>
    </section>

    <section class="container contact-grid">
        <div>
            <h2 class="section-title" style="font-size:clamp(1.6rem,3vw,2.2rem);">Le conseil, sans détour</h2>
            <p style="color:var(--muted); max-width:42ch;">
                Dites-nous le plat, l'occasion, le budget et le nombre de convives. Nous vous proposons
                une ou deux bouteilles, avec la manière de les servir.
            </p>
            <div class="team-actions" style="justify-content:flex-start; margin-top:1.75rem;">
                @if ($contact->hasWhatsapp())
                    <a class="btn" href="{{ $contact->whatsappHref('Bonjour Nasande Vins, j\'aimerais un conseil.') }}">Écrire sur WhatsApp</a>
                @endif
                @if ($contact->hasPhone())
                    <a class="btn btn-outline-dark" href="{{ $contact->telHref() }}">{{ $contact->phone }}</a>
                @endif
            </div>
        </div>

        <div class="contact-card">
            <h2>Coordonnées</h2>
            @if ($contact->hasPhone())
                <div class="contact-line">
                    <span>Téléphone</span>
                    <a href="{{ $contact->telHref() }}">{{ $contact->phone }}</a>
                </div>
            @endif
            @if ($contact->hasWhatsapp())
                <div class="contact-line">
                    <span>WhatsApp</span>
                    <a href="{{ $contact->whatsappHref('Bonjour Nasande Vins.') }}">Discuter avec la cave</a>
                </div>
            @endif
            @if ($contact->hasEmail())
                <div class="contact-line">
                    <span>Email</span>
                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                </div>
            @endif
            <div class="contact-line">
                <span>Livraison</span>
                <p>{{ config('commerce.delivery.city', 'Brazzaville') }}, République du Congo</p>
            </div>
            <div class="contact-line">
                <span>Horaires</span>
                <p>Lundi – samedi · 9h – 19h</p>
            </div>
            @if (! $contact->hasPhone() && ! $contact->hasWhatsapp() && ! $contact->hasEmail())
                <p style="margin-top:1rem; color:var(--muted); font-size:.82rem;">
                    Les coordonnées seront affichées ici dès qu'elles seront renseignées dans l'administration.
                </p>
            @endif
        </div>
    </section>
@endsection
