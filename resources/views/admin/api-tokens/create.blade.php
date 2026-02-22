@extends('admin.layout')

@section('title', 'APIトークン発行')
@section('page-title', 'APIトークン発行')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.api-tokens.index') }}">APIトークン管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規発行</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-key"></i> 新規APIトークン</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('system-admin.api-tokens.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="project_id" class="form-label">プロジェクト <span class="text-danger">*</span></label>
                <select name="project_id" id="project_id" class="form-control" required>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ (int) old('project_id') === (int) $project->id ? 'selected' : '' }}>
                            {{ $project->name }} ({{ $project->project_key }})
                        </option>
                    @endforeach
                </select>
                @error('project_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="name" class="form-label">トークン名 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required maxlength="100" placeholder="例: mobile-app">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="expires_at" class="form-label">有効期限</label>
                <input type="date" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at') }}">
                <p class="form-text text-muted">未指定の場合は無期限です。</p>
                @error('expires_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">発行</button>
                <a href="{{ route('system-admin.api-tokens.index') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
