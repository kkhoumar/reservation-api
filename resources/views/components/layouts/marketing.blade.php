<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head')
    <title>Maquis Express · Commandes, caisse et stock pour les maquis</title>
    <meta name="description" content="Commandes, comptoir, caisse, stock et crédits clients pour les maquis, bars et restaurants de Côte d'Ivoire. Espèces, Wave, Orange Money, et le rapport du soir sur WhatsApp.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        :root {
            --color-primary: #0B4D3C;
            --color-primary-900: #062E24;
            --color-primary-700: #146652;
            --color-primary-300: #5EBFA0;
            --color-mint: #EEF6F2;
            --color-accent: #D2622A;
            --color-accent-dark: #B5501D;
            --color-accent-light: #F2A26F;
            --color-danger: #D93B3B;
            --color-bg: #FFFFFF;
            --color-surface: #FFFFFF;
            --color-text: #10231D;
            --color-text-secondary: #5B6B65;
            --color-border: #E3EAE6;
            --shadow-sm: 0 1px 2px rgba(6,46,36,0.06), 0 2px 8px rgba(6,46,36,0.04);
            --shadow-md: 0 10px 30px rgba(6,46,36,0.10);
            --shadow-lg: 0 30px 70px rgba(6,46,36,0.18);
            --font-display: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            overflow-x: clip;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3 { font-family: var(--font-display); letter-spacing: -0.02em; }
        a { color: var(--color-accent); }

        /* ---------- Barre du haut ---------- */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(255,255,255,0.85);
            backdrop-filter: saturate(160%) blur(12px);
            border-bottom: 1px solid transparent;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .nav.scrolled { border-bottom-color: var(--color-border); box-shadow: 0 6px 20px rgba(6,46,36,0.06); }
        .nav-inner { max-width: 1180px; margin: 0 auto; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--color-text); }
        .nav-brand .badge {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark));
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px; font-family: var(--font-display);
            box-shadow: 0 6px 14px rgba(210,98,42,0.35);
        }
        .nav-brand .name { font-weight: 800; font-size: 16px; font-family: var(--font-display); }
        .nav-links { display: flex; gap: 6px; align-items: center; }
        .nav-links a { color: var(--color-text-secondary); text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 12px; border-radius: 8px; transition: color .15s, background .15s; }
        .nav-links a:hover { color: var(--color-primary); background: var(--color-mint); }
        .nav-actions { display: flex; gap: 8px; align-items: center; }
        .btn-nav-primary {
            background: var(--color-accent); color: white; text-decoration: none;
            padding: 10px 18px; border-radius: 10px; font-size: 14px; font-weight: 700;
            transition: transform .15s, box-shadow .15s, background .15s;
            box-shadow: 0 6px 16px rgba(210,98,42,0.28);
        }
        .btn-nav-primary:hover { background: var(--color-accent-dark); transform: translateY(-1px); }
        .btn-nav-ghost { color: var(--color-text); text-decoration: none; font-size: 14px; font-weight: 700; padding: 10px 12px; }
        .menu-toggle { display: none; background: none; border: 1px solid var(--color-border); border-radius: 10px; width: 40px; height: 40px; cursor: pointer; color: var(--color-text); }

        /* ---------- Boutons communs ---------- */
        main { max-width: 1180px; margin: 0 auto; padding: 0 24px; }
        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--color-accent); color: white; text-decoration: none;
            border: none; border-radius: 12px; padding: 15px 26px; font-weight: 700; font-size: 15px; cursor: pointer; font-family: inherit;
            box-shadow: 0 10px 24px rgba(210,98,42,0.32);
            transition: transform .15s, box-shadow .15s, background .15s;
        }
        .btn-primary:hover { background: var(--color-accent-dark); transform: translateY(-2px); box-shadow: 0 14px 30px rgba(210,98,42,0.38); }
        .btn-secondary-outline {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: white; color: var(--color-text); text-decoration: none;
            border: 1.5px solid var(--color-border); border-radius: 12px; padding: 14px 24px; font-weight: 700; font-size: 15px; font-family: inherit;
            transition: border-color .15s, transform .15s;
        }
        .btn-secondary-outline:hover { border-color: var(--color-primary-300); transform: translateY(-2px); }

        /* ---------- Pied de page ---------- */
        footer { background: var(--color-primary-900); color: rgba(255,255,255,0.7); margin-top: 0; }
        .foot-inner { max-width: 1180px; margin: 0 auto; padding: 40px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; }
        footer .foot-brand { display: flex; align-items: center; gap: 10px; color: white; font-weight: 800; font-family: var(--font-display); }
        footer .foot-links { display: flex; gap: 22px; flex-wrap: wrap; }
        footer .foot-links a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; }
        footer .foot-links a:hover { color: white; }

        @media (max-width: 920px) {
            .nav-links {
                display: none; position: absolute; top: 100%; left: 0; right: 0;
                flex-direction: column; align-items: stretch; gap: 0;
                background: white; border-bottom: 1px solid var(--color-border); padding: 8px 16px 16px; box-shadow: var(--shadow-md);
            }
            .nav-links.open { display: flex; }
            .nav-links a { padding: 12px; }
            .menu-toggle { display: inline-flex; align-items: center; justify-content: center; }
        }
        @media (max-width: 600px) {
            .nav-inner { padding: 12px 16px; }
            .nav-brand .name { display: none; }
            .btn-nav-ghost { display: none; }
            .btn-nav-primary { padding: 9px 14px; font-size: 13px; }
            main { padding: 0 16px; }
        }
    </style>
