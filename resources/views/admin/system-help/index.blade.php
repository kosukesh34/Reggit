@extends('admin.layout')

@section('title', 'ヘルプ記事')
@section('page-title', 'ヘルプ記事')

@section('content')
<div class="page-header">
    <h1 class="page-header-title">ヘルプ記事</h1>
    <a href="{{ route('system-admin.help-articles.create') }}" class="btn btn-primary">追加</a>
</div>
<p class="form-text">Markdownで書いた記事は、各店舗のヘルプ一覧に出ます。使い方の固定記事と同じスラッグは使えません。</p>
<div class="card">
    <div class="card-body">
        @if($articles->isEmpty())
        <p>追加したヘルプはまだありません。</p>
        @else
        <table class="table">
            <thead>
                <tr><th>タイトル</th><th>区分</th><th>公開</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($articles as $article)
                <tr>
                    <td>{{ $article->title }}</td>
                    <td>{{ $article->category === 'faq' ? 'よくある質問' : '使い方' }}</td>
                    <td>{{ $article->is_published ? '公開' : '非公開' }}</td>
                    <td><a href="{{ route('system-admin.help-articles.edit', ['id' => $article->id]) }}">編集</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
