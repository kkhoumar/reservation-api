<?php

namespace App\Livewire;

use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\CashRemittance;
use App\Models\User;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Sacoches des serveuses : qui a combien sur elle, et réception des
 * versements à la caisse (caissière, gérante, patron).
 */
class Sacoches extends Component
{
    #[Locked]
    public ?int $serverId = null;

    public ?int $amount = null;
    public string $note = '';

    protected function servers()
    {
        // Les serveuses désactivées restent visibles tant qu'elles doivent de l'argent
        return User::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->where('role', 'server')
            ->orderBy('first_name')
            ->get()
            ->map(function (User $server) {
                $server->collected = $server->sacocheCollected();
                $server->remitted = $server->sacocheRemitted();
                $server->balance = $server->collected - $server->remitted;

                return $server;
            })
            ->filter(fn (User $s) => $s->status === 'active' || $s->balance > 0)
            ->values();
    }

    protected function findServer(int $id): User
    {
        return User::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->where('role', 'server')
            ->findOrFail($id);
    }

    public function startRemittance(int $id): void
    {
        $server = $this->findServer($id);

        $this->serverId = $server->id;
        $this->amount = max(0, $server->sacocheBalance());
        $this->note = '';
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset(['serverId', 'amount', 'note']);
        $this->resetValidation();
    }

    public function confirmRemittance(): void
    {
        if ($this->serverId === null) {
            return;
        }

        $this->validate([
            'amount' => 'required|integer|min:1|max:100000000',
            'note' => 'nullable|string|max:255',
        ]);

        $server = $this->findServer($this->serverId);

        try {
            CashRemittance::record($server, auth()->user(), $this->amount, trim($this->note));
        } catch (BusinessRuleException $e) {
            $this->addError('amount', $e->getMessage());

            return;
        }

        session()->flash('success', 'Versement de ' . number_format($this->amount, 0, ',', ' ') . ' FCFA reçu de ' . $server->first_name . '.');
        $this->cancel();
    }

    public function render()
    {
        $register = CashRegister::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();

        $servers = $this->servers();

        return view('livewire.sacoches', [
            'servers' => $servers,
            'totalOutstanding' => $servers->sum('balance'),
            'register' => $register,
            'selected' => $this->serverId ? $servers->firstWhere('id', $this->serverId) : null,
            'recent' => $register
                ? CashRemittance::with(['server', 'receiver'])
                    ->where('cash_register_id', $register->id)
                    ->latest()
                    ->get()
                : collect(),
        ]);
    }
}
