<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reggit — LINEで届く、店舗のモバイルオーダーと会員証</title>
    <meta name="description" content="注文・ポイント・スタンプ・順番待ちを、LINEミニアプリと管理画面でひとつに。">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=25">
</head>
<body class="lp">
    <header class="lp-nav">
        <div class="lp-nav__inner">
            <a class="lp-nav__brand" href="/" aria-label="Reggit">
                <img
                    src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2"
                    alt="Reggit"
                    width="40"
                    height="40"
                >
                <span class="lp-nav__brand-text">Reggit</span>
            </a>
            <div class="lp-nav__right">
                <nav class="lp-nav__links" aria-label="ページ内ナビ">
                    <a class="lp-nav__link" href="#features">機能</a>
                    <a class="lp-nav__link" href="#flow">使い方</a>
                    <a class="lp-nav__link" href="#screens">画面</a>
                    <a class="lp-nav__link" href="#platform">プラットフォーム</a>
                    <a class="lp-nav__link" href="#faq">FAQ</a>
                </nav>
                <a class="lp-nav__app" href="/liff?demo=1">デモアプリはこちら</a>
                <a class="lp-nav__cta" href="{{ route('admin.login') }}">管理画面ログイン</a>
            </div>
        </div>
    </header>

    <section class="lp-hero" aria-label="ヒーロー">
        <div class="lp-hero__media" aria-hidden="true">
            <img
                class="lp-hero__image"
                src="{{ asset('images/landing/lp-hero-cafe.jpg') }}?v=6"
                alt=""
                width="1600"
                height="900"
                fetchpriority="high"
            >
        </div>
        <div class="lp-hero__scrim" aria-hidden="true"></div>
        <div class="lp-hero__content">
            <p class="lp-hero__brand">
                <img
                    class="lp-hero__brand-icon"
                    src="{{ asset('images/landing/logos/reggit-app-icon-lg.png') }}?v=2"
                    alt=""
                    width="72"
                    height="72"
                >
                <span class="lp-hero__brand-name">Reggit</span>
            </p>
            <h1 class="lp-hero__title">LINEで届く、<br>店舗のモバイルオーダーと会員証</h1>
            <p class="lp-hero__lead">注文・ポイント・スタンプ・順番待ちを、LINEミニアプリと管理画面でひとつに。</p>
            <div class="lp-hero__actions">
                <a class="lp-btn lp-btn--primary" href="{{ route('admin.login') }}">管理画面ログイン</a>
                <a class="lp-btn lp-btn--ghost" href="#features">サービス概要へ</a>
            </div>
        </div>
    </section>

    @php
        $logoBrandFiles = [
            '01', '02', '03', '04', '05', '06', '07', '08', '09',
            '10', '11', '12', '13', '14', '15', '16', '17', '18',
        ];
    @endphp
    <section class="lp-logos" aria-label="導入イメージ">
        <p class="lp-logos__label">導入イメージ（架空ブランド）</p>
        <div class="lp-marquee" aria-hidden="true">
            <div class="lp-marquee__track">
                @foreach ([1, 2] as $loopCopy)
                    <ul class="lp-marquee__group">
                        @foreach ($logoBrandFiles as $file)
                            <li class="lp-marquee__item">
                                <img
                                    class="lp-brand-logo"
                                    src="{{ asset('images/landing/logos/brand-'.$file.'.png') }}?v=20"
                                    alt=""
                                    width="200"
                                    height="80"
                                    loading="lazy"
                                >
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
        <div class="lp-marquee lp-marquee--reverse" aria-hidden="true">
            <div class="lp-marquee__track">
                @foreach ([1, 2] as $loopCopy)
                    <ul class="lp-marquee__group">
                        @foreach (array_reverse($logoBrandFiles) as $file)
                            <li class="lp-marquee__item">
                                <img
                                    class="lp-brand-logo"
                                    src="{{ asset('images/landing/logos/brand-'.$file.'.png') }}?v=20"
                                    alt=""
                                    width="200"
                                    height="80"
                                    loading="lazy"
                                >
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>

    <main>
        <section id="features" class="lp-section">
            <p class="lp-section__eyebrow">Product</p>
            <h2 class="lp-section__title">店舗オペレーションと顧客接点を、<br>ひとつの基盤で</h2>
            <p class="lp-section__lead">LINEを起点に、来店前後の体験と店舗側の管理を途切れさせません。</p>
        </section>

        <section class="lp-feature lp-feature--order lp-reveal">
            <div class="lp-feature__media">
                <img
                    src="{{ asset('images/landing/lp-feature-order.jpg') }}?v=6"
                    alt="店舗でのモバイルオーダーの風景"
                    width="1600"
                    height="900"
                    loading="eager"
                    decoding="async"
                >
            </div>
            <div class="lp-feature__copy">
                <p class="lp-feature__label">01 / Order</p>
                <h2 class="lp-feature__title">モバイルオーダー</h2>
                <p class="lp-feature__text">LINE内でメニュー閲覧から注文まで完結。待ち時間を減らし、オペレーションをなめらかにします。</p>
            </div>
        </section>

        <section class="lp-feature lp-feature--member lp-reveal">
            <div class="lp-feature__media">
                <img
                    src="{{ asset('images/landing/lp-feature-member.jpg') }}?v=6"
                    alt="会員証を使う店舗の風景"
                    width="1600"
                    height="900"
                    loading="eager"
                    decoding="async"
                >
            </div>
            <div class="lp-feature__copy">
                <p class="lp-feature__label">02 / Membership</p>
                <h2 class="lp-feature__title">会員証・ポイント・スタンプ</h2>
                <p class="lp-feature__text">デジタル会員証でポイントとスタンプを管理。クーポン配信やランク設計でリピートを育てます。</p>
            </div>
        </section>

        <section class="lp-feature lp-feature--queue lp-reveal">
            <div class="lp-feature__media">
                <img
                    src="{{ asset('images/landing/lp-feature-queue.jpg') }}?v=6"
                    alt="順番待ちのある店舗の風景"
                    width="1600"
                    height="900"
                    loading="eager"
                    decoding="async"
                >
            </div>
            <div class="lp-feature__copy">
                <p class="lp-feature__label">03 / Queue &amp; Booking</p>
                <h2 class="lp-feature__title">順番待ち・予約</h2>
                <p class="lp-feature__text">順番登録から呼び出し、予約枠まで。店舗側の管理画面で状況を一元管理できます。</p>
            </div>
        </section>

        <section id="flow" class="lp-flow lp-reveal">
            <div class="lp-section lp-section--compact">
                <p class="lp-section__eyebrow">How it works</p>
                <h2 class="lp-section__title">導入から日常運用まで、<br>シンプルな流れ</h2>
                <p class="lp-section__lead">まずは管理画面で店舗を整え、LINEから顧客体験を届けます。</p>
            </div>
            <ol class="lp-flow__steps">
                <li class="lp-flow__step">
                    <span class="lp-flow__num">01</span>
                    <h3>プロジェクトを作成</h3>
                    <p>店舗情報・メニュー・会員設定を管理画面から登録します。</p>
                </li>
                <li class="lp-flow__step">
                    <span class="lp-flow__num">02</span>
                    <h3>LINEミニアプリを公開</h3>
                    <p>LIFF連携で、お客様は友だち追加後すぐに利用を開始できます。</p>
                </li>
                <li class="lp-flow__step">
                    <span class="lp-flow__num">03</span>
                    <h3>注文・会員・順番を運用</h3>
                    <p>来店前後の接点をひとつの画面で把握し、スタッフが迷わず対応できます。</p>
                </li>
                <li class="lp-flow__step">
                    <span class="lp-flow__num">04</span>
                    <h3>データで改善する</h3>
                    <p>ポイント・クーポン・リピート施策を続けながら、体験を磨いていけます。</p>
                </li>
            </ol>
        </section>

        <section id="screens" class="lp-screens lp-reveal">
            <div class="lp-section lp-section--compact">
                <p class="lp-section__eyebrow">App</p>
                <h2 class="lp-section__title">お客様の手元で完結する体験</h2>
                <p class="lp-section__lead">ホーム・注文・会員・スタンプ・順番待ちまで、LINE内の画面でつながります。</p>
                <a class="lp-screens__app-link" href="/liff?demo=1">デモアプリはこちら</a>
            </div>
            <div class="lp-screens__rail">
                <figure class="lp-screens__item">
                    <img src="{{ asset('images/landing/app/screen-home.png') }}?v=2" alt="ホーム画面" width="390" height="844" loading="lazy">
                    <figcaption>ホーム</figcaption>
                </figure>
                <figure class="lp-screens__item">
                    <img src="{{ asset('images/landing/app/screen-order.png') }}?v=2" alt="注文画面" width="390" height="844" loading="lazy">
                    <figcaption>注文</figcaption>
                </figure>
                <figure class="lp-screens__item">
                    <img src="{{ asset('images/landing/app/screen-member.png') }}?v=2" alt="会員証画面" width="390" height="844" loading="lazy">
                    <figcaption>会員証</figcaption>
                </figure>
                <figure class="lp-screens__item">
                    <img src="{{ asset('images/landing/app/screen-stamps.png') }}?v=2" alt="スタンプ画面" width="390" height="844" loading="lazy">
                    <figcaption>スタンプ</figcaption>
                </figure>
                <figure class="lp-screens__item">
                    <img src="{{ asset('images/landing/app/screen-queue.png') }}?v=2" alt="順番待ち画面" width="390" height="844" loading="lazy">
                    <figcaption>順番待ち</figcaption>
                </figure>
            </div>
        </section>

        <section id="benefits" class="lp-benefits lp-reveal">
            <div class="lp-section lp-section--compact">
                <p class="lp-section__eyebrow">Benefits</p>
                <h2 class="lp-section__title">現場と顧客の両方に効く設計</h2>
                <p class="lp-section__lead">待ち時間・リピート・店舗横断の管理まで、日常の課題に直接向き合います。</p>
            </div>
            <div class="lp-benefits__grid">
                <article class="lp-benefits__item">
                    <h3>待ち時間を減らす</h3>
                    <p>事前注文と順番待ちで、店頭の混雑と説明コストを下げます。</p>
                </article>
                <article class="lp-benefits__item">
                    <h3>リピートを育てる</h3>
                    <p>ポイント・スタンプ・クーポンで、次回来店のきっかけをつくります。</p>
                </article>
                <article class="lp-benefits__item">
                    <h3>スタッフの迷いを減らす</h3>
                    <p>注文状況と会員情報を同じ基盤で見られるので、引き継ぎが楽になります。</p>
                </article>
                <article class="lp-benefits__item">
                    <h3>多店舗でも崩れない</h3>
                    <p>プロジェクト単位の権限と設定で、拡大しても運用ルールを保てます。</p>
                </article>
            </div>
        </section>

        <section id="usecases" class="lp-usecases lp-reveal">
            <div class="lp-section lp-section--compact">
                <p class="lp-section__eyebrow">Use cases</p>
                <h2 class="lp-section__title">業態ごとの使い方</h2>
                <p class="lp-section__lead">カフェから食堂まで、店舗のリズムに合わせて機能を組み合わせられます。</p>
            </div>
            <div class="lp-usecases__list">
                <article class="lp-usecases__item">
                    <h3>カフェ・ベーカリー</h3>
                    <p>モバイルオーダーとスタンプで、ピーク時の回転と常連づくりを両立します。</p>
                </article>
                <article class="lp-usecases__item">
                    <h3>レストラン・食堂</h3>
                    <p>順番待ちと予約で来店の波を整え、会員証で再来店につなげます。</p>
                </article>
                <article class="lp-usecases__item">
                    <h3>多店舗ブランド</h3>
                    <p>店舗横断の権限管理と設定分離で、ブランド全体の運用を一本化します。</p>
                </article>
            </div>
        </section>

        <section id="platform" class="lp-platform lp-reveal">
            <div class="lp-platform__intro">
                <p class="lp-section__eyebrow">Platform</p>
                <h2 class="lp-section__title">エンタープライズ運用を見据えた構成</h2>
                <p class="lp-section__lead">店舗展開と権限管理を前提に、運用しやすい管理画面を提供します。</p>
            </div>
            <div class="lp-platform__list">
                <article class="lp-platform__item">
                    <h3>マルチ店舗管理</h3>
                    <p>プロジェクト単位で店舗・権限・設定を分離し、拡大時も運用を崩しません。</p>
                </article>
                <article class="lp-platform__item">
                    <h3>LINE / POS 連携</h3>
                    <p>LIFFとスマレジ連携により、会員・ポイント・会計の流れをつなげます。</p>
                </article>
                <article class="lp-platform__item">
                    <h3>セキュアな管理画面</h3>
                    <p>HTTPS前提の管理ログインと、役割に応じた操作権限で安全に運用できます。</p>
                </article>
            </div>
        </section>

        <section id="faq" class="lp-faq lp-reveal">
            <div class="lp-section lp-section--compact">
                <p class="lp-section__eyebrow">FAQ</p>
                <h2 class="lp-section__title">よくある質問</h2>
                <p class="lp-section__lead">導入前に気になりやすい点をまとめました。</p>
            </div>
            <div class="lp-faq__list">
                <details class="lp-faq__item">
                    <summary>専用アプリのダウンロードは必要ですか？</summary>
                    <p>お客様はLINEミニアプリから利用できます。店舗側は管理画面から運用します。</p>
                </details>
                <details class="lp-faq__item">
                    <summary>複数店舗でも使えますか？</summary>
                    <p>はい。プロジェクト単位で店舗・権限・設定を分けて運用できます。</p>
                </details>
                <details class="lp-faq__item">
                    <summary>既存のPOSや会員施策と連携できますか？</summary>
                    <p>スマレジ連携をはじめ、会員・ポイント・会計の流れをつなぐ構成を想定しています。</p>
                </details>
                <details class="lp-faq__item">
                    <summary>まずは一部機能だけ使えますか？</summary>
                    <p>モバイルオーダー・会員証・順番待ちを組み合わせて、必要な範囲から始められます。</p>
                </details>
            </div>
        </section>

        <section class="lp-cta lp-reveal">
            <div class="lp-cta__inner">
                <h2 class="lp-cta__title">店舗のデジタル接点を、今日から整える</h2>
                <p class="lp-cta__lead">管理画面からプロジェクト設定・メニュー・会員運用を開始できます。</p>
                <a class="lp-btn lp-btn--on-dark" href="{{ route('admin.login') }}">管理画面ログイン</a>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <a class="lp-footer__brand" href="/" aria-label="Reggit">
            <img
                src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2"
                alt="Reggit"
                width="40"
                height="40"
            >
        </a>
        <p class="lp-footer__copy">&copy; {{ date('Y') }} Reggit</p>
    </footer>

    <script>
        (function () {
            const nodes = document.querySelectorAll('.lp-reveal');
            if (!('IntersectionObserver' in window) || nodes.length === 0) {
                nodes.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }
            const observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12 });
            nodes.forEach(function (el) { observer.observe(el); });
        })();
    </script>
</body>
</html>
