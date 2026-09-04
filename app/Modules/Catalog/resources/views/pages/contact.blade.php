@extends('catalog::layouts.app')

@section('title', 'Contact | '.config('app.name'))
@section('meta_description', 'Contacter nasande.cg à Brazzaville : téléphone, WhatsApp et email pour une commande ou une question produit.')

@section('content')
<section class="container legal-page">
    <h1>Contact</h1>
    <p>Une question sur un soin, un stock ou une livraison à Brazzaville ? Écrivez-nous — on répond plus vite sur WhatsApp.</p>

    <ul class="contact-card">
        @if ($contact->hasPhone())
            <li>
                <span>Téléphone</span>
                <a href="{{ $contact->telHref() }}">{{ $contact->phone }}</a>
            </li>
        @endif
        @if ($contact->hasWhatsapp())
            <li>
                <span>WhatsApp</span>
                <a href="{{ $contact->whatsappHref('Bonjour, je vous contacte depuis nasande.cg.') }}">Discuter sur WhatsApp</a>
            </li>
        @endif
        @if ($contact->hasEmail())
            <li>
                <span>Email</span>
                <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
            </li>
        @endif
        <li>
            <span>Livraison</span>
            <strong>Brazzaville, République du Congo</strong>
        </li>
    </ul>

    @if (! $contact->hasPhone() && ! $contact->hasWhatsapp() && ! $contact->hasEmail())
        <p>Les coordonnées seront affichées ici dès qu’elles seront renseignées dans l’administration.</p>
    @endif
</section>
@endsection
