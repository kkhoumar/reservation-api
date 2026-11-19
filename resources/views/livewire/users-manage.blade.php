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

    <div class="page-head" style="display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                Équipe
            </div>
            <h1>Utilisateurs</h1>
            @if ($restaurant?->userLimit())
                <div style="font-size: 13px; color: var(--color-text-secondary); margin-top: 4px;">
                    Formule {{ $restaurant->plan->name }} : {{ $restaurant->activeUsersCount() }} / {{ $restaurant->userLimit() }} utilisateurs actifs
                </div>
            @endif
        </div>
        <button wire:click="openCreateForm" class="btn btn-primary">+ Ajouter un utilisateur</button>
    </div>

    @if ($showForm)
        <div class="card" style="max-width: 460px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;">{{ $editingId ? 'Modifier' : 'Nouvel' }} utilisateur</h3>

            @if ($editingId && ($member = $users->firstWhere('id', $editingId)))
                <div x-data="avatarPicker('saveMemberPhoto', {{ $member->id }})" style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px; padding: 12px; background: var(--color-bg); border-radius: 14px;">
                    <x-avatar :user="$member" size="56" />
                    <div style="min-width: 0;">
                        <div style="font-weight: 700; font-size: 14px;">Photo de profil</div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px;">
                            <button type="button" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px;" @click="pick()" :disabled="busy" x-text="busy ? 'Envoi…' : '{{ $member->avatar_path ? 'Changer' : 'Ajouter une photo' }}'"></button>
                            @if ($member->avatar_path)
                                <button type="button" class="btn btn-ghost" style="padding: 6px 12px; font-size: 13px; color: var(--color-danger);" wire:click="removeMemberPhoto({{ $member->id }})">Retirer</button>
                            @endif
                        </div>
                        <div x-show="error" x-text="error" x-cloak style="color: var(--color-danger); font-size: 12.5px; margin-top: 6px;"></div>
                    </div>
                    <input type="file" accept="image/*" x-ref="file" @change="chosen($event)" hidden>
                </div>
            @endif

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
                        <td style="padding: 12px 20px; font-weight: 600;"><span style="display: inline-flex; align-items: center; gap: 10px;"><x-avatar :user="$user" size="34" /> {{ $user->first_name }} {{ $user->last_name }}</span></td>
                        <td style="padding: 12px 20px; color: var(--color-text-secondary);">{{ $user->phone }}</td>
                        <td style="padding: 12px 20px;">
                            @php
                                $roleLabels = ['owner' => 'Propriétaire', 'manager' => 'Gérant', 'server' => 'Serveur', 'cashier' => 'Caissier'];
                            @endphp
                            {{ $roleLabels[$user->role] ?? $user->role }}
                        </td>
                        <td style="padding: 12px 20px;">
                            <span style="background: {{ $user->status === 'active' ? 'var(--color-success-bg)' : 'var(--color-danger-bg)' }}; color: {{ $user->status === 'active' ? 'var(--color-success)' : 'var(--color-danger)' }}; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;">
                                {{ $user->status === 'active' ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td style="padding: 12px 20px; text-align: right;">
                            @can('update', $user)
                                <button wire:click="editUser({{ $user->id }})" class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">Modifier</button>
                            @endcan
                            @can('toggleStatus', $user)
                                <button wire:click="toggleStatus({{ $user->id }})"
                                        wire:confirm="{{ $user->status === 'active' ? 'Désactiver ' . $user->first_name . ' pour un temps (congé, absence) ? Il ne pourra plus se connecter.' : 'Réactiver ' . $user->first_name . ' ?' }}"
                                        title="Pour une absence temporaire : congé, maladie…"
                                        class="btn btn-ghost"
                                        style="padding: 6px 12px; font-size: 12px; margin-right: 6px;">
                                    {{ $user->status === 'active' ? 'Désactiver' : 'Réactiver' }}
                                </button>
                            @endcan
                            @can('remove', $user)
                                <button wire:click="removeUser({{ $user->id }})"
                                        wire:confirm="{{ $user->first_name }} quitte le maquis ? Il ne pourra plus se connecter et sortira de la liste. Ses ventes et son historique sont conservés. Vous pourrez le réintégrer s'il revient."
                                        class="btn btn-danger"
                                        style="padding: 6px 12px; font-size: 12px;">
                                    Retirer du maquis
                                </button>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p style="font-size: 12px; color: var(--color-text-secondary); margin-top: 10px;">
        <strong>Désactiver</strong> : absence temporaire (congé, maladie). <strong>Retirer du maquis</strong> : la personne est partie.
        Dans les deux cas, ses ventes et son historique sont conservés.
    </p>

    @if ($formerUsers->isNotEmpty())
        <details style="margin-top: 20px;">
            <summary style="cursor: pointer; font-weight: 700; font-size: 15px;">Anciens employés ({{ $formerUsers->count() }})</summary>
            <div class="card" style="padding: 0; overflow: hidden; margin-top: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        @foreach ($formerUsers as $former)
                            @php $roleLabels = ['owner' => 'Propriétaire', 'manager' => 'Gérant', 'server' => 'Serveur', 'cashier' => 'Caissier']; @endphp
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 12px 20px;">
                                    <strong>{{ $former->first_name }} {{ $former->last_name }}</strong>
                                    <span style="display: block; font-size: 12px; color: var(--color-text-secondary);">{{ $roleLabels[$former->role] ?? $former->role }} · {{ $former->phone }}</span>
                                </td>
                                <td style="padding: 12px 20px; color: var(--color-text-secondary); font-size: 13px;">
                                    Parti(e) le {{ $former->left_at->format('d/m/Y') }}
                                </td>
                                <td style="padding: 12px 20px; text-align: right;">
                                    @can('remove', $former)
                                        <button wire:click="restoreUser({{ $former->id }})"
                                                wire:confirm="Réintégrer {{ $former->first_name }} dans l'équipe ?"
                                                class="btn btn-ghost" style="padding: 6px 12px; font-size: 12px;">
                                            Réintégrer
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</div>