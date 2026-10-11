# Waiting device route target review

基準: 最新main `f4646a59d`。既存の修正済みPR #327/#367などを除外してコードと設計を確認した。

## P1: project_keyが受付IDとして渡る

`routes/api.php`の`store-app/waiting/{project_key}/queue/{id}`は、旧`/api/queue/{id}`と5つのControllerを共有する。しかしControllerは位置引数の`int $id`のみを受け取り、Laravel dispatcherは先頭のproject_keyをIDとして渡す。通常の文字列キーでは500、数値キーでは同じproject内の別受付を読み取り・変更する。projectを越えた変更は確認していない。

修正はRequestから名前付き`route('id')`を取得する。既存Api/V1 QueueCancelControllerと同じ実装を使い、URL・UseCase・認可・店舗scopeを維持する。新しいmoduleは追加しない。

## 検証

- 修正前: 新規HTTPテストを含む24 casesで17 failed / 7 passed、66 assertions。文字列キーのTypeErrorと数値キーの誤対象を確認。旧URLの詳細/取消は既存の受付token認可が必要で、最終fixtureはその条件を維持する。
- 修正後: StoreAppAuthenticationTest / QueueApiTest / QueueApiV1Testで56 passed / 235 assertions。新旧URL、数値project_key、別受付の非変更、5操作の別project拒否を確認。
- 一時ソースで呼び出し対象をproject_keyへ変更するmutationを実施。結果と全ローカルCIの対象HEAD・ログはPRへ記載する。
- 静的な独立レビューで5つの共有経路と最小修正を確認。実HTTPテストでサーバーdispatchを検証する変更のため追加ブラウザーE2Eは実施しない。外部APIや通知配送は検証対象外。

## アーキテクチャの評価と次の対象

共有ControllerのinterfaceがURLの位置引数に依存していた。名前付きroute値でHTTP seamの解釈を局所化し、既存UseCaseをそのまま使うことでlocalityを改善する。Controllerの分割や新adapterは不要。

顧客v1 APIの商品・注文・クーポン・お知らせ・抽選にも同じ位置引数の候補がある。別PRでHTTP検証後に修正する。注文ポイント同期の永続retryとWallet更新の回復は既存/追加の設計候補。初回注文特典のlock循環候補は再現前に構造変更しない。

適用: i-have-adhd、Ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices(routing)、security-audit(静的なproject整合性確認)。Reactスキルは読んだが、この変更の実装・検証には使用していない。全コードの無欠陥や包括セキュリティ監査の完了を保証しない。
