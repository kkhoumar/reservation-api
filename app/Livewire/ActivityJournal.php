<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Journal des actions (patron et gérante) : qui a fait quoi, et quand.
 * Lecture seule : rien ne s'y modifie ni ne s'y efface.
 */
class ActivityJournal extends Component
{
    use WithPagination;

    public const PERIODS = [
        'today' => "Aujourd'hui",
        '7days' => '7 jours',
        '30days' => '30 jours',
    ];

    #[Url(as: 'periode')]
    public string $period = '7days';

    #[Url(as: 'type')]
    public string $group = '';

    #[Url(as: 'qui')]
    public ?int $userId = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->isPlatformAdmin() || in_array($user->role, ['owner', 'manager'], true), 403);
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function setPeriod(string $period): void
    {
        $this->period = array_key_exists($period, self::PERIODS) ? $period : '7days';
        $this->resetPage();
    }

    public function setGroup(string $group): void
    {
        $this->group = array_key_exists($group, ActivityLog::GROUPS) ? $group : '';
        $this->resetPage();
    }

    protected function since(): Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            '30days' => now()->subDays(29)->startOfDay(),
            default => now()->subDays(6)->startOfDay(),
        };
    }

    public function render()
    {
        $since = $this->since();
        $base = ActivityLog::query()->where('created_at', '>=', $since);

        $logs = (clone $base)
            ->with('user')
            ->when($this->group !== '', fn ($q) => $q->whereIn('action', ActivityLog::actionsInGroup($this->group)))
            ->when($this->userId, fn ($q) => $q->where('user_id', $this->userId))
            ->latest('created_at')->latest('id')
            ->paginate(40);

        $cancelled = (clone $base)->where('action', 'order_cancelled');
        $gestures = (clone $base)->whereIn('action', ['item_offered', 'discount']);

        return view('livewire.activity-journal', [
            'logs' => $logs,
            'periods' => self::PERIODS,
            'groups' => ActivityLog::GROUPS,
            'people' => User::where('restaurant_id', auth()->user()->currentRestaurantId())->orderBy('first_name')->get(),
            'stats' => [
                'sensitive' => (clone $base)->whereIn('action', ActivityLog::actionsInGroup('sensitive'))->count(),
                'cancelled' => $cancelled->count(),
                'cancelled_amount' => abs((int) (clone $base)->where('action', 'order_cancelled')->sum('amount')),
                'gestures_amount' => abs((int) $gestures->sum('amount')),
                'logins' => (clone $base)->where('action', 'login')->count(),
            ],
        ]);
    }
}
