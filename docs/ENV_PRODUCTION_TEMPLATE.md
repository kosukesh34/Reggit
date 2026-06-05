# 本番環境用の環境変数テンプレート

このファイルは **Elastic Beanstalk の環境プロパティ** や、手元で管理する `.env` のメモ用です。  
**実際のパスワードやシークレットが含まれる場合はこのファイルをコミットしないでください。**（必要なら `docs/ENV_PRODUCTION_TEMPLATE.md` を .gitignore に追加）

- **ローカル用**: プロジェクト直下の `.env` で管理（このファイルでは変更しない）
- **AWS（本番）用**: 以下および EB の環境プロパティで管理

---

## ベースURL・エンドポイント（ローカル vs AWS）

| 用途 | ローカル用（.env） | AWS・本番用（EB 環境プロパティ） |
|------|-------------------|----------------------------------|
| **APP_URL** | `http://localhost:8080` | `https://reggit.shop`（独自ドメイン運用時） |
| **アプリURL**（スマレジ画面） | `http://localhost:8080` | `https://reggit.shop` |
| **利用者契約通知先URL** | `http://localhost:8080/api/smaregi/idp` | `https://reggit.shop/api/smaregi/idp` |
| **Webhook送信先エンドポイント** | `http://localhost:8080/api/smaregi/webhook` | `https://reggit.shop/api/smaregi/webhook` |
| **リダイレクトURI** | `urn:ietf:wg:oauth:2.0:oob`（共通） | `urn:ietf:wg:oauth:2.0:oob`（共通） |

※ スマレジ・デベロッパーズでは「開発環境」をローカル用URL or AWS用URL のどちらかに合わせて切り替えて利用する。

---

## アクセスポイント一覧（本番: https://reggit.shop）

本番のベースURLを **https://reggit.shop** とするときの主要な URL 一覧。**https://www.reggit.shop** でも同じパスでアクセス可能（DNS で www も同一 ALB に向けている場合）。

| 用途 | URL（本番） |
|------|-------------|
| **トップ** | https://reggit.shop |
| **LIFF（プロジェクト指定）** | https://reggit.shop/liff/default |
| **管理画面ログイン** | https://reggit.shop/admin/login |
| **システム管理ログイン** | https://reggit.shop/system-admin/login |
| **API ベース** | https://reggit.shop/api |
| **アプリ設定取得（モバイル用）** | https://reggit.shop/api/v1/app-config?project_key=default |
| **LINE Webhook** | https://reggit.shop/api/line/webhook |
| **スマレジ 利用者契約通知** | https://reggit.shop/api/smaregi/idp |
| **スマレジ Webhook** | https://reggit.shop/api/smaregi/webhook |
| **Stripe Webhook** | https://reggit.shop/api/payment/webhook |
| **デバイス登録** | https://reggit.shop/api/devices/register |

**EB 環境プロパティ**の **APP_URL** は `https://reggit.shop` に設定する。スマレジ・LINE・Stripe の管理画面で登録する URL も上記のとおり。

---

## AWS（Elastic Beanstalk 環境プロパティで設定）

| 名前 | 説明 | 例（値は伏せてメモ） |
|------|------|----------------------|
| APP_ENV | 環境 | production |
| APP_DEBUG | デバッグ | false |
| APP_KEY | Laravel 鍵 | base64:... |
| APP_URL | 本番URL | https://reggit.shop（独自ドメイン）または http://Reggit...（EB のまま） |
| DB_HOST | RDS エンドポイント | xxx.rds.amazonaws.com |
| DB_PORT | 3306 |
| DB_DATABASE | reggit |
| DB_USERNAME | RDS ユーザー | （メモ用） |
| DB_PASSWORD | RDS パスワード | （メモ用・EB にだけ入力） |

---

## スマレジ（ローカル vs AWS）

**管理画面では設定できません。** ローカルは `.env`、AWS は EB の環境プロパティで設定。

