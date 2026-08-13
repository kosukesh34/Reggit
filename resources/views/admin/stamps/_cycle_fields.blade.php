@php
    $cycleMode = old('cycle_mode', isset($stampCard) ? ($stampCard->cycle_mode ?? \App\Enums\StampCycleMode::REPEAT) : \App\Enums\StampCycleMode::REPEAT);
    $nextStampCardId = (int) old('next_stamp_card_id', isset($stampCard) ? ($stampCard->next_stamp_card_id ?? 0) : 0);
    $cycleTargetCards = $cycleTargetCards ?? collect();
@endphp
<div class="stamp-form-section">
    <div class="stamp-form-section-head">
        <h4>達成後の動き</h4>
    </div>
    <div class="stamp-cycle-options">
        @foreach(\App\Enums\StampCycleMode::getValues() as $value => $label)
            <label class="stamp-cycle-option">
                <input type="radio" name="cycle_mode" value="{{ $value }}" {{ $cycleMode === $value ? 'checked' : '' }} data-stamp-cycle-mode>
                <span>
                    <strong>{{ $label }}</strong>
                    @if($value === \App\Enums\StampCycleMode::REPEAT)
                        <small>必要数に達したら枠をリセットし、同じカードで2周目以降を集めます。</small>
                    @elseif($value === \App\Enums\StampCycleMode::ONCE)
                        <small>1周達成したらこのカードは終了します。追加のスタンプは付きません。</small>
                    @else
                        <small>達成したカードは履歴に残り、次のカードの収集が始まります。</small>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    <div class="form-group stamp-cycle-next" data-stamp-cycle-next {{ $cycleMode === \App\Enums\StampCycleMode::NEXT_CARD ? '' : 'hidden' }}>
        <label for="next_stamp_card_id" class="form-label">次に切り替えるカード</label>
        <select name="next_stamp_card_id" id="next_stamp_card_id" class="form-control">
            <option value="">選択してください</option>
            @foreach($cycleTargetCards as $target)
                <option value="{{ $target->id }}" {{ $nextStampCardId === (int) $target->id ? 'selected' : '' }}>
                    {{ $target->name }}{{ $target->is_active ? '' : '（無効）' }}
                </option>
            @endforeach
        </select>
        <p class="form-text text-muted">お客様には今のカードだけが見え、達成後に次のカードへ進みます。</p>
        @error('next_stamp_card_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
        @if($cycleTargetCards->isEmpty())
            <p class="form-text text-muted">先に次のカードを作成してから、こちらで指定できます。</p>
        @endif
    </div>
    @error('cycle_mode')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
</div>
<style>
.stamp-cycle-options { display: grid; gap: 8px; }
.stamp-cycle-option { display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 10px; align-items: start; padding: 12px; border: 1px solid var(--studio-border, #e2e8f0); border-radius: 12px; background: #fff; cursor: pointer; }
.stamp-cycle-option strong { display: block; font-size: 14px; }
.stamp-cycle-option small { display: block; margin-top: 4px; color: #64748b; line-height: 1.5; }
.stamp-cycle-next { margin-top: 12px; }
</style>
<script>
(() => {
    const form = document.currentScript.closest('form');
    if (!form) return;
    const sync = () => {
        const selected = form.querySelector('[data-stamp-cycle-mode]:checked');
        const next = form.querySelector('[data-stamp-cycle-next]');
        if (!next) return;
        next.hidden = selected?.value !== '{{ \App\Enums\StampCycleMode::NEXT_CARD }}';
    };
    form.querySelectorAll('[data-stamp-cycle-mode]').forEach((input) => input.addEventListener('change', sync));
    sync();
})();
</script>
