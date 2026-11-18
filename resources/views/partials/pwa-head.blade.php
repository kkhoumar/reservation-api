{{-- Application installable (PWA) : à inclure dans le <head> de chaque mise en page --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#0B4D3C">
<link rel="icon" type="image/png" sizes="64x64" href="/icons/favicon-64.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Maquis Express">
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {});
        });
    }
    // Android / Chrome : on garde la proposition d'installation pour notre bouton
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        window.__installPrompt = e;
        document.dispatchEvent(new Event('me:installable'));
    });
</script>
