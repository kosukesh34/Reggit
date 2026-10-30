{{-- スタンプ画面のデザイン（カード画像の置き方）。会員アプリ（LINE・ネイティブ）の表示に使う --}}
@php($currentDesign = old('design', $stampCard?->design ?? \App\Models\StampCard::DESIGN_STANDARD))
<fieldset class="form-group stamp-form-col-span-2 stamp-design-field">
    <legend class="form-label">デザイン</legend>
    <div class="stamp-design-options">
        @foreach(\App\Models\StampCard::getDesignOptions() as $value => $label)
            <label class="stamp-design-option">
                <input type="radio" name="design" value="{{ $value }}" @checked($currentDesign === $value)>
                <span class="stamp-design-thumb is-{{ $value }}" aria-hidden="true"><i></i><b></b><b></b><b></b></span>
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('design')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</fieldset>
@once
@push('styles')
<style>
.stamp-design-field { margin: 0; padding: 0; border: 0; }
.stamp-design-options { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.stamp-design-option { display: grid; gap: 8px; padding: 10px; border: 1px solid #dbe4ee; border-radius: 12px; background: #fff; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; }
.stamp-design-option:has(input:checked) { border-color: #1d4ed8; box-shadow: 0 0 0 3px rgba(29, 78, 216, .15); }
.stamp-design-option:has(input:focus-visible) { outline: 3px solid rgba(29, 78, 216, .35); outline-offset: 2px; }
.stamp-design-option input { position: absolute; opacity: 0; pointer-events: none; }
.stamp-design-thumb { position: relative; display: flex; flex-wrap: wrap; align-content: flex-end; gap: 4px; height: 64px; padding: 6px; border-radius: 8px; background: #f1f5f9; }
.stamp-design-thumb i { display: block; background: #94a3b8; border-radius: 4px; }
.stamp-design-thumb b { width: 12px; height: 12px; border: 1.5px solid #94a3b8; border-radius: 50%; }
.stamp-design-thumb.is-standard i { position: absolute; top: 6px; left: 6px; width: 20px; height: 20px; }
.stamp-design-thumb.is-banner i { position: absolute; top: 6px; right: 6px; left: 6px; height: 26px; }
.stamp-design-thumb.is-membership { background: linear-gradient(135deg, #334155, #64748b); }
.stamp-design-thumb.is-membership b { border-color: rgba(255, 255, 255, .85); }
.stamp-design-thumb.is-membership i { position: absolute; top: 8px; left: 8px; width: 30px; height: 6px; background: rgba(255, 255, 255, .85); }
@media (max-width: 780px) { .stamp-design-options { grid-template-columns: 1fr; } }
</style>
@endpush
@endonce
