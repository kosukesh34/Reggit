@php
    $inputType = $inputType ?? 'checkbox';
    $inputName = $inputName ?? 'delivery_coupon_ids[]';
    $selectedIds = collect($selectedIds ?? []);
    $inputsDisabled = ($inputsDisabled ?? false) === true;
    $disableExpired = ($disableExpired ?? false) === true;
@endphp
<div class="ad-coupon-list">
    @foreach($view->coupons as $coupon)
        @php
            $isExpired = $coupon->end_at !== null && $coupon->end_at->isPast();
            $benefit = $coupon->type === 'percent'
                ? rtrim(rtrim((string) $coupon->value, '0'), '.').'％OFF'
                : number_format((float) $coupon->value).'円OFF';
            $period = $coupon->end_at ? $coupon->end_at->format('Y/m/d').'まで' : '期限なし';
            $isSelected = $selectedIds->contains((int) $coupon->id);
        @endphp
        <label class="ad-coupon-choice">
            <input
                type="{{ $inputType }}"
                name="{{ $inputName }}"
                value="{{ $coupon->id }}"
                data-name="{{ $coupon->name }}"
                data-benefit="{{ $benefit }}"
                data-period="{{ $period }}"
                @if($isExpired) data-expired="1" @endif
                @checked($isSelected)
                @disabled($inputsDisabled || ($disableExpired && $isExpired))
            >
            <span>{{ $coupon->name ?? '—' }}</span>
            <small>{{ $benefit }}{{ $isExpired ? ' ・期限切れ' : '' }}</small>
        </label>
    @endforeach
</div>
