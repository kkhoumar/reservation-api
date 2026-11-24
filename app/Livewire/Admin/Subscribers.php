<?php

namespace App\Livewire\Admin;

use App\Models\Restaurant;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Super Admin : suivi des abonnés. Pour chaque maquis : le patron, le dernier
 * paiement, la prochaine échéance, le retard éventuel, et les actions
 * (relancer sur WhatsApp, enregistrer un paiement). Les urgences en haut.
 */
class Subscribers extends Component
{
    #[Url(as: 'onglet', except: 'tous')]
    public string $tab = 'tous';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function boot(): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'tous' || array_key_exists($tab, Restaurant::FOLLOW_UP_GROUPS) ? $tab : 'tous';
    }

    /** Ordre : les plus urgents d'abord (échéance la plus proche, ou dépassée depuis le moins longtemps pour les bloqués). */
    private function sorted(Collection $restaurants): Collection
    {
        $rank = array_flip(array_keys(Restaurant::FOLLOW_UP_GROUPS));

        return $restaurants->sort(function (Restaurant $a, Restaurant $b) use ($rank) {
            $byGroup = $rank[$a->followUpGroup()] <=> $rank[$b->followUpGroup()];
            if ($byGroup !== 0) {
                return $byGroup;
            }

            $ea = $a->accessEndsAt()?->timestamp ?? PHP_INT_MAX;
            $eb = $b->accessEndsAt()?->timestamp ?? PHP_INT_MAX;

            return $a->followUpGroup() === 'bloques' ? $eb <=> $ea : $ea <=> $eb;
        })->values();
    }

    public function render()
    {
        $all = Restaurant::with([
            'plan',
            'lastSubscriptionPayment',
            'users' => fn ($q) => $q->where('role', 'owner')->whereNull('left_at')->orderBy('id'),
        ])->get();

        $counts = ['tous' => $all->count()] + collect(Restaurant::FOLLOW_UP_GROUPS)
            ->map(fn ($label, $group) => $all->filter(fn (Restaurant $r) => $r->followUpGroup() === $group)->count())
            ->all();

        $term = mb_strtolower(trim($this->search));
        $digits = preg_replace('/\D+/', '', $term);

        $rows = $all
            ->when($this->tab !== 'tous', fn ($c) => $c->filter(fn (Restaurant $r) => $r->followUpGroup() === $this->tab))
            ->when($term !== '', fn ($c) => $c->filter(function (Restaurant $r) use ($term, $digits) {
                $owner = $r->users->first();
                $haystack = mb_strtolower(implode(' ', [$r->name, $r->commune, $r->paymentReference(), $owner?->first_name, $owner?->last_name]));

                return str_contains($haystack, $term) || ($digits !== '' && $owner && str_contains((string) $owner->phone, $digits));
            }));

        // À encaisser d'ici la fin du mois : abonnés payants dont l'échéance tombe avant
        $dueThisMonth = $all->filter(fn (Restaurant $r) => $r->subscription_status === 'active' && $r->plan && $r->paid_until
            && ! $r->isSuspended() && $r->paid_until->lte(now()->endOfMonth()));

        return view('livewire.admin.subscribers', [
            'rows' => $this->sorted($rows),
            'counts' => $counts,
            'groups' => Restaurant::FOLLOW_UP_GROUPS,
            'dueThisMonthAmount' => (int) $dueThisMonth->sum(fn (Restaurant $r) => $r->plan->monthly_price),
            'dueThisMonthCount' => $dueThisMonth->count(),
            // Impayés à récupérer : en retard (jours de grâce) ou coupés faute de paiement
            'lateAmount' => (int) $all->filter(fn (Restaurant $r) => $r->plan && ! $r->isSuspended()
                && ($r->isInGracePeriod() || $r->isSubscriptionExpired()))
                ->sum(fn (Restaurant $r) => $r->plan->monthly_price),
            'methods' => \App\Models\SubscriptionPayment::METHODS,
        ]);
    }
}
