@extends('admin.layout')

@section('title', '既存会員連携')
@section('page-title', '既存会員連携')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.settings.index') }}">設定</a>
    <span class="breadcrumb-sep">/</span>
    <span>既存会員連携</span>
</nav>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card card-mb-24">
    <div class="card-header">
        <h3 class="card-title">連携の条件</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.settings.existing-member-link.update') }}" method="POST">
            @csrf
            <input type="hidden" name="is_enabled" value="0">
            <label class="checkbox-label checkbox-block">
                <input type="checkbox" name="is_enabled" value="1" {{ $view->isEnabled ? 'checked' : '' }} {{ $view->canManageSettings ? '' : 'disabled' }}>
                <span>既存会員連携を使う</span>
            </label>
            <div class="form-group">
                <label class="form-label" for="existing-link-description">お客様への説明</label>
                <textarea id="existing-link-description" name="description" class="form-control" maxlength="300" rows="3" {{ $view->canManageSettings ? '' : 'disabled' }}>{{ $view->description }}</textarea>
            </div>
            <p class="form-text text-muted">選んだ項目がすべて一致し、まだこのアプリに結びついていない会員だけ連携できます。メールアドレスを選ぶと、確認メールのリンクを開くまで連携しません。</p>
            @foreach($view->fields as $field)
            <label class="checkbox-label checkbox-block">
                <input type="checkbox" name="identifier_fields[]" value="{{ $field['key'] }}" {{ $field['isChecked'] ? 'checked' : '' }} {{ $view->canManageSettings ? '' : 'disabled' }}>
                <span>{{ $field['label'] }}</span>
            </label>
            @endforeach
            @if($view->canManageSettings)
            <button type="submit" class="btn btn-primary">設定を保存</button>
            @endif
        </form>
    </div>
</div>

@if($view->canLink)
<div class="card">
    <div class="card-header">
        <h3 class="card-title">店舗で連携する</h3>
    </div>
    <div class="card-body">
        <form action="{{ admin_route('admin.settings.existing-member-link.store') }}" method="POST">
            @csrf
            @foreach($view->fields as $field)
                @if($field['isChecked'])
                <div class="form-group">
                    <label class="form-label" for="link-{{ $field['key'] }}">{{ $field['label'] }}</label>
                    <input id="link-{{ $field['key'] }}" class="form-control" name="{{ $field['key'] }}" value="{{ old($field['key']) }}" type="{{ $field['key'] === 'birthday' ? 'date' : 'text' }}" required>
                </div>
                @endif
            @endforeach
            <button type="submit" class="btn btn-primary" {{ $view->isEnabled ? '' : 'disabled' }}>連携する</button>
        </form>
    </div>
</div>
@endif
@endsection
