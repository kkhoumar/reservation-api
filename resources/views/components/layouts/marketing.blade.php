<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maquis Express — La gestion de votre maquis, simplifiée</title>
    <meta name="description" content="Commandes, comptoir, caisse, stock et crédits clients pour les maquis, bars et restaurants de Côte d'Ivoire. Espèces, Wave, Orange Money, et le rapport du soir sur WhatsApp.">
    <meta name="theme-color" content="#0B4D3C">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
    :root {
    --color-primary: #0B4D3C;
    --color-primary-700: #146652;
    --color-primary-300: #5EBFA0;
    --color-accent: #C1541E;
    --color-accent-light: #E0834F;
    --color-danger: #D93B3B;
    --color-bg: #FBF6EC;
    --color-surface: #FFFFFF;
    --color-text: #1F2933;
    --color-text-secondary: #6B7280;
    --color-border: #E7E1D3;
    --sidebar-width: 232px;
}
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            margin: 0;
        }
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(247,244,236,0.9);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--color-border);
            padding: 14px 32px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--color-text); }
        .nav-brand .badge {
            width: 32px; height: 32px; border-radius: 8px; background: var(--color-accent);
            color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;
        }
        .nav-brand .name { font-weight: 800; font-size: 15px; }
        .nav-links { display: flex; gap: 28px; align-items: center; }
        .nav-links a { color: var(--color-text-secondary); text-decoration: none; font-size: 14px; font-weight: 500; }
        .nav-links a:hover { color: var(--color-text); }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn-nav-primary {
            background: var(--color-accent); color: white; text-decoration: none;
            padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: 700;
        }
        .btn-nav-ghost { color: var(--color-text); text-decoration: none; font-size: 14px; font-weight: 600; }
        main { max-width: 1080px; margin: 0 auto; padding: 0 32px; }
        h1, h2, h3 { letter-spacing: -0.02em; }
        .btn-primary {
            display: inline-block; background: var(--color-accent); color: white; text-decoration: none;
            border: none; border-radius: 9px; padding: 14px 26px; font-weight: 700; font-size: 15px; cursor: pointer; font-family: inherit;
        }
        .btn-secondary-outline {
            display: inline-block; background: transparent; color: var(--color-text); text-decoration: none;
            border: 1.5px solid var(--color-border); border-radius: 9px; padding: 14px 26px; font-weight: 700; font-size: 15px; font-family: inherit;
        }
        footer {
            border-top: 1px solid var(--color-border); margin-top: 60px; padding: 32px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
        }
        footer .foot-links { display: flex; gap: 20px; }
        footer .foot-links a { color: var(--color-text-secondary); text-decoration: none; font-size: 13px; }
        @media (max-width: 860px) {
            .nav-links { display: none; }
        }
        @media (max-width: 600px) {
            .nav { padding: 12px 16px; }
            .nav-brand .name { display: none; }
            .btn-nav-primary { padding: 8px 12px; font-size: 13px; }
            main { padding: 0 16px; }
            footer { padding: 24px 16px; }
            footer .foot-links { flex-wrap: wrap; gap: 12px; }
        }
    </style>
</head>
<body>
    <nav class="nav">
        <a href="{{ route('landing') }}" class="nav-brand">
            <div class="badge">ME</div>
            <div class="name">Maquis Express</div>
        </a>
        <div class="nav-links">
            <a href="{{ route('landing') }}#fonctionnalites">Fonctionnalités</a>
            <a href="{{ route('landing') }}#comment-ca-marche">Comment ça marche</a>
            <a href="{{ route('landing') }}#tarifs">Tarifs</a>
            <a href="{{ route('landing') }}#questions">Questions</a>
        </div>
        <div class="nav-actions">
            <a href="{{ route('login') }}" class="btn-nav-ghost">Connexion</a>
            <a href="{{ route('demo.request') }}" class="btn-nav-primary">Demander une démo</a>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer>
        <div style="font-size: 13px; color: var(--color-text-secondary);">
            © {{ date('Y') }} Maquis Express. Tous droits réservés.
        </div>
        <div class="foot-links">
            <a href="{{ route('login') }}">Connexion</a>
            <a href="{{ route('demo.request') }}">Demander une démo</a>
            <a href="{{ route('legal.terms') }}">Conditions d'utilisation</a>
            <a href="{{ route('legal.privacy') }}">Confidentialité</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>