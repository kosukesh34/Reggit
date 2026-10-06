# 店舗用 iPad アプリ

SwiftUI / iPadOS 18 以降。Xcode で各プロジェクトを開いてビルドします。

| アプリ | プロジェクト | 用途 |
| --- | --- | --- |
| Reggit Wait | `WaitingSystem.xcodeproj` | 順番待ち受付、QR 表示、AirPrint、待ち状況表示 |
| Reggit Kitchen | `KitchenMonitor.xcodeproj` | 注文一覧、準備・受取・完了操作、FCM 通知 |

各アプリの `Core/Constants/AppBuildConfig.swift` の `serverTarget` で本番・ローカルを切り替えます。本番 URL は `https://reggit.jp`。ローカル URL は実機から届くアドレスに設定してください。

管理画面で店舗・アプリごとの登録コードを発行し、初回起動で入力します。認証情報は Keychain に保存します。端末登録・失効・操作仕様は [運用ガイド](../docs/OPERATIONS.md#kitchen--waiting-の端末登録)、バックエンドの設定は [外部連携](../docs/INTEGRATIONS.md) を参照してください。

API の定義は [routes/api.php](../routes/api.php) と各アプリの API クライアントを正とします。LINE / LIFF 設定は app-config から取得します。
