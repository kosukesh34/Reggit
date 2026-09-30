@extends('admin.layout')

@section('title', 'プロジェクト管理者アカウント作成')
@section('page-title', 'プロジェクト管理者アカウント作成')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route("{$view->routePrefix}.accounts.index", $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}">アカウント管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>新規作成</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> 新規プロジェクト管理者アカウント</h3>
    </div>
    <div class="card-body">
        <form action="{{ route("{$view->routePrefix}.accounts.store", $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
                <input type="text" name="login_id" id="login_id" class="form-control" value="{{ old('login_id') }}" required pattern="[a-zA-Z0-9_-]+" maxlength="100" placeholder="半角英数字・ハイフン・アンダースコア">
                @error('login_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード <span class="text-danger">*</span></label>
                <input type="password" name="password" id="password" class="form-control" required autocomplete="new-password">
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">パスワード（確認）</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="role" class="form-label">権限 <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-control" required>
                    @foreach($view->creatableRoles as $roleKey)
                        <option value="{{ $roleKey }}" {{ old('role') === $roleKey ? 'selected' : '' }}>{{ \App\Models\AdminUser::getRoleLabel($roleKey) }}</option>
                    @endforeach
                </select>
                <p class="form-text text-muted">権限により操作できる範囲が異なります。</p>
                @error('role')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="project_ids_group">
                <label class="form-label">担当プロジェクト <span class="text-danger">*</span></label>
                <p class="form-text text-muted">複数プロジェクトを選択すると、ログイン後にプロジェクトを切り替えて管理できます。</p>
                <div class="project-ids-checkboxes">
                    @foreach($view->projects as $p)
                        <label class="checkbox-label">
                            <input type="checkbox" name="project_ids[]" value="{{ $p->id }}" {{ in_array($p->id, old('project_ids', []), true) ? 'checked' : '' }}>
                            <span>{{ $p->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('project_ids')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">作成</button>
                <a href="{{ route("{$view->routePrefix}.accounts.index", $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>

@endsection
