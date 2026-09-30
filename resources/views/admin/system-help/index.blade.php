@extends('admin.layout')

@section('title', 'ヘルプ記事')
@section('page-title', 'ヘルプ記事')

@section('content')
<div class="publishing-header">
    <p class="form-text">店舗管理者向けの操作ガイドやよくある質問を作成します。<br>公開した記事は各店舗のヘルプに表示されます。</p>
    <a href="{{ route('system-admin.help-articles.create') }}" class="btn btn-primary"><i class="fas fa-plus" aria-hidden="true"></i> ヘルプ記事を書く</a>
</div>
<div class="card">
    <div class="card-body">
        @if($articles->isEmpty())
        <div class="publishing-empty"><i class="fas fa-book-open" aria-hidden="true"></i><h2>最初のヘルプ記事を書きましょう</h2><p>操作の手順やよくある質問を、店舗管理者に共有できます。</p><a class="btn btn-primary" href="{{ route('system-admin.help-articles.create') }}">ヘルプ記事を書く</a></div>
        @else
        <table class="table">
            <thead>
                <tr><th>タイトル</th><th>区分</th><th>公開</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($articles as $article)
                <tr>
                    <td><strong>{{ $article->title }}</strong><p class="form-text">{{ $article->summary }}</p></td>
                    <td>{{ $article->category === 'faq' ? 'よくある質問' : '使い方' }}</td>
                    <td><span class="publishing-badge {{ $article->is_published ? 'is-live' : '' }}">{{ $article->is_published ? '公開中' : '下書き' }}</span></td>
                    <td><a href="{{ route('system-admin.help-articles.edit', ['id' => $article->id]) }}">編集</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/14-publishing.css') }}">
@endpush
