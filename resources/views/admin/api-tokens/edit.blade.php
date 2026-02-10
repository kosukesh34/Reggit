@extends('admin.layout')

@section('title', 'APIトークン編集')
@section('page-title', 'APIトークン編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.api-tokens.index') }}">APIトークン管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-key"></i> APIトークン編集</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('system-admin.api-tokens.update', $token->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">プロジェクト</label>
                <div>{{ $token->project?->name ?? '－' }}</div>
            </div>
            <div class="form-group">
                <label for="name" class="form-label">トークン名 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $token->name) }}" required maxlength="100">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="expires_at" class="form-label">有効期限</label>
                <input type="date" name="expires_at" id="expires_at" class="form-control" value="{{ old('expires_at', $token->expires_at?->format('Y-m-d')) }}">
                @error('expires_at')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $token->is_active) ? 'checked' : '' }}>
                    <span>有効化する</span>
                </label>
                @error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('system-admin.api-tokens.index') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
