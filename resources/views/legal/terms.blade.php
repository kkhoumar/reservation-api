{{--
    Conditions d'utilisation de Maquis Express.
    PREMIÈRE VERSION, À FAIRE RELIRE PAR UN JURISTE IVOIRIEN AVANT LE LANCEMENT.
    Après toute modification : changer config('maquis.terms_version') pour que
    les patrons acceptent de nouveau.
--}}
@php
    $l = config('maquis.legal');
    $v = fn ($key) => filled($l[$key] ?? null) ? e($l[$key]) : '<em>[à compléter]</em>';
    $grace = \App\Models\Restaurant::GRACE_DAYS;
    $hour = \App\Models\Restaurant::LOCK_HOUR;
@endphp

<x-layouts.marketing>
<article class="legal">
    <style>
        .legal { max-width: 760px; margin: 0 auto; padding: 32px 20px 64px; line-height: 1.65; font-size: 15px; }
        .legal h1 { font-size: 28px; margin-bottom: 4px; }
        .legal h2 { font-size: 18px; margin: 28px 0 8px; }
        .legal .meta { color: var(--color-text-secondary); font-size: 13px; margin-bottom: 24px; }
        .legal ul { padding-left: 20px; }
    </style>

    <h1>Conditions d'utilisation</h1>
    <div class="meta">Version du {{ \Carbon\Carbon::parse(config('maquis.terms_version'))->format('d/m/Y') }}</div>

    <h2>1. Qui sommes-nous ?</h2>
    <p>
        Maquis Express est un service en ligne de gestion pour maquis, bars et restaurants, édité par
        {!! $v('company') !!} ({!! $v('form') !!}, RCCM {!! $v('rccm') !!}), dont le siège est situé {!! $v('address') !!}.
        Contact : {!! $v('email') !!} · {!! $v('phone') !!}.
        Dans ce document, « nous » désigne l'éditeur, et « le Client » désigne l'établissement qui utilise le service, représenté par son responsable (le « patron »).
    </p>

    <h2>2. Ce que fait le service</h2>
    <p>Maquis Express permet notamment de : prendre les commandes, encaisser (espèces et Mobile Money), suivre la caisse, les dépenses, le stock, les crédits accordés aux clients, les sacoches des serveuses, et d'envoyer un rapport du soir au patron.</p>
    <p>Le service est un <strong>outil d'aide à la gestion</strong>. Il ne remplace pas la comptabilité officielle de l'établissement ni ses obligations fiscales et sociales, qui restent à la charge du Client.</p>

    <h2>3. Ouverture du compte</h2>
    <ul>
        <li>Le compte de l'établissement et celui du patron sont créés par Maquis Express, après une demande de démonstration.</li>
        <li>Le patron reçoit un mot de passe provisoire qu'il doit changer à sa première connexion, puis il accepte les présentes conditions pour son établissement.</li>
        <li>Le patron crée et gère lui-même les comptes de son personnel. Il est responsable de l'usage qui en est fait et doit retirer l'accès d'une personne qui quitte l'établissement.</li>
        <li>Les identifiants sont personnels et confidentiels. Toute action faite avec un compte est réputée faite par son titulaire.</li>
    </ul>

    <h2>4. Essai gratuit, formules et prix</h2>
    <ul>
        <li>Une période d'essai gratuite peut être accordée. Sa date de fin est affichée dans l'application.</li>
        <li>Après l'essai, le service est payant selon la formule choisie (Basic, Pro, Premium…). Le prix mensuel en FCFA et le nombre d'utilisateurs autorisés sont indiqués dans l'application (Paramètres → Mon abonnement).</li>
        <li>L'abonnement se paie <strong>à l'avance</strong>, pour 1, 3, 6 ou 12 mois, par Mobile Money ou tout autre moyen indiqué, en précisant la référence de l'établissement. L'accès est prolongé dès réception du paiement.</li>
        <li>Nous pouvons modifier nos prix. Un nouveau prix ne s'applique qu'aux paiements suivants, jamais à une période déjà payée.</li>
        <li>Sauf erreur de notre part, les sommes payées pour une période commencée ne sont pas remboursées.</li>
    </ul>

    <h2>5. Retard de paiement</h2>
    <ul>
        <li>Un rappel s'affiche dans l'application quelques jours avant la fin de la période payée.</li>
        <li>Après cette date, le Client dispose de <strong>{{ $grace }} jours de grâce</strong>. Passé ce délai, l'accès est suspendu, le matin à {{ $hour }} h, pour ne pas interrompre un service en cours.</li>
        <li>La suspension ne supprime aucune donnée. L'accès est rétabli dès le paiement.</li>
    </ul>

    <h2>6. Nos engagements</h2>
    <ul>
        <li>Nous faisons tout notre possible pour que le service soit disponible et fonctionne correctement, et pour protéger les données (voir la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>).</li>
        <li>Le service peut être interrompu pour maintenance, si possible en dehors des heures de service et après information.</li>
        <li>Nous ne pouvons pas garantir une disponibilité totale et sans erreur : il s'agit d'une obligation de moyens.</li>
    </ul>

    <h2>7. Les engagements du Client</h2>
    <ul>
        <li>Disposer du matériel (téléphone, tablette, ordinateur) et de la connexion internet nécessaires, à ses frais.</li>
        <li>Saisir des informations exactes, vérifier sa caisse, et contrôler les paiements Mobile Money reçus sur ses propres téléphones.</li>
        <li>Utiliser le service de façon licite, et informer son personnel et ses clients à crédit que leurs informations sont enregistrées (voir la politique de confidentialité).</li>
        <li>Ne pas tenter d'accéder aux données d'un autre établissement ni de perturber le service.</li>
    </ul>

    <h2>8. Responsabilité</h2>
    <ul>
        <li>Le Client reste seul responsable de la gestion de son établissement, de son argent, de son stock et de son personnel. Nous ne sommes pas responsables des vols, des erreurs de saisie, des erreurs de rendu de monnaie, ni des décisions prises à partir des chiffres de l'application.</li>
        <li>Nous ne sommes pas responsables des coupures d'internet ou d'électricité chez le Client, ni des services de tiers (opérateurs Mobile Money, WhatsApp…).</li>
        <li>Si notre responsabilité était engagée, elle serait limitée au montant payé par le Client pour les trois derniers mois d'abonnement.</li>
    </ul>

    <h2>9. Données de l'établissement</h2>
    <p>
        Les données saisies dans l'application (ventes, caisse, dépenses, crédits, personnel…) appartiennent au Client.
        Nous les traitons uniquement pour fournir le service, selon la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.
        Le Client peut demander une copie de ses données.
    </p>

    <h2>10. Fin de l'abonnement</h2>
    <ul>
        <li>Le Client peut arrêter à tout moment en ne renouvelant pas son abonnement.</li>
        <li>Nous pouvons suspendre ou fermer un compte en cas de non-paiement ou d'usage frauduleux ou contraire aux présentes conditions.</li>
        <li>Après la fin de l'abonnement, les données sont conservées pendant la durée indiquée dans la politique de confidentialité, puis supprimées.</li>
    </ul>

    <h2>11. Propriété du service</h2>
    <p>L'application, son nom, son logo et son code restent notre propriété. Le Client reçoit un droit d'utilisation personnel, pour son établissement, pendant la durée de son abonnement.</p>

    <h2>12. Modification des conditions</h2>
    <p>Nous pouvons faire évoluer ces conditions. Le patron est alors invité à accepter la nouvelle version à sa prochaine connexion. S'il la refuse, il peut ne pas renouveler son abonnement.</p>

    <h2>13. Droit applicable et litiges</h2>
    <p>
        Ces conditions sont soumises au droit ivoirien, notamment à la loi n° 2013-546 du 30 juillet 2013 relative aux transactions électroniques.
        En cas de désaccord, nous chercherons d'abord une solution à l'amiable. À défaut, les tribunaux compétents d'Abidjan seront saisis.
    </p>
</article>
</x-layouts.marketing>
