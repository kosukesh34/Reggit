@extends('layouts.lp')

@section('title', 'ヘルプ・使い方')

@section('content')
<article class="lp-container public-help help-manual">
    <a class="public-help-back" href="/">Reggit トップへ</a>
    <h1>ヘルプ・使い方</h1>
    <p class="help-lead">会員・注文・予約・特典などの操作を、画面画像と詳しい手順で確認できます。どなたでもログインなしで読めます。</p>
    <form class="help-search" method="GET" action="{{ route('help.index') }}" role="search">
        <label for="help-q">知りたい機能を検索</label>
        <input id="help-q" type="search" name="q" value="{{ $query }}" maxlength="200" placeholder="例：既存会員連携、予約、クーポン">
        <button class="lp-button lp-button--small" type="submit">検索</button>
    </form>
    @foreach(['guide' => '使い方', 'faq' => 'よくある質問'] as $category => $label)
        @php($items = array_filter($articles, fn (array $article): bool => $article['category'] === $category))
        <section aria-labelledby="help-{{ $category }}">
            <h2 id="help-{{ $category }}">{{ $label }}</h2>
            @if($items === [])
                <p class="help-empty">該当する項目はありません。別の機能名で検索してください。</p>
            @else
                <ul class="help-list">
                    @foreach($items as $article)
                        <li><a href="{{ route('help.show', ['slug' => $article['slug']]) }}"><strong>{{ $article['title'] }}</strong><span>{{ $article['summary'] }}</span></a></li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach
</article>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/components/12-help-manual.css') }}">
<link rel="stylesheet" href="{{ asset('css/help-public.css') }}">
@endpush