| 名前 | ローカル用（.env） | AWS・本番用（EB 環境プロパティ） |
|------|-------------------|----------------------------------|
| SMAREGI_CLIENT_ID | ローカル用の値（そのまま） | `d613e480ff3c7165b1386734345ff700` |
| SMAREGI_CLIENT_SECRET | ローカル用の値（そのまま） | `0859bbcccef9f14ca054bca24bc484852a241451f03e4998b4756f2aaa7d37c6` |
| **SMAREGI_CONTRACT_ID** | ローカル用契約ID（例: `sb_skx928r2` または未設定で config デフォルト） | **`sb_skp835t6`** |

※ SMAREGI_ID_BASE_URL / SMAREGI_API_BASE_URL / SMAREGI_SCOPE はローカル・AWSとも .env.example のままで可。  
※ SMAREGI_WEBHOOK_SECRET は必要に応じてそれぞれの環境で設定。

---

## スマレジ（AWS・本番用）EB に追加する一覧

**Reggit-env** の「環境のプロパティ」に以下を追加する。

| 名前 | 値（AWS用） |
|------|-------------|
| SMAREGI_CLIENT_ID | `d613e480ff3c7165b1386734345ff700` |
| SMAREGI_CLIENT_SECRET | `0859bbcccef9f14ca054bca24bc484852a241451f03e4998b4756f2aaa7d37c6` |
| SMAREGI_CONTRACT_ID | `sb_skp835t6` |

---

## スマレジ・デベロッパーズで入力するURL（切り替え用）

**ローカルでテストするとき** → 左のURLを入力。**AWS（本番）で使うとき** → 右のURLを入力。

| 項目 | ローカル用 | AWS・本番用 |
|------|------------|-------------|
| **アプリURL** | `http://localhost:8080` | `https://reggit.shop` |
| **利用者契約通知先URL** | `http://localhost:8080/api/smaregi/idp` | `https://reggit.shop/api/smaregi/idp` |
| **Webhook送信先エンドポイント** | `http://localhost:8080/api/smaregi/webhook` | `https://reggit.shop/api/smaregi/webhook` |
| **リダイレクトURI** | `urn:ietf:wg:oauth:2.0:oob` | `urn:ietf:wg:oauth:2.0:oob` |

このファイルはあくまでメモ用です。本番の秘密情報は EB の環境プロパティまたは手元の .env にだけ入れ、リポジトリには上げないでください。

---

## .env の項目を本番（EB）に持っていく一覧

**いま .env に書かれている項目は、本番でも EB の環境プロパティに設定してください。**  
下記は .env の並びに合わせた一覧です。「本番の値」が「要変更」のものだけ、本番用の値に差し替えてから EB に追加します。

