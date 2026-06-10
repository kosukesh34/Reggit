タスク指示書と参照資料を読み、現行実装を調査して実装方針を立てます。
OneSignal と通知配信の既存実装を調査します。
実装コードとアーキテクチャドキュメントを直接確認します。
## 計画レポート: OneSignal Collapse ID 実装（Issue #56）

### 参照資料の確認

| 資料 | 状態 |
|------|------|
| `.takt/runs/20260610-071338-implement-using-only-the-files-9cmv45/context/task/order.md` | 確認済み（Primary spec） |
| `docs/CLEAN_ARCHITECTURE.md` | 確認済み（`order.md` 実装手順で指定） |
| GitHub Issue #56 | 確認済み（`gh issue view 56`） |

`order.md` に「参照資料」セクションはなく、「参考」として Issue URL のみ記載。Issue 本文は「通知を取り消せるようにする / LINEみたいな感じ」であり、`order.md` の背景と一致する。

---

### 要件分析

#### 明示要求（order.md + Issue #56）

| # | 要件 | 変更要否 | 根拠 |
|---|------|----------|------|
| R1 | OneSignal 通知に Collapse ID を付与する | **要** | `sendOneSignalPush` の JSON に `collapse_id` なし（`app/Services/PushNotificationService.php:199-206`） |
| R2 | 通知を取り消せるようにする（LINE 的挙動） | **要** | Issue #56 本文・`order.md` 背景。Collapse ID による置換＋削除時の再送が必要 |
| R3 | `docs/CLEAN_ARCHITECTURE.md` に従う | **要** | 外部 API は Service、削除オーケストレーションは UseCase |
| R4 | UseCase 連鎖なし | **制約** | DeleteUseCase → PushNotificationService を直接注入 |
| R5 | マルチテナント `project_id` スコープ | **要** | Collapse ID 形式に `project_id` を必須含有 |
| R6 | 既存通知フローを壊さない | **制約** | オプショナル引数・条件付き JSON フィールド追加のみ |
| R7 | `docker compose exec app php artisan test` で検証 | **要** | 新規 Unit テスト追加 |

#### 暗黙要求（明示要求から直接導出）

| 要件 | 根拠 | 変更要否 |
|------|------|----------|
| お知らせ削除時にプッシュ取り消し | R2 + お知らせが主要配信経路（`AdminAnnouncementStoreDeliveryUseCase:154`） | **要** |
| 全 OneSignal 送信経路に Collapse ID | R1 + 単一実装 `sendOneSignalPush` 経由 | **要** |
| OneSignal 無効時は no-op | R6 + 既存 `isOneSignalEnabled()` パターン（`:269-276`） | **要** |

#### 変更不要

| 項目 | 根拠 |
|------|------|
| FCM 経路 | `sendFcmChunk` / `sendFcmChunkToUser`（`:278-407`）は別 API。OneSignal 専用タスク |
| モバイルアプリ | `order.md` に記載なし。OS 側マッピングは OneSignal が担当 |
| `SendOneSignalTestCommand` | タスク指示書に記載なし |
| `AdminController::deleteAnnouncement` | ルート未使用（`routes/web.php:203` は `AnnouncementController` を参照） |
| DB スキーマ変更 | Collapse ID は送信時に決定する派生値 |

---

### 現状調査

#### OneSignal 送信の集約点

`PushNotificationService` が唯一の本番送信実装:

| 公開メソッド | 呼び出し元 | 用途 |
|-------------|-----------|------|
| `sendAnnouncement` | 配信 UseCase / Jobs / `PointExpiryReminderService:82` | お知らせ |
| `sendToUser` | `CouponNotificationService:60` | クーポン通知 |
| `sendQueueCalledToUser` | `QueueAdminService:93` | 順番呼出 |

すべて `sendOneSignalPush`（`:174-231`）に集約。API URL は `config/push.php:14`。

#### 取り消しフローの現状

`AdminAnnouncementDeleteUseCase`（`:16-19`）は `AnnouncementAdminService::delete` のみ。プッシュ取り消しなし。

---

### 設計方針

#### Collapse ID 形式

`App\Support\PushCollapseIdBuilder` を新設（`CouponLiffUrlBuilder` と同パターン）:

```
{project_id}:{type}:{entity_id}
```

