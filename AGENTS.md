# AGENTS.md

## 使用するスキル一覧

このプロジェクトでは次のスキルを使用する。状態は 2026-10-08 時点。「適用済み」はこの会話で使用したもの、「導入済み」は利用可能にしたものを表し、導入だけでコードレビューや設計検証を実施済みとは扱わない。

| スキル | 用途・使用タイミング | 状態 |
| --- | --- | --- |
| `i-have-adhd` | 全会話の回答形式。セッション開始時に読み、明示的に解除されるまで維持 | 適用済み |
| `ponytail` | 実装・修正・リファクタリング・レビューで最小の完全な変更を選ぶ。既定は `full` | 適用済み |
| `security-audit` | セキュリティレビュー。全面監査は明示的な依頼時のみ | 初回レビューで適用済み |
| `skill-installer` | スキルの探索・インストール・参照先確認 | 今回の導入で適用済み |
| `code-review-and-quality` | 正しさ・可読性・設計・安全性・性能のレビュー、マージ前確認 | 導入済み・該当作業で適用 |
| `improve-codebase-architecture` | 依頼されたアーキテクチャレビュー・設計改善 | 導入済み・該当作業で適用 |
| `vercel-react-best-practices` | Vite/React の実装・レビュー・性能改善 | 導入済み・該当作業で適用 |
| `codebase-design` | アーキテクチャ検討時のモジュール・インターフェース設計 | 補助スキルとして導入済み |
| `domain-modeling` | 設計検討時のドメイン用語・用語集・設計判断の整理 | 補助スキルとして導入済み |
| `grilling` | 設計案の検討や、ユーザーが求めた要件・判断の掘り下げ | 補助スキルとして導入済み |

- グローバルスキルの保存先は `/Users/kosuke/.codex/skills/<スキル名>/SKILL.md`。`skill-installer` は `/Users/kosuke/.codex/skills/.system/skill-installer/SKILL.md`、`security-audit` はプロジェクト内の `.agents/skills/security-audit/SKILL.md` を使う。
- 各作業の開始時に、今回適用するスキルをユーザーへ伝える。終了時は実際に適用したスキルと確認結果を報告し、導入済みの一覧をそのまま使用実績として報告しない。
- `laravel-best-practices`・`systematic-debugging`・`playwright-best-practices` は調査した追加候補であり、現時点では未導入・未適用。

## 開発スキル: Ponytail

- コードの実装・修正・リファクタリング・レビューでは、セッション開始時に `/Users/kosuke/.codex/skills/ponytail/SKILL.md` を読み、Ponytail の `full` モードを適用する。明示的な無効化指示があれば従う。
- 既存のコード・標準機能・導入済み依存を優先し、要求を完全に満たす最小の変更を選ぶ。安全性、検証、アクセシビリティ、必要なテストは省略しない。
- ユーザーの指示とこのリポジトリの CI・E2E・マージルールを優先する。回答形式はユーザー指定の i-have-adhd を維持する。
- 導入元: `DietrichGebert/ponytail`、確認済みコミット `b088b2df6e08d4306c6a3c3d575fe38c2d2d2989`。スキルが未導入の環境ではこの公開元から導入し、別名のスキルで代替しない。

## 作業別の開発スキル

必要な作業に対応する `/Users/kosuke/.codex/skills/<スキル名>/SKILL.md` を読み、参照する資料も必要な範囲で読む。

| 作業 | 適用するスキル | 導入元・固定コミット |
| --- | --- | --- |
| コードレビュー・変更後の確認・マージ前レビュー | `code-review-and-quality` | `addyosmani/agent-skills` @ `1401c8b8030e023baeebb31781a6653fe8e93026` |
| ユーザーが依頼したアーキテクチャレビュー・設計改善 | `improve-codebase-architecture` | `mattpocock/skills` @ `b0618bc436ad893b3c5e84e55fba86586d34a404` |
| React の実装・レビュー・データ取得や描画の改善 | `vercel-react-best-practices` | `vercel-labs/agent-skills` @ `063bee94c3f4df8453406c830b0a7df0f2860278` |

- アーキテクチャスキルが参照する `codebase-design`・`domain-modeling`・`grilling` も同じコミットから導入済み。関連する設計作業でのみ使い、通常の小さな修正には設計変更や追加質問を持ち込まない。
- React スキルは Vite/React に該当する規則を適用する。Next.js 専用の規則を理由に構成を変更しない。
- インストールしたスキルだけでレビューや検証が完了したとは扱わない。実際に行った確認と未確認事項を報告する。
- ユーザーの指示、Ponytail の最小変更方針、既存のアーキテクチャ、CI・E2E・マージルールを優先する。

