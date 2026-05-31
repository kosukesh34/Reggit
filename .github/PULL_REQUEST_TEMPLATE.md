## 変更内容の概要

<!-- このPRで何を変更したか簡潔に -->

## 関連Issue

<!-- あれば Issue 番号を記載 -->

## 作成者チェックリスト

- [ ] 新規・修正したコードに必要なテストを追加した（既存のみの修正でテスト不要と判断した場合はチェック不要）
- [ ] ローカルで `php artisan test` を実行し、テストが通ることを確認した（PHP 8.2 環境）
- [ ] コーディング規則に沿っている（定数化・厳密比較・UseCase 委譲・Input の try 不要など）
- [ ] 機密情報・秘密鍵をコードに含めていない

## レビュアー向け

コードレビュー時は **変更した部分** を中心に確認してください。

- **テスト**: CI でテストが通っていること。クリティカルな処理（他店舗データの参照・更新など）にテストがあること
- **セキュリティ**: 認可（project_id スコープ）、入力バリデーション、機密情報の扱い
- PR 作成手順: [docs/GITHUB_PULL_REQUEST_WORKFLOW.md](../blob/main/docs/GITHUB_PULL_REQUEST_WORKFLOW.md)
- レビュー・規約: [docs/MR_AND_CODE_REVIEW_PROCEDURE.md](../blob/main/docs/MR_AND_CODE_REVIEW_PROCEDURE.md)
- 本文のたたき台: [docs/pull_request_templates/](../tree/main/docs/pull_request_templates)
