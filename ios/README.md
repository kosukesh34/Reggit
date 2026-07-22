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

### 向け先・プロジェクト（ビルド時固定）

接続先とプロジェクトは実行時には変更できません。ビルド前に次のファイルだけ編集します。

- **ファイル**: `WaitingSystem/Core/Constants/AppBuildConfig.swift`

```swift
// 本番
static let serverTarget: AppServerTarget = .production
static let projectKey = "sb_skt733n4"

// ローカルに切り替えるとき
static let serverTarget: AppServerTarget = .local
// 必要なら localBaseURL を ngrok 等に変更
```

| 項目 | production | local |
|------|------------|-------|
| API URL | `https://reggit.jp` | `http://localhost:8080`（変更可） |
| プロジェクト | `projectKey` の値 | 同左 |
| LINE / LIFF | API から自動取得 | 同左 |

設定画面では向け先・API URL・プロジェクト・API取得の LINE 情報は表示のみです（プリンター・パスコード等は従来どおり編集可）。
