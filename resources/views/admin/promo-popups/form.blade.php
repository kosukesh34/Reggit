@extends('admin.layout')
@section('title', $view->popup ? 'ポップアップを編集' : 'ポップアップを作成')
@section('page-title', $view->popup ? 'ポップアップを編集' : 'ポップアップを作成')

@php
    use App\Domain\Promotion\PromoPopupAudience;
    use App\Models\PromoPopup;

    $popup = $view->popup;
    $audience = old('audience', $popup?->audience ?? PromoPopupAudience::ALL);
    $selectedTagIds = array_map('intval', old('audience_tag_ids', $popup?->audience_tag_ids ?? []));
    $selectedRanks = old('audience_ranks', $popup?->audience_ranks ?? []);
    $selectedStoreIds = array_map('intval', old('store_ids', $popup?->store_ids ?? []));
    $dateValue = fn ($value) => $value?->format('Y-m-d\TH:i');
    $displayType = old('display_type', $popup?->display_type ?? 'modal');
    $displayCards = [
        'fullscreen' => ['fa-expand', '画面全体で大きく。キャンペーンの告知に'],
        'modal' => ['fa-window-restore', '画面中央のカード。お知らせ向き'],
        'bottom_sheet' => ['fa-arrow-up-from-bracket', '下からせり上がるシート'],
        'banner' => ['fa-grip-lines', '下部の細い帯。操作を妨げない'],
    ];
    $audienceCards = [
        PromoPopupAudience::ALL => 'fa-users',
        PromoPopupAudience::MEMBERS => 'fa-id-card',
        PromoPopupAudience::GUESTS => 'fa-user-plus',
        PromoPopupAudience::SEGMENT => 'fa-tags',
    ];
@endphp

@section('content')
@include('admin.engagement._assets')
<div class="eg-page">
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.promo-popups.index') }}">ポップアップ</a>
    <span class="breadcrumb-sep">/</span>
    <span>{{ $popup ? '編集' : '作成' }}</span>
