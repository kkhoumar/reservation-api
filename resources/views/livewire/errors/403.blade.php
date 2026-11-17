<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé — Maquis Express</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primary: #063B27;
            --color-accent: #FF7A00;
            --color-danger: #E84A3A;
            --color-text: #1F2933;
            --color-text-secondary: #6B7280;
            --color-border: #E5E7EB;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--color-primary);
            background-image: radial-gradient(circle at 20% 20%, rgba(255,255,255,0.06), transparent 45%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: var(--color-text);
        }
        .error-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        }
        .icon-circle {
            width: 56px; height: 56px;
            border-radius: 50%;
            background: rgba(232,74,58,0.1);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px auto;
        }
        h1 { font-size: 20px; font-weight: 800; margin: 0 0 8px 0; }
        p { font-size: 14px; color: var(--color-text-secondary); margin: 0 0 24px 0; line-height: 1.5; }
        .btn-primary {
            display: inline-block;
            background: var(--color-accent);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            padding: 11px 22px;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="icon-circle">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E84A3A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
            </svg>
        </div>
        <h1>Accès non autorisé</h1>
        <p>
            @if(isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                Cette page n'est pas accessible avec votre rôle actuel.
            @endif
        </p>
        <a href="{{ url('/') }}" class="btn-primary">Retour à l'accueil</a>
    </div>
</body>
</html>