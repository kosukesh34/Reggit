# スマレジ同期の追加レビュー（2026-10-10）

## 確認した問題

HTTP応答前にWebhook同期を実行すると、要求されたprojectが未設定のためProjectScopeが会員を0件に絞り込みます。HTTP inlineの販売・取消・会員ポイント同期で、修正前の3件の失敗を再現しました。

契約resolverが返したproject IDを条件に、会員の発見のみProjectScopeを明示的に外します。実際の各会員処理はTenantContextのprojectを明示して行い、成功・例外の両方で元のprojectを復元します。requiresProjectは維持します。既存の店舗コンテキストと他のglobal scopeは維持します。

会員画面からの同期も同じprivateメソッドを使うため、この入口は要求されたmember IDだけに限定します。Webhookは同じ契約の複数projectを処理し、他契約の会員は更新しません。

独立レビューで、soft delete済みprojectの会員が残っているとproject relationがnullとなりHTTP500になる指摘を受け、実行再現しました。有効projectへの絞込み・eager loading・null guardで他の有効projectの処理を維持します。

## 検証

- 関連26 tests / 103 assertions成功。HTTP inline sale/cancel/customer、契約の分離、削除済みproject、会員画面の対象限定、例外時のコンテキスト復元を確認。
- Pint全1,646 files成功。独立最終レビューで阻害指摘なし。
- 専用の隔離MySQL、外部ネットワーク遮断、scratchソース、read-only依存、資源上限で実行。
- 実スマレジ、Wallet等の実配信、ブラウザーE2E、本番負荷は未検証。E2EをActionsに追加していない。

今回の変更は、要求/ジョブの暗黙状態に依存した同期処理のproject境界を明示するものです。全アプリの無欠陥や包括的なセキュリティ監査の完了は保証しません。

適用スキル: i-have-adhd、ponytail full、code-review-and-quality、improve-codebase-architecture、codebase-design、laravel-best-practices、security-audit（レビュー・隔離検証指針）。
