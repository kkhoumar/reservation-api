<?php

namespace App\Livewire;

use App\Models\CashMovement;
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

    // ---------- Argent du tiroir (ajouter / retirer) ----------
    public bool $showMove = false;
    public string $moveType = 'in';
    public ?int $moveAmount = null;
    public string $moveReason = '';
    public string $moveNote = '';

    // ---------- Corriger le fonds de départ (tant que rien ne s'est passé) ----------
    public bool $fixingOpening = false;
    public ?int $newOpening = null;

    public function openMove(string $type = 'in'): void
    {
        $this->resetValidation();
        $this->moveType = $type === 'out' && CashMovement::canRemove(auth()->user()) ? 'out' : 'in';
        $this->moveAmount = null;
        $this->moveReason = '';
        $this->moveNote = '';
        $this->showMove = true;
    }

    public function updatedMoveType(): void
    {
        $this->moveReason = '';
    }

    public function saveMovement(): void
    {
        $user = auth()->user();
        $type = $this->moveType === 'out' ? 'out' : 'in';

        if (! ($type === 'out' ? CashMovement::canRemove($user) : CashMovement::canAdd($user))) {
            \App\Support\Toast::error($type === 'out'
                ? "Seuls le patron et la gérante peuvent retirer de l'argent du tiroir."
                : "Vous ne pouvez pas ajouter d'argent à la caisse.");

            return;
        }

        $this->validate([
            'moveAmount' => 'required|integer|min:1|max:100000000',
            'moveReason' => ['required', \Illuminate\Validation\Rule::in(array_keys(CashMovement::REASONS[$type]))],
            'moveNote' => $this->moveReason === 'other' ? 'required|string|max:255' : 'nullable|string|max:255',
        ], [
            'moveAmount.required' => 'Combien ? Tapez le montant.',
            'moveAmount.min' => 'Le montant doit être plus grand que 0.',
            'moveReason.required' => 'Pourquoi ? Touchez une des raisons.',
            'moveReason.in' => 'Pourquoi ? Touchez une des raisons.',
            'moveNote.required' => 'Écrivez en quelques mots pourquoi.',
        ]);

        $amount = (int) $this->moveAmount;

        $error = DB::transaction(function () use ($user, $type, $amount) {
            // La caisse ne doit pas être clôturée en même temps
            $register = CashRegister::where('restaurant_id', $user->currentRestaurantId())
                ->whereNull('closed_at')->lockForUpdate()->first();

            if (! $register) {
                return 'La caisse est fermée. Ouvrez-la d\'abord.';
            }

            if ($type === 'out') {
                $inDrawer = $register->calculateTheoreticalAmount();
                if ($amount > $inDrawer) {
                    return 'Le tiroir ne devrait contenir que ' . number_format(max(0, $inDrawer), 0, ',', ' ') . ' F. Vérifiez le montant.';
                }
            }

            $move = CashMovement::create([
                'restaurant_id' => $register->restaurant_id,
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'reason' => $this->moveReason,
                'note' => trim($this->moveNote) ?: null,
            ]);

            $why = mb_strtolower($move->reasonLabel()) . ($move->note ? ' : ' . $move->note : '');
            \App\Models\ActivityLog::record($type === 'in' ? 'cash_in' : 'cash_out',
                ($type === 'in' ? 'a ajouté ' : 'a retiré ') . \App\Models\ActivityLog::fcfa($amount) . ($type === 'in' ? ' au tiroir (' : ' du tiroir (') . $why . ').',
                ['amount' => $amount, 'meta' => ['cash_register_id' => $register->id]]);

            return null;
        });

        if ($error) {
            $this->addError('moveAmount', $error);

            return;
        }

        $this->showMove = false;
        \App\Support\Toast::success(number_format($amount, 0, ',', ' ') . ($type === 'in' ? ' F ajoutés au tiroir.' : ' F retirés du tiroir.'));
    }

    /** Fonds de départ mal tapé : on peut le corriger tant que rien n'est passé par la caisse. */
    public function fixOpening(): void
    {
        $register = $this->currentRegister();

        if (! $register || ! CashRegister::canOpen(auth()->user()) || $register->journal()->isNotEmpty()) {
            \App\Support\Toast::error('Le fonds de départ ne peut plus être corrigé : utilisez « Argent du tiroir ».');
            $this->fixingOpening = false;

            return;
        }

        $this->validate(['newOpening' => 'required|integer|min:0|max:100000000'], [
            'newOpening.required' => 'Tapez le bon montant.',
        ]);

        $old = (int) $register->opening_amount;
        $register->update(['opening_amount' => (int) $this->newOpening]);
        \App\Models\ActivityLog::record('opening_fixed',
            'a corrigé le fonds de départ : ' . \App\Models\ActivityLog::fcfa($old) . ' → ' . \App\Models\ActivityLog::fcfa((int) $this->newOpening) . '.',
            ['amount' => (int) $this->newOpening]);

        $this->fixingOpening = false;
        \App\Support\Toast::success('Fonds de départ corrigé : ' . number_format((int) $this->newOpening, 0, ',', ' ') . ' F.');
    }

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

        $opened = CashRegister::openFor($restaurantId, auth()->user(), (int) $this->openingAmount) !== null;

        if (!$opened) {
            \App\Support\Toast::error('Une caisse est déjà ouverte.');

            return;
        }

        $this->openingAmount = 0;
        \App\Support\Toast::success('Caisse ouverte.');
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

            $gap = (int) $register->discrepancy;
            \App\Models\ActivityLog::record('register_closed', 'a clôturé la caisse : ' . \App\Models\ActivityLog::fcfa((int) $this->countedAmount) . ' comptés, '
                . ($gap === 0 ? 'aucun écart.' : 'écart de ' . ($gap > 0 ? '+' : '') . \App\Models\ActivityLog::fcfa($gap) . '.')
                . ($this->closingNote ? ' Note : ' . trim($this->closingNote) : ''), ['amount' => $gap, 'meta' => ['register_id' => $register->id]]);

            return true;
        });

        if (!$closed) {
            \App\Support\Toast::error('Cette caisse est déjà clôturée.');

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
            'canAddCash' => CashMovement::canAdd(auth()->user()),
            'canRemoveCash' => CashMovement::canRemove(auth()->user()),
            'moveReasons' => CashMovement::REASONS,        ]);
    }
}