| .env の名前 | 本番の値 | 備考 |
|-------------|----------|------|
| APP_NAME | そのまま可 | 例: Reggit |
| APP_ENV | **要変更** | `production` にする |
| APP_KEY | そのまま可 | 同じ鍵を本番でも使う場合 |
| APP_DEBUG | **要変更** | 必ず `false` |
| APP_URL | **要変更** | `https://reggit.shop`（独自ドメイン）または EB の URL |
| APP_API_BASE | そのまま可 | `/api` |
| DEMO_SEED | 要変更推奨 | 本番では `false` が無難。デモ用なら `true` のまま |
| DEMO_SYSTEM_LOGIN_ID 〜 DEMO_STORE_NAME | そのまま可 or 省略可 | デモ用アカウントを使う場合のみ EB に追加 |
| LOG_CHANNEL | そのまま可 | `stack` |
| LOG_LEVEL | 要変更推奨 | 本番は `error` や `warning`。デバッグ時は `debug` |
| DB_CONNECTION | そのまま可 | `mysql` |
| DB_HOST | **要変更** | RDS のエンドポイント（例: xxx.rds.amazonaws.com） |
| DB_PORT | そのまま可 | `3306` |
| DB_DATABASE | そのまま可 | `reggit`（RDS で作成した DB 名） |
| DB_USERNAME | **要変更** | RDS のマスターユーザー名 |
| DB_PASSWORD | **要変更** | RDS のパスワード |
| ADMIN_EMAIL | そのまま可 | 未設定なら login_id に使われる |
| ADMIN_PASSWORD | **要変更推奨** | 本番では強めのパスワードに |
| ADMIN_NAME | そのまま可 | 任意 |
| LINE_CHANNEL_ACCESS_TOKEN | そのまま可 | 本番用チャネルなら本番用トークンに差し替え |
| LINE_CHANNEL_SECRET | そのまま可 | 同上 |
| LINE_LIFF_ID | 要確認 | 本番用 LIFF なら本番用 ID に。同じ LIFF ならそのまま |
| LINE_LIFF_URL | **要変更** | 本番なら `https://reggit.shop/...` など |
| STRIPE_KEY | **要変更** | 本番は Live 公開キー `pk_live_...` |
| STRIPE_SECRET | **要変更** | 本番は Live シークレット `sk_live_...` |
| STRIPE_WEBHOOK_SECRET | **要変更** | 本番用 Webhook の `whsec_...`（Stripe で本番 URL 登録後に取得） |
| SMAREGI_CLIENT_ID | **要変更（AWS用）** | AWS 用は別の値（例: d613e480ff3c7165...）。ローカルと同じならそのまま |
| SMAREGI_CLIENT_SECRET | **要変更（AWS用）** | 同上 |
| SMAREGI_ID_BASE_URL | そのまま可 | https://id.smaregi.dev |
| SMAREGI_API_BASE_URL | そのまま可 | https://api.smaregi.dev |
| SMAREGI_SCOPE | そのまま可 | pos.stores:read pos.stores:write ...（1行で） |
| SMAREGI_WEBHOOK_SECRET | 任意 | 使う場合はデベロッパーズで設定した値と一致させる |
| SMAREGI_WEBHOOK_SECRET_HEADER | そのまま可 | X-Smaregi-Webhook-Secret |
| SMAREGI_CONTRACT_ID | **要変更（AWS用）** | 本番は `sb_skp835t6` など AWS 用契約ID（.env に無ければ EB で追加） |
| WALLET_PASS_ENABLED | そのまま可 | true / false |
| WALLET_PASS_TEAM_ID 〜 WALLET_PASS_ASSETS_PATH | そのまま可 | 証明書・キーはイメージに含めるか S3 等から取得する運用にすること |
| GOOGLE_WALLET_ENABLED | そのまま可 | true / false |
| GOOGLE_WALLET_ISSUER_ID | そのまま可 | 本番用 Issuer なら差し替え |
| GOOGLE_WALLET_CREDENTIALS_PATH | 要確認 | 本番ではパスを合わせるか S3 等から取得 |
| GOOGLE_WALLET_ORIGINS | **要変更** | `https://reggit.shop` を追加（カンマ区切り） |
| GOOGLE_WALLET_CLASS_SUFFIX | そのまま可 | membership 等 |
| GOOGLE_WALLET_ALLOW_DEMO_USER | 要変更推奨 | 本番では `false` が無難 |
| BROADCAST_DRIVER | そのまま可 | log |
| CACHE_DRIVER | そのまま可 | file |
| FILESYSTEM_DISK | そのまま可 | local |
| QUEUE_CONNECTION | そのまま可 | sync |
| SESSION_DRIVER | そのまま可 | file |
| SESSION_LIFETIME | そのまま可 | 120 |
| REDIS_HOST, REDIS_PASSWORD, REDIS_PORT | 省略可 | Redis を使う場合のみ EB に追加 |
| MAIL_MAILER 〜 MAIL_FROM_NAME | **要変更（本番で送信する場合）** | 本番用 SMTP（例: AWS SES）に差し替え。使わないなら省略可 |
| JAPANPOST_API_HOST | そのまま可 | api.da.pf.japanpost.jp |
| JAPANPOST_CLIENT_ID | そのまま可 | 本番でも同じ認証を使う場合 |
| JAPANPOST_CLIENT_SECRET | そのまま可 | 同上 |
| PUSH_ONESIGNAL_ENABLED | そのまま可 | true / false |
| PUSH_ONESIGNAL_APP_ID | そのまま可 | モバイルアプリの OneSignalAppId と同一に |
| PUSH_ONESIGNAL_API_KEY | そのまま可 | REST API Key |
| PUSH_ONESIGNAL_API_URL | そのまま可 | https://api.onesignal.com/notifications |

