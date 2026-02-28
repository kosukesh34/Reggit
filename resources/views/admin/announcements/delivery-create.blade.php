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

<form action="{{ admin_route('admin.announcements.delivery.store') }}" method="POST" id="deliveryForm">
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
            <div class="form-group">
                <label class="form-label">配信種別 <span class="text-danger">*</span></label>
                <div class="delivery-type-options">
                    <label class="radio-label">
                        <input type="radio" name="delivery_type" value="immediate_bulk" {{ old('delivery_type', 'immediate_bulk') === 'immediate_bulk' ? 'checked' : '' }} data-target="bulk">
                        <span>一斉配信</span>
                        <small class="d-block text-muted">登録会員全員（{{ number_format($memberCount) }}人）に即時配信</small>
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
                </div>
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
    }
    document.querySelectorAll('input[name="delivery_type"]').forEach(function(radio) {
        radio.addEventListener('change', toggleBlocks);
    });
    toggleBlocks();

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
        if (document.querySelector('input[name="delivery_type"]:checked').value === 'personal' && selectedMembers.length === 0) {
            alert('個人宛配信の場合は配信対象の会員を1人以上選択してください');
            return false;
        }
    });
})();
</script>
@endpush
@endsection
