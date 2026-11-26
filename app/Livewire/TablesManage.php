<?php

namespace App\Livewire;

use App\Models\RestaurantTable;
use App\Models\Zone;
use App\Support\Toast;
use Livewire\Component;

/**
 * « Mes tables » : le patron ou la gérante crée ses tables et ses zones
 * (terrasse, salle…). On peut en créer plusieurs d'un coup.
 */
class TablesManage extends Component
{
    /** Combien de tables créer d'un coup, et dans quelle zone. */
    public int $howMany = 6;
    public string $zoneName = '';
    public ?int $zoneId = null;

    /** Renommer une table. */
    public ?int $editingId = null;
    public string $editingLabel = '';

    public string $newZone = '';

    protected function restaurantId(): int
    {
        return (int) auth()->user()->currentRestaurantId();
    }

    /** Une zone par défaut (« Salle ») quand le maquis n'en a pas encore. */
    protected function targetZone(): Zone
    {
        if ($this->zoneId && ($zone = Zone::find($this->zoneId))) {
            return $zone;
        }

        return Zone::firstOrCreate(['restaurant_id' => $this->restaurantId(), 'name' => 'Salle']);
    }

    /** Prochain numéro libre : Table 1, Table 2… sans doublon. */
    protected function nextNumber(): int
    {
        $max = 0;
        foreach (RestaurantTable::pluck('label') as $label) {
            if (preg_match('/^Table\s+(\d+)$/i', trim($label), $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $max + 1;
    }

    public function createTables(): void
    {
        $this->validate(['howMany' => 'required|integer|min:1|max:50'], [
            'howMany.required' => 'Combien de tables ?',
            'howMany.min' => 'Au moins 1 table.',
            'howMany.max' => '50 tables au plus d\'un coup.',
        ]);

        $zone = $this->targetZone();
        $from = $this->nextNumber();
        for ($i = 0; $i < $this->howMany; $i++) {
            RestaurantTable::create([
                'restaurant_id' => $this->restaurantId(),
                'zone_id' => $zone->id,
                'label' => 'Table ' . ($from + $i),
                'status' => 'free',
            ]);
        }

        $to = $from + $this->howMany - 1;
        \App\Models\ActivityLog::record('tables_created', "a créé {$this->howMany} table" . ($this->howMany > 1 ? 's' : '') . " ({$zone->name}).");
        Toast::success($this->howMany > 1
            ? "Tables {$from} à {$to} créées dans « {$zone->name} »."
            : "Table {$from} créée dans « {$zone->name} ».");
    }

    public function addZone(): void
    {
        $name = trim($this->newZone);
        $this->validate(['newZone' => 'required|string|max:60'], ['newZone.required' => 'Donnez un nom à la zone, par exemple Terrasse.']);

        if (Zone::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            $this->addError('newZone', "La zone « {$name} » existe déjà.");

            return;
        }

        $zone = Zone::create(['restaurant_id' => $this->restaurantId(), 'name' => $name]);
        $this->zoneId = $zone->id;
        $this->newZone = '';
        Toast::success("Zone « {$name} » ajoutée. Les prochaines tables iront dedans.");
    }

    public function chooseZone($zoneId): void
    {
        $this->zoneId = Zone::find((int) $zoneId)?->id;
    }

    public function startRename($tableId): void
    {
        $table = RestaurantTable::findOrFail((int) $tableId);
        $this->editingId = $table->id;
        $this->editingLabel = $table->label;
        $this->resetValidation();
    }

    public function saveRename(): void
    {
        $table = RestaurantTable::findOrFail((int) $this->editingId);
        $label = trim($this->editingLabel);
        $this->validate(['editingLabel' => 'required|string|max:40'], ['editingLabel.required' => 'Donnez un nom à la table.']);

        if (RestaurantTable::where('id', '!=', $table->id)->whereRaw('LOWER(label) = ?', [mb_strtolower($label)])->exists()) {
            $this->addError('editingLabel', "Une autre table s'appelle déjà « {$label} ».");

            return;
        }

        $old = $table->label;
        $table->update(['label' => $label]);
        $this->editingId = null;
        Toast::success("« {$old} » s'appelle maintenant « {$label} ».");
    }

    public function deleteTable($tableId): void
    {
        $table = RestaurantTable::findOrFail((int) $tableId);

        if ($table->status !== 'free' || $table->orders()->whereNotIn('status', ['paid', 'cancelled'])->exists()) {
            Toast::error("« {$table->label} » a une commande en cours. Attendez qu'elle soit payée.");

            return;
        }

        if ($table->orders()->exists()) {
            Toast::error("« {$table->label} » a déjà des ventes dans l'historique : renommez-la plutôt que de la supprimer.");

            return;
        }

        $label = $table->label;
        $table->delete();
        Toast::success("« {$label} » est supprimée.");
    }

    public function deleteZone($zoneId): void
    {
        $zone = Zone::withCount('tables')->findOrFail((int) $zoneId);

        if ($zone->tables_count > 0) {
            Toast::error("La zone « {$zone->name} » contient encore des tables.");

            return;
        }

        $name = $zone->name;
        $zone->delete();
        if ($this->zoneId === (int) $zoneId) {
            $this->zoneId = null;
        }
        Toast::success("Zone « {$name} » supprimée.");
    }

    public function render()
    {
        $zones = Zone::with(['tables' => fn ($q) => $q->orderByRaw('LENGTH(label)')->orderBy('label')])->orderBy('id')->get();
        $loose = RestaurantTable::whereNull('zone_id')->orderBy('label')->get();

        if ($this->zoneId === null && $zones->isNotEmpty()) {
            $this->zoneId = $zones->first()->id;
        }

        return view('livewire.tables-manage', [
            'zones' => $zones,
            'loose' => $loose,
            'count' => RestaurantTable::count(),
            'usesTables' => (bool) auth()->user()->currentRestaurant()?->usesTables(),
        ]);
    }
}
