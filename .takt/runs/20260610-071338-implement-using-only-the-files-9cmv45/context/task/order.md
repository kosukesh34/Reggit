# GitHub Issue #56: onesignal Collapse IDの実装

## 背景

通知を取り消せるようにする（LINEのような挙動）。

## 自律的な進め方

1. **調査** — OneSignal・プッシュ通知・既存の通知配信コードをコードベース全体から把握する
2. **計画** — Collapse ID の設計方針・影響範囲・テスト方針を plan にまとめる
3. **実装** — `docs/CLEAN_ARCHITECTURE.md` に従い Controller → UseCase → Service で実装
4. **検証** — `docker compose exec app php artisan test` で関連テストを実行・追加
5. **PR** — ドラフト PR を作成（レビューは GitHub で実施）

## 制約

- UseCase 連鎖なし
- マルチテナント: `project_id` スコープ必須
- 既存の通知フローを壊さない
- コミットメッセージは日本語または英語で意図が伝わるもの

## 参考

- Issue: https://github.com/kosukesh34/Reggit/issues/56
