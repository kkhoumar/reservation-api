<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Mon maquis
        </div>
        <h1>Paramètres</h1>
    </div>

    <form wire:submit="save" style="max-width: 560px;">
        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Comment servez-vous vos clients ?</h3>

            @foreach ($modes as $value => $mode)
                <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid {{ $serviceMode === $value ? 'var(--color-primary)' : 'var(--color-border)' }}; border-radius: 10px; margin-bottom: 8px; cursor: pointer;">
                    <input type="radio" wire:model.live="serviceMode" value="{{ $value }}" style="margin-top: 3px;">
                    <span>
                        <strong>{{ $mode['label'] }}</strong>
                        <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">{{ $mode['hint'] }}</span>
                    </span>
                </label>
            @endforeach
            @error('serviceMode') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Moment du paiement</h3>
            @foreach ($timings as $value => $timing)
                <label style="display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid {{ $paymentTiming === $value ? 'var(--color-primary)' : 'var(--color-border)' }}; border-radius: 10px; margin-bottom: 8px; cursor: pointer;">
                    <input type="radio" wire:model.live="paymentTiming" value="{{ $value }}" style="margin-top: 3px;">
                    <span>
                        <strong>{{ $timing['label'] }}</strong>
                        <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">{{ $timing['hint'] }}</span>
                    </span>
                </label>
            @endforeach
            @error('paymentTiming') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Rapport du soir sur WhatsApp</h3>
            <label style="display: flex; gap: 12px; align-items: flex-start; cursor: pointer;">
                <input type="checkbox" wire:model.live="whatsappReportEnabled" style="margin-top: 3px;">
                <span>
                    <strong>Recevoir le rapport automatiquement à chaque clôture de caisse</strong>
                    <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">
                        Ventes, espèces, Mobile Money, crédit, dépenses, écart de caisse et points à surveiller.
                    </span>
                </span>
            </label>
            @if ($whatsappReportEnabled)
                <label style="font-size: 13px; font-weight: 600; display: block; margin-top: 12px;">Numéro qui reçoit le rapport</label>
                <input type="tel" wire:model="reportPhone" placeholder="{{ $ownerPhone ? 'Vide = votre numéro (' . $ownerPhone . ')' : '07 XX XX XX XX' }}" style="width: 100%; margin-top: 4px;">
                @error('reportPhone') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
            @endif
            @unless ($whatsappConfigured)
                <p style="font-size: 12px; color: var(--color-text-secondary); margin: 10px 0 0 0;">
                    L'envoi automatique n'est pas encore en service sur Maquis Express. En attendant, la caissière envoie le rapport d'un clic à la clôture.
                </p>
            @endunless
        </div>

        <div class="card" style="margin-bottom: 16px;">
            <h3 style="margin-top: 0;">Crédit client</h3>
            <label style="display: flex; gap: 12px; align-items: flex-start; cursor: pointer;">
                <input type="checkbox" wire:model="creditEnabled" style="margin-top: 3px;">
                <span>
                    <strong>Autoriser les clients à payer plus tard</strong>
                    <span style="display: block; font-size: 13px; color: var(--color-text-secondary);">
                        Les sommes dues sont notées sur le compte du client, avec qui a autorisé le crédit.
                    </span>
                </span>
            </label>
            @error('creditEnabled') <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>

    {{-- ===== Mon abonnement ===== --}}
    @php
        [$badge, $color] = $restaurant->subscriptionBadge();
        $end = $restaurant->accessEndsAt();
        $days = $restaurant->daysLeft();
        $methodLabels = \App\Models\SubscriptionPayment::METHODS;
    @endphp
    <div id="abonnement" class="card" style="max-width: 560px; margin-top: 28px; border-left: 6px solid var(--color-{{ $color }});">
        <h3 style="margin-top: 0;">Mon abonnement Maquis Express</h3>

        <div style="display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; font-size: 14px;">
            <div>
                <div style="color: var(--color-text-secondary); font-size: 12px;">Formule</div>
                <strong>{{ $restaurant->plan?->name ?? 'Non choisie' }}</strong>
                @if ($restaurant->plan)
                    <span style="color: var(--color-text-secondary);"> · {{ number_format($restaurant->plan->monthly_price, 0, ',', ' ') }} FCFA / mois</span>
                @endif
            </div>
            <div>
                <div style="color: var(--color-text-secondary); font-size: 12px;">Situation</div>
                <strong style="color: var(--color-{{ $color }});">{{ $badge }}</strong>
            </div>
        </div>

        @if ($end)
            <p style="font-size: 14px; margin: 12px 0 0 0;">
                @if ($restaurant->subscription_status === 'trial')
                    Essai gratuit jusqu'au <strong>{{ $end->format('d/m/Y') }}</strong>
                @else
                    Accès payé jusqu'au <strong>{{ $end->format('d/m/Y') }}</strong>
                @endif
                @if ($days !== null && $days >= 0)
                    ({{ $days === 0 ? "aujourd'hui" : 'encore ' . $days . ' jour' . ($days > 1 ? 's' : '') }}).
                @elseif ($restaurant->isInGracePeriod())
                    : <strong style="color: var(--color-danger);">l'échéance est passée.</strong> L'accès sera coupé le {{ $restaurant->lockAt()->format('d/m/Y') }} à {{ \App\Models\Restaurant::LOCK_HOUR }} h.
                @endif
            </p>
        @endif

        @if ($restaurant->plan?->max_users)
            <p style="font-size: 13px; color: var(--color-text-secondary); margin: 6px 0 0 0;">
                {{ $restaurant->activeUsersCount() }} / {{ $restaurant->plan->max_users }} utilisateurs actifs.
            </p>
        @endif

        <div style="background: var(--color-bg); border-radius: 10px; padding: 12px 14px; margin-top: 14px; font-size: 14px;">
            <strong>Comment payer</strong>
            @if ($billing !== [])
                <div style="margin-top: 6px;">Envoyez le montant de votre formule à :</div>
                @foreach (['wave', 'orange_money', 'mtn_momo', 'moov_money'] as $method)
                    @if (! empty($billing[$method]))
                        <div>• {{ $methodLabels[$method] }} : <strong>{{ $billing[$method] }}</strong></div>
                    @endif
                @endforeach
            @else
                <div style="margin-top: 6px;">Contactez Maquis Express pour régler votre abonnement.</div>
            @endif
            <div style="margin-top: 6px;">Indiquez la référence <strong>{{ $restaurant->paymentReference() }}</strong>. Votre accès est prolongé dès réception.</div>
            @if (! empty($billing['whatsapp']))
                <a href="https://wa.me/225{{ \App\Models\User::normalizePhone($billing['whatsapp']) }}?text={{ rawurlencode('Bonjour, je viens de payer l\'abonnement de ' . $restaurant->name . ' (réf. ' . $restaurant->paymentReference() . ').') }}"
                   target="_blank" rel="noopener" class="btn btn-ghost" style="margin-top: 10px; text-decoration: none;">
                    Prévenir Maquis Express sur WhatsApp
                </a>
            @endif
        </div>

        @if ($plans->isNotEmpty())
            <details style="margin-top: 14px;">
                <summary style="cursor: pointer; font-size: 14px; font-weight: 600;">Voir les formules</summary>
                @foreach ($plans as $plan)
                    <div style="display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                        <span><strong>{{ $plan->name }}</strong>
                            <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ $plan->usersLabel() }}{{ $plan->description ? ' · ' . $plan->description : '' }}</span>
                        </span>
                        <strong style="white-space: nowrap;">{{ number_format($plan->monthly_price, 0, ',', ' ') }} F/mois</strong>
                    </div>
                @endforeach
            </details>
        @endif

        @if ($subscriptionPayments->isNotEmpty())
            <h3 style="margin: 16px 0 6px 0; font-size: 14px;">Mes paiements</h3>
            @foreach ($subscriptionPayments as $p)
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-top: 1px solid var(--color-border); font-size: 13px;">
                    <span>{{ $p->created_at->format('d/m/Y') }} · {{ $p->plan?->name }} · {{ $p->months }} mois <span style="color: var(--color-text-secondary);">({{ $methodLabels[$p->method] ?? $p->method }})</span></span>
                    <strong>{{ number_format($p->amount, 0, ',', ' ') }} F</strong>
                </div>
            @endforeach
        @endif
    </div>
</div>
