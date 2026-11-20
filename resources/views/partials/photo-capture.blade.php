{{--
    Prise de photo de preuve (reçu, bon de livraison, perte).
    @include('partials.photo-capture', ['model' => 'receiptPhoto', 'label' => 'Photo du reçu', 'hint' => 'Obligatoire'])
--}}
<div x-data="photoCapture('{{ $model }}')" style="margin-bottom: 14px;">
    <label style="font-size: 13px; font-weight: 700; display: block; margin-bottom: 6px;">{{ $label }}</label>
    <input type="file" accept="image/*" capture="environment" x-ref="file" @change="chosen($event)" hidden>
    <template x-if="!preview">
        <button type="button" @click="pick()" :disabled="busy"
                style="width: 100%; border: 2px dashed var(--color-border); background: var(--color-bg); border-radius: 14px; padding: 16px 14px; display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; font: inherit; font-weight: 700; color: var(--color-text);">
            <x-icon name="phone" size="22" />
            <span x-text="busy ? 'Préparation de la photo…' : 'Prendre une photo'"></span>
            <small style="font-weight: 500; color: var(--color-text-secondary); font-size: 12.5px; text-align: center;">{{ $hint }}</small>
        </button>
    </template>
    <template x-if="preview">
        <div style="display: flex; align-items: center; gap: 12px;">
            <img :src="preview" alt="{{ $label }}" style="width: 88px; height: 88px; object-fit: cover; border-radius: 12px; border: 1px solid var(--color-border);">
            <button type="button" @click="pick()" style="border: 1px solid var(--color-border); background: #fff; border-radius: 999px; padding: 6px 12px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer;">Reprendre la photo</button>
        </div>
    </template>
    <div x-show="error" x-text="error" x-cloak style="color: var(--color-danger); font-size: 13px; margin-top: 6px;"></div>
    @error($model) <div style="color: var(--color-danger); font-size: 13px; margin-top: 6px;">{{ $message }}</div> @enderror
</div>