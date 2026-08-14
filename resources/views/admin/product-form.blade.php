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
            <a href="{{ admin_route('admin.products') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> キャンセル
            </a>
        </div>
    </form>
</div>
@endsection

