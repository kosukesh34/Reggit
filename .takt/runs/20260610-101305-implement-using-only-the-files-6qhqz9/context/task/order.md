## 概要
Issue #50「クーポンを正常に配信できるようにする」に対し、管理画面のプレゼント配信（一括配布）で「会員を選択して配布」を選んだのに会員ID未入力だと空配列が渡され全員に配布されるバグがある。また MemberCoupon 作成時に SendCouponIssuedNotificationJob が投入されるが、ジョブ内の find が ProjectScope の影響を受けうる（SendScheduledAnnouncementJob は withoutGlobalScopes を使用）ほか、レコード未検出時にサイレント return しており LINE/プッシュ通知が届かない。一括配布フローの Feature テストも未整備のため、修正と回帰防止テストを行う。

## 目的
- 管理画面からクーポンを意図した対象会員にのみ配布でき、配布完了後に発行通知（LINE / ネイティブプッシュ）が確実に処理されるようにする
- クーポン一括配布の主要フローを Feature テストでカバーし、配信経路のリグレッションを防ぐ

## 受け入れ条件
- [ ] 「会員を選択して配布」（target=selected）で会員IDが空または無効のみの場合、バリデーションエラーとなり member_coupons が作成されない（全員配布にフォールバックしない）
- [ ] POST admin/coupons/bulk-issue で全員配布したとき、対象会員の member_coupons レコードが作成され、各作成ごとに SendCouponIssuedNotificationJob がキュー投入される Feature テストが追加されパスする
- [ ] SendCouponIssuedNotificationJob は MemberCoupon::withoutGlobalScopes()->find() で取得し、ジョブ dispatch に afterCommit を適用する（SendScheduledAnnouncementJob と同様のパターン）
- [ ] ジョブ実行時に member_coupons が見つからない場合はログを残し、見つかった場合は CouponNotificationService::notifyIssued が呼ばれることをテストで確認する
- [ ] docker compose exec app php artisan test で CouponNotificationTest および追加した一括配布 Feature テストがすべてパスする