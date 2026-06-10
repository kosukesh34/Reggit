# タスク計画

## 元の要求

GitHub Issue #56: OneSignal Collapse ID の実装。通知を取り消せるようにする（LINE のような挙動）。

## 分析結果

### 目的

OneSignal 経由のプッシュ通知に Collapse ID を付与し、同一 ID による通知置換を可能にする。お知らせ削除時には配信済み通知を取り消せるようにする。

### 分解した要件

| # | 要件 | 種別 | 備考 |
|---|------|------|------|
| 1 | OneSignal 送信 JSON に `collapse_id` を付与する | 明示 | `sendOneSignalPush` に現状なし（`PushNotificationService.php:199-206`） |
| 2 | 通知を取り消せるようにする | 明示 | Issue #56 本文・`order.md` 背景 |
| 3 | お知らせ削除時にプッシュ取り消しを実行する | 暗黙 | R2 + お知らせが主要配信経路 |
| 4 | 全 OneSignal 送信経路に Collapse ID を付与する | 暗黙 | R1 + 単一実装 `sendOneSignalPush` 経由 |
| 5 | Collapse ID に `project_id` を含める | 明示 | `order.md` マルチテナント制約 |
| 6 | `docs/CLEAN_ARCHITECTURE.md` に従う | 明示 | 外部 API は Service、削除オーケストレーションは UseCase |
| 7 | UseCase 連鎖なし | 明示 | DeleteUseCase → PushNotificationService 直接注入 |
| 8 | 既存通知フローを壊さない | 明示 | オプショナル引数・条件付き JSON フィールド追加のみ |
| 9 | OneSignal 無効時は no-op | 暗黙 | R8 + 既存 `isOneSignalEnabled()` パターン |
| 10 | `docker compose exec app php artisan test` で検証 | 明示 | 新規 Unit テスト追加 |

### 参照資料の調査結果（参照資料がある場合）

| 資料 | 状態 |
|------|------|
| `order.md`（Primary spec） | 確認済み |
| `docs/CLEAN_ARCHITECTURE.md` | 確認済み。外部 API は Service 層、削除オーケストレーションは UseCase 層で実装 |
| GitHub Issue #56 | 確認済み。「通知を取り消せるようにする / LINEみたいな感じ」 |

`order.md` に「参照資料」セクションはなく、「参考」として Issue URL のみ記載。Issue 本文は `order.md` の背景と一致する。

**現状との主要な差異:**

- `PushNotificationService::sendOneSignalPush` の JSON に `collapse_id` がない
- `AdminAnnouncementDeleteUseCase` は DB 削除のみでプッシュ取り消しがない
- OneSignal 送信は `sendAnnouncement` / `sendToUser` / `sendQueueCalledToUser` の 3 経路がすべて `sendOneSignalPush` に集約されている

### スコープ

**変更対象:**

| ファイル | 変更内容 |
|---------|---------|
| `app/Support/PushCollapseIdBuilder.php` | 新規 — Collapse ID 生成 |
| `app/Services/PushNotificationService.php` | `collapse_id` 付与、取り消しメソッド追加 |
| `app/UseCases/Admin/Announcement/AdminAnnouncementDeleteUseCase.php` | 削除前に取り消しプッシュ |
| `app/Services/CouponNotificationService.php` | `sendToUser` に collapse ID 渡し |
| `tests/Unit/PushCollapseIdBuilderTest.php` | 新規 |
| `tests/Unit/PushNotificationServiceTest.php` | 新規 |
| `tests/Unit/AdminAnnouncementDeleteUseCaseTest.php` | 新規 |
| `tests/Feature/CouponNotificationTest.php` | `sendToUser` モック期待値更新 |

**配線が必要な箇所:**

| 層 | 箇所 |
|----|------|
| Builder | `PushCollapseIdBuilder` 3 static メソッド |
| Service | `sendOneSignalPush`, `sendAnnouncementViaOneSignal`, `sendQueueCalledToUser`, `sendToUser`, `cancelAnnouncementPush`, `cancelOneSignalPush` |
| UseCase | `AdminAnnouncementDeleteUseCase` |
| 呼び出し元 | `CouponNotificationService:60-72` |

### 検討したアプローチ（設計判断がある場合）

| アプローチ | 採否 | 理由 |
|-----------|------|------|
| `PushCollapseIdBuilder` を `app/Support/` に新設 | 採用 | `CouponLiffUrlBuilder` と同パターン。ID 生成ロジックを Service から分離 |
| Collapse ID 形式 `{project_id}:{type}:{entity_id}` | 採用 | テナント分離（R5）、64 文字制限内に収まる |
| 取り消しは同一 `collapse_id` で空通知を再送 | 採用 | OneSignal に配信済み通知の直接削除 API がない。置換が標準手段 |
| 取り消し対象は `DELIVERY_STATUS_SENT` のお知らせのみ | 採用 | 未配信お知らせに取り消しプッシュは不要 |
| FCM 経路にも Collapse ID を追加 | 不採用 | OneSignal 専用タスク。FCM は別 API |
| `SendOneSignalTestCommand` にも対応 | 不採用 | タスク指示書に記載なし |
| クーポン失効・順番キャンセル時の取り消し | 不採用 | Issue に明記なし |

### 実装アプローチ

