<x-layouts.guest>
    @php
        $restaurant = auth()->user()?->restaurant;
        $billing = array_filter(['whatsapp' => \App\Support\SupportContact::whatsappNumber()] + config('maquis.billing', []));
        $labels = \App\Models\SubscriptionPayment::METHODS;
    @endphp

    <div class="auth-badge" style="background: var(--color-danger);">!</div>
    <h1 style="font-size: 20px; font-weight: 800; margin: 0 0 8px 0;">Accès suspendu</h1>

    <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0 0 16px 0; line-height: 1.5;">
        @if ($restaurant?->isTrialExpired())
            La période d'essai de <strong>{{ $restaurant->name }}</strong> est terminée.
        @elseif ($restaurant?->isSubscriptionExpired())
            L'abonnement de <strong>{{ $restaurant->name }}</strong> n'a pas été renouvelé
            (payé jusqu'au {{ $restaurant->paid_until->format('d/m/Y') }}).
        @else
            L'accès de <strong>{{ $restaurant?->name ?? 'votre établissement' }}</strong> à Maquis Express est suspendu.
        @endif
    </p>

    <p style="font-size: 14px; margin: 0 0 12px 0; line-height: 1.5;">
        <strong>Vos données sont conservées.</strong> L'accès revient dès que l'abonnement est réglé.
    </p>

    @if ($restaurant && auth()->user()?->role === 'owner')
        <div style="background: #FBF6EC; border-radius: 10px; padding: 12px 14px; font-size: 14px; text-align: left; margin-bottom: 20px; line-height: 1.6;">
            @if ($restaurant->plan)
                Formule {{ $restaurant->plan->name }} : <strong>{{ number_format($restaurant->plan->monthly_price, 0, ',', ' ') }} FCFA / mois</strong><br>
            @endif
            @foreach (['wave', 'orange_money', 'mtn_momo', 'moov_money'] as $method)
                @if (! empty($billing[$method]))
                    {{ $labels[$method] }} : <strong>{{ $billing[$method] }}</strong><br>
                @endif
            @endforeach
            Référence à indiquer : <strong>{{ $restaurant->paymentReference() }}</strong>
            @if (! empty($billing['whatsapp']))
                <br><a href="https://wa.me/225{{ \App\Models\User::normalizePhone($billing['whatsapp']) }}?text={{ rawurlencode('Bonjour, je souhaite renouveler l\'abonnement de ' . $restaurant->name . ' (réf. ' . $restaurant->paymentReference() . ').') }}" target="_blank" rel="noopener">Contacter Maquis Express sur WhatsApp</a>
            @endif
        </div>
    @else
        <p style="font-size: 14px; margin: 0 0 24px 0;">Prévenez le patron du maquis.</p>
    @endif

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn-primary">Se déconnecter</button>
    </form>
</x-layouts.guest>
