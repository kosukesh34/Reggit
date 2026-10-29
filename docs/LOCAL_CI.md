# CI とマージゲート

2026-10-10 から GitHub Actions を停止しローカル CI を必須にしていたが、2026-10-29 のユーザー指示で Actions の CI を再開した。
リポジトリは public のため、GitHub 標準 runner の実行時間は無料。

## GitHub Actions（必須）

`.github/workflows/ci.yml` が main 向け PR と main への push で実行する。E2E は含めない。

| ジョブ | 内容 |
|---|---|
| `test`（4分割） | PHP テスト。ファイルを名前順に4分割して並列実行する。MySQL 8.4 はメモリ上（tmpfs）で動かす |
| `lint` | マージゲート契約テスト（6メソッド・13ケース）、Pint、PHPStan |
| `frontend` | TypeScript、ESLint、フロントエンド単体テスト、Vite ビルド |
| `main` | 上の全ジョブの成功を確認する集約ジョブ。マージ条件はこの1つ |

- 速度のため、vendor を composer.lock のハッシュでキャッシュし、npm はキャッシュ付きで `npm ci --ignore-scripts` する。
- 同じ PR に新しい push があると古い実行は止まる。
- 実接続専用 `smaregi-integration` は除外し、外部連携の検証完了とは扱わない。

## マージ

```sh
scripts/merge-pr.sh <PR番号>
```

- PR の最新 HEAD と checkout の一致、main の取り込み、clean な作業ツリーを確認する。
- CI の完了を待ち、その HEAD で `main` チェックが成功していなければマージしない。
- CI 完了後も checkout・PR HEAD・main を再確認し、GitHub では `--match-head-commit` で同じ HEAD のみマージする。
- squash の本文は GitHub の既定と同じくコミットメッセージを並べ、Claude の署名行（`Co-Authored-By: Claude`、`Generated with Claude Code`）だけを取り除く。
- マージ後、リモートブランチは GitHub の `delete_branch_on_merge` で削除され、ローカルブランチはスクリプトが HEAD を切り離してから削除する。別 checkout が main を使用していてもマージできるよう、main への切替は行わない。不要になった worktree は作業完了後に別途整理する。
- ローカルスクリプトは GitHub UI からの直接マージを防止するものではない。

マージゲートの契約テストは Git・GitHub をローカル fixture に置換してホストの Python 標準ライブラリで実行する。実際のマージや外部接続は行わない。

## E2E

E2E は Actions では実行しない。2026-10-29 のユーザー指示により、当面はローカル実行も必須にしない。

## ローカル CI（任意）

Actions と同じチェックを手元の隔離 Docker 環境で実行する。マージ条件ではない。

Docker を起動し、`docker compose build app` で `reggit-app`、`docker pull mysql:8.4`、`docker pull node:24-bookworm-slim` を準備する。
PHP 8.4 で `composer install` し、vendor を composer.lock と一致させる。commit 済みの clean なブランチで実行する。

```sh
python3 scripts/local-ci.py
```

- PHPStan はコンテナ（メモリ1GB）に収めるため `phpstan.neon` で作業プロセスを1つにしている（2026-10-28、ユーザー承認のうえ設定）。1つでも約20秒で終わる。
- PHP コアテストの制限時間は1500秒（2026-10-22、ユーザー承認のうえ延長）。
- DB は毎回専用の一時コンテナを作成し、既存 DB・アプリ・`.env` にアクセスしない。PHP は読み取り専用ソースと vendor、ネットワークなしの専用 DB のみを使用する。
- Node の依存取得のみネットワークを使用し、npm lifecycle scripts は実行しない。全リソースを終了時に削除する。
- 途中でプロセスを強制終了した場合は `reggit-local-ci-*` の残存コンテナ・volume を確認する。
