@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ');
    $line = 'display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--color-border); font-size: 14px;';
    $hint = 'display: block; font-size: 12px; color: var(--color-text-secondary); font-weight: 400;';
@endphp

<div>
    <x-flash />


    <div style="margin-bottom: 20px;">
        <div style="font-size: 12px; font-weight: 600; color: var(--color-accent); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
            Encaissement
        </div>
        <h1>Caisse</h1>
    </div>

    @if (!$register)
        {{-- ================= CAISSE FERMÉE : OUVERTURE ================= --}}
        <div class="card" style="max-width: 480px;">
            <h3 style="margin-top: 0;">La caisse est fermée</h3>
            <p style="color: var(--color-text-secondary); font-size: 14px;">
                Pour commencer la journée, comptez les espèces que vous mettez dans le tiroir
                pour rendre la monnaie aux clients. C'est le <strong>fonds de départ</strong>.
            </p>

            @if ($lastClosed)
                <div style="background: var(--color-bg); border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 14px;">
                    Dernière clôture : <strong>{{ $fcfa($lastClosed->counted_amount) }} FCFA</strong> comptés
                    le {{ $lastClosed->closed_at->format('d/m à H:i') }} par {{ $lastClosed->closedByUser->first_name ?? '—' }}.
                </div>
            @endif

            <label style="font-size: 13px; font-weight: 600;">Fonds de départ (FCFA)</label>
            <x-money-input model="openingAmount" :empty="0" style="width: 100%; margin: 6px 0 16px 0; font-size: 18px;" />
            @error('openingAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror

            <button wire:click="openRegister" class="btn btn-primary" style="width: 100%; justify-content: center;">
                Ouvrir la caisse
            </button>
        </div>
    @else
        {{-- ================= CAISSE OUVERTE ================= --}}

        {{-- 1. Le chiffre principal --}}
        <div class="card" style="margin-bottom: 16px; border-left: 6px solid var(--color-primary); display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 13px; color: var(--color-text-secondary);">Argent qui doit être dans le tiroir maintenant</div>
                <div style="font-size: 34px; font-weight: 800; color: var(--color-primary); letter-spacing: -0.01em;">
                    {{ $fcfa($breakdown['theoretical']) }} <span style="font-size: 16px;">FCFA</span>
                </div>
                <div style="font-size: 13px; color: var(--color-text-secondary);">
                    Caisse ouverte par <strong>{{ $register->openedByUser->first_name }}</strong>
                    le {{ $register->opened_at->format('d/m à H:i') }}
                    (depuis {{ $register->opened_at->locale('fr')->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE) }})
                </div>
            </div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @if (auth()->user()->currentRestaurant()?->paysBeforeService())
                    <a href="{{ route('counter') }}" class="btn btn-ghost" style="text-decoration: none;">Comptoir</a>
                @endif
                <a href="{{ route('expenses.index') }}" class="btn btn-ghost" style="text-decoration: none;">+ Dépense</a>
                @if (! $showCloseForm)
                    <button type="button" @click="$wire.set('showCloseForm', true).then(() => $nextTick(() => document.getElementById('compter-tiroir')?.scrollIntoView({ behavior: 'smooth', block: 'start' })))" class="btn btn-danger">Clôturer la caisse</button>
                @endif
            </div>
        </div>

        {{-- 2. Les chiffres de la session --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 16px;">
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">Ventes de la session</div>
                <div style="font-size: 20px; font-weight: 800;">{{ $fcfa($sales->total()) }}</div>
                <div style="font-size: 12px; color: var(--color-text-secondary);">{{ $sales->count() }} bon{{ $sales->count() > 1 ? 's' : '' }} réglé{{ $sales->count() > 1 ? 's' : '' }}</div>
            </div>
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">Payé en espèces</div>
                <div style="font-size: 20px; font-weight: 800;">{{ $fcfa($sales->cash) }}</div>
                <div style="font-size: 12px; color: var(--color-text-secondary);">dans le tiroir ou les sacoches</div>
            </div>
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">Payé en Mobile Money</div>
                <div style="font-size: 20px; font-weight: 800;">{{ $fcfa($sales->mobileTotal()) }}</div>
                <div style="font-size: 12px; color: var(--color-text-secondary);">sur les téléphones</div>
            </div>
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">Vendu à crédit</div>
                <div style="font-size: 20px; font-weight: 800; color: {{ $sales->credit > 0 ? 'var(--color-accent)' : 'inherit' }};">{{ $fcfa($sales->credit) }}</div>
                <div style="font-size: 12px; color: var(--color-text-secondary);">pas encore payé</div>
            </div>
            <div class="card" style="padding: 14px 16px;">
                <div style="font-size: 12px; color: var(--color-text-secondary);">Dépenses</div>
                <div style="font-size: 20px; font-weight: 800; color: {{ $breakdown['expenses_total'] > 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $fcfa($breakdown['expenses_total']) }}</div>
                <div style="font-size: 12px; color: var(--color-text-secondary);">payées avec la caisse</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; align-items: start;">
            <div style="display: flex; flex-direction: column; gap: 16px;">

                {{-- 3. Le calcul expliqué --}}
                <div class="card">
                    <h3 style="margin-top: 0;">Comment on arrive à ce montant</h3>

                    <div style="{{ $line }}">
                        <span>Fonds de départ<span style="{{ $hint }}">Espèces mises dans le tiroir à l'ouverture</span></span>
                        <strong>{{ $fcfa($breakdown['opening_amount']) }}</strong>
                    </div>
                    <div style="{{ $line }}">
                        <span>+ Espèces des ventes
                            <span style="{{ $hint }}">{{ $breakdown['payments_count'] }} vente{{ $breakdown['payments_count'] > 1 ? 's' : '' }} payée{{ $breakdown['payments_count'] > 1 ? 's' : '' }} en espèces au comptoir : reçu {{ $fcfa($breakdown['received']) }}, monnaie rendue {{ $fcfa($breakdown['change_given']) }}</span>
                        </span>
                        <strong style="color: var(--color-primary-300);">+{{ $fcfa($breakdown['received'] - $breakdown['change_given']) }}</strong>
                    </div>
                    @if ($breakdown['remittances_count'] > 0 || $outstanding->isNotEmpty())
                        <div style="{{ $line }}">
                            <span>+ Versements des serveuses<span style="{{ $hint }}">{{ $breakdown['remittances_count'] }} {{ $breakdown['remittances_count'] > 1 ? 'versements' : 'versement' }} : l'argent des sacoches remis à la caisse</span></span>
                            <strong style="color: var(--color-primary-300);">+{{ $fcfa($breakdown['remittances_total']) }}</strong>
                        </div>
                    @endif
                    @if ($breakdown['repayments_count'] > 0 || $sales->credit > 0)
                        <div style="{{ $line }}">
                            <span>+ Remboursements de crédits<span style="{{ $hint }}">{{ $breakdown['repayments_count'] }} {{ $breakdown['repayments_count'] > 1 ? 'clients venus payer leur crédit' : 'client venu payer son crédit' }} en espèces</span></span>
                            <strong style="color: var(--color-primary-300);">+{{ $fcfa($breakdown['repayments_total']) }}</strong>
                        </div>
                    @endif
                    <div style="{{ $line }}">
                        <span>− Dépenses<span style="{{ $hint }}">{{ $breakdown['expenses_count'] }} {{ $breakdown['expenses_count'] > 1 ? 'dépenses payées' : 'dépense payée' }} avec l'argent de la caisse</span></span>
                        <strong style="color: var(--color-danger);">−{{ $fcfa($breakdown['expenses_total']) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 14px 0 0 0; font-size: 16px;">
                        <strong>= Doit être dans le tiroir</strong>
                        <strong style="color: var(--color-primary); font-size: 20px;">{{ $fcfa($breakdown['theoretical']) }} FCFA</strong>
                    </div>
                </div>

                {{-- 4. Ce qui n'est pas dans le tiroir --}}
                @if ($breakdown['mobile_total'] > 0 || $sales->credit > 0 || $outstanding->isNotEmpty())
                    <div class="card">
                        <h3 style="margin-top: 0;">Ce qui n'est PAS dans le tiroir</h3>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0;">Ces montants ne doivent pas être cherchés dans la caisse.</p>

                        @if ($breakdown['mobile_total'] > 0)
                            <div style="{{ $line }}">
                                <span>Mobile Money<span style="{{ $hint }}">Arrivé sur les téléphones du maquis : à vérifier avant la clôture</span>
                                    @foreach ($breakdown['mobile_money'] as $method => $amount)
                                        <span style="{{ $hint }}">• {{ \App\Models\Payment::METHODS[$method] ?? $method }} : {{ $fcfa($amount) }}</span>
                                    @endforeach
                                </span>
                                <strong>{{ $fcfa($breakdown['mobile_total']) }}</strong>
                            </div>
                        @endif
                        @if ($sales->credit > 0)
                            <div style="{{ $line }}">
                                <span>Vendu à crédit<span style="{{ $hint }}">Les clients paieront plus tard : voir <a href="{{ route('credits') }}" style="color: var(--color-accent);">Crédits</a></span></span>
                                <strong style="color: var(--color-accent);">{{ $fcfa($sales->credit) }}</strong>
                            </div>
                        @endif
                        @foreach ($outstanding as $server)
                            <div style="{{ $line }}">
                                <span>Sacoche de {{ $server->first_name }}<span style="{{ $hint }}">Encore sur elle : à verser à la caisse</span></span>
                                <strong style="color: var(--color-accent);">{{ $fcfa($server->balance) }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- 5. Journal --}}
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                        <h3 style="margin: 0;">Journal de la caisse <span style="font-weight: 500; font-size: 13px; color: var(--color-text-secondary);">· {{ $journal->count() }} mouvement{{ $journal->count() > 1 ? 's' : '' }}</span></h3>
                        <button wire:click="$toggle('showJournal')" class="btn btn-ghost" style="padding: 5px 12px; font-size: 13px;">{{ $showJournal ? 'Masquer' : 'Afficher' }}</button>
                    </div>

                    @if ($showJournal)
                        @forelse ($journal->take(100) as $row)
                            <div style="display: flex; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--color-border); font-size: 14px; {{ $row->drawer ? '' : 'opacity: 0.75;' }}">
                                <span style="display: flex; gap: 10px;">
                                    <span style="color: var(--color-text-secondary); font-variant-numeric: tabular-nums; min-width: 40px;">{{ $row->at->format('H:i') }}</span>
                                    <span>
                                        {{ $row->label }}
                                        @if (! $row->drawer)
                                            <span style="font-size: 11px; font-weight: 600; padding: 1px 6px; border-radius: 4px; background: var(--color-bg); color: var(--color-text-secondary);">hors tiroir</span>
                                        @endif
                                        <span style="{{ $hint }}">{{ collect([$row->detail, $row->by ? 'par ' . $row->by : null])->filter()->join(' · ') }}</span>
                                    </span>
                                </span>
                                <strong style="white-space: nowrap; color: {{ ! $row->drawer ? 'var(--color-text-secondary)' : ($row->amount < 0 ? 'var(--color-danger)' : 'var(--color-primary-300)') }};">
                                    {{ $row->drawer && $row->amount >= 0 ? '+' : '' }}{{ $row->amount < 0 ? '−' . $fcfa(abs($row->amount)) : $fcfa($row->amount) }}
                                </strong>
                            </div>
                        @empty
                            <p style="font-size: 13px; color: var(--color-text-secondary);">Aucun mouvement depuis l'ouverture.</p>
                        @endforelse
                        @if ($journal->count() > 100)
                            <p style="font-size: 12px; color: var(--color-text-secondary);">Les 100 derniers mouvements sont affichés.</p>
                        @endif
                    @endif
                </div>
            </div>

            {{-- 6. Clôture --}}
            <div class="card">
                @if (! $showCloseForm)
                    <h3 style="margin-top: 0;">Clôture</h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary);">
                        En fin de service, comptez les billets et les pièces du tiroir.
                        L'application compare avec ce qui devrait y être et calcule l'écart.
                    </p>
                    @if ($outstanding->isNotEmpty())
                        <p style="font-size: 13px; color: var(--color-accent); font-weight: 600;">
                            Avant de clôturer, récupérez les sacoches : <a href="{{ route('sacoches') }}" style="color: var(--color-accent);">Recevoir les versements →</a>
                        </p>
                    @endif
                    <button type="button" @click="$wire.set('showCloseForm', true).then(() => $nextTick(() => document.getElementById('compter-tiroir')?.scrollIntoView({ behavior: 'smooth', block: 'start' })))" class="btn btn-danger" style="width: 100%; justify-content: center;">
                        Clôturer la caisse
                    </button>

                    @if ($history->isNotEmpty())
                        <h3 style="margin-top: 24px;">Dernières clôtures</h3>
                        @foreach ($history as $past)
                            <a href="{{ route('cash-register.closed', $past->id) }}" style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 13px; text-decoration: none; color: inherit;">
                                <span>{{ $past->closed_at->format('d/m H:i') }} · {{ $past->closedByUser->first_name ?? '—' }}</span>
                                <strong style="color: {{ $past->discrepancy == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }};">
                                    écart {{ $past->discrepancy > 0 ? '+' : '' }}{{ $fcfa($past->discrepancy) }}
                                </strong>
                            </a>
                        @endforeach
                    @endif
                @else
                    <h3 id="compter-tiroir" style="margin-top: 0; scroll-margin-top: 84px;">Compter le tiroir</h3>

                    @if (! $directEntry)
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-top: 0;">Indiquez combien vous avez de chaque billet et pièce.</p>
                        <div style="display: grid; grid-template-columns: 1fr 70px 80px; gap: 6px 8px; align-items: center; font-size: 13px;">
                            @foreach ($denominations as $key => [$value, $label])
                                <span>{{ $label }}</span>
                                <x-money-input model="counts.{{ $key }}" live placeholder="0" style="padding: 6px 8px; width: 100%;" />
                                <span style="text-align: right; color: var(--color-text-secondary);">{{ $fcfa(max(0, (int) ($counts[$key] ?? 0)) * $value) }}</span>
                            @endforeach
                        </div>
                        @error('counts.*') <div style="color: var(--color-danger); font-size: 13px; margin-top: 8px;">{{ $message }}</div> @enderror
                        <button type="button" wire:click="$set('directEntry', true)" style="background: none; border: none; color: var(--color-text-secondary); font-size: 12px; text-decoration: underline; cursor: pointer; padding: 8px 0;">
                            Saisir seulement le total
                        </button>
                    @else
                        <label style="font-size: 13px; font-weight: 600;">Montant total compté (FCFA)</label>
                        <x-money-input model="countedAmount" live style="width: 100%; margin: 6px 0 8px 0; font-size: 18px;" />
                        <button type="button" wire:click="$set('directEntry', false)" style="background: none; border: none; color: var(--color-text-secondary); font-size: 12px; text-decoration: underline; cursor: pointer; padding: 0 0 8px 0;">
                            Compter billet par billet
                        </button>
                    @endif
                    @error('countedAmount') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 8px;">{{ $message }}</div> @enderror

                    <div style="background: var(--color-bg); border-radius: 10px; padding: 12px; margin: 10px 0 14px 0; font-size: 14px;">
                        <div style="display: flex; justify-content: space-between;"><span>Doit être dans le tiroir</span><strong>{{ $fcfa($breakdown['theoretical']) }}</strong></div>
                        <div style="display: flex; justify-content: space-between;"><span>Compté</span><strong>{{ $countedAmount !== null ? $fcfa($countedAmount) : '—' }}</strong></div>
                        @if ($countedAmount !== null)
                            @php $diff = $countedAmount - $breakdown['theoretical']; @endphp
                            <div style="display: flex; justify-content: space-between; margin-top: 6px; padding-top: 6px; border-top: 1px solid var(--color-border); font-size: 16px;">
                                <span>Écart</span>
                                <strong style="color: {{ $diff == 0 ? 'var(--color-primary-300)' : 'var(--color-danger)' }};">
                                    {{ $diff > 0 ? '+' : '' }}{{ $fcfa($diff) }} FCFA
                                </strong>
                            </div>
                            <div style="font-size: 12px; color: var(--color-text-secondary); margin-top: 4px;">
                                @if ($diff == 0) La caisse est juste.
                                @elseif ($diff < 0) Il manque {{ $fcfa(abs($diff)) }} FCFA dans le tiroir.
                                @else Il y a {{ $fcfa($diff) }} FCFA de trop dans le tiroir.
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($countedAmount !== null && abs($countedAmount - $breakdown['theoretical']) >= $threshold)
                        <label style="font-size: 13px; font-weight: 600;">Raison de l'écart (obligatoire au-delà de {{ $fcfa($threshold) }} FCFA)</label>
                        <textarea wire:model="closingNote" rows="3" style="width: 100%; margin: 6px 0 12px 0; font-family: inherit; font-size: 14px; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--color-border);" placeholder="Ex : erreur de rendu monnaie sur CMD-00012"></textarea>
                    @endif
                    @error('closingNote') <div style="color: var(--color-danger); font-size: 13px; margin-bottom: 12px;">{{ $message }}</div> @enderror

                    <button wire:click="confirmClose" class="btn btn-danger" style="width: 100%; justify-content: center;">
                        Confirmer la clôture
                    </button>
                    <button wire:click="$set('showCloseForm', false)" class="btn btn-ghost" style="width: 100%; margin-top: 8px; justify-content: center;">
                        Annuler
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>
