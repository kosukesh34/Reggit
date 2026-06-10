# 変更スコープ宣言

## タスク
Issue #50: クーポン一括配布のバリデーション修正と通知ジョブの信頼性向上

## 変更予定
| 種別 | ファイル |
|------|---------|
| 変更 | `app/Http/Requests/Admin/CouponBulkIssueRequest.php` |
| 変更 | `app/Http/Controllers/Admin/CouponController.php` |
| 変更 | `app/Jobs/SendCouponIssuedNotificationJob.php` |
| 変更 | `app/Observers/MemberCouponObserver.php` |
| 作成 | `tests/Feature/AdminCouponBulkIssueTest.php` |
| 変更 | `tests/Feature/CouponNotificationTest.php` |

## 推定規模
Medium

## 影響範囲
- 管理画面クーポン一括配布（POST `admin/coupons/bulk-issue`）
- `MemberCoupon` 作成時の `SendCouponIssuedNotificationJob` 投入・実行