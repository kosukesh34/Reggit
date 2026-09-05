# 本番環境用の環境変数テンプレート

このファイルは **Xserver 上の本番 `.env`** や、手元で管理する環境変数のメモ用です。
**実際のパスワードやシークレットが含まれる場合はこのファイルをコミットしないでください。**（必要なら `docs/ENV_PRODUCTION_TEMPLATE.md` を .gitignore に追加）

- **ローカル用**: プロジェクト直下の `.env` で管理（このファイルでは変更しない）
- **本番用（Xserver）**: サーバー上のプロジェクト直下 `.env` で管理

---

## ベースURL・エンドポイント（ローカル vs 本番）

| 用途 | ローカル用（.env） | 本番用（Xserver の .env） |
|------|-------------------|---------------------------|
| **APP_URL** | `http://localhost:8080` | `https://reggit.jp` |
| **アプリURL**（スマレジ画面） | `http://localhost:8080` | `https://reggit.jp` |
| **利用者契約通知先URL** | `http://localhost:8080/api/smaregi/idp` | `https://reggit.jp/api/smaregi/idp` |
| **Webhook送信先エンドポイント** | `http://localhost:8080/api/smaregi/webhook` | `https://reggit.jp/api/smaregi/webhook` |
| **リダイレクトURI** | `urn:ietf:wg:oauth:2.0:oob`（共通） | `urn:ietf:wg:oauth:2.0:oob`（共通） |

※ スマレジ・デベロッパーズでは「開発環境」をローカル用 URL or 本番用 URL のどちらかに合わせて切り替えて利用する。

---

## アクセスポイント一覧（本番: https://reggit.jp）

本番のベース URL を **https://reggit.jp** とするときの主要な URL 一覧。**https://www.reggit.jp** でも同じパスでアクセス可能（DNS で www も同一サーバーに向けている場合）。

| 用途 | URL（本番） |
|------|-------------|
| **トップ** | https://reggit.jp |
| **LIFF（プロジェクト指定）** | https://reggit.jp/liff/default |
| **管理画面ログイン** | https://reggit.jp/admin/login |
| **システム管理ログイン** | https://reggit.jp/system-admin/login |
| **API ベース** | https://reggit.jp/api |
| **アプリ設定取得（モバイル用）** | https://reggit.jp/api/v1/app-config?project_key=default |
| **LINE Webhook** | https://reggit.jp/api/line/webhook |
| **スマレジ 利用者契約通知** | https://reggit.jp/api/smaregi/idp |
| **スマレジ Webhook** | https://reggit.jp/api/smaregi/webhook |
| **Stripe Webhook** | https://reggit.jp/api/payment/webhook |
| **デバイス登録** | https://reggit.jp/api/devices/register |

**`.env` の `APP_URL`** は `https://reggit.jp` に設定する。スマレジ・LINE・Stripe の管理画面で登録する URL も上記のとおり。

---

## 本番インフラ（Xserver）

| 項目 | 説明 |
|------|------|
| ホスティング | エックスサーバー |
| アプリ配置 | ドキュメントルート配下（例: `public/` を公開ディレクトリに設定） |
| DB | Xserver の MySQL（同一サーバー内。`DB_HOST` は通常 `localhost` またはサーバーパネル記載のホスト名） |
| メール送信 | Xserver の SMTP（同一サーバー内から送信。後述） |
| キュー | `QUEUE_CONNECTION=database` + cron または常駐で `php artisan queue:work` |
| 環境変数 | サーバー上の `.env` に記載 |

---

## 本番の基本設定（.env）

| 名前 | 説明 | 例（値は伏せてメモ） |
|------|------|----------------------|
| APP_ENV | 環境 | production |
| APP_DEBUG | デバッグ | false |
| APP_KEY | Laravel 鍵 | base64:... |
| APP_URL | 本番 URL | https://reggit.jp |
| DB_HOST | MySQL ホスト | localhost（または Xserver パネル記載のホスト名） |
| DB_PORT | 3306 |
| DB_DATABASE | reggit |
| DB_USERNAME | MySQL ユーザー | （サーバーパネルで作成したユーザー） |
| DB_PASSWORD | MySQL パスワード | （メモ用・サーバー上の .env にだけ入力） |

---

## スマレジ（ローカル vs 本番）

**管理画面では設定できません。** ローカルは `.env`、本番は Xserver 上の `.env` で設定。