| type | 生成メソッド | 例 |
|------|------------|-----|
| `announcement` | `forAnnouncement(int $projectId, int $announcementId)` | `1:announcement:42` |
| `queue_called` | `forQueueCalled(int $projectId, int $userId)` | `1:queue_called:99` |
| `coupon_issued` | `forCouponIssued(int $projectId, int $memberCouponId)` | `1:coupon_issued:15` |

- `project_id` 先頭でテナント分離（R5）
- 想定 ID 長は Android `collapse_key` 64 文字制限内（`project_id` + `type` + `entity_id` は通常十分短い）

#### 送信時の変更

`sendOneSignalPush` に `?string $collapseId = null` を追加。非空時のみ JSON に `collapse_id` を付与:

```php
// PushNotificationService.php sendOneSignalPush 内 (:199-206 付近)
'json' => array_filter([
    'app_id' => $appId,
    'include_subscription_ids' => $chunk,
    'target_channel' => 'push',
    'headings' => ['en' => $title],
    'contents' => ['en' => $body],
    'data' => $data,
    'collapse_id' => $collapseId !== null && $collapseId !== '' ? $collapseId : null,
], fn ($v) => $v !== null),
```

各公開メソッドでの Collapse ID 割当:

- `sendAnnouncementViaOneSignal` → `PushCollapseIdBuilder::forAnnouncement($announcement->project_id, $announcement->id)`
- `sendQueueCalledToUser` → `PushCollapseIdBuilder::forQueueCalled($projectId, $userId)`
- `sendToUser` → 末尾に `?string $collapseId = null` 追加（呼び出し元が指定）

#### 取り消し（お知らせ削除時）

`PushNotificationService::cancelAnnouncementPush(Announcement $announcement): array` を追加:

1. `isOneSignalEnabled()` が false なら no-op（既存 skipped 形式を返す）
2. `resolveTargetTokens($announcement)` で配信先を再解決（`:234-267` を再利用）
3. 送信時と同一 Collapse ID で private `cancelOneSignalPush` を実行
4. 取り消しペイロード:

```php
'json' => [
    'app_id' => $appId,
    'include_subscription_ids' => $chunk,
    'target_channel' => 'push',
    'collapse_id' => $collapseId,
    'content_available' => true,
    'headings' => ['en' => ''],
    'contents' => ['en' => ''],
    'data' => ['action' => 'cancel'],
],
```

**プラットフォーム制約**: OneSignal に配信済み通知を直接削除する API はない。同一 `collapse_id` による置換が標準手段。OS によっては空通知が一瞬表示される可能性あり（モバイル側ハンドラ未実装のため v1 では許容）。

#### UseCase 変更

`AdminAnnouncementDeleteUseCase` に `PushNotificationService` を注入:

```php
public function handle(int $id): void
{
    $announcement = $this->announcementAdminService->findOrFail($id);

    if ($announcement->delivery_status === Announcement::DELIVERY_STATUS_SENT) {
        $this->pushNotificationService->cancelAnnouncementPush($announcement);
    }

    $this->announcementAdminService->delete($id);
}
```

`AnnouncementController::deleteAnnouncement`（`:76-81`）は変更不要。

---

### 影響範囲

| ファイル | 変更内容 |
|---------|---------|
| `app/Support/PushCollapseIdBuilder.php` | **新規** — Collapse ID 生成 |
| `app/Services/PushNotificationService.php` | `collapse_id` 付与、取り消しメソッド追加 |
| `app/UseCases/Admin/Announcement/AdminAnnouncementDeleteUseCase.php` | 削除前に取り消しプッシュ |
| `app/Services/CouponNotificationService.php` | `sendToUser` に collapse ID 渡し |
| `tests/Unit/PushCollapseIdBuilderTest.php` | **新規** |
| `tests/Unit/PushNotificationServiceTest.php` | **新規** — Guzzle Client モック |
| `tests/Unit/AdminAnnouncementDeleteUseCaseTest.php` | **新規** |
| `tests/Feature/CouponNotificationTest.php` | `sendToUser` モック期待値の更新（6 引数目） |

#### 配線が必要な箇所

