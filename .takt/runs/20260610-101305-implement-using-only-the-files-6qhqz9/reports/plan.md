# タスク計画

## 元の要求
Issue #50「クーポンを正常に配信できるようにする」に対し、管理画面のプレゼント配信（一括配布）で「会員を選択して配布」を選んだのに会員ID未入力だと空配列が渡され全員に配布されるバグがある。また MemberCoupon 作成時に SendCouponIssuedNotificationJob が投入されるが、ジョブ内の find が ProjectScope の影響を受けうる（SendScheduledAnnouncementJob は withoutGlobalScopes を使用）ほか、レコード未検出時にサイレント return しており LINE/プッシュ通知が届かない。一括配布フローの Feature テストも未整備のため、修正と回帰防止テストを行う。

## 分析結果

### 目的
- 管理画面からクーポンを意図した対象会員にのみ配布でき、配布完了後に発行通知（LINE / ネイティブプッシュ）が確実に処理されるようにする
- クーポン一括配布の主要フローを Feature テストでカバーし、配信経路のリグレッションを防ぐ

### 分解した要件
| # | 要件 | 種別 | 備考 |
|---|------|------|------|
| 1 | `target=selected` で会員ID入力が空の場合、バリデーションエラーとなる | 明示 | `member_coupons` が作成されないこと |
| 2 | `target=selected` で会員IDが無効のみ（非数値・0のみ）の場合、バリデーションエラーとなる | 明示 | パース後の配列が空になるケース |
| 3 | `target=selected` でバリデーションエラー時、全員配布にフォールバックしない | 暗黙 | #1/#2 から導出。現行は空配列が `issueCouponToMembers` に渡り全員配布される |
| 4 | POST `admin/coupons/bulk-issue` で全員配布したとき、対象会員の `member_coupons` が作成される | 明示 | Feature テストで検証 |
| 5 | 各 `member_coupons` 作成ごとに `SendCouponIssuedNotificationJob` がキュー投入される | 明示 | Feature テストで検証 |
| 6 | `SendCouponIssuedNotificationJob` は `MemberCoupon::withoutGlobalScopes()->find()` で取得する | 明示 | `SendScheduledAnnouncementJob.php:24` と同パターン |
| 7 | ジョブ dispatch に `afterCommit` を適用する | 明示 | `MemberCouponObserver` の dispatch 箇所 |
| 8 | ジョブ実行時に `member_coupons` が見つからない場合はログを残す | 明示 | 現行はサイレント return（`SendCouponIssuedNotificationJob.php:24-26`） |
| 9 | ジョブ実行時に `member_coupons` が見つかった場合、`CouponNotificationService::notifyIssued` が呼ばれることをテストで確認する | 明示 | |
| 10 | `docker compose exec app php artisan test` で `CouponNotificationTest` および追加した一括配布 Feature テストがすべてパスする | 明示 | 最終検証 |

### 参照資料の調査結果（参照資料がある場合）
タスク指示書 `order.md` のみ指定。「参照資料」セクションの別ファイル指定はなし。

コード内の参照パターンとして `SendScheduledAnnouncementJob` が言及されている。調査結果:
- `SendScheduledAnnouncementJob.php:24` は `Announcement::withoutGlobalScopes()->find()` を使用
- `SendScheduledAnnouncementJob` には `afterCommit` は未適用（`app/` 全体で `afterCommit` の使用箇所なし）
- 受け入れ条件は `withoutGlobalScopes` を `SendScheduledAnnouncementJob` と同パターン、`afterCommit` は `MemberCouponObserver` に明示適用と読み分ける

### スコープ
**変更対象ファイル（6ファイル）:**

| ファイル | 変更内容 |
|---------|---------|
| `app/Http/Requests/Admin/CouponBulkIssueRequest.php` | `parsedMemberIds()` 追加、`withValidator()` で `target=selected` 時のバリデーション |
| `app/Http/Controllers/Admin/CouponController.php` | インラインパースを `$request->parsedMemberIds()` に置換 |
| `app/Jobs/SendCouponIssuedNotificationJob.php` | `withoutGlobalScopes()->find()` + 未検出時ログ |
| `app/Observers/MemberCouponObserver.php` | `dispatch()->afterCommit()` |
| `tests/Feature/AdminCouponBulkIssueTest.php` | 新規作成 |
| `tests/Feature/CouponNotificationTest.php` | ジョブ実行テスト追加 |