</head>
<body>
    <nav class="nav" id="siteNav">
        <div class="nav-inner">
            <a href="{{ route('landing') }}" class="nav-brand">
                <div class="badge">ME</div>
                <div class="name">Maquis Express</div>
            </a>
            <div class="nav-links" id="navLinks">
                <a href="{{ route('landing') }}#fonctionnalites">Fonctionnalités</a>
                <a href="{{ route('landing') }}#comment-ca-marche">Comment ça marche</a>
                <a href="{{ route('landing') }}#tarifs">Tarifs</a>
                <a href="{{ route('landing') }}#questions">Questions</a>
                <a href="{{ route('login') }}" class="only-mobile">Connexion</a>
            </div>
            <div class="nav-actions">
                <a href="{{ route('login') }}" class="btn-nav-ghost">Connexion</a>
                <a href="{{ route('demo.request') }}" class="btn-nav-primary">Demander une démo</a>
                <button type="button" class="menu-toggle" aria-label="Ouvrir le menu" aria-expanded="false" onclick="toggleSiteMenu(this)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>

    <footer>
        <div class="foot-inner">
            <div>
                <div class="foot-brand"><div class="nav-brand"><div class="badge">ME</div></div> Maquis Express</div>
                <div style="font-size: 13px; margin-top: 8px;">Commandes, caisse et stock pour les maquis de Côte d'Ivoire. © {{ date('Y') }}</div>
            </div>
            <div class="foot-links">
                <a href="{{ route('login') }}">Connexion</a>
                <a href="{{ route('demo.request') }}">Demander une démo</a>
                <a href="{{ route('legal.terms') }}">Conditions d'utilisation</a>
                <a href="{{ route('legal.privacy') }}">Confidentialité</a>
            </div>
        </div>
    </footer>

    <script>
        // Barre du haut : ombre au défilement
        (function () {
            var nav = document.getElementById('siteNav');
            var onScroll = function () { nav.classList.toggle('scrolled', window.scrollY > 8); };
            onScroll();
            window.addEventListener('scroll', onScroll, { passive: true });
        })();

        // Menu mobile
        function toggleSiteMenu(btn) {
            var links = document.getElementById('navLinks');
            var open = links.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        document.querySelectorAll('#navLinks a').forEach(function (a) {
            a.addEventListener('click', function () { document.getElementById('navLinks').classList.remove('open'); });
        });
    </script>
    <style>
        .only-mobile { display: none; }
        @media (max-width: 600px) { .nav-links .only-mobile { display: block; } }
    </style>

    @include('partials.tap-feedback')
    @livewireScripts
</body>
</html>
