@php
    $coupon = $coupon ?? null;
    $code = old('code', $coupon?->code);
    $name = old('name', $coupon?->name);
    $description = old('description', $coupon?->description);
    $imageUrl = old('image_url', $coupon?->image_url ?? '');
    $imageFit = old('image_fit', $coupon?->image_fit ?? \App\Models\Coupon::IMAGE_FIT_COVER);
    $useDisplay = old('use_display', $coupon?->use_display ?? \App\Models\Coupon::USE_DISPLAY_BARCODE);
    $type = old('type', $coupon?->type ?? 'percent');
    if ($type !== 'percent' && $type !== 'fixed') {
        $type = 'percent';
    }
    $value = old('value', $coupon?->value ?? 10);
    $minOrderAmount = old('min_order_amount', $coupon?->min_order_amount);
    $maxOrderAmount = old('max_order_amount', $coupon?->max_order_amount);
    $applyConditionDivision = old('apply_condition_division', $coupon?->apply_condition_division ?? \App\Enums\CouponApplyConditionDivision::ALL);
    $combineDivision = old('combine_division', $coupon?->combine_division ?? \App\Enums\CouponCombineDivision::ALLOWED);
    $usageLimit = old('usage_limit', $coupon?->usage_limit);
    $startAt = old('start_at', $coupon?->start_at?->format('Y-m-d\TH:i'));
    $endAt = old('end_at', $coupon?->end_at?->format('Y-m-d\TH:i'));
    $isActive = (bool) old('is_active', $coupon === null ? true : $coupon->is_active);
@endphp