**バグの原因箇所:**
- `CouponController.php:159-163` — `target=selected` でもパース結果が空配列のまま UseCase に渡る
- `MemberAdminService.php:191-198` — `$memberIds` が空の場合は全員に配布（`target=all` では正しい挙動）

**ProjectScope の影響:**
- `MemberCoupon` は `BelongsToProject` trait により `ProjectScope` が付与（`app/Models/Concerns/BelongsToProject.php:12`）
- `ProjectScope` は `AdminContext::getCurrentProjectId()` が設定されている場合のみ `project_id` で絞り込む（`app/Models/Scopes/ProjectScope.php:14-17`）
- キューワーカー実行時にコンテキストが異なると `find()` が失敗しうる

### 検討したアプローチ（設計判断がある場合）
| アプローチ | 採否 | 理由 |
|-----------|------|------|
| バリデーションを `CouponBulkIssueRequest` の `withValidator()` に実装 | 採用 | `AnnouncementDeliveryStoreRequest.php:29-35` と同種の条件付きバリデーションパターンが既存 |
| バリデーションを Controller に実装 | 不採用 | Controller は薄く保つ。Request 層が責務 |
| `MemberAdminService` の空配列＝全員ロジックを削除 | 不採用 | `target=all` フローで空配列が意図的に使われる（`CouponController.php:158`） |
| パースロジックを Request の `parsedMemberIds()` に一元化 | 採用 | Controller とバリデーションの二重実装を防ぐ |
| 未検出時ログに `LogHelper::warning` を使用 | 採用 | `CouponNotificationService.php:39` と同プロジェクト慣習 |
| `SendScheduledAnnouncementJob` にも `afterCommit` を追加 | 不採用 | タスク指示書のスコープ外 |

### 実装アプローチ

**1. バリデーション修正（`CouponBulkIssueRequest`）**

`parsedMemberIds(): array` を追加。現行 `CouponController.php:160-161` と同等のパース:

```php
array_values(array_filter(array_map('intval', preg_split('/[\s,]+/', trim($input)))));
```

`withValidator()` で `target === 'selected'` のとき:
1. パース結果が空 → `member_ids_input` にエラー（空文字・非数値のみ・`0` のみ）
2. パース結果はあるが、当該 `project_id` の `members` に存在する ID が0件 → エラー（存在しない ID のみ）

`project_id` は `AdminContext::getCurrentProjectId()` で取得（管理画面ミドルウェア `BindAdminProject` で設定済み）。

エラーメッセージ例:
- 空/非数値: 「会員を選択して配布では、対象会員のIDを1件以上入力してください」
- 存在しない ID のみ: 「入力された会員IDが見つかりません」

**2. Controller 修正（`CouponController::bulkIssue`）**

- `target=all` → `$memberIds = []`（変更なし）
- `target=selected` → `$memberIds = $request->parsedMemberIds()`

**3. ジョブ修正（`SendCouponIssuedNotificationJob`）**

```php
$memberCoupon = MemberCoupon::withoutGlobalScopes()->find($this->memberCouponId);
if ($memberCoupon === null) {
    LogHelper::warning('[Coupon] issued notification job: member_coupon not found', [
        'member_coupon_id' => $this->memberCouponId,
    ]);
    return;
}
$couponNotificationService->notifyIssued($memberCoupon);
```

**4. Observer 修正（`MemberCouponObserver`）**

```php
SendCouponIssuedNotificationJob::dispatch($memberCoupon->id)->afterCommit();
```

**5. テスト追加**

新規 `tests/Feature/AdminCouponBulkIssueTest.php`:
- 全員配布 → `member_coupons` 作成 + ジョブ N 回投入（`Queue::fake()`）
- `target=selected` + 空入力 → `assertSessionHasErrors`、DB 0件
- `target=selected` + 非数値のみ（`abc, xyz`）→ 同上
- `target=selected` + 存在しない ID のみ（`99999`）→ 同上