## マージ・E2E の必須ルール

- E2E・ブラウザーテストはローカルで実行する。GitHub Actions への追加・実行は禁止。必要なアプリ・DB は既存の実行に干渉しないローカル環境を使用し、実行結果を PR に記載する。
- マージ前に、PR の最新 HEAD に対する CI の `test`・`lint`・`main` がすべて成功したことを確認する。他のチェックも失敗・保留・キャンセル・不明ならマージしない。
- push 後は以前のコミットの成功結果を流用せず、最新 HEAD のチェック完了を待つ。CI の無効化、失敗を無視したマージ、`--admin` による迂回は禁止。
- マージには `scripts/merge-pr.sh <PR番号>` を使用する。ユーザーのマージ承認は別途必要（会話内の明示的な承認を引き継ぐ）。
- GitHub のプランが対応する場合は main の branch protection/ruleset でも `main` を required status check に設定する。設定できない場合も上記ルールを必ず守る。


## Cursor Cloud specific instructions

Reggit は PHP 8.4 / Laravel 13 + Vite/React（管理画面・LIFF）+ MySQL のマルチテナント Web アプリです。リポジトリ本来のワークフローは Docker Compose（`setup.sh` / `Makefile` / `README.md` はすべて `docker-compose exec app ...` 前提）ですが、**Cloud Agent の VM では Docker を使わずネイティブに PHP・MySQL・Node を動かしています**。以下はネイティブ環境特有の非自明な注意点のみを記載します。標準的なコマンドは `README.md` / `Makefile` / `composer.json` / `package.json` を参照してください。

### 重要な差分（Docker → ネイティブ）
- `Makefile` の各ターゲットは `docker-compose exec app` を包むため **この VM では動きません**。中身の `php artisan ...` / `npm ...` を直接実行してください（例: `make test` の代わりに `php artisan test`）。
- `.env` / `.env.testing` の `DB_HOST` は Docker のサービス名 `db` ではなく **`127.0.0.1`** に設定済みです（MySQL は VM 上でネイティブ起動）。`.env` は gitignore 対象で、更新スクリプトでは再生成しません。

### サービスの起動（毎セッション必要）
- MySQL は自動起動しないため、作業開始時に起動する: `sudo service mysql start`
- DB: 開発用 `reggit` / テスト用 `reggit_test`、ユーザー `reggit_user` / パスワード `password`。
- アプリ起動（dev）: `php artisan serve --host=0.0.0.0 --port=8000` と `npm run dev`（Vite HMR）を別々に起動。管理画面 Blade は `@vite` で Vite dev サーバー（:5173）からアセットを読むため、UI を触る場合は `npm run dev` も必要。
- アクセス先: アプリ `http://localhost:8000`
  - 店舗管理ログイン: `/admin/default/login`（`demo_store` / `DemoStore#2026`）
  - システム管理ログイン: `/system-admin/login`（`demo_sys` / `DemoSys#2026`）
  - LIFF: `/liff/default`

### Lint / テスト
- Lint（整形チェック）: `./vendor/bin/pint --test`（自動修正は `./vendor/bin/pint`）。
- テスト: `php artisan test`。`phpunit.xml` が `APP_ENV=testing` と `DB_DATABASE=reggit_test` を強制するため `.env.testing` を読み、開発 DB `reggit` は破壊されません。

### 既知のハマりどころ
- `tests/Feature/SmaregiApiIntegrationTest.php`（`@group smaregi-integration`、3 テスト）は**スマレジ外部 API への実接続**テストです。`env.testing.example` がダミー資格情報を含むため空判定でのスキップが効かず、有効な `SMAREGI_CLIENT_ID` / `SMAREGI_CLIENT_SECRET` が無いと **失敗**します（コア 670 テストは成功）。実接続を検証しない限りこの 3 件の失敗は想定内です。
- `php artisan db:seed` は最後にスマレジ同期を試み、資格情報が無いと「スマレジ同期に失敗しました」と出ますが **DB のサンプルデータ投入は完了しており非ブロッキング**です。
- LINE / Stripe / スマレジ / 日本郵便 / Apple・Google Wallet / Push はすべて任意の外部連携で、デフォルト無効またはスタブです。コア開発・テストには不要です。
