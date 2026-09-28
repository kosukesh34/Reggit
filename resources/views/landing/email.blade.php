@extends('layouts.lp')

@section('title', 'メールアドレスの認証')

@section('content')
    <section class="lp-container lp-inquiry" aria-labelledby="email-title">
        <p class="lp-eyebrow">導入登録 · 1 / 2</p>
        <h1 id="email-title">メールアドレスの認証</h1>
        <p>メールアドレスを入力してください。認証メールのリンクからメールアドレスを確認し、店舗情報の入力へ進みます。</p>

        @if (session('status'))
            <p class="lp-alert lp-alert--success" role="status">{{ session('status') }}</p>
            <p>メールが届かない場合は、迷惑メールフォルダと入力したメールアドレスをご確認ください。必要に応じて再送信できます。</p>
        @endif

        <form class="lp-inquiry__form" method="POST" action="{{ route('landing.register.email') }}">
            @csrf
            <label class="lp-field" for="registration-email">
                <span>メールアドレス</span>
                <input id="registration-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="name@company.jp" @if($errors->has('email')) aria-invalid="true" aria-describedby="registration-email-error" @endif>
            </label>
            @error('email')
                <p id="registration-email-error" class="lp-alert lp-alert--error" role="alert">{{ $message }}</p>
            @enderror
            <button class="lp-button lp-button--primary" type="submit">{{ session('status') ? '認証メールを再送する' : '認証メールを送る' }}</button>
            <small>登録後すぐに契約が発生することはありません。</small>
        </form>
    </section>
@endsection
