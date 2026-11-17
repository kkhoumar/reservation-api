<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Règle métier refusée (commande déjà payée, stock insuffisant, caisse
 * déjà ouverte...). Le message est destiné à l'utilisateur : les écrans
 * l'affichent tel quel.
 */
class BusinessRuleException extends RuntimeException
{
}
