<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion admin — {{ config('app.name') }}</title>
    <style>
        body { font-family:system-ui,sans-serif; background:#f4f6f8; display:grid; place-items:center; min-height:100vh; margin:0; }
        .card { background:#fff; padding:2rem; border-radius:10px; width:min(420px,92vw); box-shadow:0 8px 24px rgba(0,0,0,.08); }
        h1 { margin:0 0 1rem; font-size:1.35rem; }
        label { display:block; font-weight:600; margin:.75rem 0 .25rem; }
        input { width:100%; padding:.6rem .7rem; border:1px solid #e5e7eb; border-radius:6px; }
        button { margin-top:1rem; width:100%; padding:.7rem; background:#0a3d62; color:#fff; border:0; border-radius:6px; font:inherit; cursor:pointer; }
        .error { color:#dc2626; font-size:.9rem; margin-top:.25rem; }
    </style>
</head>
<body>
<div class="card">
    <h1>Administration Nasande Vins</h1>
    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        @error('email')<div class="error">{{ $message }}</div>@enderror

        <label for="password">Mot de passe</label>
        <input id="password" type="password" name="password" required>
        @error('password')<div class="error">{{ $message }}</div>@enderror

        <label><input type="checkbox" name="remember"> Se souvenir de moi</label>
        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>
