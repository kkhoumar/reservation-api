<?php

namespace App\Livewire\Admin;

use App\Models\Plan;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Super Admin : prix et limites des formules. Un nouveau prix s'applique
 * aux prochains paiements ; les paiements passés ne changent pas.
 */
class PlansManage extends Component
{
    /** @var array<int, array{name: string, monthly_price: int|string|null, max_users: int|string|null, description: ?string, is_active: bool}> */
    public array $form = [];

    public function mount(): void
    {
        foreach (Plan::orderBy('sort_order')->get() as $plan) {
            $this->form[$plan->id] = [
                'name' => $plan->name,
                'monthly_price' => $plan->monthly_price,
                'max_users' => $plan->max_users,
                'description' => $plan->description,
                'is_active' => $plan->is_active,
            ];
        }
    }

    public function save(int $planId): void
    {
        $plan = Plan::findOrFail($planId);

        $this->validate([
            "form.$planId.name" => 'required|string|max:60',
            "form.$planId.monthly_price" => 'required|integer|min:0|max:10000000',
            "form.$planId.max_users" => 'nullable|integer|min:1|max:1000',
            "form.$planId.description" => 'nullable|string|max:255',
            "form.$planId.is_active" => 'boolean',
        ], [], [
            "form.$planId.monthly_price" => 'prix',
            "form.$planId.max_users" => 'nombre d\'utilisateurs',
        ]);

        $data = $this->form[$planId];
        $plan->update([
            'name' => $data['name'],
            'monthly_price' => (int) $data['monthly_price'],
            'max_users' => $data['max_users'] === '' || $data['max_users'] === null ? null : (int) $data['max_users'],
            'description' => $data['description'] ?: null,
            'is_active' => (bool) $data['is_active'],
        ]);

        Log::info('Formule modifiée', ['admin_id' => auth()->id(), 'plan' => $plan->code, 'price' => $plan->monthly_price]);

        session()->flash('success', "Formule {$plan->name} enregistrée.");
    }

    public function render()
    {
        return view('livewire.admin.plans-manage', [
            'plans' => Plan::withCount('restaurants')->orderBy('sort_order')->get(),
        ]);
    }
}
