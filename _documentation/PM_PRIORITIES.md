# Reggit — PM 優先度バックログ

> CEO/PM（Orchestra + `orchestra-pm-review`）が更新する戦略ドキュメント。Takt の `auto-improvement-loop` supervisor は計画前にこれを参照する。

| Property | Value |
|----------|-------|
| Last updated | 2026-06-10 |
| Owner | AI CEO/PM（人間が最終承認） |

---

## 北極星

マルチテナント店舗向け会員・予約・ポイント SaaSとして、**店舗オーナーが自社サイトから会員獲得でき、日常運用が止まらない** 状態を維持する。

---

## 現在の優先順位（P0 → P3）

### P0 — ブロッカー

| # | 項目 | 根拠 | 状態 |
|---|------|------|------|
| — | （現在なし） | 本番障害・セキュリティインシデントなし | — |

### P1 — 高価値・オープン Issue / マージ待ち

| # | Issue / PR | 概要 | 状態 |
|---|------------|------|------|
| 1 | #56 / [PR #57](https://github.com/kosukesh34/Reggit/pull/57) | OneSignal Collapse ID（通知の取り消し・LINE 風 UX） | Takt 完了・ドラフト PR。CI: test ✅ / **lint ❌**。人間レビュー待ち |
| 2 | #52 | 別サイトからの会員登録（embed / LIFF） | pending タスクあり。北極星に直結 |
| 3 | #50 | クーポンを正常に配信できるようにする | pending タスクあり。日常運用ブロッカー候補 |
| 4 | #49 | スマレジ管理画面から Reggit を開ける（URL 連携） | pending タスクあり |
| 5 | #51 | ランクを管理画面から設定 | open Issue。Takt タスク未作成 |

### P2 — 品質・運用・整理

| # | 項目 | 根拠 |
|---|------|------|
| 1 | **#56 重複 Takt タスク** `issue-56-no-onesignal-collapse`（pending） | PR #57 完了済み。supervisor はスキップし、タスクはクローズ判断 |
| 2 | **#58** cursor-agent 接続エラー由来タスク（pending） | 実装対象ではない。Issue + タスクをクローズ推奨 |
| 3 | [PR #36](https://github.com/kosukesh34/Reggit/pull/36) 本番向けセキュリティエンドポイント強化 | 2026-04 から open。レビュー・マージ判断 |
| 4 | 古いドラフト PR [#42](https://github.com/kosukesh34/Reggit/pull/42) [#35](https://github.com/kosukesh34/Reggit/pull/35) [#33](https://github.com/kosukesh34/Reggit/pull/33) | スコープ確認のうえマージ・分割・クローズ |
| 5 | Orchestra / Takt 設定（`.cursor/cli.json` 等）のコミット | worktree 反映・再現性 |

### P3 — 後回し

| # | 項目 | 根拠 |
|---|------|------|
| 1 | #5 メニュー画面の作成 | 2026-01 起票・本文なし。スコープ未定 |
| 2 | cosmetic UI のみの変更 | ユーザー価値が薄い |
| 3 | 明確な ROI のないリファクタ | 技術的負債は ROI 明示時のみ |

---

## マージ待ち PR（要人間判断）

| PR | ブランチ | 状態 | 次のアクション |
|----|----------|------|----------------|
| [#57](https://github.com/kosukesh34/Reggit/pull/57) | `takt/56/onesignal-collapse-id` | DRAFT・MERGEABLE・lint ❌ | lint 修正後 Ready for review → マージ |
| [#42](https://github.com/kosukesh34/Reggit/pull/42) | `feature/smaregi-point-sync-followup` | DRAFT・WIP | #49 と統合判断 or クローズ |
| [#36](https://github.com/kosukesh34/Reggit/pull/36) | `security-hardening-points-client-debug` | OPEN | セキュリティレビュー |
| [#35](https://github.com/kosukesh34/Reggit/pull/35) | `feature/default-design-coding-rules` | OPEN | スコープ確認 |
| [#33](https://github.com/kosukesh34/Reggit/pull/33) | `feature/ios-wallet-auto-update` | OPEN | スコープ確認 |

---

## 今スプリントの方針（自動更新対象）

1. **最優先（人間）:** [PR #57](https://github.com/kosukesh34/Reggit/pull/57) の lint CI 修正 → レビュー・マージ（#56 クローズ）
2. **次の実装（Takt）:** **#52** 別サイトからの会員登録 — #56 は PR 済みのため重複禁止でスキップ
3. **キュー順:** #52 → #50 → #49 → #51（supervisor が `PM_PRIORITIES` と pending 重複を参照）
4. **整理:** #58 Issue・pending タスクは実装せずクローズ。#56 重複 pending タスクもクローズ
5. **レビューは GitHub** — 人間がマージ判断。実装は Takt `autonomous-loop` に委譲（手動 `takt add` しない）

---

## Takt 実行状況（スナップショット）

| タスク | Issue | status | 備考 |
|--------|-------|--------|------|
| `onesignal-collapse-id` | 56 | completed | PR #57 |
| `autonomous-loop` | — | running | 常時稼働 |
| `issue-56-no-onesignal-collapse` | 56 | pending | **重複 — スキップ推奨** |
| `issue-52-nosaitokara-dekiruyou` | 52 | pending | 次の実装候補 |
| `issue-50-kuuponwo-ni-dekiruyou` | 50 | pending | |
| `issue-49-sumareji-kara-keruyou` | 49 | pending | |
| `cursor-agent-cli-exited-with-c` | 58 | pending | **ノイズ — クローズ推奨** |

---

## 更新ログ

| 日付 | 変更 |
|------|------|
| 2026-06-10 | 初版。CEO/PM オーケストレーション導入に伴い作成 |
| 2026-06-10 | 定期 PM レビュー。PR #57 CI 状態・#51 追加・#58/#56 重複を P2 に昇格。次実装を #52 に明示。マージ待ち PR 表を追加 |
