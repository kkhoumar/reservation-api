<?php

namespace App\Livewire\Admin;

use App\Models\DemoRequest;
use App\Models\Restaurant;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

/**
 * Espace Super Admin : tous les maquis de la plateforme.
 * - Entrer dans un maquis pour voir et gérer toutes ses données.
 * - Verrouiller (suspendre) un maquis qui n'a pas payé, ou le réactiver.
 */
class RestaurantsList extends Component
{
    /** Recherche : nom du maquis, code ME-…, nom ou numéro du patron. */
    public string $search = '';

    /**
     * Le numéro d'une demande de démo a déjà un compte ? On le dit au Super Admin
     * (jamais au visiteur) : déjà client, ancien client ou employé d'un maquis.
     *
     * @return array<int, array{kind: string, label: string, restaurant: ?Restaurant}>
     */
    public static function demoMatches($demoRequests): array
    {
        $phones = $demoRequests->map(fn ($d) => User::normalizePhone($d->phone))->filter()->unique()->values();
        if ($phones->isEmpty()) {
            return [];
        }

        $users = User::withoutGlobalScopes()->with('restaurant')->whereIn('phone', $phones)->get()->keyBy(fn ($u) => User::normalizePhone($u->phone));
        $out = [];
        foreach ($demoRequests as $demo) {
            $user = $users[User::normalizePhone($demo->phone)] ?? null;
            if (! $user) {
                continue;
            }
            $r = $user->restaurant;
            $out[$demo->id] = match (true) {
                $user->isPlatformAdmin() => ['kind' => 'admin', 'label' => 'Votre compte Super Admin', 'restaurant' => null],
                ! $r => ['kind' => 'staff', 'label' => 'Numéro déjà utilisé par un compte', 'restaurant' => null],
                $user->role === 'owner' && $user->left_at === null && ! $r->isLocked() => ['kind' => 'client', 'label' => "Déjà client : {$r->name}", 'restaurant' => $r],
                $user->role === 'owner' && $user->left_at === null => ['kind' => 'former', 'label' => "Ancien client : {$r->name} (" . mb_strtolower($r->subscriptionBadge()[0]) . ')', 'restaurant' => $r],
                default => ['kind' => 'staff', 'label' => (User::ROLE_LABELS[$user->role] ?? 'Compte') . ($user->left_at ? ' (parti)' : '') . " chez {$r->name}", 'restaurant' => $r],
            };
        }

        return $out;
    }

    public function enterRestaurant($restaurantId)
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        session([User::ADMIN_RESTAURANT_SESSION_KEY => $restaurant->id]);

        Log::info('Super Admin entré dans un maquis', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
        ]);

        $this->redirect(route('dashboard.index'), navigate: false);
    }

    public function toggleStatus($restaurantId)
    {
        $restaurant = Restaurant::findOrFail($restaurantId);

        // Bloqué (suspendu, essai terminé ou impayé) → réactivé ; sinon → suspendu
        $newStatus = $restaurant->isLocked() ? 'active' : 'suspended';

        $restaurant->update(['subscription_status' => $newStatus]);

        // Réactivation manuelle d'un impayé : sans échéance, sinon il resterait bloqué.
        // Pour un vrai paiement, utiliser l'écran Abonnement (qui fixe la date payée).
        if ($newStatus === 'active' && $restaurant->isSubscriptionExpired()) {
            $restaurant->forceFill(['paid_until' => null])->save();
        }

        Log::info('Super Admin : statut de maquis modifié', [
            'admin_id' => auth()->id(),
            'restaurant_id' => $restaurant->id,
            'status' => $newStatus,
        ]);

        \App\Support\Toast::success($newStatus === 'suspended'
            ? "{$restaurant->name} est verrouillé : son personnel n'a plus accès à l'application."
            : "{$restaurant->name} est réactivé.");
    }

    /** Demande de démo : « contacté » ou « sans suite ». */
    public function setDemoStatus($demoRequestId, string $status)
    {
        if (! in_array($status, ['contacted', 'rejected'], true)) {
            return;
        }

        DemoRequest::findOrFail($demoRequestId)->forceFill(['status' => $status])->save();
    }

    public function render()
    {
        $all = Restaurant::with(['plan', 'users' => fn ($q) => $q->where('role', 'owner')->whereNull('left_at')->orderBy('id')])
            ->withCount(['users as active_users_count' => fn ($q) => $q->where('status', 'active')->whereNull('left_at')])
            ->orderBy('name')
            ->get();

        $restaurants = $all;
        $needle = mb_strtolower(trim($this->search));
        if ($needle !== '') {
            $digits = preg_replace('/\D/', '', $needle);
            $restaurants = $all->filter(function (Restaurant $r) use ($needle, $digits) {
                $owner = $r->users->first();
                $text = mb_strtolower($r->name . ' ' . $r->paymentReference() . ' ' . $r->commune . ' ' . ($owner ? $owner->first_name . ' ' . $owner->last_name : ''));

                return str_contains($text, $needle)
                    || (strlen($digits) >= 4 && $owner && str_contains(User::normalizePhone($owner->phone), $digits));
            })->values();
        }

        $demoRequests = DemoRequest::whereIn('status', ['new', 'contacted'])->latest()->get();

        $paying = $all->filter(fn (Restaurant $r) => $r->subscription_status === 'active' && $r->plan && ! $r->isLocked());

        return view('livewire.admin.restaurants-list', [
            'restaurants' => $restaurants,
            'monthlyRevenue' => (int) $paying->sum(fn (Restaurant $r) => $r->plan->monthly_price),
            'payingCount' => $paying->count(),
            'collectedThisMonth' => (int) SubscriptionPayment::where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'trialCount' => $all->filter(fn (Restaurant $r) => $r->subscription_status === 'trial' && ! $r->isLocked())->count(),
            'toChaseCount' => $all->filter(fn (Restaurant $r) => $r->subscription_status !== 'suspended'
                && ($r->isSubscriptionExpired() || $r->isInGracePeriod() || ($r->subscription_status === 'active' && $r->needsRenewalReminder())))->count(),
            // Rappels du jour non partis automatiquement (API WhatsApp absente ou refus)
            'manualReminders' => \App\Models\SubscriptionReminder::with('restaurant')
                ->where('status', '!=', 'sent')
                ->where('created_at', '>=', today())
                ->get(),
            'demoRequests' => $demoRequests,
            'demoMatches' => self::demoMatches($demoRequests),
            'totalCount' => $all->count(),
        ]);
    }
}
