# 追加の批判的レビュー — 2026-10-10

## 注文のポイント利用検証（P1）

100円の現金注文に500ポイントを指定すると、値引き額は100円に制限されても、500ポイントが消費される。さらにポイント機能無効の店舗でも、現金・Stripeの注文でポイント利用を受け付ける。APIの4回帰ケースは修正前に400の期待に対し201で失敗した。

共有のOrderServiceが商品・オプションから算出した総額と店舗設定で、注文保存・在庫・台帳の変更前に利用額を検証する。値を勝手に丸めず、既存のOrderOperationExceptionで拒否する。現金の全額利用、有効時のStripe部分利用、無効時の利用額0は維持する。既存注文の決済確定や回復時の方針は変更しない。

関連27 tests / 104 assertions成功。独立の静的レビューで阻害指摘なし。全体テスト・Pint・PHPStan・最新HEADのCI結果はPR本文に記録する。

専用MySQL、外部ネットワーク遮断、read-only依存、資源上限でローカル検証する。ブラウザーE2E・実外部API・本番負荷は未検証。ActionsでE2Eを実行しない。

適用: i-have-adhd、Ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices。
