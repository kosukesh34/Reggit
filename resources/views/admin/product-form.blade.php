@extends('admin.layout')

@section('title', isset($product) ? '商品編集' : '商品追加')
@section('page-title', isset($product) ? '商品編集' : '商品追加')

@section('content')
<div class="card">
        <form action="{{ isset($product) ? admin_route('admin.products.update', ['id' => $product->id]) : admin_route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($product))
            @method('PUT')
        @endif

        <div class="form-group">
            <label class="form-label">商品名</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
        </div>

        @php
            $optionGroups = old('option_groups');
            if ($optionGroups === null && isset($product)) {
                $optionGroups = $product->optionGroups->filter(fn ($group) => $group->smaregi_option_group_id === null)->map(fn ($group) => [
                    'name' => $group->name,
                    'selection_type' => $group->selection_type,
                    'is_required' => $group->is_required,
                    'options' => $group->options->map(fn ($option) => [
                        'name' => $option->name,
                        'price_delta' => (float) $option->price_delta,
                    ])->all(),
                ])->all();
            }
            $optionGroups = is_array($optionGroups) ? $optionGroups : [];
        @endphp
        <div class="form-group">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div>
                    <label class="form-label"><i class="fas fa-sliders-h"></i> 商品オプション</label>
                    <p class="form-help">例：はちみつの量「普通 / 多め（+50円）」、サイズ、トッピングなど</p>
                </div>
                <button type="button" class="btn btn-secondary" id="add-option-group"><i class="fas fa-plus"></i> グループ追加</button>
            </div>
            <div id="option-groups" style="display:grid;gap:14px;margin-top:14px">
                @foreach($optionGroups as $groupIndex => $group)
                    <div class="card option-group-row" data-group-index="{{ $groupIndex }}" style="padding:16px;margin:0">
                        <div style="display:grid;grid-template-columns:minmax(180px,1fr) 160px auto auto;gap:10px;align-items:end">
                            <div><label class="form-label">グループ名</label><input class="form-control" name="option_groups[{{ $groupIndex }}][name]" value="{{ $group['name'] ?? '' }}" required></div>
                            <div><label class="form-label">選択方法</label><select class="form-control" name="option_groups[{{ $groupIndex }}][selection_type]"><option value="single" @selected(($group['selection_type'] ?? 'single') === 'single')>1つ選択</option><option value="multiple" @selected(($group['selection_type'] ?? '') === 'multiple')>複数選択</option></select></div>
                            <label class="checkbox-label" style="min-height:42px"><input type="checkbox" name="option_groups[{{ $groupIndex }}][is_required]" value="1" @checked($group['is_required'] ?? false)> 必須</label>
                            <button type="button" class="btn btn-outline-danger remove-option-group">削除</button>
                        </div>
                        <div class="option-rows" style="display:grid;gap:8px;margin-top:12px">
                            @foreach(($group['options'] ?? []) as $optionIndex => $option)
                                <div class="option-row" style="display:grid;grid-template-columns:minmax(180px,1fr) 150px auto;gap:10px">
                                    <input class="form-control" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][name]" value="{{ $option['name'] ?? '' }}" placeholder="例：はちみつ多め" required>
                                    <input type="number" class="form-control" name="option_groups[{{ $groupIndex }}][options][{{ $optionIndex }}][price_delta]" value="{{ $option['price_delta'] ?? 0 }}" step="1" placeholder="追加・割引">
                                    <button type="button" class="btn btn-outline-danger remove-option">×</button>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary add-option" style="margin-top:10px">選択肢を追加</button>
                    </div>
                @endforeach
            </div>
            @if(isset($product))
                @foreach($product->optionGroups->filter(fn ($group) => $group->smaregi_option_group_id !== null) as $smaregiGroup)
                    <div class="alert alert-info" style="margin-top:12px"><strong>{{ $smaregiGroup->name }}</strong>：スマレジから同期（{{ $smaregiGroup->options->pluck('name')->join(' / ') }}）</div>
                @endforeach
            @endif
        </div>

        <div class="form-group">
            <label class="form-label">説明</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
        </div>

        <div class="form-group">
            <label class="form-label">価格</label>
            <input type="number" name="price" class="form-control" value="{{ old('price', $product->price ?? '') }}" min="0" step="1" required>
        </div>

        <div class="form-group">
            <label class="form-label"><i class="fas fa-tags"></i> カテゴリ</label>
            <select name="category" class="form-select" required>
                <option value="food" {{ old('category', $product->category ?? '') === 'food' ? 'selected' : '' }}>フード</option>
                <option value="drink" {{ old('category', $product->category ?? '') === 'drink' ? 'selected' : '' }}>ドリンク</option>
                <option value="dessert" {{ old('category', $product->category ?? '') === 'dessert' ? 'selected' : '' }}>デザート</option>
                <option value="side" {{ old('category', $product->category ?? '') === 'side' ? 'selected' : '' }}>サイドメニュー</option>
                <option value="other" {{ old('category', $product->category ?? '') === 'other' ? 'selected' : '' }}>その他</option>
            </select>
        </div>

        @include('admin.partials._frontend_logo_dropzone', [
            'logoInputName' => 'image',
            'logoClearName' => 'image_clear',
            'logoFieldId' => 'product_image',
            'logoCurrentUrl' => (string) old('image_url', $product->image_url ?? ''),
            'logoLabel' => '商品画像',
        ])

        <div class="form-group">
            <div class="form-check mb-2">
                @php
                    $stockUnlimited = old('stock_unlimited', isset($product) ? $product->stock === null : false);
                @endphp
                <input type="checkbox" name="stock_unlimited" id="stock_unlimited" value="1" {{ $stockUnlimited ? 'checked' : '' }}>
                <label for="stock_unlimited">在庫無制限にする</label>
            </div>
            <label class="form-label" for="stock">在庫数</label>
            <input type="number" name="stock" id="stock" class="form-control" value="{{ $stockUnlimited ? '' : old('stock', $product->stock ?? 0) }}" min="0" {{ $stockUnlimited ? '' : 'required' }} placeholder="{{ $stockUnlimited ? '無制限' : '' }}">
            <small class="form-help">在庫無制限にした場合は在庫チェック・減算を行いません</small>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_available" id="is_available" value="1" {{ ((bool) old('is_available', $product->is_available ?? true)) ? 'checked' : '' }}>
                <label for="is_available">販売中</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> 保存
            </button>
            <x-back-link :href="admin_route('admin.products')" label="キャンセル" />
        </div>
    </form>
