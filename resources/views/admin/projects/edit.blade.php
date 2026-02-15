@extends('admin.layout')

@section('title', 'プロジェクト編集')
@section('page-title', 'プロジェクト編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route('system-admin.projects.index') }}">プロジェクト管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-building"></i> プロジェクト編集</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('system-admin.projects.update', $project->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="project_key" class="form-label">プロジェクトキー <span class="text-danger">*</span></label>
                <input type="text" name="project_key" id="project_key" class="form-control" value="{{ old('project_key', $project->project_key) }}" required maxlength="64" pattern="[a-zA-Z0-9_-]+">
                @error('project_key')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="name" class="form-label">プロジェクト名 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $project->name) }}" required maxlength="255">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="description" class="form-label">説明</label>
                <input type="text" name="description" id="description" class="form-control" value="{{ old('description', $project->description) }}" maxlength="255">
                @error('description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="plan" class="form-label">プラン <span class="text-danger">*</span></label>
                <select name="plan" id="plan" class="form-control" required>
                    @foreach(\App\Enums\ProjectPlan::getValues() as $value => $label)
                        <option value="{{ $value }}" {{ old('plan', $project->plan ?? 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('plan')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $project->is_active) ? 'checked' : '' }}>
                    <span>有効化する</span>
                </label>
                @error('is_active')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route('system-admin.projects.index') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
