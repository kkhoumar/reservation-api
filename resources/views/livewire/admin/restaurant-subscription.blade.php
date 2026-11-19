@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    [$badge, $color] = $restaurant->subscriptionBadge();
    $end = $restaurant->accessEndsAt();
    $days = $restaurant->daysLeft();
@endphp

<div>
    <div style="margin-bottom: 20px;">
        <a href="{{ route('admin.restaurants') }}" style="font-size: 13px; color: var(--color-text-secondary); text-decoration: none;">← Tous les maquis</a>
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin: 8px 0 4px 0;">
            Gérer le maquis
        </div>
        <h1>{{ $restaurant->name }}</h1>
        <div style="font-size: 13px; color: var(--color-text-secondary);">Référence de paiement : <strong>{{ $restaurant->paymentReference() }}</strong></div>
    </div>

    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px;">
        <div class="card" style="padding: 14px 16px; border-left: 4px solid var(--color-{{ $color }});">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Situation</div>
            <div style="font-size: 20px; font-weight: 800; color: var(--color-{{ $color }});">{{ $badge }}</div>
            @if ($end)
                <div style="font-size: 12px; color: var(--color-text-secondary);">
                    {{ $restaurant->subscription_status === 'trial' ? 'Essai' : 'Payé' }} jusqu'au {{ $end->format('d/m/Y') }}
                    @if ($days !== null && $days >= 0) ({{ $days }} jour{{ $days > 1 ? 's' : '' }}) @endif
                </div>
            @elseif ($restaurant->subscription_status === 'active')
                <div style="font-size: 12px; color: var(--color-text-secondary);">Activé sans échéance</div>
            @endif
        </div>
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Formule</div>
            <div style="font-size: 20px; font-weight: 800;">{{ $restaurant->plan?->name ?? 'Aucune' }}</div>
            @if ($restaurant->plan)
                <div style="font-size: 12px; color: var(--color-text-secondary);">{{ $fcfa($restaurant->plan->monthly_price) }} FCFA / mois</div>
            @endif
        </div>
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Utilisateurs actifs</div>
            <div style="font-size: 20px; font-weight: 800;">{{ $activeUsers }}{{ $restaurant->userLimit() ? ' / ' . $restaurant->userLimit() : '' }}</div>
            <div style="font-size: 12px; color: var(--color-text-secondary);">{{ $restaurant->plan?->usersLabel() ?? 'Pas de limite tant qu\'aucune formule n\'est choisie' }}</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 16px; align-items: start;">
        <div class="card">
            <h3 style="margin-top: 0;">Enregistrer un paiement reçu</h3>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0;">
                L'accès est prolongé du nombre de mois payés et le maquis est réactivé s'il était bloqué.
            </p>

            <label style="font-size: 13px; font-weight: 600;">Formule</label>
            <select wire:model.live="planId" style="width: 100%; margin: 6px 0 12px 0;">
                @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} : {{ $fcfa($plan->monthly_price) }} F/mois · {{ $plan->usersLabel() }}</option>
                @endforeach
            </select>
            @error('planId') <div style="color: var(--color-danger); font-size: 12px; margin-bottom: 8px;">{{ $message }}</div> @enderror

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Durée</label>
                    <select wire:model.live="months" style="width: 100%; margin: 6px 0 12px 0;">
                        @foreach ($allowedMonths as $m)
                            <option value="{{ $m }}">{{ $m }} mois</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Payé par</label>
                    <select wire:model="method" style="width: 100%; margin: 6px 0 12px 0;">
                        @foreach ($methods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label style="font-size: 13px; font-weight: 600;">Montant reçu (FCFA)</label>
            <x-money-input model="amount" style="width: 100%; margin: 6px 0 4px 0; font-size: 18px;" />
            <div style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 12px;">Calculé automatiquement ; modifiable en cas de remise.</div>
            @error('amount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

            <label style="font-size: 13px; font-weight: 600;">Référence de la transaction</label>
            <input type="text" wire:model="reference" maxlength="100" placeholder="Facultatif" style="width: 100%; margin: 6px 0 12px 0;">

            <label style="font-size: 13px; font-weight: 600;">Note</label>
            <input type="text" wire:model="note" maxlength="255" placeholder="Facultatif" style="width: 100%; margin: 6px 0 16px 0;">

            <button wire:click="recordPayment" wire:confirm="Enregistrer ce paiement ?" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Enregistrer le paiement
            </button>
            <button wire:click="changePlan" class="btn btn-ghost" style="width: 100%; justify-content: center; margin-top: 8px;">
                Changer la formule sans paiement
            </button>
        </div>

        <div class="card">
            <h3 style="margin-top: 0;">Historique des paiements</h3>
            @forelse ($payments as $p)
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                    <span>
                        <strong>{{ $p->created_at->format('d/m/Y') }}</strong> · {{ $p->plan?->name ?? '—' }} · {{ $p->months }} mois
                        <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">
                            {{ $methods[$p->method] ?? $p->method }}@if ($p->reference) · réf. {{ $p->reference }}@endif
                            · du {{ $p->period_start->format('d/m/Y') }} au {{ $p->period_end->format('d/m/Y') }}
                            · par {{ $p->recordedBy?->first_name ?? '—' }}
                            @if ($p->note) · {{ $p->note }} @endif
                        </span>
                    </span>
                    <strong style="white-space: nowrap;">{{ $fcfa($p->amount) }} F</strong>
                </div>
            @empty
                <p style="font-size: 13px; color: var(--color-text-secondary);">Aucun paiement enregistré.</p>
            @endforelse
        </div>
    </div>

    {{-- ================= Zone dangereuse ================= --}}
    <div style="margin-top: 32px;">
        <h3 style="margin: 0 0 4px; color: var(--color-danger);">Zone dangereuse</h3>
        <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 14px;">Ces actions sont <strong>définitives</strong>. Pour confirmer, tapez le nom exact du maquis : <strong>{{ $restaurant->name }}</strong></p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">
            {{-- Vider les données de test --}}
            <div class="card" style="border: 1.5px solid #F3DDB8;">
                <h3 style="margin-top: 0;">Vider les données de test</h3>
                <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0; line-height: 1.55;">
                    Avant de remettre le maquis au client : efface les ventes, paiements, caisses, dépenses, crédits, versements, mouvements de stock et comptages.
                    <strong>Garde</strong> les réglages, l'équipe, le menu, les tables et l'abonnement.
                </p>
                <div style="background: var(--color-bg); border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 12px;">
                    Actuellement : {{ $activity['orders'] }} {{ $activity['orders'] > 1 ? 'commandes' : 'commande' }} · {{ $activity['registers'] }} {{ $activity['registers'] > 1 ? 'caisses' : 'caisse' }} · {{ $activity['expenses'] }} {{ $activity['expenses'] > 1 ? 'dépenses' : 'dépense' }} · {{ $activity['customers'] }} client(s) à crédit
                </div>
                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; margin-bottom: 6px; cursor: pointer;">
                    <input type="checkbox" wire:model="resetCustomers"> Effacer aussi les clients à crédit
                </label>
                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; margin-bottom: 12px; cursor: pointer;">
                    <input type="checkbox" wire:model="resetStock"> Remettre le stock de tous les produits à zéro
                </label>
                <input type="text" wire:model="resetConfirm" placeholder="Nom du maquis pour confirmer" autocomplete="off" style="width: 100%; margin-bottom: 6px;">
                @error('resetConfirm') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 6px;">{{ $message }}</div> @enderror
                <button wire:click="resetData" wire:confirm="Vider les données de test de {{ $restaurant->name }} ? C'est définitif." class="btn btn-ghost" style="width: 100%; justify-content: center; color: var(--color-warning); border-color: #F3DDB8; margin-top: 6px;">
                    Vider les données de test
                </button>
            </div>

            {{-- Supprimer le maquis --}}
            <div class="card" style="border: 1.5px solid #F6C9C9;">
                <h3 style="margin-top: 0;">Supprimer le maquis</h3>
                <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0; line-height: 1.55;">
                    Supprime le maquis et <strong>tout</strong> ce qui lui appartient : {{ $activity['users'] }} {{ $activity['users'] > 1 ? 'comptes' : 'compte' }} de l'équipe, le menu, les tables, les ventes, la caisse et l'historique d'abonnement.
                </p>
                @if ($activity['paid'] > 0)
                    <div style="background: var(--color-danger-bg); color: #8E2222; border-radius: 10px; padding: 10px 12px; font-size: 13px; margin-bottom: 12px;">
                        <x-icon name="alert" size="15" style="color: var(--color-warning);" /> Ce maquis a déjà payé {{ $fcfa($activity['paid']) }} FCFA d'abonnement. Vérifiez qu'il s'agit bien d'un maquis de test.
                    </div>
                @endif
                <input type="text" wire:model="deleteConfirm" placeholder="Nom du maquis pour confirmer" autocomplete="off" style="width: 100%; margin-bottom: 6px;">
                @error('deleteConfirm') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 6px;">{{ $message }}</div> @enderror
                <button wire:click="deleteRestaurant" wire:confirm="Supprimer définitivement {{ $restaurant->name }} et toutes ses données ?" class="btn btn-danger" style="width: 100%; justify-content: center; margin-top: 6px;">
                    Supprimer définitivement le maquis
                </button>
            </div>
        </div>
    </div>
</div>
