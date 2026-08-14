@extends('admin.layout')

@section('title', 'お知らせ配信')
@section('page-title', 'お知らせ配信')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.announcements') }}">お知らせ</a>
    <span class="breadcrumb-sep">/</span>
    <span>配信</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-paper-plane"></i> お知らせ配信</h1>
    <p class="page-header-desc">一斉配信・個人宛・予約配信でお知らせを送ります</p>
</div>

@if($errors->any())
    <div class="alert alert-error" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif

<form action="{{ admin_route('admin.announcements.delivery.store') }}" method="POST" id="deliveryForm" enctype="multipart/form-data">
    @csrf
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="card-title">配信内容</h2>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="title" class="form-label">タイトル <span class="text-danger">*</span></label>
                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required maxlength="200" placeholder="例: キャンペーンのお知らせ">
            </div>
            <div class="form-group">
                <label for="body" class="form-label">本文</label>
                <textarea name="body" id="body" class="form-control" rows="5" maxlength="5000" placeholder="お知らせの内容">{{ old('body') }}</textarea>
            </div>
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'image_file',
                'logoClearName' => 'image_file_clear',
                'logoFieldId' => 'ann_image',
                'logoCurrentUrl' => (string) old('image_url', ''),
                'logoLabel' => '画像',
            ])
            <div class="form-group">
                <label for="image_url" class="form-label">または 画像URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/image.png">
            </div>
            <div class="form-group">
                <label class="form-label">配信種別 <span class="text-danger">*</span></label>
                <div class="delivery-type-options">
                    <label class="radio-label">
                        <input type="radio" name="delivery_type" value="immediate_bulk" {{ old('delivery_type', 'immediate_bulk') === 'immediate_bulk' ? 'checked' : '' }} data-target="bulk">
                        <span>一斉配信</span>
                        <small class="d-block text-muted">登録会員全員（{{ number_format($view->memberCount) }}人）に即時配信</small>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="delivery_type" value="personal" {{ old('delivery_type') === 'personal' ? 'checked' : '' }} data-target="personal">
                        <span>個人宛配信</span>
                        <small class="d-block text-muted">指定した会員のみに配信</small>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="delivery_type" value="scheduled" {{ old('delivery_type') === 'scheduled' ? 'checked' : '' }} data-target="scheduled">
                        <span>予約配信</span>
                        <small class="d-block text-muted">指定日時に全員へ配信</small>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="delivery_type" value="segment" {{ old('delivery_type') === 'segment' ? 'checked' : '' }} data-target="segment">
                        <span>セグメント配信（属性絞り込み）</span>
                        <small class="d-block text-muted">チャネル・誕生月・来店・売上などの条件に該当する会員のみに配信（AND）</small>
                    </label>
                </div>
            </div>
            <div id="segmentBlock" class="form-group" style="display: none;">
                <label class="form-label">絞り込み条件 <span class="text-danger">*</span></label>
                <p class="form-text text-muted mb-3">指定した条件をすべて満たす会員に配信します（1つ以上必須）</p>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="segment_channel" class="form-label">登録チャネル</label>
                        <select name="segment_channel" id="segment_channel" class="form-control segment-filter">
                            <option value="">指定しない</option>
                            @foreach(\App\Support\MemberChannel::filterOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('segment_channel') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="segment_birthday_month" class="form-label">誕生月</label>
                        <select name="segment_birthday_month" id="segment_birthday_month" class="form-control segment-filter">
                            <option value="">指定しない</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (int) old('segment_birthday_month') === $m ? 'selected' : '' }}>{{ $m }}月</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="segment_sex" class="form-label">性別</label>
                        <select name="segment_sex" id="segment_sex" class="form-control segment-filter">
                            <option value="">指定しない</option>
                            <option value="1" {{ old('segment_sex') === '1' ? 'selected' : '' }}>男性</option>
                            <option value="2" {{ old('segment_sex') === '2' ? 'selected' : '' }}>女性</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="segment_visit_count_min" class="form-label">来店回数（以上）</label>
                        <input type="number" name="segment_visit_count_min" id="segment_visit_count_min" class="form-control segment-filter" value="{{ old('segment_visit_count_min') }}" min="0" placeholder="例: 3">
                        <p class="form-text text-muted mb-0">注文件数で判定</p>
                    </div>
                    <div class="col-md-4">
                        <label for="segment_visit_count_max" class="form-label">来店回数（以下）</label>
                        <input type="number" name="segment_visit_count_max" id="segment_visit_count_max" class="form-control segment-filter" value="{{ old('segment_visit_count_max') }}" min="0" placeholder="例: 10">
                    </div>
                    <div class="col-md-4">
                        <label for="segment_rank" class="form-label">会員ランク</label>
                        <input type="text" name="segment_rank" id="segment_rank" class="form-control segment-filter" value="{{ old('segment_rank') }}" maxlength="64" placeholder="例: gold">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="segment_product_id" class="form-label">購入した商品</label>
                        <select name="segment_product_id" id="segment_product_id" class="form-control segment-filter">
                            <option value="">指定しない</option>
                            @foreach(($view->products ?? []) as $product)
                                <option value="{{ $product->id }}" {{ (string) old('segment_product_id') === (string) $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-text text-muted mb-0">アプリ注文・スマレジPOSで購入した会員に絞り込み</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="segment_lifetime_points_min" class="form-label">累計付与ポイント（以上）</label>
                        <input type="number" name="segment_lifetime_points_min" id="segment_lifetime_points_min" class="form-control segment-filter" value="{{ old('segment_lifetime_points_min') }}" min="0" placeholder="例: 1000">
                        <p class="form-text text-muted mb-0">これまでに付与されたポイント合計</p>
                    </div>
                    <div class="col-md-4">
                        <label for="segment_lifetime_points_max" class="form-label">累計付与ポイント（以下）</label>
                        <input type="number" name="segment_lifetime_points_max" id="segment_lifetime_points_max" class="form-control segment-filter" value="{{ old('segment_lifetime_points_max') }}" min="0" placeholder="例: 5000">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="segment_last_visit_mode" class="form-label">最終来店</label>
                        <select name="segment_last_visit_mode" id="segment_last_visit_mode" class="form-control segment-filter">
                            <option value="">指定しない</option>
                            <option value="inactive_days" {{ old('segment_last_visit_mode') === 'inactive_days' ? 'selected' : '' }}>N日以上来店なし</option>
                            <option value="visited_within_days" {{ old('segment_last_visit_mode') === 'visited_within_days' ? 'selected' : '' }}>N日以内に来店あり</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="segment_last_visit_days" class="form-label">最終来店の日数</label>
                        <select name="segment_last_visit_days" id="segment_last_visit_days" class="form-control segment-filter">
                            <option value="7" {{ old('segment_last_visit_days', 30) == 7 ? 'selected' : '' }}>7日</option>
                            <option value="30" {{ old('segment_last_visit_days', 30) == 30 ? 'selected' : '' }}>30日</option>
                            <option value="60" {{ old('segment_last_visit_days') == 60 ? 'selected' : '' }}>60日</option>
                            <option value="90" {{ old('segment_last_visit_days') == 90 ? 'selected' : '' }}>90日</option>
                            <option value="180" {{ old('segment_last_visit_days') == 180 ? 'selected' : '' }}>180日</option>
                        </select>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label">売上条件（任意）</label>
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label for="segment_period_days" class="form-label">対象期間</label>
                            <select name="segment_period_days" id="segment_period_days" class="form-control segment-filter">
                                <option value="7" {{ old('segment_period_days', 30) == 7 ? 'selected' : '' }}>直近7日間</option>
                                <option value="30" {{ old('segment_period_days', 30) == 30 ? 'selected' : '' }}>直近30日間</option>
                                <option value="90" {{ old('segment_period_days') == 90 ? 'selected' : '' }}>直近90日間</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="radio-inline mr-3">
                            <input type="radio" name="segment_condition" value="" {{ old('segment_condition', '') === '' ? 'checked' : '' }} class="segment-filter">
                            <span>指定しない</span>
                        </label>
                        <label class="radio-inline mr-3">
                            <input type="radio" name="segment_condition" value="min_amount" {{ old('segment_condition') === 'min_amount' ? 'checked' : '' }} class="segment-filter" data-segment="min_amount">
                            <span>購入額以上</span>
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="segment_condition" value="no_orders" {{ old('segment_condition') === 'no_orders' ? 'checked' : '' }} class="segment-filter" data-segment="no_orders">
                            <span>未購入</span>
                        </label>
                    </div>
                    <div id="segmentMinAmountBlock" class="row" style="display: none;">
                        <div class="col-md-4">
                            <label for="segment_min_amount" class="form-label">最低購入額（円）</label>
                            <input type="number" name="segment_min_amount" id="segment_min_amount" class="form-control segment-filter" value="{{ old('segment_min_amount', 5000) }}" min="0" step="1" placeholder="例: 5000">
                        </div>
                    </div>
                </div>
                <p id="segmentPreview" class="form-text text-muted mt-2"></p>
            </div>
            <div id="personalTargetBlock" class="form-group" style="display: none;">
                <label class="form-label">配信対象の会員</label>
                <p class="form-text text-muted mb-2">会員番号または名前で検索し、対象を選択してください</p>
                <input type="text" id="memberSearch" class="form-control mb-2" placeholder="検索..." autocomplete="off">
                <div id="memberSearchResults" class="border rounded p-2 mb-2" style="max-height: 200px; overflow-y: auto; display: none;"></div>
                <div id="selectedMembers" class="selected-members"></div>
                <div id="targetMemberIdsHidden"></div>
            </div>
            <div id="scheduledBlock" class="form-group" style="display: none;">
                <label for="scheduled_at" class="form-label">配信日時</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
            </div>
            <div class="form-group">
                <label for="delivery_coupon_id" class="form-label">配信と同時にクーポンを配布（任意）</label>
                <select name="delivery_coupon_id" id="delivery_coupon_id" class="form-control">
                    <option value="">配布しない</option>
                    @foreach($view->coupons ?? [] as $coupon)
                        <option value="{{ $coupon->id }}" {{ old('delivery_coupon_id') == $coupon->id ? 'selected' : '' }}>{{ $coupon->name }}</option>
                    @endforeach
                </select>
                <p class="form-text text-muted mb-0">お知らせの配信対象と同じ会員に、選択したクーポンを1枚付与します</p>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="hidden" name="is_pinned" value="0">
                    <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                    <span>お知らせを固定表示する</span>
                </label>
            </div>
            <div class="form-group">
                <label for="expires_at" class="form-label">掲載終了日</label>
                <input type="date" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at') }}">
            </div>
        </div>
    </div>
    <div class="form-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> 配信する</button>
        <a href="{{ admin_route('admin.announcements') }}" class="btn btn-secondary">一覧に戻る</a>
    </div>
</form>

@push('styles')
<style>
.delivery-type-options .radio-label { display: block; margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #dee2e6; border-radius: 6px; cursor: pointer; }
.delivery-type-options .radio-label:hover { background: #f8f9fa; }
.delivery-type-options input[type=radio] { margin-right: 0.5rem; }
.selected-members .tag { display: inline-block; margin: 2px; padding: 4px 8px; background: #e9ecef; border-radius: 4px; font-size: 0.875rem; }
.selected-members .tag button { margin-left: 4px; cursor: pointer; border: none; background: none; color: #666; }
</style>
@endpush
@push('scripts')
<script>
(function() {
    const membersUrl = '{{ admin_route("admin.announcements.ajax.members") }}';
    let selectedMembers = [];

    function toggleBlocks() {
        const type = document.querySelector('input[name="delivery_type"]:checked')?.value;
        document.getElementById('personalTargetBlock').style.display = type === 'personal' ? 'block' : 'none';
        document.getElementById('scheduledBlock').style.display = type === 'scheduled' ? 'block' : 'none';
        const segmentBlock = document.getElementById('segmentBlock');
        segmentBlock.style.display = type === 'segment' ? 'block' : 'none';
        toggleSegmentCondition();
    }
    function toggleSegmentCondition() {
        const cond = document.querySelector('input[name="segment_condition"]:checked')?.value;
        document.getElementById('segmentMinAmountBlock').style.display = cond === 'min_amount' ? 'block' : 'none';
    }
    document.querySelectorAll('input[name="delivery_type"]').forEach(function(radio) {
        radio.addEventListener('change', toggleBlocks);
    });
    document.querySelectorAll('input[name="segment_condition"]').forEach(function(radio) {
        radio.addEventListener('change', function() { toggleSegmentCondition(); updateSegmentPreview(); });
    });
    toggleBlocks();

    var segmentPreviewTimeout;
    function updateSegmentPreview() {
        if (document.querySelector('input[name="delivery_type"]:checked')?.value !== 'segment') return;
        clearTimeout(segmentPreviewTimeout);
        segmentPreviewTimeout = setTimeout(function() {
            var params = new URLSearchParams();
            ['segment_channel', 'segment_birthday_month', 'segment_visit_count_min', 'segment_visit_count_max',
             'segment_lifetime_points_min', 'segment_lifetime_points_max',
             'segment_last_visit_mode', 'segment_last_visit_days', 'segment_sex', 'segment_rank',
             'segment_product_id',
             'segment_period_days', 'segment_min_amount'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el && el.value !== '') params.set(id, el.value);
            });
            var condition = document.querySelector('input[name="segment_condition"]:checked')?.value || '';
            if (condition !== '') params.set('segment_condition', condition);
            var url = '{{ admin_route("admin.announcements.segment.preview") }}?' + params.toString();
            fetch(url).then(function(r) { return r.json(); }).then(function(data) {
                var el = document.getElementById('segmentPreview');
                el.textContent = '対象会員: ' + (data.count || 0) + '人';
            }).catch(function() {
                document.getElementById('segmentPreview').textContent = '';
            });
        }, 400);
    }
    document.querySelectorAll('.segment-filter').forEach(function(el) {
        el.addEventListener('change', updateSegmentPreview);
        el.addEventListener('input', updateSegmentPreview);
    });
    if (document.querySelector('input[name="delivery_type"]:checked')?.value === 'segment') updateSegmentPreview();

    const searchInput = document.getElementById('memberSearch');
    const resultsDiv = document.getElementById('memberSearchResults');
    const selectedDiv = document.getElementById('selectedMembers');
    const hiddenInput = document.getElementById('targetMemberIds');

    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 1) { resultsDiv.style.display = 'none'; return; }
        searchTimeout = setTimeout(function() {
            fetch(membersUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(function(data) {
                    resultsDiv.innerHTML = '';
                    if (!data.data || data.data.length === 0) {
                        resultsDiv.innerHTML = '<p class="text-muted small mb-0">該当する会員がいません</p>';
                    } else {
                        data.data.forEach(function(m) {
                            if (selectedMembers.indexOf(m.id) >= 0) return;
                            const el = document.createElement('div');
                            el.className = 'py-1 px-2 cursor-pointer border-bottom';
                            el.style.cursor = 'pointer';
                            el.textContent = (m.member_number || '') + ' - ' + (m.name || '—');
                            el.addEventListener('click', function() {
                                selectedMembers.push(m.id);
                                renderSelected();
                                resultsDiv.style.display = 'none';
                                searchInput.value = '';
                            });
                            resultsDiv.appendChild(el);
                        });
                    }
                    resultsDiv.style.display = 'block';
                });
        }, 300);
    });

    function renderSelected() {
        selectedDiv.innerHTML = '';
        const tagContainer = document.createElement('span');
        selectedMembers.forEach(function(id, i) {
            const span = document.createElement('span');
            span.className = 'tag';
            span.innerHTML = 'ID:' + id + ' <button type="button" data-index="' + i + '">&times;</button>';
            span.querySelector('button').addEventListener('click', function() {
                selectedMembers.splice(parseInt(this.getAttribute('data-index'), 10), 1);
                renderSelected();
            });
            tagContainer.appendChild(span);
        });
        selectedDiv.appendChild(tagContainer);
        const hiddenContainer = document.getElementById('targetMemberIdsHidden');
        hiddenContainer.innerHTML = '';
        selectedMembers.forEach(function(id) {
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'target_member_ids[]';
            h.value = id;
            hiddenContainer.appendChild(h);
        });
    }
    document.getElementById('deliveryForm').addEventListener('submit', function() {
        const deliveryType = document.querySelector('input[name="delivery_type"]:checked').value;
        if (deliveryType === 'personal' && selectedMembers.length === 0) {
            alert('個人宛配信の場合は配信対象の会員を1人以上選択してください');
            return false;
        }
        if (deliveryType === 'segment') {
            const cond = document.querySelector('input[name="segment_condition"]:checked')?.value;
            if (cond === 'min_amount' && (!document.getElementById('segment_min_amount').value || parseInt(document.getElementById('segment_min_amount').value, 10) < 0)) {
                alert('セグメント配信で「購入額以上」を選んだ場合は、最低購入額を入力してください');
                return false;
            }
            const hasChannel = document.getElementById('segment_channel').value !== '';
            const hasBirthday = document.getElementById('segment_birthday_month').value !== '';
            const hasVisit = document.getElementById('segment_visit_count_min').value !== '' || document.getElementById('segment_visit_count_max').value !== '';
            const hasLifetimePoints = document.getElementById('segment_lifetime_points_min').value !== '' || document.getElementById('segment_lifetime_points_max').value !== '';
            const hasLastVisit = document.getElementById('segment_last_visit_mode').value !== '';
            const hasSex = document.getElementById('segment_sex').value !== '';
            const hasRank = document.getElementById('segment_rank').value.trim() !== '';
            const hasProduct = document.getElementById('segment_product_id').value !== '';
            const hasSales = cond === 'min_amount' || cond === 'no_orders';
            if (!hasChannel && !hasBirthday && !hasVisit && !hasLifetimePoints && !hasLastVisit && !hasSex && !hasRank && !hasProduct && !hasSales) {
                alert('セグメント配信では1つ以上の絞り込み条件を指定してください');
                return false;
            }
        }
    });
})();
</script>
@endpush
@endsection
