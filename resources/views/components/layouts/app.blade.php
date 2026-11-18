<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Maquis Express' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @livewireStyles
    <style>
        /* =====================================================================
           Maquis Express — système de design de l'application
           Mêmes couleurs que le site public et la page de connexion.
           ===================================================================== */
        :root {
            /* Marque */
            --color-primary: #0B4D3C;
            --color-primary-900: #062E24;
            --color-primary-700: #146652;
            --color-primary-300: #5EBFA0;
            --color-mint: #EEF6F2;
            --color-accent: #D2622A;
            --color-accent-dark: #B5501D;
            --color-accent-light: #F2A26F;
            /* États */
            --color-success: #1E8E5A;
            --color-success-bg: #E8F6EE;
            --color-warning: #B7791F;
            --color-warning-bg: #FDF4E3;
            --color-danger: #D93B3B;
            --color-danger-bg: #FDECEC;
            /* Surfaces et texte */
            --color-bg: #F4F7F5;
            --color-surface: #FFFFFF;
            --color-text: #10231D;
            --color-text-secondary: #5B6B65;
            --color-border: #E3EAE6;
            --shadow-sm: 0 1px 2px rgba(6,46,36,.05), 0 1px 6px rgba(6,46,36,.04);
            --shadow-md: 0 8px 24px rgba(6,46,36,.08);
            --font-display: 'Plus Jakarta Sans', 'Inter', sans-serif;
            --sidebar-width: 248px;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            font-feature-settings: 'tnum' 1;
            -webkit-font-smoothing: antialiased;
        }

        a { color: var(--color-accent); }

        /* ---------- Menu latéral ---------- */
        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background:
                radial-gradient(420px 260px at 100% 0%, rgba(94,191,160,.16), transparent 60%),
                linear-gradient(180deg, #0B4D3C 0%, #062E24 100%);
            display: flex; flex-direction: column;
            padding: 22px 14px;
            overflow-y: auto;
        }
        .sidebar-brand {
            display: flex; align-items: center; gap: 11px;
            padding: 0 8px 22px 8px; margin-bottom: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.10);
        }
        .sidebar-brand .badge {
            width: 38px; height: 38px; border-radius: 11px;
            background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark));
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px; font-family: var(--font-display);
            box-shadow: 0 6px 14px rgba(210,98,42,.35); flex-shrink: 0;
        }
        .sidebar-brand .name { color: white; font-weight: 800; font-size: 15px; line-height: 1.2; font-family: var(--font-display); }
        .sidebar-brand .sub { color: rgba(255,255,255,0.5); font-size: 11px; }

        .sidebar-nav { display: flex; flex-direction: column; gap: 2px; margin-top: 10px; }
        .nav-group {
            font-size: 10.5px; font-weight: 800; letter-spacing: .09em; text-transform: uppercase;
            color: rgba(255,255,255,0.38); padding: 16px 12px 6px;
        }
        .sidebar-link {
            position: relative; display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 10px;
            color: rgba(255,255,255,0.74); text-decoration: none;
            font-size: 14px; font-weight: 600;
            transition: background .15s ease, color .15s ease;
        }
        .sidebar-link svg { flex-shrink: 0; opacity: .85; }
        .sidebar-link:hover { background: rgba(255,255,255,0.07); color: white; }
        .sidebar-link.active { background: rgba(255,255,255,0.12); color: white; }
        .sidebar-link.active::before {
            content: ''; position: absolute; left: -14px; top: 8px; bottom: 8px; width: 4px;
            border-radius: 0 4px 4px 0; background: var(--color-accent);
        }
        .sidebar-link.active svg { opacity: 1; color: #FFB37F; }
        .sidebar-link.disabled { color: rgba(255,255,255,0.32); cursor: default; pointer-events: none; }
        .sidebar-link .soon { margin-left: auto; font-size: 10px; font-weight: 600; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 5px; }

        .sidebar-footer {
            margin-top: auto; padding: 14px 10px 0;
            border-top: 1px solid rgba(255,255,255,0.10);
            color: rgba(255,255,255,0.55); font-size: 12px; line-height: 1.5;
        }
        .sidebar-footer strong { color: white; font-size: 13px; }

        /* ---------- Structure ---------- */
        .app-shell { margin-left: var(--sidebar-width); min-height: 100vh; display: flex; flex-direction: column; }

        .topbar {
            position: sticky; top: 0; z-index: 40;
            background: rgba(255,255,255,0.88);
            backdrop-filter: saturate(160%) blur(10px);
            border-bottom: 1px solid var(--color-border);
            padding: 12px 32px;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
        }
        .topbar .date { font-size: 13px; color: var(--color-text-secondary); font-weight: 500; }
        .topbar .acting-user { display: flex; align-items: center; gap: 10px; font-size: 13px; }
        .topbar .avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary-300), var(--color-primary-700));
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px; font-family: var(--font-display);
        }
        .topbar .who { line-height: 1.25; }
        .topbar .who strong { display: block; font-size: 13px; color: var(--color-text); }
        .topbar .who small { color: var(--color-text-secondary); font-size: 12px; }
        .topbar .logout {
            background: white; border: 1px solid var(--color-border); color: var(--color-text-secondary);
            cursor: pointer; font-size: 13px; font-weight: 600; font-family: inherit;
            padding: 7px 12px; border-radius: 9px; transition: color .15s, border-color .15s;
        }
        .topbar .logout:hover { color: var(--color-danger); border-color: #F3C2C2; }

        main { flex: 1; max-width: 1200px; width: 100%; margin: 0 auto; padding: 28px 32px 56px; }

        /* ---------- Titres ---------- */
        h1, h2, h3 { font-family: var(--font-display); letter-spacing: -0.015em; color: var(--color-text); }
        h1 { font-size: 26px; font-weight: 800; margin: 0; }
        h3 { font-size: 16px; font-weight: 800; }

        /* ---------- Cartes ---------- */
        .card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
        }
        a.card { transition: transform .15s ease, box-shadow .15s ease; }
        a.card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

        /* ---------- Boutons ---------- */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            border: 1px solid transparent; border-radius: 10px;
            padding: 10px 18px; font-weight: 700; font-size: 14px; font-family: inherit;
            cursor: pointer; text-decoration: none; line-height: 1.2;
            transition: background .15s ease, border-color .15s ease, transform .05s ease, box-shadow .15s ease;
        }
        .btn:active { transform: translateY(1px); }
        .btn:focus-visible { outline: none; box-shadow: 0 0 0 4px rgba(94,191,160,.30); }
        .btn:disabled, .btn[disabled] { opacity: .6; cursor: not-allowed; }
        .btn-primary { background: var(--color-accent); color: white; box-shadow: 0 4px 12px rgba(210,98,42,.22); }
        .btn-primary:hover { background: var(--color-accent-dark); }
        .btn-secondary { background: var(--color-primary); color: white; }
        .btn-secondary:hover { background: var(--color-primary-700); }
        .btn-ghost { background: white; color: var(--color-text); border-color: var(--color-border); }
        .btn-ghost:hover { border-color: var(--color-primary-300); background: var(--color-mint); }
        .btn-danger { background: var(--color-danger); color: white; }
        .btn-danger:hover { background: #BF2F2F; }

        /* ---------- Formulaires ---------- */
        select, input, textarea {
            font-family: inherit; font-size: 14px; color: var(--color-text);
            padding: 10px 12px; border-radius: 10px;
            border: 1px solid var(--color-border); background: white;
            transition: border-color .15s, box-shadow .15s;
        }
        input[type=checkbox], input[type=radio] { padding: 0; accent-color: var(--color-primary); width: 16px; height: 16px; }
        select:focus, input:focus, textarea:focus {
            outline: none; border-color: var(--color-primary-300);
            box-shadow: 0 0 0 4px rgba(94,191,160,.18);
        }
        label { color: var(--color-text); }
        ::placeholder { color: #9AA8A2; }

        /* ---------- Tableaux ---------- */
        table { border-collapse: collapse; }
        thead th { font-size: 11.5px !important; text-transform: uppercase; letter-spacing: .05em; color: var(--color-text-secondary) !important; font-weight: 700 !important; }
        tbody tr { transition: background .12s ease; }
        tbody tr:hover { background: #F8FBF9; }

        /* ---------- Messages ---------- */
        .flash { display: flex; gap: 10px; align-items: flex-start; border-radius: 14px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; border: 1px solid; }
        .flash-error { background: var(--color-danger-bg); border-color: #F6C9C9; color: #8E2222; }
        .flash-success { background: var(--color-success-bg); border-color: #BFE5CF; color: #145C3A; }

        /* Les messages des écrans (carte à bordure gauche colorée) prennent
           automatiquement un fond teinté assorti : succès, erreur, alerte */
        div.card[style*="border-left: 6px solid var(--color-danger)"] { background: var(--color-danger-bg); border-color: #F6CFCF; }
        div.card[style*="border-left: 6px solid var(--color-primary-300)"] { background: var(--color-success-bg); border-color: #C6E8D4; }
        div.card[style*="border-left: 6px solid var(--color-accent)"] { background: var(--color-warning-bg); border-color: #F3DDB8; }

        /* Pagination Livewire (sans Tailwind) */
        nav[role=navigation] svg { width: 16px; height: 16px; }
        nav[role=navigation] { font-size: 13px; }

        /* ---------- Téléphone ---------- */
        .mobile-menu-toggle { display: none; background: white; border: 1px solid var(--color-border); border-radius: 10px; width: 40px; height: 40px; cursor: pointer; color: var(--color-text); align-items: center; justify-content: center; }

        @media (max-width: 860px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.22s ease; z-index: 100; width: 270px; }
            .sidebar.open { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,0.3); }
            .app-shell { margin-left: 0; }
            .mobile-menu-toggle { display: inline-flex; }
            .topbar { padding: 10px 16px; }
            .topbar .date, .topbar .who { display: none; }
            main { padding: 16px 16px 40px; }
            h1 { font-size: 22px; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(6,46,36,0.45); z-index: 90; }
            .sidebar-overlay.open { display: block; }
            div[style*="grid-template-columns: repeat(auto-fit"] { grid-template-columns: 1fr !important; }
            div[style*="grid-template-columns: 2fr 1fr"],
            div[style*="grid-template-columns: 1fr 1.4fr"],
            div[style*="grid-template-columns: 1fr 1.6fr"],
            div[style*="grid-template-columns: 1fr 1.3fr"],
            div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
            div[style*="grid-template-columns: repeat(2, 1fr)"] { grid-template-columns: 1fr !important; }
            div[style*="grid-template-columns: repeat(auto-fill"] { grid-template-columns: repeat(2, 1fr) !important; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="badge">ME</div>
            <div>
                <div class="name">Maquis Express</div>
                <div class="sub">Gestion de restaurant</div>
            </div>
        </div>

        @php
            $currentRestaurant = auth()->user()?->currentRestaurant();
            // Maquis verrouillé : lecture seule, on cache les écrans qui servent à vendre
            $readOnly = \App\Support\ReadOnlyGuard::isReadOnly();
        @endphp
        @php $canManage = auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager'], true)); @endphp
        <nav class="sidebar-nav">
            @if ($canManage)
                <div class="nav-group">Pilotage</div>
                <a href="{{ route('dashboard.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                    Tableau de bord
                </a>
            @endif
            <div class="nav-group">Service</div>
            @if ($readOnly)
                {{-- rien pour vendre --}}
            @elseif ($currentRestaurant?->usesTables() ?? true)
                <a href="{{ route('tables.index') }}" class="sidebar-link {{ request()->routeIs('tables.index') || request()->routeIs('orders.new') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/><line x1="15" y1="9" x2="15" y2="21"/></svg>
                    Tables & commandes
                </a>
            @else
                <a href="{{ route('orders.new') }}" class="sidebar-link {{ request()->routeIs('orders.new') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Vente rapide
                </a>
            @endif
            @php
                $paysBefore = $currentRestaurant?->paysBeforeService() ?? false;
                // Le maquis s'adapte au personnel : sans serveuse, la personne qui
                // prend la commande encaisse elle-même (pas de comptoir, pas de sacoche)
                $hasServers = $currentRestaurant?->hasActiveServers() ?? false;
                $showCounter = $paysBefore && ($hasServers || \App\Models\Order::where('status', 'awaiting_payment')->exists());
                $showSacoches = ! $paysBefore && $currentRestaurant
                    && ($hasServers || \App\Support\PeriodStats::sacochesOutstanding($currentRestaurant->id) > 0);
            @endphp
            @if(! $readOnly && $showCounter && auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager', 'cashier'])))
                <a href="{{ route('counter') }}" class="sidebar-link {{ request()->routeIs('counter') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10h18v10H3z"/><path d="M7 10V6h10v4"/><path d="M12 14h.01"/></svg>
                    Comptoir
                </a>
            @endif
            @if(($currentRestaurant?->creditEnabled() ?? false) && auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager', 'cashier'])))
                <a href="{{ route('credits') }}" class="sidebar-link {{ request()->routeIs('credits') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                    Crédits
                </a>
            @endif
            @if(! $readOnly && auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager', 'cashier'])))
                <a href="{{ route('cash-register.manage') }}" class="sidebar-link {{ request()->routeIs('cash-register.manage') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3v4M8 3v4"/></svg>
                    Caisse
                </a>
                @if ($showSacoches)
                    <a href="{{ route('sacoches') }}" class="sidebar-link {{ request()->routeIs('sacoches') ? 'active' : '' }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8h12l1 12H5L6 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                        Sacoches
                    </a>
                @endif
            @endif
            @if(auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager'])))
                <a href="{{ route('cash-register.history') }}" class="sidebar-link {{ request()->routeIs('cash-register.history') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9M13 17V5M8 17v-3"/></svg>
                    Historique caisse
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager', 'cashier'])))
                <a href="{{ route('sales.today') }}" class="sidebar-link {{ request()->routeIs('sales.today') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Ventes du jour
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager', 'cashier'])))
                <a href="{{ route('expenses.index') }}" class="sidebar-link {{ request()->routeIs('expenses.index') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 9V7a5 5 0 0 0-10 0v2"/><rect x="3" y="9" width="18" height="12" rx="2"/></svg>
                    Dépenses
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->isPlatformAdmin() || in_array(auth()->user()->role, ['owner', 'manager'])))
                <div class="nav-group">Gestion</div>
                <a href="{{ route('users.manage') }}" class="sidebar-link {{ request()->routeIs('users.manage') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Utilisateurs
                </a>
                @unless ($readOnly)
                    <a href="{{ route('products.manage') }}" class="sidebar-link {{ request()->routeIs('products.manage') ? 'active' : '' }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h18v18H3z"/><path d="M8 21V9"/></svg>
                        Menu & produits
                    </a>
                @endunless
                <a href="{{ route('stock.index') }}" class="sidebar-link {{ request()->routeIs('stock.index') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
                    Stock
                </a>
                <a href="{{ route('exports.index') }}" class="sidebar-link {{ request()->routeIs('exports.*') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Exports
                </a>
            @endif
            @if(auth()->check() && (auth()->user()->isPlatformAdmin() || auth()->user()->role === 'owner'))
                <a href="{{ route('settings') }}" class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Paramètres
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            @if ($currentRestaurant)
                <strong>{{ $currentRestaurant->name }}</strong>
                @if ($currentRestaurant->commune || $currentRestaurant->city)
                    <br>{{ collect([$currentRestaurant->commune, $currentRestaurant->city])->filter()->join(', ') }}
                @endif
            @elseif (auth()->user()?->isPlatformAdmin())
                <strong>Super Admin</strong>
            @endif
            <div style="margin-top: 8px;">
                <a href="{{ route('legal.terms') }}" target="_blank" style="color: rgba(255,255,255,0.5); font-size: 11px;">Conditions</a>
                · <a href="{{ route('legal.privacy') }}" target="_blank" style="color: rgba(255,255,255,0.5); font-size: 11px;">Confidentialité</a>
            </div>
        </div>
    </aside>

    <div class="app-shell">
        <div class="topbar">
            <button class="mobile-menu-toggle" onclick="toggleSidebar()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <div class="date">{{ ucfirst(\Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY')) }}</div>
            <div class="acting-user">
                @auth
                    @php $sacoche = auth()->user()->isServer() ? auth()->user()->sacocheBalance() : 0; @endphp
                    @if (auth()->user()->isServer() && (! $paysBefore || $sacoche > 0))
                        <div title="Argent encaissé à verser à la caisse" style="padding: 6px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; background: {{ $sacoche > 0 ? 'var(--color-warning-bg)' : 'var(--color-success-bg)' }}; color: {{ $sacoche > 0 ? 'var(--color-accent-dark)' : 'var(--color-success)' }};">
                            Ma sacoche : {{ number_format($sacoche, 0, ',', ' ') }} FCFA
                        </div>
                    @endif
                    @php
                        $roleLabels = ['owner' => 'Patron', 'manager' => 'Gérante', 'cashier' => 'Caisse / comptoir', 'server' => 'Serveuse'];
                        $roleLabel = auth()->user()->isPlatformAdmin() ? 'Super Admin' : ($roleLabels[auth()->user()->role] ?? auth()->user()->role);
                    @endphp
                    <div class="avatar">{{ strtoupper(mb_substr(auth()->user()->first_name, 0, 1)) }}</div>
                    <div class="who">
                        <strong>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</strong>
                        <small>{{ $roleLabel }}</small>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0 0 0 6px;">
                        @csrf
                        <button type="submit" class="logout" title="Se déconnecter">Déconnexion</button>
                    </form>
                @endauth
            </div>
        </div>

        <main>
            @if (auth()->user()?->isPlatformAdmin())
                <div class="card" style="border-left: 6px solid var(--color-accent); margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                    <div style="font-size: 14px;">
                        <strong>Mode Super Admin</strong>
                        @if ($currentRestaurant)
                            — vous êtes dans <strong>{{ $currentRestaurant->name }}</strong>
                            @if ($currentRestaurant->isLocked())
                                <span style="color: var(--color-danger); font-weight: 600;">(verrouillé)</span>
                            @endif
                        @endif
                    </div>
                    <div style="display: flex; gap: 8px;">
                        @php $newDemos = \App\Models\DemoRequest::where('status', 'new')->count(); @endphp
                        @if ($newDemos > 0)
                            <a href="{{ route('admin.restaurants') }}" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">
                                🔔 {{ $newDemos }} nouvelle{{ $newDemos > 1 ? 's' : '' }} demande{{ $newDemos > 1 ? 's' : '' }} de démo
                            </a>
                        @endif
                        <a href="{{ route('admin.restaurants') }}" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">Tous les maquis</a>
                        @if ($currentRestaurant)
                            <form method="POST" action="{{ route('admin.leave-restaurant') }}" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="padding: 7px 14px; font-size: 13px;">Quitter ce maquis</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Lecture seule : abonnement impayé, suspendu ou essai terminé --}}
            @if ($readOnly)
                <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px;">
                    <span>
                        <strong>Mode lecture seule.</strong>
                        @if ($currentRestaurant->isTrialExpired())
                            Votre essai gratuit est terminé.
                        @elseif ($currentRestaurant->isSubscriptionExpired())
                            Votre abonnement n'a pas été renouvelé (payé jusqu'au {{ $currentRestaurant->paid_until->format('d/m/Y') }}).
                        @else
                            L'accès de votre maquis est suspendu.
                        @endif
                        Vous pouvez consulter vos chiffres, mais plus vendre ni enregistrer. Vos données sont conservées.
                    </span>
                    @if (auth()->user()->role === 'owner')
                        <a href="{{ route('settings') }}#abonnement" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">Payer l'abonnement</a>
                    @endif
                </div>
            @endif

            {{-- Rappel d'abonnement : échéance proche ou dépassée (patron et gérante) --}}
            @if ($currentRestaurant && in_array(auth()->user()?->role, ['owner', 'manager'], true) && $currentRestaurant->needsRenewalReminder())
                @php $daysLeft = $currentRestaurant->daysLeft(); @endphp
                <div class="card" style="border-left: 6px solid {{ $currentRestaurant->isInGracePeriod() ? 'var(--color-danger)' : 'var(--color-accent)' }}; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 14px;">
                    <span>
                        @if ($currentRestaurant->isInGracePeriod())
                            <strong>Abonnement à renouveler :</strong> l'échéance est passée. L'accès sera coupé le
                            {{ $currentRestaurant->lockAt()->format('d/m/Y') }} à {{ \App\Models\Restaurant::LOCK_HOUR }} h.
                        @elseif ($currentRestaurant->subscription_status === 'trial')
                            <strong>Votre essai gratuit se termine {{ $daysLeft === 0 ? "aujourd'hui" : 'dans ' . $daysLeft . ' jour' . ($daysLeft > 1 ? 's' : '') }}</strong>
                            ({{ $currentRestaurant->trial_ends_at->format('d/m/Y') }}).
                        @else
                            <strong>Votre abonnement se termine {{ $daysLeft === 0 ? "aujourd'hui" : 'dans ' . $daysLeft . ' jour' . ($daysLeft > 1 ? 's' : '') }}</strong>
                            ({{ $currentRestaurant->paid_until->format('d/m/Y') }}).
                        @endif
                    </span>
                    @if (auth()->user()->role === 'owner')
                        <a href="{{ route('settings') }}#abonnement" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">Renouveler</a>
                    @endif
                </div>
            @endif

            @if (session('error'))
                <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
    </script>

    @livewireScripts
</body>
</html>