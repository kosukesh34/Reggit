@extends('admin.layout')
@section('title', 'スタンプカード追加')
@section('page-title', 'スタンプカード追加')
@section('content')
<nav class="breadcrumb"><a href="{{ admin_route('admin.stamps') }}">スタンプ</a><span class="breadcrumb-sep">/</span><span>新規</span></nav>
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-stamp"></i> 新規スタンプカード</h3></div>
    <div class="card-body">
        <form action="{{ admin_route('admin.stamps.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="name" class="form-label">名前 <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required maxlength="255">
                @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="required_stamps" class="form-label">必要スタンプ数（個数） <span class="text-danger">*</span></label>
                <input type="number" name="required_stamps" id="required_stamps" class="form-control" value="{{ old('required_stamps', 10) }}" min="1" max="999" required>
                <p class="form-text text-muted">スタンプを何個集めたら達成にするか（1〜999）。</p>
                @error('required_stamps')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="layout_style" class="form-label">表示パターン（UI）</label>
                <select name="layout_style" id="layout_style" class="form-control">
                    @foreach(\App\Models\StampCard::getLayoutOptions() as $value => $label)
                        <option value="{{ $value }}" {{ old('layout_style', 'grid') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="form-text text-muted">アプリでのスタンプカードの見た目を選択します。</p>
                @error('layout_style')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="valid_days" class="form-label">有効期限（日数）</label>
                <input type="number" name="valid_days" id="valid_days" class="form-control" value="{{ old('valid_days') }}" min="1" max="3650" placeholder="未設定">
                <p class="form-text text-muted">初回スタンプから何日以内に達成するか（未入力＝無制限）。</p>
                @error('valid_days')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="valid_until" class="form-label">キャンペーン終了日</label>
                <input type="date" name="valid_until" id="valid_until" class="form-control" value="{{ old('valid_until') }}">
                <p class="form-text text-muted">この日以降は新規でスタンプを貯められません（未設定＝制限なし）。</p>
                @error('valid_until')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="reward_description" class="form-label">特典内容</label>
                <input type="text" name="reward_description" id="reward_description" class="form-control" value="{{ old('reward_description') }}" maxlength="500">
                @error('reward_description')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label for="image_url" class="form-label">画像URL</label>
                <input type="url" name="image_url" id="image_url" class="form-control" value="{{ old('image_url') }}" maxlength="500" placeholder="https://example.com/stamp-card.png">
                <p class="form-text text-muted">未入力の場合はデフォルト画像が使用されます。</p>
                @error('image_url')<span class="invalid-feedback">{{ $message }}</span>@enderror
            </div>
            <div class="form-group form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ ((bool) old('is_active', true)) ? 'checked' : '' }}>
                <label for="is_active" class="form-check-label">有効</label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">追加</button>
                <a href="{{ admin_route('admin.stamps') }}" class="btn btn-secondary">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
