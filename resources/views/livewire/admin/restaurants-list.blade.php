<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Super Admin
            </div>
            <h1>Établissements</h1>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('admin.plans') }}" class="btn btn-ghost" style="text-decoration: none;">Formules et prix</a>
            <a href="{{ route('admin.restaurants.create') }}" class="btn btn-primary" style="text-decoration: none;">+ Nouveau maquis</a>
        </div>
    </div>

    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if ($demoRequests->isNotEmpty())
        <h3 style="margin: 0 0 10px 0;">Demandes de démo ({{ $demoRequests->count() }})</h3>
        <div class="card" style="padding: 0; overflow: hidden; margin-bottom: 28px; border-left: 6px solid var(--color-accent);">
            <table style="width: 100%; border-collapse: collapse;">
                <tbody>
                    @foreach ($demoRequests as $demo)
                        <tr style="border-bottom: 1px solid var(--color-border);">
                            <td style="padding: 12px 20px;">
                                <div style="font-weight: 600;">{{ $demo->restaurant_name }}</div>
                                <div style="font-size: 12px; color: var(--color-text-secondary);">
                                    {{ $demo->contact_name }}{{ $demo->commune ? ' · ' . $demo->commune : '' }} · {{ $demo->created_at->diffForHumans() }}
                                </div>
                                @if ($demo->message)
                                    <div style="font-size: 12px; margin-top: 4px;">« {{ $demo->message }} »</div>
                                @endif
                            </td>
                            <td style="padding: 12px 20px; white-space: nowrap;">
                                <a href="tel:{{ $demo->phone }}" style="font-weight: 600;">{{ $demo->phone }}</a>
                                <a href="https://wa.me/225{{ $demo->phone }}" target="_blank" rel="noopener" style="font-size: 12px; margin-left: 6px;">WhatsApp</a>
                            </td>
                            <td style="padding: 12px 20px;">
                                <span style="font-size: 12px; font-weight: 600; color: {{ $demo->status === 'new' ? 'var(--color-accent)' : 'var(--color-text-secondary)' }};">
                                    {{ \App\Models\DemoRequest::STATUSES[$demo->status] }}
                                </span>
                            </td>
                            <td style="padding: 12px 20px; text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.restaurants.create', ['demande' => $demo->id]) }}" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">Créer le maquis</a>
                                @if ($demo->status === 'new')
                                    <button wire:click="setDemoStatus({{ $demo->id }}, 'contacted')" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">Contacté</button>
                                @endif
                                <button wire:click="setDemoStatus({{ $demo->id }}, 'rejected')" wire:confirm="Classer cette demande sans suite ?" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">Sans suite</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($manualReminders->isNotEmpty())
        <div class="card" style="border-left: 6px solid var(--color-accent); margin-bottom: 16px;">
            <h3 style="margin: 0 0 6px 0;">Rappels du jour à envoyer à la main ({{ $manualReminders->count() }})</h3>
            <p style="font-size: 13px; color: var(--color-text-secondary); margin: 0 0 8px 0;">Ils ne sont pas partis automatiquement (API WhatsApp non configurée, ou refus). Un clic ouvre WhatsApp avec le message prêt.</p>
            @foreach ($manualReminders as $reminder)
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 6px 0; border-top: 1px solid var(--color-border); font-size: 14px;">
                    <span><strong>{{ $reminder->restaurant?->name }}</strong> · {{ $reminder->kind }} · échéance {{ $reminder->due_date->format('d/m/Y') }}
                        @if ($reminder->error) <span style="font-size: 12px; color: var(--color-text-secondary);">({{ $reminder->error }})</span> @endif
                    </span>
                    @if ($url = $reminder->restaurant?->reminderWhatsappUrl())
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-ghost" style="padding: 5px 12px; font-size: 13px; text-decoration: none;">Envoyer sur WhatsApp</a>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Chiffres de la plateforme --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Revenu mensuel</div>
            <div style="font-size: 22px; font-weight: 800; color: var(--color-primary);">{{ number_format($monthlyRevenue, 0, ',', ' ') }} FCFA</div>
            <div style="font-size: 12px; color: var(--color-text-secondary);">{{ $payingCount }} maquis payant{{ $payingCount > 1 ? 's' : '' }} à jour</div>
        </div>
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">Encaissé ce mois</div>
            <div style="font-size: 22px; font-weight: 800;">{{ number_format($collectedThisMonth, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="card" style="padding: 14px 16px;">
            <div style="font-size: 12px; color: var(--color-text-secondary);">En essai</div>
            <div style="font-size: 22px; font-weight: 800;">{{ $trialCount }}</div>
        </div>
        <div class="card" style="padding: 14px 16px; border-left: 4px solid {{ $toChaseCount > 0 ? 'var(--color-accent)' : 'var(--color-border)' }};">
            <div style="font-size: 12px; color: var(--color-text-secondary);">À relancer</div>
            <div style="font-size: 22px; font-weight: 800; color: {{ $toChaseCount > 0 ? 'var(--color-accent)' : 'inherit' }};">{{ $toChaseCount }}</div>
            <div style="font-size: 12px; color: var(--color-text-secondary);">expire bientôt, en retard ou impayé</div>
        </div>
    </div>

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Établissement</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Formule</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Utilisateurs</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Situation</th>
                    <th style="padding: 14px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($restaurants as $restaurant)
                    @php
                        [$badge, $color] = $restaurant->subscriptionBadge();
                        $end = $restaurant->accessEndsAt();
                    @endphp
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 14px 20px;">
                            <strong>{{ $restaurant->name }}</strong>
                            <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ collect([$restaurant->commune, $restaurant->city])->filter()->join(', ') ?: '—' }} · {{ $restaurant->paymentReference() }}</span>
                        </td>
                        <td style="padding: 14px 20px;">
                            {{ $restaurant->plan?->name ?? '—' }}
                            @if ($restaurant->plan)
                                <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ number_format($restaurant->plan->monthly_price, 0, ',', ' ') }} F/mois</span>
                            @endif
                        </td>
                        <td style="padding: 14px 20px;">
                            {{ $restaurant->active_users_count }}{{ $restaurant->plan?->max_users ? ' / ' . $restaurant->plan->max_users : '' }}
                        </td>
                        <td style="padding: 14px 20px;">
                            <span style="background: color-mix(in srgb, var(--color-{{ $color }}) 14%, transparent); color: var(--color-{{ $color }}); padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                {{ $badge }}
                            </span>
                            @if ($end)
                                <div style="font-size: 11px; color: var(--color-text-secondary); margin-top: 4px;">
                                    {{ $restaurant->subscription_status === 'trial' ? 'essai' : 'payé' }} jusqu'au {{ $end->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 20px; text-align: right; white-space: nowrap;">
                            @if ($restaurant->subscription_status !== 'suspended' && ($restaurant->needsRenewalReminder() || $restaurant->isLocked()) && ($remindUrl = $restaurant->reminderWhatsappUrl()))
                                <a href="{{ $remindUrl }}" target="_blank" rel="noopener" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; text-decoration: none; margin-right: 6px;" title="Message de rappel prêt à envoyer au patron">Relancer</a>
                            @endif
                            <a href="{{ route('admin.restaurants.subscription', $restaurant->id) }}" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none; margin-right: 6px;">Gérer</a>
                            <button wire:click="enterRestaurant({{ $restaurant->id }})"
                                    class="btn btn-secondary"
                                    style="padding: 7px 14px; font-size: 13px;">
                                Entrer
                            </button>
                            <button wire:click="toggleStatus({{ $restaurant->id }})"
                                    wire:confirm="{{ $restaurant->isLocked()
                                        ? 'Réactiver ' . $restaurant->name . ' ?'
                                        : 'Verrouiller ' . $restaurant->name . ' ? Son personnel n\'aura plus accès à l\'application.' }}"
                                    class="btn {{ $restaurant->isLocked() ? 'btn-primary' : 'btn-danger' }}"
                                    style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">
                                {{ $restaurant->isLocked() ? 'Réactiver' : 'Verrouiller' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>