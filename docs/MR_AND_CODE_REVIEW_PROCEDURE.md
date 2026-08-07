# MR（Merge Request）・PR（Pull Request）作成手順とコードレビュー手順

> **GitHub 向けの実務手順（ブランチ命名・`gh` コマンド・PR 前チェック）** は [GITHUB_PULL_REQUEST_WORKFLOW.md](./GITHUB_PULL_REQUEST_WORKFLOW.md) を参照。  
> **コーディング規約の詳細** はリポジトリルートの [CODING_STANDARDS.md](../CODING_STANDARDS.md) と [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) を参照。  
> 本文書は **アーキテクチャ・デグレ・レビュー観点** に重点を置く。

## 1. MR/PR 作成手順

### 1.1 ブランチで作業する

- 機能・修正ごとにブランチを切る（例: `feature/xxx`, `fix/xxx`）
- 作業前に `main` または `develop` から最新を pull する

### 1.2 変更をコミットする

- 変更は論理単位でコミットする（細かく分けすぎず、まとめすぎない）
- コミットメッセージは意図が分かるように書く（例: `feat: 予約CRUDをUseCase化`）

### 1.3 リモートにプッシュする

```bash
git push -u origin <ブランチ名>
```

### 1.4 PR/MR を作成する

**GitHub の場合**

1. リポジトリの GitHub ページを開く
2. プッシュ後に表示される「Compare & pull request」をクリックする  
   または: **Pull requests** → **New pull request**
3. **base**: マージ先（`main` または `develop`）を選択
4. **compare**: 自分のブランチを選択
5. タイトル・説明を記入する
6. 作成後、CI が走ることを確認（`.github/workflows/ci.yml` は `main` / `develop` 向け PR で実行される）

**GitLab の場合**

1. リポジトリの GitLab ページを開く
2. **Merge requests** → **New merge request**
3. **Source branch**: 自分のブランチ
4. **Target branch**: マージ先（`main` または `develop`）
5. タイトル・説明を記入し、作成

### 1.5 マージ前に確認すること

- CI（テスト・Lint）が成功していること
- コードレビューで指摘があれば対応し、承認を得ること

### 1.6 PR を分割する場合（必読）

1 PR = 1 目的（機能追加と無関係なリファクタを混ぜない）。分割後は **各 PR ごと** に下記を確認する。

| 確認 | 内容 |
|------|------|
| アーキテクチャ | 層の依存が崩れていないか（§2.2） |
| デグレ | ソート・順序・既存処理の消失がないか（§2.4） |
| テスト | その PR の変更範囲に必要なテストがあるか。既存で足りるなら PR 本文に理由 |
| スコープ | diff が説明と一致しているか（関係ないファイルが混ざっていないか） |
| 参照実装 | system-admin / 同種の Admin Controller と同じパターンか |

---

## 2. コードレビュー手順（レビュアー向け）

コードレビューでは **変更した部分を中心** に確認する。ただし **確認の順序はアーキテクチャ → デグレ → セキュリティ → スタイル** とする。

### 2.1 アーキテクチャ（最優先）

#### 層と依存の向き

- [ ] **Controller** にビジネスロジックが入っていない（validate → Input → UseCase → レスポンス）
- [ ] **UseCase から UseCase を呼んでいない**（共通化は Service へ）
- [ ] **Controller / UseCase で `new` していない**（ビジネス依存はコンテナ注入。Input/DTO の `new` は可）
- [ ] **UseCase が `Request` に依存していない**（Input またはプリミティブ）
- [ ] **UseCase 内で `Validator::make` を使っていない**（Input で検証済みを前提）
- [ ] **static を安易に使っていない**（§18.5 CODING_STANDARDS）
- [ ] **参照実装と一致**（system-admin / 同機能の既存 Admin Controller）

#### SOLID（要点）

- [ ] 単一責務: Controller・UseCase が 1 役割に収まっている
- [ ] 依存性逆転: 具象を `new` せず注入。UseCase → Service の一方向

詳細: [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md)、[DDD.md](./DDD.md)

### 2.2 必須確認項目（テスト・セキュリティ）

#### テストが正しく実行されること

- [ ] **CI でテストが通っている**
  - GitHub Actions: PR の「Checks」で `php artisan test` が成功していること
  - ローカルで確認する場合: `php artisan test`（PHP 8.2 環境が必要）
- [ ] **新規・修正したコードにテストがあるか**
  - 方針: 既存コードへの追加テストは必須ではない。**新規作成・修正したコード**には原則テストを追加する
  - **Feature**: 処理落ちしないこと、認可、**モード別挙動**（例: 特定プロバイダでバリデーションスキップ）
  - **Unit（Input 等）**: バリデーションロジックの検証に留める。Controller 経路は Feature で担保
  - 他店舗データへの干渉・参照など **クリティカルな部分** はテストがあること
  - 既存テストで足りる場合は PR 本文にその旨を書く

