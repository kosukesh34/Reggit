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
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=14">
</head>
<body class="lp">
    <header class="lp-nav">
        <div class="lp-nav__inner">
            <a class="lp-nav__brand" href="/" aria-label="Reggit">
                <img
                    src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2"
                    alt="Reggit"
                    width="48"
                    height="48"
                >
                <span class="lp-nav__brand-text">Reggit</span>
            </a>
            <div class="lp-nav__right">
                <nav class="lp-nav__links" aria-label="ページ内ナビ">
                    <a class="lp-nav__link" href="#features">機能</a>
                    <a class="lp-nav__link" href="#platform">プラットフォーム</a>
                </nav>
                <a class="lp-nav__cta" href="{{ route('admin.login') }}">管理画面ログイン</a>
            </div>
        </div>
    </header>

    <section class="lp-hero" aria-label="ヒーロー">
        <div class="lp-hero__intro">
            <img
                class="lp-hero__logo"
                src="{{ asset('images/landing/logos/reggit-app-icon-lg.png') }}?v=2"
                alt="Reggit"
                width="120"
                height="120"
            >
            <h1 class="lp-hero__title">LINEで届く、<br>店舗のモバイルオーダーと会員証</h1>
            <p class="lp-hero__lead">注文・ポイント・スタンプ・順番待ちを、LINEミニアプリと管理画面でひとつに。</p>
            <div class="lp-hero__actions">
                <a class="lp-btn lp-btn--primary" href="{{ route('admin.login') }}">管理画面ログイン</a>
                <a class="lp-btn lp-btn--ghost" href="#features">サービス概要へ</a>
            </div>
        </div>
        <div class="lp-hero__visual">
            <img
                class="lp-hero__image"
                src="{{ asset('images/landing/lp-hero-cafe.jpg') }}?v=5"
                alt="店舗の風景"
                width="1600"
                height="900"
                fetchpriority="high"
            >
        </div>
    </section>

    @php
        $logoBrands = [
            'NORD KITCHEN', '蒼葉珈琲', 'MILL & CO.', 'ORIZA', 'HARBOR DINING', '灯火食堂',
            'LUMINA BAKE', '翠庭レストラン', 'ATELIER 28', '風見鶏食堂', 'BLUE SPOON', '陽だまりパン',
            'KEI DINER', '森のテーブル', 'PULSE CAFE', '白鷺キッチン', 'FOLIO EATS', '月影バル',
            'CANVAS BISTRO', '虹色うどん', 'NORTH OVEN', '茶柱屋', 'RIVER SIDE', '木漏れ日食堂',
            'APEX GRILL', '夕凪カフェ', 'STONE & SALT', '豆の音', 'URBAN BOWL', '花筏キッチン',
            'MINT LEAF', '潮風ダイナー', 'GOLDEN CRUST', '雪見亭', 'PARK HOUSE', '霧島コーヒー',
        ];
    @endphp
    <section class="lp-logos" aria-label="導入イメージ">
        <p class="lp-logos__label">導入イメージ（架空ブランド）</p>
        <div class="lp-marquee" aria-hidden="true">
            <div class="lp-marquee__track">
                @foreach ([1, 2] as $loopCopy)
                    <ul class="lp-marquee__group">
                        @foreach ($logoBrands as $index => $brandName)
                            <li class="lp-marquee__item">
                                <img
                                    src="{{ asset('images/landing/logos/brand-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'.svg') }}?v=11"
                                    alt="{{ $brandName }}"
                                    height="64"
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
                    src="{{ asset('images/landing/lp-feature-order.jpg') }}?v=5"
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
                    src="{{ asset('images/landing/lp-feature-member.jpg') }}?v=5"
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
                    src="{{ asset('images/landing/lp-feature-queue.jpg') }}?v=5"
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

        <section id="platform" class="lp-platform lp-reveal">
            <div class="lp-platform__intro">
                <p class="lp-section__eyebrow">Platform</p>
                <h2 class="lp-section__title">エンタープライズ運用を見据えた構成</h2>
                <p class="lp-section__lead">店舗展開と権限管理を前提に、運用しやすい管理画面を提供します。</p>
            </div>
            <div class="lp-platform__grid">
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

        <section class="lp-cta lp-reveal">
            <div class="lp-cta__inner">
                <h2 class="lp-cta__title">店舗のデジタル接点を、今日から整える</h2>
                <p class="lp-cta__lead">管理画面からプロジェクト設定・メニュー・会員運用を開始できます。</p>
                <a class="lp-btn lp-btn--primary" href="{{ route('admin.login') }}">管理画面ログイン</a>
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
            }, { threshold: 0.18 });
            nodes.forEach(function (el) { observer.observe(el); });
        })();
    </script>
</body>
</html>
