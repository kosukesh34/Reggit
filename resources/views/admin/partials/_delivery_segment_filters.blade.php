@php
    $savedSegment = is_array($savedSegment ?? null) ? $savedSegment : [];
    $isCouponDelivery = ($isCouponDelivery ?? false) === true;
    // 会員一覧など配信以外の画面では、人数表示とグループ保存（お知らせ配信フォーム専用）を出さない
    $isDeliveryForm = ($isDeliveryForm ?? true) === true;
    // 会員一覧のGET条件など、明示の savedSegment を session の old より優先する
    $segmentOld = function (string $key, mixed $default = null) use ($savedSegment) {
        if (\Illuminate\Support\Arr::has($savedSegment, $key)) {
            return data_get($savedSegment, $key);
        }
        if (session()->hasOldInput()) {
            return old($key, $default);
        }

        return $default;
    };
    $selectedBirthdayMonths = collect((array) $segmentOld('segment_birthday_months', []))->map(fn ($month) => (int) $month)->filter(fn ($month) => $month >= 1 && $month <= 12)->unique()->values();
    $selectedTagIds = collect((array) $segmentOld('segment_tag_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
    $selectedRfmSegments = collect((array) $segmentOld('segment_rfm_segments', []))->map(fn ($key) => (string) $key)->filter()->unique()->values();
    $advancedSegmentOpen = collect([
        'segment_visit_count_min', 'segment_visit_count_max', 'segment_lifetime_points_min',
        'segment_lifetime_points_max', 'segment_last_visit_mode', 'segment_rank',
        'segment_product_id', 'segment_coupon_id', 'segment_coupon_status', 'segment_condition',
    ])->contains(fn ($field) => filled($segmentOld($field)));
@endphp
                        @if($isDeliveryForm)
                        <div class="ad-segment-head">
                            <span id="segmentPreview">人数を確認中</span>
                        </div>
                        @endif
                        <div class="ad-cond-builder">
                            <div class="ad-cond-list" id="segmentConditionRows"></div>
                            <div class="ad-cond-add">
                                <button type="button" class="ad-cond-add-btn" id="segmentAddCondition" aria-expanded="false" aria-controls="segmentConditionMenu"><i class="fas fa-plus" aria-hidden="true"></i>条件を追加</button>
                                <div class="ad-cond-menu" id="segmentConditionMenu" hidden>
                                    <ul class="ad-cond-menu-groups">
                                        <li><button type="button" data-menu-group="attribute">属性<i class="fas fa-chevron-right" aria-hidden="true"></i></button></li>
                                        <li><button type="button" data-menu-group="visit">来店・購入<i class="fas fa-chevron-right" aria-hidden="true"></i></button></li>
                                        @if(count($view->profileFields ?? []) > 0)
                                            <li><button type="button" data-menu-group="profile">プロフィール<i class="fas fa-chevron-right" aria-hidden="true"></i></button></li>
                                        @endif
                                    </ul>
                                    <ul class="ad-cond-menu-fields">
                                        <li><button type="button" data-menu-field="channel" data-group="attribute">登録チャネル</button></li>
                                        <li><button type="button" data-menu-field="birthday" data-group="attribute">誕生月</button></li>
                                        <li><button type="button" data-menu-field="sex" data-group="attribute">性別</button></li>
                                        <li><button type="button" data-menu-field="rfm" data-group="attribute">RFMセグメント</button></li>
                                        <li><button type="button" data-menu-field="tags" data-group="attribute">会員タグ</button></li>
                                        <li><button type="button" data-menu-field="visits" data-group="visit">来店回数</button></li>
                                        <li><button type="button" data-menu-field="rank" data-group="visit">会員ランク</button></li>
                                        <li><button type="button" data-menu-field="points" data-group="visit">累計付与ポイント</button></li>
                                        <li><button type="button" data-menu-field="last_visit" data-group="visit">最終来店</button></li>
                                        <li><button type="button" data-menu-field="product" data-group="visit">購入した商品</button></li>
                                        <li><button type="button" data-menu-field="held_coupon" data-group="visit">保有クーポン</button></li>
                                        <li><button type="button" data-menu-field="sales" data-group="visit">売上</button></li>
                                        @if($isCouponDelivery)
                                            <li><button type="button" data-menu-field="stamp" data-group="visit">スタンプ</button></li>
                                        @endif
                                        @foreach(($view->profileFields ?? []) as $profileField)
                                            @if(($profileField['key'] ?? '') !== '')
                                                <li><button type="button" data-menu-field="profile-{{ $profileField['key'] }}" data-group="profile">{{ $profileField['label'] ?? $profileField['key'] }}</button></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <template id="segmentConditionRowTemplate">
                                <div class="ad-cond-card">
                                    <div class="ad-cond-card-head">
                                        <span class="ad-cond-card-group"></span>
                                        <strong class="ad-cond-card-title"></strong>
                                        <button type="button" class="ad-cond-card-remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                                    </div>
                                    <div class="ad-cond-card-body"></div>
                                </div>
                            </template>
                        </div>
                        @if($view->audienceGroups->isNotEmpty())
                        <section class="ad-confirm-conditions">
                            <div class="ad-confirm-conditions-head">
                                <span>保存したグループ</span>
                            </div>
                            <div class="ad-filter-grid">
                                @foreach($view->audienceGroups as $group)
                                <label class="checkbox-label">
                                    <input type="checkbox" name="segment_audience_group_ids[]" value="{{ $group->id }}" data-label="{{ $group->name }}" class="segment-filter" @checked(collect((array) $segmentOld('segment_audience_group_ids', []))->contains($group->id))>
                                    {{ $group->name }}
                                </label>
                                @endforeach
                            </div>
                        </section>
                        @endif
                        <div id="segmentConditionPool" hidden>
                        <div class="ad-segment-advanced">
                        <div class="ad-filter-grid">
                            <div class="form-group mb-0 ad-condition-item" data-condition="channel" hidden>
                                <div class="ad-filter-label"><label for="segment_channel" class="form-label">登録チャネル</label></div>
                                <select name="segment_channel" id="segment_channel" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                        <option value="{{ $value }}" {{ $segmentOld('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter mb-0 ad-condition-item" data-condition="birthday" hidden>
                                <legend class="form-label">誕生月</legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--months">
                                    @for($m = 1; $m <= 12; $m++)
                                        <label><input type="checkbox" name="segment_birthday_months[]" value="{{ $m }}" data-label="{{ $m }}月" class="segment-filter" {{ $selectedBirthdayMonths->contains($m) ? 'checked' : '' }}><span>{{ $m }}月</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="form-group mb-0 ad-condition-item" data-condition="sex" hidden>
                                <div class="ad-filter-label"><label for="segment_sex" class="form-label">性別</label></div>
                                <select name="segment_sex" id="segment_sex" class="form-control segment-filter">
                                    <option value="">指定しない</option>
                                    <option value="1" {{ $segmentOld('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                                    <option value="2" {{ $segmentOld('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                                </select>
                            </div>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0 ad-condition-item" data-condition="rfm" hidden>
                                <legend class="form-label">RFMセグメント</legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--tags">
                                    @foreach($view->rfmSegments as $rfmKey => $rfm)
                                        <label style="--ad-tag-color: {{ $rfm['color'] }}"><input type="checkbox" name="segment_rfm_segments[]" value="{{ $rfmKey }}" data-label="{{ $rfm['label'] }}" class="segment-filter" {{ $selectedRfmSegments->contains($rfmKey) ? 'checked' : '' }}><span><i aria-hidden="true"></i>{{ $rfm['label'] }}</span></label>
                                    @endforeach
                                </div>
                            </fieldset>
                            <fieldset class="ad-multi-filter ad-filter-group-wide mb-0 ad-condition-item" data-condition="tags" hidden>
                                <legend class="form-label">会員タグ</legend>
                                @if(($view->memberTags ?? collect())->isEmpty())
                                    <p class="ad-multi-filter-empty">会員タグはまだ登録されていません。</p>
                                @else
                                    <div class="ad-multi-filter-options ad-multi-filter-options--tags">
                                        @foreach($view->memberTags as $tag)
                                            <label style="--ad-tag-color: {{ $tag->color }}"><input type="checkbox" name="segment_tag_ids[]" value="{{ $tag->id }}" data-label="{{ $tag->name }}" class="segment-filter" {{ $selectedTagIds->contains((int) $tag->id) ? 'checked' : '' }}><span><i aria-hidden="true"></i>{{ $tag->name }} <small>{{ number_format($tag->members_count ?? 0) }}人</small></span></label>
                                        @endforeach
                                    </div>
                                    @include('admin.partials._segment_value_mode', ['name' => 'segment_value_mode[tags]', 'target' => 'segment_tag_ids[]', 'caption' => '選んだタグの組み合わせ', 'current' => $segmentOld('segment_value_mode.tags')])
                                @endif
                            </fieldset>
                        </div>
                        </div>

                        <div class="ad-segment-advanced">
                                <div class="ad-filter-grid">
                                    <div class="ad-condition-item" data-condition="visits" hidden>
                                        <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_visit_count_min" class="form-label">来店回数（以上）</label></div><input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control segment-filter" value="{{ $segmentOld('segment_visit_count_min') }}" min="0" placeholder="例：3"></div>
                                        <div class="form-group mb-0"><label for="segment_visit_count_max" class="form-label">来店回数（以下）</label><input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control segment-filter" value="{{ $segmentOld('segment_visit_count_max') }}" min="0" placeholder="例：10"></div>
                                    </div>
                                    <div class="form-group mb-0 ad-condition-item" data-condition="rank" hidden>
                                        <div class="ad-filter-label"><label for="segment_rank" class="form-label">会員ランク</label></div>@php $selectedRank = (string) $segmentOld('segment_rank', ''); @endphp<select name="segment_rank" id="segment_rank" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach($segmentRankOptions ?? [] as $rankKey => $rankLabel)
                                                <option value="{{ $rankKey }}" @selected($selectedRank === (string) $rankKey)>{{ $rankLabel }}</option>
                                            @endforeach
                                            {{-- 以前に手入力した、今は存在しないランクも選択を保つ --}}
                                            @if($selectedRank !== '' && ! array_key_exists($selectedRank, $segmentRankOptions ?? []))
                                                <option value="{{ $selectedRank }}" selected>{{ $selectedRank }}（登録されていないランク）</option>
                                            @endif
                                        </select>
                                        @if($isCouponDelivery)
                                            <label class="checkbox-label"><input type="checkbox" name="event_rank_up" id="event_rank_up" value="1" class="segment-filter" @checked($segmentOld('event_rank_up') || $segmentOld('delivery_type') === \App\Enums\CouponDeliveryType::EVENT_RANK_UP_ARRIVAL)> このランクへ昇格したときに自動で配布する</label>
                                        @endif
                                    </div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="points" hidden>
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label></div><input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control segment-filter" value="{{ $segmentOld('segment_lifetime_points_min') }}" min="0" placeholder="例：1000"></div>
                                    <div class="form-group mb-0"><label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label><input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control segment-filter" value="{{ $segmentOld('segment_lifetime_points_max') }}" min="0" placeholder="例：5000"></div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="last_visit" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_last_visit_mode" class="form-label">最終来店</label></div>
                                        <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="inactive_days" {{ $segmentOld('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                                            <option value="visited_within_days" {{ $segmentOld('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
                                            @if($isCouponDelivery)
                                                <option value="days_after" {{ $segmentOld('segment_last_visit_mode') === 'days_after' ? 'selected' : '' }}>最終来店からちょうどN日後</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_last_visit_days" class="form-label">日数</label>
                                        <select name="segment_last_visit_days" id="segment_last_visit_days" class="form-control segment-filter" data-ignore-empty="1">
                                            @foreach([7, 30, 60, 90, 180] as $days)
                                                <option value="{{ $days }}" {{ (int) $segmentOld('segment_last_visit_days', 30) === $days ? 'selected' : '' }}>{{ $days }}日</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group mb-0 ad-condition-item" data-condition="product" hidden>
                                    <div class="ad-filter-label"><label for="segment_product_id" class="form-label">購入した商品</label></div>
                                    <select name="segment_product_id" id="segment_product_id" class="form-control segment-filter">
                                        <option value="">指定しない</option>
                                        @foreach(($view->products ?? []) as $product)
                                            <option value="{{ $product->id }}" {{ (string) $segmentOld('segment_product_id') === (string) $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="ad-field-help">アプリ注文・スマレジPOSで購入した会員に絞り込みます。</p>
                                </div>
                                <div class="ad-filter-grid ad-condition-item" data-condition="held_coupon" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_coupon_id" class="form-label">保有クーポン</label></div>
                                        <select name="segment_coupon_id" id="segment_coupon_id" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach(($view->coupons ?? []) as $coupon)
                                                <option value="{{ $coupon->id }}" {{ (string) $segmentOld('segment_coupon_id') === (string) $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_coupon_status" class="form-label">使用状態</label>
                                        <select name="segment_coupon_status" id="segment_coupon_status" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="unused" {{ $segmentOld('segment_coupon_status') === 'unused' ? 'selected' : '' }}>未使用</option>
                                            <option value="used" {{ $segmentOld('segment_coupon_status') === 'used' ? 'selected' : '' }}>使用済み</option>
                                        </select>
                                    </div>
                                    <p class="ad-field-help">指定クーポンを保有し、使用状態が一致する会員に絞り込みます。</p>
                                </div>
                                <div class="ad-sales-filter ad-condition-item" data-condition="sales" hidden>
                                    <div class="form-group mb-0">
                                        <label for="segment_period_days" class="form-label">売上の対象期間</label>
                                        <select name="segment_period_days" id="segment_period_days" class="form-control segment-filter" data-ignore-empty="1">
                                            <option value="7" {{ (int) $segmentOld('segment_period_days', 30) === 7 ? 'selected' : '' }}>直近7日間</option>
                                            <option value="30" {{ (int) $segmentOld('segment_period_days', 30) === 30 ? 'selected' : '' }}>直近30日間</option>
                                            <option value="90" {{ (int) $segmentOld('segment_period_days', 30) === 90 ? 'selected' : '' }}>直近90日間</option>
                                        </select>
                                    </div>
                                    <fieldset>
                                        <legend class="form-label">売上条件 <small>1つだけ選択</small></legend>
                                        <label><input type="radio" name="segment_condition" value="" {{ $segmentOld('segment_condition', '') === '' ? 'checked' : '' }} class="segment-filter"> 指定しない</label>
                                        <label><input type="radio" name="segment_condition" value="min_amount" {{ $segmentOld('segment_condition') === 'min_amount' ? 'checked' : '' }} class="segment-filter"> 購入額以上</label>
                                        <label><input type="radio" name="segment_condition" value="no_orders" {{ $segmentOld('segment_condition') === 'no_orders' ? 'checked' : '' }} class="segment-filter"> 未購入</label>
                                    </fieldset>
                                    <div id="segmentMinAmountBlock" class="form-group mb-0" hidden>
                                        <label for="segment_min_amount" class="form-label">最低購入額</label>
                                        <div class="ad-input-suffix"><input type="number" name="segment_min_amount" id="segment_min_amount" class="form-control segment-filter" value="{{ $segmentOld('segment_min_amount') }}" min="0" step="1" placeholder="5000"><span>円</span></div>
                                    </div>
                                </div>
                                @if($isCouponDelivery)
                                    <div class="ad-filter-grid ad-condition-item" data-condition="stamp" hidden>
                                        <div class="form-group mb-0">
                                            <div class="ad-filter-label"><label for="stamp_card_id" class="form-label">スタンプカード</label></div>
                                            <select name="stamp_card_id" id="stamp_card_id" class="form-control segment-filter">
                                                <option value="">指定しない</option>
                                                @foreach(($view->stampCards ?? []) as $card)
                                                    <option value="{{ $card->id }}" @selected((string) $segmentOld('stamp_card_id') === (string) $card->id)>{{ $card->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label for="stamp_count_min" class="form-label">累計スタンプ以上</label>
                                            <input type="number" name="stamp_count_min" id="stamp_count_min" class="form-control segment-filter" value="{{ $segmentOld('stamp_count_min') }}" min="0">
                                        </div>
                                        <label class="checkbox-label"><input type="checkbox" name="stamp_completed" id="stamp_completed" value="1" class="segment-filter" @checked($segmentOld('stamp_completed'))> 達成済みのみ</label>
                                    </div>
                                @endif
                            </div>
                            @if(count($view->profileFields) > 0)
                            <div class="ad-segment-advanced"><div class="ad-filter-grid">
                                    @forelse($view->profileFields as $profileField)
                                        @php
                                            $profileKey = (string) ($profileField['key'] ?? '');
                                            $profileLabel = (string) ($profileField['label'] ?? $profileKey);
                                            $profileValues = array_values((array) ($profileField['values'] ?? []));
                                            $profileOld = (array) $segmentOld('segment_profile.'.$profileKey, []);
                                        @endphp
                                        @if($profileKey !== '')
                                            <div class="form-group mb-0 ad-profile-filter ad-condition-item" data-condition="profile-{{ $profileKey }}" data-profile-label="{{ $profileLabel }}" data-profile-key="{{ $profileKey }}" hidden>
                                                <div class="ad-filter-label"><label class="form-label" for="segment_profile_{{ $profileKey }}">{{ $profileLabel }}</label></div>
                                                @if($profileValues !== [])
                                                    <div class="ad-multi-filter-options">
                                                        @foreach($profileValues as $value)
                                                            <label><input type="checkbox" name="segment_profile[{{ $profileKey }}][]" value="{{ $value }}" class="segment-filter" {{ in_array((string) $value, array_map('strval', $profileOld), true) ? 'checked' : '' }}><span>{{ $value }}</span></label>
                                                        @endforeach
                                                    </div>
                                                    @if(\App\Enums\ProfileCustomFieldInputType::allowsMultipleValues((string) ($profileField['input_type'] ?? '')))
                                                        @include('admin.partials._segment_value_mode', ['name' => 'segment_value_mode[profile]['.$profileKey.']', 'target' => 'segment_profile['.$profileKey.'][]', 'caption' => '選んだ値の組み合わせ', 'current' => $segmentOld('segment_value_mode.profile.'.$profileKey)])
                                                    @endif
                                                @else
                                                    <input id="segment_profile_{{ $profileKey }}" name="segment_profile[{{ $profileKey }}]" class="form-control segment-filter" value="{{ $segmentOld('segment_profile.'.$profileKey) }}" placeholder="{{ $profileLabel }}の値を入力" maxlength="255">
                                                    <p class="ad-field-help">入力値と完全一致</p>
                                                @endif
                                            </div>
                                        @endif
                                    @empty
                                        <div class="ad-profile-empty"><p>この店舗には追加プロフィール項目がまだありません。</p><a href="{{ admin_route('admin.settings.profile') }}">プロフィール設定で項目を追加する <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                                    @endforelse
                                </div></div>
                            @endif
                        </div>
                            <section class="ad-confirm-conditions" id="deliverySummaryConditionBlock" aria-labelledby="deliverySummaryConditionTitle" hidden>
                                <div class="ad-confirm-conditions-head">
                                    <span id="deliverySummaryConditionTitle">選択中の条件</span>
                                </div>
                                <div class="ad-condition-chips" id="deliverySummaryConditions" aria-live="polite"></div>
                            </section>
                            <fieldset class="ad-match-mode" id="segmentMatchMode" hidden>
                                <legend>条件の組み合わせ</legend>
                                <div class="ad-match-mode-options">
                                    <label><input type="radio" name="segment_match_mode" value="{{ \App\Enums\SegmentCombineMode::ALL }}" class="segment-filter" @checked($segmentOld('segment_match_mode', \App\Enums\SegmentCombineMode::ALL) === \App\Enums\SegmentCombineMode::ALL)><span><strong>すべて一致</strong></span></label>
                                    <label><input type="radio" name="segment_match_mode" value="{{ \App\Enums\SegmentCombineMode::ANY }}" class="segment-filter" @checked($segmentOld('segment_match_mode') === \App\Enums\SegmentCombineMode::ANY)><span><strong>いずれか一致</strong></span></label>
                                </div>
                            </fieldset>
                            @if($isDeliveryForm && ! $isCouponDelivery)
                            <details class="ad-details ad-segment-details" id="segmentGroupSave" @if(filled($segmentOld('audience_group_name')) || $errors->has('audience_group_name')) open @else hidden @endif>
                                <summary><span>この条件をグループとして保存</span></summary>
                                <div class="ad-segment-advanced">
                                    <div class="form-actions">
                                        <input type="text" name="audience_group_name" class="form-control" maxlength="40" placeholder="グループ名（例: 休眠の常連）" value="{{ $segmentOld('audience_group_name') }}">
                                        <button type="submit" class="btn btn-secondary" formnovalidate formaction="{{ admin_route('admin.announcements.audience-groups.store') }}">保存する</button>
                                    </div>
                                    @error('audience_group_name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                                </div>
                            </details>
                            @endif
