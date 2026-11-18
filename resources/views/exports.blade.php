<x-layouts.app>
    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Pour le patron et le comptable
        </div>
        <h1>Exports</h1>
        <p style="font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 0 0;">
            Téléchargez vos chiffres. Les fichiers s'ouvrent directement dans <strong>Excel</strong>, Google Sheets ou LibreOffice.
        </p>
    </div>

    <form method="GET" action="{{ route('exports.index') }}" class="card" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 16px;">
        <div>
            <label style="font-size: 13px; font-weight: 600; display: block;">Du</label>
            <input type="date" name="from" value="{{ $from }}" style="margin-top: 4px;">
        </div>
        <div>
            <label style="font-size: 13px; font-weight: 600; display: block;">Au</label>
            <input type="date" name="to" value="{{ $to }}" style="margin-top: 4px;">
        </div>
        <button type="submit" class="btn btn-ghost">Changer la période</button>
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            @php
                $shortcuts = [
                    'Ce mois' => [now()->startOfMonth(), now()],
                    'Mois dernier' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                    'Cette année' => [now()->startOfYear(), now()],
                ];
            @endphp
            @foreach ($shortcuts as $label => [$a, $b])
                <a href="{{ route('exports.index', ['from' => $a->toDateString(), 'to' => $b->toDateString()]) }}"
                   class="btn btn-ghost" style="padding: 8px 12px; font-size: 13px; text-decoration: none;">{{ $label }}</a>
            @endforeach
        </div>
    </form>

    <div style="font-size: 14px; margin-bottom: 12px;">
        Période : <strong>du {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</strong>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px;">
        @foreach ($exports as $type => [$label, $description])
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; gap: 12px;">
                <div>
                    <h3 style="margin: 0 0 4px 0;">{{ $label }}</h3>
                    <div style="font-size: 13px; color: var(--color-text-secondary);">{{ $description }}</div>
                </div>
                <a href="{{ route('exports.download', ['type' => $type, 'from' => $from, 'to' => $to]) }}"
                   class="btn btn-primary" style="justify-content: center; text-decoration: none;">Télécharger</a>
            </div>
        @endforeach
    </div>
</x-layouts.app>
