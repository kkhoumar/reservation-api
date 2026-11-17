<div>

    {{-- HERO --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 48px; align-items: center; padding: 56px 0 40px 0;">
        <div>
            <div style="display: inline-block; background: rgba(193,84,30,0.1); color: var(--color-accent); font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; margin-bottom: 20px;">
                Fait pour les maquis de Côte d'Ivoire
            </div>
            <h1 style="font-size: 40px; font-weight: 800; line-height: 1.15; margin: 0 0 18px 0;">
                Arrêtez de gérer votre maquis au cahier.
            </h1>
            <p style="font-size: 16px; color: var(--color-text-secondary); line-height: 1.6; margin: 0 0 28px 0; max-width: 440px;">
                Commandes, tables, caisse et dépenses réunis dans une seule application simple — pour un gérant qui n'a pas de temps à perdre.
            </p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo</a>
                <a href="#comment-ca-marche" class="btn-secondary-outline">Voir comment ça marche</a>
            </div>
            <div style="margin-top: 28px; font-size: 13px; color: var(--color-text-secondary);">
                14 jours d'essai gratuit · Sans carte bancaire
            </div>
        </div>

        {{-- Aperçu du dashboard --}}
        <div style="background: var(--color-primary); border-radius: 16px; padding: 20px; box-shadow: 0 30px 60px rgba(11,77,60,0.25);">
            <div style="background: white; border-radius: 12px; padding: 18px;">
                <div style="font-size: 11px; color: var(--color-text-secondary); margin-bottom: 4px;">Chiffre d'affaires aujourd'hui</div>
                <div style="font-size: 26px; font-weight: 800; margin-bottom: 14px;">125 500 <span style="font-size: 14px; color: var(--color-text-secondary);">FCFA</span></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;">
                    <div style="background: var(--color-bg); border-radius: 8px; padding: 10px;">
                        <div style="font-size: 10px; color: var(--color-text-secondary);">Commandes</div>
                        <div style="font-size: 17px; font-weight: 700;">32</div>
                    </div>
                    <div style="background: var(--color-bg); border-radius: 8px; padding: 10px;">
                        <div style="font-size: 10px; color: var(--color-text-secondary);">Tables occupées</div>
                        <div style="font-size: 17px; font-weight: 700;">8 / 12</div>
                    </div>
                </div>

                <div style="font-size: 11px; color: var(--color-text-secondary); margin-bottom: 8px;">Dernières ventes</div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 13px;">
                    <span>Table 5 · 3 articles</span>
                    <strong style="color: var(--color-accent);">11 000 F</strong>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-top: 1px solid var(--color-border); font-size: 13px;">
                    <span>À emporter</span>
                    <strong style="color: var(--color-accent);">8 000 F</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- PREUVE SOCIALE --}}
    <div style="text-align: center; padding: 20px 0 40px 0; border-top: 1px solid var(--color-border);">
        <p style="font-size: 13px; color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600;">
            Conçu avec des gérants de maquis à Abidjan
        </p>
    </div>

    {{-- DOULEUR --}}
    <div style="padding: 40px 0; border-top: 1px solid var(--color-border);">
        <h2 style="font-size: 24px; font-weight: 800; text-align: center; margin: 0 0 36px 0;">
            Ce que vous vivez chaque semaine
        </h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(217,59,59,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D93B3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Commandes perdues</div>
                <div style="font-size: 13px; color: var(--color-text-secondary); line-height: 1.5;">
                    Prises à l'oral, mal transmises entre le serveur et la cuisine.
                </div>
            </div>
            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(217,59,59,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D93B3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Écarts de caisse</div>
                <div style="font-size: 13px; color: var(--color-text-secondary); line-height: 1.5;">
                    En fin de journée, sans jamais savoir vraiment d'où ça vient.
                </div>
            </div>
            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 22px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(217,59,59,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#D93B3B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Bénéfice flou</div>
                <div style="font-size: 13px; color: var(--color-text-secondary); line-height: 1.5;">
                    Une impression de gagner, sans jamais un vrai chiffre en fin de semaine.
                </div>
            </div>
        </div>
    </div>

    {{-- FONCTIONNALITÉS --}}
    <div id="fonctionnalites" style="padding: 40px 0; border-top: 1px solid var(--color-border); scroll-margin-top: 80px;">
        <h2 style="font-size: 24px; font-weight: 800; text-align: center; margin: 0 0 8px 0;">
            Tout ce dont votre équipe a besoin
        </h2>
        <p style="text-align: center; color: var(--color-text-secondary); font-size: 14px; margin: 0 0 36px 0;">
            Une application, trois métiers : serveur, caissier, gérant.
        </p>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 24px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(193,84,30,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#C1541E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="9" x2="9" y2="21"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 16px; margin-bottom: 6px;">Tables & commandes</div>
                <div style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.5;">
                    Plan des tables en temps réel, prise de commande en quelques clics, suivi du statut jusqu'au service.
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 24px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(11,77,60,0.08); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#0B4D3C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 3v4M8 3v4"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 16px; margin-bottom: 6px;">Caisse avec écart tracé</div>
                <div style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.5;">
                    Ouverture, clôture, calcul automatique du solde et justification obligatoire des écarts.
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 24px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(94,191,160,0.15); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#146652" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 16px; margin-bottom: 6px;">Dashboard en direct</div>
                <div style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.5;">
                    Chiffre d'affaires, dépenses et bénéfice estimé, mis à jour à chaque vente.
                </div>
            </div>

            <div style="background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 24px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(193,84,30,0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                    <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#C1541E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div style="font-weight: 700; font-size: 16px; margin-bottom: 6px;">Rôles séparés</div>
                <div style="font-size: 14px; color: var(--color-text-secondary); line-height: 1.5;">
                    Serveur, caissier, gérant — chacun accède seulement à ce qui le concerne.
                </div>
            </div>
        </div>
    </div>

    {{-- COMMENT ÇA MARCHE --}}
    <div id="comment-ca-marche" style="padding: 40px 0; border-top: 1px solid var(--color-border); scroll-margin-top: 80px;">
        <h2 style="font-size: 24px; font-weight: 800; text-align: center; margin: 0 0 36px 0;">
            Opérationnel en 5 minutes
        </h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; margin: 0 auto 14px auto;">1</div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Créez votre établissement</div>
                <div style="font-size: 13px; color: var(--color-text-secondary);">Nom, tables, menu — configuré en quelques minutes.</div>
            </div>
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; margin: 0 auto 14px auto;">2</div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Ajoutez votre équipe</div>
                <div style="font-size: 13px; color: var(--color-text-secondary);">Serveurs et caissiers, chacun avec son propre accès.</div>
            </div>
            <div style="text-align: center;">
                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--color-primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; margin: 0 auto 14px auto;">3</div>
                <div style="font-weight: 700; font-size: 15px; margin-bottom: 6px;">Encaissez et pilotez</div>
                <div style="font-size: 13px; color: var(--color-text-secondary);">Suivez votre CA et votre caisse en temps réel.</div>
            </div>
        </div>
    </div>

    {{-- TARIFS --}}
    <div id="tarifs" style="padding: 40px 0; border-top: 1px solid var(--color-border); scroll-margin-top: 80px;">
        <h2 style="font-size: 24px; font-weight: 800; text-align: center; margin: 0 0 8px 0;">
            Un tarif simple, pensé pour votre budget
        </h2>
        <p style="text-align: center; color: var(--color-text-secondary); font-size: 14px; margin: 0 0 36px 0;">
            Commencez gratuitement. Aucune carte bancaire requise.
        </p>

        <div style="max-width: 380px; margin: 0 auto; background: white; border: 2px solid var(--color-accent); border-radius: 16px; padding: 32px; text-align: center;">
            <div style="display: inline-block; background: rgba(193,84,30,0.1); color: var(--color-accent); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; margin-bottom: 16px;">
                ESSAI GRATUIT
            </div>
            <div style="font-size: 36px; font-weight: 800; margin-bottom: 4px;">14 jours</div>
            <div style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 24px;">
                Accès complet, sans engagement
            </div>

            <div style="text-align: left; margin-bottom: 24px;">
                <div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B4D3C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size: 14px;">Tables, commandes et cuisine</span>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B4D3C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size: 14px;">Caisse et suivi des dépenses</span>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B4D3C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size: 14px;">Dashboard et rapports en direct</span>
                </div>
                <div style="display: flex; gap: 10px; align-items: flex-start;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0B4D3C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span style="font-size: 14px;">Comptes serveurs et caissiers illimités</span>
                </div>
            </div>

            <a href="{{ route('demo.request') }}" class="btn-primary" style="display: block; text-align: center;">
                Demander une démo gratuite
            </a>

            <p style="font-size: 12px; color: var(--color-text-secondary); margin-top: 14px;">
                Après l'essai, contactez-nous pour votre tarif adapté à votre établissement.
            </p>
        </div>
    </div>

    {{-- CTA FINAL --}}
    <div style="background: var(--color-primary); border-radius: 20px; padding: 48px; text-align: center; margin: 40px 0 60px 0;">
        <h2 style="font-size: 24px; font-weight: 800; color: white; margin: 0 0 10px 0;">Prêt à essayer ?</h2>
        <p style="font-size: 14px; color: rgba(255,255,255,0.7); margin: 0 0 24px 0;">
            14 jours d'essai gratuit, sans carte bancaire.
        </p>
        <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo</a>
    </div>

</div>