<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Maquis Express</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
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
        .auth-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        }
        .auth-badge {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: var(--color-accent);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            font-family: inherit;
            font-size: 14px;
            padding: 11px 13px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            margin-top: 6px;
        }
        input:focus { outline: none; border-color: var(--color-accent); box-shadow: 0 0 0 3px rgba(255,122,0,0.12); }
        label { font-size: 13px; font-weight: 600; }
        .btn-primary {
            width: 100%;
            background: var(--color-accent);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 700;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            margin-top: 8px;
        }
        .error { color: var(--color-danger); font-size: 13px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="auth-card">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>