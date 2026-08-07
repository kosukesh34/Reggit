## 変更内容の概要

<!-- このPRで何を変更したか簡潔に -->

## 関連Issue

<!-- あれば Issue 番号を記載 -->

## 作成者チェックリスト

- [ ] 新規・修正したコードに必要なテストを追加した（既存のみの修正でテスト不要と判断した場合はチェック不要）
- [ ] ローカルで `php artisan test` を実行し、テストが通ることを確認した（PHP 8.4 環境）
- [ ] コーディング規則に沿っている（定数化・厳密比較・UseCase 委譲・Input の try 不要など）
- [ ] 機密情報・秘密鍵をコードに含めていない

## レビュアー向け

確認順序: **アーキテクチャ → デグレ → テスト・セキュリティ → スタイル**（変更箇所を中心に）。

- **アーキテクチャ**: Controller→UseCase→Service の一方向、UseCase 連鎖なし、`new` によるビジネス依存生成なし（[CODING_STANDARDS.md](../CODING_STANDARDS.md) §18）
- **デグレ**: ソート・順序・既存処理の消失が意図的か
- **テスト**: CI 成功。クリティカル（他店舗 `project_id` 等）にテストあり
- **セキュリティ**: 認可、バリデーション二重化なし、機密情報なし
- PR 作成手順: [docs/GITHUB_PULL_REQUEST_WORKFLOW.md](../blob/main/docs/GITHUB_PULL_REQUEST_WORKFLOW.md)
- レビュー・規約: [docs/MR_AND_CODE_REVIEW_PROCEDURE.md](../blob/main/docs/MR_AND_CODE_REVIEW_PROCEDURE.md)
- アーキテクチャ詳細: [docs/CLEAN_ARCHITECTURE.md](../blob/main/docs/CLEAN_ARCHITECTURE.md)
- 本文のたたき台: [docs/pull_request_templates/](../tree/main/docs/pull_request_templates)
