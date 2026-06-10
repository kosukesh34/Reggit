# 変更スコープ宣言

## タスク
OneSignal 通知に Collapse ID を付与し、お知らせ削除時にプッシュ通知を取り消せるようにする

## 変更予定
| 種別 | ファイル |
|------|---------|
| 作成 | `app/Support/PushCollapseIdBuilder.php` |
| 作成 | `tests/Unit/PushCollapseIdBuilderTest.php` |
| 作成 | `tests/Unit/PushNotificationServiceTest.php` |
| 作成 | `tests/Unit/AdminAnnouncementDeleteUseCaseTest.php` |
| 変更 | `app/Services/PushNotificationService.php` |
| 変更 | `app/UseCases/Admin/Announcement/AdminAnnouncementDeleteUseCase.php` |
| 変更 | `app/Services/CouponNotificationService.php` |
| 変更 | `tests/Feature/CouponNotificationTest.php` |

## 推定規模
Medium

## 影響範囲
- OneSignal プッシュ送信（お知らせ・クーポン・順番呼出）
- 管理画面お知らせ削除時のプッシュ取り消し