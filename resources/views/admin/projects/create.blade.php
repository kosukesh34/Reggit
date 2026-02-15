@extends('admin.layout')

@section('title', 'プロジェクト作成')
@section('page-title', 'プロジェクト作成')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.projects.index') }}">プロジェクト管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規作成</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> 新規プロジェクト</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('system-admin.projects.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="project_key" class="form-label">プロジェクトキー <span class="text-danger">*</span></label>
                <input type="text" name="project_key" id="project_key" class="form-control" value="{{ old('project_key') }}" required maxlength="64" pattern="[a-zA-Z0-9_-]+">
                <p class="form-text text-muted">URLやAPIで利用する識別子です（英数字・ハイフン・アンダースコア）。</p>
                @error('project_key')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="name" class="form-label">プロジェクト名 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required maxlength="255">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="description" class="form-label">説明</label>
                <input type="text" name="description" id="description" class="form-control" value="{{ old('description') }}" maxlength="255">
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="plan" class="form-label">プラン <span class="text-danger">*</span></label>
                <select name="plan" id="plan" class="form-control" required>
                    @foreach(\App\Enums\ProjectPlan::getValues() as $value => $label)
                        <option value="{{ $value }}" {{ old('plan', 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('plan')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    <span>有効化する</span>
                </label>
                @error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">作成</button>
                <a href="{{ route('system-admin.projects.index') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