1. **`PushCollapseIdBuilder` 作成**（`app/Support/PushCollapseIdBuilder.php`）

   | メソッド | 形式 |
   |---------|------|
   | `forAnnouncement($projectId, $announcementId)` | `{projectId}:announcement:{announcementId}` |
   | `forQueueCalled($projectId, $userId)` | `{projectId}:queue_called:{userId}` |
   | `forCouponIssued($projectId, $memberCouponId)` | `{projectId}:coupon_issued:{memberCouponId}` |

2. **`PushNotificationService` 改修**
   - `sendOneSignalPush` に `?string $collapseId = null` を追加。非空時のみ JSON に `collapse_id` を付与
   - `sendAnnouncementViaOneSignal` → `forAnnouncement`
   - `sendQueueCalledToUser` → `forQueueCalled`
   - `sendToUser` に `?string $collapseId = null` を追加
   - `cancelAnnouncementPush(Announcement $announcement)` を追加:
     1. `isOneSignalEnabled()` が false なら no-op
     2. `resolveTargetTokens($announcement)` で配信先再解決
     3. 同一 Collapse ID で private `cancelOneSignalPush` を実行
   - 取り消しペイロード: `collapse_id`, `content_available: true`, 空 `headings`/`contents`, `data: ['action' => 'cancel']`

3. **`AdminAnnouncementDeleteUseCase` 改修**
   - `PushNotificationService` を注入
   - `findOrFail` → `delivery_status === DELIVERY_STATUS_SENT` なら `cancelAnnouncementPush` → `delete`

4. **`CouponNotificationService` 改修**
   - `sendToUser` 呼び出し時に `PushCollapseIdBuilder::forCouponIssued(...)` を渡す

5. **テスト追加・更新**
   - `PushCollapseIdBuilderTest` — ID 形式・`project_id` 含有
   - `PushNotificationServiceTest` — Guzzle Client モックで `collapse_id` 付与・取り消しペイロード検証
   - `AdminAnnouncementDeleteUseCaseTest` — sent 時のみ `cancelAnnouncementPush` 呼び出し
   - `CouponNotificationTest` — `sendToUser` 第 6 引数の更新

### 到達経路・起動条件（利用者向け機能の追加/変更がある場合）

| 項目 | 内容 |
|------|------|
| 利用者が到達する入口 | 管理画面 お知らせ一覧 → 削除ボタン（`DELETE admin.announcements.delete`、`routes/web.php:203`） |
| 更新が必要な呼び出し元・配線 | `AnnouncementController::deleteAnnouncement` → `AdminAnnouncementDeleteUseCase` → `PushNotificationService::cancelAnnouncementPush`（Controller は変更不要） |
| 起動条件 | `admin.permission:announcements.manage` 権限、`delivery_status === sent` のお知らせ削除時のみ取り消しプッシュ実行 |
| 未対応項目 | なし（本番ルートは `AnnouncementController` 経由。未使用の `AdminController::deleteAnnouncement` はスコープ外） |

**送信（Collapse ID 付き）の到達経路:**

```
管理画面 配信登録 → AdminAnnouncementStoreDeliveryUseCase → sendAnnouncement
クーポン発行 → CouponNotificationService → sendToUser
順番呼出 → QueueAdminService → sendQueueCalledToUser
ポイント期限リマインダー → PointExpiryReminderService → sendAnnouncement
```

## 実装ガイドライン（設計が必要な場合のみ）

- **参照パターン**
  - OneSignal HTTP 送信: `PushNotificationService::sendOneSignalPush` `:192-207`
  - 配信先トークン解決: `resolveTargetTokens` `:234-267`
  - Support クラス: `app/Support/CouponLiffUrlBuilder.php`
  - UseCase + Service 注入: `AdminAnnouncementStoreDeliveryUseCase:18-24`
  - Support Unit テスト: `tests/Unit/QueueLiffUrlBuilderTest.php`
  - Service モック Feature テスト: `tests/Feature/CouponNotificationTest.php:86-98`

- **実装手順**
  1. `PushCollapseIdBuilder` 作成
  2. `PushNotificationService` 改修（送信 + 取り消し）
  3. `AdminAnnouncementDeleteUseCase` 改修
  4. `CouponNotificationService` 改修
  5. Unit テスト 3 ファイル追加、`CouponNotificationTest` 更新
  6. `docker compose exec app php artisan test --filter=PushCollapseIdBuilderTest`
  7. `docker compose exec app php artisan test --filter=PushNotificationServiceTest`
  8. `docker compose exec app php artisan test --filter=AdminAnnouncementDeleteUseCaseTest`
  9. `docker compose exec app php artisan test --filter=CouponNotificationTest`

- **アンチパターン（禁止）**
  - UseCase から別 UseCase を呼ぶ
  - `SendOneSignalTestCommand` に OneSignal HTTP ロジックを複製する
  - `collapse_id` を `data` ペイロード（クライアント向け）に含める
  - FCM 経路に Collapse ID 相当を追加する
  - OneSignal 無効時に例外を投げる
  - Controller に取り消しロジックを書く

## スコープ外（項目がある場合のみ）

| 項目 | 除外理由 |
|------|---------|
| クーポン失効・順番キャンセル時のプッシュ取り消し | Issue に明記なし |
| モバイルアプリ側 `data.action === 'cancel'` ハンドラ | `order.md` に記載なし |
| `SendOneSignalTestCommand` への `--collapse-id` オプション | タスク指示書に記載なし |
| FCM 経路の通知グルーピング | OneSignal 専用タスク |
| `AdminController::deleteAnnouncement` の修正 | ルート未使用（`routes/web.php:203` は `AnnouncementController` を参照） |

## 確認事項（あれば）

なし。