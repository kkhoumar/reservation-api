<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CashRegisterManage extends Component
{
    public int $openingAmount = 0;
    public ?int $countedAmount = null;
    public string $closingNote = '';
    public bool $showCloseForm = false;

    const DISCREPANCY_THRESHOLD = 500;

    protected function currentRegister(): ?CashRegister
    {
        return CashRegister::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNull('closed_at')
            ->latest('opened_at')
            ->first();
    }

    public function openRegister()
    {
        $this->validate([
            'openingAmount' => 'required|integer|min:0|max:100000000',
        ]);

        $restaurantId = auth()->user()->currentRestaurantId();

        $opened = DB::transaction(function () use ($restaurantId) {
            // Verrou sur le maquis : deux ouvertures simultanées sont sérialisées
            Restaurant::whereKey($restaurantId)->lockForUpdate()->first();

            if ($this->currentRegister()) {
                return false;
            }

            CashRegister::create([
                'restaurant_id' => $restaurantId,
                'opened_by' => auth()->id(),
                'opening_amount' => $this->openingAmount,
                'opened_at' => now(),
            ]);

            return true;
        });

        if (!$opened) {
            session()->flash('error', 'Une caisse est déjà ouverte.');

            return;
        }

        $this->openingAmount = 0;
        session()->flash('success', 'Caisse ouverte.');
    }

    public function confirmClose()
    {
        $register = $this->currentRegister();

        if (!$register) {
            return;
        }

        $this->validate([
            'countedAmount' => 'required|integer|min:0|max:100000000',
            'closingNote' => 'nullable|string|max:1000',
        ]);

        $theoretical = $register->calculateTheoreticalAmount();
        $diff = $this->countedAmount - $theoretical;

        if (abs($diff) >= self::DISCREPANCY_THRESHOLD && trim($this->closingNote) === '') {
            $this->addError('closingNote', "Écart de " . number_format(abs($diff), 0, ',', ' ') . " FCFA — merci d'indiquer une raison avant de confirmer.");
            return;
        }

        $closed = DB::transaction(function () use ($register) {
            // Verrou + revérification : une caisse ne se clôture qu'une fois
            $register = CashRegister::whereKey($register->id)->lockForUpdate()->first();

            if (!$register || !$register->isOpen()) {
                return false;
            }

            $register->close($this->countedAmount, auth()->id(), $this->closingNote ?: null);

            return true;
        });

        if (!$closed) {
            session()->flash('error', 'Cette caisse est déjà clôturée.');

            return;
        }

        $this->redirect(route('cash-register.closed', ['register' => $register->id]), navigate: false);
    }

    public function render()
    {
        $register = $this->currentRegister();

        $breakdown = $register?->breakdown();

        $history = CashRegister::where('restaurant_id', auth()->user()->currentRestaurantId())
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->limit(5)
            ->get();

        // Serveuses qui ont encore de l'argent sur elles (rappel avant clôture)
        $outstanding = $register
            ? User::where('restaurant_id', auth()->user()->currentRestaurantId())
                ->where('role', 'server')
                ->orderBy('first_name')
                ->get()
                ->each(fn (User $s) => $s->balance = $s->sacocheBalance())
                ->filter(fn (User $s) => $s->balance > 0)
                ->values()
            : collect();

        return view('livewire.cash-register-manage', [
            'register' => $register,
            'outstanding' => $outstanding,
            'breakdown' => $breakdown,
            'history' => $history,
            'threshold' => self::DISCREPANCY_THRESHOLD,
        ]);
    }
}