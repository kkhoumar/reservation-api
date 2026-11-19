<div class="lp">
    <style>
        /* ====== Bases ====== */
        .lp .band { width: 100vw; margin-left: calc(50% - 50vw); }
        /* Jamais de défilement de côté (barre de défilement incluse dans 100vw) */
        html, body { overflow-x: clip; }
        .lp .wrap { max-width: 1180px; margin: 0 auto; padding: 0 24px; }
        .lp section { padding: 88px 0; scroll-margin-top: 70px; }
        .lp .alt { background: var(--color-mint); }
        .lp .eyebrow { display: inline-flex; align-items: center; gap: 8px; background: rgba(210,98,42,0.10); color: var(--color-accent); font-size: 12px; font-weight: 800; padding: 7px 14px; border-radius: 999px; letter-spacing: .04em; text-transform: uppercase; }
        .lp h2 { font-size: 34px; font-weight: 800; text-align: center; margin: 16px auto 12px; line-height: 1.12; max-width: 760px; }
        .lp .lead { text-align: center; color: var(--color-text-secondary); font-size: 18px; margin: 0 auto 48px; max-width: 640px; line-height: 1.6; }
        .lp .center { text-align: center; }
        .lp .card { background: white; border: 1px solid var(--color-border); border-radius: 20px; padding: 26px; box-shadow: var(--shadow-sm); transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
        .lp .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: rgba(94,191,160,.6); }
        .lp .card h3 { font-size: 18px; margin: 0 0 8px; }
        .lp .card p { font-size: 15px; color: var(--color-text-secondary); line-height: 1.6; margin: 0; }
        .lp .icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; color: var(--color-primary); }
        .lp .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
        .lp .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

        /* Apparition au défilement */
        .lp .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s ease; }
        .lp .reveal.in { opacity: 1; transform: none; }
        .lp .reveal.d1 { transition-delay: .08s; } .lp .reveal.d2 { transition-delay: .16s; } .lp .reveal.d3 { transition-delay: .24s; }

        /* ====== HERO ====== */
        .lp .hero { position: relative; overflow: hidden; color: white;
            background:
                radial-gradient(800px 420px at 85% 10%, rgba(94,191,160,.28), transparent 60%),
                radial-gradient(700px 400px at 0% 100%, rgba(210,98,42,.22), transparent 60%),
                linear-gradient(160deg, #0B4D3C 0%, #062E24 100%);
        }
        .lp .hero::after { content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: radial-gradient(rgba(255,255,255,.07) 1px, transparent 1px); background-size: 22px 22px;
            mask-image: linear-gradient(to bottom, black, transparent 85%); -webkit-mask-image: linear-gradient(to bottom, black, transparent 85%); }
        .lp .hero-grid { position: relative; z-index: 1; display: grid; grid-template-columns: 1.08fr .92fr; gap: 56px; align-items: center; padding-top: 76px; padding-bottom: 92px; }
        .lp .hero .eyebrow { background: rgba(255,255,255,.12); color: #FFD7BF; }
        .lp .hero h1 { font-size: 46px; font-weight: 800; line-height: 1.1; margin: 22px 0 22px; letter-spacing: -0.035em; }
        .lp .hero h1 .hl { background: linear-gradient(90deg, #FFB37F, #F07A3A); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .lp .hero p.sub { font-size: 17px; color: rgba(255,255,255,.78); line-height: 1.6; margin: 0 0 32px; max-width: 540px; }
        .lp .ctas { display: flex; gap: 12px; flex-wrap: wrap; }
        .lp .hero .btn-ghost-light { display: inline-flex; align-items: center; gap: 8px; color: white; text-decoration: none; font-weight: 700; font-size: 15px; padding: 15px 22px; border-radius: 12px; border: 1.5px solid rgba(255,255,255,.28); transition: background .15s, transform .15s; }
        .lp .hero .btn-ghost-light:hover { background: rgba(255,255,255,.08); transform: translateY(-2px); }
        .lp .trust { display: flex; gap: 10px 22px; flex-wrap: wrap; margin-top: 30px; font-size: 14px; color: rgba(255,255,255,.75); }
        .lp .trust span { display: inline-flex; align-items: center; gap: 7px; }
        .lp .trust span::before { content: ''; width: 18px; height: 18px; border-radius: 50%; background: rgba(94,191,160,.25) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235EBFA0' stroke-width='3.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E") center/11px no-repeat; }
        .lp .pay-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 26px; }
        .lp .phone-col { display: flex; flex-direction: column; align-items: center; }
        .lp .phone-col .phone-zone { width: 100%; }
        /* ---- Bouton « Installer l'application mobile » (posé sur le bas du téléphone) ---- */
        .lp .app-dl { max-width: 420px; }
        .lp .app-dl-under { position: relative; z-index: 4; margin-top: -58px; display: flex; flex-direction: column; align-items: center; text-align: center; animation: dlIn .7s cubic-bezier(.2,.9,.3,1.2) .9s both; }
        .lp .app-dl-under .app-dl-help { text-align: left; max-width: 340px; }
        .lp .app-dl-btn { position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 12px; background: #0A0F0D; color: white; border: 1px solid rgba(255,255,255,.22); border-radius: 16px; padding: 10px 20px 10px 10px; cursor: pointer; font-family: inherit; transition: transform .2s, border-color .2s, background .2s; box-shadow: 0 18px 40px rgba(0,0,0,.40), 0 0 0 6px rgba(255,255,255,.06); }
        .lp .app-dl-btn:hover { transform: translateY(-3px) scale(1.02); border-color: rgba(255,255,255,.5); background: #111A16; }
        .lp .app-dl-btn:disabled { cursor: default; transform: none; }
        /* Reflet qui balaie le bouton */
        .lp .app-dl-btn::before { content: ''; position: absolute; top: 0; bottom: 0; left: -60%; width: 40%; background: linear-gradient(100deg, transparent, rgba(255,255,255,.22), transparent); transform: skewX(-20deg); animation: dlShine 4.5s ease-in-out 2s infinite; }
        .lp .app-dl-ico { position: relative; width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, #F07A3A, var(--color-accent-dark)); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .lp .app-dl-ico svg { position: absolute; }
        /* Halo qui pulse autour de l'icône */
        .lp .app-dl-ico::after { content: ''; position: absolute; inset: 0; border-radius: 12px; box-shadow: 0 0 0 0 rgba(240,122,58,.7); animation: dlPulse 2.2s ease-out infinite; }
        /* Flèche qui descend vers le « bac » */
        .lp .dl-arrow { animation: dlBounce 1.6s ease-in-out infinite; }
        .lp .app-dl-btn small { display: block; font-size: 11px; color: rgba(255,255,255,.65); font-weight: 600; letter-spacing: .02em; }
        .lp .app-dl-btn strong { display: block; font-size: 15px; font-family: var(--font-display); }
        .lp .app-dl-help { margin-top: 12px; background: rgba(6,46,36,.92); border: 1px solid rgba(255,255,255,.16); border-radius: 14px; padding: 14px 16px; font-size: 14px; line-height: 1.6; color: rgba(255,255,255,.92); box-shadow: 0 18px 40px rgba(0,0,0,.35); }
        .lp .app-dl-help ol { margin: 6px 0 0; padding-left: 20px; }
        @keyframes dlBounce { 0%, 100% { transform: translateY(-3px); opacity: 1; } 50% { transform: translateY(2px); opacity: .85; } 60% { transform: translateY(3px); } }
        @keyframes dlPulse { 0% { box-shadow: 0 0 0 0 rgba(240,122,58,.65); } 70% { box-shadow: 0 0 0 14px rgba(240,122,58,0); } 100% { box-shadow: 0 0 0 0 rgba(240,122,58,0); } }
        @keyframes dlShine { 0% { left: -60%; } 30%, 100% { left: 130%; } }
        @keyframes dlIn { from { opacity: 0; transform: translateY(18px) scale(.95); } to { opacity: 1; transform: none; } }

        /* ---- Barre d'installation fixe, sur téléphone uniquement ---- */
        .lp .app-sticky { display: none; }
        @media (max-width: 640px) {
            .lp .app-dl-under { margin-top: 22px; }
            .lp .app-sticky { position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 60; display: flex; align-items: center; gap: 12px; background: #0A0F0D; color: white; border-radius: 18px; padding: 10px 10px 10px 12px; box-shadow: 0 18px 40px rgba(0,0,0,.4); transform: translateY(140%); transition: transform .45s cubic-bezier(.2,.9,.3,1.1); }
            .lp .app-sticky.show { transform: none; }
            .lp .app-sticky .txt { flex: 1; line-height: 1.25; }
            .lp .app-sticky .txt strong { display: block; font-family: var(--font-display); font-size: 15px; }
            .lp .app-sticky .txt small { color: rgba(255,255,255,.65); font-size: 12px; }
            .lp .app-sticky .go { background: var(--color-accent); color: white; border: none; border-radius: 12px; padding: 10px 14px; font-weight: 800; font-size: 14px; font-family: inherit; }
            .lp .app-sticky .close { background: none; border: none; color: rgba(255,255,255,.6); font-size: 20px; padding: 4px 6px; line-height: 1; }
        }
        .lp .pay { font-size: 12px; font-weight: 800; padding: 6px 12px; border-radius: 8px; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.14); color: rgba(255,255,255,.9); }

        /* Téléphone */
        .lp .phone-zone { position: relative; display: flex; justify-content: center; }
        .lp .phone { position: relative; width: 310px; background: #0A0F0D; border-radius: 42px; padding: 11px; box-shadow: 0 50px 100px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.08) inset; transform: rotate(-2deg); }
        .lp .phone::before { content: ''; position: absolute; top: 18px; left: 50%; transform: translateX(-50%); width: 90px; height: 22px; background: #0A0F0D; border-radius: 12px; z-index: 2; }
        .lp .screen { background: #EFE7DE; border-radius: 32px; overflow: hidden; min-height: 560px; }
        .lp .wa-head { background: #0B5D4B; color: white; padding: 46px 16px 12px; font-size: 13px; display: flex; gap: 10px; align-items: center; }
        .lp .wa-head .av { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--color-accent), var(--color-accent-dark)); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 12px; }
        .lp .wa-body { padding: 14px 12px 20px; }
        .lp .typing { display: inline-flex; gap: 4px; background: white; padding: 10px 14px; border-radius: 12px 12px 12px 3px; }
        .lp .typing i { width: 7px; height: 7px; border-radius: 50%; background: #9AA6A1; animation: blink 1.2s infinite ease-in-out; }
        .lp .typing i:nth-child(2) { animation-delay: .15s; } .lp .typing i:nth-child(3) { animation-delay: .3s; }
        @keyframes blink { 0%, 80%, 100% { opacity: .3; transform: translateY(0); } 40% { opacity: 1; transform: translateY(-3px); } }
        .lp .bubble { background: white; border-radius: 12px 12px 12px 3px; padding: 12px 14px; font-size: 13px; line-height: 1.6; color: #1C2B26; box-shadow: 0 1px 1px rgba(0,0,0,.08); }
        .lp .bubble .line { opacity: 0; transform: translateY(4px); transition: opacity .35s ease, transform .35s ease; }
        .lp .bubble .line.on { opacity: 1; transform: none; }
        .lp .bubble .time { text-align: right; font-size: 10px; color: #8E9A95; margin-top: 4px; }
        .lp .float { position: absolute; background: white; color: var(--color-text); border-radius: 16px; padding: 12px 14px; box-shadow: var(--shadow-lg); font-size: 13px; display: flex; gap: 10px; align-items: center; animation: floaty 6s ease-in-out infinite; z-index: 3; }
        .lp .float b { display: block; font-family: var(--font-display); font-size: 15px; }
        .lp .float small { color: var(--color-text-secondary); }
        .lp .float .dot { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; }
        .lp .float.f1 { left: -48px; top: 210px; }
        .lp .float.f2 { right: -40px; bottom: 90px; animation-delay: -3s; }
        @keyframes floaty { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }

        /* ====== Bandeau de faits ====== */
        .lp .facts { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0; background: white; border: 1px solid var(--color-border); border-radius: 22px; box-shadow: var(--shadow-md); margin-top: -46px; position: relative; z-index: 2; }
        .lp .fact { padding: 24px; text-align: center; border-right: 1px solid var(--color-border); }
        .lp .fact:last-child { border-right: none; }
        .lp .fact b { display: block; font-family: var(--font-display); font-size: 30px; color: var(--color-primary); }
        .lp .fact span { font-size: 14px; color: var(--color-text-secondary); }

        /* ====== Douleurs ====== */
        .lp .pain .icon { background: #FDEDEA; color: #C2452D; }

        /* ====== Présentoir interactif ====== */
        .lp .showcase { display: grid; grid-template-columns: .9fr 1.1fr; gap: 40px; align-items: center; }
        .lp .tabs { display: flex; flex-direction: column; gap: 10px; }
        .lp .tab { text-align: left; background: white; border: 1.5px solid var(--color-border); border-radius: 16px; padding: 16px 18px; cursor: pointer; font-family: inherit; display: flex; gap: 14px; align-items: flex-start; transition: border-color .2s, box-shadow .2s, transform .2s; position: relative; overflow: hidden; }
        .lp .tab:hover { transform: translateX(3px); }
        .lp .tab .ti { width: 40px; height: 40px; border-radius: 12px; background: var(--color-mint); display: flex; align-items: center; justify-content: center; color: var(--color-primary); flex-shrink: 0; transition: background .2s, color .2s; }
        .lp .tab strong { display: block; font-size: 16px; color: var(--color-text); font-family: var(--font-display); }
        .lp .tab span.d { display: block; font-size: 14px; color: var(--color-text-secondary); line-height: 1.5; margin-top: 3px; }
        .lp .tab.active { border-color: var(--color-primary); box-shadow: var(--shadow-md); }
        .lp .tab.active .ti { background: var(--color-primary); color: white; }
        .lp .tab .bar { position: absolute; left: 0; bottom: 0; height: 3px; width: 0; background: var(--color-accent); }
        .lp .tab.active .bar { animation: fill 6s linear forwards; }
        .lp .showcase.paused .tab.active .bar { animation: none; width: 0; }
        @keyframes fill { to { width: 100%; } }

        .lp .device { background: linear-gradient(160deg, #0B4D3C, #062E24); border-radius: 28px; padding: 18px; box-shadow: var(--shadow-lg); }
        .lp .device-bar { display: flex; gap: 6px; margin: 2px 4px 14px; }
        .lp .device-bar i { width: 10px; height: 10px; border-radius: 50%; background: rgba(255,255,255,.25); }
        .lp .panel { display: none; background: #F7FAF8; border-radius: 18px; padding: 20px; min-height: 380px; animation: panelIn .45s ease; }
        .lp .panel.active { display: block; }
        @keyframes panelIn { from { opacity: 0; transform: translateY(10px) scale(.99); } to { opacity: 1; transform: none; } }
        .lp .ui-title { font-family: var(--font-display); font-weight: 800; font-size: 18px; margin: 0 0 14px; display: flex; justify-content: space-between; align-items: center; }
        .lp .ui-pill { font-size: 11px; font-weight: 800; padding: 4px 10px; border-radius: 999px; background: rgba(210,98,42,.12); color: var(--color-accent); font-family: 'Inter', sans-serif; }
        .lp .ui-card { background: white; border: 1px solid var(--color-border); border-radius: 14px; padding: 14px; margin-bottom: 10px; }
        .lp .ui-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; font-size: 14px; }
        .lp .ui-muted { font-size: 12px; color: var(--color-text-secondary); }
        .lp .ui-big { font-family: var(--font-display); font-size: 30px; font-weight: 800; color: var(--color-primary); }
        .lp .ui-btn { font-size: 12px; font-weight: 800; padding: 7px 12px; border-radius: 9px; background: var(--color-accent); color: white; border: none; }
        .lp .ui-btn.ghost { background: white; color: var(--color-text); border: 1px solid var(--color-border); }
        .lp .ui-chip { display: inline-block; font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px; border: 1px solid var(--color-border); background: white; margin: 0 6px 6px 0; }
        .lp .ui-chip.on { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .lp .ui-bar { height: 8px; border-radius: 99px; background: #E1EAE5; overflow: hidden; }
        .lp .ui-bar i { display: block; height: 100%; border-radius: 99px; background: var(--color-primary-300); }
        .lp .ok { color: #1E8E5A; font-weight: 800; }
        .lp .warn { color: var(--color-danger); font-weight: 800; }

        /* ====== Rôles ====== */
        .lp .role { position: relative; overflow: hidden; }
        .lp .role .who { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--color-accent); margin-bottom: 8px; }
        .lp .role ul { list-style: none; padding: 0; margin: 12px 0 0; font-size: 14px; color: var(--color-text-secondary); line-height: 2; }
        .lp .role li::before { content: '→  '; color: var(--color-primary-300); font-weight: 800; }
        .lp .role .icon { background: var(--color-mint); color: var(--color-primary); width: 44px; height: 44px; margin-bottom: 14px; }
        .lp .ui-ico { display: inline-flex; vertical-align: -2px; color: inherit; }
        .lp .ui-row > .ui-ico { margin-right: 6px; }

        /* ====== Étapes ====== */
        .lp .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; position: relative; }
        .lp .steps::before { content: ''; position: absolute; top: 28px; left: 12%; right: 12%; height: 2px; background: repeating-linear-gradient(90deg, var(--color-primary-300) 0 8px, transparent 8px 16px); }
        .lp .step { text-align: center; position: relative; }
        .lp .step-n { width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid var(--color-primary); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; margin: 0 auto 16px; font-family: var(--font-display); box-shadow: 0 0 0 8px var(--color-mint); position: relative; }
        .lp .step h3 { font-size: 17px; margin: 0 0 6px; }
        .lp .step p { font-size: 14px; color: var(--color-text-secondary); margin: 0; line-height: 1.55; }

        /* ====== Tarifs ====== */
        .lp .plans { display: grid; gap: 22px; align-items: stretch; grid-template-columns: repeat(var(--cols, 3), 1fr); }
        @media (max-width: 900px) { .lp .plans { grid-template-columns: 1fr; max-width: 440px; margin-left: auto; margin-right: auto; } }
        .lp .plan { position: relative; display: flex; flex-direction: column; padding: 30px; }
        .lp .plan.featured { background: linear-gradient(165deg, #0B4D3C, #062E24); color: white; border-color: transparent; box-shadow: var(--shadow-lg); transform: translateY(-8px); }
        .lp .plan.featured:hover { transform: translateY(-12px); }
        .lp .plan.featured p, .lp .plan.featured .per { color: rgba(255,255,255,.7); }
        .lp .plan .badge-pop { position: absolute; top: -13px; right: 24px; background: linear-gradient(90deg, #F07A3A, var(--color-accent)); color: white; font-size: 11px; font-weight: 800; padding: 6px 12px; border-radius: 999px; letter-spacing: .05em; }
        .lp .plan .price { font-family: var(--font-display); font-size: 42px; font-weight: 800; margin: 10px 0 0; letter-spacing: -0.03em; }
        .lp .plan .per { font-size: 14px; color: var(--color-text-secondary); }
        .lp .plan ul { list-style: none; padding: 0; margin: 22px 0 26px; font-size: 15px; line-height: 2.1; flex: 1; }
        .lp .plan li::before { content: ''; display: inline-block; width: 15px; height: 15px; margin-right: 8px; vertical-align: -2px; background: no-repeat center/15px url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235EBFA0' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M20 6 9 17l-5-5'/%3E%3C/svg%3E"); color: var(--color-primary-300); font-weight: 900; }
        .lp .plan .btn-primary, .lp .plan .btn-secondary-outline { width: 100%; }

        /* ====== FAQ ====== */
        .lp .faq { max-width: 780px; margin: 0 auto; }
        .lp details { background: white; border: 1px solid var(--color-border); border-radius: 16px; padding: 0 22px; margin-bottom: 12px; transition: box-shadow .2s, border-color .2s; }
        .lp details[open] { box-shadow: var(--shadow-md); border-color: rgba(94,191,160,.6); }
        .lp summary { font-weight: 700; cursor: pointer; font-size: 16px; padding: 20px 0; list-style: none; display: flex; justify-content: space-between; align-items: center; gap: 16px; font-family: var(--font-display); }
        .lp summary::-webkit-details-marker { display: none; }
        .lp summary::after { content: '+'; flex-shrink: 0; width: 30px; height: 30px; border-radius: 50%; background: var(--color-mint); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; transition: transform .25s; }
        .lp details[open] summary::after { transform: rotate(45deg); }
        .lp details p { color: var(--color-text-secondary); font-size: 15px; line-height: 1.65; margin: 0 0 20px; }

        /* ====== Appel final ====== */
        .lp .final { position: relative; overflow: hidden; border-radius: 32px; padding: 72px 32px; text-align: center; color: white; margin: 0 0 88px;
            background: radial-gradient(600px 300px at 50% 0%, rgba(94,191,160,.30), transparent 70%), linear-gradient(160deg, #0B4D3C, #062E24); }
        .lp .final h2 { color: white; margin-top: 0; }
        .lp .final p { font-size: 18px; color: rgba(255,255,255,.8); margin: 0 auto 30px; max-width: 560px; line-height: 1.6; }

        /* ====== Écrans ====== */
        @media (max-width: 1000px) {
            .lp .hero-grid, .lp .showcase { grid-template-columns: 1fr; }
            .lp .hero h1 { font-size: 38px; }
            .lp .grid-4, .lp .steps, .lp .facts { grid-template-columns: repeat(2, 1fr); }
            .lp .fact:nth-child(2) { border-right: none; }
            .lp .fact:nth-child(-n+2) { border-bottom: 1px solid var(--color-border); }
            .lp .steps::before { display: none; }
            .lp .grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .lp .wrap { padding: 0 16px; }
            .lp section { padding: 64px 0; }
            .lp h2 { font-size: 26px; }
            .lp .lead { font-size: 16px; }
            .lp .hero-grid { padding-top: 44px; padding-bottom: 80px; gap: 40px; }
            .lp .hero h1 { font-size: 30px; }
            .lp .grid-3, .lp .grid-4, .lp .steps { grid-template-columns: 1fr; }
            /* Sur téléphone : pas de « téléphone dans le téléphone ».
               On garde seulement le message WhatsApp, dans une carte simple. */
            .lp .phone { width: 100%; max-width: 420px; transform: none; background: none; padding: 0; border-radius: 20px; box-shadow: 0 24px 50px rgba(0,0,0,.35); }
            .lp .phone::before { display: none; }
            .lp .screen { min-height: 0; border-radius: 20px; }
            .lp .wa-head { padding: 12px 16px; }
            .lp .wa-body { padding: 12px 10px 14px; }
            .lp .bubble { font-size: 13.5px; line-height: 1.5; }
            .lp .bubble .line:empty, .lp .bubble .line.gap { height: 6px; }
            /* Message affiché d'emblée, complet : la page ne « saute » pas */
            .lp #waTyping { display: none !important; }
            .lp #waBubble { display: block !important; }
            .lp .bubble .line { opacity: 1; transform: none; transition: none; }
            .lp .float { display: none; }
            .lp .plan.featured { transform: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .lp *, .lp *::before, .lp *::after { animation: none !important; transition: none !important; }
            .lp .reveal, .lp .bubble .line { opacity: 1; transform: none; }
        }
    </style>

    {{-- ================= HERO ================= --}}
    <div class="band hero">
        <div class="wrap hero-grid">
            <div>
                <span class="eyebrow"><span aria-hidden="true" style="display: inline-flex; width: 18px; height: 12px; border-radius: 2px; overflow: hidden;"><i style="flex: 1; background: #F77F00;"></i><i style="flex: 1; background: #fff;"></i><i style="flex: 1; background: #009E60;"></i></span> Fait pour les maquis de Côte d'Ivoire</span>
                <h1>Votre maquis tourne.<br><span class="hl">Votre argent rentre.</span><br>Même quand vous n'êtes&nbsp;pas&nbsp;là.</h1>
                <p class="sub">
                    Commandes, comptoir, caisse, stock et crédits clients sur un simple téléphone.
                    Et chaque soir, le rapport de la journée arrive sur votre WhatsApp.
                </p>
                <div class="ctas">
                    <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo gratuite →</a>
                    <a href="#fonctionnalites" class="btn-ghost-light">Voir l'application</a>
                </div>
                <div class="trust">
                    <span>{{ $trialDays }} jours d'essai gratuit</span>
                    <span>Installation avec notre équipe</span>
                    <span>Sans matériel à acheter</span>
                </div>
                <div class="pay-row" aria-label="Moyens de paiement acceptés">
                    <span class="pay">Espèces</span><span class="pay">Wave</span><span class="pay">Orange Money</span><span class="pay">MTN MoMo</span><span class="pay">Moov Money</span>
                </div>
            </div>

            <div class="phone-col">
            <div class="phone-zone">
                <div class="float f1"><div class="dot" style="background: #E7F6EF; color: #1E8E5A;"><x-icon name="check-circle" size="19" /></div><div><b>Caisse juste</b><small>Écart : 0 F</small></div></div>
                <div class="float f2"><div class="dot" style="background: #FDEFE6; color: #D2622A;"><x-icon name="wallet" size="19" /></div><div><b>+ 71 000 F</b><small>apportés par Aya</small></div></div>

                <div class="phone" aria-label="Exemple de rapport du soir reçu sur WhatsApp">
                    <div class="screen">
                        <div class="wa-head"><div class="av">ME</div><div><strong>Maquis Express</strong><br><span style="opacity: .75; font-size: 11px;">rapport du soir</span></div></div>
                        <div class="wa-body">
                            <div class="typing" id="waTyping"><i></i><i></i><i></i></div>
                            <div class="bubble" id="waBubble" style="display: none;">
                                <div class="line"><strong>Maquis Chez Koffi</strong> — samedi</div>
                                <div class="line">Caisse 18:02 → 23:47 · clôturée par Jean</div>
                                <div class="line gap">&nbsp;</div>
                                <div class="line"><strong>Ventes : 186 500 FCFA</strong> (64 bons)</div>
                                <div class="line">• Espèces : 121 000</div>
                                <div class="line">• Wave : 48 500</div>
                                <div class="line">• À crédit : 17 000</div>
                                <div class="line">• Dépenses : 9 000</div>
                                <div class="line gap">&nbsp;</div>
                                <div class="line"><strong>Caisse</strong> · Écart : 0 ✅</div>
                                <div class="line gap">&nbsp;</div>
                                <div class="line"><strong>Par serveuse</strong></div>
                                <div class="line">• Aya : 23 bons · 71 000</div>
                                <div class="line">• Awa : 19 bons · 58 500</div>
                                <div class="line gap">&nbsp;</div>
                                <div class="line"><strong>À surveiller</strong> · Offerts : 3 000 F</div>
                                <div class="time">23:48 ✓✓</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Application mobile installable --}}
            <div class="app-dl app-dl-under">
                <button type="button" id="installAppBtn" class="app-dl-btn">
                    <span class="app-dl-ico">
                        {{-- Flèche de téléchargement animée --}}
                        <svg class="dl-arrow" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7 10.5l5 5 5-5"/></svg>
                        <svg class="dl-tray" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 20h14"/></svg>
                    </span>
                    <span style="text-align: left;">
                        <small id="installAppSmall">Gratuit · Android et iPhone</small>
                        <strong id="installAppLabel">Installer l'application mobile</strong>
                    </span>
                </button>
                <div id="installAppHelp" class="app-dl-help" hidden></div>
            </div>
            </div>
        </div>
    </div>

    {{-- ================= FAITS ================= --}}
    <div class="facts reveal">
        <div class="fact"><b>5</b><span>moyens de paiement</span></div>
        <div class="fact"><b>4</b><span>rôles : serveuse, comptoir, gérante, patron</span></div>
        <div class="fact"><b>1</b><span>rapport WhatsApp chaque soir</span></div>
        <div class="fact"><b>0 F</b><span>de matériel à acheter</span></div>
    </div>

    {{-- ================= DOULEURS ================= --}}
    <section>
        <div class="center reveal"><span class="eyebrow">Ça vous parle ?</span></div>
        <h2 class="reveal">Ce qui fait perdre de l'argent à un maquis</h2>
        <p class="lead reveal">Ce n'est pas un manque de clients. C'est tout ce qui se perd entre le frigo, la table et la caisse.</p>
        <div class="grid-4 pain">
            <div class="card reveal"><div class="icon"><x-icon name="bottle" size="24" /></div><h3>Des boissons jamais payées</h3><p>Une tournée oubliée, une bouteille « offerte » aux amis… et le soir, le compte n'y est pas.</p></div>
            <div class="card reveal d1"><div class="icon"><x-icon name="cash-missing" size="24" /></div><h3>Une caisse qui ne tombe pas juste</h3><p>Il manque 5 000 F, et personne ne sait d'où vient l'écart.</p></div>
            <div class="card reveal d2"><div class="icon"><x-icon name="notebook" size="24" /></div><h3>Des crédits oubliés</h3><p>« Mets ça sur mon compte » : trois semaines après, plus personne ne sait combien il doit.</p></div>
            <div class="card reveal d3"><div class="icon"><x-icon name="box" size="24" /></div><h3>Un stock qui fond tout seul</h3><p>Les casiers arrivent, des bouteilles disparaissent, et personne ne sait combien le maquis gagne.</p></div>
        </div>
    </section>

    {{-- ================= PRÉSENTOIR INTERACTIF ================= --}}
    <div class="band alt">
        <div class="wrap"><section id="fonctionnalites">
            <div class="center reveal"><span class="eyebrow">La solution</span></div>
            <h2 class="reveal">Tout votre maquis, dans une seule application</h2>
            <p class="lead reveal">Fait pour le travail d'un maquis : la tournée, le comptoir, les habitués, le Mobile Money. Touchez une fonction pour la voir.</p>

            <div class="showcase reveal" id="showcase">
                <div class="tabs" role="tablist">
                    <button class="tab active" role="tab" data-panel="p-comptoir"><div class="ti"><x-icon name="receipt" size="20" /></div><div><strong>Bon de comptoir</strong><span class="d">La serveuse envoie la commande, le comptoir encaisse, puis on sert. Pas d'argent, pas de boisson.</span></div><span class="bar"></span></button>
                    <button class="tab" role="tab" data-panel="p-caisse"><div class="ti"><x-icon name="cash" size="20" /></div><div><strong>Une caisse qui tombe juste</strong><span class="d">On compte les billets. L'application dit tout de suite s'il manque de l'argent, et combien.</span></div><span class="bar"></span></button>
                    <button class="tab" role="tab" data-panel="p-mobile"><div class="ti"><x-icon name="phone" size="20" /></div><div><strong>Espèces et Mobile Money</strong><span class="d">Wave, Orange, MTN, Moov, ou mixte avec la monnaie à rendre.</span></div><span class="bar"></span></button>
                    <button class="tab" role="tab" data-panel="p-credit"><div class="ti"><x-icon name="notebook" size="20" /></div><div><strong>Crédits clients</strong><span class="d">Qui doit combien et pour quoi. Un clic envoie le rappel sur WhatsApp.</span></div><span class="bar"></span></button>
                    <button class="tab" role="tab" data-panel="p-stock"><div class="ti"><x-icon name="box" size="20" /></div><div><strong>Stock surveillé</strong><span class="d">Livraisons, casse, inventaire : ce qui manque, en bouteilles et en francs.</span></div><span class="bar"></span></button>
                    <button class="tab" role="tab" data-panel="p-benef"><div class="ti"><x-icon name="chart" size="20" /></div><div><strong>Votre vrai bénéfice</strong><span class="d">Les ventes, moins les prix d'achat, les dépenses et les pertes : ce qui vous reste.</span></div><span class="bar"></span></button>
                </div>

                <div class="device">
                    <div class="device-bar"><i></i><i></i><i></i></div>

                    <div class="panel active" id="p-comptoir">
                        <div class="ui-title">Comptoir <span class="ui-pill">2 bons à encaisser</span></div>
                        <div class="ui-card">
                            <div class="ui-row"><strong>Table 4</strong><strong style="color: var(--color-primary);">3 500 FCFA</strong></div>
                            <div class="ui-muted">Aya · CMD-00142 · il y a 1 min</div>
                            <div style="margin: 8px 0 10px; font-size: 13px;">3 × Bière · 1 × Bissap</div>
                            <div class="ui-row" style="justify-content: flex-start; gap: 8px;"><span class="ui-btn">Encaisser</span><span class="ui-btn ghost">À crédit</span><span class="ui-btn ghost">Offrir / remise</span></div>
                        </div>
                        <div class="ui-card">
                            <div class="ui-row"><strong>Table 7</strong><strong style="color: var(--color-primary);">7 000 FCFA</strong></div>
                            <div class="ui-muted">Awa · CMD-00143 · à l'instant</div>
                            <div style="margin-top: 8px; font-size: 13px;">1 × Poulet braisé · 2 × Bière</div>
                        </div>
                        <div class="ui-card" style="background: #F0FAF5; border-color: #CDEBDD;"><div class="ui-row"><span class="ui-ico" style="color: var(--color-success);"><x-icon name="check-circle" size="16" /></span><span>Table 2 encaissée : servez la commande</span></div></div>
                    </div>

                    <div class="panel" id="p-caisse">
                        <div class="ui-title">Caisse <span class="ui-pill">ouverte par Jean à 18:02</span></div>
                        <div class="ui-card">
                            <div class="ui-muted">Argent qui doit être dans le tiroir</div>
                            <div class="ui-big">131 000 FCFA</div>
                        </div>
                        <div class="ui-card">
                            <div class="ui-row"><span>Fonds de départ</span><strong>10 000</strong></div>
                            <div class="ui-row"><span>+ Espèces des ventes</span><strong class="ok">+121 000</strong></div>
                            <div class="ui-row"><span>+ Remboursements de crédits</span><strong class="ok">+9 000</strong></div>
                            <div class="ui-row"><span>− Dépenses</span><strong class="warn">−9 000</strong></div>
                        </div>
                        <div class="ui-card"><div class="ui-row"><span>Compté au billetage</span><strong>131 000</strong></div><div class="ui-row"><span>Écart</span><strong class="ok">0 F · la caisse est juste <span class="ui-ico"><x-icon name="check-circle" size="15" /></span></strong></div></div>
                    </div>

                    <div class="panel" id="p-mobile">
                        <div class="ui-title">Encaissement <span class="ui-pill">Table 7 · 10 000 FCFA</span></div>
                        <div class="ui-card">
                            <div class="ui-muted" style="margin-bottom: 8px;">Mode de paiement</div>
                            <span class="ui-chip">Espèces</span><span class="ui-chip on">Wave</span><span class="ui-chip">Orange Money</span><span class="ui-chip">MTN MoMo</span><span class="ui-chip">Moov</span>
                        </div>
                        <div class="ui-card">
                            <div class="ui-row"><span>Payé par Wave</span><strong>6 000</strong></div>
                            <div class="ui-row"><span>Reste en espèces</span><strong>4 000</strong></div>
                            <div class="ui-row"><span>Reçu en espèces</span><strong>5 000</strong></div>
                            <div class="ui-row" style="margin-top: 6px;"><span>Monnaie à rendre</span><strong style="color: var(--color-accent); font-size: 18px;">1 000 FCFA</strong></div>
                        </div>
                        <div class="ui-muted">Seules les espèces vont dans le tiroir ; le Wave est suivi à part.</div>
                    </div>

                    <div class="panel" id="p-credit">
                        <div class="ui-title">Crédits clients <span class="ui-pill">42 500 F à récupérer</span></div>
                        <div class="ui-card"><div class="ui-row"><strong>M. Konan</strong><strong style="color: var(--color-accent);">18 000 F</strong></div><div class="ui-muted">Plafond 20 000 · dernier passage hier</div></div>
                        <div class="ui-card"><div class="ui-row"><strong>Tantie Awa</strong><strong style="color: var(--color-accent);">14 500 F</strong></div>
                            <div style="font-size: 13px; margin-top: 6px;">12/11 · 3 × Bière, 1 × Alloco · Table 3 · servi par Aya</div></div>
                        <div class="ui-card"><div class="ui-row"><strong>Ibrahim</strong><strong style="color: var(--color-accent);">10 000 F</strong></div></div>
                        <span class="ui-btn" style="display: inline-flex; align-items: center; gap: 6px;"><x-icon name="message" size="15" /> Relancer sur WhatsApp</span>
                    </div>

                    <div class="panel" id="p-stock">
                        <div class="ui-title">Inventaire <span class="ui-pill">compté à l'aveugle</span></div>
                        <div class="ui-card" style="border-color: #F4C9C9; background: #FFF6F6;"><strong class="warn">Il manque l'équivalent de 4 200 FCFA</strong></div>
                        <div class="ui-card">
                            <div class="ui-row"><span>Bière</span><span class="ui-muted">attendu 48, compté 42</span><strong class="warn">−6</strong></div>
                            <div class="ui-row" style="margin-top: 6px;"><span>Sucrerie</span><span class="ui-muted">attendu 30, compté 30</span><strong class="ok">juste</strong></div>
                        </div>
                        <div class="ui-card">
                            <div class="ui-row" style="margin-bottom: 6px;"><span>Bière</span><span class="ui-muted">42 restantes</span></div><div class="ui-bar"><i style="width: 70%"></i></div>
                            <div class="ui-row" style="margin: 10px 0 6px;"><span>Jus de Bissap</span><span class="warn" style="font-size: 12px;">4 restants <span class="ui-ico"><x-icon name="alert" size="13" /></span></span></div><div class="ui-bar"><i style="width: 12%; background: var(--color-danger);"></i></div>
                        </div>
                    </div>

                    <div class="panel" id="p-benef">
                        <div class="ui-title">Tableau de bord <span class="ui-pill">▲ +18 % vs hier</span></div>
                        <div class="ui-card"><div class="ui-muted">Bénéfice estimé aujourd'hui</div><div class="ui-big">74 000 FCFA</div></div>
                        <div class="ui-card">
                            <div class="ui-row"><span>Ventes</span><strong>186 500</strong></div>
                            <div class="ui-row"><span>− prix d'achat des produits</span><strong>98 500</strong></div>
                            <div class="ui-row"><span>− autres dépenses</span><strong>9 000</strong></div>
                            <div class="ui-row"><span>− pertes de stock</span><strong>5 000</strong></div>
                        </div>
                        <div class="ui-card">
                            <div class="ui-muted" style="margin-bottom: 8px;">7 derniers jours</div>
                            <div style="display: flex; align-items: flex-end; gap: 8px; height: 70px;">
                                @foreach ([45, 60, 38, 72, 55, 88, 100] as $h)
                                    <div style="flex: 1; height: {{ $h }}%; border-radius: 6px 6px 0 0; background: {{ $loop->last ? 'var(--color-primary)' : 'var(--color-accent-light)' }};"></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section></div>
    </div>

    {{-- ================= LE RAPPORT ET LE RESTE ================= --}}
    <section>
        <div class="center reveal"><span class="eyebrow">Et aussi</span></div>
        <h2 class="reveal">Le contrôle, même à distance</h2>
        <p class="lead reveal">Vous savez ce qui se passe au maquis sans y être.</p>
        <div class="grid-3">
            <div class="card reveal"><div class="icon" style="background: #E7F6EF; color: #1E8E5A;"><x-icon name="message" size="24" /></div><h3>Le rapport du soir sur WhatsApp</h3><p>À la fermeture, vous recevez les ventes, l'écart de caisse, le chiffre de chaque serveuse et ce qu'il faut surveiller.</p></div>
            <div class="card reveal d1"><div class="icon" style="background: #FDEFE6; color: #D2622A;"><x-icon name="gift" size="24" /></div><h3>Offerts et remises tracés</h3><p>Seuls le patron et la gérante peuvent offrir, avec un motif. Fini le « c'était offert » invérifiable.</p></div>
            <div class="card reveal d2"><div class="icon" style="background: #EEF2FB; color: #3B5BA9;"><x-icon name="sheet" size="24" /></div><h3>Vos chiffres dans Excel</h3><p>Ventes, dépenses, caisse, crédits et stock se téléchargent en fichier Excel, pour vous ou votre comptable.</p></div>
        </div>
    </section>

    {{-- ================= POUR QUI ================= --}}
    <div class="band alt">
        <div class="wrap"><section>
            <div class="center reveal"><span class="eyebrow">Chacun son écran</span></div>
            <h2 class="reveal">Simple pour l'équipe, clair pour le patron</h2>
            <p class="lead reveal">Chacun ne voit que ce dont il a besoin. Si vous êtes seul à tout faire, un seul écran sert à prendre la commande, encaisser et servir.</p>
            <div class="grid-4">
                <div class="card role reveal"><div class="icon"><x-icon name="service" size="24" /></div><div class="who">La serveuse</div><h3>Prend la commande</h3><ul><li>Choisit la table</li><li>Touche les boissons</li><li>Envoie au comptoir</li></ul></div>
                <div class="card role reveal d1"><div class="icon"><x-icon name="receipt" size="24" /></div><div class="who">Le comptoir</div><h3>Encaisse et sert</h3><ul><li>Voit les bons arriver</li><li>Espèces ou Mobile Money</li><li>Clôture la caisse</li></ul></div>
                <div class="card role reveal d2"><div class="icon"><x-icon name="clipboard" size="24" /></div><div class="who">La gérante</div><h3>Organise</h3><ul><li>Crédits et offerts</li><li>Stock et livraisons</li><li>Équipe et dépenses</li></ul></div>
                <div class="card role reveal d3"><div class="icon"><x-icon name="crown" size="24" /></div><div class="who">Le patron</div><h3>Contrôle</h3><ul><li>Bénéfice et tableau de bord</li><li>Rapport du soir WhatsApp</li><li>Exports Excel</li></ul></div>
            </div>
        </section></div>
    </div>

    {{-- ================= COMMENT ÇA MARCHE ================= --}}
    <section id="comment-ca-marche">
        <div class="center reveal"><span class="eyebrow">Démarrer</span></div>
        <h2 class="reveal">On installe votre maquis avec vous</h2>
        <p class="lead reveal">Pas besoin de connaître l'informatique. Notre équipe fait la mise en route avec vous.</p>
        <div class="steps">
            <div class="step reveal"><div class="step-n">1</div><h3>Demandez une démo</h3><p>Laissez votre nom et votre numéro : on vous appelle.</p></div>
            <div class="step reveal d1"><div class="step-n">2</div><h3>On prépare votre maquis</h3><p>On crée votre compte, puis on saisit avec vous le menu, les tables et l'équipe.</p></div>
            <div class="step reveal d2"><div class="step-n">3</div><h3>Essayez gratuitement</h3><p>{{ $trialDays }} jours pour l'utiliser en vrai, pendant le service.</p></div>
            <div class="step reveal d3"><div class="step-n">4</div><h3>Choisissez votre formule</h3><p>Vous payez par Wave ou Orange Money, pour un mois ou plusieurs.</p></div>
        </div>
    </section>

    {{-- ================= TARIFS ================= --}}
    @if ($plans->isNotEmpty())
        <div class="band alt">
            <div class="wrap"><section id="tarifs">
                <div class="center reveal"><span class="eyebrow">Tarifs</span></div>
                <h2 class="reveal">Un prix clair, selon la taille de votre équipe</h2>
                <p class="lead reveal">Chaque formule contient toutes les fonctions. Seuls le nombre de personnes et le rapport automatique changent. Vous commencez par {{ $trialDays }} jours d'essai gratuit.</p>
                <div class="plans" style="--cols: {{ min(3, $plans->count()) }};">
                    @foreach ($plans as $i => $plan)
                        @php $featured = $plans->count() >= 3 && $i === 1; @endphp
                        <div class="card plan reveal {{ $featured ? 'featured' : '' }} {{ 'd' . min(3, $i) }}">
                            @if ($featured) <span class="badge-pop">RECOMMANDÉ</span> @endif
                            <h3 style="font-size: 22px; margin: 0;">{{ $plan->name }}</h3>
                            <p style="font-size: 14px; margin: 6px 0 0;">{{ $plan->description }}</p>
                            <div class="price">{{ number_format($plan->monthly_price, 0, ',', ' ') }} <span style="font-size: 16px;">FCFA</span></div>
                            <div class="per">par mois</div>
                            <ul>
                                <li>{{ $plan->usersLabel() }}</li>
                                <li>Commandes, comptoir et caisse</li>
                                <li>Espèces et Mobile Money</li>
                                <li>Crédits clients et stock</li>
                                <li>Tableau de bord et exports</li>
                                @if ($plan->includes_auto_report)
                                    <li><strong>Rapport du soir automatique sur WhatsApp</strong></li>
                                @else
                                    <li>Rapport du soir à envoyer d'un clic</li>
                                @endif
                            </ul>
                            <a href="{{ route('demo.request') }}" class="{{ $featured ? 'btn-primary' : 'btn-secondary-outline' }}">Demander une démo</a>
                        </div>
                    @endforeach
                </div>
                <p class="center reveal" style="font-size: 14px; color: var(--color-text-secondary); margin-top: 26px;">
                    Vous payez d'avance par Mobile Money, sans engagement. Vous arrêtez quand vous voulez.
                </p>
            </section></div>
        </div>
    @endif

    {{-- ================= FAQ ================= --}}
    <section id="questions">
        <div class="center reveal"><span class="eyebrow">Questions</span></div>
        <h2 class="reveal">Vos questions</h2>
        <p class="lead reveal">Ce que les patrons nous demandent le plus souvent.</p>
        <div class="faq reveal">
            <details><summary>Je dois acheter du matériel ?</summary><p>Non. L'application marche sur les téléphones Android ou iPhone que votre équipe a déjà, sur une tablette ou sur un ordinateur.</p></details>
            <details><summary>Et s'il n'y a pas internet ?</summary><p>L'application a besoin d'internet pendant le service. La 3G ou la 4G du téléphone suffit.</p></details>
            <details><summary>Mon petit maquis, c'est juste moi. Ça marche quand même ?</summary><p>Oui. Si vous êtes seul, le même écran sert à prendre la commande, encaisser et servir. Vous ajoutez du personnel quand vous voulez.</p></details>
            <details><summary>Mes serveuses vont-elles s'en sortir ?</summary><p>Oui. Elles choisissent la table, touchent les boissons et envoient. On forme votre équipe le jour de l'installation.</p></details>
            <details><summary>Mes chiffres sont-ils protégés ?</summary><p>Chaque maquis ne voit que ses propres données. Dans l'équipe, chacun n'ouvre que les écrans de son rôle. Les mots de passe sont chiffrés. Détails dans notre <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>.</p></details>
            <details><summary>Et si je ne paie pas à temps ?</summary><p>Vous êtes prévenu {{ \App\Models\Restaurant::REMINDER_DAYS }} jours avant, puis vous avez {{ \App\Models\Restaurant::GRACE_DAYS }} jours de tolérance. Ensuite, votre maquis passe en lecture seule. Vous voyez toujours vos chiffres et rien n'est supprimé. Tout revient dès le paiement.</p></details>
        </div>
    </section>

    {{-- ================= APPEL FINAL ================= --}}
    <div class="final reveal">
        <h2>Prêt à savoir où passe votre argent ?</h2>
        <p>Demandez une démo. On vous appelle, on installe votre maquis avec vous, puis vous l'essayez {{ $trialDays }} jours gratuitement.</p>
        <div class="ctas" style="justify-content: center;">
            <a href="{{ route('demo.request') }}" class="btn-primary">Demander une démo gratuite →</a>
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn-secondary-outline" style="background: transparent; color: white; border-color: rgba(255,255,255,.35);">Nous écrire sur WhatsApp</a>
            @endif
        </div>
    </div>

    {{-- Barre d'installation fixe (téléphone uniquement) --}}
    <div class="app-sticky" id="installSticky" role="region" aria-label="Installer l'application">
        <span class="app-dl-ico">
            <svg class="dl-arrow" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7 10.5l5 5 5-5"/></svg>
            <svg class="dl-tray" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M5 20h14"/></svg>
        </span>
        <span class="txt"><strong>Maquis Express sur votre téléphone</strong><small>Gratuit, sans Play Store</small></span>
        <button type="button" class="go" id="installStickyGo">Installer</button>
        <button type="button" class="close" id="installStickyClose" aria-label="Fermer"><x-icon name="x" size="18" /></button>
    </div>

    <script>
        (function () {
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // 1. Apparition au défilement
            var items = document.querySelectorAll('.lp .reveal');
            if (reduce || !('IntersectionObserver' in window)) {
                items.forEach(function (el) { el.classList.add('in'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
                }, { threshold: 0.12 });
                items.forEach(function (el) { io.observe(el); });
            }

            // 2. Le rapport WhatsApp qui « s'écrit »
            var typing = document.getElementById('waTyping');
            var bubble = document.getElementById('waBubble');
            var lines = bubble ? bubble.querySelectorAll('.line') : [];
            function showReport() {
                typing.style.display = 'none';
                bubble.style.display = 'block';
                lines.forEach(function (l, i) { setTimeout(function () { l.classList.add('on'); }, reduce ? 0 : i * 110); });
            }
            setTimeout(showReport, reduce ? 0 : 1300);

            // 2 bis. Bouton « Installer l'application mobile »
            (function () {
                var btn = document.getElementById('installAppBtn');
                var help = document.getElementById('installAppHelp');
                if (!btn) return;

                var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
                var isMobile = /android|iphone|ipad|ipod/i.test(navigator.userAgent);

                if (standalone) {
                    document.getElementById('installAppSmall').textContent = 'C\'est fait';
                    document.getElementById('installAppLabel').textContent = 'Application installée ✓';
                    btn.disabled = true;
                    return;
                }

                function showHelp(html) { help.innerHTML = html; help.hidden = false; }

                btn.addEventListener('click', function () {
                    if (window.__installPrompt) {
                        window.__installPrompt.prompt();
                        window.__installPrompt.userChoice.finally(function () { window.__installPrompt = null; });
                        return;
                    }
                    if (!help.hidden) { help.hidden = true; return; }
                    if (isIos) {
                        showHelp('<strong>Sur iPhone, dans Safari :</strong><ol><li>Touchez <strong>Partager</strong> ⬆️ en bas de l\'écran.</li><li>Choisissez <strong>« Sur l\'écran d\'accueil »</strong>.</li><li>L\'icône Maquis Express apparaît sur votre téléphone.</li></ol>');
                    } else if (isMobile) {
                        showHelp('<strong>Sur Android, dans Chrome :</strong><ol><li>Touchez le menu <strong>⋮</strong> en haut à droite.</li><li>Choisissez <strong>« Installer l\'application »</strong> ou <strong>« Ajouter à l\'écran d\'accueil »</strong>.</li><li>L\'icône Maquis Express apparaît sur votre téléphone.</li></ol>');
                    } else {
                        showHelp('<strong>Ouvrez ce site sur votre téléphone</strong> (Chrome sur Android, Safari sur iPhone), puis touchez ce bouton : l\'application s\'installe comme une vraie application, avec son icône, sans passer par le Play Store.');
                    }
                });

                // Barre fixe sur téléphone : apparaît après quelques secondes, se ferme avec ✕
                var sticky = document.getElementById('installSticky');
                function hideSticky() { if (sticky) sticky.classList.remove('show'); }
                if (sticky) {
                    var dismissed = false;
                    try { dismissed = sessionStorage.getItem('me-sticky-off') === '1'; } catch (e) {}
                    if (!dismissed) setTimeout(function () { sticky.classList.add('show'); }, 2500);
                    document.getElementById('installStickyClose').addEventListener('click', function () {
                        hideSticky();
                        try { sessionStorage.setItem('me-sticky-off', '1'); } catch (e) {}
                    });
                    document.getElementById('installStickyGo').addEventListener('click', function () {
                        hideSticky();
                        btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        btn.click();
                    });
                }

                window.addEventListener('appinstalled', function () {
                    document.getElementById('installAppLabel').textContent = 'Application installée ✓';
                    help.hidden = true;
                    hideSticky();
                });
            })();

            // 3. Présentoir : onglets + défilement automatique
            var showcase = document.getElementById('showcase');
            if (!showcase) return;
            var tabs = Array.prototype.slice.call(showcase.querySelectorAll('.tab'));
            var current = 0, timer = null;

            function activate(i) {
                current = i;
                tabs.forEach(function (t, k) {
                    var on = k === i;
                    t.classList.toggle('active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    document.getElementById(t.dataset.panel).classList.toggle('active', on);
                });
                // relance la barre de progression
                var bar = tabs[i].querySelector('.bar');
                bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = '';
            }
            function startAuto() {
                if (reduce) return;
                stopAuto();
                timer = setInterval(function () { activate((current + 1) % tabs.length); }, 6000);
            }
            function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }

            tabs.forEach(function (t, i) {
                t.addEventListener('click', function () {
                    activate(i);
                    stopAuto();
                    showcase.classList.add('paused'); // l'utilisateur a choisi : on arrête le défilement
                });
            });
            showcase.addEventListener('mouseenter', function () { if (!showcase.classList.contains('paused')) stopAuto(); });
            showcase.addEventListener('mouseleave', function () { if (!showcase.classList.contains('paused')) startAuto(); });
            startAuto();
        })();
    </script>
</div>
