<div>
    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Super Admin
            </div>
            <h1>Établissements</h1>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('admin.subscribers') }}" class="btn btn-secondary" style="text-decoration: none;">Abonnés et paiements</a>
            <a href="{{ route('admin.plans') }}" class="btn btn-ghost" style="text-decoration: none;">Formules et prix</a>
            <a href="{{ route('admin.settings') }}" class="btn btn-ghost" style="text-decoration: none;">Réglages</a>
            <a href="{{ route('admin.restaurants.create') }}" class="btn btn-primary" style="text-decoration: none;">+ Nouveau maquis</a>
        </div>
    </div>

    <x-flash />

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
                                @if ($match = $demoMatches[$demo->id] ?? null)
                                    <div class="demo-tag demo-tag-{{ $match['kind'] }}">
                                        {{ $match['label'] }}
                                        <span>
                                            @switch($match['kind'])
                                                @case('client') Il a sûrement oublié son mot de passe : donnez-lui-en un nouveau. @break
                                                @case('former') Réactivez son maquis ou prolongez son essai plutôt que d'en créer un autre. @break
                                                @case('staff') Pour son propre maquis, il faudra un autre numéro pour son compte patron. @break
                                                @default Ce numéro est déjà pris.
                                            @endswitch
                                        </span>
                                    </div>
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
                                @if (in_array($match['kind'] ?? null, ['client', 'former'], true))
                                    <a href="{{ route('admin.restaurants.subscription', $match['restaurant']->id) }}{{ $match['kind'] === 'client' ? '#comptes' : '' }}" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">{{ $match['kind'] === 'client' ? 'Nouveau mot de passe' : 'Gérer son maquis' }}</a>
                                @else
                                    <a href="{{ route('admin.restaurants.create', ['demande' => $demo->id]) }}" class="btn btn-primary" style="padding: 7px 14px; font-size: 13px; text-decoration: none;">Créer le maquis</a>
                                @endif
                                @if ($demo->status === 'new')
                                    <button wire:click="setDemoStatus({{ $demo->id }}, 'contacted')" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">Contacté</button>
                                @endif
                                <button wire:click="setDemoStatus({{ $demo->id }}, 'rejected')" wire:confirm="Classer cette demande sans suite ? Elle sortira de la liste des nouvelles demandes." data-confirm-ok="Classer sans suite" class="btn btn-ghost" style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">Sans suite</button>
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

    <div class="adm-search">
        <x-icon name="search" size="18" />
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Maquis, patron ou téléphone…" aria-label="Rechercher un maquis">
        @if ($search !== '')
            <span class="adm-count">{{ $restaurants->count() }} / {{ $totalCount }}</span>
        @endif
    </div>
    <style>
        .adm-search { position: relative; display: flex; align-items: center; margin-bottom: 12px; }
        .adm-search svg { position: absolute; left: 14px; color: var(--color-text-secondary); pointer-events: none; }
        .adm-search input { width: 100%; padding: 12px 70px 12px 42px; border-radius: 12px; border: 1px solid var(--color-border); background: #fff; }
        .adm-count { position: absolute; right: 14px; font-size: 12px; color: var(--color-text-secondary); }
        .adm-owner { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 8px; margin-top: 6px; font-size: 13px; }
        .adm-owner a { font-size: 12px; font-weight: 600; text-decoration: none; }
        .adm-owner .none { color: var(--color-danger); font-size: 12px; }
        .demo-tag { display: inline-block; margin-top: 8px; padding: 6px 10px; border-radius: 10px; font-size: 12px; font-weight: 700; line-height: 1.4; }
        .demo-tag span { display: block; font-weight: 500; }
        .demo-tag-client { background: #E6F4EC; color: #146C43; }
        .demo-tag-former { background: #FFF1DD; color: #8A4B00; }
        .demo-tag-staff, .demo-tag-admin { background: #E7F0FB; color: #1F4E8C; }
    </style>

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
                            @php $owner = $restaurant->users->first(); $ownerPhone = $owner ? \App\Models\User::normalizePhone($owner->phone) : null; @endphp
                            <div class="adm-owner">
                                @if ($owner)
                                    <span>👤 <strong>{{ trim($owner->first_name . ' ' . $owner->last_name) }}</strong> · {{ trim(chunk_split($ownerPhone, 2, ' ')) }}</span>
                                    <a href="tel:{{ $ownerPhone }}">Appeler</a>
                                    <a href="https://wa.me/225{{ $ownerPhone }}" target="_blank" rel="noopener">WhatsApp</a>
                                @else
                                    <span class="none">Pas de patron actif</span>
                                @endif
                            </div>
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
                                        ? 'Réactiver ' . $restaurant->name . ' ? Son personnel pourra de nouveau utiliser l\'application.'
                                        : 'Verrouiller ' . $restaurant->name . ' ? Son personnel n\'aura plus accès à l\'application. Ses données sont gardées.' }}"
                                    class="btn {{ $restaurant->isLocked() ? 'btn-primary' : 'btn-danger' }}"
                                    style="padding: 7px 14px; font-size: 13px; margin-left: 6px;">
                                {{ $restaurant->isLocked() ? 'Réactiver' : 'Verrouiller' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
                @if ($restaurants->isEmpty())
                    <tr><td colspan="5" style="padding: 24px 20px; text-align: center; color: var(--color-text-secondary);">Aucun maquis ne correspond à « {{ $search }} ».</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>