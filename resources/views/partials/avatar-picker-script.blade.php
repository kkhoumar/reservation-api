{{--
    Choix d'une photo de profil : le téléphone la recadre en carré et la réduit
    à 320 px (environ 20 Ko) avant l'envoi. Rapide même en 3G.
    Utilisation : x-data="avatarPicker('savePhoto')"  (méthode Livewire qui reçoit l'image)
--}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('avatarPicker', (method, ...args) => ({
            busy: false,
            error: '',

            pick() { this.$refs.file.click(); },

            async chosen(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                if (!/^image\//.test(file.type)) { this.error = 'Choisissez une photo.'; return; }
                if (file.size > 15 * 1024 * 1024) { this.error = 'Photo trop lourde, choisissez-en une autre.'; return; }

                this.busy = true;
                this.error = '';
                try {
                    const dataUrl = await this.shrink(file, 320);
                    const message = await this.$wire[method](...args, dataUrl);
                    if (message) this.error = message;
                } catch (e) {
                    this.error = 'La photo n\'a pas pu être envoyée. Réessayez.';
                }
                this.busy = false;
            },

            shrink(file, size) {
                return new Promise((resolve, reject) => {
                    const url = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = () => {
                        const side = Math.min(img.naturalWidth, img.naturalHeight);
                        const sx = (img.naturalWidth - side) / 2, sy = (img.naturalHeight - side) / 2;
                        const canvas = document.createElement('canvas');
                        canvas.width = size; canvas.height = size;
                        const ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, size, size);
                        ctx.imageSmoothingQuality = 'high';
                        ctx.drawImage(img, sx, sy, side, side, 0, 0, size, size);
                        URL.revokeObjectURL(url);
                        resolve(canvas.toDataURL('image/jpeg', 0.85));
                    };
                    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('image')); };
                    img.src = url;
                });
            },
        }));
    });
</script>
<script>
    // Nouvelle photo dans « Mon profil » : la pastille du haut change tout de suite
    window.addEventListener('avatar-updated', (e) => {
        const url = e.detail && e.detail.url;
        if (!url) { location.reload(); return; }
        document.querySelectorAll('.me-profile-link .me-avatar').forEach((el) => {
            el.innerHTML = '<img src="' + url + '" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">';
        });
    });
</script>
<script>
    // Photo de preuve (perte, casse) : prise avec l'appareil photo, réduite à 1280 px (environ 100 Ko)
    document.addEventListener('alpine:init', () => {
        Alpine.data('photoCapture', (model) => ({
            preview: '',
            busy: false,
            error: '',
            pick() { this.$refs.file.click(); },
            clear() { this.preview = ''; this.$wire.$set(model, '', false); },
            async chosen(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                if (!/^image\//.test(file.type)) { this.error = 'Choisissez une photo.'; return; }
                this.busy = true; this.error = '';
                try {
                    const dataUrl = await new Promise((resolve, reject) => {
                        const url = URL.createObjectURL(file);
                        const img = new Image();
                        img.onload = () => {
                            const max = 1280, ratio = Math.min(1, max / Math.max(img.naturalWidth, img.naturalHeight));
                            const w = Math.round(img.naturalWidth * ratio), h = Math.round(img.naturalHeight * ratio);
                            const canvas = document.createElement('canvas'); canvas.width = w; canvas.height = h;
                            const ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
                            ctx.drawImage(img, 0, 0, w, h);
                            URL.revokeObjectURL(url);
                            resolve(canvas.toDataURL('image/jpeg', 0.72));
                        };
                        img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('image')); };
                        img.src = url;
                    });
                    this.preview = dataUrl;
                    this.$wire.$set(model, dataUrl, false);
                } catch (e) {
                    this.error = 'La photo n\'a pas pu être lue. Reprenez-la.';
                }
                this.busy = false;
            },
        }));
    });
</script>
