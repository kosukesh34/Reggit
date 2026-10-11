<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4f0e8">
    <meta name="description" content="Reggitは、LINEミニアプリと店舗管理画面で会員証・注文・予約・順番待ちをまとめる店舗向けサービスです。メール認証後に店舗アプリを発行します。">
    <title>Reggit｜会員も、注文も、予約も。LINEの中で。</title>
    <link rel="icon" type="image/png" href="{{ asset('images/reggit-logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ asset('images/landing/v3/hero-cafe.webp') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-home.css') }}?v=13">
</head>
<body class="lp">
    <a class="lp-skip" href="#main">本文へ移動</a>
    <header class="lp-header">
        <div class="lp-container lp-header__inner">
            <a class="lp-brand" href="/" aria-label="Reggit トップへ">
                <img src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="64" height="64">
            </a>
            <nav class="lp-nav" aria-label="メインナビゲーション">
                <a href="#features">機能</a>
                <a href="#scenes">利用シーン</a>
                <a href="#start">導入の流れ</a>
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
                    <a href="#scenes">利用シーン</a>
                    <a href="#start">導入の流れ</a>
                    <a href="#faq">よくある質問</a>
                    <a href="{{ route('admin.login') }}">管理画面ログイン</a>
                    <a href="#register">導入登録を始める</a>
                    <a href="{{ route('demo.liff') }}">アプリのデモ</a>
                </nav>
            </details>
        </div>
    </header>

    <main id="main">
        <section class="lp-hero" aria-labelledby="hero-title">
            <div class="lp-container lp-hero__grid">
                <div class="lp-hero__copy">
                    <p class="lp-kicker">店舗向け LINEミニアプリ</p>
                    <h1 id="hero-title">会員も、注文も、予約も。<br>LINEの中で。</h1>
                    <p>お客様はアプリをインストールせずに、会員証・モバイルオーダー・予約・順番待ちを使えます。店舗は同じ管理画面で、会員と受付を扱えます。</p>
                    <div class="lp-hero__actions">
                        <a class="lp-button" href="#register">導入登録を始める</a>
                        <a class="lp-link" href="{{ route('demo.liff') }}">アプリのデモを見る</a>
                    </div>
                </div>
                <div class="lp-hero__stage">
                    <img src="{{ asset('images/landing/v3/hero-cafe.webp') }}" alt="カフェのカウンターでスマートフォンを見る人" width="1280" height="720" fetchpriority="high">
                    <figure class="lp-device">
                        <img src="{{ asset('images/landing/shots/home.webp') }}" alt="デモ店舗のホーム画面。会員バーコードと、会員証・注文・クーポンなどのメニュー" width="390" height="844">
                    </figure>
                </div>
            </div>
            <p class="lp-container lp-hero__note">写真は利用イメージです。画面はデモ店舗の実際の表示で、店舗の設定により異なります。</p>
        </section>

        <section class="lp-promises" aria-label="Reggitの要点">
            <ul class="lp-container lp-promises__list">
                <li><strong>インストール不要</strong><span>LINEミニアプリから開けます</span></li>
                <li><strong>来店の前後</strong><span>会員・注文・予約・順番待ち</span></li>
                <li><strong>管理はひとつ</strong><span>会員と受付を同じ画面で</span></li>
                <li><strong>連携は任意</strong><span>スマレジ、決済、Wallet</span></li>
            </ul>
        </section>

        <section class="lp-problems" aria-labelledby="problems-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">分かれがちな業務</p>
                    <h2 id="problems-title">会員証、注文、予約が、別のサービスになりやすい。</h2>
                </div>
                <ol class="lp-problems__list">
                    <li>
                        <span>01</span>
                        <strong>アプリを入れてもらえない</strong>
                        <p>専用アプリのダウンロードが、初回の会員登録で止まりやすい。LINEミニアプリなら、その場で開けます。</p>
                    </li>
                    <li>
                        <span>02</span>
                        <strong>受付が画面ごとに分かれる</strong>
                        <p>注文、予約、順番待ちを別々に見ていると、今だれを待っているかが追いにくい。</p>
                    </li>
                    <li>
                        <span>03</span>
                        <strong>来店のあとが続かない</strong>
                        <p>ポイント、スタンプ、クーポンが紙や別アプリにあると、次の来店の理由が残りにくい。</p>
                    </li>
                </ol>
            </div>
        </section>

        <section id="features" class="lp-features" aria-labelledby="features-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">主な機能</p>
                    <h2 id="features-title">来店前から、次の来店まで。</h2>
                    <p>お客様が使う入口と、店舗が扱う情報を、同じサービスの中でつなぎます。</p>
                </div>

                <article class="lp-feature" aria-labelledby="feature-member">
                    <div class="lp-feature__copy">
                        <p class="lp-feature__label">会員証・特典</p>
                        <h3 id="feature-member">会員証と特典を、ひとつに。</h3>
                        <p>会員証、ポイント、スタンプ、クーポンをアプリにまとめます。お客様は保有する特典を確認し、店頭で提示できます。Apple / Google Wallet への保存にも対応します。</p>
                    </div>
                    <figure class="lp-feature__photo">
                        <img src="{{ asset('images/landing/v3/scene-member.webp') }}" alt="店頭でスマートフォンを提示する様子" width="1152" height="864" loading="lazy">
                    </figure>
                    <figure class="lp-phone">
                        <img src="{{ asset('images/landing/shots/member.webp') }}" alt="デモ店舗の会員証画面。ランク、ポイント、バーコード、クーポン" width="390" height="844" loading="lazy">
                    </figure>
                </article>

                <article class="lp-feature lp-feature--flip" aria-labelledby="feature-order">
                    <div class="lp-feature__copy">
                        <p class="lp-feature__label">モバイルオーダー</p>
                        <h3 id="feature-order">注文を、受け付けやすく。</h3>
                        <p>お客様は商品を選び、スマートフォンから注文できます。店舗では注文内容と対応状況を管理画面で確認できます。</p>
                    </div>
                    <figure class="lp-feature__photo">
                        <img src="{{ asset('images/landing/v3/scene-order.webp') }}" alt="カウンターでコーヒーを淹れる様子" width="1152" height="864" loading="lazy">
                    </figure>
                    <figure class="lp-phone">
                        <img src="{{ asset('images/landing/shots/order.webp') }}" alt="デモ店舗の注文画面。商品の検索、カテゴリ、価格" width="390" height="844" loading="lazy">
                    </figure>
                </article>

                <div class="lp-split">
                    <article class="lp-split__card" aria-labelledby="feature-queue">
                        <figure class="lp-split__visual">
                            <img src="{{ asset('images/landing/v3/scene-queue.webp') }}" alt="店の前で順番を待つ人" width="1152" height="864" loading="lazy">
                            <img class="lp-split__phone" src="{{ asset('images/landing/shots/queue.webp') }}" alt="デモ店舗の順番待ち画面。待ち組数と整理券の発行" width="390" height="844" loading="lazy">
                        </figure>
                        <div>
                            <p class="lp-feature__label">順番待ち</p>
                            <h3 id="feature-queue">待ち状況を、手元に。</h3>
                            <p>受付番号と現在の状況をスマートフォンで確認できます。呼び出しが近づいたことも知らせられます。</p>
                        </div>
                    </article>
                    <article class="lp-split__card" aria-labelledby="feature-reserve">
                        <figure class="lp-split__visual">
                            <img src="{{ asset('images/landing/v3/scene-reserve.webp') }}" alt="予約された二人分の食卓" width="1152" height="864" loading="lazy">
                            <img class="lp-split__phone" src="{{ asset('images/landing/shots/reserve.webp') }}" alt="デモ店舗の予約画面。確定した来店日時" width="390" height="844" loading="lazy">
                        </figure>
                        <div>
                            <p class="lp-feature__label">予約</p>
                            <h3 id="feature-reserve">予約も、同じアプリから。</h3>
                            <p>来店予約を会員の動線に置けます。受付状況は、注文や順番待ちとあわせて管理画面で扱えます。</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section id="screens" class="lp-deskwrap" aria-labelledby="desk-title">
            <div class="lp-container lp-deskwrap__grid">
                <div>
                    <p class="lp-kicker">店舗の管理画面</p>
                    <h2 id="desk-title">今の受付が、一枚で見える。</h2>
                    <p>注文、予約、順番待ち、会員。別のツールを開かずに、今日の対応を確認できます。</p>
                </div>
                <figure class="lp-admin">
                    <img src="{{ asset('images/landing/shots/admin.webp') }}" alt="デモ店舗の管理画面。未処理の注文、本日の予約、順番待ち" width="1440" height="900" loading="lazy">
                </figure>
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
                    <figure>
                        <img src="{{ asset('images/landing/v3/hero-cafe.webp') }}" alt="明るいカフェのカウンター" width="1280" height="720" loading="lazy">
                        <figcaption><strong>カフェ</strong><span>注文・会員証</span></figcaption>
                    </figure>
                    <figure>
                        <img src="{{ asset('images/landing/v3/scene-retail.webp') }}" alt="雑貨を並べた物販店の店内" width="1280" height="720" loading="lazy">
                        <figcaption><strong>物販店</strong><span>会員証・ポイント</span></figcaption>
                    </figure>
                    <figure>
                        <img src="{{ asset('images/landing/v3/scene-reserve.webp') }}" alt="食事の席が整えられた店内" width="1152" height="864" loading="lazy">
                        <figcaption><strong>飲食店</strong><span>予約・順番待ち</span></figcaption>
                    </figure>
                </div>
                <p class="lp-scenes__note">写真は利用シーンのイメージです。</p>
            </div>
        </section>

        <section class="lp-integrations" aria-labelledby="integrations-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">入口と連携</p>
                    <h2 id="integrations-title">LINEから始めて、必要なら専用アプリへ。</h2>
                </div>
                <ul class="lp-integrations__list">
                    <li><strong>LINEミニアプリ</strong><p>インストールせずに、LINEから利用できます。</p></li>
                    <li><strong>iOS / Android</strong><p>店舗・ブランドに合わせた専用アプリにも対応します。</p></li>
                    <li><strong>スマレジ連携</strong><p>会員や購買情報を、利用する機能に応じて接続できます。必須ではありません。</p></li>
                    <li><strong>Apple / Google Wallet</strong><p>会員証をウォレットに保存して、店頭で提示できます。</p></li>
                </ul>
            </div>
        </section>

        <section id="start" class="lp-start" aria-labelledby="start-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">導入の流れ</p>
                    <h2 id="start-title">メール認証のあと、店舗アプリを発行します。</h2>
                    <p>登録時点では契約は発生しません。発行後すぐに、管理画面とお客様向けアプリのURLをお渡しします。</p>
                </div>
                <ol class="lp-start__list">
                    <li>
                        <span>1</span>
                        <strong>メールアドレスを認証</strong>
                        <p>認証メールのリンクを開くと、店舗情報の入力へ進みます。</p>
                    </li>
                    <li>
                        <span>2</span>
                        <strong>店舗情報を入力</strong>
                        <p>店舗名、担当者、電話番号、希望プランを入力します。</p>
                    </li>
                    <li>
                        <span>3</span>
                        <strong>アプリと管理画面を受け取る</strong>
                        <p>LINEミニアプリのURLと、管理画面のログイン情報を発行します。</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="lp-compare" aria-labelledby="compare-title">
            <div class="lp-container">
                <div class="lp-section-heading">
                    <p class="lp-kicker">機能の範囲</p>
                    <h2 id="compare-title">会員・注文・予約を、同じサービスで扱う。</h2>
                    <p>アプリ作成、予約、順番待ちを別々に契約している店舗向けの範囲です。各サービスの対応範囲はプランにより異なります。</p>
                </div>
                <div class="lp-compare__table-wrap">
                    <table class="lp-compare__table">
                        <caption class="lp-visually-hidden">Reggitと、専用アプリ作成・予約順番待ちサービスの機能範囲</caption>
                        <thead>
                            <tr>
                                <th scope="col">機能</th>
                                <th scope="col">専用アプリ作成</th>
                                <th scope="col">予約・順番待ち</th>
                                <th scope="col">Reggit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="row">デジタル会員証・ポイント</th>
                                <td>対応することが多い</td>
                                <td>対象外が多い</td>
                                <td>対応</td>
                            </tr>
                            <tr>
                                <th scope="row">モバイルオーダー</th>
                                <td>プランによる</td>
                                <td>対象外が多い</td>
                                <td>対応</td>
                            </tr>
                            <tr>
                                <th scope="row">予約・順番待ち</th>
                                <td>対象外が多い</td>
                                <td>対応</td>
                                <td>対応</td>
                            </tr>
                            <tr>
                                <th scope="row">LINEから利用</th>
                                <td>別途連携が多い</td>
                                <td>プランによる</td>
                                <td>標準</td>
                            </tr>
                            <tr>
                                <th scope="row">店舗の管理画面</th>
                                <td>アプリ管理が中心</td>
                                <td>受付管理が中心</td>
                                <td>会員・注文・受付をまとめて扱う</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="lp-options" aria-labelledby="options-title">
            <div class="lp-container lp-options__grid">
                <div>
                    <p class="lp-kicker">希望プラン</p>
                    <h2 id="options-title">まずは LINE。専用アプリが必要ならネイティブ。</h2>
                </div>
                <div class="lp-options__list">
                    <div><strong>LINEプラン</strong><p>LINEミニアプリで会員証・注文・予約・順番待ちを提供します。</p></div>
                    <div><strong>ネイティブプラン</strong><p>LINEに加えて、iOS / Android の専用アプリも利用します。</p></div>
                    <div><strong>料金</strong><p>登録時点では請求しません。規模と機能を確認したあと、ご案内します。</p></div>
                </div>
            </div>
        </section>

        <section id="faq" class="lp-faq" aria-labelledby="faq-title">
            <div class="lp-container lp-faq__grid">
                <div>
                    <p class="lp-kicker">よくある質問</p>
                    <h2 id="faq-title">導入前に確認したいこと。</h2>
                </div>
                <div class="lp-faq__items">
                    <details>
                        <summary>飲食店以外でも使えますか？<span aria-hidden="true"></span></summary>
                        <p>はい。会員証、ポイント、スタンプ、クーポンは物販店などでも利用できます。注文・予約・順番待ちは必要な機能を選べます。</p>
                    </details>
                    <details>
                        <summary>お客様はアプリのインストールが必要ですか？<span aria-hidden="true"></span></summary>
                        <p>LINEミニアプリはインストール不要です。店舗の運用に合わせて、iOS・Androidの専用アプリにも対応しています。</p>
                    </details>
                    <details>
                        <summary>スマレジとの連携は必須ですか？<span aria-hidden="true"></span></summary>
                        <p>必須ではありません。利用する機能や店舗の運用に合わせて設定できます。</p>
                    </details>
                    <details>
                        <summary>導入までどのくらいかかりますか？<span aria-hidden="true"></span></summary>
                        <p>メールアドレスの認証と店舗情報の入力が終わると、その場で店舗アプリのURLと管理画面のログイン情報を発行します。LINE公式アカウントの審査やスマレジ接続は、必要な場合にそのあと設定します。</p>
                    </details>
                    <details>
                        <summary>導入登録後、すぐに契約が発生しますか？<span aria-hidden="true"></span></summary>
                        <p>メールアドレスの登録と店舗情報の入力だけで、すぐに契約が発生することはありません。</p>
                    </details>
                </div>
            </div>
        </section>

        <section id="register" class="lp-register" aria-labelledby="register-title">
            <div class="lp-container lp-register__grid">
                <div class="lp-register__copy">
                    <p class="lp-kicker">導入登録</p>
                    <h2 id="register-title">店舗に合った使い方を、<br>ここから。</h2>
                    <p>メールアドレスを入力してください。認証リンクを開いたあと、店舗情報を入力するとアプリと管理画面を発行します。</p>
                    <ol class="lp-register__steps">
                        <li>メールアドレスを認証する</li>
                        <li>店舗情報を入力する</li>
                        <li>アプリと管理画面を受け取る</li>
                    </ol>
                </div>
                <div class="lp-register__form">
                    <h3>導入登録を始める</h3>
                    <p>認証メールを送ります。届いたリンクから店舗情報の入力へ進みます。</p>
                    <form method="POST" action="{{ route('landing.register.email') }}">
                        @csrf
                        <label class="lp-field" for="landing-email">
                            <span>メールアドレス</span>
                            <input id="landing-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" placeholder="name@company.jp" inputmode="email" spellcheck="false">
                        </label>
                        <button class="lp-button" type="submit">メール認証へ進む</button>
                    </form>
                    <small>登録後すぐに契約が発生することはありません。<a href="{{ route('landing.register.email.show') }}">認証ページを開く</a></small>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer">
        <div class="lp-container lp-footer__main">
            <div>
                <a href="/" aria-label="Reggit トップへ"><img src="{{ asset('images/reggit-logo.png') }}" alt="Reggit" width="72" height="72"></a>
                <p>店舗向けLINEミニアプリ・管理システム</p>
            </div>
            <nav aria-label="フッターナビゲーション">
                <a href="#features">機能</a>
                <a href="#screens">管理画面</a>
                <a href="#start">導入の流れ</a>
                <a href="{{ route('demo.liff') }}">アプリのデモ</a>
                <a href="{{ route('landing.register.email.show') }}">導入登録</a>
                <a href="{{ route('admin.login') }}">管理画面ログイン</a>
            </nav>
        </div>
        <div class="lp-container lp-footer__bottom"><span>&copy; {{ date('Y') }} Reggit</span></div>
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