**手順**: 上記の「名前」を EB の環境プロパティに 1 件ずつ追加し、「本番の値」に従って入力する。「そのまま可」は .env の値をそのままコピーしてよい（DB_* と APP_URL など「要変更」は除く）。

---

## EB 環境プロパティ 設定一覧（全項目）

**Reggit-env** → **設定** → **更新、モニタリング、ログ記録** → **編集** → **環境プロパティ** で、以下を **1件ずつ「追加」** して設定する。  
値はあなたの環境（RDS・APP_KEY・独自ドメインなど）に合わせて置き換える。

### 必須（アプリ起動に必要）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| NATIVE_APP_USER_AGENT_TOKEN | Reggit | Skip.env と一致（ネイティブ UA 識別子） |
| NATIVE_APP_URL_SCHEME | reggit | Skip.env と一致（`reggit://` 深いリンク） |
| NATIVE_APP_ORDER_TYPE_MOBILE_LABEL | Reggit | 注文種別・機能フラグの表示名 |
| APP_ENV | production | 本番用 |
| APP_DEBUG | false | 本番では必ず false |
| APP_KEY | base64:xxxxxxxx... | ローカルで `php artisan key:generate --show` で表示した値。既に使っている鍵があればそのまま |
| APP_URL | http://Reggit.ap-northeast-3.elasticbeanstalk.com | 独自ドメイン運用時は `https://reggit.shop` に変更 |
| DB_CONNECTION | mysql | そのまま |
| DB_HOST | database-1.cpi8emy64hbe.ap-northeast-3.rds.amazonaws.com | あなたの RDS エンドポイント |
| DB_PORT | 3306 | |
| DB_DATABASE | reggit | RDS で作成した DB 名 |
| DB_USERNAME | admin | RDS のマスターユーザー名 |
| DB_PASSWORD | （RDS のパスワード） | 手入力。画面ではマスクされる |

### 管理画面ログイン（初回システム管理者）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| ADMIN_EMAIL | reserve | 未設定時は login_id にこの値が使われる |
| ADMIN_PASSWORD | reserve | 本番では強めのパスワードに変更 |
| ADMIN_NAME | システムアドミン | 任意 |

※ ADMIN_LOGIN_ID を設定すると、login_id はその値になる（未設定なら ADMIN_EMAIL）。

### スマレジ（AWS 用）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| SMAREGI_CLIENT_ID | d613e480ff3c7165b1386734345ff700 | 上記「スマレジ（AWS・本番用）」参照 |
| SMAREGI_CLIENT_SECRET | 0859bbcccef9f14ca054bca24bc484852a241451f03e4998b4756f2aaa7d37c6 | 同上 |
| SMAREGI_CONTRACT_ID | sb_skp835t6 | AWS 用契約ID |
| SMAREGI_ID_BASE_URL | https://id.smaregi.dev | そのままで可 |
| SMAREGI_API_BASE_URL | https://api.smaregi.dev | そのままで可 |
| SMAREGI_SCOPE | pos.stores:read pos.stores:write pos.products:read pos.products:write pos.transactions:read pos.transactions:write pos.customers:read pos.customers:write pos.stock:read pos.stock:write pos.staffs:read pos.suppliers:read pos.terminals:notify | そのままで可 |

### ログ・キャッシュ・セッション（そのままで可）

| 名前 | 値 | 備考 |
|------|-----|------|
| LOG_CHANNEL | stack | |
| LOG_LEVEL | production なら error や warning 推奨 | デバッグ時は debug |
| CACHE_DRIVER | file | |
| SESSION_DRIVER | file | |
| SESSION_LIFETIME | 120 | |
| FILESYSTEM_DISK | local | |
| QUEUE_CONNECTION | sync | |
| BROADCAST_DRIVER | log | |

### 使う場合だけ設定（Stripe・LINE・メール・プッシュなど）

**決済（Stripe）** — オンライン決済を使う場合に EB に追加する。

