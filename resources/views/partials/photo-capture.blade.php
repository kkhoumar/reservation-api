{{--
    Photo de preuve (reçu, bon de livraison).
    @include('partials.photo-capture', ['model' => 'receiptPhoto', 'label' => 'Photo du reçu', 'hint' => 'Obligatoire'])

    Deux boutons : « Prendre une photo » (appareil photo) et « Choisir dans le
    téléphone » (capture Wave / Orange Money, facture reçue par WhatsApp…).
    Le composant Livewire doit avoir {model}Source et {model}Date : l'application
    note d'où vient la photo, pour que le patron le voie.
--}}
<div class="pc" x-data="photoCapture('{{ $model }}', true)">
    <span class="pc-label">{{ $label }}</span>
    <input type="file" accept="image/*" capture="environment" x-ref="camera" @change="chosen($event, 'camera')" hidden>
    <input type="file" accept="image/*" x-ref="gallery" @change="chosen($event, 'gallery')" hidden>

    <template x-if="!preview">
        <div>
            <div class="pc-btns">
                <button type="button" class="pc-btn" @click="pick('camera')" :disabled="busy">
                    <x-icon name="camera" size="24" />
                    <span>Prendre une photo</span>
                </button>
                <button type="button" class="pc-btn" @click="pick('gallery')" :disabled="busy">
                    <x-icon name="image" size="24" />
                    <span>Choisir dans le téléphone</span>
                </button>
            </div>
            <small class="pc-hint" x-text="busy ? 'Préparation de la photo…' : @js($hint)">{{ $hint }}</small>
        </div>
    </template>

    <template x-if="preview">
        <div class="pc-done">
            <img :src="preview" alt="{{ $label }}">
            <div class="pc-done-txt">
                <span class="pc-origin" :class="old && 'old'">
                    <span x-text="originText()"></span>
                </span>
                <div class="pc-again">
                    <button type="button" @click="pick('camera')"><x-icon name="camera" size="16" /> Reprendre</button>
                    <button type="button" @click="pick('gallery')"><x-icon name="image" size="16" /> Autre photo</button>
                </div>
            </div>
        </div>
    </template>

    <div class="pc-err" x-show="error" x-text="error" x-cloak></div>
    @error($model) <div class="pc-err">{{ $message }}</div> @enderror
</div>

@once
<style>
    .pc { margin-bottom: 14px; }
    .pc-label { font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px; }
    .pc-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .pc-btn { min-height: 84px; border: 2px dashed var(--color-border); background: var(--color-bg); border-radius: 14px; padding: 12px 8px;
        display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer;
        font: inherit; font-weight: 700; font-size: 14px; line-height: 1.25; text-align: center; color: var(--color-text); -webkit-tap-highlight-color: transparent; }
    .pc-btn:active { transform: scale(.98); }
    .pc-btn:hover { border-color: var(--color-primary-300); }
    .pc-btn:focus-visible { outline: 3px solid var(--color-primary-300); outline-offset: 2px; }
    .pc-btn[disabled] { opacity: .6; cursor: wait; }
    .pc-btn svg { color: var(--color-primary); }
    .pc-hint { display: block; margin-top: 6px; font-size: 12.5px; color: var(--color-text-secondary); text-align: center; }
    .pc-done { display: flex; align-items: center; gap: 12px; }
    .pc-done img { width: 88px; height: 88px; flex: none; object-fit: cover; border-radius: 12px; border: 1px solid var(--color-border); }
    .pc-done-txt { min-width: 0; display: flex; flex-direction: column; gap: 8px; }
    .pc-origin { font-size: 12.5px; font-weight: 700; color: var(--color-success); }
    .pc-origin.old { color: #9A5B0C; }
    .pc-again { display: flex; flex-wrap: wrap; gap: 6px; }
    .pc-again button { display: inline-flex; align-items: center; gap: 5px; min-height: 36px; border: 1px solid var(--color-border); background: #fff;
        border-radius: 999px; padding: 6px 12px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--color-text); }
    .pc-err { color: var(--color-danger); font-size: 13px; margin-top: 6px; }
    /* Très petits écrans : les deux boutons l'un sous l'autre */
    @media (max-width: 330px) {
        .pc-btns { grid-template-columns: 1fr; }
        .pc-btn { min-height: 60px; flex-direction: row; }
    }
</style>
@endonce
