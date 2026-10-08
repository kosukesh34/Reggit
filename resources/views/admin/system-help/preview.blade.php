@extends('admin.layout')

@section('title', 'ヘルプ記事のプレビュー')
@section('page-title', 'ヘルプ記事のプレビュー')

@section('content')
<div class="publishing-header">
    <a href="{{ route('system-admin.help-articles.index') }}">一覧へ戻る</a>
    <a class="btn btn-primary" href="{{ route('system-admin.help-articles.edit', ['id' => $article->id]) }}">この記事を編集</a>
</div>
<p class="form-text">{{ $article->is_published ? '公開中' : '下書き' }}の記事です。店舗ヘルプと同じ表示で確認できます。</p>
<div class="card"><div class="card-body">
    <article class="help-manual">
        <h1>{{ $article->title }}</h1>
        <p class="help-lead">{{ $article->summary }}</p>
        <div class="help-markdown">{!! $html !!}</div>
    </article>
</div></div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/14-publishing.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/components/12-help-manual.css') }}">
@endpush