</div>

<template id="option-group-template">
    <div class="card option-group-row" style="padding:16px;margin:0">
        <div style="display:grid;grid-template-columns:minmax(180px,1fr) 160px auto auto;gap:10px;align-items:end">
            <div><label class="form-label">グループ名</label><input class="form-control group-name" required placeholder="例：はちみつの量"></div>
            <div><label class="form-label">選択方法</label><select class="form-control group-type"><option value="single">1つ選択</option><option value="multiple">複数選択</option></select></div>
            <label class="checkbox-label" style="min-height:42px"><input type="checkbox" class="group-required" value="1"> 必須</label>
            <button type="button" class="btn btn-outline-danger remove-option-group">削除</button>
        </div>
        <div class="option-rows" style="display:grid;gap:8px;margin-top:12px"></div>
        <button type="button" class="btn btn-sm btn-secondary add-option" style="margin-top:10px">選択肢を追加</button>
    </div>
</template>
<script>
(function () {
    const container = document.getElementById('option-groups');
    const template = document.getElementById('option-group-template');
    if (!container || !template) return;
    function reindex() {
        container.querySelectorAll('.option-group-row').forEach((group, gi) => {
            group.querySelector('.group-name, input[name*="[name]"]').name = `option_groups[${gi}][name]`;
            group.querySelector('.group-type, select[name*="[selection_type]"]').name = `option_groups[${gi}][selection_type]`;
            group.querySelector('.group-required, input[name*="[is_required]"]').name = `option_groups[${gi}][is_required]`;
            group.querySelectorAll('.option-row').forEach((row, oi) => {
                row.querySelector('input:nth-child(1)').name = `option_groups[${gi}][options][${oi}][name]`;
                row.querySelector('input:nth-child(2)').name = `option_groups[${gi}][options][${oi}][price_delta]`;
            });
        });
    }
    function addOption(group, name = '', price = '0') {
        const row = document.createElement('div');
        row.className = 'option-row';
        row.style.cssText = 'display:grid;grid-template-columns:minmax(180px,1fr) 150px auto;gap:10px';
        row.innerHTML = `<input class="form-control" value="${name}" placeholder="例：はちみつ多め" required><input type="number" class="form-control" value="${price}" step="1" placeholder="追加・割引"><button type="button" class="btn btn-outline-danger remove-option">×</button>`;
        group.querySelector('.option-rows').appendChild(row);
        reindex();
    }
    document.getElementById('add-option-group').addEventListener('click', () => {
        const group = template.content.firstElementChild.cloneNode(true);
        container.appendChild(group);
        addOption(group);
    });
    container.addEventListener('click', (event) => {
        const group = event.target.closest('.option-group-row');
        if (event.target.closest('.remove-option-group')) { group.remove(); reindex(); }
        else if (event.target.closest('.add-option')) addOption(group);
        else if (event.target.closest('.remove-option')) { event.target.closest('.option-row').remove(); reindex(); }
    });
    reindex();
})();
</script>
@endsection
