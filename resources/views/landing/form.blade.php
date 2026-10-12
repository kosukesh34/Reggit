@extends('layouts.lp')

@section('title', '導入登録フォーム')

@section('content')
    <section class="lp-container lp-inquiry" aria-labelledby="inquiry-title">
        <p class="lp-eyebrow">導入登録</p>
        <ol class="lp-progress">
            <li>1. メール認証</li>
            <li aria-current="step">2. 店舗情報</li>
            <li>3. 発行</li>
        </ol>
        <h1 id="inquiry-title">入力フォーム</h1>
        <p class="lp-inquiry__lead">認証済みのメールアドレス（{{ $view->email }}）で店舗と管理者アカウントを発行します。送信後すぐに LIFF URL と管理画面のログイン情報をお渡しします。</p>

        @if (session('status'))
            <p class="lp-alert lp-alert--success" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <p class="lp-alert lp-alert--error" role="alert">{{ $errors->first() }}</p>
        @endif

        <form class="lp-inquiry__form" method="POST" action="{{ $view->formAction }}">
            @csrf
            <label class="lp-field">
                <span>店舗名・会社名</span>
                <input type="text" name="store_name" value="{{ old('store_name', $view->storeName) }}" required maxlength="255" autocomplete="organization">
            </label>
            <label class="lp-field">
                <span>ご担当者名</span>
                <input type="text" name="contact_name" value="{{ old('contact_name', $view->contactName) }}" required maxlength="255" autocomplete="name">
            </label>
            <label class="lp-field">
                <span>電話番号</span>
                <input type="tel" name="phone" value="{{ old('phone', $view->phone) }}" required maxlength="50" autocomplete="tel">
            </label>
            <fieldset class="lp-field">
                <legend>希望プラン</legend>
                <div class="lp-plan-choices">
                    @foreach ($view->planOptions as $option)
                        <label>
                            <input type="radio" name="plan" value="{{ $option['value'] }}" required @checked(old('plan', $view->plan) === $option['value'])>
                            <span>
                                <strong>{{ $option['label'] }}</strong>
                                <small>{{ $option['description'] }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <label class="lp-field">
                <span>ご質問・ご要望（任意）</span>
                <textarea name="message" rows="5" maxlength="2000">{{ old('message', $view->message) }}</textarea>
            </label>
            <button type="submit" class="lp-button lp-button--primary">店舗を発行する</button>
        </form>
    </section>
@endsection
