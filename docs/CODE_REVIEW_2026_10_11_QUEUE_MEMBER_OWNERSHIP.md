# 待ち列受付: 現在会員と券保存の整合性レビュー（2026-10-11）

## 問題と変更

QueueStoreUseCase は入力 User の member relation を会員必須判定と券の member_id に使っていた。読み込み後に Member が移管・削除されると会員必須受付を通過し、旧 user と現在の所有者が異なる member を券に保存できた。通常・V1 の受付 controller は同じ UseCase を呼ぶ。

現在 project を基準に User → Member の順で最新行をロックし、会員必須判定と券保存を同じ DB トランザクションに含める。店頭受付の認証済み WaitingSystem による会員必須除外と、会員でない LINE・アプリユーザーを券に紐付ける動作は維持する。stats と出力作成は保存後に行う。

## 採番との整合性

当初の修正案で採番の5回再試行を同じ MySQL repeatable-read TX に置くと、別接続が同番号を先に保存した後も古い MAX を使い続けることを再現した。最終変更では UniqueConstraintViolationException ごとに保存 TX 全体を rollback して再試行する。各試行で User・Member・会員必須設定・番号を確認し直す。5回上限と店舗単位の unique 制約を維持する。

## 検証

- 変更前: cached relation の移管／削除 × 会員必須／任意の4件が失敗（8 assertions / 6.09秒）。必須受付を通過し、任意受付では旧 member_id を保存した。
- 採番の修正途中の再現: 別 MySQL 接続が同 project／store／queue_number を INSERT して autocommit する fixture。内側再試行では Unique1062 の1件失敗（1 assertion / 6.10秒）。初期の store_id=NULL fixture は店舗境界を再現しないため破棄した。
- 最終変更: 関連7クラス57テスト成功（234 assertions / 42.08秒）。通常・V1 の受付、店頭受付、設定、状態競合、管理画面を含む。
- 2接続の会員ロックテスト: 会員確認後の設定 getter callback から移管／soft delete を試すと writer は1205で待機失敗し、券保存後には同じ UPDATE が成功する。
- Member の lockForUpdate だけを一時コピーから除去すると上記2件が失敗（6 assertions / 6.86秒）。リポジトリのロックは維持。
- Pint（3ファイル）・全体 PHPStan 成功。独立した正しさ・可読性・設計・安全性・性能レビューで最終 production blocker なし。git diff --check 成功。
- コミット後の全ローカル CI は scripts/merge-pr.sh で別途実施し、対象 HEAD と結果を PR に記録する。

## 範囲

2接続テストは同一プロセスの制御された DB 競合であり、並行 HTTP 負荷テストではない。実際の HTTP 入口は既存の API feature tests で確認した。外部APIやブラウザーUIは変更していないため追加 E2E は実行していない。保存後に将来会員が移管・削除された場合の既存券の再紐付けは行わない。最上位 TX の再試行を対象とし、任意の呼び出し元外側 TX 内での snapshot 更新を保証する変更ではない。
