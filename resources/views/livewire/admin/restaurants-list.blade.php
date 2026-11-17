<div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Super Admin
            </div>
            <h1>Établissements</h1>
        </div>
        <a href="{{ route('admin.restaurants.create') }}" class="btn btn-primary" style="text-decoration: none;">+ Nouveau maquis</a>
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

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Établissement</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Ville</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Utilisateurs</th>
                    <th style="padding: 14px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Statut</th>
                    <th style="padding: 14px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($restaurants as $restaurant)
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 14px 20px; font-weight: 600;">{{ $restaurant->name }}</td>
                        <td style="padding: 14px 20px; color: var(--color-text-secondary);">{{ $restaurant->city ?? '—' }}</td>
                        <td style="padding: 14px 20px;">{{ $restaurant->users_count }}</td>
                        <td style="padding: 14px 20px;">
                            @php
                                $statusColors = [
                                    'trial' => ['bg' => 'rgba(255,166,43,0.15)', 'text' => '#B36200', 'label' => 'Essai'],
                                    'active' => ['bg' => 'rgba(101,185,110,0.15)', 'text' => '#0B5D3B', 'label' => 'Actif'],
                                    'suspended' => ['bg' => 'rgba(232,74,58,0.12)', 'text' => '#E84A3A', 'label' => 'Suspendu'],
                                ];
                                $s = $statusColors[$restaurant->subscription_status] ?? $statusColors['trial'];
                                if ($restaurant->isTrialExpired()) {
                                    $s = $statusColors['suspended'];
                                    $s['label'] = 'Essai terminé';
                                }
                            @endphp
                            <span style="background: {{ $s['bg'] }}; color: {{ $s['text'] }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                {{ $s['label'] }}
                            </span>
                            @if ($restaurant->subscription_status === 'trial' && $restaurant->trial_ends_at)
                                <div style="font-size: 11px; color: var(--color-text-secondary); margin-top: 4px;">
                                    jusqu'au {{ $restaurant->trial_ends_at->format('d/m/Y') }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 20px; text-align: right; white-space: nowrap;">
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