| 名前 | ローカル用（.env） | 本番用（Xserver の .env） |
|------|-------------------|---------------------------|
| SMAREGI_CLIENT_ID | ローカル用の値（そのまま） | `（本番用クライアント ID）` |
| SMAREGI_CLIENT_SECRET | ローカル用の値（そのまま） | `（本番用クライアントシークレット）` |
| **SMAREGI_CONTRACT_ID** | ローカル用契約 ID（例: `sb_skx928r2` または未設定で config デフォルト） | **`sb_skp835t6`** |

※ SMAREGI_ID_BASE_URL / SMAREGI_API_BASE_URL / SMAREGI_SCOPE はローカル・本番とも .env.example のままで可。
※ SMAREGI_WEBHOOK_SECRET は必要に応じてそれぞれの環境で設定。

---

## スマレジ（本番用）.env に追加する一覧

本番サーバー上の `.env` に以下を設定する。

| 名前 | 値（本番用） |
|------|-------------|
| SMAREGI_CLIENT_ID | `（本番用クライアント ID）` |
| SMAREGI_CLIENT_SECRET | `（本番用クライアントシークレット）` |
| SMAREGI_CONTRACT_ID | `sb_skp835t6` |

---

## スマレジ・デベロッパーズで入力する URL（切り替え用）

**ローカルでテストするとき** → 左の URL を入力。**本番で使うとき** → 右の URL を入力。

| 項目 | ローカル用 | 本番用 |
|------|------------|--------|
| **アプリ URL** | `http://localhost:8080` | `https://reggit.jp` |
| **利用者契約通知先 URL** | `http://localhost:8080/api/smaregi/idp` | `https://reggit.jp/api/smaregi/idp` |
| **Webhook 送信先エンドポイント** | `http://localhost:8080/api/smaregi/webhook` | `https://reggit.jp/api/smaregi/webhook` |
| **リダイレクト URI** | `urn:ietf:wg:oauth:2.0:oob` | `urn:ietf:wg:oauth:2.0:oob` |

このファイルはあくまでメモ用です。本番の秘密情報はサーバー上の `.env` にだけ入れ、リポジトリには上げないでください。

---

## .env の項目を本番（Xserver）に持っていく一覧

**いま .env に書かれている項目は、本番でもサーバー上の `.env` に設定してください。**
下記は .env の並びに合わせた一覧です。「本番の値」が「要変更」のものだけ、本番用の値に差し替えてから反映します。

