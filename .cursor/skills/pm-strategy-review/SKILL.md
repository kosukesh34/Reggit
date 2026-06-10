# PM strategy review

> CEO/PM としてバックログ・優先度・実行状況をレビューし、`_documentation/PM_PRIORITIES.md` を更新する。実装は行わない。

## Trigger

- "PM review"
- "CEO review"
- "優先度を見直して"
- `scripts/orchestra-pm-review.sh` からの自動起動（`mode: scheduled`）

## When to use

- 定期戦略レビュー（6 時間ごとのデーモン）
- ユーザーが「何をすべきか」「優先順位」を聞いたときの前処理

## When NOT to use

- 具体的な実装・バグ修正（Takt / coder に委譲）
- 1 行修正レベルの作業

## Process

1. **状況把握**
   - `_documentation/PM_PRIORITIES.md`（現優先度）
   - `_documentation/AI_LEARNINGS.md`（制約・決定）
   - `gh issue list --state open --limit 30`
   - `.takt/tasks.yaml`（pending / running / completed）
   - `gh pr list --state open`（`takt/*` ブランチ）

2. **判断**
   - P0〜P3 に再ランク
   - 重複タスク・無意味タスク（エラーフォールバック等）を P2 に記載
   - マージ待ち PR を明示

3. **更新**
   - `_documentation/PM_PRIORITIES.md` の「現在の優先順位」「今スプリントの方針」「更新ログ」を更新
   - Last updated 日付を更新
   - 重大な product 判断があれば `AI_LEARNINGS.md` の Decision Log に 1 行追記

4. **委譲**
   - 次に実装すべき 1 件を「今スプリントの方針」に明記
   - 実装自体は Takt `autonomous-loop` に任せる（手動で `takt add` しない）

## Output

- 更新された `PM_PRIORITIES.md`
- 変更サマリー（3 行以内、ログ用）

## References

- `.cursor/rules/ceo-pm-director.mdc`
- `docs/TAKT_ORCHESTRA.md`