| 名前 | 値 | 備考 |
|------|-----|------|
| STRIPE_KEY | （Stripe 公開キー `pk_live_...`） | フロントの Stripe.js 用。本番は Live キー |
| STRIPE_SECRET | （Stripe シークレット `sk_live_...`） | サーバー側の決済処理用。本番は Live キー |
| STRIPE_WEBHOOK_SECRET | （Webhook 署名シークレット `whsec_...`） | Stripe ダッシュボードで「開発者 → Webhook」でエンドポイント登録後に表示。本番用エンドポイントは `https://reggit.shop/api/...` など |

※ Stripe ダッシュボードで本番用のキーと Webhook エンドポイント（`https://reggit.shop` ベース）を用意し、その値を EB の環境プロパティに設定する。

**LINE（LIFF・メッセージ）** — LINE 連携・LIFF を使う場合。

| 名前 | 値 | 備考 |
|------|-----|------|
| LINE_CHANNEL_ACCESS_TOKEN | （チャネルアクセストークン） | LINE Developers のチャネル設定から |
| LINE_CHANNEL_SECRET | （チャネルシークレット） | 同上 |
| LINE_LIFF_ID | （LIFF ID） | LIFF アプリの ID。本番用 LIFF の URL は `https://reggit.shop` ベースに |
| LINE_LIFF_URL | 未設定で可 | 必要なら `https://reggit.shop/...` を指定 |

**メール送信** — 本番でメールを送る場合（注文通知・パスワードリセット等）。

| 名前 | 値 | 備考 |
|------|-----|------|
| MAIL_MAILER | smtp | |
| MAIL_HOST | （SMTP ホスト。例: email-smtp.ap-northeast-1.amazonaws.com） | AWS SES を使う場合はリージョンに合わせる |
| MAIL_PORT | 587 | TLS の場合 |
| MAIL_USERNAME | （SMTP ユーザー） | SES の場合は IAM の SMTP 認証情報 |
| MAIL_PASSWORD | （SMTP パスワード） | 同上 |
| MAIL_ENCRYPTION | tls | |
| MAIL_FROM_ADDRESS | （送信元アドレス。SES で検証済みのアドレス） | |
| MAIL_FROM_NAME | Reggit | 任意 |

**プッシュ通知** — 使う場合のみ。

| 名前 | 値 | 備考 |
|------|-----|------|
| PUSH_FCM_ENABLED | true | FCM を使う場合 |
| PUSH_FCM_SERVER_KEY | （FCM サーバーキー） | Firebase コンソールから |
| PUSH_ONESIGNAL_ENABLED | true | OneSignal を使う場合 |
| PUSH_ONESIGNAL_APP_ID | （OneSignal App ID） | モバイルアプリの OneSignalAppId と同一に |
| PUSH_ONESIGNAL_API_KEY | （REST API Key） | OneSignal Dashboard → Keys & IDs |

**その他（使う場合のみ）**

| 名前 | 値 | 備考 |
|------|-----|------|
| SANCTUM_STATEFUL_DOMAINS | reggit.shop | 独自ドメインで SPA/API 認証を使う場合。複数はカンマ区切り |
| JAPANPOST_API_HOST | （日本郵便 API ホスト） | 日本郵便 API を使う場合 |
| JAPANPOST_CLIENT_ID | （クライアントID） | 同上 |
| JAPANPOST_CLIENT_SECRET | （クライアントシークレット） | 同上 |
| SMAREGI_WEBHOOK_SECRET | （任意の文字列） | スマレジ Webhook の署名検証用。デベロッパーズで設定した値と一致させる |

### 使わない場合は省略してよいもの

- **WALLET_PASS_***（Apple Wallet）
- **GOOGLE_WALLET_***（Google Wallet）
- **PUSH_FCM_*** / **PUSH_ONESIGNAL_***（プッシュ通知を使わない場合）
- **JAPANPOST_***（日本郵便 API を使わない場合）
- **REDIS_***（Redis を使わない場合。キャッシュ・セッションは file のままなら不要）

---

**設定の流れ**: まず「必須」をすべて追加 → 保存・適用。その後、スマレジ・LINE・Stripe など必要なブロックだけ追加する。
