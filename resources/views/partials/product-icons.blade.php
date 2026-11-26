{{--
    Dessins des produits (une seule fois par page). Chaque <x-product-icon> s'en sert
    avec sa couleur (--c). Dessins maison, sans marque : utilisables sans droits.
--}}
<svg width="0" height="0" style="position: absolute; overflow: hidden;" aria-hidden="true" focusable="false">
    <defs>
        <symbol id="pi-biere" viewBox="0 0 64 64">
            <path d="M27.5 7l1-4h7l1 4z" fill="#C9A227"/>
            <path d="M28.5 7h7v12c0 4 8.5 6.5 8.5 14v23a3 3 0 0 1-3 3H23a3 3 0 0 1-3-3V33c0-7.5 8.5-10 8.5-14z" style="fill: var(--c)"/>
            <rect x="35.5" y="34" width="8.5" height="25" rx="3" fill="#000" opacity=".16"/>
            <rect x="33.5" y="7" width="2" height="12" fill="#000" opacity=".16"/>
            <rect x="20" y="38" width="24" height="13" rx="1.5" fill="#F6EEDB"/>
            <rect x="24" y="42.5" width="16" height="2.4" rx="1.2" style="fill: var(--c)" opacity=".7"/>
            <rect x="27" y="46.5" width="10" height="1.8" rx=".9" style="fill: var(--c)" opacity=".7"/>
            <rect x="28.5" y="11" width="7" height="3.5" rx=".8" fill="#F6EEDB"/>
        </symbol>
        <symbol id="pi-grande" viewBox="0 0 64 64">
            <path d="M28 6l1-4h6l1 4z" fill="#C9A227"/>
            <path d="M29 6h6v10c0 4 11 6 11 15v26a3 3 0 0 1-3 3H21a3 3 0 0 1-3-3V31c0-9 11-11 11-15z" style="fill: var(--c)"/>
            <rect x="37" y="32" width="9" height="28" rx="3" fill="#000" opacity=".16"/>
            <rect x="33" y="6" width="2" height="10" fill="#000" opacity=".16"/>
            <rect x="18" y="36" width="28" height="15" rx="1.5" fill="#F6EEDB"/>
            <rect x="22.5" y="40.5" width="19" height="2.6" rx="1.3" style="fill: var(--c)" opacity=".7"/>
            <text x="32" y="48.5" font-family="Inter, sans-serif" font-weight="800" font-size="5.5" text-anchor="middle" style="fill: var(--c)" opacity=".8">65 cl</text>
            <rect x="29" y="9" width="6" height="3.5" rx=".8" fill="#F6EEDB"/>
        </symbol>
        <symbol id="pi-canette" viewBox="0 0 64 64">
            <path d="M23 10.5l2-4h14l2 4z" fill="#BAC4C0"/>
            <path d="M23 55.5l2 3.5h14l2-3.5z" fill="#BAC4C0"/>
            <rect x="21" y="10" width="22" height="46" rx="3" style="fill: var(--c)"/>
            <rect x="36" y="10" width="7" height="46" rx="3" fill="#000" opacity=".16"/>
            <path d="M21 29c7-3.5 15 3.5 22 0v8c-7 3.5-15-3.5-22 0z" fill="#fff"/>
        </symbol>
        <symbol id="pi-sucrerie" viewBox="0 0 64 64">
            <path d="M27.5 7l1-4h7l1 4z" fill="#C9A227"/>
            <path d="M28.5 7h7v6c0 3 5 4 5 9 0 4-3 5-3 8s4 4 4 9v17a3 3 0 0 1-3 3h-13a3 3 0 0 1-3-3V39c0-5 4-6 4-9s-3-4-3-8c0-5 5-6 5-9z" style="fill: var(--c)"/>
            <rect x="35" y="40" width="6.5" height="19" rx="3" fill="#000" opacity=".16"/>
            <rect x="22.5" y="44" width="19" height="9" rx="1.5" fill="#F6EEDB"/>
            <rect x="26" y="47.5" width="12" height="2" rx="1" style="fill: var(--c)" opacity=".7"/>
        </symbol>
        <symbol id="pi-eau" viewBox="0 0 64 64">
            <rect x="27" y="3" width="10" height="5.5" rx="1.5" fill="#2F6FB5"/>
            <path d="M28 8.5h8v2.5c5 1 8 4 8 9v36a3 3 0 0 1-3 3H23a3 3 0 0 1-3-3V20c0-5 3-8 8-9z" style="fill: var(--c)"/>
            <rect x="37" y="22" width="7" height="37" rx="3" fill="#000" opacity=".1"/>
            <rect x="20" y="33" width="24" height="10" fill="#2F6FB5"/>
            <path d="M21 25h22M21 50h22" stroke="#7FB2DE" stroke-width="2.4" stroke-linecap="round" fill="none"/>
        </symbol>
        <symbol id="pi-jus" viewBox="0 0 64 64">
            <path d="M37 16V5l6-2.5" stroke="#8A9993" stroke-width="2.4" stroke-linecap="round" fill="none"/>
            <path d="M21 21l3-8h16l3 8v35a3 3 0 0 1-3 3H24a3 3 0 0 1-3-3z" style="fill: var(--c)"/>
            <rect x="36" y="21" width="7" height="38" rx="3" fill="#000" opacity=".16"/>
            <path d="M21 21h22v3H21z" fill="#fff"/>
            <circle cx="32" cy="40" r="8.5" fill="#fff"/>
            <circle cx="32" cy="40" r="5" style="fill: var(--c)" opacity=".7"/>
            <path d="M32 31.5c2-3 5-3 6-2-1 2-3 3-6 2z" fill="#3E9A4B"/>
        </symbol>
        <symbol id="pi-vin" viewBox="0 0 64 64">
            <rect x="28.5" y="3" width="7" height="9" rx="1" fill="#3B1420"/>
            <path d="M29 6h6v12c0 3 6 5 6 11v27a3 3 0 0 1-3 3H26a3 3 0 0 1-3-3V29c0-6 6-8 6-11z" style="fill: var(--c)"/>
            <rect x="35" y="30" width="6" height="29" rx="3" fill="#000" opacity=".16"/>
            <rect x="23" y="37" width="18" height="13" rx="1.5" fill="#F6EEDB"/>
            <rect x="26" y="41.5" width="12" height="2.2" rx="1.1" style="fill: var(--c)" opacity=".7"/>
        </symbol>
        <symbol id="pi-verre" viewBox="0 0 64 64">
            <path d="M15 17h34l-3.4 37.5a4 4 0 0 1-4 3.5H22.4a4 4 0 0 1-4-3.5z" fill="#E3ECEF"/>
            <path d="M17 33h30l-1.9 21.4a4 4 0 0 1-4 3.6H22.9a4 4 0 0 1-4-3.6z" style="fill: var(--c)"/>
            <rect x="23" y="26" width="10" height="10" rx="2.2" fill="#fff" transform="rotate(-14 28 31)"/>
            <rect x="32" y="29" width="9" height="9" rx="2" fill="#fff" transform="rotate(10 36 33)"/>
        </symbol>
        <symbol id="pi-gobelet" viewBox="0 0 64 64">
            <path d="M36 14l5-10" stroke="#8A9993" stroke-width="2.6" stroke-linecap="round" fill="none"/>
            <path d="M18 16h28l-3.5 39a4 4 0 0 1-4 3.6H25.5a4 4 0 0 1-4-3.6z" style="fill: var(--c)"/>
            <rect x="37" y="16" width="9" height="42.5" rx="3" fill="#000" opacity=".14"/>
            <path d="M17 16h30" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
            <path d="M22 32h20" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".85"/>
        </symbol>
        <symbol id="pi-plat" viewBox="0 0 64 64">
            <circle cx="32" cy="33" r="26" fill="#E1E7E4"/>
            <circle cx="32" cy="33" r="20" fill="#fff"/>
            <path d="M36 27l9-9" stroke="#EFE4CC" stroke-width="4.5" stroke-linecap="round" fill="none"/>
            <circle cx="46.5" cy="15.5" r="3" fill="#F6EEDB"/>
            <circle cx="48.5" cy="19" r="3" fill="#F6EEDB"/>
            <path d="M20 33c-2-7 4-13 11-12 6 1 8 7 5 11-3 5-11 8-16 1z" style="fill: var(--c)"/>
            <path d="M24 26l6 5M23 31l6 4" stroke="#7A3E10" stroke-width="1.6" stroke-linecap="round" opacity=".6" fill="none"/>
            <circle cx="37" cy="42" r="3.4" fill="#E8B33A"/>
            <circle cx="42" cy="39" r="3.4" fill="#E8B33A"/>
            <circle cx="32" cy="45" r="3" fill="#E8B33A"/>
        </symbol>
    </defs>
</svg>
<style>
    /* Petite image d'un produit : un rond pâle, le dessin ou la vraie photo */
    .pi { display: inline-grid; place-items: center; flex: none; border-radius: 50%; background: #EEF4F1; overflow: hidden; vertical-align: middle; }
    .pi svg { width: 82%; height: 82%; display: block; }
    .pi img { width: 100%; height: 100%; object-fit: cover; display: block; }
</style>
