<div class="tm">
    <x-flash />

    <a href="{{ route('tables.index') }}" class="tm-back">← Plan des tables</a>
    <div class="tm-eyebrow">Réglages</div>
    <h1>Mes tables</h1>
    <p class="tm-lead">Créez les tables de votre maquis. Les serveuses les verront sur le plan pour prendre les commandes.</p>

    @if (! $usesTables)
        <div class="tm-warn">Votre maquis est réglé sur <strong>Vente rapide</strong> : les tables ne s'affichent pas. Pour les utiliser, choisissez « Tables et vente rapide » dans <a href="{{ route('settings') }}">Paramètres</a>.</div>
    @endif

    {{-- 1. Créer plusieurs tables d'un coup --}}
    <div class="card tm-create">
        <h3>{{ $count === 0 ? 'Créer mes tables' : 'Ajouter des tables' }}</h3>
        <div class="tm-row">
            <label for="tm-how">Combien ?</label>
            <div class="tm-steps">
                <button type="button" wire:click="$set('howMany', {{ max(1, $howMany - 1) }})" aria-label="Une de moins"><x-icon name="minus" size="18" /></button>
                <input id="tm-how" type="number" inputmode="numeric" min="1" max="50" wire:model.live="howMany">
                <button type="button" wire:click="$set('howMany', {{ min(50, $howMany + 1) }})" aria-label="Une de plus"><x-icon name="plus" size="18" /></button>
            </div>
        </div>
        @error('howMany') <div class="tm-err">{{ $message }}</div> @enderror

        @if ($zones->count() > 1)
            <div class="tm-row tm-zones-pick">
                <span class="tm-label">Dans quelle zone ?</span>
                <div class="tm-chips">
                    @foreach ($zones as $zone)
                        <button type="button" wire:click="chooseZone({{ $zone->id }})" class="{{ $zoneId === $zone->id ? 'on' : '' }}">{{ $zone->name }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        <button type="button" wire:click="createTables" wire:loading.attr="disabled" class="btn btn-primary tm-go">
            Créer {{ $howMany }} table{{ $howMany > 1 ? 's' : '' }}
        </button>
        <p class="tm-hint">Elles s'appelleront Table 1, Table 2… Vous pourrez les renommer (« Terrasse 1 », « VIP »…).</p>
    </div>

    {{-- 2. Les zones et leurs tables --}}
    @foreach ($zones as $zone)
        <div class="card tm-zone" wire:key="zone-{{ $zone->id }}">
            <div class="tm-zone-head">
                <h3>{{ $zone->name }} <span>{{ $zone->tables->count() }} table{{ $zone->tables->count() > 1 ? 's' : '' }}</span></h3>
                @if ($zone->tables->isEmpty())
                    <button type="button" wire:click="deleteZone({{ $zone->id }})" wire:confirm="Supprimer la zone « {{ $zone->name }} » ?" class="tm-link danger">Supprimer la zone</button>
                @endif
            </div>
            @if ($zone->tables->isEmpty())
                <p class="tm-hint">Aucune table. Choisissez cette zone en haut, puis « Créer ».</p>
            @endif
            <div class="tm-tables">
                @foreach ($zone->tables as $table)
                    <div class="tm-table" wire:key="t-{{ $table->id }}">
                        @if ($editingId === $table->id)
                            <form wire:submit="saveRename" class="tm-rename">
                                <input type="text" wire:model="editingLabel" maxlength="40" aria-label="Nouveau nom de la table" autofocus>
                                <button type="submit" class="btn btn-primary">OK</button>
                                <button type="button" wire:click="$set('editingId', null)" class="btn btn-ghost">Annuler</button>
                            </form>
                            @error('editingLabel') <div class="tm-err">{{ $message }}</div> @enderror
                        @else
                            <strong>{{ $table->label }}</strong>
                            <span class="tm-acts">
                                <button type="button" wire:click="startRename({{ $table->id }})" aria-label="Renommer {{ $table->label }}"><x-icon name="pencil" size="16" /></button>
                                <button type="button" wire:click="deleteTable({{ $table->id }})" wire:confirm="Supprimer « {{ $table->label }} » ?" aria-label="Supprimer {{ $table->label }}" class="danger"><x-icon name="trash" size="16" /></button>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    {{-- 3. Ajouter une zone --}}
    <div class="card tm-newzone">
        <h3>Ajouter une zone</h3>
        <p class="tm-hint">Pour séparer la terrasse, la salle, le coin VIP…</p>
        <form wire:submit="addZone" class="tm-rename">
            <input type="text" wire:model="newZone" maxlength="60" placeholder="Ex : Terrasse" aria-label="Nom de la zone">
            <button type="submit" class="btn btn-ghost">Ajouter</button>
        </form>
        @error('newZone') <div class="tm-err">{{ $message }}</div> @enderror
    </div>

    @if ($count > 0)
        <a href="{{ route('tables.index') }}" class="btn btn-primary tm-done">Voir le plan des tables</a>
    @endif

    <style>
        .tm { max-width: 720px; }
        .tm-back { font-size: 13px; color: var(--color-text-secondary); text-decoration: none; }
        .tm-eyebrow { font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: .04em; margin: 10px 0 4px; }
        .tm-lead { font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 18px; }
        .tm-warn { background: var(--color-warning-bg); color: #6B4A12; border-radius: 12px; padding: 12px 14px; font-size: 14px; margin-bottom: 16px; }
        .tm-warn a { color: inherit; font-weight: 700; }
        .tm .card { margin-bottom: 14px; }
        .tm h3 { margin: 0 0 12px; font-size: 17px; }
        .tm-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .tm-row label, .tm-label { font-weight: 700; font-size: 14px; }
        .tm-steps { display: flex; align-items: center; gap: 6px; }
        .tm-steps button { width: 46px; height: 46px; border-radius: 12px; border: 1px solid var(--color-border); background: #fff; cursor: pointer; display: grid; place-items: center; color: var(--color-text); }
        .tm-steps input { width: 74px; height: 46px; text-align: center; font-size: 20px; font-weight: 800; border-radius: 12px; }
        .tm-zones-pick { align-items: flex-start; flex-direction: column; }
        .tm-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .tm-chips button { border: 1.5px solid var(--color-border); background: #fff; border-radius: 999px; padding: 8px 14px; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
        .tm-chips button.on { border-color: var(--color-primary); background: var(--color-mint); color: var(--color-primary); }
        .tm-go { width: 100%; justify-content: center; min-height: 50px; font-size: 16px; }
        .tm-hint { font-size: 12.5px; color: var(--color-text-secondary); margin: 8px 0 0; line-height: 1.45; }
        .tm-err { color: var(--color-danger); font-size: 13px; font-weight: 600; margin: -6px 0 10px; }
        .tm-zone-head { display: flex; justify-content: space-between; align-items: baseline; gap: 10px; }
        .tm-zone-head h3 span { font-size: 13px; font-weight: 500; color: var(--color-text-secondary); margin-left: 6px; }
        .tm-tables { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; }
        .tm-table { display: flex; align-items: center; justify-content: space-between; gap: 6px; border: 1px solid var(--color-border); border-radius: 12px; padding: 8px 8px 8px 12px; min-height: 48px; flex-wrap: wrap; }
        .tm-table strong { font-size: 15px; }
        .tm-acts { display: flex; gap: 2px; }
        .tm-acts button { width: 36px; height: 36px; border: 0; background: none; border-radius: 9px; cursor: pointer; color: var(--color-text-secondary); display: grid; place-items: center; }
        .tm-acts button:hover { background: var(--color-bg); }
        .tm-acts .danger { color: var(--color-danger); }
        .tm-rename { display: flex; gap: 6px; width: 100%; flex-wrap: wrap; }
        .tm-rename input { flex: 1 1 140px; min-width: 0; }
        .tm-link { background: none; border: 0; padding: 0; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--color-text-secondary); text-decoration: underline; }
        .tm-link.danger { color: var(--color-danger); }
        .tm-done { width: 100%; justify-content: center; min-height: 48px; text-decoration: none; }
        @media (max-width: 640px) { .tm-tables { grid-template-columns: 1fr 1fr; } }
    </style>
</div>
