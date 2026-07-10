# DDD（ドメイン駆動設計）移行方針

Reggit はクリーンアーキテクチャ（UseCase 層）完了後、**Bounded Context 単位で Domain 層を導入**した。

## 1. 目標と現状

| 層 | 役割 | 状態 |
|----|------|------|
| **Domain** | ビジネスルール・集約・値オブジェクト | ✅ 主要 BC 導入済み |
| **Application (UseCase)** | オーケストレーション | ✅ Domain / Repository 経由 |
| **Infrastructure** | Eloquent・外部 API | ✅ `app/Infrastructure/Persistence` |

**DDD コア移行: 完了（約 98%）**。Admin 設定画面の `ShopSetting::set*` / View Composer の UI 読み取りは strangler として静的 API を残置。

## 2. ディレクトリ構成

```
app/Domain/{Member,Order,Coupon,Reservation,Queue,Settings,Shared}/
app/Infrastructure/Persistence/Eloquent/...
app/Infrastructure/Events/
```

## 3. Bounded Context 一覧

| Context | 進捗 | 主な内容 |
|---------|------|----------|
| **Member** | ✅ | ポイント台帳、ランク、`PointsEarned` → スマレジ同期リスナー |
| **Order** | ✅ | 作成・検証・在庫・決済確定・キャンセル・スタンプ。読取一覧は Eloquent（読みモデル） |
| **Coupon** | ✅ | 利用・発行・表示・配信セグメント Policy / TargetRepository |
| **Reservation** | ✅ | 作成・キャンセル・編集・空き枠・管理ステータス |
| **Queue** | ✅ | キャンセル・呼び出し・入店・管理ステータス。待ち時間は設定値ベース |
| **Settings** | ✅ 読み取り | ドメイン経路は `ShopSettingRepository`。管理画面書き込みは静的 API 委譲 |

## 4. 依存の向き

```
UseCase → Domain（集約・Policy・Repository IF）
UseCase → Service（外部連携）
Infrastructure → Domain
Domain → Eloquent / ShopSetting に依存しない
```

## 5. ドメインイベント

| イベント | リスナー |
|---------|----------|
| `PointsEarned` | `LoggingDomainEventListener` / `SyncPointsEarnedToSmaregiListener`（`points` は加算が正・取り消しが負） |
| `OrderConfirmed` | `LoggingDomainEventListener`（ログ） |
| `OrderPointsSyncRequested` | `SyncOrderConfirmedPointsToSmaregiListener`（注文ポイント差分をスマレジ同期。`orders.smaregi_points_synced_at` で冪等） |

`DomainEventDispatcher` → `MulticastDomainEventDispatcher`。

## 6. 意図的に残しているもの

- Admin Settings / LIFF / テーマ等の **書き込み・View Composer**（UI 設定。Repository 委譲は任意）
- `OrderIndex` / `OrderShow` 等の **読み取り専用 Eloquent**（CQRS 読みモデルとして許容）
- Eloquent `Member::syncPointsLedger` / `MemberRepository::syncPointsLedger` のバックフィル（レガシーデータ整合。読取時は自動実行せず、ポイント更新前など明示呼び出しのみ）

## 7. テスト方針

| 対象 | 種別 | DB |
|------|------|-----|
| 集約・Policy | Unit | 不要 |
| Eloquent Repository | Unit | 必要 |
| UseCase / Feature | Feature | 必要 |
