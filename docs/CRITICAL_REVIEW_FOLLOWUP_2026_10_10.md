# 追加の批判的レビュー — 2026-10-10

## 注文のポイント利用検証（P1）

100円の現金注文に500ポイントを指定すると、値引き額は100円に制限されても、500ポイントが消費される。さらにポイント機能無効の店舗でも、現金・Stripeの注文でポイント利用を受け付ける。APIの4回帰ケースは修正前に400の期待に対し201で失敗した。

共有のOrderServiceが商品・オプションから算出した総額と店舗設定で、注文保存・在庫・台帳の変更前に利用額を検証する。値を勝手に丸めず、既存のOrderOperationExceptionで拒否する。現金の全額利用、有効時のStripe部分利用、無効時の利用額0は維持する。既存注文の決済確定や回復時の方針は変更しない。

関連27 tests / 104 assertions成功。独立の静的レビューで阻害指摘なし。全体テスト・Pint・PHPStan・最新HEADのCI結果はPR本文に記録する。

専用MySQL、外部ネットワーク遮断、read-only依存、資源上限でローカル検証する。ブラウザーE2E・実外部API・本番負荷は未検証。ActionsでE2Eを実行しない。

適用: i-have-adhd、Ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices。

## 即時クーポン一括配布の原子性

- 2人目の発行後・全発行後の配信履歴保存前に例外を注入すると、いずれも発行2件が残り、履歴が欠落することを再現。
- 即時発行とCouponDelivery保存を同じtransactionにまとめる。既存afterCommit通知を利用し、途中失敗は全体rollbackにする。
- CouponBulkIssueAtomicityTestの2failurepointsで、発行と履歴の両方が0件になることを確認する。RefreshDatabaseの外側transactionがあるため、このテスト単独は通知rollbackの証明ではない。
- 並行重複と古いsnapshotの問題は、共通一括発行処理の別PRで修正する。

## 共通一括発行の並行制御

- 通常発行/grant key付き発行の双方で、並行実行・古いREPEATABLE READ snapshot・2人目発行失敗の6ケースを再現。通常発行は重複、grant key付きはunique制約例外、途中失敗は先行発行が残る。
- issueCouponToMembersをtransactionで保護し、対象会員をID順にlockForUpdate。hasUnusedValidとhasIssuedGrantKeyはlocking current readにする。
- 既存並行barrierへ2providerを追加し、独立DB接続で検証する。予約/即時/ランクアップ/お知らせ添付が共通修正を利用する。手動単件配布は変更しない。
- 大規模な配布では会員更新の待機が増える。負荷上限は未測定。

## 予約配信の失敗隔離と復旧

- 最初の配信で例外が出ると同じ実行の後続が停止する。Laravel SyncQueueはfailed()を呼ぶため、一時障害も初回でFAILEDになる。SCHEDULEDのまま毎分失敗する、という初期推測は実装とテストで否定した。
- Bus::dispatchNowで同期handlerを実行し、配信単位で例外を報告して継続する。既知の入力不備はFAILED、一時障害はtransaction rollbackでSCHEDULEDを保ち次回に再試行する。失敗時の終了コードは1。
- 期限切れ・不正な保存条件・一時障害からの次回回復と重複防止を含む関連15 tests / 75 assertions成功。恒久エラーと一時障害の分類は既存InvalidArgumentExceptionに合わせる。
