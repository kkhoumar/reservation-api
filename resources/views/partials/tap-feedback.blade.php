{{--
    Retour visuel immédiat sur téléphone :
    - le bouton touché réagit tout de suite (léger enfoncement) ;
    - pendant que le serveur répond, le bouton est grisé (pas de double appui)
      et une fine barre de chargement apparaît en haut de l'écran.
--}}
<style>
    button, .btn, .chip, a.btn { -webkit-tap-highlight-color: transparent; touch-action: manipulation; }
    button:active:not(:disabled), .btn:active, .chip:active { transform: scale(.97); }
    button[data-loading], a[data-loading], .btn[data-loading] { opacity: .6; cursor: progress; pointer-events: none; }
    #me-progress {
        position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 9999;
        background: linear-gradient(90deg, #D2622A, #F2A26F);
        box-shadow: 0 0 8px rgba(210,98,42,.5);
        opacity: 0; transition: width .4s ease, opacity .25s ease;
        pointer-events: none;
    }
    #me-progress.on { opacity: 1; width: 70%; transition: width 6s cubic-bezier(.1,.7,.2,1), opacity .15s ease; }
    #me-progress.done { opacity: 0; width: 100%; transition: width .2s ease, opacity .35s ease .15s; }
    @media (prefers-reduced-motion: reduce) {
        button:active, .btn:active, .chip:active { transform: none; }
    }
</style>
<div id="me-progress" aria-hidden="true"></div>
<script>
    document.addEventListener('livewire:init', function () {
        var bar = document.getElementById('me-progress');
        if (!bar || !window.Livewire || !Livewire.hook) return;
        var pending = 0, timer = null;

        function start() {
            pending++;
            if (timer || bar.classList.contains('on')) return;
            // Pas de barre pour les réponses très rapides
            timer = setTimeout(function () {
                timer = null;
                bar.classList.remove('done');
                void bar.offsetWidth;
                bar.classList.add('on');
            }, 150);
        }
        function stop() {
            pending = Math.max(0, pending - 1);
            if (pending) return;
            if (timer) { clearTimeout(timer); timer = null; return; }
            bar.classList.remove('on');
            bar.classList.add('done');
            setTimeout(function () { if (!pending) { bar.classList.remove('done'); bar.style.width = ''; } }, 600);
        }

        Livewire.hook('request', function (args) {
            start();
            var done = false;
            function finish() { if (!done) { done = true; stop(); } }
            if (args && args.respond) args.respond(finish);
            if (args && args.succeed) args.succeed(finish);
            if (args && args.fail) args.fail(finish);
        });
    });
</script>
