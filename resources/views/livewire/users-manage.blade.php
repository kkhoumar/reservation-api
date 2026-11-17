<div>
    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="card" style="border-left: 6px solid var(--color-danger); margin-bottom: 16px;">
            {{ session('error') }}
        </div>
    @endif

    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Équipe
            </div>
            <h1>Utilisateurs</h1>
        </div>
        <button wire:click="openCreateForm" class="btn btn-primary">+ Ajouter un utilisateur</button>
    </div>

    @if ($showForm)
        <div class="card" style="max-width: 460px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">{{ $editingId ? 'Modifier' : 'Nouvel' }} utilisateur</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Prénom</label>
                    <input type="text" wire:model="firstName" style="width: 100%; margin-top: 6px;">
                    @error('firstName') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label style="font-size: 13px; font-weight: 600;">Nom</label>
                    <input type="text" wire:model="lastName" style="width: 100%; margin-top: 6px;">
                    @error('lastName') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">Téléphone</label>
                <input type="text" wire:model="phone" placeholder="+225 07 XX XX XX XX" style="width: 100%; margin-top: 6px;">
                @error('phone') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 600;">
                    Mot de passe {{ $editingId ? '(laisser vide pour ne pas changer)' : '' }}
                </label>
                <input type="password" wire:model="password" style="width: 100%; margin-top: 6px;">
                @error('password') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
            </div>

            @if ($editingId !== auth()->id())
                @php $formRoles = ['server' => 'Serveur', 'cashier' => 'Caissier', 'manager' => 'Gérant']; @endphp
                <div style="margin-bottom: 20px;">
                    <label style="font-size: 13px; font-weight: 600;">Rôle</label>
                    <select wire:model="role" style="width: 100%; margin-top: 6px;">
                        @foreach ($formRoles as $value => $label)
                            @if (in_array($value, $assignableRoles, true))
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('role') <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror
                </div>
            @endif

            <div style="display: flex; gap: 10px;">
                <button wire:click="save" class="btn btn-primary" style="flex: 1;">Enregistrer</button>
                <button wire:click="$set('showForm', false)" class="btn btn-ghost" style="flex: 1;">Annuler</button>
            </div>
        </div>
    @endif

    <div class="card" style="padding: 0; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--color-border); text-align: left;">
                    <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Nom</th>
                    <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Téléphone</th>
                    <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Rôle</th>
                    <th style="padding: 12px 20px; font-size: 12px; color: var(--color-text-secondary); font-weight: 600;">Statut</th>
                    <th style="padding: 12px 20px;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr style="border-bottom: 1px solid var(--color-border);">
                        <td style="padding: 12px 20px; font-weight: 600;">{{ $user->first_name }} {{ $user->last_name }}</td>
                        <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $user->phone }}</td>
                        <td style="padding: 12px 20px;">
                            @php
                                $roleLabels = ['owner' => 'Propriétaire', 'manager' => 'Gérant', 'server' => 'Serveur', 'cashier' => 'Caissier'];
                            @endphp
                            {{ $roleLabels[$user->role] ?? $user->role }}
                        </td>
                        <td style="padding: 12px 20px;">
                            <span style="background: {{ $user->status === 'active' ? 'rgba(94,191,160,0.15)' : 'rgba(217,59,59,0.1)' }}; color: {{ $user->status === 'active' ? '#146652' : '#D93B3B' }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                {{ $user->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td style="padding: 12px 20px; text-align: right;">
                            @can('update', $user)
                                <button wire:click="editUser({{ $user->id }})" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">Modifier</button>
                            @endcan
                            @can('toggleStatus', $user)
                                <button wire:click="toggleStatus({{ $user->id }})"
                                        wire:confirm="Confirmer ?"
                                        class="btn {{ $user->status === 'active' ? 'btn-danger' : 'btn-primary' }}"
                                        style="padding: 6px 12px; font-size: 12px;">
                                    {{ $user->status === 'active' ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>