@extends('admin.layout')
@section('title', '会員タグ管理')
@section('page-title', '会員タグ管理')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.members') }}">会員管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>タグ管理</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title">会員タグ管理</h1>
    <p class="page-header-desc">会員に付けるタグの作成と削除を行います</p>
</div>

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> 新規タグ作成</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.member-tags.store') }}" method="POST" class="form-inline form-inline-flex">
            @csrf
            <div class="form-group">
                <label class="form-label">タグ名 <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="50" placeholder="例: VIP" value="{{ old('name') }}">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">色</label>
                <div class="color-input-row">
                    <input type="color" name="color" class="color-picker" value="{{ old('color', '#0d9488') }}">
                </div>
                @error('color')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions form-actions-zero">
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> 作成</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags"></i> タグ一覧</h3>
        <span class="card-header-note">{{ $tags->count() }}件</span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>タグ名</th>
                <th>色</th>
                <th>付与会員数</th>
                <th class="table-actions-cell">操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tags as $tag)
            <tr>
                <td>
                    <span class="member-tag-badge" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                </td>
                <td><code>{{ $tag->color }}</code></td>
                <td>{{ number_format($tag->members_count ?? 0) }}人</td>
                <td class="table-actions-cell">
                    <form action="{{ admin_route('admin.member-tags.destroy', ['id' => $tag->id]) }}" method="POST"
                          onsubmit="return confirmDialog('タグ「{{ $tag->name }}」を削除しますか？付与済みの会員からも解除されます。')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-action btn-delete"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>タグがありません</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
