@php
    $isCouponDelivery = ($isCouponDelivery ?? false) === true;
    $oldAudienceType = $oldAudienceType ?? 'immediate_bulk';
@endphp
<div class="andel-card" role="region" id="delivery-audience" aria-label="配信対象">
    <fieldset class="ad-delivery-fieldset">
        <legend>配信対象 <span class="ad-required">必須</span></legend>
        <div class="ad-delivery-types ad-delivery-types--audience">
            <label class="ad-delivery-choice {{ $oldAudienceType === 'immediate_bulk' ? 'is-selected' : '' }}">
                <input type="radio" name="audience_type" value="immediate_bulk" {{ $oldAudienceType === 'immediate_bulk' ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-users" aria-hidden="true"></i></span>
                <span><strong>全会員</strong><small>現在登録されている会員全員</small></span>
                <span class="ad-choice-badge">{{ number_format($view->memberCount) }}人</span>
            </label>
            <label class="ad-delivery-choice {{ $oldAudienceType === 'personal' ? 'is-selected' : '' }}">
                <input type="radio" name="audience_type" value="personal" {{ $oldAudienceType === 'personal' ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-user-check" aria-hidden="true"></i></span>
                <span><strong>会員を選ぶ</strong><small>指定した会員だけに配信</small></span>
                <span class="ad-choice-check"><i class="fas fa-check" aria-hidden="true"></i></span>
            </label>
            <label class="ad-delivery-choice {{ $oldAudienceType === 'segment' ? 'is-selected' : '' }}">
                <input type="radio" name="audience_type" value="segment" {{ $oldAudienceType === 'segment' ? 'checked' : '' }}>
                <span class="ad-choice-icon"><i class="fas fa-filter" aria-hidden="true"></i></span>
                <span><strong>条件で絞る</strong><small>属性・RFM・来店・購入条件で配信</small></span>
                <span class="ad-choice-badge" id="segmentChoiceCount">人数を確認</span>
            </label>
        </div>
    </fieldset>

    <div id="personalTargetBlock" class="ad-context-panel ad-context-panel--stack" hidden>
        <div class="ad-context-title">
            <span class="ad-context-icon"><i class="fas fa-user-plus" aria-hidden="true"></i></span>
            <div><strong>配信する会員を選択</strong><small>{{ \App\Support\AdminMemberIdentifier::label() }}または名前で検索できます</small></div>
        </div>
        <div class="ad-member-search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="memberSearch" class="form-control" placeholder="{{ \App\Support\AdminMemberIdentifier::label() }}・名前で検索" autocomplete="off">
        </div>
        <div class="ad-member-csv">
            <label for="memberCsv" class="btn btn-secondary">CSVから追加</label>
            <input type="file" id="memberCsv" accept=".csv,text/csv" hidden>
            <button type="button" class="admin-help" data-tooltip="1列目に{{ \App\Support\AdminMemberIdentifier::label() }}を入れてください。1行目が「{{ \App\Support\AdminMemberIdentifier::label() }}」でも読み込めます。&#10;&#10;{{ \App\Support\AdminMemberIdentifier::label() }}&#10;{{ \App\Support\AdminMemberIdentifier::usesId() ? '1234' : 'M00001234' }}&#10;{{ \App\Support\AdminMemberIdentifier::usesId() ? '5678' : 'M00005678' }}" aria-label="CSVの形式を見る">?</button>
            <p id="memberCsvMessage" class="ad-field-help" hidden></p>
        </div>
        <div id="memberSearchResults" class="ad-member-results" hidden></div>
        <div id="selectedMembers" class="ad-selected-members"></div>
        <div id="targetMemberIdsHidden"></div>
    </div>

    <div id="segmentBlock" class="ad-segment-panel" hidden>
        @include('admin.partials._delivery_segment_filters', ['isCouponDelivery' => $isCouponDelivery])
    </div>

    <details class="ad-audience-preview" id="audiencePreviewDetails">
        <summary class="ad-audience-preview-head">
            <div><strong><i class="fas fa-list" aria-hidden="true"></i> 配信対象者一覧</strong><small id="audiencePreviewHint">この一覧の対象者を配信時点で保存します</small></div>
            <span id="audiencePreviewCount">確認中…</span>
        </summary>
        <div id="audiencePreviewPanel">
            <div class="ad-audience-preview-table-wrap">
                <table class="ad-audience-preview-table">
                    <thead><tr><th>会員名</th><th>タグ</th><th>{{ \App\Support\AdminMemberIdentifier::label() }}</th><th>通知可否</th><th>チャネル</th><th>ランク</th></tr></thead>
                    <tbody id="audiencePreviewRows"><tr><td colspan="6" class="ad-audience-loading">対象者を読み込んでいます…</td></tr></tbody>
                </table>
            </div>
            <div class="ad-audience-pagination">
                <button type="button" class="btn btn-sm btn-secondary" id="audiencePrevPage" disabled><i class="fas fa-chevron-left"></i> 前へ</button>
                <span id="audiencePageLabel">1 / 1ページ</span>
                <button type="button" class="btn btn-sm btn-secondary" id="audienceNextPage" disabled>次へ <i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </details>
</div>
