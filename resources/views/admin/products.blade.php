@extends('admin.layout')

@section('title', '商品管理')
@section('page-title', '商品管理')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">商品一覧</h3>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">新規追加</a>
    </div>
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
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr>
                <td>{{ $product->id }}</td>
                <td>
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                </td>
                <td>{{ $product->name }}</td>
                <td>¥{{ number_format($product->price) }}</td>
                <td>{{ $product->category }}</td>
                <td>{{ $product->stock }}</td>
                <td>
                    <span class="badge badge-{{ $product->is_available ? 'success' : 'danger' }}">
                        {{ $product->is_available ? '販売中' : '販売停止' }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('admin.products.edit', $product->id) }}" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">編集</a>
                    <form action="{{ route('admin.products.delete', $product->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('本当に削除しますか？')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;">削除</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px;">商品がありません</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    <div class="pagination">
        {{ $products->links() }}
    </div>
</div>
@endsection


