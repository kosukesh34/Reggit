# 本番デプロイ（クイックガイド）

通常は次の **1 コマンド** で十分です（`main` を Xserver 本番へ反映）。

```bash
reggit deploy
```

同等の Makefile ターゲット:

```bash
make deploy
```

実装: [`scripts/reggit`](../scripts/reggit) の `deploy` サブコマンド。

---

## 前提

1. 変更は **コミット済み**（未コミットがあるとデフォルトで停止）
2. ローカルに本番用 SSH 鍵がある（パスは環境変数 `REGGIT_DEPLOY_SSH_KEY`。未設定時の既定は `scripts/reggit` を参照）
3. `reggit` が PATH にあること（例: `~/.local/bin/reggit` → `scripts/reggit` のシンボリックリンク）
4. プロジェクトルートは環境変数 `REGGIT_ROOT`（シェル設定で指定している場合はその値）

秘密鍵・パスワード・本番 `.env` の中身は **リポジトリに書かない・コミットしない**。

---

## コマンドがやること

1. デプロイブランチ（既定: `main`）へ切替 + `git pull --ff-only`
2. `npm run build`（フロント資産）
3. `rsync` で本番アプリディレクトリへ同期（`.env` / `vendor` / `storage` などは除外）
4. 本番上で `composer install` / `migrate` / キャッシュ再生成

---

## よく使うオプション

| コマンド | 用途 |
|----------|------|
| `reggit deploy` | 通常の本番反映 |
| `reggit deploy --skip-build` | フロント変更がないとき（ビルド省略） |
| `reggit deploy --allow-dirty` | 未コミット変更も含めて送る（緊急時のみ） |
| `reggit help` | ヘルプ表示 |

---

## 接続先の上書き（任意）

接続先・鍵・ポートは環境変数で上書きできます。**値はシェル設定やローカルのみに置き、このドキュメントや PR には書かない。**

| 変数 | 意味 |
|------|------|
| `REGGIT_ROOT` | ローカルのプロジェクトルート |
| `REGGIT_DEPLOY_HOST` | SSH 先（`user@host`） |
| `REGGIT_DEPLOY_PATH` | 本番のホーム相対パス |
| `REGGIT_DEPLOY_SSH_KEY` | SSH 秘密鍵のパス |
| `REGGIT_DEPLOY_SSH_PORT` | SSH ポート |
| `REGGIT_DEPLOY_BRANCH` | デプロイするブランチ（既定 `main`） |

既定値は `scripts/reggit` 先頭を参照。

---

## 失敗しやすい点

- **未コミット変更**: 先に commit / stash。どうしても送るなら `--allow-dirty`
- **SSH 鍵なし**: `REGGIT_DEPLOY_SSH_KEY` のパスに鍵があるか確認
- **`REGGIT_ROOT` 不一致**: 別ディレクトリを触っている場合は変数を合わせる
- **本番の `.env`**: rsync 対象外。環境変数の変更はサーバ側で行う（項目の一覧は [ENV_PRODUCTION_TEMPLATE.md](./ENV_PRODUCTION_TEMPLATE.md)）
