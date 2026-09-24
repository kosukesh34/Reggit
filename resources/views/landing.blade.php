<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ffffff">
    <meta name="description" content="Reggitは、LINEミニアプリと店舗管理画面で会員証・注文・予約・順番待ちをまとめる店舗向けサービスです。">
    <title>Reggit｜会員・注文・予約を、ひとつの店舗アプリに</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/landing/enterprise-logos/reggit-mark.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/landing/app/actual-admin.webp') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v=4">
</head>
<body class="lp">
    <a class="lp-skip" href="#main">本文へ移動</a>
    <header class="lp-header">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/landing/enterprise-logos/reggit-wordmark.svg') }}" alt="Reggit" width="160" height="36">
            </a>
            <nav class="lp-nav" aria-label="メインナビゲーション">
                <a href="#features">機能</a>
                <a href="#screens">実際の画面</a>
                <a href="#scenes">利用シーン</a>
                <a href="#faq">よくある質問</a>
            </nav>
            <div class="lp-header__actions">
                <a class="lp-login" href="{{ route('admin.login') }}">管理画面ログイン</a>
                <a class="lp-button lp-button--header" href="#register">導入登録を始める</a>
            </div>
            <details class="lp-mobile-nav">
                <summary>メニュー<span aria-hidden="true"></span></summary>
                <nav aria-label="モバイルナビゲーション">
                    <a href="#features">機能</a>
                    <a href="#screens">実際の画面</a>
                    <a href="#scenes">利用シーン</a>
                    <a href="#faq">よくある質問</a>
                    <a href="{{ route('admin.login') }}">管理画面ログイン</a>
                    <a href="#register">導入登録を始める</a>
                </nav>
            </details>
        </div>
    </header>

    <main id="main">
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="lp-container">
                <div class="lp-hero__intro">
                    <div>
                        <p class="lp-kicker">店舗向けLINEミニアプリ・管理システム</p>
                        <h1 id="hero-title">会員・注文・予約を、<br>ひとつの店舗アプリに。</h1>
                    </div>
                    <div class="lp-hero__aside">
                        <p>Reggitは、LINEミニアプリと店舗管理画面で、会員証・モバイルオーダー・予約・順番待ちをまとめるサービスです。</p>
                        <div class="lp-hero__actions">
                            <a class="lp-button" href="#register">導入登録を始める</a>
                            <a class="lp-link" href="/liff">アプリのデモを体験する</a>
                        </div>
                    </div>
                </div>

                <div id="screens" class="lp-product" aria-label="Reggitの実際の画面">
                    <figure class="lp-product__mobile">
                        <div class="lp-product__label"><span>お客様向け</span><strong>LINEミニアプリ</strong></div>
                        <div class="lp-product__mobile-frame"><img src="{{ asset('images/landing/app/actual-home.webp') }}" alt="実際のReggit LINEミニアプリのホーム画面" width="780" height="1688" fetchpriority="high"></div>
                    </figure>
                    <div class="lp-product__connector" aria-hidden="true"><span></span></div>
                    <figure class="lp-product__admin">
                        <div class="lp-product__label"><span>店舗向け</span><strong>管理画面</strong></div>
                        <div class="lp-product__admin-viewport" tabindex="0"><img src="{{ asset('images/landing/app/actual-admin.webp') }}" alt="デモデータを使用した実際のReggit管理画面。注文、予約、順番待ち、会員情報を表示" width="1512" height="982" fetchpriority="high"></div>
                        <figcaption class="lp-product__scroll-hint">横にスクロールして画面を確認できます</figcaption>
                    </figure>
                </div>
                <p class="lp-product__note">画面はデモデータを使用しています。表示内容は店舗の設定により異なります。</p>
            </div>
        </section>

        <section class="lp-overview" aria-label="Reggitでつながる領域">
            <div class="lp-container lp-overview__grid">
                <div><span>お客様</span><strong>LINEから利用</strong><p>会員証・注文・予約・順番待ち</p></div>
                <div><span>店舗</span><strong>管理画面で運営</strong><p>会員・商品・注文・受付状況</p></div>
                <div><span>外部連携</span><strong>必要に応じて接続</strong><p>スマレジ、決済、Walletなど</p></div>
            </div>
        </section>

        @php
            $features = [
                [
                    'id' => 'order',
                    'label' => 'モバイルオーダー',
                    'title' => '注文を、受け付けやすく。',
                    'body' => 'お客様は商品を選び、スマートフォンから注文できます。店舗では注文内容と対応状況を管理画面で確認できます。',
                    'photo' => 'kitchen-team.webp',
                    'photo_alt' => '注文を確認しながら商品を準備する店舗スタッフ',
                    'screen' => 'actual-order.webp',
                    'screen_alt' => 'Reggitの実際の商品一覧・注文画面',
                ],
                [
                    'id' => 'member',
                    'label' => '会員証・特典',
                    'title' => '会員証と特典を、ひとつに。',
                    'body' => '会員証、ポイント、スタンプ、クーポンをアプリにまとめます。お客様は保有する特典を確認し、店頭で提示できます。',
                    'photo' => 'member-retail.webp',
                    'photo_alt' => '店舗でスマートフォンの会員証を提示するお客様',
                    'screen' => 'actual-member.webp',
                    'screen_alt' => 'Reggitの実際のデジタル会員証画面',
                ],
                [
                    'id' => 'queue',
                    'label' => '順番待ち・予約',
                    'title' => '待ち時間の見通しを、手元に。',
                    'body' => '順番待ちの受付と現在の状況をスマートフォンで確認できます。予約も同じアプリから利用できます。',
                    'photo' => 'queue-family.webp',
                    'photo_alt' => '順番待ちの間に店外で過ごす家族',
                    'screen' => 'actual-queue.webp',
                    'screen_alt' => 'Reggitの実際の順番待ち画面',
                ],
            ];
        @endphp
        <section id="features" class="lp-features" aria-labelledby="features-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">主な機能</p>
                    <h2 id="features-title">来店前から、次の来店まで。</h2>
                    <p>お客様が使う画面と店舗が扱う情報を、同じサービスの中でつなぎます。</p>
                </div>
                <div class="lp-feature-list">
                    @foreach ($features as $feature)
                        <article class="lp-feature" aria-labelledby="feature-{{ $feature['id'] }}">
                            <div class="lp-feature__copy">
                                <p class="lp-feature__label">{{ $feature['label'] }}</p>
                                <h3 id="feature-{{ $feature['id'] }}">{{ $feature['title'] }}</h3>
                                <p>{{ $feature['body'] }}</p>
                            </div>
                            <figure class="lp-feature__photo">
                                <img src="{{ asset('images/landing/editorial/'.$feature['photo']) }}" alt="{{ $feature['photo_alt'] }}" width="1536" height="1024" loading="lazy">
                            </figure>
                            <figure class="lp-feature__screen">
                                <img src="{{ asset('images/landing/app/'.$feature['screen']) }}" alt="{{ $feature['screen_alt'] }}" width="780" height="1688" loading="lazy">
                                <figcaption>実際のアプリ画面</figcaption>
                            </figure>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lp-benefits" aria-labelledby="benefits-title">
            <div class="lp-container lp-benefits__grid">
                <div class="lp-benefits__copy">
                    <p class="lp-kicker">会員向け機能</p>
                    <h2 id="benefits-title">使える特典を、見つけやすく。</h2>
                    <p>ポイント残高やクーポンを、会員証とあわせて確認できます。店舗は特典の内容を管理画面から設定できます。</p>
                </div>
                <div class="lp-benefits__screens">
                    <figure><img src="{{ asset('images/landing/app/actual-point.webp') }}" alt="Reggitの実際のポイント画面" width="780" height="1688" loading="lazy"><figcaption>ポイント</figcaption></figure>
                    <figure><img src="{{ asset('images/landing/app/actual-coupon.webp') }}" alt="Reggitの実際のクーポン画面" width="780" height="1688" loading="lazy"><figcaption>クーポン</figcaption></figure>
                </div>
            </div>
        </section>

        <section id="scenes" class="lp-scenes" aria-labelledby="scenes-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">利用シーン</p>
                    <h2 id="scenes-title">業態に合わせて、必要な機能を選ぶ。</h2>
                    <p>会員証を中心に使う店舗にも、注文や順番待ちが必要な店舗にも。画面と機能は店舗ごとに設定できます。</p>
                </div>
                <div class="lp-scenes__grid">
                    <figure><img src="{{ asset('images/landing/editorial/store-hero.webp') }}" alt="カフェのカウンターで接客する様子" width="1536" height="1024" loading="lazy"><figcaption><strong>カフェ</strong><span>注文・会員証</span></figcaption></figure>
                    <figure><img src="{{ asset('images/landing/editorial/workshop-hands.webp') }}" alt="陶器店で商品を包む様子" width="1536" height="1024" loading="lazy"><figcaption><strong>物販店</strong><span>会員証・ポイント</span></figcaption></figure>
                    <figure><img src="{{ asset('images/landing/editorial/table-conversation.webp') }}" alt="食堂で食事を楽しむお客様" width="1536" height="1024" loading="lazy"><figcaption><strong>飲食店</strong><span>予約・順番待ち</span></figcaption></figure>
                </div>
                <p class="lp-scenes__note">写真は利用シーンのイメージです。</p>
            </div>
        </section>

        @php
            $sampleCompanies = [
                ['file' => 'tonoma.svg', 'name' => 'TONOMA'],
                ['file' => 'nolena.svg', 'name' => 'NOLENA'],
                ['file' => 'mareto.svg', 'name' => 'MARETO'],
                ['file' => 'oruma.svg', 'name' => 'ORUMA'],
                ['file' => 'senoma.svg', 'name' => 'SENOMA'],
            ];
        @endphp
        <section class="lp-identities" aria-labelledby="identities-title">
            <div class="lp-container">
                <div class="lp-identities__heading">
                    <div><p class="lp-kicker">ロゴデザイン例</p><h2 id="identities-title">ブランドごとの表情をつくる。</h2></div>
                    <p>以下は、このページのために制作した架空企業のロゴです。実際の導入企業を示すものではありません。</p>
                </div>
                <div class="lp-identities__logos">
                    @foreach ($sampleCompanies as $company)
                        <div><img src="{{ asset('images/landing/enterprise-logos/'.$company['file']) }}" alt="架空企業「{{ $company['name'] }}」のロゴ" width="200" height="64" loading="lazy"></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lp-options" aria-labelledby="options-title">
            <div class="lp-container lp-options__grid">
                <div><p class="lp-kicker">提供方法・連携</p><h2 id="options-title">お客様の入口と、店舗の運営をつなぐ。</h2></div>
                <div class="lp-options__list">
                    <div><strong>LINEミニアプリ</strong><p>インストールせずに、LINEから利用できます。</p></div>
                    <div><strong>iOS / Android</strong><p>店舗・ブランドに合わせた専用アプリにも対応します。</p></div>
                    <div><strong>スマレジ連携</strong><p>利用する機能に応じて、会員・購買情報などを連携できます。</p></div>
                </div>
            </div>
        </section>

        <section id="faq" class="lp-faq" aria-labelledby="faq-title">
            <div class="lp-container lp-faq__grid">
                <div><p class="lp-kicker">よくある質問</p><h2 id="faq-title">導入前に確認したいこと。</h2></div>
                <div class="lp-faq__items">
                    <details><summary>飲食店以外でも使えますか？<span aria-hidden="true"></span></summary><p>はい。会員証、ポイント、スタンプ、クーポンは物販店などでも利用できます。注文・予約・順番待ちは必要な機能を選べます。</p></details>
                    <details><summary>お客様はアプリのインストールが必要ですか？<span aria-hidden="true"></span></summary><p>LINEミニアプリはインストール不要です。店舗の運用に合わせて、iOS・Androidの専用アプリにも対応しています。</p></details>
                    <details><summary>スマレジとの連携は必須ですか？<span aria-hidden="true"></span></summary><p>必須ではありません。利用する機能や店舗の運用に合わせて設定できます。</p></details>
                    <details><summary>導入登録後、すぐに契約が発生しますか？<span aria-hidden="true"></span></summary><p>メールアドレスの登録と店舗情報の入力だけで、すぐに契約が発生することはありません。</p></details>
                </div>
            </div>
        </section>

        <section id="register" class="lp-register" aria-labelledby="register-title">
            <div class="lp-container lp-register__grid">
                <div class="lp-register__copy">
                    <p class="lp-kicker">導入登録</p>
                    <h2 id="register-title">店舗に合った使い方を、<br>ここから。</h2>
                    <p>まずはメールアドレスをご登録ください。認証後、店舗情報の入力フォームをご案内します。</p>
                    <img src="{{ asset('images/landing/editorial/storefront-detail.webp') }}" alt="街のお店の外観イメージ" width="1536" height="1024" loading="lazy">
                </div>
                <form class="lp-register__form" method="POST" action="{{ route('landing.register.email') }}">
                    @csrf
                    @if (session('status'))
                        <p class="lp-alert lp-alert--success" role="status">{{ session('status') }}</p>
                    @endif
                    @if ($errors->has('email'))
                        <p class="lp-alert lp-alert--error" role="alert">{{ $errors->first('email') }}</p>
                    @endif
                    <label for="lp-email">メールアドレス</label>
                    <input id="lp-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="name@company.jp">
                    <button class="lp-button" type="submit">認証メールを送る</button>
                    <small>登録後すぐに契約が発生することはありません。</small>
                </form>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer__main">
            <div><a href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/landing/enterprise-logos/reggit-wordmark-light.svg') }}" alt="Reggit" width="160" height="36"></a><p>店舗向けLINEミニアプリ・管理システム</p></div>
            <nav aria-label="フッターナビゲーション"><a href="#features">機能</a><a href="#screens">実際の画面</a><a href="/liff">アプリのデモ</a><a href="{{ route('admin.login') }}">管理画面ログイン</a></nav>
        </div>
        <div class="lp-container lp-footer__bottom"><span>&copy; {{ date('Y') }} Reggit</span><span>Reggit</span></div>
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
