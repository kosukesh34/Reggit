@extends('admin.layout')
@section('title', '会員編集')
@section('page-title', '会員編集 ' . \App\Support\AdminMemberIdentifier::value($view->member))

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.members') }}">会員一覧</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.members.detail', ['id' => $view->member->id]) }}">{{ $view->member->user->name ?? ('会員 '.\App\Support\AdminMemberIdentifier::value($view->member)) }}</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title">会員編集</h1>
</div>

@if($errors->any())
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-circle"></i> 入力内容に誤りがあります。ご確認ください。
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> 会員編集</h3>
        <a href="{{ admin_route('admin.members.detail', ['id' => $view->member->id]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> 詳細に戻る</a>
    </div>
    <div class="card-body">
        <div class="settings-category-tabs" role="tablist" aria-label="会員編集の項目" data-member-edit-tabs>
            <button type="button" role="tab" id="member-edit-tab-profile" aria-controls="member-edit-panel-profile" aria-selected="true">プロフィール項目</button>
            <button type="button" role="tab" id="member-edit-tab-benefits" aria-controls="member-edit-panel-benefits" aria-selected="false" tabindex="-1">ポイント・スタンプ</button>
        </div>
        <form id="member-edit-form" action="{{ admin_route('admin.members.update', ['id' => $view->member->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="settings-tab-panel" role="tabpanel" id="member-edit-panel-profile" aria-labelledby="member-edit-tab-profile" data-member-edit-panel>
            <section class="form-section mb-4">
                <h4 class="form-section-title">連絡先</h4>
                @php
                    [$nameLast, $nameFirst] = \App\Support\PersonNameParts::split(old('name', $view->member->user->name ?? ''));
                    [$kanaLast, $kanaFirst] = \App\Support\PersonNameParts::split(old('kana', $view->member->user->kana ?? ''));
                    $nameLast = old('name_last', $nameLast);
                    $nameFirst = old('name_first', $nameFirst);
                    $kanaLast = old('kana_last', $kanaLast);
                    $kanaFirst = old('kana_first', $kanaFirst);
                @endphp
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="name_last" class="form-label">姓</label>
                        <input type="text" name="name_last" id="name_last" class="form-control" value="{{ $nameLast }}" maxlength="85" autocomplete="family-name">
                        @error('name_last')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="name_first" class="form-label">名</label>
                        <input type="text" name="name_first" id="name_first" class="form-control" value="{{ $nameFirst }}" maxlength="85" autocomplete="given-name">
                        @error('name_first')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="kana_last" class="form-label">フリガナ（姓）</label>
                        <input type="text" name="kana_last" id="kana_last" class="form-control" value="{{ $kanaLast }}" maxlength="85" placeholder="ヤマダ">
                        @error('kana_last')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        @error('kana')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="kana_first" class="form-label">フリガナ（名）</label>
                        <input type="text" name="kana_first" id="kana_first" class="form-control" value="{{ $kanaFirst }}" maxlength="85" placeholder="タロウ">
                        @error('kana_first')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $view->member->user->email ?? '') }}" maxlength="255">
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="phone" class="form-label">電話番号</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $view->member->user->phone ?? '') }}" maxlength="20">
                        @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title">会員情報</h4>
                <div class="row">
                    <div class="col-md-6 form-group">
                        @if(\App\Support\AdminMemberIdentifier::usesId($view->member->project_id))
                        <label class="form-label">会員ID</label>
                        <input type="text" class="form-control" value="{{ $view->member->id }}" readonly>
                        <input type="hidden" name="member_number" value="{{ $view->member->member_number }}">
                        @else
                        <label for="member_number" class="form-label">会員番号 <span class="text-danger">*</span></label>
                        <input type="text" name="member_number" id="member_number" class="form-control" value="{{ old('member_number', $view->member->member_number) }}" maxlength="64" required>
                        @error('member_number')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                        @endif
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active" {{ old('status', $view->member->status) === 'active' ? 'selected' : '' }}>有効</option>
                            <option value="inactive" {{ old('status', $view->member->status) === 'inactive' ? 'selected' : '' }}>無効</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="rank" class="form-label">ランク</label>
                        <select name="rank" id="rank" class="form-control">
                            <option value="">— 未設定 —</option>
                            @foreach($view->rankOptions ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ old('rank', $view->member->rank) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="birthday" class="form-label">生年月日</label>
                    <input type="date" name="birthday" id="birthday" class="form-control" value="{{ old('birthday', $view->member->birthday ? $view->member->birthday->format('Y-m-d') : '') }}">
                    @error('birthday')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="postal_code" class="form-label">郵便番号</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code', $view->member->postal_code ?? '') }}" maxlength="8" placeholder="例: 100-0001">
                    @error('postal_code')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="address" class="form-label">住所</label>
                    <textarea name="address" id="address" class="form-control" rows="2" maxlength="85">{{ old('address', $view->member->displayAddress()) }}</textarea>
                    @error('address')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="sex" class="form-label">性別</label>
                    <select name="sex" id="sex" class="form-control">
                        <option value="">— 未設定 —</option>
                        <option value="1" {{ old('sex', $view->member->sex) === '1' ? 'selected' : '' }}>男性</option>
                        <option value="2" {{ old('sex', $view->member->sex) === '2' ? 'selected' : '' }}>女性</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="customer_store_id" class="form-label">対象店舗</label>
                    <select name="customer_store_id" id="customer_store_id" class="form-control">
                        <option value="">未設定</option>
                        @foreach($view->customerStoreOptions as $option)
                        <option value="{{ $option['value'] }}" @selected((string) old('customer_store_id', $view->member->customer_store_id) === $option['value'])>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="mail_receive_flag" class="form-label">店舗からの案内メールを受け取る</label>
                    <select name="mail_receive_flag" id="mail_receive_flag" class="form-control">
                        <option value="">— 未設定 —</option>
                        <option value="1" {{ old('mail_receive_flag', $view->member->mail_receive_flag) === '1' ? 'selected' : '' }}>許可する</option>
                        <option value="0" {{ old('mail_receive_flag', $view->member->mail_receive_flag) === '0' ? 'selected' : '' }}>許可しない</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notification_preference" class="form-label">お知らせ通知</label>
                    <select name="notification_preference" id="notification_preference" class="form-control" required>
                        @foreach(\App\Enums\MemberNotificationPreference::getValues() as $value => $label)
                            <option value="{{ $value }}" {{ old('notification_preference', \App\Enums\MemberNotificationPreference::normalize($view->member->notification_preference)) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('notification_preference')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </section>

            @php
                $profileExtra = is_array($view->member->profile_extra) ? $view->member->profile_extra : [];
            @endphp
            @if($view->customProfileFields !== [])
            <section class="form-section mb-4">
                <h4 class="form-section-title">追加プロフィール項目</h4>
                <p class="text-muted small mb-3">プロフィール設定で追加した項目です。スマレジの会員自由項目と連携します。</p>
                @foreach($view->customProfileFields as $cf)
                    @php
                        $fieldKey = (string) ($cf['key'] ?? '');
                        $fieldLabel = (string) ($cf['label'] ?? $fieldKey);
                        $inputType = (string) ($cf['input_type'] ?? \App\Enums\ProfileCustomFieldInputType::TEXT);
                        $values = isset($cf['values']) && is_array($cf['values']) ? $cf['values'] : [];
                        $description = (string) ($cf['description'] ?? '');
                        $current = old('custom_profile.'.$fieldKey, $profileExtra[$fieldKey] ?? null);
                    @endphp
                    @if($fieldKey === '')
                        @continue
                    @endif
                    <div class="form-group">
                        <label class="form-label" @if($inputType !== \App\Enums\ProfileCustomFieldInputType::RADIO && $inputType !== \App\Enums\ProfileCustomFieldInputType::CHECKBOX) for="custom_profile_{{ $fieldKey }}" @endif>
                            {{ $fieldLabel }}
                            @if(!empty($cf['required'])) <span class="text-danger">*</span>@endif
                        </label>
                        @if($inputType === \App\Enums\ProfileCustomFieldInputType::TEXTAREA)
                            <textarea name="custom_profile[{{ $fieldKey }}]" id="custom_profile_{{ $fieldKey }}" class="form-control" rows="3" maxlength="500">{{ is_array($current) ? '' : $current }}</textarea>
                        @elseif($inputType === \App\Enums\ProfileCustomFieldInputType::SELECTBOX)
                            <select name="custom_profile[{{ $fieldKey }}]" id="custom_profile_{{ $fieldKey }}" class="form-control">
                                <option value="">未選択</option>
                                @foreach($values as $option)
                                    <option value="{{ $option }}" {{ (string) $current === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif($inputType === \App\Enums\ProfileCustomFieldInputType::RADIO)
                            <div>
                                @foreach($values as $optionIndex => $option)
                                    <label class="radio-label-inline me-3">
                                        <input type="radio" name="custom_profile[{{ $fieldKey }}]" value="{{ $option }}" {{ (string) $current === (string) $option ? 'checked' : '' }}>
                                        {{ $option }}
                                    </label>
                                @endforeach
                            </div>
                        @elseif($inputType === \App\Enums\ProfileCustomFieldInputType::CHECKBOX)
                            @php
                                $checkedValues = is_array($current) ? $current : (is_string($current) && $current !== '' ? [$current] : []);
                            @endphp
                            <div>
                                @foreach($values as $option)
                                    <label class="checkbox-label d-block mb-1">
                                        <input type="checkbox" name="custom_profile[{{ $fieldKey }}][]" value="{{ $option }}" {{ in_array($option, $checkedValues, true) ? 'checked' : '' }}>
                                        {{ $option }}
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <input type="text" name="custom_profile[{{ $fieldKey }}]" id="custom_profile_{{ $fieldKey }}" class="form-control" value="{{ is_array($current) ? '' : $current }}" maxlength="500">
                        @endif
                        @if($description !== '')
                            <div class="text-muted small mt-1">{{ $description }}</div>
                        @endif
                        @error('custom_profile.'.$fieldKey)<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                @endforeach
            </section>
            @endif

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>変更を保存</button>
                <a href="{{ admin_route('admin.members.detail', ['id' => $view->member->id]) }}" class="btn btn-secondary">キャンセル</a>
            </div>
            </div>
        </form>

        <div class="settings-tab-panel" role="tabpanel" id="member-edit-panel-benefits" aria-labelledby="member-edit-tab-benefits" data-member-edit-panel hidden>
            <section class="form-section mb-4">
                <h4 class="form-section-title">ポイント</h4>
                <p class="text-muted small mb-3">「ポイントを直接指定」に数値を入れると、その値に合わせて調整されます（例: 500）。加算・減算と併用した場合は直接指定が優先されます。保存時にポイント履歴へ記録され、Apple Wallet / Google Wallet も更新されます。</p>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="point_set" class="form-label">ポイントを直接指定</label>
                        <input type="number" name="point_set" id="point_set" class="form-control" form="member-edit-form" value="{{ old('point_set') }}" min="0" step="1" placeholder="例: 500">
                        @error('point_set')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_add" class="form-label">ポイント加算</label>
                        <input type="number" name="point_add" id="point_add" class="form-control" form="member-edit-form" value="{{ old('point_add', 0) }}" min="0" step="1" placeholder="0">
                        @error('point_add')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_subtract" class="form-label">ポイント減算</label>
                        <input type="number" name="point_subtract" id="point_subtract" class="form-control" form="member-edit-form" value="{{ old('point_subtract', 0) }}" min="0" step="1" placeholder="0">
                        @error('point_subtract')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_description" class="form-label">理由（管理用メモ）</label>
                        <input type="text" name="point_description" id="point_description" class="form-control" form="member-edit-form" value="{{ old('point_description') }}" maxlength="255" placeholder="例: 管理画面での調整">
                    </div>
                </div>
                <p class="text-muted small mb-3">現在のポイント: <strong>{{ number_format($view->member->points) }}pt</strong></p>
                <button type="submit" class="btn btn-primary" form="member-edit-form"><i class="fas fa-check me-1"></i>ポイントの変更を保存</button>
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title"><i class="fas fa-stamp"></i> スタンプ</h4>
        @if($view->activeStampCards->isEmpty())
            <p class="text-muted mb-0">スタンプカードがありません。<a href="{{ admin_route('admin.stamps.create') }}">スタンプカードを作成</a>してください。</p>
        @else
            @if($view->member->memberStamps->isNotEmpty())
            <p class="text-muted small mb-3">現在:
                @foreach($view->member->memberStamps as $ms)
                    {{ $ms->stampCard->name ?? '—' }} {{ $ms->current_stamps }} / {{ $ms->stampCard->required_stamps ?? 0 }}@if(!$loop->last)、@endif
                @endforeach
            </p>
            @endif
            <form action="{{ admin_route('admin.members.adjust-stamps', ['id' => $view->member->id]) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label for="stamp_card_id" class="form-label">スタンプカード <span class="text-danger">*</span></label>
                        <select name="stamp_card_id" id="stamp_card_id" class="form-control" required>
                            <option value="">選択してください</option>
                            @foreach($view->activeStampCards as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->name }}（{{ $sc->required_stamps }}個で達成）</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="stamp_add" class="form-label">加算</label>
                        <input type="number" name="stamp_add" id="stamp_add" class="form-control" value="{{ old('stamp_add', 0) }}" min="0" max="100" step="1">
                    </div>
                    <div class="col-md-2 form-group">
                        <label for="stamp_subtract" class="form-label">減算</label>
                        <input type="number" name="stamp_subtract" id="stamp_subtract" class="form-control" value="{{ old('stamp_subtract', 0) }}" min="0" max="100" step="1">
                    </div>
                    <div class="col-md-4 form-group">
                        <label for="stamp_description" class="form-label">理由（管理用メモ）</label>
                        <input type="text" name="stamp_description" id="stamp_description" class="form-control" value="{{ old('stamp_description') }}" maxlength="255" placeholder="例: 管理画面での調整">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-stamp"></i> スタンプを更新</button>
            </form>
        @endif
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title"><i class="fas fa-ticket-alt"></i> クーポン</h4>
        @if($view->activeCoupons->isEmpty())
            <p class="text-muted">配布可能なクーポンがありません。<a href="{{ admin_route('admin.coupons.create') }}">クーポンを作成</a>してください。</p>
        @else
            <form action="{{ admin_route('admin.members.issue-coupon', ['id' => $view->member->id]) }}" method="POST" class="form-inline mb-3">
                @csrf
                <div class="form-group me-2">
                    <label for="coupon_id" class="form-label me-1">クーポン</label>
                    <select name="coupon_id" id="coupon_id" class="form-control" required>
                        <option value="">選択してください</option>
                        @foreach($view->activeCoupons as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}（{{ $c->code }}）</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-gift"></i> 配布する</button>
            </form>
        @endif

        <h4 class="h6">保有クーポン</h4>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>クーポン名</th>
                    <th>状態</th>
                    <th>発行日</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($view->member->memberCoupons as $mc)
                <tr>
                    <td>{{ $mc->coupon_name_snapshot ?? $mc->coupon->name ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ $mc->status === 'unused' ? 'success' : 'secondary' }}">
                            {{ $mc->status === 'unused' ? '未使用' : '使用済み' }}
                        </span>
                    </td>
                    <td>{{ $mc->issued_at ? $mc->issued_at->format('Y/m/d') : '—' }}</td>
                    <td class="table-actions-cell">
                        @if($mc->status === 'unused')
                        <form action="{{ admin_route('admin.members.delete-coupon', ['id' => $view->member->id, 'memberCouponId' => $mc->id]) }}" method="POST" class="d-inline delete-form" data-item-name="{{ $mc->coupon_name_snapshot ?? 'クーポン' }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger no-loading"><i class="fas fa-trash"></i> 削除</button>
                        </form>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="empty-state">
                        <i class="fas fa-ticket-alt"></i>
                        <p>保有クーポンがありません</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
            </section>

            <section class="form-section mb-0">
                <h4 class="form-section-title"><i class="fas fa-store"></i> 来店登録</h4>
        <form action="{{ admin_route('admin.members.record-visit', ['id' => $view->member->id]) }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="visited_at" class="form-label">来店日時 <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="visited_at" id="visited_at" class="form-control" value="{{ old('visited_at', now()->format('Y-m-d\\TH:i')) }}" required>
                </div>
                <div class="col-md-6 form-group">
                    <label for="visit_note" class="form-label">メモ</label>
                    <input type="text" name="note" id="visit_note" class="form-control" value="{{ old('note') }}" maxlength="500" placeholder="任意">
                </div>
                <div class="col-md-2 form-group d-flex align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> 登録</button>
                </div>
            </div>
        </form>
            </section>
        </div>
    </div>
</div>
<script>
(function () {
    const tablist = document.querySelector('[data-member-edit-tabs]');
    const form = document.getElementById('member-edit-form');
    if (tablist === null || form === null) {
        return;
    }
    const buttons = Array.from(tablist.querySelectorAll('[role="tab"]'));
    const panels = Array.from(document.querySelectorAll('[data-member-edit-panel]'));
    const activate = (id) => {
        buttons.forEach((button) => {
            const selected = button.getAttribute('aria-controls') === id;
            button.setAttribute('aria-selected', selected ? 'true' : 'false');
            button.tabIndex = selected ? 0 : -1;
        });
        panels.forEach((panel) => {
            panel.hidden = panel.id !== id;
        });
    };
    buttons.forEach((button, index) => {
        button.addEventListener('click', () => activate(button.getAttribute('aria-controls')));
        button.addEventListener('keydown', (event) => {
            let next = null;
            if (event.key === 'ArrowRight') next = buttons[(index + 1) % buttons.length];
            if (event.key === 'ArrowLeft') next = buttons[(index + buttons.length - 1) % buttons.length];
            if (event.key === 'Home') next = buttons[0];
            if (event.key === 'End') next = buttons[buttons.length - 1];
            if (next === null) return;
            event.preventDefault();
            activate(next.getAttribute('aria-controls'));
            next.focus();
        });
    });
    const invalidPanel = panels.find((panel) => panel.querySelector('.invalid-feedback'));
    if (invalidPanel) {
        activate(invalidPanel.id);
    }
    form.addEventListener('submit', (event) => {
        if (form.checkValidity()) {
            return;
        }
        const invalid = form.querySelector(':invalid');
        const panel = invalid ? invalid.closest('[role="tabpanel"]') : null;
        if (panel === null || panel.hidden !== true) {
            return;
        }
        event.preventDefault();
        activate(panel.id);
        window.requestAnimationFrame(() => invalid.reportValidity());
    });
})();
</script>
@endsection
