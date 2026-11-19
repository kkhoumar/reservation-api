{{-- Design commun aux écrans de comptage du stock (.sc) --}}
<style>
    [x-cloak] { display: none !important; }
    .sc { --sc-ok: var(--color-success); --sc-ok-bg: var(--color-success-bg); --sc-miss: var(--color-danger); --sc-miss-bg: var(--color-danger-bg); --sc-over: #2463B5; --sc-over-bg: #EAF1FB; padding-bottom: 96px; }
    .sc .eyebrow { font-size: 12px; font-weight: 700; color: var(--color-accent); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
    .sc h1 { margin: 0; }
    .sc .lead { font-size: 14px; color: var(--color-text-secondary); margin: 6px 0 0; line-height: 1.5; max-width: 640px; }
    .sc .muted { color: var(--color-text-secondary); }
    .sc .small { font-size: 12.5px; }

    /* --- En-tête : dernier comptage + période --- */
    .sc-top { display: grid; grid-template-columns: 1fr 1.4fr; gap: 14px; margin: 18px 0; }
    .sc-card { background: #fff; border: 1px solid var(--color-border); border-radius: 16px; padding: 16px 18px; min-width: 0; }
    .sc-card h3 { margin: 0 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; color: var(--color-text-secondary); display: flex; align-items: center; gap: 8px; }
    .sc-last { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
    .sc-last .when { font-size: 18px; font-weight: 800; line-height: 1.25; }
    .sc-pill { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: 13px; font-weight: 700; white-space: nowrap; }
    .sc-pill.ok { background: var(--sc-ok-bg); color: var(--sc-ok); }
    .sc-pill.warn { background: var(--color-warning-bg); color: var(--color-warning); }
    .sc-pill.danger { background: var(--sc-miss-bg); color: var(--sc-miss); }
    .sc-moves { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
    .sc-move { background: var(--color-bg); border-radius: 12px; padding: 10px 12px; }
    .sc-move strong { display: block; font-size: 20px; line-height: 1.1; }
    .sc-move span { font-size: 12px; color: var(--color-text-secondary); }
    .sc-team { display: flex; flex-wrap: wrap; gap: 6px; }
    .sc-team span { font-size: 12.5px; background: var(--color-mint); color: var(--color-primary); padding: 4px 10px; border-radius: 999px; font-weight: 600; }
    .sc-warn { display: flex; gap: 10px; align-items: flex-start; background: var(--color-warning-bg); color: #6B4A12; border-radius: 12px; padding: 12px 14px; font-size: 14px; margin-bottom: 16px; line-height: 1.45; }
    .sc-warn a { color: inherit; font-weight: 700; }

    /* --- Barre de filtres --- */
    .sc-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
    .sc-seg { display: inline-flex; background: #fff; border: 1px solid var(--color-border); border-radius: 12px; padding: 3px; gap: 2px; }
    .sc-seg button { border: 0; background: none; font: inherit; font-size: 13.5px; font-weight: 600; padding: 7px 12px; border-radius: 9px; cursor: pointer; color: var(--color-text-secondary); white-space: nowrap; }
    .sc-seg button.on { background: var(--color-primary); color: #fff; }
    .sc-progress { flex: 1 1 220px; max-width: 340px; }
    .sc-progress .track { height: 8px; background: var(--color-border); border-radius: 99px; overflow: hidden; margin-top: 5px; }
    .sc-progress .fill { height: 100%; background: var(--color-primary-300); border-radius: 99px; transition: width .25s; }

    /* --- Sections et lignes produit --- */
    .sc-section { margin-bottom: 22px; }
    .sc-section-head { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin: 0 0 8px; padding: 0 2px; }
    .sc-section-head h2 { font-size: 18px; margin: 0; }
    .sc-list { background: #fff; border: 1px solid var(--color-border); border-radius: 16px; overflow: hidden; }
    .sc-row { display: grid; grid-template-columns: minmax(0, 1fr) 104px auto auto; gap: 6px 16px; align-items: center; padding: 14px 18px; border-top: 1px solid var(--color-border); transition: background .2s; }
    .sc-row:first-child { border-top: 0; }
    .sc-row > * { min-width: 0; }
    .sc-row.ok { background: linear-gradient(90deg, var(--sc-ok-bg), #fff 60%); }
    .sc-row.miss { background: linear-gradient(90deg, var(--sc-miss-bg), #fff 60%); }
    .sc-row.over { background: linear-gradient(90deg, var(--sc-over-bg), #fff 60%); }
    .sc-name { font-weight: 700; font-size: 15.5px; }
    .sc-name .cat { font-weight: 500; font-size: 12px; color: var(--color-text-secondary); margin-left: 4px; }
    .sc-calc { font-size: 12.5px; color: var(--color-text-secondary); margin-top: 3px; line-height: 1.5; }
    .sc-calc .in { color: var(--sc-ok); font-weight: 600; }
    .sc-calc .out { color: var(--color-text); font-weight: 600; }
    .sc-tag { display: inline-block; font-size: 11px; font-weight: 700; color: var(--color-warning); background: var(--color-warning-bg); padding: 1px 7px; border-radius: 6px; margin-left: 4px; vertical-align: 1px; }
    .sc-expected { text-align: center; background: var(--color-bg); border-radius: 12px; padding: 7px 6px; }
    .sc-expected small { display: block; font-size: 11px; color: var(--color-text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    .sc-expected strong { font-size: 22px; line-height: 1.15; }
    .sc-input { display: flex; align-items: center; gap: 6px; justify-content: flex-end; }
    .sc-step { width: 40px; height: 44px; border-radius: 11px; border: 1px solid var(--color-border); background: #fff; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--color-text); flex-shrink: 0; }
    .sc-input input { width: 68px; height: 44px; text-align: center; font-size: 19px; font-weight: 800; border-radius: 11px; padding: 0 4px; }
    .sc-exact { height: 44px; border-radius: 11px; border: 1px solid var(--color-primary-300); background: #fff; color: var(--color-primary); font: inherit; font-size: 13px; font-weight: 700; padding: 0 10px; cursor: pointer; white-space: nowrap; display: inline-flex; align-items: center; gap: 5px; }
    .sc-exact.on { background: var(--sc-ok); border-color: var(--sc-ok); color: #fff; }
    .sc-result { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .sc-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; padding: 5px 11px; border-radius: 999px; }
    .sc-badge.ok { background: var(--sc-ok-bg); color: var(--sc-ok); }
    .sc-badge.miss { background: var(--sc-miss-bg); color: var(--sc-miss); }
    .sc-badge.over { background: var(--sc-over-bg); color: var(--sc-over); }
    .sc-reasons { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
    .sc-reasons > span { font-size: 12.5px; color: var(--color-text-secondary); }
    .sc-chip { border: 1px solid var(--color-border); background: #fff; border-radius: 999px; padding: 5px 11px; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; color: var(--color-text); }
    .sc-chip.on { background: var(--color-text); border-color: var(--color-text); color: #fff; }

    /* --- Sous-total de section --- */
    .sc-sub { display: flex; flex-wrap: wrap; gap: 6px 20px; justify-content: space-between; padding: 12px 18px; background: var(--color-bg); border-top: 1px solid var(--color-border); font-size: 13.5px; }
    .sc-sub strong { white-space: nowrap; }

    /* --- Résumé final --- */
    .sc-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 14px; }
    .sc-sum { background: #fff; border: 1px solid var(--color-border); border-radius: 16px; padding: 16px 18px; }
    .sc-sum.total { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
    .sc-sum.total, .sc-sum.total h3, .sc-sum.total h3 span { color: #fff; }
    .sc-sum.total .muted { color: rgba(255,255,255,.72); }
    .sc-sum h3 { margin: 0 0 10px; font-size: 16px; display: flex; justify-content: space-between; gap: 8px; align-items: baseline; }
    .sc-sum h3 span { font-size: 12.5px; font-weight: 600; }
    .sc-sum .line { display: flex; justify-content: space-between; gap: 10px; padding: 6px 0; font-size: 14px; border-top: 1px dashed rgba(127,140,135,.3); }
    .sc-sum .line:first-of-type { border-top: 0; }
    .sc-sum .line strong { white-space: nowrap; }
    .sc-sum .gap { margin-top: 8px; padding: 10px 12px; border-radius: 12px; display: flex; justify-content: space-between; gap: 10px; font-weight: 800; font-size: 15px; }
    .sc-sum .gap.ok { background: var(--sc-ok-bg); color: var(--sc-ok); }
    .sc-sum .gap.miss { background: var(--sc-miss-bg); color: var(--sc-miss); }
    .sc-sum .gap.over { background: var(--sc-over-bg); color: var(--sc-over); }
    .sc-sum .gap.none { background: var(--color-bg); color: var(--color-text-secondary); font-weight: 600; }
    .sc-sum.total .gap.none { background: rgba(255,255,255,.1); color: rgba(255,255,255,.8); }
    .sc-sum.total .gap.ok { background: rgba(94,191,160,.22); color: #BFF0DE; }
    .sc-sum.total .gap.miss { background: rgba(217,59,59,.25); color: #FFD3D3; }
    .sc-sum.total .gap.over { background: rgba(36,99,181,.3); color: #D5E5FA; }
    .sc-validate { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: end; }
    .sc-validate input { width: 100%; }
    .sc-error { background: var(--sc-miss-bg); color: var(--sc-miss); border-radius: 12px; padding: 10px 14px; font-size: 14px; font-weight: 600; margin-top: 12px; }

    /* --- Barre collante en bas --- */
    .sc-sticky { position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; background: rgba(255,255,255,.97); backdrop-filter: blur(6px); border-top: 1px solid var(--color-border); box-shadow: 0 -8px 24px rgba(11,77,60,.08); padding: 10px 16px calc(10px + env(safe-area-inset-bottom)); }
    .sc-sticky-in { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .sc-sticky .info { font-size: 13px; line-height: 1.35; min-width: 0; }
    .sc-sticky .info strong { font-size: 15px; }

    /* --- Historique --- */
    .sc-hist a { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 18px; border-top: 1px solid var(--color-border); color: inherit; text-decoration: none; }
    .sc-hist a:first-child { border-top: 0; }
    .sc-hist a:hover { background: var(--color-bg); }
    .sc-hist .d { font-weight: 700; font-size: 14.5px; }
    .sc-hist .r { text-align: right; white-space: nowrap; font-weight: 800; font-size: 14.5px; }

    @media (min-width: 861px) { .sc-sticky { left: var(--sidebar-width, 0px); } }
    @media (max-width: 900px) {
        .sc-top { grid-template-columns: 1fr; }
        .sc-summary { grid-template-columns: 1fr; }
    }
    @media (max-width: 760px) {
        .sc-row { grid-template-columns: minmax(0, 1fr) auto auto; padding: 14px; gap: 10px; }
        .sc-info { grid-column: 1 / -1; }
        .sc-expected { display: flex; align-items: baseline; gap: 6px; padding: 8px 12px; text-align: left; }
        .sc-expected small { display: inline; }
        .sc-expected strong { font-size: 20px; }
        .sc-sub { padding: 12px 14px; }
        .sc-validate { grid-template-columns: 1fr; }
        .sc-validate .btn { width: 100%; justify-content: center; }
    }
    @media (max-width: 420px) {
        .sc-row { grid-template-columns: minmax(0, 1fr) auto; }
        .sc-expected { grid-column: 1; grid-row: 2; }
        .sc-exact { grid-column: 2; grid-row: 2; height: auto; align-self: stretch; padding: 0 16px; }
        .sc-input { grid-column: 1 / -1; grid-row: 3; justify-content: stretch; }
        .sc-step { width: 52px; height: 48px; }
        .sc-input input { flex: 1; width: auto; min-width: 0; height: 48px; font-size: 21px; }
        .sc-moves strong { font-size: 18px; }
        .sc-seg button { padding: 7px 10px; }
        .sc-bar .sc-seg { width: 100%; }
        .sc-bar .sc-seg button { flex: 1; }
    }
    /* --- Vides (bouteilles consignées) --- */
    .sc-empties { grid-column: 1 / -1; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #F2F7FD; border: 1px dashed #B9D2EE; border-radius: 12px; padding: 8px 12px; }
    .sc-empties .lbl { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #2463B5; }
    .sc-empties input { width: 84px; height: 40px; text-align: center; font-size: 17px; font-weight: 800; border-radius: 10px; }
    .sc-empties .exp { font-size: 12.5px; color: var(--color-text-secondary); }
    .sc-why { font-size: 12.5px; color: var(--color-text); line-height: 1.45; }
    .sc-why b { color: var(--color-danger); }

    /* --- Finitions --- */
    .sc { font-variant-numeric: tabular-nums; }
    .sc-card, .sc-list, .sc-sum, .st-kpi, .sc-seg, .st-tabs { box-shadow: 0 1px 2px rgba(16,35,29,.04), 0 6px 18px -8px rgba(16,35,29,.08); }
    .sc-sum.total, .st-kpi.hl { box-shadow: 0 10px 28px -10px rgba(11,77,60,.45); }
    .sc-info.with-ava { display: flex; align-items: center; gap: 12px; }
    .sc-txt { min-width: 0; }
    .sc-ava { width: 40px; height: 40px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .sc-ava.drink { background: #E6F0FA; color: #2463B5; }
    .sc-ava.food { background: #FCEFE6; color: var(--color-accent); }
    .sc-section-head h2 { display: flex; align-items: center; gap: 10px; }
    .sc-h-ico { width: 32px; height: 32px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; }
    .sc-h-ico.drink { background: #E6F0FA; color: #2463B5; }
    .sc-h-ico.food { background: #FCEFE6; color: var(--color-accent); }
    .sc-row, .st-row:not(.head), .sc-hist a, .st-move { transition: background-color .15s; }
    .st-row:not(.head):hover, .st-move:hover { background: #FAFCFB; }
    .sc-step { transition: background-color .15s, transform .1s; }
    .sc-step:hover { background: var(--color-bg); }
    .sc-step:active, .sc-exact:active, .sc-chip:active { transform: scale(.94); }
    .sc-input input:focus, .st-line input:focus { outline: none; border-color: var(--color-primary-300); box-shadow: 0 0 0 4px rgba(94,191,160,.22); }
    .sc-exact, .sc-chip { transition: background-color .15s, color .15s, border-color .15s, transform .1s; }
    .sc-exact:hover:not(.on) { background: var(--color-mint); }
    .sc-chip:hover:not(.on) { border-color: var(--color-text-secondary); }
    @media (max-width: 760px) {
        .sc-ava { width: 36px; height: 36px; border-radius: 11px; }
    }
</style>
