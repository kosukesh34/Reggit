計画に沿って実装します。タスク指示書・レポート・対象コードを確認します。
対象ファイルと既存パターンを確認します。
スコープ宣言を作成し、実装を進めます。
テストを実行してビルドを確認します。
## 作業結果

Issue #50 の計画に従い、クーポン一括配布のバリデーション修正と通知ジョブの信頼性向上を実装しました。

- `target=selected` で会員IDが空・無効・存在しない場合にバリデーションエラーとし、全員配布へのフォールバックを防止
- `SendCouponIssuedNotificationJob` を `withoutGlobalScopes()->find()` + 未検出時ログ出力に変更
- `MemberCouponObserver` の dispatch に `afterCommit()` を適用
- Feature テストを新規・追加し、全テストがパスすることを確認

## 変更内容

| ファイル | 内容 |
|---------|------|
| `app/Http/Requests/Admin/CouponBulkIssueRequest.php` | `parsedMemberIds()` と `withValidator()` を追加。`selected` 時に有効な会員IDを必須化 |
| `app/Http/Controllers/Admin/CouponController.php` | インラインパースを `$request->parsedMemberIds()` に委譲 |
| `app/Jobs/SendCouponIssuedNotificationJob.php` | `withoutGlobalScopes()->find()` と `LogHelper::warning` を追加 |
| `app/Observers/MemberCouponObserver.php` | `->afterCommit()` を追加 |
| `tests/Feature/AdminCouponBulkIssueTest.php` | 全員配布・バリデーションエラー計4ケースを新規追加 |
| `tests/Feature/CouponNotificationTest.php` | ジョブ実行・ログ・スコープ回避の3ケースを追加 |

## ビルド結果

- `./vendor/bin/pint` を変更ファイルに実行し、スタイル問題を修正済み（6ファイル、問題なし）

## テスト結果

```bash
docker run --rm --network reggit_reggit_network \
  -v <worktree>:/var/www/html -w /var/www/html \
  -e DB_HOST=reggit_db -e DB_DATABASE=reggit_test \
  <app-image> php artisan test --filter=CouponNotificationTest

docker run ... php artisan test --filter=AdminCouponBulkIssueTest
```

| テストスイート | 結果 |
|--------------|------|
| `CouponNotificationTest` | 6 passed (9 assertions) |
| `AdminCouponBulkIssueTest` | 4 passed (15 assertions) |

UI変更はないため、スクリーンショットは不要です。