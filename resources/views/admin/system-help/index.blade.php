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
                <tr><th>画面イメージ</th><th>タイトル・内容</th><th>区分</th><th>公開</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($articles as $article)
                @php
                    preg_match_all('~!\[[^\]]*\]\((/images/help/[a-zA-Z0-9_-]+\.png)\)~', $article->body, $screens);
                    $screenCount = count($screens[1]);
                    preg_match_all('/^## [0-9]+\. /m', $article->body, $steps);
                @endphp
                <tr>
                    <td class="help-admin-cover">
                        <a href="{{ route('system-admin.help-articles.show-preview', ['id' => $article->id]) }}" aria-label="{{ $article->title }}のプレビュー">
                            @if($screenCount > 0)
                            <img src="{{ $screens[1][0] }}" alt="{{ $article->title }}の画面" loading="lazy">
                            @else
                            <span class="form-text">画像なし</span>
                            @endif
                        </a>
                    </td>
                    <td><strong><a href="{{ route('system-admin.help-articles.show-preview', ['id' => $article->id]) }}">{{ $article->title }}</a></strong><p class="form-text">{{ $article->summary }}</p><span class="help-admin-count">{{ count($steps[0]) }}手順 ・ {{ $screenCount }}枚の画面画像</span></td>
                    <td>{{ $article->category === 'faq' ? 'よくある質問' : '使い方' }}</td>
                    <td><span class="publishing-badge {{ $article->is_published ? 'is-live' : '' }}">{{ $article->is_published ? '公開中' : '下書き' }}</span></td>
                    <td><a class="btn btn-secondary btn-sm" href="{{ route('system-admin.help-articles.show-preview', ['id' => $article->id]) }}">プレビュー</a> <a class="btn btn-secondary btn-sm" href="{{ route('system-admin.help-articles.edit', ['id' => $article->id]) }}">編集</a></td>
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
<style>
.help-admin-cover { width: 150px; }
.help-admin-cover img { width: 130px; height: 90px; object-fit: contain; background: #f1f5f9; border: 1px solid #dbe3e8; border-radius: 6px; }
.help-admin-count { display: inline-block; padding: 4px 8px; font-size: 12px; color: #475569; background: #f1f5f9; border-radius: 6px; }
</style>
@endpush
