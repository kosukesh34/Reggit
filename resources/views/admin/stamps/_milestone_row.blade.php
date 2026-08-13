@php
    $index = $index ?? 0;
    $isNamed = $isNamed ?? true;
    $idPrefix = $idPrefix ?? 'reward_milestones';
    $milestone = is_array($milestone ?? null) ? $milestone : [];
    $rewardType = \App\Enums\StampRewardType::normalize((string) ($milestone['reward_type'] ?? \App\Enums\StampRewardType::COUPON));
    $selectedCouponId = (int) ($milestone['coupon_id'] ?? 0);
    $coupons = $coupons ?? collect();
    $countId = $idPrefix.'_'.$index.'_stamp_count';
    $typeId = $idPrefix.'_'.$index.'_reward_type';
    $couponId = $idPrefix.'_'.$index.'_coupon_id';
@endphp
<div class="stamp-milestone-row" data-milestone-row>
    <div class="stamp-milestone-count-wrap">
        <label class="form-label" data-label-count @if($isNamed) for="{{ $countId }}" @endif>スタンプ数</label>
        <input type="number" min="1" max="999" class="form-control milestone-count-input" placeholder="10"
            @if($isNamed) id="{{ $countId }}" name="reward_milestones[{{ $index }}][stamp_count]" value="{{ $milestone['stamp_count'] ?? '' }}" @endif>
    </div>
    <div class="stamp-milestone-type-wrap">
        <label class="form-label" data-label-type @if($isNamed) for="{{ $typeId }}" @endif>特典の種類</label>
        <select class="form-control milestone-type-input" @if($isNamed) id="{{ $typeId }}" name="reward_milestones[{{ $index }}][reward_type]" @endif>
            @foreach(\App\Enums\StampRewardType::getValues() as $value => $label)
                <option value="{{ $value }}" @selected($rewardType === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="stamp-milestone-reward-wrap">
        <div data-reward-coupon @if($rewardType !== \App\Enums\StampRewardType::COUPON) hidden @endif>
            <label class="form-label" data-label-coupon @if($isNamed) for="{{ $couponId }}" @endif>クーポン</label>
            <select class="form-control milestone-coupon-input" @if($isNamed) id="{{ $couponId }}" name="reward_milestones[{{ $index }}][coupon_id]" @endif>
                <option value="">クーポンを選択</option>
                @foreach($coupons as $coupon)
                    <option value="{{ $coupon->id }}" @selected($selectedCouponId === (int) $coupon->id)>
                        {{ $coupon->name }}{{ $coupon->is_active ? '' : '（無効）' }}
                    </option>
                @endforeach
            </select>
        </div>
        <div data-reward-gacha @if($rewardType !== \App\Enums\StampRewardType::GACHA) hidden @endif>
            <p class="stamp-milestone-gacha-note">到達時にガチャ権利を1回付与します</p>
        </div>
    </div>
    <div class="stamp-milestone-action-wrap">
        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-milestone aria-label="特典を削除">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>
