# 批判的レビュー — 2026-10-09

前回の検証記録は `CRITICAL_REVIEW_2026_10_08.md` に保持する。今回の設計改善は、ファイル全体を分割するよりも、共有する scope・不変条件・登録順序を一箇所で守る修正を優先した。

## 反映した修正

- PR #290: 複数店舗の予約番号をプロジェクト単位で採番し、逐次・並列予約の番号衝突を防止。merge済み。
- PR #291: キッチン端末の店舗所属と通知先を一致させ、旧端末の移行・Swift再登録・設定保存とlogoutの登録順序を修正。merge済み。
- PR #292: ストレージ操作の整合性を修正。merge済み。
- 店舗migration rollback: 別PRで修正中。専用MySQLで旧 `down()` の1553（FKが必要とする `member_stamp_transactions_project_id_store_id_index` の削除）を再現。残るproject-leading indexを保護し、旧受付・端末 UNIQUE 制約を復元。制約に戻せない重複はDDL前に拒否して全schema・dataを保持する回帰テストを追加。

## 検証と限界

root側でChromeのローカルブラウザー検証7件、フロントエンドunit54件が成功。Swiftの実ソースを使う登録順序assert harness、iOS Simulator buildも成功。実機FCM配信・Swiftの実ネットワーク障害・本番負荷・実外部APIは未検証。会員読取り時の外部同期などは後続の設計検討対象とする。

rollbackはメンテナンス時の書込停止が前提。店舗情報・所属自体を削除する既存仕様は維持する。重複preflightはMySQL DDL全体を原子的にするものではない。最新mainとrollback修正を統合した全coreテスト結果は確定後に追記する。

初回検証でポート置換が一致せず3307の既存 `reggit_test` へテストを開始し、即中断した。開発DBは使用していない。出力がないためテストDBへの影響は未確認で、復元・追加接続は行っていない。ユーザーには報告済み。以後は接続先assertと環境変数指定により、root専用3321・agent専用3322の `reggit_test` のみで検証した。
