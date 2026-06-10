# 決定ログ

## 1. `target=selected` のバリデーションを Request 層に集約
- **背景**: `CouponController` のインラインパースにより、空配列が `MemberAdminService` に渡り全員配布と解釈されるバグがあった
- **検討した選択肢**: Controller でガードする / `CouponBulkIssueRequest` に `parsedMemberIds()` + `withValidator()` を追加する
- **理由**: `AnnouncementDeliveryStoreRequest` 等の既存パターンに合わせ、パースと検証を Request に一元化し Controller を薄く保つ

## 2. `MemberAdminService::issueCouponToMembers` の空配列＝全員セマンティクスを維持
- **背景**: `target=all` では意図的に空配列を渡して全員配布している
- **検討した選択肢**: Service 側の空配列解釈を削除する / Service はそのままに Request で `selected` 時のみガードする
- **理由**: `target=all` の既存フローを壊さず、バグ原因（`selected` 時の空配列到達）だけを Request 層で遮断する

## 3. `afterCommit()` は `MemberCouponObserver` のみに適用
- **背景**: 受け入れ条件でジョブ dispatch への `afterCommit` が指定されている一方、`SendScheduledAnnouncementJob` には未適用
- **検討した選択肢**: `SendScheduledAnnouncementJob` にも追加する / Observer のみ適用する
- **理由**: タスクスコープ外の変更を避け、受け入れ条件どおり Observer の dispatch のみ修正する

## 4. ジョブ内のレコード取得に `withoutGlobalScopes()->find()` を使用
- **背景**: キューワーカー実行時に `ProjectScope` の影響で `MemberCoupon` が取得できない可能性がある
- **検討した選択肢**: 通常の `find()` を維持する / `SendScheduledAnnouncementJob` と同様に `withoutGlobalScopes()` を使う
- **理由**: 既存の `SendScheduledAnnouncementJob` パターンに合わせ、プロジェクトコンテキストに依存しない取得を保証する

## 5. レコード未検出時は `LogHelper::warning` でログ出力
- **背景**: 従来はサイレント `return` で通知欠落の原因が追跡できなかった
- **検討した選択肢**: サイレント return を維持する / `LogHelper::warning` でログを残す
- **理由**: 受け入れ条件と `CouponNotificationService` の慣習に従い、未検出を握りつぶさず観測可能にする