| .env の名前 | 本番の値 | 備考 |
|-------------|----------|------|
| APP_NAME | そのまま可 | 例: Reggit |
| APP_ENV | **要変更** | `production` にする |
| APP_KEY | そのまま可 | 同じ鍵を本番でも使う場合 |
| APP_DEBUG | **要変更** | 必ず `false` |
| APP_URL | **要変更** | `https://reggit.jp` |
| APP_API_BASE | そのまま可 | `/api` |
| DEMO_SEED | 要変更推奨 | 本番では `false` が無難。デモ用なら `true` のまま |
| DEMO_SYSTEM_LOGIN_ID 〜 DEMO_STORE_NAME | そのまま可 or 省略可 | デモ用アカウントを使う場合のみ本番 .env に追加 |
| LOG_CHANNEL | そのまま可 | `stack` |
| LOG_LEVEL | 要変更推奨 | 本番は `error` や `warning`。デバッグ時は `debug` |
| DB_CONNECTION | そのまま可 | `mysql` |
| DB_HOST | **要変更** | Xserver の MySQL ホスト（通常 `localhost`） |
| DB_PORT | そのまま可 | `3306` |
| DB_DATABASE | そのまま可 | `reggit`（Xserver で作成した DB 名） |
| DB_USERNAME | **要変更** | Xserver の MySQL ユーザー名 |
| DB_PASSWORD | **要変更** | Xserver の MySQL パスワード |
| ADMIN_EMAIL | そのまま可 | 未設定なら login_id に使われる |
| ADMIN_PASSWORD | **要変更推奨** | 本番では強めのパスワードに |
| ADMIN_NAME | そのまま可 | 任意 |
| LINE_CHANNEL_ACCESS_TOKEN | そのまま可 | 本番用チャネルなら本番用トークンに差し替え |
| LINE_CHANNEL_SECRET | そのまま可 | 同上 |
| LINE_LIFF_ID | 要確認 | 本番用 LIFF なら本番用 ID に。同じ LIFF ならそのまま |
| LINE_LIFF_URL | **要変更** | 本番なら `https://reggit.jp/...` など |
| STRIPE_KEY | **要変更** | 本番は Live 公開キー `pk_live_...` |
| STRIPE_SECRET | **要変更** | 本番は Live シークレット `sk_live_...` |
| STRIPE_WEBHOOK_SECRET | **要変更** | 本番用 Webhook の `whsec_...`（Stripe で本番 URL 登録後に取得） |
| SMAREGI_CLIENT_ID | **要変更（本番用）** | 本番用の値に差し替える。ローカルと同じならそのまま |
| SMAREGI_CLIENT_SECRET | **要変更（本番用）** | 同上 |
| SMAREGI_ID_BASE_URL | そのまま可 | https://id.smaregi.dev |
| SMAREGI_API_BASE_URL | そのまま可 | https://api.smaregi.dev |
| SMAREGI_SCOPE | そのまま可 | pos.stores:read pos.stores:write ...（1行で） |
| SMAREGI_WEBHOOK_SECRET | 任意 | 使う場合はデベロッパーズで設定した値と一致させる |
| SMAREGI_WEBHOOK_SECRET_HEADER | そのまま可 | X-Smaregi-Webhook-Secret |
| SMAREGI_CONTRACT_ID | **要変更（本番用）** | 本番は `sb_skp835t6` など本番用契約 ID |
| WALLET_PASS_ENABLED | そのまま可 | true / false |
| WALLET_PASS_TEAM_ID 〜 WALLET_PASS_ASSETS_PATH | そのまま可 | 証明書・キーはサーバー上に配置する |
| GOOGLE_WALLET_ENABLED | そのまま可 | true / false |
| GOOGLE_WALLET_ISSUER_ID | そのまま可 | 本番用 Issuer なら差し替え |
| GOOGLE_WALLET_CREDENTIALS_PATH | 要確認 | 本番ではパスを合わせる |
| GOOGLE_WALLET_ORIGINS | **要変更** | `https://reggit.jp` を追加（カンマ区切り） |
| GOOGLE_WALLET_CLASS_SUFFIX | そのまま可 | membership 等 |
| GOOGLE_WALLET_ALLOW_DEMO_USER | 要変更推奨 | 本番では `false` が無難 |
| BROADCAST_DRIVER | そのまま可 | log |
| CACHE_DRIVER | そのまま可 | file |
| FILESYSTEM_DISK | そのまま可 | local |
| QUEUE_CONNECTION | **要変更（本番）** | 開発のみ `sync`。本番は `database` + `queue:work`（cron 等で常駐） |
| SESSION_DRIVER | そのまま可 | file |
| SESSION_LIFETIME | そのまま可 | 120 |
| REDIS_HOST, REDIS_PASSWORD, REDIS_PORT | 省略可 | Redis を使う場合のみ .env に追加 |
| MAIL_MAILER 〜 MAIL_FROM_NAME | **要変更（本番で送信する場合）** | Xserver の SMTP に差し替え（後述）。使わないなら省略可 |
| JAPANPOST_API_HOST | そのまま可 | api.da.pf.japanpost.jp |
| JAPANPOST_CLIENT_ID | そのまま可 | 本番でも同じ認証を使う場合 |
| JAPANPOST_CLIENT_SECRET | そのまま可 | 同上 |
| PUSH_FCM_ENABLED | そのまま可 | true / false |
| PUSH_FCM_PROJECT_ID | そのまま可 | Firebase プロジェクト ID（例: reggit-dbb5a） |
| PUSH_FCM_CREDENTIALS | そのまま可 | サービスアカウント JSON のパス（サーバ上に配置） |

**手順**: ローカルの `.env` をベースに、上記の「要変更」項目を本番用の値に差し替えて Xserver 上の `.env` に配置する。「そのまま可」はローカルの値をそのままコピーしてよい（DB_* と APP_URL など「要変更」は除く）。

---

## 本番 .env 設定一覧（全項目）

Xserver 上のプロジェクト直下 `.env` に、以下を設定する。
値は環境（MySQL ユーザー・APP_KEY・独自ドメインなど）に合わせて置き換える。