#### セキュリティに問題がないこと

- [ ] **認可・認証**
  - 管理画面・API でプロジェクト（テナント）が正しくスコープされているか
  - 他プロジェクトのデータを参照・更新していないか（`project_id` の漏れがないか）
- [ ] **入力値**
  - ユーザー入力は Request / Input クラスでバリデーションされているか
  - Request・Input・UseCase で **同じバリデーションを二重にしていないか**
  - 出力時のエスケープ（XSS）は View で適切か
- [ ] **機密情報**
  - 秘密鍵・パスワード・トークンがコードやログに含まれていないか
  - `.env` や環境変数で管理すべきものはハードコードされていないか

### 2.3 デグレ（機能・順序の消失）

- [ ] **ソート・一覧の順序**が意図せず変わっていないか
- [ ] **既存の分岐・処理**が削除されていないか（削除は意図的なら PR 本文に理由）
- [ ] **表示・API のフィールド**が欠けていないか（意図的な仕様変更か確認）

### 2.4 コーディング規則（変更箇所）

以下は **変更した部分** について、[CODING_STANDARDS.md](../CODING_STANDARDS.md) に沿っているかを確認する。

| 項目 | 確認内容 |
|------|----------|
| 基本方針 | 変更最小限。DB 上 null でも実動作に無関係なら過剰な null チェックはしない |
| 定数・Enum | マジックナンバー・文字列は Enum（EnumBase 継承）。定数化の過剰はしない |
| 比較・判定 | `empty()` 極力禁止、`===`、`StrHelper` で null/空 |
| 命名 | lowerCamel。bool は `isXxx` / `canXxx` / `hasXxx` 等。`xxxFlg` / `xxxBool` 禁止 |
| View・DTO | 4 props 以上は DTO 化。View には表示用文字列（配列+キーでラベル組み立てない） |
| 日付 | 比較は Carbon の `gt()` 等。表示用にだけ Carbon 化しない。`DateUtil::nullableCarbon` は導入済みなら使用 |
| 例外 | Input を try で囲まない。catch は必要以上に細かくしない |
| トランザクション | 複数テーブル更新時のみ |
| ルート必須パラメータ | Controller 内の冗長な null/空チェックなし |
| コード量 | 関数≈15行・クラス≈100行を目安（テスト・DTO は例外） |
| UI | 遷移は `a`。`readonly` は意図があるときのみ |
| 簡潔さ | 使い捨て中間変数の乱立なし。重複 CSS/関数は共通化 |
| bool 返却 | `0`/`1` ではなく `true`/`false` |
| PHPDoc | `mixed` 避け、`array{...}` で形状を明示 |

### 2.5 コードレビュー指摘事項のまとめ（繰り返さないため）

[CODING_STANDARDS.md](../CODING_STANDARDS.md) の「コードレビュー指摘事項」および以下を参照。

- UseCase から UseCase を呼ばない / Controller で `new` しない
- static は原則使わない
- バリデーション二重化をしない
- デグレ（ソート・順序・コード消失）に注意
- ルート必須パラメータの冗長チェック不要
- Input を try で囲まない
- View には表示用文字列
- UseCase で Validator を使わない
- 定数化は過剰にしない
- トランザクションは複数テーブル更新時のみ

---

## 3. レビュー実施の流れ

1. PR/MR が作成されたら、**CI の結果**を確認する（テスト・Lint が緑であること）
2. **変更ファイル一覧**を確認し、スコープが PR 説明と一致するか見る
3. **§2.1 アーキテクチャ**を最優先で確認する
4. **§2.3 デグレ**を確認する
5. **§2.2 テスト・セキュリティ**を確認する
6. **§2.4 コーディング規則**（変更箇所のみ）を確認する
7. 指摘は PR/MR のコメントで行い、修正後は再レビューする
8. 問題がなければ承認し、マージする

---

## 4. 参考

- コーディング規則: [CODING_STANDARDS.md](../CODING_STANDARDS.md)
- アーキテクチャ: [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md)
- アーキテクチャ・DDD: [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md)、[DDD.md](./DDD.md)
- PR 作成ワークフロー: [GITHUB_PULL_REQUEST_WORKFLOW.md](./GITHUB_PULL_REQUEST_WORKFLOW.md)
- CI 定義: `.github/workflows/ci.yml`（テスト・Laravel Pint）
- テスト実行: `php artisan test`（PHP 8.2 必要）
