@extends('admin.layout')
@section('title', '会員編集')
@section('page-title', '会員編集 #' . $member->id)

@section('content')
<nav class="breadcrumb">
    <a href="{{ admin_route('admin.members') }}">会員一覧</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}">会員 #{{ $member->id }}</a>
    <span class="breadcrumb-sep">/</span>
    <span>編集</span>
</nav>

@if($errors->any())
<div class="alert alert-danger" role="alert">
    <i class="fas fa-exclamation-circle"></i> 入力内容に誤りがあります。ご確認ください。
    <ul class="mb-0 mt-2">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <div class="card-header card-header-with-action">
        <h3 class="card-title"><i class="fas fa-user-edit"></i> 会員編集</h3>
        <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> 詳細に戻る</a>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.members.update', ['id' => $member->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <section class="form-section mb-4">
                <h4 class="form-section-title"><span class="form-section-num">1</span> ユーザー情報（登録情報）</h4>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="name" class="form-label">名前</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $member->user->name ?? '') }}" maxlength="255">
                        @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="kana" class="form-label">名前（カナ）</label>
                        <input type="text" name="kana" id="kana" class="form-control" value="{{ old('kana', $member->user->kana ?? '') }}" maxlength="255">
                        @error('kana')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="email" class="form-label">メールアドレス</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $member->user->email ?? '') }}" maxlength="255">
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="phone" class="form-label">電話番号</label>
                        <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $member->user->phone ?? '') }}" maxlength="32">
                        @error('phone')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title"><span class="form-section-num">2</span> 会員情報</h4>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="member_number" class="form-label">会員番号 <span class="text-danger">*</span></label>
                        <input type="text" name="member_number" id="member_number" class="form-control" value="{{ old('member_number', $member->member_number) }}" maxlength="64" required>
                        @error('member_number')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="status" class="form-label">ステータス <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>有効</option>
                            <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>無効</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="rank" class="form-label">ランク</label>
                        <select name="rank" id="rank" class="form-control">
                            <option value="">— 未設定 —</option>
                            @foreach($rankOptions ?? [] as $key => $label)
                            <option value="{{ $key }}" {{ old('rank', $member->rank) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="birthday" class="form-label">生年月日</label>
                    <input type="date" name="birthday" id="birthday" class="form-control" value="{{ old('birthday', $member->birthday ? $member->birthday->format('Y-m-d') : '') }}">
                    @error('birthday')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="postal_code" class="form-label">郵便番号</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" value="{{ old('postal_code', $member->postal_code ?? '') }}" maxlength="16" placeholder="例: 100-0001">
                    @error('postal_code')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="prefecture" class="form-label">都道府県</label>
                    <input type="text" name="prefecture" id="prefecture" class="form-control" value="{{ old('prefecture', $member->prefecture ?? '') }}" maxlength="64">
                    @error('prefecture')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="city" class="form-label">市区町村</label>
                    <input type="text" name="city" id="city" class="form-control" value="{{ old('city', $member->city ?? '') }}" maxlength="128">
                    @error('city')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="street" class="form-label">番地・建物名</label>
                    <input type="text" name="street" id="street" class="form-control" value="{{ old('street', $member->street ?? '') }}" maxlength="256">
                    @error('street')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="address" class="form-label">住所（まとめ）</label>
                    <textarea name="address" id="address" class="form-control" rows="2" maxlength="500">{{ old('address', $member->address ?? '') }}</textarea>
                    @error('address')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label for="sex" class="form-label">性別</label>
                    <select name="sex" id="sex" class="form-control">
                        <option value="">— 未設定 —</option>
                        <option value="1" {{ old('sex', $member->sex) === '1' ? 'selected' : '' }}>男性</option>
                        <option value="2" {{ old('sex', $member->sex) === '2' ? 'selected' : '' }}>女性</option>
                    </select>
                </div>
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title"><span class="form-section-num">3</span> ポイント操作（任意）</h4>
                <p class="text-muted small mb-3">「ポイントを直接指定」に数値を入れると、その値に合わせて調整されます（例: 500）。加算・減算と併用した場合は直接指定が優先されます。保存時にポイント履歴へ記録され、Apple Wallet / Google Wallet も更新されます。</p>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label for="point_set" class="form-label">ポイントを直接指定</label>
                        <input type="number" name="point_set" id="point_set" class="form-control" value="{{ old('point_set') }}" min="0" step="1" placeholder="例: 500">
                        @error('point_set')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_add" class="form-label">ポイント加算</label>
                        <input type="number" name="point_add" id="point_add" class="form-control" value="{{ old('point_add', 0) }}" min="0" step="1" placeholder="0">
                        @error('point_add')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_subtract" class="form-label">ポイント減算</label>
                        <input type="number" name="point_subtract" id="point_subtract" class="form-control" value="{{ old('point_subtract', 0) }}" min="0" step="1" placeholder="0">
                        @error('point_subtract')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label for="point_description" class="form-label">理由（管理用メモ）</label>
                        <input type="text" name="point_description" id="point_description" class="form-control" value="{{ old('point_description') }}" maxlength="255" placeholder="例: 管理画面での調整">
                    </div>
                </div>
                <p class="text-muted small mb-0">現在のポイント: <strong>{{ number_format($member->points) }}pt</strong></p>
            </section>

            <section class="form-section mb-4">
                <h4 class="form-section-title"><span class="form-section-num">4</span> 任意項目（JSON）</h4>
                <p class="text-muted small mb-2">プロフィールの追加項目を JSON で編集できます。例: <code>{"company_name":"株式会社 Example"}</code></p>
                <div class="form-group">
                    <label for="profile_extra" class="form-label">profile_extra</label>
                    <textarea name="profile_extra" id="profile_extra" class="form-control font-monospace" rows="4" maxlength="2000" placeholder='{"key": "value"}'>{{ old('profile_extra', $member->profile_extra ? json_encode($member->profile_extra, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '') }}</textarea>
                    @error('profile_extra')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>変更を保存</button>
                <a href="{{ admin_route('admin.members.detail', ['id' => $member->id]) }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
