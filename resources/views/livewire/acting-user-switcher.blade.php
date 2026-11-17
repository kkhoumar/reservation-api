<div class="card" style="margin-bottom: 20px;">
    <strong>Se connecter en tant que (temporaire, avant le vrai login) :</strong>
    <div style="display: flex; gap: 10px; margin-top: 10px;">
        <select wire:model="userId" style="flex: 1;">
            <option value="">-- Choisir un utilisateur --</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}">
                    {{ $user->first_name }} {{ $user->last_name }} ({{ $user->role }})
                </option>
            @endforeach
        </select>
        <button wire:click="switchUser" class="btn btn-secondary">Se connecter</button>
    </div>
</div>