### 必須（アプリ起動に必要）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| NATIVE_APP_USER_AGENT_TOKEN | Reggit | Skip.env と一致（ネイティブ UA 識別子） |
| NATIVE_APP_URL_SCHEME | reggit | Skip.env と一致（`reggit://` 深いリンク） |
| NATIVE_APP_ORDER_TYPE_MOBILE_LABEL | Reggit | 注文種別・機能フラグの表示名 |
| APP_ENV | production | 本番用 |
| APP_DEBUG | false | 本番では必ず false |
| APP_KEY | base64:xxxxxxxx... | ローカルで `php artisan key:generate --show` で表示した値。既に使っている鍵があればそのまま |
| APP_URL | https://reggit.jp | 独自ドメイン |
| DB_CONNECTION | mysql | そのまま |
| DB_HOST | localhost | Xserver の MySQL ホスト（パネル記載を確認） |
| DB_PORT | 3306 | |
| DB_DATABASE | reggit | Xserver で作成した DB 名 |
| DB_USERNAME | （MySQL ユーザー名） | サーバーパネルで作成したユーザー |
| DB_PASSWORD | （MySQL パスワード） | 手入力。リポジトリには上げない |

### 管理画面ログイン（初回システム管理者）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| ADMIN_EMAIL | reserve | 未設定時は login_id にこの値が使われる |
| ADMIN_PASSWORD | reserve | 本番では強めのパスワードに変更 |
| ADMIN_NAME | システムアドミン | 任意 |

※ ADMIN_LOGIN_ID を設定すると、login_id はその値になる（未設定なら ADMIN_EMAIL）。

### スマレジ（本番用）

| 名前 | 値（例） | 備考 |
|------|----------|------|
| SMAREGI_CLIENT_ID | （本番用クライアント ID） | 上記「スマレジ（本番用）」参照 |
| SMAREGI_CLIENT_SECRET | （本番用クライアントシークレット） | 同上 |
| SMAREGI_CONTRACT_ID | sb_skp835t6 | 本番用契約 ID |
| SMAREGI_ID_BASE_URL | https://id.smaregi.dev | そのままで可 |
| SMAREGI_API_BASE_URL | https://api.smaregi.dev | そのままで可 |
| SMAREGI_SCOPE | pos.stores:read pos.stores:write pos.products:read pos.products:write pos.transactions:read pos.transactions:write pos.customers:read pos.customers:write pos.stock:read pos.stock:write pos.staffs:read pos.suppliers:read pos.terminals:notify | そのままで可 |

### ログ・キャッシュ・セッション・キュー（そのままで可）

| 名前 | 値 | 備考 |
|------|-----|------|
| LOG_CHANNEL | stack | |
| LOG_LEVEL | production なら error や warning 推奨 | デバッグ時は debug |
| CACHE_DRIVER | file | |
| SESSION_DRIVER | file | |
| SESSION_LIFETIME | 120 | |
| FILESYSTEM_DISK | local | |
| QUEUE_CONNECTION | database | 予約配信・スマレジ同期 Job はワーカー必須。`sync` は開発のみ。cron で `php artisan queue:work --stop-when-empty` または常駐プロセスを設定 |
| BROADCAST_CONNECTION | reverb | LIFF 会員証の即時反映。`php artisan reverb:start` と WebSocket プロキシが必要 |
| BROADCAST_DRIVER | reverb | `BROADCAST_CONNECTION` と同じ値 |
| REVERB_APP_ID | 1001 | ローカル `.env.example` と同じでよい。変えるなら `npm run build` 前に合わせる |
| REVERB_APP_KEY | reggit-reverb-key | LIFF の `window.__REVERB_APP_KEY__` と一致させる |
| REVERB_APP_SECRET | （本番用に生成） | サーバ間の署名用。公開しない |
| REVERB_HOST | 127.0.0.1 | Laravel から Reverb への接続先 |
| REVERB_PORT | 8081 | Reverb の待受ポート |
| REVERB_SCHEME | http | リバースプロキシが TLS 終端するなら http のまま |
| REVERB_SERVER_HOST | 0.0.0.0 | |
| REVERB_SERVER_PORT | 8081 | |

### 使う場合だけ設定（Stripe・LINE・メール・プッシュなど）

**決済（Stripe）** — オンライン決済を使う場合に .env に追加する。

