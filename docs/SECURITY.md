# セキュリティ

## 認証とデータ境界

| 入口 | 制御 |
|---|---|
| 管理画面 | `admin` guard のセッション、ロール、担当プロジェクト・店舗、スタッフ機能権限 |
| LIFF 顧客 | 本番では LINE ID トークンを検証。`X-Line-User-Id` だけを認証に使わない |
| 顧客端末 | `X-Device-Identifier` と発行済み `X-Device-Token` の両方を照合 |
| API v1 | Bearer / `X-Api-Token`。プロジェクト・スコープ・有効期限・失効を検証 |
| Kitchen / Waiting | Sanctum Bearer と端末固有鍵、店舗・アプリ能力、発行者の現在の権限 |
| 外部 Webhook | LINE / Stripe の署名、スマレジの契約通知トークン / POS 受信秘密値 |

`project_id` と `store_id` の境界、所有者、機能権限をそれぞれ確認します。トークンや公開店舗キーがあるだけで任意の会員データへアクセスできる設計にしません。構造は [アーキテクチャ](ARCHITECTURE.md)、店舗端末の登録と失効は [運用](OPERATIONS.md#kitchen--waiting-の端末登録) を参照します。

管理ログインは試行制限を適用します。管理パスワードは hashed cast、API トークンは SHA-256、顧客端末の秘密値は暗号化・JSON 非公開として保存します。管理者 seed の固定パスワードへのフォールバック、本番のデモ seed、再 seed による既存パスワード・ロールの上書きは禁止します。

## CSRF・入力・出力

`web` グループの Cookie 認証 API とフォームは CSRF 検証を行います。Blade は `@csrf`、LIFF / ネイティブ WebView の `ApiClient` は同一オリジンへ `X-CSRF-TOKEN` を送ります。CSRF トークンを外部オリジンへ送信しません。

`api/*` 全体を検証対象から外しません。外部 Webhook は署名などの専用認証を前提に除外します。`web` を使用しない Bearer API は Cookie の本人状態へ依存させません。独自クライアントが Cookie 認証 API を使う場合も CSRF トークンが必要です。

FormRequest / HTTP 境界で型・範囲を検証し、業務側で所有者・状態・整合性を確認します。Blade の `{{ }}`、React の通常描画、`textContent` を優先し、HTML を許可する場合は専用の検査を通します。

## 機密情報と本番

`.env`、鍵・証明書、サービスアカウント JSON、Bearer・端末トークン、署名付きURLを Git・文書・ログへ残しません。監査ログのマスキングは新しい秘密項目を追加した際も確認します。外部エラーの詳細や内部パスはクライアントへ返しません。

本番は HTTPS、`APP_ENV=production`、`APP_DEBUG=false` を使用し、Secure Cookie、公開ルート、ファイル権限を確認します。API トークンは期限・用途を限定し、紛失・担当変更時は失効させます。環境設定は [デプロイ](DEPLOY.md) に集約しています。

## 回帰確認

| 対象 | 主なテスト |
|---|---|
| 未認証アクセス・会員・端末・決済境界 | `SecurityTest` / `SecurityBoundaryTest` |
| 管理者 seed | `AdminSeederSecurityTest` |
| 実際の CSRF 検証 | `ApiCsrfSecurityTest` |
| 複数店舗の閲覧・操作・配信 | `MultiStoreTest` |
| 店舗端末の認証・発行・失効 | `StoreAppAuthenticationTest` / `AdminSettingsTest` |
| 特典取消と再実行 | `StampMilestoneRewardTest` など |

実行方法は [開発手順](DEVELOPMENT.md) を参照します。依存関係は `composer audit` / `npm audit` で確認します。過去のレビューで問題がなかったことを、現在の安全性や本番設定の証明として扱いません。
