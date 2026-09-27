# Reggit Wait（iPad 順番待ち受付）

順番待ち受付用の iPad アプリです。SwiftUI で構築されています。

## 概要

- **表示名**: Reggit Wait
- **プラットフォーム**: iPadOS 18.0+
- **言語**: Swift 5
- **UI**: SwiftUI
- **アーキテクチャ**: MVVM

## 機能

- 順番待ちの受付（人数選択 → チケット発行）
- QRコード表示（LINE LIFF 連携）
- 受付票の印刷（AirPrint）
- 待ち状況のリアルタイム表示
- 管理者パスコードによるセキュリティ

## API 連携

バックエンド（Laravel）の以下のエンドポイントと連携します。

| メソッド | パス | 用途 |
|----------|------|------|
| GET | `/api/v1/app-config?project_key=` | 店舗名・LINE/LIFF 設定の取得 |
| POST | `/api/queue` | チケット作成 |
| GET | `/api/queue/me` | 待ち状況一覧 |
| GET | `/api/queue/{id}` | チケット詳細 |
| POST | `/api/queue/{id}/cancel` | キャンセル |
| POST | `/api/queue/call-next` | 次の呼び出し |

本番の API ベース URL は `https://reggit.jp` です。LINE / LIFF 設定はアプリに手入力せず、上記 app-config から受け取ります。

## ビルド

Xcode で `WaitingSystem.xcodeproj` を開いてビルドしてください。

### 店舗端末の登録

認証・1端末制約・API・スワイプ操作の正式仕様は [`../docs/STORE_APP_SPEC.md`](../docs/STORE_APP_SPEC.md) を参照してください。

同じApp Storeビルドをすべての店舗で利用します。店舗オーナーが管理画面の基本設定でKitchen／Waitingごとの登録コードを発行し、初回起動時に入力します。各店舗・各アプリにつき登録できる端末は1台です。

- 接続先だけは `WaitingSystem/Core/Constants/AppBuildConfig.swift` の `serverTarget` で切り替えます。
- 登録コードは一度だけ使用でき、サーバーにはハッシュだけを保存します。
- 認証トークンと端末鍵はKeychainに保存され、期限はありません。APIは両方の一致を検証します。
- 管理画面でコードを再発行すると、登録済み端末は即時失効します。

```swift
// 本番
static let serverTarget: AppServerTarget = .production
// ローカルに切り替えるとき
static let serverTarget: AppServerTarget = .local
// 必要なら localBaseURL を ngrok 等に変更
```

| 項目 | production | local |
|------|------------|-------|
| API URL | `https://reggit.jp` | `http://localhost:8080`（変更可） |
| プロジェクト | ログインした店舗 | ログインした店舗 |
| LINE / LIFF | API から自動取得 | 同左 |

設定画面ではログイン中のプロジェクトを表示します（プリンター・パスコード等は従来どおり編集可）。

---

# Reggit Kitchen（iPad キッチンモニター）

モバイル注文の未完了一覧を表示し、準備中／受取可／完了へ進める店舗向け iPad アプリです。

## 概要

- **表示名**: Reggit Kitchen
- **プロジェクト**: `KitchenMonitor.xcodeproj`
- **Bundle ID**: `gamechanger.IDNect.KitchenMonitor`
- **プラットフォーム**: iPadOS 18.0+

## 機能

- 未完了注文ボード（引換番号・品目・数量）
- ステータス操作（準備中 → 受取可 → 完了）
- 8秒ポーリング＋新規注文時の音
- Push 通知（FCM。端末は Firebase Messaging の registration token を登録）
- 管理パスコード／スリープ防止

## セットアップ

1. Xcode で `KitchenMonitor.xcodeproj` を開き、必要なら `AppBuildConfig.swift` の `serverTarget` を設定
2. 管理画面で端末登録コードを発行し、実機の初回起動画面へ入力

## API

| メソッド | パス | 用途 |
|----------|------|------|
| GET | `/api/kitchen/{project_key}/orders` | 未完了一覧 |
| POST | `/api/kitchen/{project_key}/orders/{id}/status` | ステータス更新 |
| POST | `/api/kitchen/{project_key}/devices` | Push トークン登録 |

認証: 一度きりの登録コードで発行された無期限Bearerトークンと端末固有鍵。両方ともアプリ種別とプロジェクトIDに固定されます。
