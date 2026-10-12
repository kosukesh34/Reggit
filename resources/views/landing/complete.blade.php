@extends('layouts.lp')

@section('title', $view->title)

@section('content')
    <section class="lp-container lp-status lp-ready" aria-labelledby="ready-title">
        <p class="lp-eyebrow">導入登録</p>
        <ol class="lp-progress">
            <li>1. メール認証</li>
            <li>2. 店舗情報</li>
            <li aria-current="step">3. 発行</li>
        </ol>
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
            <a class="lp-button lp-button--primary" href="{{ $view->loginUrl }}">管理画面へ</a>
            <a class="lp-button lp-button--outline" href="{{ $view->liffUrl }}">店舗アプリを開く</a>
        </div>
        <div class="lp-ready__next">
            <h2>次にすること</h2>
            <ol>
                <li>管理画面で店舗名・営業時間・使う機能を確認する</li>
                <li>店舗アプリを開き、会員証や注文画面を確認する</li>
                <li>必要ならスマレジや LINE 公式アカウントを接続する</li>
            </ol>
        </div>
    </section>
@endsection
