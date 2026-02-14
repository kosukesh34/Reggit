@extends('admin.layout')

@section('title', 'ギャラリーカテゴリ')
@section('page-title', 'ギャラリーカテゴリ')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.gallery.categories') }}">ギャラリー</a>
    <span class="breadcrumb-sep">/</span>
    <span>カテゴリ</span>
</nav>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags"></i> 新規カテゴリ</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ admin_route('admin.gallery.categories.store') }}" class="form-inline form-inline-flex">
            @csrf
            <div class="form-group">
                <label class="form-label">名前</label>
                <input type="text" name="name" class="form-control" required maxlength="100">
            </div>
            <div class="form-group">
                <label class="form-label">並び順</label>
                <input type="number" name="sort_order" class="form-control" value="0" min="0" max="999">
            </div>
            <div class="form-group form-check pt-30">
                <input type="checkbox" name="is_active" class="form-check-input" value="1" checked>
                <label class="form-check-label">表示</label>
            </div>
            <div class="form-actions form-actions-zero">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> 追加</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list"></i> 一覧</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>名前</th>
                <th>並び順</th>
                <th>表示</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
            <tr>
                <form method="POST" action="{{ admin_route('admin.gallery.categories.update', ['id' => $category->id]) }}">
                    @csrf
                    @method('PUT')
                    <td>{{ $category->id }}</td>
                    <td><input type="text" name="name" class="form-control" value="{{ $category->name }}" required maxlength="100"></td>
                    <td><input type="number" name="sort_order" class="form-control" value="{{ $category->sort_order }}" min="0" max="999"></td>
                    <td class="text-center"><input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }}></td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <button type="submit" class="btn btn-action btn-edit"><i class="fas fa-save"></i> 更新</button>
                        </div>
                </form>
                        <form method="POST" action="{{ admin_route('admin.gallery.categories.delete', ['id' => $category->id]) }}" class="d-inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button>
                        </form>
                    </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>カテゴリがありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
