<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Restaurant extends Model
{
    use HasFactory;

    // plan_id et paid_until volontairement absents : seul le Super Admin
    // les modifie, via RecordSubscriptionPayment (forceFill)
    protected $fillable = [
        'name', 'logo', 'type', 'phone', 'email', 'address',
        'commune', 'city', 'opening_hours',
        'subscription_status', 'trial_ends_at',
        'service_mode', 'credit_enabled', 'payment_timing',
        'whatsapp_report_enabled', 'report_phone',
        'receipt_required_from', 'setup_done_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'paid_until' => 'datetime',
        'credit_enabled' => 'boolean',
        'whatsapp_report_enabled' => 'boolean',
        'receipt_required_from' => 'integer',
        'setup_done_at' => 'datetime',
    ];

    protected $attributes = [
        'service_mode' => 'both',
        'credit_enabled' => false,
        'payment_timing' => 'before',
        'whatsapp_report_enabled' => true,
    ];

    public const PAYMENT_TIMINGS = [
        'before' => ['label' => 'Paiement avant service', 'hint' => 'La serveuse apporte l\'argent au comptoir, puis on sert. Pas d\'argent, pas de boisson.'],
        'after' => ['label' => 'Paiement à la fin', 'hint' => 'Le client consomme, puis paie l\'addition en partant.'],
    ];

    /**
     * Numéro qui reçoit le rapport du soir : celui choisi dans les
     * paramètres, sinon celui du patron.
     */
    public function reportRecipientPhone(): ?string
    {
        if (filled($this->report_phone)) {
            return $this->report_phone;
        }

        return $this->users()
            ->where('role', 'owner')
            ->where('status', 'active')
            ->whereNull('left_at')
            ->orderBy('id')
            ->value('phone');
    }

    /**
     * Le maquis a-t-il des serveuses actives ? Sinon, la personne qui prend
     * la commande encaisse elle-même : pas de comptoir, pas de sacoche.
     */
    public function hasActiveServers(): bool
    {
        return $this->users()->where('role', 'server')->where('status', 'active')->exists();
    }

    public function paysBeforeService(): bool
    {
        return $this->payment_timing !== 'after';
    }

    public const SERVICE_MODES = [
        'both' => ['label' => 'Tables et vente rapide', 'hint' => 'Service à table, et vente directe au comptoir.'],
        'tables' => ['label' => 'Service à table', 'hint' => 'Chaque commande est liée à une table.'],
        'quick' => ['label' => 'Vente rapide', 'hint' => 'Pas de tables : on sert et on encaisse directement.'],
    ];

    public function usesTables(): bool
    {
        return in_array($this->service_mode, ['tables', 'both'], true);
    }

    /** La photo du reçu est-elle obligatoire pour une dépense de ce montant ? (réglage à 0 = toujours) */
    public function receiptRequiredFor(int $amount): bool
    {
        return $amount >= (int) $this->receipt_required_from;
    }

    public function usesQuickSale(): bool
    {
        return in_array($this->service_mode, ['quick', 'both'], true);
    }

    public function creditEnabled(): bool
    {
        return $this->credit_enabled === true;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended';
    }

    public function isTrialExpired(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isPast();
    }

    /** Durée d'essai gratuit proposée par défaut (création d'un maquis, site public). */
    public const DEFAULT_TRIAL_DAYS = 14;

    /** Jours de tolérance après la date payée avant le verrouillage. */
    public const GRACE_DAYS = 3;

    /** Rappel au patron quand l'échéance approche. */
    public const REMINDER_DAYS = 7;

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /** Dernier paiement d'abonnement reçu (page Abonnés du Super Admin). */
    public function lastSubscriptionPayment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SubscriptionPayment::class)->latestOfMany();
    }

    /** Onglets de la page Abonnés, dans l'ordre d'affichage. */
    public const FOLLOW_UP_GROUPS = [
        'retard' => 'En retard',
        'bientot' => 'Bientôt',
        'ajour' => 'À jour',
        'essai' => 'En essai',
        'bloques' => 'Bloqués',
    ];

    /**
     * Où ranger ce maquis pour le suivi des paiements :
     * bloques (verrouillé), retard (échéance dépassée, encore dans les jours de grâce),
     * essai, bientot (échéance dans les 7 jours), ajour.
     */
    public function followUpGroup(): string
    {
        return match (true) {
            $this->isLocked() => 'bloques',
            $this->isInGracePeriod() => 'retard',
            $this->subscription_status === 'trial' => 'essai',
            $this->needsRenewalReminder() => 'bientot',
            default => 'ajour',
        };
    }

    /**
     * Date jusqu'à laquelle l'accès est réglé : fin d'essai, ou date payée.
     * Null = pas de date limite (maquis activé à la main, sans échéance).
     */
    public function accessEndsAt(): ?Carbon
    {
        return match ($this->subscription_status) {
            'trial' => $this->trial_ends_at,
            'active' => $this->paid_until,
            default => null,
        };
    }

    /** Date payée dépassée (on est peut-être encore dans les jours de grâce). */
    public function isPaymentOverdue(): bool
    {
        return $this->subscription_status === 'active'
            && $this->paid_until !== null
            && $this->paid_until->isPast();
    }

    /** Heure de coupure : le matin, quand le maquis est fermé (jamais en plein service). */
    public const LOCK_HOUR = 10;

    /**
     * Moment où l'accès sera coupé faute de paiement : le matin à 10 h,
     * après les jours de grâce. Null si pas d'échéance payée.
     */
    public function lockAt(): ?Carbon
    {
        return $this->paid_until
            ?->copy()
            ->startOfDay()
            ->addDays(self::GRACE_DAYS + 1)
            ->setTime(self::LOCK_HOUR, 0);
    }

    /** Date payée + jours de grâce dépassés (au matin) : le maquis est verrouillé. */
    public function isSubscriptionExpired(): bool
    {
        return $this->isPaymentOverdue() && $this->lockAt()->isPast();
    }

    public function isInGracePeriod(): bool
    {
        return $this->isPaymentOverdue() && ! $this->isSubscriptionExpired();
    }

    /** Jours restants avant l'échéance (négatif si dépassée), null sans échéance. */
    public function daysLeft(): ?int
    {
        $end = $this->accessEndsAt();

        return $end ? (int) floor(now()->diffInDays($end, false)) : null;
    }

    /** À rappeler au patron : échéance proche, ou dépassée mais encore en grâce. */
    public function needsRenewalReminder(): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        $days = $this->daysLeft();

        return $days !== null && $days <= self::REMINDER_DAYS;
    }

    /**
     * Libellé court de la situation, pour le Super Admin et le patron.
     *
     * @return array{0: string, 1: string} [libellé, couleur]
     */
    public function subscriptionBadge(): array
    {
        return match (true) {
            $this->isSuspended() => ['Suspendu', 'danger'],
            $this->isTrialExpired() => ['Essai terminé', 'danger'],
            $this->isSubscriptionExpired() => ['Impayé', 'danger'],
            $this->isInGracePeriod() => ['En retard', 'accent'],
            $this->subscription_status === 'trial' => ['Essai', 'accent'],
            $this->needsRenewalReminder() => ['Expire bientôt', 'accent'],
            default => ['À jour', 'primary'],
        };
    }

    /**
     * Rapport du soir envoyé automatiquement sur WhatsApp : inclus pendant
     * l'essai (le patron découvre tout), puis selon la formule.
     */
    public function hasAutoReport(): bool
    {
        return $this->subscription_status === 'trial' || (bool) $this->plan?->includes_auto_report;
    }

    /** Utilisateurs qui comptent dans la formule : actifs, pas partis. */
    public function activeUsersCount(): int
    {
        return $this->users()->where('status', 'active')->whereNull('left_at')->count();
    }

    public function userLimit(): ?int
    {
        return $this->plan?->max_users;
    }

    public function canAddActiveUser(): bool
    {
        $limit = $this->userLimit();

        return $limit === null || $this->activeUsersCount() < $limit;
    }

    /** Jours avant l'échéance où le patron reçoit un rappel. */
    public const REMINDER_STEPS = [7, 3, 1, 0];

    /**
     * Étape de rappel du jour (null = pas de rappel aujourd'hui) :
     * J-7, J-3, J-1, J0, retard (jours de grâce), coupe (accès coupé).
     * Une suspension manuelle par le Super Admin n'envoie pas de rappel.
     */
    public function reminderKind(): ?string
    {
        if (! in_array($this->subscription_status, ['trial', 'active'], true) || ! $this->accessEndsAt()) {
            return null;
        }

        if ($this->isLocked()) {
            return 'coupe';
        }

        if ($this->isInGracePeriod()) {
            return 'retard';
        }

        $days = $this->daysLeft();

        return in_array($days, self::REMINDER_STEPS, true) ? ($days === 0 ? 'J0' : "J-{$days}") : null;
    }

    /** Comment payer, en une ligne (numéros de la plateforme). */
    public static function howToPayLine(): string
    {
        $billing = array_filter(config('maquis.billing', []));
        $labels = SubscriptionPayment::METHODS;

        $line = collect(['wave', 'orange_money', 'mtn_momo', 'moov_money'])
            ->filter(fn ($m) => ! empty($billing[$m]))
            ->map(fn ($m) => "{$labels[$m]} {$billing[$m]}")
            ->join(' · ');

        return $line !== '' ? $line : 'contactez Maquis Express';
    }

    /** La situation, en une phrase, pour le rappel. */
    public function reminderSituation(): string
    {
        $end = $this->accessEndsAt();
        $what = $this->subscription_status === 'trial' ? 'Votre essai gratuit' : 'Votre abonnement';
        $days = $this->daysLeft();

        return match (true) {
            $this->isLocked() => "{$what} est terminé depuis le {$end->format('d/m/Y')} : votre maquis est en lecture seule (vous voyez vos chiffres mais ne pouvez plus vendre)",
            $this->isInGracePeriod() => "{$what} est terminé depuis le {$end->format('d/m/Y')} : l'accès sera coupé le {$this->lockAt()->format('d/m/Y')} à " . self::LOCK_HOUR . ' h',
            $days === 0 => "{$what} se termine aujourd'hui ({$end->format('d/m/Y')})",
            default => "{$what} se termine le {$end->format('d/m/Y')}, dans {$days} jour" . ($days > 1 ? 's' : ''),
        };
    }

    public function ownerPhone(): ?string
    {
        return $this->users()->where('role', 'owner')->where('status', 'active')->whereNull('left_at')->orderBy('id')->value('phone');
    }

    /** Lien WhatsApp avec le rappel prêt à envoyer au patron (envoi manuel). */
    public function reminderWhatsappUrl(): ?string
    {
        $phone = $this->ownerPhone();

        return $phone ? 'https://wa.me/225' . User::normalizePhone($phone) . '?text=' . rawurlencode($this->reminderText()) : null;
    }

    /** Message complet du rappel (envoi manuel par le Super Admin). */
    public function reminderText(): string
    {
        $plan = $this->plan;

        return "Bonjour, ici Maquis Express. {$this->reminderSituation()}."
            . ($plan ? " Formule {$plan->name} : " . number_format($plan->monthly_price, 0, ',', ' ') . ' FCFA/mois.' : '')
            . " Pour payer : " . self::howToPayLine() . ", référence {$this->paymentReference()}. Merci !";
    }

    /** Référence à indiquer par le maquis quand il paie (Wave, Orange…). */
    public function paymentReference(): string
    {
        return 'ME-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Maquis verrouillé : suspendu par le Super Admin, essai terminé, ou
     * abonnement impayé après les jours de grâce. Son personnel ne peut plus
     * utiliser l'application ; les données sont conservées.
     */
    public function isLocked(): bool
    {
        return $this->isSuspended() || $this->isTrialExpired() || $this->isSubscriptionExpired();
    }
}