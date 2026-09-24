@extends('admin.layout')

@section('title', 'ヘルプ')
@section('page-title', 'ヘルプ')

@section('content')
<article class="help-manual">
    <h1>ヘルプ</h1>
    <p class="help-lead">使い方は実際の画面と赤枠、よくある質問は文章で確認できます。</p>
    <form class="help-search" method="GET" action="{{ admin_route('admin.help') }}">
        <label for="help-q">ヘルプを検索</label>
        <input id="help-q" type="search" name="q" value="{{ $query }}" placeholder="例: 引き継ぎ、失敗、キャンセル" autocomplete="off">
        <button type="submit" class="btn btn-primary">検索</button>
    </form>
    @php
        $groups = ['guide' => '使い方', 'faq' => 'よくある質問'];
    @endphp
    @foreach($groups as $category => $label)
        @php
            $items = array_values(array_filter($articles, fn (array $article): bool => ($article['category'] ?? 'guide') === $category));
        @endphp
        <h2>{{ $label }}</h2>
        @if($items === [])
        <p class="help-empty">該当する項目はありません。</p>
        @else
        <ul class="help-list">
            @foreach($items as $article)
            <li>
                <a href="{{ admin_route('admin.help.show', ['slug' => $article['slug']]) }}">
                    <strong>{{ $article['title'] }}</strong>
                    <span>{{ $article['summary'] }}</span>
                </a>
            </li>
            @endforeach
        </ul>
        @endif
    @endforeach
</article>
@endsection
