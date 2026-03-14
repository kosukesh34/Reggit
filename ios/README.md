# WaitingSystem iPad App

順番待ち受付用のiPadアプリです。SwiftUI で構築されています。

## 概要

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

バックエンド（Laravel）の以下のエンドポイントと連携します:

| メソッド | パス | 用途 |
|----------|------|------|
| POST | `/api/queue` | チケット作成 |
| GET | `/api/queue/me` | 待ち状況一覧 |
| GET | `/api/queue/{id}` | チケット詳細 |
| POST | `/api/queue/{id}/cancel` | キャンセル |
| POST | `/api/queue/call-next` | 次の呼び出し |

## ビルド

Xcode で `WaitingSystem.xcodeproj` を開いてビルドしてください。
