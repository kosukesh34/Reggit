@extends('admin.layout')

@section('title', 'ホーム設定')
@section('page-title', 'ホーム設定')

@section('content')
<nav class="breadcrumb" aria-label="パンくず">
    <a href="{{ admin_route('admin.dashboard') }}">ダッシュボード</a>
    <span class="breadcrumb-sep">/</span>
    <span>ホーム設定</span>
</nav>

<div class="page-header">
    <h1 class="page-header-title"><i class="fas fa-home"></i> ホーム設定</h1>
    <p class="page-header-desc">LINE画面（LIFF）で表示するタブを選択し、並び順を設定できます。</p>
</div>

@if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
@endif

<form action="{{ admin_route('admin.settings.home.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-th-large"></i> 表示するタブ</h2>
        </div>
        <div class="card-body">
            <p class="form-text mb-3">表示するタブにチェックを入れてください。並び順の番号が小さいほど左側に表示されます。</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width:80px">表示</th>
                            <th>タブ</th>
                            <th style="width:100px">並び順</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tabs as $index => $tab)
                        <tr>
                            <td>
                                <input type="hidden" name="tabs[{{ $index }}][tab]" value="{{ $tab['tab'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][label]" value="{{ $tab['label'] }}">
                                <input type="hidden" name="tabs[{{ $index }}][icon]" value="{{ $tab['icon'] }}">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="tabs[{{ $index }}][enabled]" value="1" {{ $tab['enabled'] ? 'checked' : '' }}>
                                    <span>表示する</span>
                                </label>
                            </td>
                            <td>
                                <i class="fas {{ $tab['icon'] ?? 'fa-circle' }}" style="margin-right:8px;color:#6b7280"></i>
                                {{ $tab['label'] }}
                                <span class="text-muted">({{ $tab['tab'] }})</span>
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" name="tabs[{{ $index }}][order]" value="{{ $tab['order'] }}" min="0" style="width:70px">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> 保存する</button>
                <a href="{{ admin_route('admin.settings.basic') }}" class="btn btn-secondary">基本設定に戻る</a>
            </div>
        </div>
    </div>
</form>
@endsection
