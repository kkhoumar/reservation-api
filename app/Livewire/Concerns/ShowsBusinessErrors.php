<?php

namespace App\Livewire\Concerns;

use App\Exceptions\BusinessRuleException;

/**
 * Une règle métier refusée pendant une action (lecture seule, montant
 * invalide…) s'affiche en message d'erreur au lieu d'une page de plantage.
 */
trait ShowsBusinessErrors
{
    public function exception($e, $stopPropagation): void
    {
        if ($e instanceof BusinessRuleException) {
            \App\Support\Toast::error($e->getMessage());
            $this->addError('business', $e->getMessage());
            $stopPropagation();
        }
    }
}
