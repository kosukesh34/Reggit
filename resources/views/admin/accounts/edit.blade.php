@extends('admin.layout')

@section('title', 'プロジェクト管理者アカウント編集')
@section('page-title', 'プロジェクト管理者アカウント編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route($view->routePrefix.'.accounts.index', $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}">アカウント管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> プロジェクト管理者アカウント編集</h3>
    </div>
    <div class="card-body">
        <form action="{{ route("{$view->routePrefix}.accounts.update", array_merge(
            ['id' => $view->adminUser->id],
            $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []
        )) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
                <input type="text" name="login_id" id="login_id" class="form-control" value="{{ old('login_id', $view->adminUser->login_id) }}" required pattern="[a-zA-Z0-9_-]+" maxlength="100" placeholder="半角英数字・ハイフン・アンダースコア">
                @error('login_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $view->adminUser->email) }}" required autocomplete="email">
                @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="password" class="form-label">パスワード（変更する場合のみ入力）</label>
                <input type="password" name="password" id="password" class="form-control" autocomplete="new-password">
                @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation" class="form-label">パスワード（確認）</label>
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $view->adminUser->name) }}" required>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="role" class="form-label">権限 <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-control" required>
                    @foreach($view->creatableRoles as $roleKey)
                        <option value="{{ $roleKey }}" {{ old('role', $view->adminUser->role) === $roleKey ? 'selected' : '' }}>{{ \App\Models\AdminUser::getRoleLabel($roleKey) }}</option>
                    @endforeach
                    {{-- 現在のロールが作成可能リストにない場合でも表示（変更不可） --}}
                    @if(in_array($view->adminUser->role, $view->creatableRoles, true) === false)
                        <option value="{{ $view->adminUser->role }}" selected>{{ \App\Models\AdminUser::getRoleLabel($view->adminUser->role) }}（変更不可）</option>
                    @endif
                </select>
                <p class="form-text text-muted">権限により操作できる範囲が異なります。</p>
                @error('role')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="project_ids_group">
                <label class="form-label">担当プロジェクト <span class="text-danger">*</span></label>
                <p class="form-text text-muted">複数プロジェクトを選択すると、ログイン後にプロジェクトを切り替えて管理できます。</p>
                @php $selectedIds = old('project_ids', $view->adminUser->projects->isNotEmpty() ? $view->adminUser->projects->pluck('id')->all() : ($view->adminUser->project_id ? [$view->adminUser->project_id] : [])); @endphp
                <div class="project-ids-checkboxes">
                    @foreach($view->projects as $p)
                        <label class="checkbox-label">
                            <input type="checkbox" name="project_ids[]" value="{{ $p->id }}" {{ in_array($p->id, $selectedIds, true) ? 'checked' : '' }}>
                            <span>{{ $p->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('project_ids')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">更新</button>
                <x-back-link :href="route($view->routePrefix.'.accounts.index', $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : [])" label="キャンセル" />
            </div>
        </form>
    </div>
</div>

@endsection
