<div class="lp">
    <style>
        .lp section { padding: 56px 0; border-top: 1px solid var(--color-border); scroll-margin-top: 72px; }
        .lp .eyebrow { display: inline-block; background: rgba(193,84,30,0.1); color: var(--color-accent); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; letter-spacing: 0.02em; }
        .lp h2 { font-size: 30px; font-weight: 800; text-align: center; margin: 12px 0 8px; line-height: 1.2; }
        .lp .lead { text-align: center; color: var(--color-text-secondary); font-size: 16px; margin: 0 auto 36px; max-width: 620px; line-height: 1.6; }
        .lp .center { text-align: center; }
        .lp .card { background: white; border: 1px solid var(--color-border); border-radius: 16px; padding: 24px; }
        .lp .icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 14px; font-size: 20px; }
        .lp .card h3 { font-size: 17px; margin: 0 0 6px; }
        .lp .card p { font-size: 14px; color: var(--color-text-secondary); line-height: 1.55; margin: 0; }
        .lp .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .lp .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .lp .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }

        /* Hero */
        .lp .hero { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 48px; align-items: center; padding: 56px 0 48px; }
        .lp .hero h1 { font-size: 46px; font-weight: 900; line-height: 1.08; margin: 18px 0; letter-spacing: -0.03em; }
        .lp .hero h1 em { font-style: normal; color: var(--color-accent); }
        .lp .hero p { font-size: 17px; color: var(--color-text-secondary); line-height: 1.6; margin: 0 0 28px; max-width: 500px; }
        .lp .ctas { display: flex; gap: 12px; flex-wrap: wrap; }
        .lp .trust { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 26px; font-size: 13px; color: var(--color-text-secondary); }
        .lp .trust span::before { content: '✓ '; color: var(--color-primary-300); font-weight: 800; }

        /* Téléphone : rapport WhatsApp */
        .lp .phone { width: 300px; margin: 0 auto; background: #111; border-radius: 34px; padding: 12px; box-shadow: 0 40px 80px rgba(11,77,60,0.28); }
        .lp .phone-screen { background: #E5DDD5; border-radius: 24px; overflow: hidden; }
        .lp .wa-head { background: #075E54; color: white; padding: 12px 14px; font-size: 13px; display: flex; gap: 10px; align-items: center; }
        .lp .wa-head .av { width: 30px; height: 30px; border-radius: 50%; background: var(--color-accent); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 11px; }
        .lp .wa-body { padding: 14px 10px 18px; }
        .lp .bubble { background: white; border-radius: 10px 10px 10px 2px; padding: 10px 12px; font-size: 12.5px; line-height: 1.55; box-shadow: 0 1px 1px rgba(0,0,0,0.08); }
        .lp .bubble .time { text-align: right; font-size: 10px; color: #999; margin-top: 4px; }

        /* Pour qui */
        .lp .role { border-top: 4px solid var(--color-primary); }
        .lp .role .who { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-accent); margin-bottom: 6px; }
        .lp .role ul { padding-left: 18px; margin: 8px 0 0; font-size: 14px; color: var(--color-text-secondary); line-height: 1.7; }

        /* Étapes */
        .lp .step-n { width: 44px; height: 44px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 17px; margin: 0 auto 14px; }

        /* Tarifs */
        .lp .plan { position: relative; text-align: left; display: flex; flex-direction: column; }
        .lp .plan.featured { border: 2px solid var(--color-accent); box-shadow: 0 20px 40px rgba(193,84,30,0.12); }
        .lp .plan .badge-pop { position: absolute; top: -12px; left: 24px; background: var(--color-accent); color: white; font-size: 11px; font-weight: 800; padding: 4px 12px; border-radius: 999px; }
        .lp .plan .price { font-size: 34px; font-weight: 900; margin: 6px 0 2px; }
        .lp .plan .price small { font-size: 14px; font-weight: 600; color: var(--color-text-secondary); }
        .lp .plan ul { list-style: none; padding: 0; margin: 16px 0 22px; font-size: 14px; line-height: 1.9; flex: 1; }
        .lp .plan li::before { content: '✓  '; color: var(--color-primary-300); font-weight: 800; }

        /* FAQ */
        .lp details { background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 16px 18px; margin-bottom: 10px; }
        .lp summary { font-weight: 700; cursor: pointer; font-size: 15px; }
        .lp details p { color: var(--color-text-secondary); font-size: 14px; line-height: 1.6; margin: 10px 0 0; }

        .lp .final { background: var(--color-primary); border-radius: 24px; padding: 52px 32px; text-align: center; margin: 48px 0 24px; color: white; }
        .lp .final h2 { color: white; }

        @media (max-width: 900px) {
            .lp .hero { grid-template-columns: 1fr; gap: 36px; padding-top: 32px; }
            .lp .hero h1 { font-size: 34px; }
            .lp .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .lp .grid-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .lp .grid-2, .lp .grid-4 { grid-template-columns: 1fr; }
            .lp h2 { font-size: 24px; }
            .lp .hero h1 { font-size: 30px; }
            .lp .phone { width: 100%; max-width: 300px; }
        }
    </style>

    {{-- ================= HERO ================= --}}
    <div class="hero">
        <div>
            <span class="eyebrow">Fait pour les maquis, bars et restaurants de Côte d'Ivoire</span>
            <h1>Votre maquis tourne. <em>Votre argent rentre.</em> Même quand vous n'êtes pas là.</h1>
            <p>
                Commandes, comptoir, caisse, stock et crédits clients sur un simple téléphone.
                Espèces, Wave et Orange Money. Et chaque soir, le rapport de la journée arrive sur votre WhatsApp.
            </p>
            <div class="ctas">
                <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo gratuite</a>
                <a href="#fonctionnalites" class="btn-secondary-outline">Voir ce que ça fait</a>
            </div>
            <div class="trust">
                <span>{{ $trialDays }} jours d'essai gratuit</span>
                <span>Installation avec notre équipe</span>
                <span>Sur téléphone, tablette ou ordinateur</span>
            </div>
        </div>

        <div>
            <div class="phone" aria-label="Exemple de rapport du soir reçu sur WhatsApp">
                <div class="phone-screen">
                    <div class="wa-head"><div class="av">ME</div><div><strong>Maquis Express</strong><br><span style="opacity: .8; font-size: 11px;">rapport du soir</span></div></div>
                    <div class="wa-body">
                        <div class="bubble">
                            <strong>Maquis Chez Koffi</strong> — rapport du samedi<br>
                            Caisse 18:02 → 23:47, clôturée par Jean<br><br>
                            <strong>Ventes : 186 500 FCFA</strong> (64 bons)<br>
                            • Espèces : 121 000<br>
                            • Wave : 48 500<br>
                            • À crédit : 17 000<br>
                            • Dépenses : 9 000<br><br>
                            <strong>Caisse</strong><br>
                            • Écart : 0 ✅<br><br>
                            <strong>Par serveuse</strong><br>
                            • Aya : 23 bons · 71 000<br>
                            • Awa : 19 bons · 58 500<br><br>
                            <strong>À surveiller</strong><br>
                            • Offerts et remises : 3 000 F
                            <div class="time">23:48 ✓✓</div>
                        </div>
                    </div>
                </div>
            </div>
            <p class="center" style="font-size: 13px; color: var(--color-text-secondary); margin-top: 14px;">Exemple de rapport reçu par le patron à la fermeture</p>
        </div>
    </div>

    {{-- ================= DOULEURS ================= --}}
    <section>
        <div class="center"><span class="eyebrow">Ça vous parle ?</span></div>
        <h2>Ce qui fait perdre de l'argent à un maquis</h2>
        <p class="lead">Ce n'est pas un manque de clients. C'est tout ce qui se perd entre le frigo, la table et la caisse.</p>
        <div class="grid-4">
            <div class="card">
                <div class="icon" style="background: rgba(217,59,59,0.1);">🍺</div>
                <h3>Des boissons servies, jamais payées</h3>
                <p>Une tournée oubliée, une bouteille « offerte » aux amis… et le soir, le compte n'y est pas.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(217,59,59,0.1);">💸</div>
                <h3>Une caisse qui ne tombe jamais juste</h3>
                <p>Il manque 5 000 F, et personne ne sait d'où vient l'écart.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(217,59,59,0.1);">📒</div>
                <h3>Des crédits oubliés</h3>
                <p>« Mets ça sur mon compte » : trois semaines après, plus personne ne sait combien il doit.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(217,59,59,0.1);">📦</div>
                <h3>Un stock qui fond tout seul</h3>
                <p>Les casiers arrivent, les bouteilles disparaissent, et le bénéfice reste un mystère.</p>
            </div>
        </div>
    </section>

    {{-- ================= FONCTIONNALITÉS ================= --}}
    <section id="fonctionnalites">
        <div class="center"><span class="eyebrow">La solution</span></div>
        <h2>Tout votre maquis, au même endroit</h2>
        <p class="lead">Pensé pour la façon dont on travaille vraiment dans un maquis : la tournée, le comptoir, les habitués, le Mobile Money.</p>
        <div class="grid-3">
            <div class="card">
                <div class="icon" style="background: rgba(193,84,30,0.1);">🧾</div>
                <h3>Bon de comptoir</h3>
                <p>La serveuse envoie la commande au comptoir depuis son téléphone. On encaisse, puis on sert : <strong>pas d'argent, pas de boisson</strong>.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(11,77,60,0.08);">💰</div>
                <h3>Une caisse qui tombe juste</h3>
                <p>Chaque mouvement est noté. À la fermeture, on compte les billets et l'application dit tout de suite s'il manque quelque chose.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(94,191,160,0.18);">📱</div>
                <h3>Espèces et Mobile Money</h3>
                <p>Wave, Orange Money, MTN, Moov, ou une partie en espèces : chaque paiement est enregistré, avec la monnaie à rendre.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(193,84,30,0.1);">📒</div>
                <h3>Crédits clients</h3>
                <p>Ce que chaque habitué doit, ce qu'il a consommé et quand. Plafond par client, et relance polie sur WhatsApp en un clic.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(11,77,60,0.08);">📦</div>
                <h3>Stock surveillé</h3>
                <p>Livraisons, casse et inventaire du frigo : l'application vous dit ce qui manque, en bouteilles et en francs.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(94,191,160,0.18);">📊</div>
                <h3>Votre vrai bénéfice</h3>
                <p>Ventes, prix d'achat, dépenses, pertes : vous savez enfin combien vous gagnez, jour par jour.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(193,84,30,0.1);">💬</div>
                <h3>Le rapport du soir sur WhatsApp</h3>
                <p>À la fermeture, vous recevez les ventes, la caisse, chaque serveuse et ce qu'il faut surveiller. Même à distance.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(11,77,60,0.08);">🎁</div>
                <h3>Offerts et remises tracés</h3>
                <p>Seuls le patron et la gérante peuvent offrir une boisson, avec un motif. Plus de « c'était offert » invérifiable.</p>
            </div>
            <div class="card">
                <div class="icon" style="background: rgba(94,191,160,0.18);">📥</div>
                <h3>Vos chiffres dans Excel</h3>
                <p>Ventes, dépenses, caisse, crédits, stock : téléchargez tout pour vous ou pour votre comptable.</p>
            </div>
        </div>
    </section>

    {{-- ================= POUR QUI ================= --}}
    <section>
        <div class="center"><span class="eyebrow">Chacun son écran</span></div>
        <h2>Simple pour l'équipe, clair pour le patron</h2>
        <p class="lead">Chacun ne voit que ce dont il a besoin. Et si vous êtes seul à tout faire, l'application s'adapte.</p>
        <div class="grid-4">
            <div class="card role">
                <div class="who">La serveuse</div>
                <h3>Prend la commande</h3>
                <ul><li>Choisit la table</li><li>Ajoute les boissons</li><li>Envoie au comptoir</li></ul>
            </div>
            <div class="card role">
                <div class="who">Le comptoir</div>
                <h3>Encaisse et sert</h3>
                <ul><li>Voit les bons arriver</li><li>Espèces ou Mobile Money</li><li>Clôture la caisse</li></ul>
            </div>
            <div class="card role">
                <div class="who">La gérante</div>
                <h3>Organise</h3>
                <ul><li>Crédits et offerts</li><li>Stock et livraisons</li><li>Équipe et dépenses</li></ul>
            </div>
            <div class="card role">
                <div class="who">Le patron</div>
                <h3>Contrôle</h3>
                <ul><li>Tableau de bord et bénéfice</li><li>Rapport du soir sur WhatsApp</li><li>Exports Excel</li></ul>
            </div>
        </div>
    </section>

    {{-- ================= COMMENT ÇA MARCHE ================= --}}
    <section id="comment-ca-marche">
        <div class="center"><span class="eyebrow">Démarrer</span></div>
        <h2>On installe votre maquis avec vous</h2>
        <p class="lead">Pas besoin d'être à l'aise avec l'informatique : notre équipe s'occupe de la mise en route.</p>
        <div class="grid-4 center">
            <div>
                <div class="step-n">1</div>
                <h3 style="font-size: 16px; margin: 0 0 6px;">Demandez une démo</h3>
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">Laissez votre nom et votre numéro : on vous appelle.</p>
            </div>
            <div>
                <div class="step-n">2</div>
                <h3 style="font-size: 16px; margin: 0 0 6px;">On prépare votre maquis</h3>
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">Votre compte est créé, puis on saisit ensemble votre menu, vos tables et votre équipe.</p>
            </div>
            <div>
                <div class="step-n">3</div>
                <h3 style="font-size: 16px; margin: 0 0 6px;">Essayez gratuitement</h3>
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">{{ $trialDays }} jours pour l'utiliser en vrai, pendant le service.</p>
            </div>
            <div>
                <div class="step-n">4</div>
                <h3 style="font-size: 16px; margin: 0 0 6px;">Choisissez votre formule</h3>
                <p style="font-size: 14px; color: var(--color-text-secondary); margin: 0;">Vous payez par Wave ou Orange Money, au mois ou plus.</p>
            </div>
        </div>
    </section>

    {{-- ================= TARIFS ================= --}}
    @if ($plans->isNotEmpty())
        <section id="tarifs">
            <div class="center"><span class="eyebrow">Tarifs</span></div>
            <h2>Un prix clair, selon la taille de votre équipe</h2>
            <p class="lead">Toutes les fonctionnalités dans chaque formule. Seul le nombre de personnes change. {{ $trialDays }} jours d'essai gratuit pour commencer.</p>
            <div class="grid-{{ min(3, $plans->count()) }}" style="align-items: stretch;">
                @foreach ($plans as $i => $plan)
                    @php $featured = $plans->count() >= 3 && $i === 1; @endphp
                    <div class="card plan {{ $featured ? 'featured' : '' }}">
                        @if ($featured) <span class="badge-pop">RECOMMANDÉ</span> @endif
                        <h3 style="font-size: 20px;">{{ $plan->name }}</h3>
                        <div class="price">{{ number_format($plan->monthly_price, 0, ',', ' ') }} <small>FCFA / mois</small></div>
                        <div style="font-size: 14px; color: var(--color-text-secondary);">{{ $plan->description }}</div>
                        <ul>
                            <li>{{ $plan->usersLabel() }}</li>
                            <li>Commandes, comptoir et caisse</li>
                            <li>Espèces et Mobile Money</li>
                            <li>Crédits clients et stock</li>
                            <li>Tableau de bord et exports</li>
                        </ul>
                        <a href="{{ route('demo.request') }}" class="{{ $featured ? 'btn-primary' : 'btn-secondary-outline' }}" style="text-align: center;">Demander une démo</a>
                    </div>
                @endforeach
            </div>
            <p class="center" style="font-size: 13px; color: var(--color-text-secondary); margin-top: 18px;">
                Paiement d'avance par Mobile Money, sans engagement : vous arrêtez quand vous voulez.
            </p>
        </section>
    @endif

    {{-- ================= FAQ ================= --}}
    <section id="questions">
        <h2>Vos questions</h2>
        <p class="lead">Ce que les patrons nous demandent le plus souvent.</p>
        <div style="max-width: 760px; margin: 0 auto;">
            <details>
                <summary>Je dois acheter du matériel ?</summary>
                <p>Non. L'application marche sur les téléphones Android que votre équipe a déjà, sur une tablette ou sur un ordinateur. Il suffit d'un navigateur internet.</p>
            </details>
            <details>
                <summary>Et s'il n'y a pas internet ?</summary>
                <p>L'application a besoin d'une connexion pendant le service : une connexion mobile (3G ou 4G) suffit.</p>
            </details>
            <details>
                <summary>Mon petit maquis, c'est juste moi. Ça marche quand même ?</summary>
                <p>Oui. Si vous êtes seul à tout faire, l'écran s'adapte : vous prenez la commande, vous encaissez, vous servez. Vous ajoutez du personnel quand vous voulez.</p>
            </details>
            <details>
                <summary>Mes serveuses vont-elles s'en sortir ?</summary>
                <p>Oui : choisir la table, toucher les boissons, envoyer. On forme votre équipe à l'installation.</p>
            </details>
            <details>
                <summary>Mes chiffres sont-ils protégés ?</summary>
                <p>Chaque maquis ne voit que ses propres données, chacun dans l'équipe n'accède qu'à ce que son rôle permet, et les mots de passe sont chiffrés. Voir notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p>
            </details>
            <details>
                <summary>Et si je ne paie pas à temps ?</summary>
                <p>Vous êtes prévenu à l'avance, puis vous avez quelques jours de tolérance. Ensuite, votre maquis passe en lecture seule : vous voyez toujours vos chiffres, rien n'est supprimé, et tout revient dès le paiement.</p>
            </details>
        </div>
    </section>

    {{-- ================= APPEL FINAL ================= --}}
    <div class="final">
        <h2>Prêt à savoir où passe votre argent ?</h2>
        <p style="font-size: 16px; color: rgba(255,255,255,0.8); margin: 0 auto 26px; max-width: 520px;">
            Demandez une démo : on vous appelle, on installe votre maquis avec vous, et vous essayez {{ $trialDays }} jours gratuitement.
        </p>
        <div class="ctas" style="justify-content: center;">
            <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo gratuite</a>
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-secondary-outline" style="color: white; border-color: rgba(255,255,255,0.4);">Nous écrire sur WhatsApp</a>
            @endif
        </div>
    </div>
</div>