<div class="cp-studio">
    <div class="cp-studio-main">
        <section class="cp-card" id="coupon-basic" aria-labelledby="coupon-basic-title">
            <div class="cp-card-head">
                <span class="cp-card-icon"><i class="fas fa-pen-nib" aria-hidden="true"></i></span>
                <div>
                    <p class="cp-card-step">STEP 01</p>
                    <h2 id="coupon-basic-title">クーポンの顔をつくる</h2>
                    <p>会員がひと目で内容を理解できる、名前とビジュアルを設定します。</p>
                </div>
            </div>

            <div class="cp-field-grid cp-field-grid--identity">
                <div class="form-group">
                    <div class="cp-label-row">
                        <label for="name" class="form-label">クーポン名 <span class="cp-required">必須</span></label>
                        <span class="cp-char-count" id="nameCounter">0 / 255</span>
                    </div>
                    <input type="text" name="name" id="name" class="form-control cp-control-lg" value="{{ $name }}" required maxlength="255" placeholder="例：はじめましての10%OFF">
                    <p class="cp-field-help">特典がすぐ伝わる、短く具体的な名前がおすすめです。</p>
                    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="code" class="form-label">管理コード <span class="cp-required">必須</span></label>
                    <div class="cp-code-input">
                        <input type="text" name="code" id="code" class="form-control cp-control-lg" value="{{ $code }}" required maxlength="50" placeholder="WELCOME10" autocomplete="off">
                        <button type="button" class="cp-code-generate" id="generateCouponCode" aria-label="管理コードを自動生成"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i></button>
                    </div>
                    <p class="cp-field-help">POS連携にも使う重複しない英数字です。</p>
                    @error('code')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <div class="cp-label-row">
                    <label for="description" class="form-label">説明文 <span class="cp-optional">任意</span></label>
                    <span class="cp-char-count" id="descriptionCounter">0 / 2,000</span>
                </div>
                <textarea name="description" id="description" class="form-control" rows="3" maxlength="2000" placeholder="例：ご来店の際、スタッフにこの画面をご提示ください。">{{ $description }}</textarea>
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="cp-media-field">
                @include('admin.partials._frontend_logo_dropzone', [
                    'logoInputName' => 'image',
                    'logoClearName' => 'image_clear',
                    'logoFieldId' => 'coupon_image',
                    'logoCurrentUrl' => (string) $imageUrl,
                    'logoLabel' => 'キービジュアル',
                    'logoHelp' => '',
                    'logoCompact' => false,
                ])
                <details class="cp-details cp-url-details" @if($imageUrl !== '') open @endif>
                    <summary><span><i class="fas fa-link" aria-hidden="true"></i> 画像URLを使う</span></summary>
                    <div class="form-group mb-0 mt-2">
                        <label for="image_url" class="form-label">画像URL</label>
                        <input type="url" name="image_url" id="image_url" class="form-control" value="{{ $imageUrl }}" maxlength="500" placeholder="https://example.com/coupon.png">
                        @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        @error('image')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </details>
                <fieldset class="cp-image-fit">
                    <legend class="form-label">画像の表示方法</legend>
                    <div class="cp-image-fit-grid">
                        <label class="cp-choice cp-image-fit-option {{ $imageFit === \App\Models\Coupon::IMAGE_FIT_COVER ? 'is-selected' : '' }}">
                            <input type="radio" name="image_fit" value="{{ \App\Models\Coupon::IMAGE_FIT_COVER }}" {{ $imageFit === \App\Models\Coupon::IMAGE_FIT_COVER ? 'checked' : '' }}>
                            <span class="cp-image-fit-sample cp-image-fit-sample--cover" aria-hidden="true"><i class="fas fa-image"></i></span>
                            <span class="cp-choice-copy">
                                <span class="cp-choice-title">枠いっぱい</span>
                                <span class="cp-choice-desc">余白なく表示し、端をトリミング</span>
                            </span>
                            <span class="cp-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                        <label class="cp-choice cp-image-fit-option {{ $imageFit === \App\Models\Coupon::IMAGE_FIT_CONTAIN ? 'is-selected' : '' }}">
                            <input type="radio" name="image_fit" value="{{ \App\Models\Coupon::IMAGE_FIT_CONTAIN }}" {{ $imageFit === \App\Models\Coupon::IMAGE_FIT_CONTAIN ? 'checked' : '' }}>
                            <span class="cp-image-fit-sample cp-image-fit-sample--contain" aria-hidden="true"><i class="fas fa-image"></i></span>
                            <span class="cp-choice-copy">
                                <span class="cp-choice-title">画像全体</span>
                                <span class="cp-choice-desc">画像を切らずに全体を表示</span>
                            </span>
                            <span class="cp-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                    </div>
                    @error('image_fit')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </fieldset>
            </div>
        </section>

        <section class="cp-card" id="coupon-benefit" aria-labelledby="coupon-benefit-title">
            <div class="cp-card-head">
                <span class="cp-card-icon cp-card-icon--warm"><i class="fas fa-tags" aria-hidden="true"></i></span>
                <div>
                    <p class="cp-card-step">STEP 02</p>
                    <h2 id="coupon-benefit-title">特典を決める</h2>
                    <p>お会計に対して、割合または固定金額で割引できます。</p>
                </div>
            </div>

            <div class="cp-benefit-builder">
                <div class="form-group mb-0">
                    <span class="form-label">割引タイプ <span class="cp-required">必須</span></span>
                    <div class="cp-choice-grid cp-choice-grid--2">
                        <label class="cp-choice cp-choice--benefit {{ $type === 'percent' ? 'is-selected' : '' }}">
                            <input type="radio" name="type" value="percent" {{ $type === 'percent' ? 'checked' : '' }}>
                            <span class="cp-choice-mark">%</span>
                            <span class="cp-choice-copy">
                                <span class="cp-choice-title">パーセントOFF</span>
                                <span class="cp-choice-desc">会計金額に応じて割引</span>
                            </span>
                            <span class="cp-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                        <label class="cp-choice cp-choice--benefit {{ $type === 'fixed' ? 'is-selected' : '' }}">
                            <input type="radio" name="type" value="fixed" {{ $type === 'fixed' ? 'checked' : '' }}>
                            <span class="cp-choice-mark">¥</span>
                            <span class="cp-choice-copy">
                                <span class="cp-choice-title">金額OFF</span>
                                <span class="cp-choice-desc">決まった金額を割引</span>
                            </span>
                            <span class="cp-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                    </div>
                    @error('type')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="cp-value-card">
                    <label for="value" class="form-label"><span id="valueLabel">{{ $type === 'fixed' ? '割引額' : '割引率' }}</span> <span class="cp-required">必須</span></label>
                    <div class="cp-value-row">
                        <input type="number" name="value" id="value" class="form-control" value="{{ $value }}" min="0" step="0.01" inputmode="decimal" required>
                        <span class="cp-value-unit" id="valueUnit">{{ $type === 'fixed' ? '円' : '%' }}</span>
                    </div>
                    <p class="cp-value-example" id="valueExample">会計金額から {{ $value }}{{ $type === 'fixed' ? '円' : '%' }} 割引します</p>
                    @error('value')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>
        </section>

        <section class="cp-card" id="coupon-conditions" aria-labelledby="coupon-conditions-title">
            <div class="cp-card-head">
                <span class="cp-card-icon cp-card-icon--green"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                <div>
                    <p class="cp-card-step">STEP 03</p>
                    <h2 id="coupon-conditions-title">発行条件を整える</h2>
                    <p>スマレジと同じ項目です。未入力の期間・金額は制限なしとして扱われます。</p>
                </div>
            </div>

            <div class="cp-period-grid">
                <div class="form-group mb-0">
                    <label for="start_at" class="form-label">利用開始 <span class="cp-optional">任意</span></label>
                    <div class="cp-input-icon"><i class="far fa-calendar" aria-hidden="true"></i><input type="datetime-local" name="start_at" id="start_at" class="form-control" value="{{ $startAt }}"></div>
                    @error('start_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <span class="cp-period-arrow"><i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                <div class="form-group mb-0">
                    <label for="end_at" class="form-label">利用終了 <span class="cp-optional">任意</span></label>
                    <div class="cp-input-icon"><i class="far fa-calendar" aria-hidden="true"></i><input type="datetime-local" name="end_at" id="end_at" class="form-control" value="{{ $endAt }}"></div>
                    @error('end_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            <fieldset class="cp-issue-conditions">
                <legend class="form-label">適用条件区分 <span class="cp-required">必須</span></legend>
                <div class="cp-choice-grid cp-choice-grid--2">
                    @foreach(\App\Enums\CouponApplyConditionDivision::getValues() as $optionValue => $label)
                        <label class="cp-choice {{ (string) $applyConditionDivision === (string) $optionValue ? 'is-selected' : '' }}">
                            <input type="radio" name="apply_condition_division" value="{{ $optionValue }}" {{ (string) $applyConditionDivision === (string) $optionValue ? 'checked' : '' }}>
                            <span class="cp-choice-copy">
                                <span class="cp-choice-title">{{ $label }}</span>
                                <span class="cp-choice-desc">{{ (string) $optionValue === \App\Enums\CouponApplyConditionDivision::ALL ? '会計全体に適用します' : '指定商品が含まれるときだけ適用します' }}</span>
                            </span>
                            <span class="cp-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
                        </label>
                    @endforeach
                </div>
                @error('apply_condition_division')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </fieldset>

            <div class="cp-grid-2">
                <div class="form-group">
                    <label for="min_order_amount" class="form-label">最低対象金額</label>
                    <div class="cp-input-suffix"><input type="number" name="min_order_amount" id="min_order_amount" class="form-control" value="{{ $minOrderAmount }}" min="0" step="1" placeholder="指定なし"><span>円</span></div>
                    @error('min_order_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="max_order_amount" class="form-label">最高対象金額</label>
                    <div class="cp-input-suffix"><input type="number" name="max_order_amount" id="max_order_amount" class="form-control" value="{{ $maxOrderAmount }}" min="0" step="1" placeholder="指定なし"><span>円</span></div>
                    @error('max_order_amount')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="combine_division" class="form-label">併用区分 <span class="cp-required">必須</span></label>
                <select name="combine_division" id="combine_division" class="form-control">
                    @foreach(\App\Enums\CouponCombineDivision::getValues() as $optionValue => $label)
                        <option value="{{ $optionValue }}" {{ (string) $combineDivision === (string) $optionValue ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('combine_division')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="cp-status-row">
                <span class="cp-status-icon"><i class="fas fa-bolt" aria-hidden="true"></i></span>
                <span class="cp-status-copy"><strong>作成後すぐに利用可能</strong><small>OFFにすると下書きとして保存されます</small></span>
                <label class="cp-switch">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ $isActive ? 'checked' : '' }}>
                    <span class="cp-switch-ui" aria-hidden="true"></span>
                    <span class="sr-only">このクーポンを有効にする</span>
                </label>
            </div>

            <details class="cp-details cp-advanced" @if($usageLimit) open @endif>
                <summary>
                    <span><i class="fas fa-sliders" aria-hidden="true"></i> アプリ側の表示</span>
                    <small>利用回数上限・レジ表示</small>
                </summary>
                <div class="cp-advanced-body">
                    <div class="form-group">
                        <label for="usage_limit" class="form-label">全体の利用回数上限</label>
                        <div class="cp-input-suffix"><input type="number" name="usage_limit" id="usage_limit" class="form-control" value="{{ $usageLimit }}" min="1" placeholder="指定なし"><span>回</span></div>
                        @error('usage_limit')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <label for="use_display" class="form-label">利用時にレジで表示するもの</label>
                        <select name="use_display" id="use_display" class="form-control">
                            @foreach(\App\Models\Coupon::getUseDisplayOptions() as $optionValue => $label)
                                <option value="{{ $optionValue }}" {{ $useDisplay === $optionValue ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('use_display')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </details>
        </section>

        <div class="cp-create-footer">
            <p><i class="fas fa-circle-info" aria-hidden="true"></i> 作成後も内容は編集できます</p>
            <div>
                <a href="{{ $cancelUrl }}" class="btn btn-secondary cp-cancel">キャンセル</a>
                <button type="submit" class="btn btn-primary cp-submit"><span>{{ $submitLabel }}</span><i class="fas fa-arrow-right" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>

    <aside class="cp-studio-aside" aria-label="クーポンプレビュー">
        <div class="cp-preview-panel">
            <div class="cp-preview-head">
                <div>
                    <span class="cp-live-dot" aria-hidden="true"></span>
                    <strong>LIVE PREVIEW</strong>
                </div>
                <span>会員アプリ</span>
            </div>
            @include('admin.coupons._preview')
        </div>
        <div class="cp-setting-summary">
            <p class="cp-summary-title">設定サマリー</p>
            <dl>
                <div><dt><i class="fas fa-tag" aria-hidden="true"></i> 特典</dt><dd id="couponSummaryDiscount">{{ $value }}{{ $type === 'fixed' ? '円' : '%' }} OFF</dd></div>
                <div><dt><i class="far fa-calendar" aria-hidden="true"></i> 利用期間</dt><dd id="couponSummaryPeriod">期限なし</dd></div>
                <div><dt><i class="fas fa-circle" aria-hidden="true"></i> ステータス</dt><dd id="couponSummaryStatus">{{ $isActive ? '有効' : '下書き' }}</dd></div>
            </dl>
        </div>
    </aside>
</div>