| 層 | 箇所 |
|----|------|
| Builder | `PushCollapseIdBuilder` 3 static メソッド |
| Service | `sendOneSignalPush`, `sendAnnouncementViaOneSignal`, `sendQueueCalledToUser`, `sendToUser`, `cancelAnnouncementPush`, `cancelOneSignalPush` |
| UseCase | `AdminAnnouncementDeleteUseCase` |
| 呼び出し元 | `CouponNotificationService:60-72` |

---

### 実装ガイドライン（Coder 向け）

#### 参照すべき既存パターン

| パターン | 参照箇所 |
|---------|---------|
| OneSignal HTTP 送信 | `PushNotificationService::sendOneSignalPush` `:192-207` |
| 配信先トークン解決 | `resolveTargetTokens` `:234-267` |
| Support クラス配置 | `app/Support/CouponLiffUrlBuilder.php` |
| UseCase + Service 注入 | `AdminAnnouncementStoreDeliveryUseCase:18-24` |
| Support クラス Unit テスト | `tests/Unit/QueueLiffUrlBuilderTest.php` |
| Service モック Feature テスト | `tests/Feature/CouponNotificationTest.php:86-98` |

#### 実装手順

1. `PushCollapseIdBuilder` 作成（`forAnnouncement`, `forQueueCalled`, `forCouponIssued`）
2. `PushNotificationService` 改修
   - `sendOneSignalPush(..., ?string $collapseId = null)`
   - 各送信メソッドから Builder 経由で ID 付与
   - `cancelOneSignalPush` / `cancelAnnouncementPush` 追加
3. `AdminAnnouncementDeleteUseCase` 改修
4. `CouponNotificationService` で `PushCollapseIdBuilder::forCouponIssued(...)` を `sendToUser` に渡す
5. Unit テスト 3 ファイル追加、`CouponNotificationTest` 更新
6. `docker compose exec app php artisan test --filter=PushCollapseIdBuilderTest`
7. `docker compose exec app php artisan test --filter=PushNotificationServiceTest`
8. `docker compose exec app php artisan test --filter=AdminAnnouncementDeleteUseCaseTest`
9. `docker compose exec app php artisan test --filter=CouponNotificationTest`

#### アンチパターン（禁止）

- UseCase から別 UseCase を呼ぶ（連鎖禁止）
- `SendOneSignalTestCommand` に OneSignal HTTP ロジックを複製する
- `collapse_id` を `data` ペイロード（クライアント向け）に含める
- FCM 経路に Collapse ID 相当を無理に追加する
- OneSignal 無効時に例外を投げる（既存は skipped/no-op）
- Controller に取り消しロジックを書く

#### 利用者向け機能の到達経路

**取り消し**:

```
管理画面 お知らせ一覧
  → DELETE admin.announcements.delete (routes/web.php:203)
  → AnnouncementController::deleteAnnouncement (:76)
  → AdminAnnouncementDeleteUseCase::handle
  → PushNotificationService::cancelAnnouncementPush  ← 新規
  → AnnouncementAdminService::delete
```

**送信（Collapse ID 付き）**:

```
管理画面 配信登録 → AdminAnnouncementStoreDeliveryUseCase → sendAnnouncement
クーポン発行 → CouponNotificationService → sendToUser
順番呼出 → QueueAdminService → sendQueueCalledToUser
ポイント期限リマインダー → PointExpiryReminderService → sendAnnouncement
```

---

### テスト方針

| テスト | 検証内容 |
|--------|---------|
| `PushCollapseIdBuilderTest` | 各メソッドの ID 形式、`project_id` 含有 |
| `PushNotificationServiceTest` | Client モックで送信時 `collapse_id` が JSON に含まれること。取り消し時 `content_available` + 空 contents |
| `AdminAnnouncementDeleteUseCaseTest` | `DELIVERY_STATUS_SENT` 時に `cancelAnnouncementPush` が 1 回呼ばれる。draft 時は呼ばれない |
| `CouponNotificationTest` | `sendToUser` の第 6 引数に collapse ID が渡されること |

---

### スコープ外（今回含めない）

- クーポン失効・順番キャンセル時のプッシュ取り消し（Issue に明記なし）
- モバイルアプリ側 `data.action === 'cancel'` ハンドラ
- `SendOneSignalTestCommand` への `--collapse-id` オプション
- FCM 経路の通知グルーピング
- 未使用の `AdminController::deleteAnnouncement` の修正

---

### 確認事項

なし。Issue #56・`order.md`・コード調査から実装方針は確定可能。