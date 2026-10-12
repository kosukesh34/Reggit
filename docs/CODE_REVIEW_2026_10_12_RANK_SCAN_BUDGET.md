# ランク再送のscheduler占有 — 2026-10-12

## P2: 外部再送が他の定期処理を待たせる

Dockerのschedulerは `schedule:run` を同期実行し、終了後に60秒sleepする。ランク再送は最大20projectと100memberへ外部通信を実行するため、API障害・遅延時に次の他定期処理まで開始を遅らせる。

既存の統合コマンドをLaravelの `runInBackground()` で分離する。毎分起動と5分mutexは維持する。走査開始から45秒経過後は次の対象を開始しない。未着手要求は変更せず、次回走査に残す。projectのclaim等の例外は対象単位で報告し、後続を続けてcommand終了コードは失敗にする。

既存の復旧service、lease、ACK、tenant context、外部adapterには変更を加えない。45秒は実行中の対象を中断する期限ではない。API停止・project backlogで会員再送が遅れる可能性は残り、完了時間を保証しない。

## 検証

4つの回帰テストは遅いprojectとmemberの次回持越し、project例外と後続処理、schedulerのbackground・毎分・5分mutexを確認する。独立した正しさ・可読性・設計・安全性・性能レビューでブロッキング指摘なし。確定したテスト・mutation・静的解析・全CIの対象HEADとログはPRへ記録する。

初回の関連テスト実行は新規4件と設定再送テストを通過したが、後半で隔離DB接続が失敗した。成功結果として流用しない。最新main取り込み後のcommit済みHEADに必須mergeゲートを実行する。

旧PR #329は最新mainのPR #351と重複していたため閉じた。ランク設定の永続再送自体は最新実装を利用する。旧worktreeの未コミット案は復旧可能なsnapshotとして保管する。

適用: i-have-adhd、Ponytail full、code-review-and-quality、Laravelの既存scheduler/command構成、アーキテクチャの独立レビュー。実接続・本番環境変更・ブラウザーE2Eは未実施。全面無欠陥は保証しない。
