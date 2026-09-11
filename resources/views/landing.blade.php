<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reggit — 会員証・注文・スタンプ・順番待ちを、ひとつの店舗アプリで</title>
    <meta name="description" content="LINEミニアプリで会員証、ポイント、スタンプ、クーポン、モバイルオーダー、順番待ち、予約を提供。必要なら同じ画面をiOS・Androidの専用アプリとしても配布できます。店舗運用はひとつの管理画面に集約します。">
    <meta name="theme-color" content="#3d2b26">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&family=Noto+Sans+JP:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=58">
</head>
<body class="lp">
    <a class="lp-skip" href="#main">本文へスキップ</a>
    <header class="lp-header">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2" alt="" width="36" height="36">
                <span>Reggit</span>
            </a>
            <nav class="lp-header__nav" aria-label="ページ内ナビゲーション">
                <a href="#plans">提供プラン</a>
                <a href="#features">機能</a>
                <a href="#screens">実際の画面</a>
                <a href="#admin">管理画面</a>
                <a href="#faq">FAQ</a>
            </nav>
            <div class="lp-header__actions">
                <a class="lp-text-link" href="/liff">LINE版を体験</a>
                <a class="lp-button lp-button--small lp-button--outline" href="#register">導入登録</a>
                <a class="lp-button lp-button--small lp-button--dark" href="{{ route('admin.login') }}">管理画面ログイン</a>
            </div>
        </div>
    </header>

    <main id="main">
        <section class="lp-container lp-hero" aria-labelledby="hero-title">
            <div class="lp-hero__copy">
                <p class="lp-eyebrow">店舗アプリ運営プラットフォーム</p>
                <h1 id="hero-title">お店のアプリを、<br>LINEでも、<br><em>ネイティブでも。</em></h1>
                <p class="lp-hero__lead">会員証、ポイント、スタンプ、クーポン、モバイルオーダー、順番待ち、予約。お客様の接点はLINEミニアプリですぐ始められ、必要なら同じ画面をiOS・Androidの専用アプリとしても配布できます。店舗側の運用は、ひとつの管理画面に集約します。</p>
                <div class="lp-hero__actions">
                    <a class="lp-button lp-button--primary" href="/liff">実際のLINE版UIを体験 <span aria-hidden="true">→</span></a>
                    <a class="lp-button lp-button--outline" href="#plans">2つのプランを見る</a>
                </div>
                <p class="lp-hero__note"><span></span> 掲載画面はデモ店舗で動作している実装UIです</p>
            </div>

            <figure class="lp-hero__media">
                <img class="lp-hero__photo" src="{{ asset('images/landing/lp-hero-reggit-v2.webp') }}" alt="タブレットで店舗を運営するカフェスタッフとスマートフォンを利用するお客様" width="1774" height="887" fetchpriority="high">
                <div class="lp-phone lp-phone--hero" aria-label="実装済みのReggitアプリホーム画面">
                    <img src="{{ asset('images/landing/app/actual-home.webp') }}?v=5" alt="デモ店舗のアプリホーム画面" width="390" height="844">
                </div>
                <figcaption>店舗での利用イメージ <span>／</span> 実装済みアプリ画面</figcaption>
            </figure>
        </section>

        <section class="lp-proof" aria-label="Reggitの提供形態">
            <div class="lp-container lp-proof__grid">
                <div><span>01</span><p><strong>LINEミニアプリ</strong>インストールせず、LINEから利用</p></div>
                <div><span>02</span><p><strong>iOS・Android</strong>同じ画面を専用アプリとして配布</p></div>
                <div><span>03</span><p><strong>共通の管理画面</strong>会員・注文・店舗運営を一元化</p></div>
            </div>
        </section>

        @php
            $logoBrandFilesTop = ['02', '06', '08', '12', '15', '18', '20', '24', '26', '28', '30', '32', '34'];
            $logoBrandFilesBottom = ['03', '07', '10', '14', '16', '23', '25', '29', '33', '01', '05', '13', '17'];
        @endphp
        <section class="lp-logos" aria-label="導入イメージ">
            <p class="lp-logos__label">導入イメージ（架空の店舗ブランド）</p>
            <div class="lp-marquee" aria-hidden="true">
                <div class="lp-marquee__track">
                    @foreach ([1, 2] as $loopCopy)
                        <ul class="lp-marquee__group">
                            @foreach ($logoBrandFilesTop as $file)
                                <li class="lp-marquee__item">
                                    <img class="lp-brand-logo" src="{{ asset('images/landing/logos/brand-'.$file.'.svg') }}" alt="" height="56" loading="eager" decoding="async">
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
                            @foreach ($logoBrandFilesBottom as $file)
                                <li class="lp-marquee__item">
                                    <img class="lp-brand-logo" src="{{ asset('images/landing/logos/brand-'.$file.'.svg') }}" alt="" height="56" loading="eager" decoding="async">
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="plans" class="lp-container lp-section lp-plans" aria-labelledby="plans-title">
            <header class="lp-section-heading">
                <div>
                    <p class="lp-eyebrow">提供プラン</p>
                    <h2 id="plans-title">入口は2つ。<br>運営基盤はひとつ。</h2>
                </div>
                <p>まずはLINEで始めるか、同じ画面を専用アプリとしても配布するか。事業のフェーズに合わせて選択できます。</p>
            </header>

            <div class="lp-plan-list">
                <article class="lp-plan">
                    <div class="lp-plan__index">01</div>
                    <div class="lp-plan__title">
                        <p>LINE PLAN</p>
                        <h3>LINEミニアプリ</h3>
                        <span>インストール不要</span>
                    </div>
                    <div class="lp-plan__description">
                        <p>普段使っているLINEの中で、会員証・注文・スタンプ・順番待ちをすぐに提供できます。</p>
                        <ul>
                            <li>短期間で導入しやすい</li>
                            <li>LINE通知・友だち導線と連携</li>
                            <li>アプリストア申請が不要</li>
                        </ul>
                    </div>
                    <a class="lp-plan__action lp-plan__action--line" href="/liff">LINE版UIを体験 <span aria-hidden="true">→</span></a>
                </article>

                <article class="lp-plan lp-plan--native">
                    <div class="lp-plan__index">02</div>
                    <div class="lp-plan__title">
                        <p>NATIVE PLAN</p>
                        <h3>ネイティブアプリ</h3>
                        <span>iOS / Android</span>
                    </div>
                    <div class="lp-plan__description">
                        <p>LINE版と同じ画面を、アプリストアから入れる専用アプリとして配布できます。中身はWeb画面で、プッシュ通知に対応します。</p>
                        <ul>
                            <li>App Store・Google Playで配布</li>
                            <li>プッシュ通知（FCM）に対応</li>
                            <li>画面・機能はLINE版と共通</li>
                        </ul>
                    </div>
                    <a class="lp-plan__action" href="#screens">共通UIを見る <span aria-hidden="true">→</span></a>
                </article>
            </div>

            <div class="lp-shared">
                <strong>両プラン共通</strong>
                <ul>
                    <li>管理画面</li>
                    <li>顧客・会員データ</li>
                    <li>注文・予約・順番待ち</li>
                    <li>ポイント・スタンプ・クーポン</li>
                    <li>店舗別のデザイン設定</li>
                </ul>
            </div>
        </section>

        <section id="features" class="lp-features" aria-labelledby="features-title">
            <div class="lp-container">
                <header class="lp-section-heading lp-section-heading--light">
                    <div>
                        <p class="lp-eyebrow">主な機能</p>
                        <h2 id="features-title">来店前から、<br>次の来店まで。</h2>
                    </div>
                    <p>機能を別々のサービスで増やすのではなく、顧客体験と店舗運営をひとつの流れにまとめます。</p>
                </header>

                <div class="lp-feature-list">
                    <article class="lp-feature">
                        <div class="lp-feature__visual">
                            <img src="{{ asset('images/landing/lp-order-v2.webp') }}" alt="モバイルオーダーで商品を受け取るお客様" width="1536" height="1024" loading="lazy">
                        </div>
                        <div class="lp-feature__copy">
                            <p class="lp-feature__number">01</p>
                            <h3>モバイルオーダー</h3>
                            <p>メニュー閲覧から注文、受け取りまでアプリ内で完結。ピーク時のレジ待ちを減らします。</p>
                            <dl><div><dt>お客様</dt><dd>好きな場所から事前注文</dd></div><div><dt>店舗</dt><dd>注文状況と商品を一元管理</dd></div></dl>
                        </div>
                    </article>

                    <article class="lp-feature lp-feature--reverse">
                        <div class="lp-feature__visual">
                            <img src="{{ asset('images/landing/lp-membership-v2.webp') }}" alt="店舗でデジタル会員証を利用するお客様" width="1536" height="1024" loading="lazy">
                        </div>
                        <div class="lp-feature__copy">
                            <p class="lp-feature__number">02</p>
                            <h3>会員証・ポイント・スタンプ</h3>
                            <p>デジタル会員証、ポイント、スタンプ、クーポンをひとつに。来店履歴を次の接点につなげます。</p>
                            <dl><div><dt>お客様</dt><dd>会員情報と特典をすぐ確認</dd></div><div><dt>店舗</dt><dd>会員情報と利用履歴を把握</dd></div></dl>
                        </div>
                    </article>

                    <article class="lp-feature">
                        <div class="lp-feature__visual">
                            <img src="{{ asset('images/landing/lp-queue-v2.webp') }}" alt="タブレットで順番待ちを管理する店舗スタッフ" width="1731" height="909" loading="lazy">
                        </div>
                        <div class="lp-feature__copy">
                            <p class="lp-feature__number">03</p>
                            <h3>予約・順番待ち</h3>
                            <p>受付から呼び出しまでオンラインで管理。お客様は店頭を離れて待つことができます。</p>
                            <dl><div><dt>お客様</dt><dd>待ち組数と呼び出しを確認</dd></div><div><dt>店舗</dt><dd>予約枠と来店状況を管理</dd></div></dl>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="screens" class="lp-container lp-section lp-screens" aria-labelledby="screens-title">
            <header class="lp-section-heading">
                <div>
                    <p class="lp-eyebrow">実際のアプリ画面</p>
                    <h2 id="screens-title">提供先が変わっても、<br>迷わない共通UI。</h2>
                </div>
                <p>LINEプランとネイティブプランは同じ機能基盤を使います。以下はデモ店舗で動作している画面です。ホームから注文、会員証、ポイント、クーポン、順番待ちまでつながります。</p>
            </header>
            <p class="lp-screens__labels" aria-label="対応プラン"><span>LINE PLAN</span><i></i><span>NATIVE PLAN（同じ画面）</span></p>
            <div class="lp-screens__grid">
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-home.webp') }}?v=5" alt="デモ店舗のホーム画面" width="390" height="844" loading="lazy"></div><figcaption><span>01</span>ホーム</figcaption></figure>
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-order.webp') }}?v=5" alt="デモ店舗のモバイルオーダー画面" width="390" height="844" loading="lazy"></div><figcaption><span>02</span>注文</figcaption></figure>
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-member.webp') }}?v=5" alt="デモ店舗の会員証画面" width="390" height="844" loading="lazy"></div><figcaption><span>03</span>会員証</figcaption></figure>
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-point.webp') }}?v=5" alt="デモ店舗のポイント画面" width="390" height="844" loading="lazy"></div><figcaption><span>04</span>ポイント</figcaption></figure>
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-coupon.webp') }}?v=5" alt="デモ店舗のクーポン画面" width="390" height="844" loading="lazy"></div><figcaption><span>05</span>クーポン</figcaption></figure>
                <figure class="lp-app-screen"><div class="lp-phone lp-phone--gallery"><img src="{{ asset('images/landing/app/actual-queue.webp') }}?v=5" alt="デモ店舗の順番待ち画面" width="390" height="844" loading="lazy"></div><figcaption><span>06</span>順番待ち</figcaption></figure>
            </div>
        </section>

        <section id="admin" class="lp-admin" aria-labelledby="admin-title">
            <div class="lp-container">
                <header class="lp-section-heading">
                    <div>
                        <p class="lp-eyebrow">共通の店舗管理画面</p>
                        <h2 id="admin-title">すべての運用を、<br>ひとつの画面から。</h2>
                    </div>
                    <p>LINEとネイティブ、どちらの利用状況も同じ管理画面に集約。注文、予約、順番待ち、会員、クーポンを確認できます。</p>
                </header>
                <figure class="lp-laptop">
                    <div class="lp-laptop__screen">
                        <img src="{{ asset('images/landing/app/actual-admin.webp') }}?v=2" alt="現在動作しているReggit店舗管理ダッシュボード。注文、予約、順番待ち、売上、会員数を表示" width="1512" height="982" loading="lazy">
                    </div>
                    <div class="lp-laptop__base" aria-hidden="true"><span></span></div>
                    <figcaption><strong>MacBook 14インチ想定</strong><span>16:10ディスプレイ比率</span><span>デモ店舗の実際の管理画面</span></figcaption>
                </figure>
            </div>
        </section>

        <section class="lp-container lp-integrations" aria-labelledby="integrations-title">
            <div>
                <p class="lp-eyebrow">対応プラットフォーム</p>
                <h2 id="integrations-title">お客様の入口から、<br>店舗の会計まで。</h2>
                <p>LINE、アプリストア、スマレジとの連携を前提に、運用を分断しない構成です。</p>
            </div>
            <ul aria-label="対応サービス">
                <li><img src="{{ asset('images/landing/partners/line.png') }}?v=4" alt="LINE" width="72" height="72" loading="lazy"></li>
                <li><img src="{{ asset('images/landing/partners/apple.png') }}?v=4" alt="App Store" height="54" loading="lazy"></li>
                <li><img src="{{ asset('images/landing/partners/android.png') }}?v=4" alt="Google Play" height="54" loading="lazy"></li>
                <li><img src="{{ asset('images/landing/partners/smaregi.png') }}?v=4" alt="スマレジ" height="54" loading="lazy"></li>
            </ul>
        </section>

        <section id="faq" class="lp-container lp-section lp-faq" aria-labelledby="faq-title">
            <header class="lp-section-heading lp-section-heading--compact"><div><p class="lp-eyebrow">よくあるご質問</p><h2 id="faq-title">導入前に知っておきたいこと。</h2></div></header>
            <div class="lp-faq__list">
                <details><summary><span>01</span>LINEプランとネイティブプランの違いは何ですか？</summary><p>LINEプランはダウンロード不要で、友だち追加後すぐに使えます。ネイティブプランは同じ画面をiOS・Androidの専用アプリ（WebView）として配布し、アプリストア掲載とプッシュ通知に対応します。</p></details>
                <details><summary><span>02</span>プランごとに管理画面は変わりますか？</summary><p>管理画面と基本の運用フローは共通です。会員・注文・予約・順番待ち・スタンプ・クーポンを同じ画面から確認できます。</p></details>
                <details><summary><span>03</span>複数店舗でも利用できますか？</summary><p>はい。店舗ごとに権限や設定を分けながら、同じ運用品質で管理できます。</p></details>
                <details><summary><span>04</span>LINEからネイティブへ展開できますか？</summary><p>同じ管理基盤を利用するため、既存の店舗運用や顧客データを活かしながら提供形態を広げられます。具体的な移行範囲は導入時に設計します。</p></details>
            </div>
        </section>

        <section id="register" class="lp-container lp-register" aria-labelledby="register-title">
            <div class="lp-register__copy">
                <p class="lp-eyebrow">導入登録</p>
                <h2 id="register-title">まずはメールアドレスから。</h2>
                <p>認証メールをお送りします。認証が完了すると入力フォームのURLをご案内し、送信後すぐに管理画面と店舗アプリ（LIFF）のURLをお渡しします。システム管理者の操作は不要です。</p>
            </div>
            <form class="lp-register__form" method="POST" action="{{ route('landing.register.email') }}">
                @csrf
                @if (session('status'))
                    <p class="lp-alert lp-alert--success" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->has('email'))
                    <p class="lp-alert lp-alert--error" role="alert">{{ $errors->first('email') }}</p>
                @endif
                <label class="lp-field">
                    <span>メールアドレス</span>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="you@example.com">
                </label>
                <button type="submit" class="lp-button lp-button--primary">認証メールを送る</button>
            </form>
        </section>

        <section class="lp-container lp-cta">
            <div>
                <p class="lp-eyebrow">LINE PLAN / NATIVE PLAN</p>
                <h2>お店に合う形で、<br>アプリをはじめる。</h2>
            </div>
            <div>
                <p>まずは実際のLINE版UIと、両プラン共通の管理画面をお確かめください。</p>
                <div class="lp-cta__actions">
                    <a class="lp-button lp-button--light" href="/liff">LINE版UIを体験 <span aria-hidden="true">→</span></a>
                    <a class="lp-button lp-button--ghost" href="{{ route('admin.login') }}">管理画面を見る</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2" alt="" width="36" height="36"><span>Reggit</span></a>
            <p>店舗アプリを、LINEでも、専用アプリでも。</p>
            <p>&copy; {{ date('Y') }} Reggit</p>
        </div>
    </footer>
</body>
</html>