| 名前 | 値 | 備考 |
|------|-----|------|
| STRIPE_KEY | （Stripe 公開キー `pk_live_...`） | フロントの Stripe.js 用。本番は Live キー |
| STRIPE_SECRET | （Stripe シークレット `sk_live_...`） | サーバー側の決済処理用。本番は Live キー |
| STRIPE_WEBHOOK_SECRET | （Webhook 署名シークレット `whsec_...`） | Stripe ダッシュボードで「開発者 → Webhook」でエンドポイント登録後に表示。本番用エンドポイントは `https://reggit.jp/api/payment/webhook` |

※ Stripe ダッシュボードで本番用のキーと Webhook エンドポイント（`https://reggit.jp` ベース）を用意し、その値を本番 `.env` に設定する。

**LINE（LIFF・メッセージ）** — LINE 連携・LIFF を使う場合。

| 名前 | 値 | 備考 |
|------|-----|------|
| LINE_CHANNEL_ACCESS_TOKEN | （チャネルアクセストークン） | LINE Developers のチャネル設定から |
| LINE_CHANNEL_SECRET | （チャネルシークレット） | 同上 |
| LINE_LIFF_ID | （LIFF ID） | LIFF アプリの ID。本番用 LIFF の URL は `https://reggit.jp` ベースに |
| LINE_LIFF_URL | 未設定で可 | 必要なら `https://reggit.jp/...` を指定 |

**メール送信** — 本番でメールを送る場合（注文通知・パスワードリセット等）。
アプリが Xserver 上で動くため、**Xserver の SMTP** を使う（1日数件程度なら追加サービス不要）。

| 名前 | 値 | 備考 |
|------|-----|------|
| MAIL_MAILER | smtp | |
| MAIL_HOST | sv***.xserver.jp | サーバーパネル「送信メール(SMTP)サーバー」のホスト名 |
| MAIL_PORT | 465 | SSL の場合 |
| MAIL_USERNAME | info@reggit.jp | Xserver で作成したメールアカウント（フルアドレス） |
| MAIL_PASSWORD | （メールアカウントのパスワード） | サーバーパネルで設定した値 |
| MAIL_ENCRYPTION | ssl | ポート 465 の場合。587 + STARTTLS なら `tls` |
| MAIL_FROM_ADDRESS | info@reggit.jp | `MAIL_USERNAME` と同じドメインのアドレスにする |
| MAIL_FROM_NAME | Reggit | 任意 |

※ Xserver でメールアカウントを事前に作成すること。送信元アドレスは作成済みのアドレスを使う。

**プッシュ通知** — 使う場合のみ。

| 名前 | 値 | 備考 |
|------|-----|------|
| PUSH_FCM_ENABLED | true | FCM HTTP v1 を使う場合 |
| PUSH_FCM_PROJECT_ID | reggit-dbb5a | Firebase プロジェクト ID |
| PUSH_FCM_CREDENTIALS | storage/app/firebase/reggit-dbb5a-adminsdk.json | サービスアカウント JSON（git 外・サーバに配置） |

**その他（使う場合のみ）**

| 名前 | 値 | 備考 |
|------|-----|------|
| SANCTUM_STATEFUL_DOMAINS | reggit.jp | 独自ドメインで SPA/API 認証を使う場合。複数はカンマ区切り |
| JAPANPOST_API_HOST | （日本郵便 API ホスト） | 日本郵便 API を使う場合 |
| JAPANPOST_CLIENT_ID | （クライアント ID） | 同上 |
| JAPANPOST_CLIENT_SECRET | （クライアントシークレット） | 同上 |
| SMAREGI_WEBHOOK_SECRET | （任意の文字列） | スマレジ Webhook の署名検証用。デベロッパーズで設定した値と一致させる |

### 使わない場合は省略してよいもの

- **WALLET_PASS_***（Apple Wallet）
- **GOOGLE_WALLET_***（Google Wallet）
- **PUSH_FCM_***（プッシュ通知を使わない場合）
- **JAPANPOST_***（日本郵便 API を使わない場合）
- **REDIS_***（Redis を使わない場合。キャッシュ・セッションは file のままなら不要）

---

**設定の流れ**: Xserver にデプロイ → サーバー上で `.env` を作成 → まず「必須」をすべて設定 → `php artisan migrate` 等で動作確認 → スマレジ・LINE・Stripe など必要なブロックを追加 → キューワーカー（cron）を設定。
