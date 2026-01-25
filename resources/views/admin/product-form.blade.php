@extends('admin.layout')

@section('title', isset($product) ? '商品編集' : '商品追加')
@section('page-title', isset($product) ? '商品編集' : '商品追加')

@section('content')
<div class="card">
    <form action="{{ isset($product) ? route('admin.products.update', $product->id) : route('admin.products.store') }}" method="POST">
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

        <div class="form-group">
            <label class="form-label"><i class="fas fa-image"></i> 画像URL</label>
            <input type="url" name="image_url" class="form-control" value="{{ old('image_url', $product->image_url ?? '') }}" placeholder="https://...">
            <small class="form-help"><i class="fas fa-info-circle"></i> 画像を追加後、php artisan products:download-images を実行してください</small>
        </div>

        <div class="form-group">
            <label class="form-label">在庫数</label>
            <input type="number" name="stock" class="form-control" value="{{ old('stock', $product->stock ?? 0) }}" min="0" required>
        </div>

        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', $product->is_available ?? true) ? 'checked' : '' }}>
                <label for="is_available">販売中</label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> 保存
            </button>
            <a href="{{ route('admin.products') }}" class="btn btn-secondary">
                <i class="fas fa-times"></i> キャンセル
            </a>
        </div>
    </form>
</div>
@endsection


