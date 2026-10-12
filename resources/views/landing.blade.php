<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="会員証、注文、予約、順番待ちをLINEミニアプリに。Reggitは、お客様のスマートフォンと店舗の管理画面をつなぐサービスです。">
    <title>Reggit｜会員証も、注文も。いつものLINEで。</title>
    <link rel="icon" type="image/png" href="{{ asset('images/reggit-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/landing/app/current-home.jpg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v=9">
    <link rel="stylesheet" href="{{ asset('css/web-ui.css') }}?v=1">
</head>
<body class="lp">
    <a class="lp-skip" href="#main">本文へ移動</a>
    <p class="lp-demo-notice">このアプリはデモです</p>
    <header class="lp-header">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/reggit-logo.png') }}" alt="" width="40" height="40">
                <span>Reggit</span>
            </a>
            <nav class="lp-nav" aria-label="メインナビゲーション">
                <a href="#features">できること</a>
                <a href="#flow">導入の流れ</a>
                <a href="#faq">よくある質問</a>
                <a href="{{ route('help.index') }}">ヘルプ</a>
            </nav>
            <div class="lp-header__actions">
                <a class="lp-login" href="{{ route('admin.login') }}">管理画面ログイン</a>
                <a class="lp-button lp-button--small" href="{{ route('demo.liff') }}">デモを体験する</a>
            </div>
            <details class="lp-mobile-nav">
                <summary>メニュー<span aria-hidden="true"></span></summary>
                <nav aria-label="モバイルナビゲーション">
                    <a href="#features">できること</a>
                    <a href="#flow">導入の流れ</a>
                    <a href="#faq">よくある質問</a>
                    <a href="{{ route('help.index') }}">ヘルプ</a>
                    <a href="{{ route('landing.register.email.show') }}">導入登録</a>
                    <a href="{{ route('admin.login') }}">管理画面ログイン</a>
                </nav>
            </details>
        </div>
    </header>

    <main id="main">
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="lp-container lp-hero__grid">
                <div class="lp-hero__copy">
                    <p class="lp-hero__intro">お店のためのLINEミニアプリ</p>
                    <h1 id="hero-title">会員証も、注文も。<br>いつものLINEで。</h1>
                    <p class="lp-hero__body">会員証、注文、予約、順番待ち。<br>お客様のスマートフォンと、お店の管理画面を<br class="lp-desktop-break">Reggitでつなぎます。</p>
                    <div class="lp-hero__actions">
                        <a class="lp-button" href="{{ route('demo.liff') }}">デモを体験する</a>
                        <a class="lp-button lp-button--outline" href="{{ route('landing.register.email.show') }}">導入登録を始める</a>
                    </div>
                    <p class="lp-hero__note">LINEミニアプリなら、アプリのインストールは不要です。</p>
                </div>
                <div id="screens" class="lp-product" aria-label="Reggitの実際の画面">
                    <figure class="lp-product__customer">
                        <img src="{{ asset('images/landing/app/current-home.jpg') }}" alt="ReggitのLINEミニアプリ。会員証、注文、予約へアクセスできるホーム画面" width="390" height="844" fetchpriority="high">
                        <figcaption>お客様のLINE</figcaption>
                    </figure>
                    <figure class="lp-product__admin">
                        <img src="{{ asset('images/landing/app/current-admin.jpg') }}" alt="Reggitの管理画面。注文、予約、順番待ち、会員情報を確認するダッシュボード" width="1280" height="720" fetchpriority="high">
                        <figcaption>店舗の管理画面</figcaption>
                    </figure>
                    <p class="lp-product__note">画面はデモデータを使用しています。</p>
                </div>
            </div>
        </section>

        <div class="lp-capabilities">
            <ul class="lp-container" aria-label="利用できる主な機能">
                <li>会員証</li><li>モバイルオーダー</li><li>予約</li><li>順番待ち</li><li>ポイント・スタンプ</li><li>クーポン</li>
            </ul>
        </div>

        @php
            $features = [
                [
                    'id' => 'member',
                    'title' => '会員証・特典',
                    'body' => 'スマホで会員証を提示。ポイントやスタンプ、クーポンも同じアプリで確認できます。',
                    'screen' => 'current-member.jpg',
                    'screen_alt' => '会員番号、ランク、ポイントを表示するReggitの会員証',
                ],
                [
                    'id' => 'order',
                    'title' => 'モバイルオーダー',
                    'body' => '商品を選んでスマホから注文。店舗は注文を受け付け、準備から受け渡しまで管理できます。',
                    'screen' => 'current-order.jpg',
                    'screen_alt' => '商品を選んで注文できるReggitの商品一覧',
                ],
                [
                    'id' => 'queue',
                    'title' => '予約・順番待ち',
                    'body' => '予約や当日の受付もスマホから。お客様は待ち状況を、店舗は予約と受付の一覧を確認できます。',
                    'screen' => 'current-queue.jpg',
                    'screen_alt' => '受付番号と待ち状況を確認できるReggitの順番待ち画面',
                ],
            ];
        @endphp
        <section id="features" class="lp-features" aria-labelledby="features-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <h2 id="features-title">お店に必要な機能を、選んで使う。</h2>
                    <p>会員証から始めることも、注文や受付を組み合わせることも。<br>使う機能は、管理画面で設定できます。</p>
                </div>
                <div class="lp-feature-list">
                    @foreach ($features as $feature)
                        <article class="lp-feature" aria-labelledby="feature-{{ $feature['id'] }}">
                            <div class="lp-feature__copy">
                                <h3 id="feature-{{ $feature['id'] }}">{{ $feature['title'] }}</h3>
                                <p>{{ $feature['body'] }}</p>
                            </div>
                            <figure class="lp-feature__screen">
                                <img src="{{ asset('images/landing/app/'.$feature['screen']) }}" alt="{{ $feature['screen_alt'] }}" width="390" height="844" loading="lazy">
                            </figure>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lp-benefits" aria-labelledby="benefits-title">
            <div class="lp-container lp-benefits__grid">
                <div class="lp-benefits__copy">
                    <h2 id="benefits-title">来店のあとにも、<br>お客様とつながる。</h2>
                    <p>貯まったポイントや、次に使えるクーポンを手元に。<br>店舗はお知らせや特典を届けて、次の来店をご案内できます。</p>
                    <ul class="lp-benefits__list">
                        <li>ポイント・スタンプの付与</li>
                        <li>クーポンの発行・配信</li>
                        <li>会員向けのお知らせ</li>
                    </ul>
                </div>
                <div class="lp-benefits__screens">
                    <figure><img src="{{ asset('images/landing/app/current-member.jpg') }}" alt="ポイント残高と履歴を確認できる現在のReggit会員証画面" width="390" height="844" loading="lazy"><figcaption>会員証・ポイント</figcaption></figure>
                    <figure><img src="{{ asset('images/landing/app/current-coupon.jpg') }}" alt="Reggitで受け取ったクーポンの一覧" width="390" height="844" loading="lazy"><figcaption>クーポン</figcaption></figure>
                </div>
            </div>
        </section>

        <section class="lp-options" aria-labelledby="options-title">
            <div class="lp-container lp-options__grid">
                <div>
                    <h2 id="options-title">お店に合う、<br>2つのプラン。</h2>
                    <p>LINEで手軽に始めるか、専用アプリも用意するか。<br>お客様への届け方に合わせて選べます。</p>
                </div>
                <dl class="lp-options__list">
                    <div><dt>LINEプラン</dt><dd>お客様はLINEから利用。専用アプリのインストールは必要ありません。</dd></div>
                    <div><dt>ネイティブアプリプラン</dt><dd>LINEミニアプリに加えて、店舗・ブランド専用のiOS / Androidアプリを利用できます。公開には設定・審査などの準備が必要です。</dd></div>
                    <div><dt>スマレジ連携</dt><dd>スマレジとの連携が必須です。POSの会員・購買情報と連携して利用します。</dd></div>
                    <div><dt>画面のカスタマイズ</dt><dd>ロゴや配色、メニューを店舗に合わせて設定できます。</dd></div>
                </dl>
            </div>
        </section>

        <section id="flow" class="lp-flow" aria-labelledby="flow-title">
            <div class="lp-container">
                <div class="lp-section-heading"><h2 id="flow-title">導入登録の流れ</h2><p>メール認証から、店舗の管理画面の発行まで。</p></div>
                <ol class="lp-flow__steps">
                    <li><span class="lp-flow__number" aria-hidden="true">1</span><div><h3>メール認証</h3><p>登録したメールアドレスに届く<br>リンクを開きます。</p></div></li>
                    <li><span class="lp-flow__number" aria-hidden="true">2</span><div><h3>店舗情報の入力</h3><p>店舗名や担当者の情報、<br>希望するプランを入力します。</p></div></li>
                    <li><span class="lp-flow__number" aria-hidden="true">3</span><div><h3>管理画面の発行</h3><p>店舗アプリのURLと<br>ログイン情報をお渡しします。</p></div></li>
                </ol>
            </div>
        </section>

        <section id="faq" class="lp-faq" aria-labelledby="faq-title">
            <div class="lp-container lp-faq__grid">
                <h2 id="faq-title">よくある質問</h2>
                <div class="lp-faq__items">
                    <details><summary>飲食店以外でも使えますか？<span aria-hidden="true"></span></summary><p>はい。物販店などでも、会員証、ポイント、スタンプ、クーポンを利用できます。注文・予約・順番待ちは、必要な機能を選んで使えます。</p></details>
                    <details><summary>お客様はアプリのインストールが必要ですか？<span aria-hidden="true"></span></summary><p>LINEミニアプリはインストール不要です。店舗の運用に合わせて、iOS・Androidの専用アプリにも対応しています。</p></details>
                    <details><summary>スマレジとの連携は必須ですか？<span aria-hidden="true"></span></summary><p>はい、スマレジとの連携は必須です。</p></details>
                    <details><summary>導入登録後、すぐに契約が発生しますか？<span aria-hidden="true"></span></summary><p>メールアドレスの登録と店舗情報の入力だけで、すぐに契約が発生することはありません。</p></details>
                    <details><summary>専用アプリも登録後すぐに公開されますか？<span aria-hidden="true"></span></summary><p>専用アプリの公開には、別途設定や準備が必要です。導入登録では、まず店舗の管理画面とアプリURLを発行します。</p></details>
                </div>
            </div>
        </section>

        <section id="register" class="lp-register" aria-labelledby="register-title">
            <div class="lp-container lp-register__grid">
                <div class="lp-register__copy">
                    <h2 id="register-title">まずは、お店のアプリを<br>つくってみませんか。</h2>
                    <p>メール認証後、店舗情報の入力へ進みます。<br>登録後すぐに契約が発生することはありません。</p>
                    <a class="lp-link" href="{{ route('demo.liff') }}">登録の前にデモを体験する</a>
                </div>
                <form class="lp-register__form" method="POST" action="{{ route('landing.register.email') }}">
                    @csrf
                    <label for="landing-registration-email">メールアドレス</label>
                    <input id="landing-registration-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" spellcheck="false" required maxlength="255" placeholder="name@company.jp">
                    <button class="lp-button" type="submit">認証メールを送る</button>
                    <p>認証メールのリンクから登録を進められます。</p>
                </form>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer__main">
            <div><a class="lp-brand" href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/reggit-logo.png') }}" alt="" width="40" height="40"><span>Reggit</span></a><p>お店のためのLINEミニアプリ・管理システム</p></div>
            <nav aria-label="フッターナビゲーション"><a href="#features">できること</a><a href="#flow">導入の流れ</a><a href="{{ route('help.index') }}">ヘルプ・使い方</a><a href="{{ route('demo.liff') }}">デモを体験する</a><a href="{{ route('admin.login') }}">管理画面ログイン</a></nav>
        </div>
        <div class="lp-container lp-footer__bottom"><small>&copy; {{ date('Y') }} Reggit</small></div>
    </footer>
    <script>
        document.querySelectorAll('.lp-mobile-nav nav a').forEach((link) => {
            link.addEventListener('click', () => {
                link.closest('details').open = false;
            });
        });
    </script>
</body>
</html>
