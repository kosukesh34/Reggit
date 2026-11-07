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
                        <div class="ad-cond-builder" data-segment-more="{{ json_encode((array) $segmentOld('segment_more', []), JSON_UNESCAPED_UNICODE) }}" data-segment-blocks="{{ json_encode((array) $segmentOld('segment_blocks', []), JSON_UNESCAPED_UNICODE) }}">
                            <div class="ad-cond-list" id="segmentConditionRows"></div>
                            <div class="ad-cond-add">
                                <button type="button" class="ad-cond-add-btn" id="segmentAddCondition" aria-expanded="false" aria-controls="segmentConditionMenu"><i class="fas fa-plus" aria-hidden="true"></i>条件を追加</button>
                                @php
                                    // 条件メニューのセクション。keywords は検索で拾う別名
                                    $conditionMenu = [
                                        'attribute' => ['会員の属性', [
                                            'channel' => ['登録チャネル', 'LINE アプリ'],
                                            'registered' => ['登録日', '入会日 新規'],
                                            'acquisition' => ['登録経路', '流入 チラシ QR', count($segmentAcquisitionOptions ?? []) > 0],
                                            'store' => ['利用店舗', '店舗 お店', count($segmentStoreOptions ?? []) > 1],
                                            'birthday' => ['誕生月', '誕生日 バースデー'],
                                            'birthday_soon' => ['誕生日が近い', '誕生日 バースデー 記念日'],
                                            'age' => ['年齢', '年代 誕生日'],
                                            'sex' => ['性別', '男性 女性'],
                                            'tags' => ['会員タグ', 'タグ'],
                                            'rfm' => ['RFMセグメント', '優良 休眠 分析'],
                                        ]],
                                        'visit' => ['来店・購入', [
                                            'visits' => ['来店回数', '回数'],
                                            'last_visit' => ['最終来店', '休眠 来店なし'],
                                            'reservation' => ['予約', '予約あり 予約なし'],
                                            'queue' => ['順番待ちの利用', '順番待ち 行列 受付'],
                                            'product' => ['購入した商品', '商品 メニュー'],
                                            'order_type' => ['注文の種類', 'テイクアウト モバイル 店内 POS 配送 回数券 サブスク'],
                                            'sales' => ['売上', '購入額 未購入'],
                                            'lifetime_sales' => ['累計購入額', '購入額 売上 総額 LTV'],
                                        ]],
                                        'rank' => ['ランク・ポイント', [
                                            'rank' => ['会員ランク', 'ランク'],
                                            'rank_changed' => ['ランク変更', 'ランクアップ 昇格'],
                                            'points' => ['累計付与ポイント', 'ポイント'],
                                            'point_balance' => ['ポイント残高', 'ポイント 残高'],
                                            'points_expiring' => ['ポイントの失効予定', 'ポイント 失効 期限'],
                                        ]],
                                        'benefit' => ['クーポン・特典', [
                                            'held_coupon' => ['保有クーポン', 'クーポン 未使用 使用済み'],
                                            'coupon_expiring' => ['クーポンの期限が近い', 'クーポン 期限 失効'],
                                            'coupon_uses' => ['クーポン利用回数', 'クーポン 使った'],
                                            'stamp' => ['スタンプ', 'スタンプカード', $isCouponDelivery],
                                            'ticket' => ['回数券', 'チケット'],
                                            'subscription' => ['サブスク', '定期 月額 継続 解約'],
                                            'lottery' => ['くじの抽選権', 'くじ 抽選 ガチャ'],
                                        ]],
                                        'engagement' => ['お知らせ・アプリ', [
                                            'announcement_read' => ['お知らせの既読', '未読 既読 お知らせ'],
                                            'survey' => ['アンケートの回答', 'アンケート 回答 未回答', count($segmentSurveyOptions ?? []) > 0],
                                            'nps' => ['アンケートの評価（NPS）', 'アンケート 評価 満足度 NPS 口コミ'],
                                            'app_last_used' => ['アプリの利用', 'アプリ 最終ログイン 起動'],
                                            'wallet' => ['Apple Wallet', 'ウォレット 会員証'],
                                            'line_follow' => ['LINE友だち追加日', 'LINE 友だち 追加 新規'],
                                            'line_block' => ['LINEブロック', 'LINE ブロック 解除'],
                                            'notification' => ['通知設定', '通知 受け取らない 配信停止'],
                                        ]],
                                    ];
                                @endphp
                                <div class="ad-cond-menu" id="segmentConditionMenu" hidden>
                                    <div class="ad-cond-menu-search">
                                        <i class="fas fa-search" aria-hidden="true"></i>
                                        <input type="search" id="segmentConditionSearch" placeholder="条件を検索（例：誕生日、クーポン）" aria-label="条件を検索" autocomplete="off">
                                    </div>
                                    <ul class="ad-cond-menu-groups">
                                        @foreach($conditionMenu as $groupKey => [$groupLabel])
                                            <li><button type="button" data-menu-group="{{ $groupKey }}">{{ $groupLabel }}<i class="fas fa-chevron-right" aria-hidden="true"></i></button></li>
                                        @endforeach
                                        @if(count($view->profileFields ?? []) > 0)
                                            <li><button type="button" data-menu-group="profile">プロフィール<i class="fas fa-chevron-right" aria-hidden="true"></i></button></li>
                                        @endif
                                    </ul>
                                    <ul class="ad-cond-menu-fields">
                                        @foreach($conditionMenu as $groupKey => [, $fields])
                                            @foreach($fields as $fieldKey => $field)
                                                @if($field[2] ?? true)
                                                    <li><button type="button" data-menu-field="{{ $fieldKey }}" data-group="{{ $groupKey }}" data-keywords="{{ $field[1] }}">{{ $field[0] }}</button></li>
                                                @endif
                                            @endforeach
                                        @endforeach
                                        @foreach(($view->profileFields ?? []) as $profileField)
                                            @if(($profileField['key'] ?? '') !== '')
                                                <li><button type="button" data-menu-field="profile-{{ $profileField['key'] }}" data-group="profile" data-keywords="プロフィール">{{ $profileField['label'] ?? $profileField['key'] }}</button></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                    <p class="ad-cond-menu-empty" id="segmentConditionSearchEmpty" role="status" aria-live="polite" hidden>一致する条件がありません</p>
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
                            <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="registered" hidden>
                                <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_registered_from" class="form-label">登録日（この日から）</label></div><input type="date" name="segment_registered_from" id="segment_registered_from" class="form-control segment-filter" value="{{ $segmentOld('segment_registered_from') }}"></div>
                                <div class="form-group mb-0"><label for="segment_registered_to" class="form-label">登録日（この日まで）</label><input type="date" name="segment_registered_to" id="segment_registered_to" class="form-control segment-filter" value="{{ $segmentOld('segment_registered_to') }}"></div>
                            </div>
                            <fieldset class="ad-multi-filter mb-0 ad-condition-item" data-condition="birthday" hidden>
                                <legend class="form-label">誕生月</legend>
                                <div class="ad-multi-filter-options ad-multi-filter-options--months">
                                    @for($m = 1; $m <= 12; $m++)
                                        <label><input type="checkbox" name="segment_birthday_months[]" value="{{ $m }}" data-label="{{ $m }}月" class="segment-filter" {{ $selectedBirthdayMonths->contains($m) ? 'checked' : '' }}><span>{{ $m }}月</span></label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="age" hidden>
                                <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_age_min" class="form-label">年齢（以上）</label></div><input type="number" name="segment_age_min" id="segment_age_min" class="form-control segment-filter" value="{{ $segmentOld('segment_age_min') }}" min="0" max="150" placeholder="例：20"></div>
                                <div class="form-group mb-0"><label for="segment_age_max" class="form-label">年齢（以下）</label><input type="number" name="segment_age_max" id="segment_age_max" class="form-control segment-filter" value="{{ $segmentOld('segment_age_max') }}" min="0" max="150" placeholder="例：39"></div>
                                <p class="ad-field-help">誕生日が登録されている会員だけが対象です</p>
                            </div>
                            <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="birthday_soon" hidden>
                                <div class="form-group mb-0">
                                    <div class="ad-filter-label"><label for="segment_birthday_soon_mode" class="form-label">誕生日が近い</label></div>
                                    <select name="segment_birthday_soon_mode" id="segment_birthday_soon_mode" class="form-control segment-filter">
                                        <option value="">指定しない</option>
                                        <option value="within" @selected($segmentOld('segment_birthday_soon_mode') === 'within')>今日からN日以内</option>
                                        <option value="exact" @selected($segmentOld('segment_birthday_soon_mode') === 'exact')>ちょうどN日後</option>
                                    </select>
                                </div>
                                <div class="form-group mb-0">
                                    <label for="segment_birthday_soon_days" class="form-label">日数</label>
                                    <select name="segment_birthday_soon_days" id="segment_birthday_soon_days" class="form-control segment-filter" data-ignore-empty="1">
                                        @foreach(\App\Services\Admin\MemberSegmentService::SOON_DAYS as $days)<option value="{{ $days }}" @selected((string) $segmentOld('segment_birthday_soon_days', '7') === (string) $days)>{{ $days === 0 ? '今日' : $days.'日' }}</option>@endforeach
                                    </select>
                                </div>
                                <p class="ad-field-help">毎日のスケジュール配信では「ちょうどN日後」にすると同じ人に重ねて届きません</p>
                            </div>
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
                            @foreach([
                                'acquisition' => ['登録経路', 'segment_acquisition_source_ids', $segmentAcquisitionOptions ?? []],
                                'store' => ['利用店舗', 'segment_store_ids', $segmentStoreOptions ?? []],
                                'order_type' => ['注文の種類（キャンセル以外の注文が1回でもある）', 'segment_order_types', \App\Enums\OrderType::getValues()],
                            ] as $conditionKey => [$conditionLabel, $inputName, $choices])
                                @php $selectedChoices = collect((array) $segmentOld($inputName, []))->map(fn ($value) => (string) $value); @endphp
                                <fieldset class="ad-multi-filter ad-filter-group-wide mb-0 ad-condition-item" data-condition="{{ $conditionKey }}" hidden>
                                    <legend class="form-label">{{ $conditionLabel }} <small>いずれかに当てはまる会員</small></legend>
                                    <div class="ad-multi-filter-options">
                                        @foreach($choices as $choiceId => $choiceLabel)
                                            <label><input type="checkbox" name="{{ $inputName }}[]" value="{{ $choiceId }}" data-label="{{ $choiceLabel }}" class="segment-filter" @checked($selectedChoices->contains((string) $choiceId))><span>{{ $choiceLabel }}</span></label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach
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
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="point_balance" hidden>
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_points_min" class="form-label">ポイント残高（以上）</label></div><input type="number" name="segment_points_min" id="segment_points_min" class="form-control segment-filter" value="{{ $segmentOld('segment_points_min') }}" min="0" placeholder="例：500"></div>
                                    <div class="form-group mb-0"><label for="segment_points_max" class="form-label">ポイント残高（以下）</label><input type="number" name="segment_points_max" id="segment_points_max" class="form-control segment-filter" value="{{ $segmentOld('segment_points_max') }}" min="0" placeholder="例：3000"></div>
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
                                            @foreach(['unused' => '未使用（期限内）', 'used' => '使用済み', 'expired' => '期限切れ（未使用）', 'not_issued' => '未配布'] as $statusValue => $statusLabel)
                                                <option value="{{ $statusValue }}" {{ $segmentOld('segment_coupon_status') === $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="ad-filter-grid ad-condition-item" data-condition="announcement_read" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_announcement_id" class="form-label">お知らせ</label></div>
                                        @php $selectedAnnouncementId = (string) $segmentOld('segment_announcement_id', ''); @endphp
                                        <select name="segment_announcement_id" id="segment_announcement_id" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach($segmentAnnouncementOptions ?? [] as $announcementId => $announcementLabel)
                                                <option value="{{ $announcementId }}" @selected($selectedAnnouncementId === (string) $announcementId)>{{ $announcementLabel }}</option>
                                            @endforeach
                                            {{-- 一覧に出ない古いお知らせを選んで保存した条件も選択を保つ --}}
                                            @if($selectedAnnouncementId !== '' && ! array_key_exists((int) $selectedAnnouncementId, $segmentAnnouncementOptions ?? []))
                                                <option value="{{ $selectedAnnouncementId }}" selected>選択済みのお知らせ（ID: {{ $selectedAnnouncementId }}）</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_announcement_status" class="form-label">既読状態</label>
                                        <select name="segment_announcement_status" id="segment_announcement_status" class="form-control segment-filter" data-ignore-empty="1">
                                            <option value="unread" @selected($segmentOld('segment_announcement_status', 'unread') === 'unread')>未読（届いたがまだ開いていない）</option>
                                            <option value="read" @selected($segmentOld('segment_announcement_status') === 'read')>既読</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="reservation" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_reservation_mode" class="form-label">予約</label></div>
                                        <select name="segment_reservation_mode" id="segment_reservation_mode" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="has" @selected($segmentOld('segment_reservation_mode') === 'has')>予約あり</option>
                                            <option value="none" @selected($segmentOld('segment_reservation_mode') === 'none')>予約なし</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_reservation_days" class="form-label">対象期間（直近と今後の予約）</label>
                                        <select name="segment_reservation_days" id="segment_reservation_days" class="form-control segment-filter" data-ignore-empty="1">@foreach(\App\Services\Admin\MemberSegmentService::PERIOD_DAYS as $days)<option value="{{ $days }}" @selected((int) $segmentOld('segment_reservation_days', 90) === $days)>{{ $days }}日</option>@endforeach</select>
                                    </div>
                                    <p class="ad-field-help">キャンセルした予約は数えません</p>
                                </div>
                                <div class="form-group mb-0 ad-condition-item" data-condition="rank_changed" hidden>
                                    <div class="ad-filter-label"><label for="segment_rank_changed_days" class="form-label">ランクが変わった時期</label></div>
                                    <select name="segment_rank_changed_days" id="segment_rank_changed_days" class="form-control segment-filter">
                                        <option value="">指定しない</option>
                                        @foreach(\App\Services\Admin\MemberSegmentService::PERIOD_DAYS as $days)<option value="{{ $days }}" @selected((int) $segmentOld('segment_rank_changed_days') === $days)>直近{{ $days }}日以内</option>@endforeach
                                    </select>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="coupon_uses" hidden>
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_coupon_uses_min" class="form-label">クーポン利用回数（以上）</label></div><input type="number" name="segment_coupon_uses_min" id="segment_coupon_uses_min" class="form-control segment-filter" value="{{ $segmentOld('segment_coupon_uses_min') }}" min="0" placeholder="例：1"></div>
                                    <div class="form-group mb-0"><label for="segment_coupon_uses_max" class="form-label">クーポン利用回数（以下）</label><input type="number" name="segment_coupon_uses_max" id="segment_coupon_uses_max" class="form-control segment-filter" value="{{ $segmentOld('segment_coupon_uses_max') }}" min="0" placeholder="例：0"></div>
                                </div>
                                @foreach([
                                    'ticket' => ['回数券', 'segment_ticket_status', ['active' => '使える回数券を持っている', 'none' => '使える回数券を持っていない']],
                                    'subscription' => ['サブスク', 'segment_subscription_status', ['active' => '継続中', 'ended' => '解約済み（今は継続なし）', 'none' => '加入したことがない']],
                                    'wallet' => ['Apple Wallet', 'segment_wallet_status', ['registered' => '会員証を追加済み', 'not_registered' => '会員証を未追加']],
                                    'lottery' => ['くじの抽選権', 'segment_lottery_status', ['pending' => 'まだ引いていない抽選権がある', 'none' => '引いていない抽選権がない']],
                                    'nps' => ['アンケートの評価（最新の回答）', 'segment_nps_category', ['promoter' => '高い（9〜10）', 'passive' => '普通（7〜8）', 'detractor' => '低い（0〜6）']],
                                    'line_block' => ['LINEブロック（LINE連携済みの会員）', 'segment_line_block_status', ['blocked' => 'ブロック中', 'not_blocked' => 'ブロックしていない']],
                                    'notification' => ['通知設定', 'segment_notification_preference', \App\Enums\MemberNotificationPreference::getValues()],
                                ] as $conditionKey => [$conditionLabel, $inputName, $choices])
                                    <div class="form-group mb-0 ad-condition-item" data-condition="{{ $conditionKey }}" hidden>
                                        <div class="ad-filter-label"><label for="{{ $inputName }}" class="form-label">{{ $conditionLabel }}</label></div>
                                        <select name="{{ $inputName }}" id="{{ $inputName }}" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach($choices as $choiceValue => $choiceLabel)
                                                <option value="{{ $choiceValue }}" @selected($segmentOld($inputName) === $choiceValue)>{{ $choiceLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                                <div class="ad-filter-grid ad-condition-item" data-condition="survey" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_survey_id" class="form-label">アンケート</label></div>
                                        <select name="segment_survey_id" id="segment_survey_id" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach($segmentSurveyOptions ?? [] as $surveyId => $surveyTitle)
                                                <option value="{{ $surveyId }}" @selected((string) $segmentOld('segment_survey_id') === (string) $surveyId)>{{ $surveyTitle }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_survey_status" class="form-label">回答状況</label>
                                        <select name="segment_survey_status" id="segment_survey_status" class="form-control segment-filter" data-ignore-empty="1">
                                            <option value="unanswered" @selected($segmentOld('segment_survey_status', 'unanswered') === 'unanswered')>未回答</option>
                                            <option value="answered" @selected($segmentOld('segment_survey_status') === 'answered')>回答済み</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="app_last_used" hidden>
                                    <div class="form-group mb-0">
                                        <div class="ad-filter-label"><label for="segment_app_last_used_mode" class="form-label">アプリの利用</label></div>
                                        <select name="segment_app_last_used_mode" id="segment_app_last_used_mode" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            <option value="within_days" @selected($segmentOld('segment_app_last_used_mode') === 'within_days')>N日以内に利用あり</option>
                                            <option value="inactive_days" @selected($segmentOld('segment_app_last_used_mode') === 'inactive_days')>N日以上利用なし</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="segment_app_last_used_days" class="form-label">日数</label>
                                        <select name="segment_app_last_used_days" id="segment_app_last_used_days" class="form-control segment-filter" data-ignore-empty="1">@foreach(\App\Services\Admin\MemberSegmentService::PERIOD_DAYS as $days)<option value="{{ $days }}" @selected((int) $segmentOld('segment_app_last_used_days', 30) === $days)>{{ $days }}日</option>@endforeach</select>
                                    </div>
                                    <p class="ad-field-help">スマホアプリを入れている会員が対象です（LINE だけの会員は含みません）</p>
                                </div>
                                <div class="ad-filter-grid ad-filter-grid--2 ad-condition-item" data-condition="lifetime_sales" hidden>
                                    <div class="form-group mb-0"><div class="ad-filter-label"><label for="segment_lifetime_sales_min" class="form-label">累計購入額（円・以上）</label></div><input type="number" name="segment_lifetime_sales_min" id="segment_lifetime_sales_min" class="form-control segment-filter" value="{{ $segmentOld('segment_lifetime_sales_min') }}" min="0" step="1" placeholder="例：30000"></div>
                                    <div class="form-group mb-0"><label for="segment_lifetime_sales_max" class="form-label">累計購入額（円・以下）</label><input type="number" name="segment_lifetime_sales_max" id="segment_lifetime_sales_max" class="form-control segment-filter" value="{{ $segmentOld('segment_lifetime_sales_max') }}" min="0" step="1" placeholder="例：100000"></div>
                                    <p class="ad-field-help">キャンセル以外の注文（POS会計を含む）の合計です</p>
                                </div>
                                @foreach([
                                    'queue' => ['順番待ちの利用', 'segment_queue_days', \App\Services\Admin\MemberSegmentService::PERIOD_DAYS, '直近%d日以内に利用'],
                                    'line_follow' => ['LINE友だち追加日', 'segment_line_follow_days', \App\Services\Admin\MemberSegmentService::PERIOD_DAYS, '直近%d日以内に追加'],
                                    'points_expiring' => ['ポイントの失効予定', 'segment_points_expiring_days', \App\Services\Admin\MemberSegmentService::SOON_DAYS, '今日から%d日以内に失効'],
                                    'coupon_expiring' => ['クーポンの期限が近い', 'segment_coupon_expiring_days', \App\Services\Admin\MemberSegmentService::SOON_DAYS, '今日から%d日以内に期限切れ'],
                                ] as $conditionKey => [$conditionLabel, $inputName, $dayChoices, $dayFormat])
                                    <div class="form-group mb-0 ad-condition-item" data-condition="{{ $conditionKey }}" hidden>
                                        <div class="ad-filter-label"><label for="{{ $inputName }}" class="form-label">{{ $conditionLabel }}</label></div>
                                        <select name="{{ $inputName }}" id="{{ $inputName }}" class="form-control segment-filter">
                                            <option value="">指定しない</option>
                                            @foreach($dayChoices as $days)
                                                <option value="{{ $days }}" @selected((string) $segmentOld($inputName) === (string) $days)>{{ $days === 0 ? str_replace('今日から%d日以内に', '今日中に', $dayFormat) : sprintf($dayFormat, $days) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
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
