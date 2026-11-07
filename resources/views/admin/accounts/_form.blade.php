@php
    $adminUser = $adminUser ?? null;
    $isEdit = $adminUser !== null;
    $routeParams = $view->routePrefix === 'admin' ? ['project_key' => request()->route('project_key')] : [];
    // 入力エラーで戻った値は文字列なので、ID と厳密比較できるよう整数にそろえる
    $selectedIds = array_map('intval', (array) old(
        'project_ids',
        $isEdit
            ? ($adminUser->projects->isNotEmpty()
                ? $adminUser->projects->pluck('id')->all()
                : ($adminUser->project_id ? [$adminUser->project_id] : []))
            : []
    ));
@endphp
<div class="form-group">
    <label for="login_id" class="form-label">ログインID <span class="text-danger">*</span></label>
    <input type="text" name="login_id" id="login_id" class="form-control" value="{{ old('login_id', $isEdit ? $adminUser->login_id : null) }}" required pattern="[a-zA-Z0-9_-]+" maxlength="100" placeholder="半角英数字・ハイフン・アンダースコア">
    @error('login_id')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="email" class="form-label">メールアドレス <span class="text-danger">*</span></label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $isEdit ? $adminUser->email : null) }}" required autocomplete="email">
    @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="password" class="form-label">{{ $isEdit ? 'パスワード（変更する場合のみ入力）' : 'パスワード' }}@if(! $isEdit) <span class="text-danger">*</span>@endif</label>
    <input type="password" name="password" id="password" class="form-control" @if(! $isEdit) required @endif autocomplete="new-password">
    @error('password')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="password_confirmation" class="form-label">パスワード（確認）</label>
    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
</div>
<div class="form-group">
    <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $isEdit ? $adminUser->name : null) }}" required>
    @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-group">
    <label for="role" class="form-label">権限 <span class="text-danger">*</span></label>
    <select name="role" id="role" class="form-control" required>
        @foreach($view->creatableRoles as $roleKey)
            <option value="{{ $roleKey }}" {{ old('role', $isEdit ? $adminUser->role : null) === $roleKey ? 'selected' : '' }}>{{ \App\Models\AdminUser::getRoleLabel($roleKey) }}</option>
        @endforeach
        @if($isEdit && in_array($adminUser->role, $view->creatableRoles, true) === false)
            <option value="{{ $adminUser->role }}" selected>{{ \App\Models\AdminUser::getRoleLabel($adminUser->role) }}（変更不可）</option>
        @endif
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
                <input type="checkbox" name="project_ids[]" value="{{ $p->id }}" {{ in_array($p->id, $selectedIds, true) ? 'checked' : '' }}>
                <span>{{ $p->name }}</span>
            </label>
        @endforeach
    </div>
    @error('project_ids')<span class="invalid-feedback">{{ $message }}</span>@enderror
</div>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    <x-back-link :href="route($view->routePrefix.'.accounts.index', $routeParams)" label="キャンセル" />
</div>
