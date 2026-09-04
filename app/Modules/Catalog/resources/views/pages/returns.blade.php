@extends('catalog::layouts.app')

@section('title', 'Retours et échanges | '.config('app.name'))
@section('meta_description', 'Politique de retours nasande.cg : produits de parapharmacie, délais et conditions d’échange à Brazzaville.')

@section('content')
<section class="container legal-page">
    <h1>Retours et échanges</h1>
    <p>Les produits de parapharmacie, cosmétique et hygiène ne se reprennent pas une fois ouverts ou descellés, pour des raisons d’hygiène.</p>

    <h2>Ce que nous échangeons</h2>
    <ul>
        <li>Erreur de préparation de notre part (mauvais article, quantité).</li>
        <li>Colis manifestement endommagé à la livraison, signalé immédiatement au livreur ou le jour même.</li>
        <li>Article encore scellé, non utilisé, renvoyé dans les 48 heures avec le numéro de commande.</li>
    </ul>

    <h2>Ce que nous ne reprenons pas</h2>
    <ul>
        <li>Produits ouverts, testés, ou dont le film / opercule a été retiré.</li>
        <li>Articles en promotion soldés comme tels, sauf défaut.</li>
        <li>Commandes livrées hors Brazzaville par un tiers non mandaté.</li>
    </ul>

    <h2>Comment faire</h2>
    <p>Contactez-nous via <a href="{{ route('pages.contact') }}">WhatsApp ou téléphone</a> avec le numéro de commande et une photo si le colis est abîmé. Un échange, un avoir ou un remboursement est décidé au cas par cas.</p>
</section>
@endsection
