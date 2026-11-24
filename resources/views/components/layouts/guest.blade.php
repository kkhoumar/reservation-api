<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.pwa-head')
    <title>Connexion — Maquis Express</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
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
            --color-border: #DDE6E1;
            --font-display: 'Plus Jakarta Sans', 'Inter', sans-serif;
        }
        * { box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; margin: 0; color: var(--color-text); background: #fff; -webkit-font-smoothing: antialiased; }

        .auth { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }

        /* ---------- Panneau de marque ---------- */
        .brand { position: relative; overflow: hidden; color: white; padding: 48px 56px; display: flex; flex-direction: column; justify-content: space-between;
            background:
                radial-gradient(600px 360px at 90% 0%, rgba(94,191,160,.28), transparent 60%),
                radial-gradient(520px 320px at 0% 100%, rgba(210,98,42,.22), transparent 60%),
                linear-gradient(160deg, #0B4D3C 0%, #062E24 100%); }
        .brand::after { content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: radial-gradient(rgba(255,255,255,.07) 1px, transparent 1px); background-size: 22px 22px;
            mask-image: linear-gradient(to bottom, black, transparent 90%); -webkit-mask-image: linear-gradient(to bottom, black, transparent 90%); }
        .brand > * { position: relative; z-index: 1; }
        .logo { display: inline-flex; align-items: center; gap: 10px; color: white; text-decoration: none; font-family: var(--font-display); font-weight: 800; font-size: 17px; }
        .logo .badge { width: 38px; height: 38px; border-radius: 11px; background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark)); display: flex; align-items: center; justify-content: center; font-size: 13px; box-shadow: 0 8px 18px rgba(210,98,42,.35); }
        .brand h2 { font-family: var(--font-display); font-size: 34px; line-height: 1.15; letter-spacing: -0.025em; margin: 0 0 14px; max-width: 440px; }
        .brand h2 span { color: #FFB37F; }
        .brand p { font-size: 16px; color: rgba(255,255,255,.75); line-height: 1.6; margin: 0 0 28px; max-width: 420px; }
        .perks { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 14px; }
        .perks li { display: flex; gap: 12px; align-items: center; font-size: 15px; color: rgba(255,255,255,.9); }
        .perks .i { width: 34px; height: 34px; border-radius: 10px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.14); display: flex; align-items: center; justify-content: center; color: #FFB37F; flex-shrink: 0; }
        .mini { margin-top: 36px; background: white; color: var(--color-text); border-radius: 18px; padding: 16px 18px; max-width: 340px; box-shadow: 0 24px 50px rgba(0,0,0,.3); }
        .mini .row { display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0; }
        .mini .big { font-family: var(--font-display); font-weight: 800; font-size: 26px; color: var(--color-primary); }
        .brand .foot { font-size: 13px; color: rgba(255,255,255,.55); }

        /* ---------- Zone du formulaire ---------- */
        .side { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 40px 24px; background: #fff; }
        .auth-card { width: 100%; max-width: 400px; }
        .auth-card h1 { font-family: var(--font-display); letter-spacing: -0.02em; }
        .auth-badge {
            width: 46px; height: 46px; border-radius: 13px;
            background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark));
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; margin-bottom: 20px; font-family: var(--font-display);
            box-shadow: 0 8px 18px rgba(210,98,42,.3);
        }
        label { display: block; font-size: 13px; font-weight: 700; color: var(--color-text); }
        input[type=text], input[type=tel], input[type=password], input[type=email], input:not([type]) {
            width: 100%; font-family: inherit; font-size: 15px; padding: 13px 14px;
            border-radius: 12px; border: 1.5px solid var(--color-border); margin-top: 7px; background: #fff; color: var(--color-text);
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus { outline: none; border-color: var(--color-primary-300); box-shadow: 0 0 0 4px rgba(94,191,160,.18); }
        input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--color-primary); }
        .btn-primary {
            width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--color-accent); color: white; border: none; border-radius: 12px;
            padding: 14px; font-weight: 800; font-size: 15px; font-family: inherit; cursor: pointer; margin-top: 10px;
            box-shadow: 0 10px 22px rgba(210,98,42,.28); transition: background .15s, transform .15s;
            text-decoration: none;
        }
        .btn-primary:hover { background: var(--color-accent-dark); transform: translateY(-1px); }
        .btn-primary:disabled { opacity: .7; cursor: wait; transform: none; }
        .error { color: var(--color-danger); font-size: 13px; margin-top: 6px; }
        .alert { background: #FDECEC; color: #A32626; border: 1px solid #F6C9C9; border-radius: 12px; padding: 12px 14px; font-size: 14px; margin-bottom: 18px; }
        a { color: var(--color-accent); }
        .legal-links { text-align: center; font-size: 12px; margin-top: 28px; }
        .legal-links a { color: var(--color-text-secondary); text-decoration: none; margin: 0 8px; }
        .legal-links a:hover { color: var(--color-text); }

        @media (max-width: 900px) {
            .auth { grid-template-columns: 1fr; grid-template-rows: auto 1fr; }
            .brand { padding: 22px 20px; }
            .brand .pitch, .brand .mini, .brand .foot { display: none; }
            .side { justify-content: flex-start; padding: 32px 20px 40px; }
        }
    </style>
</head>
<body>
    <div class="auth">
        <aside class="brand">
            <a href="{{ Route::has('landing') ? route('landing') : '/' }}" class="logo"><span class="badge">ME</span> Maquis Express</a>

            <div class="pitch">
                <h2>Chaque bouteille vendue, <span>chaque franc encaissé.</span></h2>
                <p>Commandes, comptoir, caisse, stock et crédits clients, au même endroit.</p>
                <ul class="perks">
                    <li><span class="i"><x-icon name="receipt" size="18" /></span> Pas d'argent, pas de boisson : tout passe par le comptoir</li>
                    <li><span class="i"><x-icon name="cash" size="18" /></span> Une caisse qui tombe juste, chaque soir</li>
                    <li><span class="i"><x-icon name="message" size="18" /></span> Le rapport du soir sur le WhatsApp du patron</li>
                </ul>
                <div class="mini" aria-hidden="true">
                    <div style="font-size: 12px; color: var(--color-text-secondary);">Ventes de la soirée</div>
                    <div class="big">186 500 FCFA</div>
                    <div class="row"><span>Espèces</span><strong>121 000</strong></div>
                    <div class="row"><span>Wave · Orange Money</span><strong>48 500</strong></div>
                    <div class="row"><span>Écart de caisse</span><strong style="color: #1E8E5A;">0 F <x-icon name="check-circle" size="15" style="vertical-align: -2px;" /></strong></div>
                </div>
            </div>

            <div class="foot">© {{ date('Y') }} Maquis Express · Fait pour les maquis de Côte d'Ivoire</div>
        </aside>

        <main class="side">
            <div class="auth-card">
                {{ $slot }}
            </div>
            <div class="legal-links">
                <a href="{{ route('legal.terms') }}">Conditions d'utilisation</a>
                <a href="{{ route('legal.privacy') }}">Confidentialité</a>
            </div>
        </main>
    </div>
    @include('partials.whatsapp-fab', ['message' => "Bonjour, j'ai besoin d'aide pour me connecter à Maquis Express."])
    @include('partials.tap-feedback')
    @include('partials.offline-banner')
    @livewireScripts
</body>
</html>
