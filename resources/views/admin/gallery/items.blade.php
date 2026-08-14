@extends('admin.layout')

@section('title', 'ギャラリー画像')
@section('page-title', 'ギャラリー画像')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.gallery.items') }}">ギャラリー</a>
    <span class="breadcrumb-sep">/</span>
    <span>画像一覧</span>
</nav>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus"></i> 画像追加</h3>
        <a href="{{ admin_route('admin.gallery.categories') }}" class="btn btn-secondary btn-sm"><i class="fas fa-tags"></i> カテゴリ管理</a>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ admin_route('admin.gallery.items.store') }}" class="form-inline form-inline-flex" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">カテゴリ</label>
                <select name="category_id" class="form-control">
                    <option value="">未設定</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">タイトル</label>
                <input type="text" name="title" class="form-control" maxlength="150">
            </div>
            @include('admin.partials._frontend_logo_dropzone', [
                'logoInputName' => 'image',
                'logoClearName' => 'image_clear',
                'logoFieldId' => 'gallery_item_new',
                'logoCurrentUrl' => '',
                'logoLabel' => '画像',
                'logoRequired' => true,
                'logoCompact' => true,
            ])
            <div class="form-group">
                <label class="form-label">説明</label>
                <input type="text" name="description" class="form-control" maxlength="1000">
            </div>
            <div class="form-group">
                <label class="form-label">サイズ</label>
                <select name="size" class="form-control">
                    <option value="small">小</option>
                    <option value="medium" selected>中</option>
                    <option value="large">大</option>
                </select>
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
        <h3 class="card-title"><i class="fas fa-images"></i> 一覧</h3>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>プレビュー</th>
                <th>カテゴリ</th>
                <th>タイトル</th>
                <th>並び順</th>
                <th>表示</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <form method="POST" action="{{ admin_route('admin.gallery.items.update', ['id' => $item->id]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <td>{{ $item->id }}</td>
                    <td>
                        <img src="{{ $item->image_url }}" alt="" class="gallery-thumb">
                    </td>
                    <td>
                        <select name="category_id" class="form-control">
                            <option value="">未設定</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ (int) $item->category_id === (int) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="title" class="form-control" value="{{ $item->title }}" maxlength="150">
                        <input type="text" name="description" class="form-control input-mt-6" value="{{ $item->description }}" maxlength="1000" placeholder="説明">
                        <input type="hidden" name="image_url" value="{{ $item->image_url }}">
                        @include('admin.partials._frontend_logo_dropzone', [
                            'logoInputName' => 'image',
                            'logoClearName' => 'image_clear',
                            'logoFieldId' => 'gallery_item_'.$item->id,
                            'logoCurrentUrl' => (string) $item->image_url,
                            'logoLabel' => '画像を差し替え',
                            'logoCompact' => true,
                        ])
                        <select name="size" class="form-control select-mt-6">
                            <option value="small" {{ $item->size === 'small' ? 'selected' : '' }}>小</option>
                            <option value="medium" {{ $item->size === 'medium' ? 'selected' : '' }}>中</option>
                            <option value="large" {{ $item->size === 'large' ? 'selected' : '' }}>大</option>
                        </select>
                    </td>
                    <td><input type="number" name="sort_order" class="form-control" value="{{ $item->sort_order }}" min="0" max="999"></td>
                    <td class="text-center"><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}></td>
                    <td class="table-actions-cell">
                        <div class="table-actions">
                            <button type="submit" class="btn btn-action btn-edit"><i class="fas fa-save"></i> 更新</button>
                        </div>
                </form>
                        <form method="POST" action="{{ admin_route('admin.gallery.items.delete', ['id' => $item->id]) }}" class="d-inline" onsubmit="return confirm('削除しますか？');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-action btn-danger"><i class="fas fa-trash"></i> 削除</button>
                        </form>
                    </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="empty-state">
                    <i class="fas fa-images"></i>
                    <p>画像がありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
