<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Maquis Express' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            font-feature-settings: 'tnum' 1;
        }

        .sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--color-primary);
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px 28px 8px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .sidebar-brand .badge {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--color-accent);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: -0.02em;
            flex-shrink: 0;
        }

        .sidebar-brand .name {
            color: white;
            font-weight: 700;
            font-size: 15px;
            line-height: 1.2;
        }

        .sidebar-brand .sub {
            color: rgba(255,255,255,0.5);
            font-size: 11px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 12px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.72);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .sidebar-link svg { flex-shrink: 0; opacity: 0.85; }

        .sidebar-link:hover {
            background: rgba(255,255,255,0.06);
            color: white;
        }

        .sidebar-link.active {
            background: var(--color-primary-700);
            color: white;
        }

        .sidebar-link.disabled {
            color: rgba(255,255,255,0.32);
            cursor: default;
            pointer-events: none;
        }

        .sidebar-link .soon {
            margin-left: auto;
            font-size: 10px;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 5px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 12px 8px 0 8px;
            border-top: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.5);
            font-size: 12px;
        }

        .app-shell {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar .date {
            font-size: 13px;
            color: var(--color-text-secondary);
        }

        .topbar .acting-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .topbar .avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: var(--color-primary-300);
            color: var(--color-primary);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        main {
            flex: 1;
            max-width: 1180px;
            width: 100%;
            margin: 0 auto;
            padding: 28px 32px 48px 32px;
        }

        .card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 1px 2px rgba(6,59,39,0.04);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            transition: filter 0.12s ease, transform 0.05s ease;
        }

        .btn:active { transform: translateY(1px); }
        .btn:hover { filter: brightness(1.05); }

        .btn-primary { background: var(--color-accent); color: white; }
        .btn-secondary { background: var(--color-primary); color: white; }
        .btn-ghost { background: var(--color-bg); color: var(--color-text); border: 1px solid var(--color-border); }
        .btn-danger { background: var(--color-danger); color: white; }

        select, input {
            font-family: inherit;
            font-size: 14px;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid var(--color-border);
            background: white;
        }

        select:focus, input:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 3px rgba(255,122,0,0.12);
        }

        h1 { font-size: 26px; font-weight: 800; letter-spacing: -0.01em; margin: 0; }
        h3 { font-size: 15px; font-weight: 700; }

        /* ---------- Responsive mobile ---------- */
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: var(--color-text);
        }

        @media (max-width: 860px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.2s ease;
                z-index: 100;
                width: 260px;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0,0,0,0.3);
            }
            .app-shell {
                margin-left: 0;
            }
            .mobile-menu-toggle {
                display: block;
            }
            .topbar {
                padding: 12px 16px;
            }
            .topbar .date {
                display: none;
            }
            .topbar .acting-user span {
                display: none;
            }
            main {
                padding: 16px;
            }
            h1 {
                font-size: 21px;
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.4);
                z-index: 90;
            }
            .sidebar-overlay.open {
                display: block;
            }
            div[style*="grid-template-columns: repeat(auto-fit"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-template-columns: 2fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-template-columns: repeat(2, 1fr)"] {
                grid-template-columns: 1fr !important;
            }
            div[style*="grid-template-columns: repeat(auto-fill"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
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
        <nav class="sidebar-nav">
            <a href="{{ route('dashboard.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                Tableau de bord
            </a>
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
                {{ $currentRestaurant->name }}
                @if ($currentRestaurant->commune || $currentRestaurant->city)
                    <br>{{ collect([$currentRestaurant->commune, $currentRestaurant->city])->filter()->join(', ') }}
                @endif
            @elseif (auth()->user()?->isPlatformAdmin())
                Super Admin
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
                        <div title="Argent encaissé à verser à la caisse" style="margin-right: 8px; padding: 5px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; background: {{ $sacoche > 0 ? 'rgba(193,84,30,0.12)' : 'rgba(94,191,160,0.15)' }}; color: {{ $sacoche > 0 ? 'var(--color-accent)' : 'var(--color-primary)' }};">
                            Ma sacoche : {{ number_format($sacoche, 0, ',', ' ') }} FCFA
                        </div>
                    @endif
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
                    <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }} · {{ auth()->user()->role }}</span>
                    <form method="POST" action="{{ route('logout') }}" style="margin: 0 0 0 10px;">
                        @csrf
                        <button type="submit" style="background: none; border: none; color: var(--color-text-secondary); cursor: pointer; font-size: 13px; font-family: inherit;">
                            Déconnexion
                        </button>
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