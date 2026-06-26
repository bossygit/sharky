<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — {{ config('app.name') }}</title>
    <style>
        :root { --bg:#f4f6f8; --card:#fff; --text:#111; --muted:#6b7280; --primary:#0a3d62; --border:#e5e7eb; --danger:#dc2626; --ok:#16a34a; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,sans-serif; background:var(--bg); color:var(--text); }
        a { color:var(--primary); text-decoration:none; }
        .wrap { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
        .sidebar { background:var(--primary); color:#fff; padding:1rem; }
        .sidebar a { color:#dbeafe; display:block; padding:.5rem .75rem; border-radius:6px; margin-bottom:.25rem; }
        .sidebar a.active, .sidebar a:hover { background:rgba(255,255,255,.12); color:#fff; text-decoration:none; }
        .brand { font-weight:700; margin-bottom:1rem; font-size:1.1rem; }
        .main { padding:1.5rem; }
        .card { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:1rem 1.25rem; margin-bottom:1rem; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; }
        .stat { font-size:1.75rem; font-weight:700; }
        .muted { color:var(--muted); font-size:.9rem; }
        .btn { display:inline-block; padding:.55rem 1rem; border-radius:6px; border:1px solid var(--border); background:#fff; cursor:pointer; font:inherit; }
        .btn-primary { background:var(--primary); color:#fff; border-color:var(--primary); }
        .btn-danger { background:var(--danger); color:#fff; border-color:var(--danger); }
        .btn-ok { background:var(--ok); color:#fff; border-color:var(--ok); }
        .alert { padding:.75rem 1rem; border-radius:6px; margin-bottom:1rem; }
        .alert-success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
        .alert-error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:.6rem .5rem; border-bottom:1px solid var(--border); vertical-align:top; }
        label { display:block; font-weight:600; margin:.75rem 0 .25rem; }
        input, select, textarea { width:100%; padding:.55rem .65rem; border:1px solid var(--border); border-radius:6px; font:inherit; }
        textarea { min-height:120px; }
        .row { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        @media (max-width:900px) { .wrap { grid-template-columns:1fr; } .row { grid-template-columns:1fr; } }
        .badge { display:inline-block; padding:.15rem .45rem; border-radius:999px; font-size:.75rem; background:#eef2ff; }
        .badge-draft { background:#fef3c7; }
        .badge-published { background:#dcfce7; }
        .badge-archived { background:#fee2e2; }
        .badge-urgency-low { background:#e5e7eb; }
        .badge-urgency-medium { background:#fef3c7; }
        .badge-urgency-high { background:#fed7aa; }
        .badge-urgency-viral { background:#fecaca; color:#991b1b; }
        .actions { display:flex; gap:.5rem; flex-wrap:wrap; }
        .img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:.75rem; margin-top:.75rem; }
        .img-grid img { width:100%; aspect-ratio:1; object-fit:cover; border:1px solid var(--border); border-radius:6px; }
    </style>
    @stack('head')
</head>
<body>
<div class="wrap">
    <aside class="sidebar">
        <div class="brand">nasande admin</div>
        <nav>
            <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Tableau de bord</a>
            <a href="{{ route('admin.products.index') }}" @class(['active' => request()->routeIs('admin.products.*')])>Produits</a>
            <a href="{{ route('admin.brands.index') }}" @class(['active' => request()->routeIs('admin.brands.*')])>Marques</a>
            <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Catégories</a>
            <a href="{{ route('admin.orders.index') }}" @class(['active' => request()->routeIs('admin.orders.*')])>Commandes</a>
            <a href="{{ route('admin.commerce.settings') }}" @class(['active' => request()->routeIs('admin.commerce.*')])>Commerce</a>
            <a href="{{ route('admin.search.synonyms') }}" @class(['active' => request()->routeIs('admin.search.*')])>Synonymes</a>
            <a href="{{ route('admin.demand.index') }}" @class(['active' => request()->routeIs('admin.demand.*')])>Demande</a>
            <a href="{{ route('admin.enrichment.index') }}" @class(['active' => request()->routeIs('admin.enrichment.*')])>Enrichissement IA</a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener">Voir le site</a>
        </nav>
        <form method="POST" action="{{ route('admin.logout') }}" style="margin-top:2rem;">
            @csrf
            <button type="submit" class="btn" style="width:100%;">Déconnexion</button>
        </form>
    </aside>
    <main class="main">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
