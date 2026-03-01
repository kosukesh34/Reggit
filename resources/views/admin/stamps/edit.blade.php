@extends('admin.layout')
@section('title', 'スタンプカード編集')
@section('page-title', 'スタンプカード編集')

@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>編集</span></nav>
@php($layoutOptions = \App\Models\StampCard::getLayoutOptions())
@php($milestones = old('reward_milestones', $stampCard->reward_milestones ?? [['stamp_count' => '', 'description' => '']]))
@if(!is_array($milestones) || $milestones === [])
    @php($milestones = [['stamp_count' => '', 'description' => '']])
@endif

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp"></i> スタンプカード編集</h3></div>
    <div class="card-body">
        <form action="{{ admin_route('admin.stamps.update', ['id' => $stampCard->id]) }}" method="POST" data-stamp-edit-form>
            @csrf
            @method('PUT')
            <input type="hidden" name="valid_days" value="">

            <div class="form-group">
                <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $stampCard->name) }}" required maxlength="255">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="required_stamps" class="form-label">必要スタンプ数（個数） <span class="text-danger">*</span></label>
                <input type="number" name="required_stamps" id="required_stamps" class="form-control" value="{{ old('required_stamps', $stampCard->required_stamps) }}" min="1" max="999" required>
                @error('required_stamps')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="layout_style" class="form-label">表示パターン（UI）</label>
                <select name="layout_style" id="layout_style" class="form-control">
                    @foreach($layoutOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('layout_style', $stampCard->layout_style ?? 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="valid_until" class="form-label">有効期限（日付）</label>
                <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until', $stampCard->valid_until?->format('Y-m-d')) }}">
                <p class="form-text text-muted">未設定の場合は無期限</p>
                @error('valid_until')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">達成特典（複数）</label>
                <div id="editMilestonesContainer" class="stamp-milestones">
                    @foreach($milestones as $index => $milestone)
                    <div class="stamp-milestone-row" data-edit-milestone-row>
                        <div class="stamp-milestone-count-wrap">
                            <label class="form-label" for="edit_reward_milestones_{{ $index }}_stamp_count">スタンプ数</label>
                            <input type="number" min="1" max="999" class="form-control edit-milestone-count-input" id="edit_reward_milestones_{{ $index }}_stamp_count" name="reward_milestones[{{ $index }}][stamp_count]" value="{{ is_array($milestone) ? ($milestone['stamp_count'] ?? '') : '' }}" placeholder="10">
                        </div>
                        <div class="stamp-milestone-reward-wrap">
                            <label class="form-label" for="edit_reward_milestones_{{ $index }}_description">特典内容</label>
                            <input type="text" maxlength="500" class="form-control edit-milestone-description-input" id="edit_reward_milestones_{{ $index }}_description" name="reward_milestones[{{ $index }}][description]" value="{{ is_array($milestone) ? ($milestone['description'] ?? '') : '' }}" placeholder="例）ドリンク1杯無料">
                        </div>
                        <div class="stamp-milestone-action-wrap">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-edit-remove-milestone aria-label="特典を削除"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="editAddMilestoneButton"><i class="fas fa-plus"></i> 特典を追加</button>
                @error('reward_milestones')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                @error('reward_milestones.*.stamp_count')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                @error('reward_milestones.*.description')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="reward_description" class="form-label">補足テキスト</label>
                <input type="text" name="reward_description" id="reward_description" class="form-control" value="{{ old('reward_description', $stampCard->reward_description) }}" maxlength="500">
                @error('reward_description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label for="image_url" class="form-label">画像URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url', $stampCard->image_url) }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>

            <div class="form-group form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', $stampCard->is_active)) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label">有効</label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ admin_route('admin.stamps') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>

        <form action="{{ admin_route('admin.stamps.delete', ['id' => $stampCard->id]) }}" method="POST" class="mt-3" onsubmit="return confirm('このスタンプカードを削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">削除</button>
        </form>
    </div>
</div>

<template id="editMilestoneRowTemplate">
    <div class="stamp-milestone-row" data-edit-milestone-row>
        <div class="stamp-milestone-count-wrap">
            <label class="form-label" data-edit-label-count>スタンプ数</label>
            <input type="number" min="1" max="999" class="form-control edit-milestone-count-input" placeholder="10">
        </div>
        <div class="stamp-milestone-reward-wrap">
            <label class="form-label" data-edit-label-description>特典内容</label>
            <input type="text" maxlength="500" class="form-control edit-milestone-description-input" placeholder="例）ドリンク1杯無料">
        </div>
        <div class="stamp-milestone-action-wrap">
            <button type="button" class="btn btn-sm btn-outline-danger" data-edit-remove-milestone aria-label="特典を削除"><i class="fas fa-trash"></i></button>
        </div>
    </div>
</template>

<script>
(() => {
    const form = document.querySelector('[data-stamp-edit-form]');
    const container = document.getElementById('editMilestonesContainer');
    const addButton = document.getElementById('editAddMilestoneButton');
    const template = document.getElementById('editMilestoneRowTemplate');
    if (!form || !container || !addButton || !template) return;

    const rows = () => Array.from(container.querySelectorAll('[data-edit-milestone-row]'));

    const reindex = () => {
        rows().forEach((row, index) => {
            const countInput = row.querySelector('.edit-milestone-count-input');
            const descriptionInput = row.querySelector('.edit-milestone-description-input');
            const countLabel = row.querySelector('[data-edit-label-count]') || row.querySelector('.stamp-milestone-count-wrap .form-label');
            const descriptionLabel = row.querySelector('[data-edit-label-description]') || row.querySelector('.stamp-milestone-reward-wrap .form-label');
            if (!countInput || !descriptionInput) return;

            const countId = 'edit_reward_milestones_' + index + '_stamp_count';
            const descriptionId = 'edit_reward_milestones_' + index + '_description';

            countInput.name = 'reward_milestones[' + index + '][stamp_count]';
            countInput.id = countId;
            descriptionInput.name = 'reward_milestones[' + index + '][description]';
            descriptionInput.id = descriptionId;

            if (countLabel) countLabel.setAttribute('for', countId);
            if (descriptionLabel) descriptionLabel.setAttribute('for', descriptionId);
        });
    };

    addButton.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        container.appendChild(fragment);
        reindex();
    });

    container.addEventListener('click', (event) => {
        const button = event.target.closest('[data-edit-remove-milestone]');
        if (!button) return;
        const row = button.closest('[data-edit-milestone-row]');
        if (!row) return;

        if (rows().length <= 1) {
            const countInput = row.querySelector('.edit-milestone-count-input');
            const descriptionInput = row.querySelector('.edit-milestone-description-input');
            if (countInput) countInput.value = '';
            if (descriptionInput) descriptionInput.value = '';
            return;
        }
        row.remove();
        reindex();
    });

    reindex();
})();
</script>
@endsection

@push('styles')
<style>
.btn-outline-primary {
    background: #ffffff;
    color: #0f766e;
    border: 1px solid #99f6e4;
}

.btn-outline-primary:hover {
    background: #f0fdfa;
    border-color: #5eead4;
}

.btn-outline-danger {
    background: #ffffff;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.btn-outline-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
}

.stamp-milestones {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stamp-milestone-row {
    display: grid;
    grid-template-columns: 140px minmax(0, 1fr) auto;
    gap: 10px;
    padding: 10px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--bg-tertiary);
}

.stamp-milestone-action-wrap {
    display: flex;
    align-items: flex-end;
    padding-bottom: 1px;
}

.mt-3 {
    margin-top: 12px;
}

@media (max-width: 780px) {
    .stamp-milestone-row {
        grid-template-columns: 1fr;
    }

    .stamp-milestone-action-wrap {
        justify-content: flex-end;
        padding-bottom: 0;
    }
}
</style>
@endpush
