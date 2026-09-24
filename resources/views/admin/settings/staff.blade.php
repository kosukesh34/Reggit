@extends('admin.layout')

@section('title', 'スタッフ・権限')
@section('page-title', 'スタッフ・権限')

@section('content')
<div class="card card-mb-24">
    <div class="card-header"><h2 class="card-title">スタッフを追加</h2></div>
    <div class="card-body">
        <form action="{{ admin_route('admin.settings.staff.store') }}" method="POST">
            @csrf
            <div class="settings-staff-fields">
                <div class="form-group"><label class="form-label" for="staff-name">名前</label><input class="form-control" id="staff-name" name="name" value="{{ old('name') }}" required></div>
                <div class="form-group"><label class="form-label" for="staff-login">ログインID</label><input class="form-control" id="staff-login" name="login_id" value="{{ old('login_id') }}" required autocomplete="off"></div>
                <div class="form-group"><label class="form-label" for="staff-email">メールアドレス</label><input class="form-control" type="email" id="staff-email" name="email" value="{{ old('email') }}" required></div>
                <div class="form-group"><label class="form-label" for="staff-password">パスワード</label><input class="form-control" type="password" id="staff-password" name="password" minlength="8" required autocomplete="new-password"></div>
                <div class="form-group"><label class="form-label" for="staff-password-confirmation">パスワード確認</label><input class="form-control" type="password" id="staff-password-confirmation" name="password_confirmation" minlength="8" required autocomplete="new-password"></div>
            </div>
            <h3 class="settings-staff-permissions-title">許可する操作</h3>
            <div class="settings-staff-permissions">
                @foreach($permissionGroups as $group => $keys)
                    <fieldset class="settings-staff-permission-group">
                        <legend>{{ $group }}</legend>
                        @foreach($keys as $key)
                            <label class="checkbox-label"><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ in_array($key, old('permissions', []), true) ? 'checked' : '' }}> {{ $permissionLabels[$key] }}</label>
                        @endforeach
                    </fieldset>
                @endforeach
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">スタッフを追加</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">登録済みスタッフ</h2></div>
    <div class="card-body">
        @forelse($staff as $member)
            <details class="settings-staff-member">
                <summary><strong>{{ $member->name }}</strong><span>{{ $member->login_id }}</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                <form action="{{ admin_route('admin.settings.staff.update', ['id' => $member->id]) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="settings-staff-fields">
                        <div class="form-group"><label class="form-label" for="staff-name-{{ $member->id }}">名前</label><input class="form-control" id="staff-name-{{ $member->id }}" name="name" value="{{ $member->name }}" required></div>
                        <div class="form-group"><label class="form-label" for="staff-email-{{ $member->id }}">メールアドレス</label><input class="form-control" type="email" id="staff-email-{{ $member->id }}" name="email" value="{{ $member->email }}" required></div>
                        <div class="form-group"><label class="form-label" for="staff-password-{{ $member->id }}">新しいパスワード（変更時のみ）</label><input class="form-control" type="password" id="staff-password-{{ $member->id }}" name="password" minlength="8" autocomplete="new-password"></div>
                        <div class="form-group"><label class="form-label" for="staff-password-confirmation-{{ $member->id }}">新しいパスワード確認</label><input class="form-control" type="password" id="staff-password-confirmation-{{ $member->id }}" name="password_confirmation" minlength="8" autocomplete="new-password"></div>
                    </div>
                    <h3 class="settings-staff-permissions-title">許可する操作</h3>
                    <div class="settings-staff-permissions">
                        @foreach($permissionGroups as $group => $keys)
                            <fieldset class="settings-staff-permission-group">
                                <legend>{{ $group }}</legend>
                                @foreach($keys as $key)
                                    <label class="checkbox-label"><input type="checkbox" name="permissions[]" value="{{ $key }}" {{ $member->permissions->contains('permission', $key) ? 'checked' : '' }}> {{ $permissionLabels[$key] }}</label>
                                @endforeach
                            </fieldset>
                        @endforeach
                    </div>
                    <div class="form-actions"><button class="btn btn-primary" type="submit">変更を保存</button></div>
                </form>
            </details>
        @empty
            <p class="text-muted">スタッフはまだ登録されていません。</p>
        @endforelse
    </div>
</div>
@endsection
