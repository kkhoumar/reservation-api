<div>
    <div style="margin-bottom: 24px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Super Admin
        </div>
        <h1>Établissements</h1>
    </div>

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
                            @endphp
                            <span style="background: {{ $s['bg'] }}; color: {{ $s['text'] }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                {{ $s['label'] }}
                            </span>
                        </td>
                        <td style="padding: 14px 20px; text-align: right;">
                            <button wire:click="toggleStatus({{ $restaurant->id }})"
                                    wire:confirm="Confirmer le changement de statut de {{ $restaurant->name }} ?"
                                    class="btn {{ $restaurant->subscription_status === 'suspended' ? 'btn-primary' : 'btn-danger' }}"
                                    style="padding: 7px 14px; font-size: 13px;">
                                {{ $restaurant->subscription_status === 'suspended' ? 'Réactiver' : 'Suspendre' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>