`CouponNotificationTest.php` に追加:
- レコードあり → `notifyIssued` が1回呼ばれる（`CouponNotificationService` モック + `$job->handle()`）
- レコードなし → `Log::fake()` で warning ログ、モックは `shouldNotReceive`
- （推奨）`AdminContext` を別プロジェクトに設定しても `withoutGlobalScopes` で取得・通知できること

**実装順序:**
1. `CouponBulkIssueRequest`
2. `CouponController`
3. `SendCouponIssuedNotificationJob`
4. `MemberCouponObserver`
5. `AdminCouponBulkIssueTest` 新規
6. `CouponNotificationTest` 追加
7. テスト実行で受け入れ条件確認

### 到達経路・起動条件（利用者向け機能の追加/変更がある場合）
| 項目 | 内容 |
|------|------|
| 利用者が到達する入口 | 管理画面メニュー「一括配布」（`admin/layout.blade.php:144`）、クーポン一覧の「一括配布」ボタン（`admin/coupons/index.blade.php:32`） |
| 更新が必要な呼び出し元・配線 | `CouponController::bulkIssue`（Request 利用に変更）、`MemberCouponObserver`（全 `MemberCoupon::create` 経路に影響） |
| 起動条件 | 管理画面ログイン（`admin` ガード）、`coupons.manage` 権限（スタッフ以外は全権限、`CheckAdminPermission.php:24-26`）、ルート `POST /admin/coupons/bulk-issue`（`admin.coupons.bulk-issue.store`） |
| 未対応項目 | なし（UI 変更はスコープ外、バックエンドのバリデーション・通知経路のみ修正） |

## 実装ガイドライン（設計が必要な場合のみ）

**参照すべき既存実装パターン:**

| 用途 | 参照箇所 |
|------|---------|
| 条件付きバリデーション | `AnnouncementDeliveryStoreRequest.php:29-35, 68-79` |
| `withValidator` + `after` | `StampStoreRequest.php:34-54` |
| 管理画面 Feature テスト | `AdminAnnouncementsTest.php:56-107` |
| ジョブキュー検証 | `CouponNotificationTest.php:39-57` |
| Job 内 `withoutGlobalScopes` | `SendScheduledAnnouncementJob.php:24` |
| ログ出力 | `CouponNotificationService.php:39-42`（`LogHelper::warning`） |

**変更の影響範囲:**
- `parsedMemberIds()` 追加に伴い `CouponController::bulkIssue` のパース行（159-161）のみ変更。UseCase / Service は変更不要
- `MemberCouponObserver` の `afterCommit` は一括配布・個別配布・お知らせ連動配布の全 `MemberCoupon::create` 経路に影響（意図した改善）

**アンチパターン（避けること）:**
1. Controller にバリデーションロジックを書く — Request 層に集約
2. `MemberAdminService` の空配列＝全員ロジックを削除 — `target=all` で必要
3. `SendScheduledAnnouncementJob` に `afterCommit` を追加 — スコープ外
4. 未検出時のサイレント return を維持 — 必ず `LogHelper::warning`
5. パースロジックの二重実装 — `parsedMemberIds()` に一元化

**検証コマンド:**
```bash
docker compose exec app php artisan test --filter=CouponNotificationTest
docker compose exec app php artisan test --filter=AdminCouponBulkIssueTest
```

## スコープ外（項目がある場合のみ）
| 項目 | 除外理由 |
|------|---------|
| `bulk-issue.blade.php` の UI 変更 | タスク指示書に含まれない |
| `MemberAdminService::issueCouponToMembers` の空配列＝全員ロジック変更 | `target=all` で必要な既存挙動 |
| `SendScheduledAnnouncementJob` への `afterCommit` 追加 | タスク指示書の対象外 |
| `coupon_id` の project スコープバリデーション | 既存実装の問題だが本タスクの受け入れ条件に含まれない |

## 確認事項（あれば）
なし