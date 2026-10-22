# プロジェクト別決済・認証キーの管理

2026-10-07。対象は Skip・LINE/LIFF のモバイル注文。

## 対応方式

お客様向けの選択画面はクレジットカード・PayPay・Apple Pay・現金の4種類。プロジェクトで無効なものは選択不可。店内注文は既存の後払いを維持する。

オンライン決済は Stripe 経由。プロジェクトごとに独立した Stripe アカウントを登録し、そのアカウントへ売上が入金される。現時点で PayPay は Stripe Connect 非対応なので、Connect を前提にしない。

Skip・LINE は Stripe hosted Checkout を外部ブラウザーで開く。PayPay 選択時は PayPay に限定した Checkout を作成する。カード・Apple Pay は Stripe の card 決済を使用する。Apple Pay の表示は端末・ブラウザー・Wallet 設定に依存し、Checkout はカード入力も提供する。ネイティブ Apple Pay シートの実装は含まない。Android の Skip では Apple Pay タイルを選択不可にする。

## 設定手順

1. 管理画面「店舗設定 → 決済・入金先」を開く。
2. 対象プロジェクトの Stripe 公開キー・秘密キー・Webhook 署名キーを登録する。Stripe API でアカウント ID を確認し、接続アカウントを表示する。
3. 利用する4種類の方法を選ぶ。PayPay は対象 Stripe アカウントのダッシュボードでも利用申請・有効化する。
4. 保存後に表示されるプロジェクト・設定バージョン専用の Webhook URL を Stripe に登録する。イベントは `payment_intent.succeeded`。サブスクを販売する場合は `invoice.paid`・`customer.subscription.updated`・`customer.subscription.deleted` も登録する。
5. Webhook 署名キーは対象エンドポイントの値を使う。キーを後から登録・変更した場合、保存後の URL へ同じ Stripe エンドポイントの URL を更新する。
6. 設定変更前の未決済注文が残っている間は、旧バージョンの URL とアカウント認証情報も使用可能な状態に保つ。各バージョンに別エンドポイントを用意すると、旧注文の完了通知も受け取れる。

設定未登録のプロジェクトは現金のみ。既存のデフォルトプロジェクトに限り、環境変数によるカード設定を互換用に維持する。別プロジェクトへの暗黙の共通キー適用は行わない。

## 認証キーの保持先

`project_payment_settings` テーブルにプロジェクトごとのバージョンを保存する。秘密キー・Webhook 署名キーは Laravel encrypted cast で暗号化し、モデルの公開シリアライズから除外する。管理画面には秘密情報の登録有無だけを表示し、空欄で更新した際は以前の値を維持する。バリデーションエラーのセッション再表示にも秘密項目を含めない。監査ログは既存の許可された項目だけを記録する。

Stripe 公開キーだけはカード決済のクライアントへ渡せる。秘密情報はサーバーでのみ使用する。

DB の暗号化に使う APP_KEY は DB と分離し、本番環境の秘密管理基盤で管理する。APP_KEY を失うと復号できないため、バックアップと鍵更新の手順が必要。Secret Manager を使用する本番構成へ移行する場合は、DB の秘密値を参照 ID に置き換えて Resolver で取得する構成にできる。

## 注文と認証情報の分離

注文には `payment_setting_id` を保存する。決済設定を変更しても、進行中の注文は元の Stripe アカウント・設定を使用する。StripeClient はインスタンスごとにキーを持ち、SDK のグローバルキーを切り替えない。

注文の `payment_method` は既存の業務処理との互換のためオンライン決済を `stripe` として保持する。顧客が選んだブランドは `payment_brand`、Stripe API の決済確認で取得した実際のブランドは `paid_payment_brand` に保持する。

決済の確認は注文所有者・プロジェクト・設定 ID・金額・通貨を照合する。プロジェクト別 Webhook は対象の署名キーで検証する。画面の復帰パラメーターだけでは注文を支払い済みにしない。決済確認処理と既存の特典付与処理を共用する。

## 手数料・追加ブランド

日本向け公開標準料金（2026-10-07 確認）：カード 3.6%、Apple Pay はカード料金、PayPay 3.98%。PayPay のデジタルコンテンツ事業向け料金は9.48%。実際の料金は各アカウントの契約による。PayPay の金額範囲は50〜1,000,000 JPY。

d払い・au PAY・楽天ペイ・メルペイ・AEON Pay は2026-09-30に Stripe が先行提供を発表した。各アカウントの承認・料金・API仕様の確認が必要。今回の4種類の選択画面には追加していない。

## 適用・検証

追加マイグレーション：`database/migrations/2026_10_07_140000_add_project_payment_settings.php`。

本番のキー登録・PayPay 有効化・Webhook 登録と、対応端末の Apple Pay、PayPay アプリへの遷移・復帰を実機で確認する必要がある。ローカルの自動テストは実際の決済サービスへの接続・課金を行わない。

## 公式資料

- https://stripe.com/jp/pricing
- https://docs.stripe.com/payments/paypay
- https://docs.stripe.com/apple-pay?platform=web
- https://stripe.com/jp/newsroom/news/stripe-marks-10-years-in-japan-with-new-products-to-accelerate-the-growth-of-digital-businesses

## Google PayとOS別の表示

Google Payもプロジェクト設定で有効・無効を選択する。LINE側はiOS/iPadOS/macOSでApple Pay、Android/その他OSではGoogle Pay、Skip側はiOSでApple Pay、AndroidでGoogle Payを表示し、反対側のウォレットを非表示にする。カード・PayPay・現金は共通。

Google PayはApple Payと同じくStripe Checkoutのcard決済として作成し、注文にはgoogle_pay、Stripeが返す実決済ブランドにもgoogle_payを記録する。Stripeが端末・ブラウザー・保存カード・アカウント設定に応じて利用可否を判定するため、OSによる選択肢表示だけでは利用を保証しない。LINE内では既存の外部ブラウザー遷移を使う。Google Payの有効化と実機決済確認はプロジェクトのStripeアカウントごとに必要。既存設定を自動的に有効化せず、管理画面の決済設定でGoogle Payを選んで保存する。

公式資料: https://stripe.com/payments/checkout
