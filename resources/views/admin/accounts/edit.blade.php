@extends('admin.layout')

@section('title', '管理者アカウント編集')
@section('page-title', '管理者アカウント編集')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ route("{$routePrefix}.accounts.index", $routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}">アカウント管理</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> 管理者アカウント編集</h3>
    </div>
    <div class="card-body">
        <form action="{{ route("{$routePrefix}.accounts.update", array_merge(
            ['id' => $adminUser->id],
            $routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []
        )) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label for="login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
                <input type="text" name="login_id" id="login_id" class="form-control" value="{{ old('login_id', $adminUser->login_id) }}" required pattern="[a-zA-Z0-9_-]+" maxlength="100" placeholder="半角英数字・ハイフン・アンダースコア">
                @error('login_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
                <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $adminUser->email) }}" required autocomplete="email">
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
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $adminUser->name) }}" required>
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="role" class="form-label">権限 <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-control" required>
                    @foreach($creatableRoles as $roleKey)
                        <option value="{{ $roleKey }}" {{ old('role', $adminUser->role) === $roleKey ? 'selected' : '' }}>{{ \App\Models\AdminUser::getRoleLabel($roleKey) }}</option>
                    @endforeach
                    {{-- 現在のロールが作成可能リストにない場合でも表示（変更不可） --}}
                    @if(!in_array($adminUser->role, $creatableRoles))
                        <option value="{{ $adminUser->role }}" selected>{{ \App\Models\AdminUser::getRoleLabel($adminUser->role) }}（変更不可）</option>
                    @endif
                </select>
                <p class="form-text text-muted">権限により操作できる範囲が異なります。</p>
                @error('role')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="project_ids_group">
                <label class="form-label">担当プロジェクト <span class="text-danger">*</span></label>
                <p class="form-text text-muted">複数プロジェクトを選択すると、ログイン後にプロジェクトを切り替えて管理できます。</p>
                @php $selectedIds = old('project_ids', $adminUser->projects->isNotEmpty() ? $adminUser->projects->pluck('id')->all() : ($adminUser->project_id ? [$adminUser->project_id] : [])); @endphp
                <div class="project-ids-checkboxes">
                    @foreach($projects as $p)
                        <label class="checkbox-label">
                            <input type="checkbox" name="project_ids[]" value="{{ $p->id }}" {{ in_array($p->id, $selectedIds, true) ? 'checked' : '' }}>
                            <span>{{ $p->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('project_ids')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group" id="permissions_group" style="display:none;">
                <label class="form-label">スタッフ権限</label>
                <p class="form-text text-muted">スタッフがアクセスできる機能を選択してください。</p>
                @foreach($permissionGroups as $groupName => $groupPerms)
                    <div class="permission-group" style="margin-bottom:12px;">
                        <strong>{{ $groupName }}</strong>
                        <div style="padding-left:16px; margin-top:4px;">
                            @foreach($groupPerms as $perm)
                                <label class="checkbox-label" style="display:block; margin-bottom:4px;">
                                    <input type="checkbox" name="permissions[]" value="{{ $perm }}" {{ in_array($perm, old('permissions', $currentPermissions ?? []), true) ? 'checked' : '' }}>
                                    <span>{{ $allPermissions[$perm] ?? $perm }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('permissions')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">更新</button>
                <a href="{{ route("{$routePrefix}.accounts.index", $routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : []) }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
<script>
document.getElementById('role').addEventListener('change', function() {
    var projectGroup = document.getElementById('project_ids_group');
    var permGroup = document.getElementById('permissions_group');
    projectGroup.style.display = (this.value !== 'system_admin') ? 'block' : 'none';
    permGroup.style.display = (this.value === 'staff') ? 'block' : 'none';
});
document.getElementById('role').dispatchEvent(new Event('change'));
</script>
@endsection
