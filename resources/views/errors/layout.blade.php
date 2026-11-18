<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — Maquis Express</title>
    <meta name="theme-color" content="#0B4D3C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            font-family: 'Inter', -apple-system, sans-serif; color: #10231D; -webkit-font-smoothing: antialiased;
            background: radial-gradient(700px 380px at 85% 0%, rgba(94,191,160,.25), transparent 60%), linear-gradient(160deg, #0B4D3C, #062E24); }
        .box { background: white; border-radius: 22px; padding: 40px 36px; max-width: 440px; width: 100%; text-align: center; box-shadow: 0 30px 70px rgba(0,0,0,.3); }
        .code { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 13px; letter-spacing: .1em; color: #D2622A; background: #FDF0E8; display: inline-block; padding: 6px 12px; border-radius: 999px; }
        .icon { width: 64px; height: 64px; border-radius: 50%; background: #EEF6F2; display: flex; align-items: center; justify-content: center; margin: 18px auto 14px; font-size: 30px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; letter-spacing: -.02em; margin: 0 0 10px; }
        p { color: #5B6B65; font-size: 15px; line-height: 1.6; margin: 0 0 26px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; background: #D2622A; color: white; text-decoration: none; font-weight: 700; font-size: 15px; padding: 13px 24px; border-radius: 12px; box-shadow: 0 10px 22px rgba(210,98,42,.28); }
        .btn:hover { background: #B5501D; }
        .back { display: block; margin-top: 14px; color: #5B6B65; font-size: 14px; }
    </style>
</head>
<body>
    @php
        $home = auth()->check()
            ? (auth()->user()->isPlatformAdmin() ? route('admin.restaurants') : route('tables.index'))
            : route('login');
    @endphp
    <div class="box">
        <span class="code">ERREUR @yield('code')</span>
        <div class="icon">@yield('icon')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ $home }}" class="btn">{{ auth()->check() ? 'Retour à l\'application' : 'Aller à la connexion' }}</a>
        <a href="javascript:history.back()" class="back">← Revenir en arrière</a>
    </div>
</body>
</html>
