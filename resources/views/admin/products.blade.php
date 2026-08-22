@extends('admin.layout')

@section('title', '商品管理')
@section('page-title', '商品管理')

@section('content')
<div class="page-header-row">
    <div class="page-header">
        <h1 class="page-header-title"><i class="fas fa-utensils"></i> 商品管理</h1>
    </div>
    <div class="page-header-actions">
        <a href="{{ admin_route('admin.products.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> 新規追加
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 商品一覧</h3>
        <span class="card-header-note">{{ $products->total() }}件</span>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>画像</th>
                    <th>商品名</th>
                    <th>価格</th>
                    <th>カテゴリ</th>
                    <th>在庫</th>
                    <th>ステータス</th>
                    <th class="table-actions-cell">操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td><span class="id-badge">{{ $product->id }}</span></td>
                    <td>
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="table-thumb">
                    </td>
                    <td><strong>{{ $product->name }}</strong></td>
                    <td>¥{{ number_format($product->price) }}</td>
                    <td>{{ $product->category }}</td>
                    <td>{{ $product->stock === null ? '無制限' : $product->stock }}</td>
                    <td>
                        <span class="badge badge-{{ $product->is_available ? 'success' : 'danger' }}">
                            {{ $product->is_available ? '販売中' : '販売停止' }}
                        </span>
                    </td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <a href="{{ admin_route('admin.products.edit', ['id' => $product->id]) }}" class="btn btn-action btn-edit"><i class="fas fa-edit"></i> 編集</a>
                            <form action="{{ admin_route('admin.products.delete', ['id' => $product->id]) }}" method="POST" class="d-inline delete-form" data-item-name="{{ $product->name }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>商品がありません</p>
                        <p class="empty-state-hint">「新規追加」ボタンから商品を登録してください</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links('vendor.pagination.admin') }}
</div>
@endsection
