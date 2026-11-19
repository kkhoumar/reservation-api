{{--
    Politique de confidentialité de Maquis Express.
    PREMIÈRE VERSION, À FAIRE RELIRE PAR UN JURISTE IVOIRIEN AVANT LE LANCEMENT,
    ET À COMPLÉTER AVEC LA RÉFÉRENCE DE LA DÉCLARATION À L'ARTCI.
--}}
@php
    $l = config('maquis.legal');
    $v = fn ($key) => filled($l[$key] ?? null) ? e($l[$key]) : '<em>[à compléter]</em>';
@endphp

<x-layouts.marketing>
<article class="legal">
    <style>
        .legal { max-width: 760px; margin: 0 auto; padding: 32px 20px 64px; line-height: 1.65; font-size: 15px; }
        .legal h1 { font-size: 28px; margin-bottom: 4px; }
        .legal h2 { font-size: 18px; margin: 28px 0 8px; }
        .legal .meta { color: var(--color-text-secondary); font-size: 13px; margin-bottom: 24px; }
        .legal ul { padding-left: 20px; }
        .legal table { width: 100%; border-collapse: collapse; font-size: 14px; margin: 8px 0; }
        .legal th, .legal td { border: 1px solid var(--color-border); padding: 8px 10px; text-align: left; vertical-align: top; }
        @media (max-width: 600px) {
            .legal table { table-layout: fixed; font-size: 13px; }
            .legal th, .legal td { padding: 6px 7px; overflow-wrap: anywhere; hyphens: auto; }
        }
    </style>

    <h1>Politique de confidentialité</h1>
    <div class="meta">Version du {{ \Carbon\Carbon::parse(config('maquis.terms_version'))->format('d/m/Y') }}</div>

    <p>
        Cette page explique quelles informations personnelles Maquis Express enregistre, pourquoi, qui peut les voir,
        combien de temps elles sont gardées et quels sont vos droits, conformément à la loi ivoirienne
        n° 2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel.
    </p>

    <h2>1. Qui est responsable ?</h2>
    <ul>
        <li><strong>Pour les comptes des patrons et les demandes de démonstration</strong> : {!! $v('company') !!}, {!! $v('address') !!} (contact : {!! $v('email') !!}).</li>
        <li><strong>Pour les données que chaque établissement enregistre</strong> (son personnel, ses clients à crédit, ses ventes) : l'établissement lui-même. Maquis Express les traite uniquement pour son compte, pour faire fonctionner le service.</li>
    </ul>
    <p>Déclaration auprès de l'ARTCI : {!! $v('artci') !!}.</p>

    <h2>2. Quelles informations, et pourquoi ?</h2>
    <table>
        <tr><th>Qui</th><th>Informations</th><th>Pourquoi</th></tr>
        <tr><td>Patron et personnel</td><td>Prénom, nom, numéro de téléphone, rôle, mot de passe (chiffré, illisible même pour nous), dates de connexion, photo de profil si la personne en ajoute une</td><td>Se connecter, reconnaître les membres de l'équipe, savoir qui a fait quoi (vente, encaissement, dépense), sécuriser l'accès</td></tr>
        <tr><td>Journal des actions</td><td>Nom de la personne, action faite (connexion, bon annulé, remise, changement de prix…), date, heure et adresse IP</td><td>Permettre au patron et à la gérante de savoir qui a fait quoi. Ce journal ne peut être ni modifié ni effacé dans l'application</td></tr>
        <tr><td>Clients à crédit</td><td>Nom, numéro de téléphone (facultatif), plafond, consommations et remboursements</td><td>Suivre ce que le client doit, lui envoyer un rappel s'il le faut</td></tr>
        <tr><td>Personnes qui demandent une démo</td><td>Nom, nom du maquis, téléphone, commune, message</td><td>Les rappeler pour présenter le service</td></tr>
        <tr><td>Toutes les connexions</td><td>Adresse IP, journaux techniques</td><td>Sécurité : bloquer les tentatives de piratage, comprendre une panne</td></tr>
    </table>
    <p>Nous n'enregistrons <strong>pas</strong> de numéro de carte bancaire, ni de code Mobile Money. Les paiements Mobile Money se font directement entre le client et l'établissement ; seule une éventuelle référence de transaction est notée.</p>

    <h2>3. Qui peut voir ces informations ?</h2>
    <ul>
        <li>Dans un établissement, chacun ne voit que ce que son rôle permet (serveuse, caissière, gérante, patron). <strong>Un établissement ne voit jamais les données d'un autre.</strong></li>
        <li>L'équipe Maquis Express peut y accéder pour le support, la maintenance ou la sécurité, uniquement quand c'est nécessaire.</li>
        <li>Nos prestataires techniques, uniquement pour faire fonctionner le service : l'hébergeur des serveurs ({!! $v('host') !!}) et, si l'établissement l'utilise, WhatsApp (Meta) pour l'envoi du rapport du soir au patron.</li>
        <li>Nous ne vendons jamais ces informations et ne les utilisons pas pour de la publicité.</li>
    </ul>

    <h2>4. Combien de temps ?</h2>
    <ul>
        <li>Pendant toute la durée de l'abonnement de l'établissement.</li>
        <li>Après la fin de l'abonnement : 12 mois, pour permettre une reprise ou une copie, puis suppression. Certaines informations peuvent être gardées plus longtemps si la loi l'impose.</li>
        <li>Une personne qui quitte un établissement n'a plus accès à l'application ; son nom reste attaché aux ventes qu'elle a faites, pour que les comptes restent justes.</li>
        <li>Demandes de démonstration sans suite : 12 mois.</li>
    </ul>

    <h2>5. Comment les informations sont protégées</h2>
    <ul>
        <li>Connexion chiffrée (HTTPS) et mots de passe chiffrés.</li>
        <li>Séparation stricte des données de chaque établissement, et droits d'accès selon le rôle.</li>
        <li>Blocage après plusieurs mots de passe faux, mot de passe provisoire à changer à la première connexion.</li>
        <li>Sauvegardes régulières.</li>
    </ul>

    <h2>6. Vos droits</h2>
    <p>Vous pouvez demander à <strong>consulter</strong>, <strong>corriger</strong> ou <strong>faire supprimer</strong> les informations qui vous concernent, ou vous <strong>opposer</strong> à leur utilisation pour un motif légitime :</p>
    <ul>
        <li>si vous êtes employé ou client d'un établissement : adressez-vous d'abord à l'établissement (le patron) ;</li>
        <li>dans tous les cas, vous pouvez nous écrire : {!! $v('email') !!}.</li>
    </ul>
    <p>Nous répondons dans un délai d'un mois. Si vous n'êtes pas satisfait de la réponse, vous pouvez saisir l'<strong>ARTCI</strong> (Autorité de Régulation des Télécommunications/TIC de Côte d'Ivoire).</p>

    <h2>7. Cookies</h2>
    <p>Nous utilisons seulement les cookies nécessaires pour vous garder connecté et sécuriser les formulaires. Aucun cookie publicitaire ni de suivi.</p>

    <h2>8. Modifications</h2>
    <p>Cette politique peut évoluer. La date de version en haut de la page indique la dernière mise à jour ; les patrons sont invités à accepter la nouvelle version à leur prochaine connexion.</p>
</article>
</x-layouts.marketing>
