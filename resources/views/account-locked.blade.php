<x-layouts.guest>
    @php $restaurant = auth()->user()?->restaurant; @endphp

    <div class="auth-badge" style="background: var(--color-danger);">!</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 8px 0;">Accès suspendu</h1>

    <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 16px 0; line-height: 1.5;">
        @if ($restaurant?->isTrialExpired())
            La période d'essai de <strong>{{ $restaurant->name }}</strong> est terminée.
        @else
            L'accès de <strong>{{ $restaurant?->name ?? 'votre établissement' }}</strong> à Maquis Express est suspendu.
        @endif
    </p>

    <p style="font-size: 14px; margin: 0 0 24px 0; line-height: 1.5;">
        Vos données sont conservées. Pour réactiver votre compte, contactez Maquis Express
        afin de régler votre abonnement.
    </p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-primary">Se déconnecter</button>
    </form>
</x-layouts.guest>
