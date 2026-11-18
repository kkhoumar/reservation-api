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
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'paid_until' => 'datetime',
        'credit_enabled' => 'boolean',
        'whatsapp_report_enabled' => 'boolean',
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