</nav>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <strong>入力内容を確認してください</strong>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ $popup ? admin_route('admin.promo-popups.update', ['id' => $popup->id]) : admin_route('admin.promo-popups.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($popup) @method('PUT') @endif
    <div class="eg-studio">
        <div class="eg-studio-main">
            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-shapes" aria-hidden="true"></i></span>
                    <div><h2>表示形式</h2><p>選ぶと右のプレビューが切り替わります。</p></div>
                </div>
                <div class="eg-choices" role="radiogroup" aria-label="表示形式">
                    @foreach(PromoPopup::DISPLAY_TYPES as $value => $label)
                    <label class="eg-choice">
                        <input type="radio" name="display_type" value="{{ $value }}" {{ $displayType === $value ? 'checked' : '' }} required>
                        <span class="eg-choice-icon"><i class="fas {{ $displayCards[$value][0] }}" aria-hidden="true"></i></span>
                        <span><strong>{{ $label }}</strong><small>{{ $displayCards[$value][1] }}</small></span>
                    </label>
                    @endforeach
                </div>
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-pen-to-square" aria-hidden="true"></i></span>
                    <div><h2>内容</h2></div>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="title">タイトル<span class="eg-required">必須</span></label>
                    <input type="text" id="title" name="title" class="form-control" required maxlength="120" value="{{ old('title', $popup?->title) }}" placeholder="例: 夏のキャンペーン開催中">
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="body">本文</label>
                    <textarea id="body" name="body" class="form-control" rows="3" maxlength="1000" placeholder="例: 8月末まで、全品ポイント2倍">{{ old('body', $popup?->body) }}</textarea>
                </div>
                <div class="eg-field">
                    <label class="eg-label" for="image">画像</label>
                    @if($popup?->image_url)
                    <div class="eg-image-current">
                        <img src="{{ $popup->image_url }}" alt="現在の画像">
                        <label class="checkbox-label"><input type="checkbox" name="remove_image" value="1"> 画像を外す</label>
                    </div>
                    @endif
                    <input type="file" id="image" name="image" class="form-control" accept="image/png,image/jpeg,image/gif,image/webp">
                    <p class="eg-hint">2MBまで。全画面は縦長（9:16）、それ以外は横長（16:9）がきれいに表示されます。</p>
                </div>
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon"><i class="fas fa-hand-pointer" aria-hidden="true"></i></span>
                    <div><h2>ボタン</h2><p>リンク先は入力欄をクリックすると、アプリの画面と受付中のアンケートから選べます。空欄なら閉じるだけです。</p></div>
                </div>
                <div class="eg-field">
                    <span class="eg-label">1つ目のボタン</span>
                    <div class="eg-row">
                        <input type="text" id="button_label" name="button_label" class="form-control" maxlength="40" value="{{ old('button_label', $popup?->button_label) }}" placeholder="文言 例: クーポンを見る" aria-label="1つ目のボタンの文言">
                        <input type="text" id="link_url" name="link_url" class="form-control" maxlength="500" list="promo-link-options" autocomplete="off" value="{{ old('link_url', $popup?->link_url) }}" placeholder="リンク先 例: /coupon、https://…" aria-label="1つ目のボタンのリンク先">
                    </div>
                </div>
                <div class="eg-field">
                    <span class="eg-label">2つ目のボタン（任意）</span>
                    <div class="eg-row">
                        <input type="text" name="secondary_button_label" class="form-control" maxlength="40" value="{{ old('secondary_button_label', $popup?->secondary_button_label) }}" placeholder="文言 例: あとで" aria-label="2つ目のボタンの文言">
                        <input type="text" name="secondary_link_url" class="form-control" maxlength="500" list="promo-link-options" autocomplete="off" value="{{ old('secondary_link_url', $popup?->secondary_link_url) }}" placeholder="リンク先（空欄なら閉じるだけ）" aria-label="2つ目のボタンのリンク先">
                    </div>
                    <p class="eg-hint">下部バナーでは、バナーを押したときに1つ目のボタンのリンク先を開きます。</p>
                </div>
                <datalist id="promo-link-options">
                    @foreach($view->linkOptions as $path => $label)
                    <option value="{{ $path }}">{{ $label }}</option>
                    @endforeach
                </datalist>
            </section>

            <section class="eg-card eg-audience">
                <div class="eg-card-head">
                    <span class="eg-icon eg-icon--orange"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                    <div><h2>表示する相手</h2></div>
                </div>
                <div class="eg-choices" role="radiogroup" aria-label="表示する相手">
                    @foreach(PromoPopupAudience::getValues() as $value => $label)
                    <label class="eg-choice">
                        <input type="radio" name="audience" value="{{ $value }}" {{ $audience === $value ? 'checked' : '' }}>
                        <span class="eg-choice-icon"><i class="fas {{ $audienceCards[$value] }}" aria-hidden="true"></i></span>
                        <span><strong>{{ $label }}</strong></span>
                    </label>
                    @endforeach
                </div>
                <div class="eg-segment-panel">
                    <fieldset class="eg-fieldset">
                        <legend>タグ</legend>
                        @if($view->tags === [])
                            <p class="eg-hint">会員タグがありません。</p>
                        @else
                        <div class="eg-chips">
                            @foreach($view->tags as $tagId => $tagName)
                            <label class="eg-chip"><input type="checkbox" name="audience_tag_ids[]" value="{{ $tagId }}" {{ in_array($tagId, $selectedTagIds, true) ? 'checked' : '' }}>{{ $tagName }}</label>
                            @endforeach
                        </div>
                        @endif
                    </fieldset>
                    <fieldset class="eg-fieldset">
                        <legend>ランク</legend>
                        <div class="eg-chips">
                            @foreach($view->ranks as $rankKey => $rankLabel)
                            <label class="eg-chip"><input type="checkbox" name="audience_ranks[]" value="{{ $rankKey }}" {{ in_array($rankKey, $selectedRanks, true) ? 'checked' : '' }}>{{ $rankLabel }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <p class="eg-hint">選んだタグ・ランクのどれか1つに当てはまる会員に表示します。何も選ばなければ会員全員です。</p>
                </div>
                @if($view->stores !== [])
                <fieldset class="eg-fieldset eg-hint--spaced">
                    <legend>表示する店舗</legend>
                    <div class="eg-chips">
                        @foreach($view->stores as $storeId => $storeName)
                        <label class="eg-chip"><input type="checkbox" name="store_ids[]" value="{{ $storeId }}" {{ in_array($storeId, $selectedStoreIds, true) ? 'checked' : '' }}>{{ $storeName }}</label>
                        @endforeach
                    </div>
                    <p class="eg-hint">何も選ばなければ全店舗。選ぶと、アプリで利用中の店舗（なければ会員の対象店舗）が当てはまる人にだけ表示します。</p>
                </fieldset>
                @endif
            </section>

            <section class="eg-card">
                <div class="eg-card-head">
                    <span class="eg-icon eg-icon--gray"><i class="fas fa-calendar-days" aria-hidden="true"></i></span>
                    <div><h2>表示のしかた</h2></div>
                </div>
                <div class="eg-field">
                    <span class="eg-label">表示期間</span>
                    <div class="eg-row">
                        <input type="datetime-local" id="starts_at" name="starts_at" class="form-control" value="{{ old('starts_at', $dateValue($popup?->starts_at)) }}" aria-label="表示開始">
                        <span class="eg-row-text">〜</span>
                        <input type="datetime-local" id="ends_at" name="ends_at" class="form-control" value="{{ old('ends_at', $dateValue($popup?->ends_at)) }}" aria-label="表示終了">
                    </div>
                    <p class="eg-hint">空欄なら期限なし。</p>
                </div>
                <div class="eg-row">
                    <div class="eg-field">
                        <label class="eg-label" for="frequency">表示回数</label>
                        <select id="frequency" name="frequency" class="form-control">
                            @foreach(PromoPopup::FREQUENCIES as $value => $label)
                            <option value="{{ $value }}" {{ old('frequency', $popup?->frequency ?? 'once') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="eg-field">
                        <label class="eg-label" for="sort_order">優先度</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" min="-999" max="999" value="{{ old('sort_order', $popup?->sort_order ?? 0) }}">
                    </div>
                </div>
                <div class="eg-field">
                    <input type="hidden" name="hide_when_answered" value="0">
                    <label class="checkbox-label"><input type="checkbox" name="hide_when_answered" value="1" {{ old('hide_when_answered', $popup?->hide_when_answered ?? true) ? 'checked' : '' }}> リンク先のアンケートに回答した人には表示しない</label>
                </div>
                <p class="eg-hint">表示回数は、会員はLINEとアプリをまたいで1人ごとに、会員登録前の人は端末ごとに数えます。内容を編集すると「1人1回だけ」でももう一度表示します。表示中のものが複数あるときは、優先度の高い1つだけを出します。</p>
            </section>
        </div>

        <aside class="eg-studio-aside" aria-label="保存とプレビュー">
            <section class="eg-card eg-save">
                <input type="hidden" name="is_active" value="0">
                <div class="eg-switch-row">
                    <div><strong>表示する</strong><small>オフにすると下書きとして保存します</small></div>
                    <label class="eg-switch"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $popup?->is_active ?? true) ? 'checked' : '' }} aria-label="表示する"><span></span></label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save" aria-hidden="true"></i> 保存</button>
                <a href="{{ admin_route('admin.promo-popups.index') }}" class="btn btn-secondary">一覧へ戻る</a>
            </section>
            <section class="eg-card" data-promo-popup-preview data-image-url="{{ $popup?->image_url }}">
                <div class="eg-card-head eg-card-head--compact">
                    <span class="eg-icon eg-icon--green"><i class="fas fa-mobile-screen" aria-hidden="true"></i></span>
                    <div><h2>プレビュー</h2><p>アプリでの見え方</p></div>
                </div>
                <x-admin.app-preview id="promoPopupLivePreview" title="実際の会員アプリのポップアップ" path="/home" kind="promo-popup" />
            </section>
        </aside>
    </div>
</form>

@if($popup)
<div class="eg-danger-zone">
    <p>削除すると表示回数・タップ回数の記録も消えます。止めるだけなら「表示する」をオフにしてください。</p>
    <form action="{{ admin_route('admin.promo-popups.destroy', ['id' => $popup->id]) }}" method="POST" class="delete-form" data-item-name="ポップアップ「{{ $popup->title }}」">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-danger"><i class="fas fa-trash" aria-hidden="true"></i> 削除</button>
    </form>
</div>
@endif
</div>
@endsection
