<div>
    <div style="margin-bottom: 20px;">
        <a href="{{ route('admin.restaurants') }}" style="font-size: 13px; color: var(--color-text-secondary); text-decoration: none;">← Tous les maquis</a>
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin: 8px 0 4px 0;">
            Super Admin
        </div>
        <h1>Formules d'abonnement</h1>
        <p style="font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 0 0;">
            Un nouveau prix s'applique aux prochains paiements. Les paiements déjà enregistrés ne changent pas.
        </p>
    </div>

    @if (session('success'))
        <div class="card" style="border-left: 6px solid var(--color-primary-300); margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
        @foreach ($plans as $plan)
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: baseline;">
                    <input type="text" wire:model="form.{{ $plan->id }}.name" style="font-size: 18px; font-weight: 800; width: 60%;">
                    <span style="font-size: 12px; color: var(--color-text-secondary);">{{ $plan->restaurants_count }} maquis</span>
                </div>

                <label style="font-size: 13px; font-weight: 600; display: block; margin-top: 12px;">Prix par mois (FCFA)</label>
                <input type="number" wire:model="form.{{ $plan->id }}.monthly_price" style="width: 100%; margin-top: 4px; font-size: 18px;">
                @error("form.{$plan->id}.monthly_price") <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror

                <label style="font-size: 13px; font-weight: 600; display: block; margin-top: 12px;">Utilisateurs actifs maximum</label>
                <input type="number" wire:model="form.{{ $plan->id }}.max_users" placeholder="Vide = illimité" style="width: 100%; margin-top: 4px;">
                @error("form.{$plan->id}.max_users") <div style="color: var(--color-danger); font-size: 12px;">{{ $message }}</div> @enderror

                <label style="font-size: 13px; font-weight: 600; display: block; margin-top: 12px;">Description</label>
                <input type="text" wire:model="form.{{ $plan->id }}.description" maxlength="255" style="width: 100%; margin-top: 4px;">

                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; margin-top: 12px; cursor: pointer;">
                    <input type="checkbox" wire:model="form.{{ $plan->id }}.includes_auto_report">
                    Rapport du soir automatique sur WhatsApp inclus
                </label>

                <label style="display: flex; gap: 8px; align-items: center; font-size: 13px; margin-top: 8px; cursor: pointer;">
                    <input type="checkbox" wire:model="form.{{ $plan->id }}.is_active">
                    Proposée aux nouveaux paiements
                </label>

                <button wire:click="save({{ $plan->id }})" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 14px;">Enregistrer</button>
            </div>
        @endforeach
    </div>
</div>
