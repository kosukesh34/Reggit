# Critical review — 2026-10-11 (round two)

対象はクーポン通知と注文の外部同期境界。全コードが無欠陥であることを証明するレビューではなく、障害経路とtransaction/配送契約を中心に検証した。

## 修正済み・修正対象

### P2: 発行済みクーポンの通知失敗を成功扱いする

`CouponNotificationService` はPush結果のfailedを確認せず、LINE例外をログだけで吸収していた。Jobは成功終了するので通知が失われる。PR [#322](https://github.com/kosukesh34/Reggit/pull/322) は発行transactionにpendingを保存し、Jobの期限付きclaimと毎分scannerで回復する。抽選は通常Observerを抑止して発行するため、演出完了のnotifyWinが明示的に要求を保存する。notification_requested_atの条件更新で再要求を一度に絞る。Jobは保存したprojectで実行し、元contextを復元する。

検証: 修正前にLINE/Push失敗の2テストが失敗。関連55 tests / 217 assertions成功。失敗判定を反転したtemp-source mutationでテスト失敗。独立レビューで抽選の回帰を発見し、実Jobを動かすテストを追加して修正。全local CI: PHP1448 tests / 6432 assertions、frontend64 tests、Pint/PHPStan/TypeScript/ESLint/build、マージゲート契約4 tests成功。HEAD a69b6baefd9347754014f3f62e5c117801830112、merge d4e5995158991975652177093d21fd8009bb22e5。

### P2: 現金注文完了後のスマレジ引取要求が失われる

注文COMPLETEDはcommit済みでも、finalize例外がrequestに波及する。またfalse時の遅延Jobは既定syncキューで即実行され、不完了のままreturnして保存作業を残さない。同じ完了要求を再送しても状態変更なしとして早期returnする。今回の小さな修正は注文完了transactionにpending/retry_atを保存し、既存即時処理をafterCommitへ限定する。毎分scannerが再送し、5分leaseと条件ACKで旧workerを区別する。Job enqueueに依存しない。

### P2: 外側transaction rollback後にPOSだけ販売済みになり得る

OrderStatusServiceは内側transaction終了直後にPOSを呼んでいた。外側transactionがある場合はまだcommitされていない。独立レビューで判明し、DB::afterCommitで外側commitを待つようにした。rollbackテストはPOS呼出しを許可せず、要求と注文状態が同時に戻ることを確認する。

現金引取修正は隔離DBで関連24 tests / 96 assertions成功。修正前のPOS例外を回帰テストで再現し、temp-sourceの成功判定反転mutationでもテスト失敗を確認。Pint・diff check・独立再レビュー成功。全local CI結果・PR・HEADは完了後に追記する。

## 未修正: P2 取引作成要求の永続化がない

| 経路 | 根拠 (今回のbase c56fb6d5) | 影響 |
|---|---|---|
| 現金注文作成 | OrderService.php:133 commit後、:143 event dispatch | 取置き登録が停止・sync失敗後に欠落する。pickup回復は注文完了まで動かない |
| Stripe frontend confirm | PaymentConfirmUseCase.php:108 event作成、:130 commit後dispatch | 支払済み注文の販売取引がPOSにない。confirm再送で回復可能でも自動再送要求は残らない |
| Stripe webhook | PaymentWebhookUseCase.php:167 commit後、:175直接Job dispatch | フロントが戻らない注文でenqueue/sync失敗後に販売登録が欠落し得る |

PostCommitDomainEventDispatcherの既存outboxは注文通知用で、取引同期イベントは永続化しない。したがってイベント名・層分離が整っていても配送保証は成立していない。

## 設計判断と次の具体的変更案

良い点: 注文集約に状態遷移を集約し、保存済みproject/storeと既存注文単位claimを利用できる。通知outboxは独立チャネル・期限付きlease・条件ACKを備え、配送境界の参考になる。

改善点: pending/Job/afterCommitの扱いが機能ごとに違い、同期queueが実質的に再送不能でも成功として扱われる。まず保存完了と外部配送完了を明確に分離する。RepositoryやUseCaseを一括で増やす構造整理は今回の不具合解決に不要。

次の対象は上記3経路の取引作成要求だけ。注文作成・frontend決済・Webhookのtransaction内でorder/project/store/user/phase=create/available_atを保存し、保存contextで低level同期を再試行する。order/phase一意、5分lease、条件ACK、指数backoff、100件上限を既存outbox方式に合わせる。Webhookの20秒待ちを保存し、後からfrontendが確定したら未配送要求を即実行へ早める。配送済みは再開しない。POS起点注文と未決済Stripeを送らず、キャンセル済みの遅延createは実行しない。ポイント同期は別scope。

必須検証: 各経路のtransaction rollback/commit直後停止/enqueue不要、syncキュー、false・例外、期限切れleaseと旧worker、Webhook先行→frontend即時化、重複Webhook/confirm、他project/store context復元、未設定連携。実APIはmockで検証し、外部成功後のDB保存失敗時の重複は実API契約未確認として明記する。

自動承認レビューは注文・決済Webhook・DI・リスナーを一括置換する実装案を拒否した。理由: 未検証の広い変更により重要な同期経路の停止・重複実行を招く可能性があり、一般的修正承認だけでは具体的な副作用の承認が不足している。拒否された変更は実行していない。現金pickupだけの小さな修正は別途承認レビューを通過した。3経路の変更には具体的な追加承認が必要。

## 実際に適用したスキル・検証範囲

- i-have-adhd / Ponytail full: 簡潔な連絡、既存処理を活かす最小修正。
- code-review-and-quality: 正しさ・設計・tenant境界・性能・検証、独立レビュー、mutation。
- improve-codebase-architecture / codebase-design: transactionと配送責務の境界、可視化レポート、独立レビュー。
- laravel-best-practices: queue/error/transaction/migrationの規則。

このroundではReact/Playwright/security-auditを新たに適用したと扱わない。変更はbackendの配送処理で、E2Eは未実行。既存画面・本番DBは未変更。LINE/FCM/Smaregi実接続・本番migrationは未確認。配送はat least onceで部分成功後の再送が重複し得る。既存クーポン・既存完了注文のbackfillはしない。
