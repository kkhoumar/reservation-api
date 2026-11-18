<?php

namespace App\Livewire;

use App\Models\CashRegister;
use App\Models\Restaurant;
use App\Models\User;
use App\Jobs\SendRegisterReport;
use App\Services\WhatsAppCloud;
use App\Support\SalesSummary;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CashRegisterManage extends Component
{
    public int $openingAmount = 0;
    public ?int $countedAmount = null;
    public string $closingNote = '';
    public bool $showCloseForm = false;
    /** Billetage : nombre de billets/pièces par clé de CashRegister::DENOMINATIONS */
    public array $counts = [];
    public bool $directEntry = false;
    public bool $showJournal = true;

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
            'counts' => 'array',
            'counts.*' => 'nullable|integer|min:0|max:100000',
        ], [
            'counts.*.integer' => 'Nombre invalide.',
            'counts.*.min' => 'Nombre invalide.',
        ]);

        // Billetage saisi : le montant compté vient toujours du détail des billets
        $cashCount = $this->cleanCounts();
        if ($cashCount !== []) {
            $this->countedAmount = CashRegister::countTotal($cashCount);
        }

        $this->validate([
            'countedAmount' => 'required|integer|min:0|max:100000000',
            'closingNote' => 'nullable|string|max:1000',
        ], [
            'countedAmount.required' => 'Comptez l\'argent du tiroir (billets et pièces) ou saisissez le total.',
        ]);

        $theoretical = $register->calculateTheoreticalAmount();
        $diff = $this->countedAmount - $theoretical;

        if (abs($diff) >= self::DISCREPANCY_THRESHOLD && trim($this->closingNote) === '') {
            $this->addError('closingNote', "Écart de " . number_format(abs($diff), 0, ',', ' ') . " FCFA — merci d'indiquer une raison avant de confirmer.");
            return;
        }

        $closed = DB::transaction(function () use ($register, $cashCount) {
            // Verrou + revérification : une caisse ne se clôture qu'une fois
            $register = CashRegister::whereKey($register->id)->lockForUpdate()->first();

            if (!$register || !$register->isOpen()) {
                return false;
            }

            $register->close($this->countedAmount, auth()->id(), $this->closingNote ?: null, $cashCount ?: null);

            return true;
        });

        if (!$closed) {
            session()->flash('error', 'Cette caisse est déjà clôturée.');

            return;
        }

        // Rapport du soir envoyé automatiquement au patron (si WhatsApp est configuré)
        if ($register->restaurant?->whatsapp_report_enabled && $register->restaurant->hasAutoReport() && app(WhatsAppCloud::class)->isConfigured()) {
            SendRegisterReport::queueFor($register->fresh());
        }

        $this->redirect(route('cash-register.closed', ['register' => $register->id]), navigate: false);
    }

    /** @return array<string, int> billetage sans les cases vides ni les clés inconnues */
    protected function cleanCounts(): array
    {
        $clean = [];

        foreach (CashRegister::DENOMINATIONS as $key => $_) {
            $n = (int) ($this->counts[$key] ?? 0);
            if ($n > 0) {
                $clean[$key] = $n;
            }
        }

        return $clean;
    }

    /** Le total compté se met à jour à chaque billet saisi. */
    public function updatedCounts(): void
    {
        $clean = $this->cleanCounts();
        $this->countedAmount = $clean === [] ? null : CashRegister::countTotal($clean);
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

        $sales = $register
            ? new SalesSummary($register->restaurant_id, $register->opened_at, now())
            : null;

        return view('livewire.cash-register-manage', [
            'register' => $register,
            'outstanding' => $outstanding,
            'breakdown' => $breakdown,
            'sales' => $sales,
            'journal' => $register ? $register->journal() : collect(),
            'history' => $history,
            'threshold' => self::DISCREPANCY_THRESHOLD,
            'denominations' => CashRegister::DENOMINATIONS,
            'lastClosed' => $register ? null : $history->first(),
        ]);
    }
}