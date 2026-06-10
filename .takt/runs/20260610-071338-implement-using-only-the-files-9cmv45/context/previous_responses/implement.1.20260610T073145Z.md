計画に沿って実装します。タスク指示書・レポート・現行コードを確認します。
スコープ宣言を作成し、実装を進めます。
ビルドとテストを実行します。
## 作業結果

計画に従い、OneSignal Collapse ID の付与とお知らせ削除時のプッシュ取り消しを実装しました。

- `PushCollapseIdBuilder` を新設し、Collapse ID 生成を一元化
- `PushNotificationService` に送信時の `collapse_id` 付与と取り消しメソッドを追加
- `AdminAnnouncementDeleteUseCase` で配信済みお知らせ削除前に取り消しプッシュを実行
- `CouponNotificationService` からクーポン通知に Collapse ID を渡すよう更新
- 単体テスト 3 ファイル新規作成、`CouponNotificationTest` を更新

## 変更内容

| ファイル | 内容 |
|---------|------|
| `app/Support/PushCollapseIdBuilder.php` | `{project_id}:{type}:{entity_id}` 形式の ID 生成 |
| `app/Services/PushNotificationService.php` | `sendOneSignalPush` に `collapse_id` 付与、`cancelAnnouncementPush` / `cancelOneSignalPush` 追加、お知らせ・順番呼出に Collapse ID 適用 |
| `app/UseCases/Admin/Announcement/AdminAnnouncementDeleteUseCase.php` | `delivery_status === sent` のとき削除前に取り消しプッシュ |
| `app/Services/CouponNotificationService.php` | `sendToUser` 第 6 引数に Collapse ID を渡す |
| `tests/Unit/PushCollapseIdBuilderTest.php` | ID 形式の検証（新規） |
| `tests/Unit/PushNotificationServiceTest.php` | 送信・取り消しペイロード・OneSignal 無効時 no-op（新規） |
| `tests/Unit/AdminAnnouncementDeleteUseCaseTest.php` | sent/draft 時の取り消し呼び出し有無（新規） |
| `tests/Feature/CouponNotificationTest.php` | `sendToUser` モックに第 6 引数を追加 |

## ビルド結果

Docker（PHP 8.4）で変更ファイルの構文チェックを実行し、すべて成功しました。

```
No syntax errors detected in app/Support/PushCollapseIdBuilder.php
No syntax errors detected in app/Services/PushNotificationService.php
No syntax errors detected in app/UseCases/Admin/Announcement/AdminAnnouncementDeleteUseCase.php
No syntax errors detected in app/Services/CouponNotificationService.php
```

## テスト結果

```bash
docker compose run --rm --no-deps app vendor/bin/phpunit tests/Unit/PushCollapseIdBuilderTest.php
# OK (3 tests, 3 assertions)

docker compose run --rm --no-deps -e DB_HOST=host.docker.internal -e DB_PORT=3307 app php artisan test \
  tests/Unit/AdminAnnouncementDeleteUseCaseTest.php \
  tests/Unit/PushNotificationServiceTest.php \
  tests/Feature/CouponNotificationTest.php
# PASS — 8 tests, 19 assertions
```

合計 11 テスト・22 アサーションすべて成功。