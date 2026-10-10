# 注文採番の整合性レビュー（2026-10-10）

## 問題と設計

日初の異なる2注文の同時採番で、両方が番号1を選び、片方がUNIQUE違反となることを隔離MySQLで再現しました。注文行をロックしても、まだ存在しない同一日の採番値は保護されません。

採番を既存OrderNumberAssignerが所有し、project/dateごとのorder_daily_sequences行で直列化します。新規行の初期化には既存値を保持するno-op upsertを使います。最初に試したINSERT IGNOREでは、既存counterの共有duplicate-keyロックから排他ロックへの変更でRC/RR両方のデッドロックを再現しました。

counterと注文の更新は既存transactionの一部であり、外側rollbackでも両方戻ります。project行への排他ロックは追加しません。既存番号は変更せず、その日初めての採番のみ旧注文番号の最大値をcurrent readで引き継ぎます。対象注文を既にロックした他transactionとの循環を避けるため、旧番号の検索はproject/order_numberの既存範囲インデックスを明示します。日付は1回だけ取得し、counterキー・検索・番号生成で共有します。

## 検証の対象

独立connection/forkとbarrierで、READ COMMITTED / REPEATABLE READ、当日番号0件 / 既存番号あり / 既存counterありを検証します。外側transactionがproject FKの参照ロックを持つ状態も含めます。外側rollback、古いsnapshot後にcommitされた旧番号、再試行時の番号維持も確認します。legacy検索の実際のSQLをEXPLAINし、想定の範囲インデックスを使うことを確認します。

専用DB、外部ネットワーク遮断、scratchソース、read-only依存、資源上限を使用します。既存アプリとDBは変更しません。

関連39 tests / 185 assertionsが成功しました。並行テストでは先行workerがbarrierへ到達してから後行workerを起動し、待機を単調時計で測ります。失敗時には結果ファイル・最後のSQL・DB待機状態を例外へ含め、処理側とテスト側の待機を診断します。初期のbarrierでは断続的timeoutが発生し、単独成功だけで完了扱いにせず、この明示的な起動順で関連全体を再検証しました。独立最終静的レビューに阻害指摘はありません。PHPStanも成功しています。

## 展開時の制約

新しいテーブルのmigrationが必要です。旧コードはcounterを参照しないため、新旧の採番コードを同時に動かす展開には対応しません。migrationを適用し、アプリ・スケジューラー・ワーカーを同じ新しい版で再開する必要があります。この作業では本番へのmigration・展開は行いません。

counterの行ロックがproject/day内の採番を直列化します。大規模な本番トラフィックでの待機時間は未測定です。現状の注文→会員など、別の業務ロック順序まで一括変更するものではありません。

## レビュー全体の所見

| 対象 | 確認した欠陥 | 改善した責務 |
| --- | --- | --- |
| 誕生日・登録クーポン | 並行重複、古いsnapshot、途中保存 | 発行処理がtransaction・会員ロック・履歴current readを所有 |
| スマレジ来店明細 | 並行重複、部分保存、復旧漏れ | 記録操作が親と全明細を一括保存・修復 |
| スマレジ注文 | 採番失敗後も再試行で番号NULL | 既存注文の復帰経路も冪等な採番を実行 |
| スマレジWebhook | inline同期がtenant未設定で省略 | 契約で発見した各会員のprojectを設定・復元 |
| 注文採番 | 日初の並行採番で片方が失敗 | project/dayの専用採番行で直列化 |

主な問題は、呼び出し元のtransaction・context・実行順に依存していたことです。大きなクラスを機械的に分割するより、不変条件を持つモジュール自身が必要な境界を所有する変更を優先しました。

全コードの無欠陥、実Smaregi/LINE/Stripe/Wallet配信、実機、ブラウザーE2E、本番負荷は保証・検証していません。完全なセキュリティ監査は行っていません。E2EをActionsに追加していません。

実際に適用したスキル: i-have-adhd、ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices、security-audit（レビュー・隔離検証指針）。

元のallocatorへ戻す検証では日初の片方がUNIQUE違反となり、現行barrierも退行を検出しました。bootstrapのcurrent readを外すmutationでは、snapshot後にcommitされた番号9を読み落とし、次の番号10ではなく1を選んで失敗しました。
