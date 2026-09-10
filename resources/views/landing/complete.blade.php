@extends('layouts.lp')

@section('title', $view->title)

@section('content')
    <section class="lp-container lp-status lp-ready" aria-labelledby="ready-title">
        <h1 id="ready-title">{{ $view->title }}</h1>
        <p>{{ $view->body }}</p>
        <dl class="lp-ready__list">
            <div>
                <dt>店舗名</dt>
                <dd>{{ $view->storeName }}</dd>
            </div>
            <div>
                <dt>店舗アプリ（LIFF）</dt>
                <dd><a href="{{ $view->liffUrl }}">{{ $view->liffUrl }}</a></dd>
            </div>
            <div>
                <dt>管理画面</dt>
                <dd><a href="{{ $view->loginUrl }}">{{ $view->loginUrl }}</a></dd>
            </div>
            <div>
                <dt>ログインID</dt>
                <dd>{{ $view->loginId }}</dd>
            </div>
            <div>
                <dt>パスワード</dt>
                <dd>{{ $view->password !== null ? $view->password : '登録時のメールをご確認ください' }}</dd>
            </div>
        </dl>
        <div class="lp-ready__actions">
            <a class="lp-button lp-button--primary" href="{{ $view->liffUrl }}">店舗アプリを開く</a>
            <a class="lp-button lp-button--outline" href="{{ $view->loginUrl }}">管理画面へ</a>
        </div>
    </section>
@endsection
