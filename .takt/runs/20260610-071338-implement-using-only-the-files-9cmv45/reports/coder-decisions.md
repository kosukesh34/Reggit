# 決定ログ

## 1. Collapse ID 生成を `PushCollapseIdBuilder` に分離する
- **背景**: お知らせ・順番呼出・クーポン発行の3経路で同一形式の ID が必要であり、Service 内に生成ロジックを散在させない必要があった
- **検討した選択肢**: `PushNotificationService` 内に private メソッドとして実装 / `app/Support/PushCollapseIdBuilder` を新設
- **理由**: 既存の `CouponLiffUrlBuilder` と同パターンで Support 層に集約し、ID 形式の契約を1箇所で管理できるため

## 2. Collapse ID 形式を `{project_id}:{type}:{entity_id}` とする
- **背景**: マルチテナントで `project_id` スコープが必須であり、OneSignal の 64 文字制限内に収める必要があった
- **検討した選択肢**: `announcement_{id}` のみ / `{project_id}:{type}:{entity_id}` / UUID ベース
- **理由**: テナント分離を保証しつつ、通知種別とエンティティを人間が追跡可能な形式で表現でき、文字数も十分に収まるため

## 3. 取り消しは同一 `collapse_id` で空通知を再送する
- **背景**: OneSignal には配信済み通知を直接削除する API がなく、通知を「消す」手段が必要だった
- **検討した選択肢**: 同一 `collapse_id` で空通知＋`content_available: true` を再送 / クライアント側のみで非表示 / FCM 経路にも対応
- **理由**: OneSignal の標準的な置換手段であり、FCM は本タスクのスコープ外のため OneSignal 専用で実装するのが適切なため

## 4. お知らせ削除時の取り消しは `delivery_status === sent` のみ実行する
- **背景**: 未配信のお知らせには取り消すべきプッシュが存在しない
- **検討した選択肢**: 削除時は常に取り消しプッシュを送る / `sent` のときのみ送る
- **理由**: 未配信・下書き状態では配信済み通知がないため、不要な API 呼び出しを避けられるため

## 5. `AdminAnnouncementDeleteUseCase` から `PushNotificationService` を直接注入する
- **背景**: 削除オーケストレーションは UseCase 層、外部 API 呼び出しは Service 層という責務分離が必要だった
- **検討した選択肢**: 別の CancelUseCase を経由する / DeleteUseCase → PushNotificationService 直接注入
- **理由**: タスク制約「UseCase 連鎖なし」に従い、削除と取り消しを1つの UseCase で完結させるため