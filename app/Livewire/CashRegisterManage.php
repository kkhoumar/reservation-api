<?php

namespace App\Livewire;

use App\Models\CashRegister;
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
            'openingAmount' => 'required|integer|min:0',
        ]);

        CashRegister::create([
            'restaurant_id' => auth()->user()->currentRestaurantId(),
            'opened_by' => auth()->id(),
            'opening_amount' => $this->openingAmount,
            'opened_at' => now(),
        ]);

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
            'countedAmount' => 'required|integer|min:0',
        ]);

        $theoretical = $register->calculateTheoreticalAmount();
        $diff = $this->countedAmount - $theoretical;

        if (abs($diff) >= self::DISCREPANCY_THRESHOLD && trim($this->closingNote) === '') {
            $this->addError('closingNote', "Écart de " . number_format(abs($diff), 0, ',', ' ') . " FCFA — merci d'indiquer une raison avant de confirmer.");
            return;
        }

        $register->close($this->countedAmount, auth()->id(), $this->closingNote ?: null);

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

        return view('livewire.cash-register-manage', [
            'register' => $register,
            'breakdown' => $breakdown,
            'history' => $history,
            'threshold' => self::DISCREPANCY_THRESHOLD,
        ]);
    }
}