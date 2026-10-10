# 必須ローカル CI（2026-10-10 暫定運用）

ユーザー指示により GitHub Actions の CI workflow は `disabled_manually` に変更した。
Repository Actions permissions も `enabled=false` とし、全workflowを停止した。
YAML は再開用に残す。課金制限で実行前に失敗した既存チェックは、この期間のマージ判断に使わない。

## 実行

Docker を起動し、`docker compose build app` で `reggit-app`、
`docker pull mysql:8.4`、`docker pull node:24-bookworm-slim` を準備する。
PHP 8.4 で `composer install` し、vendor を composer.lock と一致させる。
コードを commit・push した clean な PR ブランチで実行する。

```sh
python3 scripts/local-ci.py
scripts/merge-pr.sh <PR番号>
```

マージスクリプトは PR の最新 HEAD・main の取り込みを確認し、必ず CI を再実行する。
CI 完了後も checkout と main を再確認し、GitHub では `--match-head-commit` で同じ HEAD のみマージする。
複数人が同時に main を変更する運用では GitHub 側の branch protection / merge queue も必要。
ローカルスクリプトは GitHub UI からの直接マージを防止するものではない。

チェック: Pint、PHPStan、PHP コアテスト、TypeScript、ESLint、フロントエンド単体テスト、Vite ビルド。
実接続専用 smaregi-integration は除外し、外部連携の検証完了とは扱わない。
E2E は変更に応じて別途ローカルで実行する。Actions では実行しない。

DB は毎回専用の一時コンテナを作成し、既存 DB・アプリ・`.env` にアクセスしない。
PHP は読み取り専用ソースと vendor、ネットワークなしの専用 DB のみを使用する。
Node の依存取得のみネットワークを使用し、npm lifecycle scripts は実行しない。
フロントエンドの実際の検証はネットワークなしで実行する。
全リソースを終了時に削除する。ログは merge-pr.sh が表示する一時ファイルに残す。
途中でプロセスを強制終了した場合は `reggit-local-ci-*` の残存コンテナ・volume を確認する。

## 再開

ユーザーの再開指示後に
`gh api --method PUT repos/{owner}/{repo}/actions/permissions -F enabled=true` と
`gh workflow enable ci.yml` を実行し、AGENTS.md と
merge-pr.sh を最新 HEAD の Actions `test`・`lint`・`main` 成功必須へ戻す。
