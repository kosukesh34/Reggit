<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reggit — 来店の便利を、次の来店へ。</title>
    <meta name="description" content="Reggitは、会員証・ポイント・クーポン・注文・予約・順番待ちをスマレジの会員・購買情報とつなぐ店舗向けサービスです。">
    <meta name="theme-color" content="#173f35">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500;600;700&family=Noto+Sans+JP:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v=61">
</head>
<body class="lp">
    <header class="lp-header">
        <div class="lp-shell lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2" alt="" width="40" height="40">
                <span>Reggit</span>
            </a>
            <nav class="lp-nav" aria-label="ページ内ナビゲーション">
                <a href="#cycle">仕組み</a>
                <a href="#experience">できること</a>
                <a href="#channels">提供方法</a>
                <a href="#faq">よくある質問</a>
            </nav>
            <div class="lp-header__actions">
                <a class="lp-login" href="{{ route('admin.login') }}">管理画面ログイン</a>
                <a class="lp-button lp-button--compact" href="#register">導入をはじめる</a>
            </div>
        </div>
    </header>

    <main>
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="lp-shell lp-hero__grid">
                <div class="lp-hero__copy">
                    <p class="lp-kicker"><span></span>店舗体験を、ひとつに</p>
                    <h1 id="hero-title">来店の便利を、<em>次の来店へ。</em></h1>
                    <p class="lp-hero__lead">会員証、注文、予約、順番待ち。お客様が今日使いたくなる体験と、スマレジの購買情報をひとつにつなぎます。</p>
                    <div class="lp-hero__actions">
                        <a class="lp-button" href="#register">無料で導入登録 <span aria-hidden="true">↗</span></a>
                        <a class="lp-button lp-button--quiet" href="/liff">デモを体験 <span aria-hidden="true">→</span></a>
                    </div>
                    <ul class="lp-hero__facts" aria-label="Reggitの特徴">
                        <li><strong>LINE</strong><span>インストール不要</span></li>
                        <li><strong>iOS / Android</strong><span>専用アプリにも対応</span></li>
                        <li><strong>スマレジ</strong><span>会員・購買情報を連携</span></li>
                    </ul>
                </div>

                <div class="lp-hero__stage" aria-label="店舗とReggitアプリの利用イメージ">
                    <div class="lp-hero__photo-wrap">
                        <img class="lp-hero__photo" src="{{ asset('images/landing/editorial/store-hero.webp') }}" alt="カフェでタブレットを操作するスタッフとスマートフォンを利用するお客様" width="1536" height="1024" fetchpriority="high">
                        <span class="lp-hero__caption">お店にも、お客様にも、自然なデジタル体験を。</span>
                    </div>
                    <div class="lp-device lp-device--hero">
                        <div class="lp-device__bar"><span></span></div>
                        <img src="{{ asset('images/landing/app/actual-home.webp') }}?v=6" alt="Reggitの店舗アプリホーム画面" width="390" height="844">
                    </div>
                </div>
            </div>
        </section>

        @php
            $logoBrandFiles = ['02', '06', '08', '12', '15', '18', '20', '24', '26', '28', '30', '32', '34'];
        @endphp
        <section class="lp-marquee" aria-label="さまざまな店舗ブランドに対応">
            <p>ひとつの仕組みを、あらゆるお店の顔に。</p>
            <div class="lp-marquee__viewport" aria-hidden="true">
                <div class="lp-marquee__track">
                    @foreach ([1, 2] as $copy)
                        <ul>
                            @foreach ($logoBrandFiles as $file)
                                <li><img src="{{ asset('images/landing/logos/brand-'.$file.'.svg') }}" alt="" height="42"></li>
                            @endforeach
                        </ul>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="cycle" class="lp-cycle">
            <div class="lp-shell">
                <header class="lp-heading lp-heading--split">
                    <div>
                        <p class="lp-kicker"><span></span>Reggitの仕組み</p>
                        <h2>会員を集めて終わらない。再来店まで、循環させる。</h2>
                    </div>
                    <p>店舗で役立つ機能を入口に、会員と購買の情報をつなぎます。難しい分析や大量配信に頼らず、次の来店につながる接点をつくります。</p>
                </header>

                <ol class="lp-loop">
                    <li><span>01</span><strong>便利な来店体験</strong><small>注文・予約・順番待ち</small></li>
                    <li><span>02</span><strong>自然な会員化</strong><small>LINE・専用アプリ</small></li>
                    <li><span>03</span><strong>購買情報を蓄積</strong><small>スマレジと連携</small></li>
                    <li><span>04</span><strong>適切な特典</strong><small>ポイント・クーポン</small></li>
                    <li class="lp-loop__last"><span>05</span><strong>次の来店へ</strong><small>休眠・誕生日・ランク</small></li>
                </ol>
            </div>
        </section>

        <section class="lp-problem">
            <div class="lp-shell lp-problem__grid">
                <div class="lp-problem__statement">
                    <p class="lp-kicker lp-kicker--light"><span></span>なぜ、Reggitなのか</p>
                    <h2>機能は増えた。でも、<em>顧客体験はつながっていない。</em></h2>
                    <figure class="lp-problem__photo">
                        <img src="{{ asset('images/landing/editorial/member-retail.webp') }}" alt="物販店でスマートフォンの会員証を提示するお客様" width="1536" height="1024" loading="lazy">
                        <figcaption>会員証を見せる。その小さな行動から、関係が続いていく。</figcaption>
                    </figure>
                </div>
                <div class="lp-problem__items">
                    <article><span>01</span><h3>情報が分かれている</h3><p>POS、会員証、注文、予約、順番待ち。サービスが増えるほど、顧客の行動が見えにくくなります。</p></article>
                    <article><span>02</span><h3>運用が続かない</h3><p>専任担当者がいない店舗に、複雑な分析や細かなキャンペーン設定を求めても定着しません。</p></article>
                    <article><span>03</span><h3>使う理由が弱い</h3><p>会員登録だけでは、お客様は戻ってきません。待ち時間や注文の手間を減らす、今日の価値が必要です。</p></article>
                </div>
            </div>
        </section>

        <section id="experience" class="lp-experience">
            <div class="lp-shell">
                <header class="lp-heading">
                    <p class="lp-kicker"><span></span>ひとつながりの体験</p>
                    <h2>お客様には迷わない体験を。店舗には増えない手間を。</h2>
                    <p>必要な機能だけを選びながら、データと運用はひとつにまとまります。</p>
                </header>

                <div class="lp-bento">
                    <article class="lp-bento__item lp-bento__item--order">
                        <img class="lp-bento__photo" src="{{ asset('images/landing/editorial/kitchen-team.webp') }}" alt="モバイル注文を確認しながら商品を準備する店舗スタッフ" width="1536" height="1024" loading="lazy">
                        <div class="lp-bento__copy"><span>モバイルオーダー</span><h3>レジに並ばず、好きな場所から注文。</h3><p>商品選びから注文状況の確認まで、スマートフォンの中で完結します。</p></div>
                        <div class="lp-device lp-device--card"><img src="{{ asset('images/landing/app/actual-order.webp') }}?v=6" alt="モバイルオーダー画面" width="390" height="844"></div>
                    </article>

                    <article class="lp-bento__item lp-bento__item--member">
                        <div class="lp-bento__copy"><span>会員証</span><h3>開けば、すぐ会員証。</h3><p>ポイント、ランク、特典を一画面に。</p></div>
                        <div class="lp-device lp-device--small"><img src="{{ asset('images/landing/app/actual-member.webp') }}?v=6" alt="デジタル会員証画面" width="390" height="844"></div>
                    </article>

                    <article class="lp-bento__item lp-bento__item--queue">
                        <img class="lp-bento__photo" src="{{ asset('images/landing/editorial/queue-family.webp') }}" alt="レストランの順番をスマートフォンで確認しながら待つ家族" width="1536" height="1024" loading="lazy">
                        <div class="lp-bento__copy"><span>順番待ち</span><h3>待つ時間を、自由な時間に。</h3><p>受付後はその場を離れても、順番と呼び出しを確認できます。</p></div>
                        <div class="lp-device lp-device--small"><img src="{{ asset('images/landing/app/actual-queue.webp') }}?v=6" alt="順番待ち画面" width="390" height="844"></div>
                    </article>

                    <article class="lp-bento__item lp-bento__item--rewards">
                        <div class="lp-bento__copy"><span>ポイントと特典</span><h3>使える特典が、ちゃんと伝わる。</h3><p>ポイント、スタンプ、クーポンを分かりやすく。送ることより、使われることを大切にします。</p></div>
                        <div class="lp-reward-stack" aria-label="ポイントとクーポンの画面">
                            <div class="lp-device"><img src="{{ asset('images/landing/app/actual-point.webp') }}?v=6" alt="ポイント画面" width="390" height="844"></div>
                            <div class="lp-device"><img src="{{ asset('images/landing/app/actual-coupon.webp') }}?v=6" alt="クーポン画面" width="390" height="844"></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="channels" class="lp-channels">
            <div class="lp-shell">
                <header class="lp-heading lp-heading--split">
                    <div><p class="lp-kicker"><span></span>選べる提供方法</p><h2>入口は選べる。運営はひとつ。</h2></div>
                    <p>お客様はLINEまたは専用アプリから。店舗はWeb管理画面とiPadから。チャネルが違っても、同じ店舗・会員情報でつながります。</p>
                </header>
                <div class="lp-channel-grid">
                    <article class="lp-channel lp-channel--line"><span>お客様向け</span><div><small>インストール不要</small><h3>LINEミニアプリ</h3><p>友だち追加から、すぐに使い始められます。</p></div></article>
                    <article class="lp-channel lp-channel--native"><span>お客様向け</span><div><small>店舗・ブランド専用</small><h3>iOS / Android</h3><p>ネイティブアプリとして、共通機能とプッシュ通知を提供します。</p></div></article>
                    <article class="lp-channel lp-channel--ipad"><span>店舗向け</span><div><small>店頭オペレーション</small><h3>Wait / Kitchen</h3><p>順番待ち受付と注文管理を、iPadアプリで支えます。</p></div></article>
                </div>
            </div>
        </section>

        <section class="lp-admin" aria-labelledby="admin-title">
            <div class="lp-shell">
                <div class="lp-admin__intro">
                    <p class="lp-kicker lp-kicker--light"><span></span>店舗管理</p>
                    <h2 id="admin-title">運営の現在地が、ひと目でわかる。</h2>
                    <p>注文、予約、順番待ち、会員、クーポン。お客様の入口がLINEでも専用アプリでも、店舗側の運用は一つの管理画面に集約されます。</p>
                    <ul><li>注文・予約・順番待ちの状況</li><li>会員情報と購買履歴</li><li>ポイント・スタンプ・クーポン</li><li>店舗ごとの機能と画面デザイン</li></ul>
                </div>
                <figure class="lp-admin__visual">
                    <div class="lp-browser">
                        <div class="lp-browser__bar"><i></i><i></i><i></i><span>reggit.jp / dashboard</span></div>
                        <img src="{{ asset('images/landing/app/actual-admin.webp') }}?v=3" alt="注文、予約、順番待ち、会員情報を表示するReggit管理画面" width="1512" height="982">
                    </div>
                    <figcaption><strong>MacBook 14インチ想定</strong><span>16:10ディスプレイ比率</span></figcaption>
                </figure>
            </div>
        </section>

        <section class="lp-values">
            <div class="lp-shell">
                <header class="lp-heading"><p class="lp-kicker"><span></span>実際の店舗で続けられること</p><h2>続けられることを、機能より先に考える。</h2></header>
                <div class="lp-values__grid">
                    <article><span>はじめやすさ</span><h3>すぐ使える</h3><p>会員証までの導線を短く。店舗の初期設定にも、分かりやすい初期値を用意します。</p></article>
                    <article><span>伝え方</span><h3>送りすぎない</h3><p>通知量ではなく、利用と再来店を重視。理由が分かる案内だけを基本にします。</p></article>
                    <article><span>運用設計</span><h3>必要なものだけ</h3><p>注文、予約、順番待ちなど、店舗に合わない機能を無理に使わせません。</p></article>
                    <article><span>データ管理</span><h3>安全につなぐ</h3><p>店舗ごとに情報を分離し、決済・ポイント・特典の重複を防ぎます。</p></article>
                </div>
            </div>
        </section>

        <section id="faq" class="lp-faq">
            <div class="lp-shell lp-faq__grid">
                <header class="lp-heading"><p class="lp-kicker"><span></span>よくある質問</p><h2>導入前に、気になること。</h2></header>
                <div class="lp-faq__list">
                    <details><summary><span>01</span>LINEと専用アプリ、どちらが必要ですか？<i></i></summary><p>店舗の運用に合わせて選べます。まずはインストール不要のLINEから始め、必要に応じてiOS・Androidの専用アプリへ広げることもできます。</p></details>
                    <details><summary><span>02</span>飲食店以外でも使えますか？<i></i></summary><p>はい。会員証、ポイント、スタンプ、クーポンは物販店でも利用できます。注文、予約、順番待ちは必要な店舗だけ有効にできます。</p></details>
                    <details><summary><span>03</span>スマレジとは何が連携されますか？<i></i></summary><p>会員、ポイント、商品、店舗、取引など、利用する機能に必要な情報を連携します。具体的な範囲は導入設定により異なります。</p></details>
                    <details><summary><span>04</span>複数店舗でも利用できますか？<i></i></summary><p>店舗ごとにデータと設定を分けて利用できます。多店舗向けの本部管理は、運用範囲に合わせて設計します。</p></details>
                </div>
            </div>
        </section>

        <section id="register" class="lp-register">
            <div class="lp-shell lp-register__card">
                <div class="lp-register__copy">
                    <p class="lp-kicker lp-kicker--light"><span></span>導入登録</p>
                    <h2>お客様の<span class="lp-no-break">「また来たい」を、</span>仕組みにしよう。</h2>
                    <p>まずはメールアドレスをご登録ください。認証後、店舗情報の入力フォームをご案内します。</p>
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
                        <span>導入登録</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="メールアドレスを入力">
                    </label>
                    <button type="submit" class="lp-button lp-button--light">認証メールを送る <span aria-hidden="true">↗</span></button>
                    <small>登録後すぐに契約が発生することはありません。</small>
                </form>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-shell lp-footer__inner">
            <a class="lp-brand" href="/"><img src="{{ asset('images/landing/logos/reggit-app-icon.png') }}?v=2" alt="" width="40" height="40"><span>Reggit</span></a>
            <p>来店の便利を、次の来店へ。</p>
            <div><a href="/liff">デモを体験</a><a href="{{ route('admin.login') }}">管理画面ログイン</a><span>&copy; {{ date('Y') }} Reggit</span></div>
        </div>
    </footer>
</body>
</html>
