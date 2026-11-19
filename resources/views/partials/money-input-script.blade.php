{{-- Comportement du composant <x-money-input> (voir components/money-input.blade.php) --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('moneyInput', (model, live, empty) => ({
            timer: null,
            typedAt: 0,

            init() {
                this.$el.value = this.format(this.$wire.$get(model));
                // Valeur changée par le serveur (remise à zéro, montant proposé…) : on l'affiche.
                // Seule exception : un champ « live » pendant la frappe, car une réponse
                // en retard porterait un ancien montant (c'était le bug « ça s'efface »).
                this.$wire.$watch(model, (value) => {
                    const typing = live && document.activeElement === this.$el && Date.now() - this.typedAt < 1500;
                    if (!typing && this.digits(this.$el.value) !== this.digits(value)) {
                        this.$el.value = this.format(value);
                    }
                });
            },

            digits(value) {
                return String(value ?? '').replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 9);
            },

            format(value) {
                const d = this.digits(value);
                return d === '' ? '' : d.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            },

            onInput() {
                const el = this.$el;
                const caret = el.selectionStart ?? el.value.length;
                const digitsBeforeCaret = (el.value.slice(0, caret).match(/\d/g) || []).length;

                const d = this.digits(el.value);
                el.value = this.format(d);

                // Remettre le curseur après le même nombre de chiffres
                let pos = 0, seen = 0;
                while (pos < el.value.length && seen < digitsBeforeCaret) {
                    if (/\d/.test(el.value[pos])) seen++;
                    pos++;
                }
                try { el.setSelectionRange(pos, pos); } catch (e) {}

                this.typedAt = Date.now();
                const number = d === '' ? empty : parseInt(d, 10);

                // Toujours à jour côté navigateur (pris avec le formulaire)…
                this.$wire.$set(model, number, false);
                // …et envoyé au serveur une seule fois, quand on arrête de taper.
                if (live) {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.$wire.$set(model, number, true), 400);
                }
            },

            onBlur() {
                this.$el.value = this.format(this.$el.value);
            },
        }));
    });
</script>
