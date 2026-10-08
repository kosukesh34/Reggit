# Reggit 開発史

リポジトリの **git コミット履歴** と GitHub の **マージ済み Pull Request** から再構成した開発タイムラインです。

- **期間**: 2026-01-25 15:54:47 〜 2026-10-07 00:06:49（日本時間 / JST）
- **コミット数**: 737
- **マージ済み PR**: 224（クローズのみの PR は含まない）
- **主な作者**: kosukesh34 / Kosuke Shigematsu
- **時刻の扱い**:
  - コミット: author date（`git log --format='%aI'`）を JST 表記
  - PR: `mergedAt`（UTC）を JST（UTC+9）に変換
- **生成元**: `git log` / `gh pr list --state merged`

---

## プロダクトの変遷（要約）

LINE モバイルオーダー／会員証アプリとして始まり、マルチテナント店舗基盤・スマレジ連携・Wallet・ネイティブアプリ・CRM配信を積み上げた。
2026年7月に React 化とスマレジ双方向連携で一気に拡張し、8月に ver1.0.0、9月にネイティブ刷新とアーキテクチャ境界強化、10月に Laravel 13 へ到達した。

---

## 月別アクティビティ

| 月 | コミット | マージ PR |
|---|---:|---:|
| 2026-01 | 19 | 4 |
| 2026-02 | 54 | 13 |
| 2026-03 | 47 | 13 |
| 2026-04 | 2 | 1 |
| 2026-05 | 23 | 8 |
| 2026-06 | 29 | 8 |
| 2026-07 | 206 | 62 |
| 2026-08 | 184 | 65 |
| 2026-09 | 128 | 32 |
| 2026-10 | 45 | 18 |
| **合計** | **737** | **224** |

---

## 主要マイルストーン（時刻付き）

| 日時（JST） | 内容 |
|---|---|
| 2026-01-25 15:54:47 | 初回コミット: LINE Mobile Order and Membership Card Application |
| 2026-01-25 16:25:14 | PR #1: リファクタリング |
| 2026-02-22 11:54:27 | PR #10: feat: Apple Wallet 会員証パス（.pkpass）対応 |
| 2026-02-28 17:08:25 | PR #18: Add Skip mobile webview app and device registration/push foundation |
| 2026-03-21 21:49:22 | PR #31: アプリ名の変更（Reggit への統一） |
| 2026-07-08 17:35:25 | PR #68: feat: migrate frontend/admin to Vite React TypeScript |
| 2026-07-22 20:08:22 | PR #112: feat: スマレジと注文の双方向連携 |
| 2026-07-24 18:32:45 | PR #128: feat: クーポンガチャ権利とピンク筐体UIを追加する |
| 2026-08-10 08:17:00 | PR #172: feat: ver1.0.0 を main に取り込み（LIFF React ホスト復旧含む） |
| 2026-08-22 19:42:04 | PR #218: feat: プッシュ経路を FCM のみにし OneSignal を撤去する |
| 2026-09-20 02:23:16 | PR #236: feat: ネイティブ顧客アプリとLIFF体験を刷新 |
| 2026-10-07 00:06:49 | PR #281: feat: upgrade Laravel 13 and add email-free card payments |

---

## フェーズ別の歴史

### 誕生: LINEモバイルオーダー＋会員証

- **期間**: 2026-01-25 〜 2026-01-31
- **主な PR**: #1–4
- **内容**: Initial commit（2026-01-25 15:54:47 JST）で LINE ミニアプリ／会員証アプリとして起動。同日中にリファクタ・管理画面UI・予約の会員制限とセキュリティ改善をマージ。01-31 に予約キャンセル／編集・会員証・LIFF・予約カレンダーUI改善。

### 基盤: マルチテナント・Wallet・Clean Architecture

- **期間**: 2026-02
- **主な PR**: #6–18
- **内容**: DB設計・責務分離、会員発行クーポン、管理画面／LIFF強化、LIFFデザイナー（D&D）、プラン／機能ON-OFF、Apple／Google Wallet、DTO→Data リネーム、郵便番号住所自動入力、Skip モバイル WebView 基盤。

### CRM・デプロイ・Reggitブランド

- **期間**: 2026-03〜04
- **主な PR**: #19–34
- **内容**: スタンプ・ランク・退会、Android WebView、登録後配信・セグメント、予約CRUD・キュー、CRM／RFM／会員タグ、AWS EB。2026-03-21 にアプリ名を Reggit に統一。4月はほぼ静穏。

### 安定化: スマレジ同期・クーポン・ネイティブ

- **期間**: 2026-05〜06
- **主な PR**: #37–66
- **内容**: スマレジポイント同期・Wallet更新、LIFF会員識別、テストDB破壊防止、順番待ち通知URL、商品重複排除、OneSignal collapse_id、クーポン使用／一括配布、Skip SOLID再構成、LIFF商品詳細・オプション注文。

### 急成長: Vite/React移行・スマレジ双方向・ガチャ・LP

- **期間**: 2026-07
- **主な PR**: #67–150
- **内容**: 管理／フロントを Vite React TS 化、DDDドメイン層、LIFF App Studio、チャネル配信、Webhook検証、POS明細セグメント、マルチテナントオンボーディング、ランク別ポイント、スマレジ注文双方向、トップLP、クーポンガチャ、注文ミッション、ドメイン reggit.jp。

### 本番品質: LIFF React統合・配信・FCM

- **期間**: 2026-08
- **主な PR**: #151–224
- **内容**: E2E、Ponytailルール、Settings DDD、ver1.0.0→main（08-10 08:17:00）、FrontendApp単一ツリー、スタンプ周回／QR、クーポン／お知らせ予約配信、プロフィール＝スマレジ項目、OneSignal撤去→FCM（08-22 19:42:04）。

### ネイティブ刷新・境界強化

- **期間**: 2026-09
- **主な PR**: #226–261
- **内容**: LPエディトリアル化、ホーム／会員証／商品拡張、ネイティブ決済・クーポンAPI、購入特典ゲーム、セキュリティ修正、テナント unbound クエリ fail-closed、設定契約分割リファクタ、lazy chunk、店舗iPad認証強化。

### Laravel 13・セキュリティ締め・運用改善

- **期間**: 2026-10
- **主な PR**: #264–281
- **内容**: 管理セッション無効化、テナント共有mutation制限、予約容量シリアライズ、会員ID統一、RFM閾値、オーディエンスグループ、注文オプション永続化、ドキュメント整理、Laravel 13＋メール不要カード決済（最終マージ 2026-10-07 00:06:49 JST）。

---

## 全コミット一覧（日時付き・時系列）

author date を JST 表記した全 737 件。Merge commit も含む。

| 日時（JST） | ハッシュ | メッセージ |
|---|---|---|
| 2026-01-25 15:54:47 | `7800ff51` | Initial commit: LINE Mobile Order and Membership Card Application |
| 2026-01-25 15:56:03 | `1d6515dc` | Remove markdown files |
| 2026-01-25 15:56:14 | `2131a701` | Add README.md |
| 2026-01-25 15:57:56 | `6d13c0b0` | Remove all comments and markdown files |
| 2026-01-25 16:23:18 | `a8aa8174` | リファクタリング |
| 2026-01-25 16:25:14 | `fade6ed9` | Merge pull request #1 from kosukesh34/refactoring |
| 2026-01-25 16:49:27 | `c0f91dc5` | 管理画面のUIの修正 |
| 2026-01-25 16:50:06 | `cb2f0968` | Merge pull request #2 from kosukesh34/admin-ui-improvements |
| 2026-01-25 17:24:39 | `9842139c` | 予約機能の会員制限とセキュリティ改善 |
| 2026-01-25 17:30:36 | `d957bffe` | CIテストの修正 |
| 2026-01-25 17:32:39 | `1c837ac9` | SQLite設定を削除し、MySQLのみを使用するように変更 |
| 2026-01-25 17:33:59 | `e3f95256` | CI用の.env.exampleファイルを追加 |
| 2026-01-25 17:37:00 | `d3a5bfbe` | CIワークフローの順序を修正（composer installを先に実行） |
| 2026-01-25 18:11:45 | `164b86c1` | CIワークフロー: composer installをphp artisan key:generateの前に実行 |
| 2026-01-25 20:11:33 | `c333c84d` | テストエラーを修正: バリデーションエラーの422レスポンスとreserved_atの型チェックを追加 |
| 2026-01-25 20:12:03 | `5a15e401` | getUserReservationsのエラーハンドリングを改善 |
| 2026-01-25 20:13:24 | `ca9fe5b2` | Merge pull request #3 from kosukesh34/feature/reservation-membership-and-security-improvements |
| 2026-01-31 19:10:03 | `886b2bd7` | 予約のキャンセル・編集、会員証・LIFF・予約カレンダーUI改善 |
| 2026-01-31 19:19:51 | `711fcc2f` | Merge pull request #4 from kosukesh34/feature/reservation-membership-and-security-improvements |
| 2026-02-01 00:15:24 | `3bfaa12f` | feat: DB設計・責務分離・キャンセル処理・会員発行クーポン対応 |
| 2026-02-01 11:11:03 | `44a8271e` | fix: member_coupons マイグレーション順の修正とテスト環境整備 |
| 2026-02-01 11:14:08 | `51402a15` | Merge pull request #6 from kosukesh34/feature/db-schema-and-member-coupons |
| 2026-02-10 19:36:13 | `5854b1a6` | feat: admin and liff enhancements |
| 2026-02-10 20:01:55 | `5848e8e2` | fix: add missing admin helpers and services |
| 2026-02-10 21:18:03 | `a0ddac69` | feat: pro liff designer ui |
| 2026-02-10 21:30:31 | `81de559e` | feat: drag drop designer and property drawer |
| 2026-02-10 21:49:32 | `3b15ddab` | fix: improve designer usability |
| 2026-02-10 21:58:55 | `13ceccde` | fix: improve drag drop and button actions |
| 2026-02-10 22:01:29 | `5db27809` | feat: html block and palette |
| 2026-02-10 22:28:19 | `1479e09c` | fix: add missing frontend managers |
| 2026-02-14 23:14:33 | `3a3cfd90` | feature: 管理画面・API・UseCaseリファクタリングとマージ解消 |
| 2026-02-14 23:16:31 | `cc8f3283` | Merge pull request #7 from kosukesh34/feature/all-changes-mr |
| 2026-02-14 23:25:13 | `bc329ae0` | refactor: UI・クリーンアーキテクチャ・ルート統一 |
| 2026-02-14 23:28:48 | `1a9ee466` | Merge origin/main into feature/all-changes (conflicts resolved, ours) |
| 2026-02-14 23:36:21 | `91e9c13a` | feat: セキュリティ・権限・UIの強化 |
| 2026-02-14 23:44:16 | `d22a82e0` | fix: GitHub CI で composer install が通るように修正 |
| 2026-02-15 00:02:33 | `3a254cbe` | テスト修正・追加とAPIルート整備: アカウントビュー変数、不足APIルート、bind.project、新規Feature/Unitテスト追加 |
| 2026-02-15 01:25:01 | `b7f1457b` | fix: コーディング規則準拠・テスト修正・LIFF Designer UI改善 |
| 2026-02-15 01:38:51 | `134f8063` | Merge pull request #8 from kosukesh34/feature/all-changes |
| 2026-02-15 01:40:14 | `cf6db8d7` | feat: フッターナビ画面切替・フィーチャーフラグ連動・デザイン反映・戻るボタン |
| 2026-02-15 01:53:54 | `1913f509` | feat: プラン機能・機能ON/OFF設定・システム管理者ナビゲーション改善 |
| 2026-02-15 13:47:30 | `d82d4d56` | Merge pull request #9 from kosukesh34/feature/all-changes |
| 2026-02-22 11:49:11 | `220bce18` | feat: Apple Wallet 会員証パス（.pkpass）対応 |
| 2026-02-22 11:54:27 | `d9ff3df8` | Merge pull request #10 from kosukesh34/feature/apple-wallet-pass |
| 2026-02-22 13:06:39 | `fe045e4b` | refactor: DTOをData(Result/View/Input)にリネーム、PHPDocを具体型に統一、不要doc削除 |
| 2026-02-22 15:23:20 | `e525e868` | feat: 管理ログインのプロジェクト選択・ID/パスワード切替、ホーム設定ルート、Google Wallet・LIFF view composer |
| 2026-02-22 15:33:08 | `f9866eb4` | refactor: UseCases の Result を Output に統一、CLEAN_ARCHITECTURE を更新 |
| 2026-02-22 15:34:16 | `6afb477b` | Merge pull request #11 from kosukesh34/refactor/dto-to-data-phpdoc-and-docs-cleanup |
| 2026-02-23 18:55:59 | `f8c73c5a` | feat: harden multi-tenant admin, liff settings, queue linking and iPad support |
| 2026-02-26 18:16:20 | `11c92833` | テストメソッド名を日本語の説明的な名前にリファクタ |
| 2026-02-26 18:27:41 | `755e1931` | chore: storage 実行時ファイルを .gitignore に追加し追跡解除 |
| 2026-02-26 18:31:44 | `97cd01f0` | fix: SMAREGI_SCOPE をダブルクォートで囲み dotenv パースエラーを解消 |
| 2026-02-26 18:39:44 | `10371ed0` | fix(ci): phpunit の DB 設定を削除し .env/CI の環境変数を使用 |
| 2026-02-26 18:51:33 | `7521cb15` | fix(ci): テスト時の500解消とAPI/アサート修正 |
| 2026-02-26 18:51:34 | `6898b2a4` | docs: Docker DB fix, update .env.example, AppServiceProvider, AdminLoginTest |
| 2026-02-26 18:56:41 | `7c30f7c6` | fix: テスト修正・スマレジ設定ドキュメント整理 |
| 2026-02-26 18:56:42 | `7da91803` | Merge pull request #12 from kosukesh34/feat/system-admin-liff-queue-hardening |
| 2026-02-26 18:56:43 | `8785c0f3` | feat: 会員プロフィールに郵便番号入力と日本郵便APIで住所自動入力 |
| 2026-02-26 18:56:44 | `328f6710` | Merge pull request #13 from kosukesh34/feature/postal-code-address-lookup |
| 2026-02-26 18:59:01 | `ce5fad8f` | docs: README を追加（アプリ概要・環境構築・API・iPad 連携） |
| 2026-02-26 19:02:41 | `a732d5a6` | Merge pull request #14 from kosukesh34/docs/readme |
| 2026-02-27 21:13:00 | `d880d901` | feat: 管理画面設定・Google Wallet・会員編集・アナウンス配信など |
| 2026-02-27 23:12:07 | `6469d611` | 管理画面ランク設定・Smaregi連携・会員smaregi_customer_id・会員一括削除コマンド・ランクカードプレビューCSS等 |
| 2026-02-28 12:22:39 | `530bbace` | feat: Google Wallet バーコード表示・日本語ラベル・クロスプラットフォーム統一デザイン |
| 2026-02-28 13:19:28 | `4cb8836c` | クリーンアーキテクチャ適用: Controller→UseCase委譲・Admin Auth/Settings/主要画面・Web/API・CLEAN_ARCHITECTURE_STATUS.md更新 |
| 2026-02-28 13:29:22 | `8a9e2938` | Merge pull request #15 from kosukesh34/feature/admin-settings-and-wallet-2026-02 |
| 2026-02-28 13:29:41 | `4ccfda8d` | Merge pull request #16 from kosukesh34/feature/google-wallet-barcode-and-unified-design |
| 2026-02-28 15:38:43 | `aa1ee551` | fix: スマレジクーポン登録と管理画面クーポン改善 |
| 2026-02-28 16:45:28 | `98e35f91` | fix: スマレジAPI失敗時はDBに保存・更新しない設計に変更 |
| 2026-02-28 17:05:49 | `fbea0c23` | Add Skip mobile webview app and device registration support |
| 2026-02-28 17:08:25 | `5c9c217b` | Merge pull request #18 from kosukesh34/feature/mobile-webview-device-registration |
| 2026-02-28 17:09:00 | `00084a59` | feat: スマレジ商品同期でDB登録、直打ち廃止 |
| 2026-02-28 17:15:08 | `f0fdb16a` | Merge pull request #17 from kosukesh34/fix/smaregi-coupon-registration |
| 2026-03-01 21:03:41 | `8bf99218` | feat: スタンプ・ランク・退会・モバイルアプリ・Android WebView 対応 |
| 2026-03-01 21:06:25 | `e3596166` | Merge pull request #19 from kosukesh34/feature/mobile-app-stamps-ranks-withdraw-android-webview |
| 2026-03-01 21:36:47 | `0eceec2f` | chore: CODING_STANDARDS 準拠と CI/レビュー設定 |
| 2026-03-01 21:40:19 | `a03f0e5a` | style: Laravel Pint でプロジェクト全体をフォーマット（CI lint 通過のため） |
| 2026-03-01 21:45:24 | `5abc3ada` | fix(ci): CURSOR_API_KEY 未設定時はコードレビューをスキップしてジョブ成功にする |
| 2026-03-01 22:28:51 | `2e9df510` | chore(ci): Cursor コードレビュー用ワークフローを削除 |
| 2026-03-02 18:13:31 | `7be8e535` | Refine LIFF designer and stabilize checkout/payment flows |
| 2026-03-02 18:22:10 | `ae533c1d` | Remove cursor review workflow and fix order/liff regressions |
| 2026-03-02 18:40:59 | `d569dd51` | Fix system admin login with legacy role compatibility |
| 2026-03-02 18:42:49 | `f8e046a0` | Merge pull request #20 from kosukesh34/chore/coding-standards-and-ci-review |
| 2026-03-02 18:46:10 | `6fe95b48` | Merge origin/main into chore/prepare-pr-liff-order-fixes-20260302 and resolve conflicts |
| 2026-03-02 18:48:31 | `f4101998` | Merge pull request #21 from kosukesh34/chore/prepare-pr-liff-order-fixes-20260302 |
| 2026-03-03 18:48:38 | `3593fdcd` | Admin設定・Smaregi連携・モバイルアプリ・会員退会などの改善 |
| 2026-03-03 18:48:39 | `39edc372` | Push通知コマンド追加、DeviceService・デバイス登録の更新、モバイルアプリ設定の更新 |
| 2026-03-03 21:42:50 | `3bcdf4bb` | Smaregi設定・モバイル・会員・ウォレット・デバイスセッションなどの更新 |
| 2026-03-03 21:50:15 | `a84d1155` | Merge pull request #22 from kosukesh34/feature/settings-smaregi-mobile-updates |
| 2026-03-03 22:09:35 | `c74966b6` | feat: 会員証に拡大ボタンを追加し、バーコード/QRを拡大表示するモーダルを実装 |
| 2026-03-03 22:13:08 | `d10bf200` | Merge pull request #23 from kosukesh34/feature/member-card-enlarge-button |
| 2026-03-14 10:09:33 | `99fce2a5` | chore: プロジェクト設定移行・LineService移動・CSSコンポーネント分割など |
| 2026-03-14 10:11:09 | `37cf988c` | Merge pull request #24 from kosukesh34/feature/member-card-enlarge-button |
| 2026-03-14 17:30:21 | `8554ee10` | feat: 登録後配信・お知らせセグメント・クーポン配信の追加 |
| 2026-03-14 17:38:51 | `9c134cb0` | fix: 各種サービス・UseCase・テスト・マイグレーションの更新 |
| 2026-03-14 17:42:18 | `de53759a` | Merge pull request #25 from kosukesh34/feature/after-registration-delivery-and-announcements |
| 2026-03-14 19:48:04 | `0a29d2b7` | feat: 予約CRUD・キュー一覧・AppConfig API・LIFF・iOS待ち番アプリ・フロント改善 |
| 2026-03-14 19:49:18 | `4c8cb800` | docs: MR/PR作成手順とコードレビュー手順を追加、PRテンプレートを追加 |
| 2026-03-14 19:54:13 | `8b5fc655` | style: Pint指摘を修正（concat_space・未使用import削除） |
| 2026-03-14 19:59:37 | `93db7e47` | style: Pint concat_space に合わせて文字列結合のスペースを削除 |
| 2026-03-14 20:04:06 | `368a6203` | Merge pull request #26 from kosukesh34/feature/after-registration-delivery-and-announcements |
| 2026-03-14 21:29:58 | `e9f3a483` | feat: CRM機能（RFM分析・会員タグ・メモ・ウィンバック・ポイント期限リマインダー）、コホート分析、CSVエクスポート、お知らせ画像対応、サンプルデータシーダー、スマレジ同期修正 |
| 2026-03-14 21:49:42 | `afa69f32` | style: Pint 指摘を修正（class_attributes_separation, ordered_imports, phpdoc, blank_line, unary_operator） |
| 2026-03-14 22:51:48 | `6c0c1aa4` | Merge pull request #27 from kosukesh34/feature/crm-rfm-tags-sample-data |
| 2026-03-15 11:08:15 | `afeeaca5` | feat: AWS Elastic Beanstalk 用 Docker デプロイと本番 env ドキュメント |
| 2026-03-15 11:09:33 | `49e52d4d` | chore: スタンプ画像・LIFF・管理画面UI・シード等の変更を追加 |
| 2026-03-15 15:59:41 | `a37b39c0` | docs: AWSデプロイ・カスタムドメイン・本番envテンプレートを更新、モバイル設定・管理画面CSS調整 |
| 2026-03-17 19:29:56 | `c028ad76` | Add EB deploy updates and mobile queue notification/linking fixes |
| 2026-03-17 19:35:52 | `0a304b89` | style: fix pint spacing in StampAdminService |
| 2026-03-17 19:47:21 | `7fad98cc` | style: fix not_operator spacing in StampAdminService |
| 2026-03-17 19:51:19 | `cf2dbb14` | Merge pull request #28 from kosukesh34/feature/aws-eb-deploy-and-env-docs |
| 2026-03-20 22:54:37 | `4cad9d7d` | testのDBフレーク耐性を強化 |
| 2026-03-20 23:04:25 | `1c0c8ffd` | CI: Use PHP 8.4 for Composer constraints |
| 2026-03-20 23:13:25 | `682251a8` | style: run Pint repair to satisfy CI |
| 2026-03-21 14:12:40 | `f4fda29e` | test: DBリトライ処理を削除（PR差分を最小化） |
| 2026-03-21 16:58:54 | `2decf5e6` | Merge pull request #29 from kosukesh34/fix/test-flakiness-retry |
| 2026-03-21 21:46:19 | `b59786a6` | アプリ名の変更（Reggit への統一） |
| 2026-03-21 21:49:22 | `ac4aaf4e` | Merge pull request #31 from kosukesh34/feature/app-name-reggit |
| 2026-03-22 12:32:58 | `95baf336` | feat: 非本番でドキュメント通りのデモ管理ユーザーを確実に反映 |
| 2026-03-22 12:35:43 | `42099720` | Merge pull request #32 from kosukesh34/feature/nonprod-demo-admin-upsert |
| 2026-04-01 23:09:00 | `3d151d12` | feat(seeder): replace sample menu with free food images and expand products |
| 2026-04-01 23:36:59 | `7b03f0b0` | Merge pull request #34 from kosukesh34/feature/seeder-free-food-images |
| 2026-05-30 10:12:14 | `28775847` | Fix Smaregi point sync and propagate point changes to Apple/Google Wallet. |
| 2026-05-30 10:26:29 | `0ddf2f89` | Fix CI test failures for wallet pass generation and admin point updates. |
| 2026-05-30 16:43:11 | `119eaaa5` | Refresh prefetched Apple Wallet URLs before token expiry. |
| 2026-05-30 16:47:49 | `9cad53e2` | Add main status check job required by branch protection rules. |
| 2026-05-30 16:58:15 | `ac4f69c4` | Merge pull request #41 from kosukesh34/fix/pr37-add-commits |
| 2026-05-31 18:40:14 | `284b0aec` | Merge pull request #37 from kosukesh34/feature/smaregi-wallet-point-sync |
| 2026-05-31 18:52:52 | `39847343` | fix: LIFF ログイン時に既存会員が未登録扱いになる問題を修正 |
| 2026-05-31 18:53:33 | `6d4cf304` | docs: GitHub PR 作成ワークフローと Cursor ルールを追加 |
| 2026-05-31 19:00:51 | `0f815c4a` | fix: 退会済みユーザーを復元せず LINE ID を解放して再ログイン可能にする |
| 2026-05-31 19:09:48 | `74cd5048` | Merge pull request #43 from kosukesh34/fix/liff-member-identity |
| 2026-05-31 19:31:49 | `9c07bbf4` | fix: テスト実行時に開発 DB (reggit) が RefreshDatabase で消える問題を修正 |
| 2026-05-31 19:40:35 | `eab0b0a8` | style: Pint の fully_qualified_strict_types に合わせて Assert を import |
| 2026-05-31 19:48:02 | `bb331310` | Merge pull request #44 from kosukesh34/fix/test-db-isolation |
| 2026-05-31 19:49:58 | `f68f1df2` | chore: コーディング規約とレビュー手順をアーキテクチャ重視に更新 |
| 2026-05-31 19:52:24 | `0f1b4758` | Merge pull request #45 from kosukesh34/chore/docs-architecture-review |
| 2026-05-31 21:06:44 | `640eada3` | fix: 順番待ち通知のURLを正しいLIFF形式に修正 |
| 2026-05-31 21:22:02 | `0e8c0f45` | Merge pull request #46 from kosukesh34/fix/queue-liff-url |
| 2026-05-31 22:01:05 | `d16358d3` | feat: LIFF UI刷新・Wallet追加修正・プロフィール編集改善 |
| 2026-05-31 22:07:46 | `59334e4c` | fix: CI失敗を修正（LineLiffAdminService追加・Walletテスト・Pint） |
| 2026-05-31 22:11:05 | `74816d27` | Merge pull request #47 from kosukesh34/feature/liff-wallet-profile-improvements |
| 2026-05-31 22:15:32 | `876a2451` | fix: スマレジ商品の重複排除と在庫同期 API パスを修正 |
| 2026-05-31 22:22:42 | `136a5f75` | style: DefaultDataSeederTest の Pint 違反を修正 |
| 2026-05-31 22:27:17 | `a92b4f59` | Merge pull request #48 from kosukesh34/fix/smaregi-product-dedup-stock-sync |
| 2026-06-02 18:56:39 | `421dc89a` | feat: ネイティブアプリのクーポン通知・Wallet追加・シェル改善 |
| 2026-06-02 22:15:28 | `da21cc71` | style: Pint 指摘の整形を修正 |
| 2026-06-05 18:59:39 | `0ccbc3ff` | fix: CI の CouponNotificationTest 失敗とタイムアウトを修正 |
| 2026-06-05 18:59:40 | `b141dc32` | Merge pull request #53 from kosukesh34/feature/native-app-coupon-wallet |
| 2026-06-05 18:59:41 | `95a4ef04` | chore: アプリ名とネイティブ識別子を Reggit に統一 |
| 2026-06-05 18:59:42 | `02b81c4b` | fix: PR指摘の互換性とPint違反を修正 |
| 2026-06-05 18:59:43 | `a6868295` | fix: Wallet 戻り URL スキームを UA 都度判定に変更 |
| 2026-06-05 18:59:52 | `52389e8c` | revert: 旧ネイティブビルド向け互換コードを削除 |
| 2026-06-05 18:59:53 | `6e375e82` | Merge pull request #54 from kosukesh34/chore/unify-reggit-branding |
| 2026-06-09 18:49:17 | `c03ade6b` | chore: Apple Wallet デフォルト画像を Reggit ブランドに差し替え |
| 2026-06-10 18:35:28 | `8d5b5301` | Merge pull request #55 from kosukesh34/chore/wallet-pass-reggit-default-assets |
| 2026-06-12 19:58:41 | `8a636afb` | feat: OneSignal の collapse_id でプッシュ通知を置き換え可能に |
| 2026-06-12 20:02:34 | `f5436c5b` | style: PushNotificationService の Pint 指摘を修正 |
| 2026-06-12 20:05:54 | `cdc666bb` | Merge pull request #62 from kosukesh34/feature/onesignal-collapse-id |
| 2026-06-13 21:56:59 | `54c16e4d` | feat: 会員クーポン使用APIと管理画面一括配布を拡充 |
| 2026-06-13 22:01:16 | `b4156186` | style: Pint 指摘を修正して CI lint を通す |
| 2026-06-13 22:05:55 | `b1350e3a` | fix: 絞り込み配布の空対象を全員配布にしない |
| 2026-06-13 22:17:08 | `c1ad1c70` | Merge pull request #63 from kosukesh34/feature/coupon-use-bulk-issue |
| 2026-06-13 22:31:54 | `6d63de9d` | feat: クーポン利用開始前の表示とスタンプ・一括配布UIを改善 |
| 2026-06-13 22:34:37 | `019319b6` | Merge pull request #64 from kosukesh34/feature/coupon-upcoming-and-ui-improvements |
| 2026-06-14 14:19:08 | `dc5791ad` | feat: LIFFの商品詳細・オプション注文とUI刷新、デモデータ整備 |
| 2026-06-14 14:26:08 | `4448013a` | fix: Pint指摘を修正し注文オプションの検証を規約どおり強化 |
| 2026-06-14 16:54:47 | `339f3483` | fix: 注文オプション検証とデモseeder・マイグレーションをレビュー指摘どおり修正 |
| 2026-06-14 16:59:29 | `4b0f9757` | fix: Pint指摘を修正してCI lintを通す |
| 2026-06-15 18:24:48 | `75a1347b` | 会員証の表示とデモ機能の削除 |
| 2026-06-15 18:30:20 | `1e13c3b4` | refactor: Skip ネイティブアプリを SOLID 原則に沿って再構成 |
| 2026-06-22 18:23:54 | `c4b591d7` | Merge pull request #66 from kosukesh34/refactor/reggit-skip-solid |
| 2026-06-22 18:24:12 | `ad2b4927` | Merge branch 'main' into feature/liff-product-options-ui-demo |
| 2026-06-22 18:32:37 | `4eb03845` | Merge pull request #65 from kosukesh34/feature/liff-product-options-ui-demo |
| 2026-07-08 18:35:15 | `24aadb92` | feat: LIFFデザイナーのテーマ・テンプレート・タブ編集を拡張 |
| 2026-07-08 18:45:59 | `cf216724` | fix: Pintの単項演算子スペース違反を修正 |
| 2026-07-08 18:51:55 | `30ab17f3` | fix: Pint規約に合わせてSanitizer整形を再調整 |
| 2026-07-08 18:57:22 | `4aa1be19` | fix: Docker上のPint整形に合わせて定数定義を調整 |
| 2026-07-08 18:57:23 | `26f2f664` | Merge pull request #67 from kosukesh34/feature/liff-designer-ui-overhaul |
| 2026-07-08 18:57:24 | `0ec70297` | feat: migrate frontend/admin to Vite React TypeScript stack |
| 2026-07-08 18:57:25 | `ab34036e` | Merge pull request #68 from kosukesh34/feature/react-vite-ts-tailwind-migration |
| 2026-07-08 21:15:06 | `181e2e8d` | feat: public/js の残存 legacy を Vite/TS へ移し見た目は既存 CSS を維持する |
| 2026-07-08 21:33:44 | `5c85e9ca` | style: 管理画面・LINEのフォントをOS標準日本語ゴシックに統一する |
| 2026-07-10 18:13:31 | `29166312` | feat: LIFFデザイナーをReact化し保存処理とUIを整理する |
| 2026-07-10 18:17:04 | `a58db17f` | Merge pull request #69 from kosukesh34/feature/complete-legacy-js-vite-ts-migration |
| 2026-07-10 22:25:33 | `e2788c36` | refactor: Clean Architecture / DDD へドメイン層を導入し UseCase を整理する |
| 2026-07-10 22:42:28 | `804d2bc5` | fix: PR #70 レビュー指摘を反映し CI・テナント境界・決済冪等を直す |
| 2026-07-10 22:54:54 | `80b089b9` | fix: 未決済キャンセルとポイント更新に行ロックを入れる |
| 2026-07-10 23:13:38 | `cd835d77` | fix: 決済確定の巻き戻し防止と未決済時のスマレジ同期を遅延する |
| 2026-07-10 23:25:09 | `4a9c771d` | fix: レビュー指摘を反映（決済イベント外出し・同期冪等・DI整理） |
| 2026-07-10 23:34:22 | `7b8b0b39` | fix: 注文確定後のスマレジ同期をネット1回にし冪等を直す |
| 2026-07-10 23:48:47 | `a57ba585` | fix: 注文ポイント同期のネット化・再試行と inbound 巻き戻し防止 |
| 2026-07-10 23:55:54 | `06606f9d` | fix: 注文ポイント同期の二重発火と claim 競合を解消する |
| 2026-07-11 00:20:18 | `695b19db` | fix: 注文ポイント同期の完了を DB 永続化し未連携時の取りこぼしを防ぐ |
| 2026-07-11 00:38:43 | `57653d4f` | fix: レビュー指摘を反映（台帳整合・claim CAS・Cash リトライ） |
| 2026-07-11 00:46:49 | `ee6bd509` | fix: CI失敗を解消（Order ProjectScope・同期例外・Node 24） |
| 2026-07-11 00:50:05 | `82f280d9` | Merge pull request #70 from kosukesh34/refactor/clean-architecture-ddd |
| 2026-07-15 18:29:19 | `d386652c` | feat: LIFFデザイナーにiPhoneベゼルとテンプレギャラリーを追加する |
| 2026-07-15 18:29:20 | `7df7c04e` | feat: LIFFデザイナーのスタジオUIと編集体験を改善する |
| 2026-07-15 18:29:21 | `55efc66a` | fix: LIFFデザイナー新UIが旧CSSに上書きされないよう読み込み順を直す |
| 2026-07-15 18:32:59 | `589ab40d` | fix: タブデザインをデザイナー設定どおりに本番・プレビューで揃える |
| 2026-07-15 22:19:34 | `1c282cce` | feat: LIFF詳細レイアウト保存とページ遷移を本番に接続する |
| 2026-07-17 22:01:23 | `c28858fe` | Merge pull request #71 from kosukesh34/feature/liff-designer-iphone-bezel-gallery |
| 2026-07-17 22:52:14 | `02fe4ed3` | feat: LIFFをApple風UXと拡張テンプレートで刷新する |
| 2026-07-18 16:56:30 | `8cbffba7` | feat: App Studio テンプレ刷新と会員証・画面遷移の本格化 |
| 2026-07-18 16:57:27 | `a6f3af7c` | chore: モバイルシェル修正と残りのローカル変更を同梱する |
| 2026-07-18 17:01:49 | `49110521` | style: Pint で LiffAppStudioTemplates と LiffDesignerTest を整形する |
| 2026-07-18 17:05:16 | `ac87784b` | Merge pull request #72 from kosukesh34/feature/liff-apple-ux-refresh |
| 2026-07-18 18:09:31 | `3dfbfa47` | feat: 順番待ち一覧の表示・採番・再呼び出しを改善する |
| 2026-07-18 18:19:14 | `f0656d31` | style: Pint で QueueEntry の import を整形する |
| 2026-07-18 18:22:58 | `adffe9e7` | Merge pull request #74 from kosukesh34/feature/admin-queue-display-and-recall |
| 2026-07-18 18:10:51 | `08438be3` | feat: 会員チャネル・セグメント配信とスマレジ登録必須化 |
| 2026-07-18 18:17:19 | `9391048b` | style: Pint で会員セグメント関連テストのメソッド名を整形する |
| 2026-07-18 18:30:20 | `5939b511` | Merge pull request #75 from kosukesh34/feature/member-channel-segment-smaregi |
| 2026-07-18 18:46:03 | `945653a4` | feat: お知らせの削除修正・画像設定とLIFFデザインを改善する |
| 2026-07-18 18:51:10 | `cffa5d61` | style: Pint でお知らせテストのメソッド名を整形する |
| 2026-07-18 18:55:20 | `ec6a4740` | Merge pull request #76 from kosukesh34/feature/announcement-image-liff-ui |
| 2026-07-18 19:01:55 | `8922e038` | fix: 会員登録ボタン連打で複数会員が作成される不具合を修正する |
| 2026-07-18 19:05:14 | `03f82e87` | Merge pull request #77 from kosukesh34/fix/member-register-double-submit |
| 2026-07-18 21:56:31 | `749e85b3` | fix: Android ネイティブタブ表示とプルリロードを改善する |
| 2026-07-18 22:01:05 | `ab59585a` | Merge pull request #78 from kosukesh34/fix/android-native-tabs-and-pull-refresh |
| 2026-07-18 23:57:00 | `8d18454c` | feat: お知らせプッシュ取消しと管理画面既読数を追加する |
| 2026-07-19 00:00:51 | `6f770862` | style: Pint で取消し関連ユニットテストを整形する |
| 2026-07-19 00:04:35 | `b9cb682a` | Merge pull request #79 from kosukesh34/feature/announcement-push-cancel-and-reads |
| 2026-07-19 00:12:02 | `fadb954d` | fix: Android でネイティブタブが再表示されない問題を修正する |
| 2026-07-19 00:15:22 | `45f45847` | Merge pull request #80 from kosukesh34/fix/android-tab-bar-render-tick |
| 2026-07-19 00:21:58 | `bb75269c` | feat: お知らせ既読から会員詳細へ遷移し、配信をLINEにも送る |
| 2026-07-19 00:28:05 | `d7a9beb8` | style: Pint 向けにお知らせ通知テストのメソッド名を整形する |
| 2026-07-19 00:34:01 | `45b49031` | Merge pull request #81 from kosukesh34/feature/announcement-reads-link-and-line-delivery |
| 2026-07-19 00:45:02 | `1907e1ac` | feat: 登録チャネルを iOS/Android 表示に分け、会員詳細へリンクする |
| 2026-07-19 00:54:15 | `a989b37e` | Merge pull request #82 from kosukesh34/feature/member-channel-ios-android-display |
| 2026-07-19 01:27:44 | `1de7ea2f` | feat: 来店・スタンプ調整と日次番号採番を追加する |
| 2026-07-19 01:31:44 | `41ac145e` | style: Pint で注文サービスと関連テストを整形する |
| 2026-07-19 01:35:59 | `416b2531` | Merge pull request #83 from kosukesh34/feature/member-visits-stamps-and-daily-sequence-numbers |
| 2026-07-19 01:56:41 | `863fece3` | feat: スマレジ Webhook のカスタムヘッダーをミドルウェアで検証する |
| 2026-07-19 02:00:21 | `fe849594` | style: Kernel の import 順を Pint に合わせる |
| 2026-07-19 02:04:03 | `8a2266cf` | Merge pull request #96 from kosukesh34/feature/smaregi-webhook-custom-header-verify |
| 2026-07-19 02:35:23 | `c5a07bdf` | feat: スマレジPOS取引明細の保存と購入商品セグメント配信を追加する |
| 2026-07-19 02:40:29 | `90c6d54f` | fix: CI向けに取引取込とポイント同期Jobの呼び出しを安定化する |
| 2026-07-19 02:44:13 | `f61c314c` | style: Pint で取引明細・セグメント関連のスタイルを整える |
| 2026-07-19 02:47:52 | `afa29aca` | Merge pull request #98 from kosukesh34/feature/smaregi-pos-visit-items-and-segment |
| 2026-07-19 16:02:46 | `dfec5b93` | feat: 順番待ちの起動アプリ選択とネイティブ連携・表示を整備する |
| 2026-07-19 16:07:13 | `759f9300` | style: Pint で順番待ち関連のスタイルを整える |
| 2026-07-19 16:11:15 | `485e53c0` | Merge pull request #100 from kosukesh34/feature/queue-launch-channels-and-native-link |
| 2026-07-19 22:08:55 | `4aa9feed` | feat: クーポン配信を owlshop 形式に寄せる |
| 2026-07-19 22:13:30 | `7b093e85` | fix: クーポン配信のテナント検証・XSS・Pintを修正する |
| 2026-07-20 18:02:19 | `e833d7ba` | Merge pull request #101 from kosukesh34/feature/coupon-delivery-owlshop-align |
| 2026-07-20 18:17:53 | `4d635c4d` | feat: 会員詳細の履歴強化と配信詳細の会員リスト統合 |
| 2026-07-20 18:23:50 | `9e5d3d5c` | test: ポイント履歴ページネーションの並びを安定させる |
| 2026-07-20 18:27:42 | `a43e3c75` | style: Pint で会員履歴関連のスタイルを整える |
| 2026-07-20 18:31:19 | `292d0bce` | Merge pull request #102 from kosukesh34/feature/member-detail-history-and-delivery-merge |
| 2026-07-20 18:31:20 | `983f0da0` | feat: マルチテナント対応（オンボーディング・LIFF設定・端末プロジェクト選択） |
| 2026-07-20 18:31:21 | `ad6df331` | fix: スマレジ契約付け替え時に旧店舗の設定をクリアする |
| 2026-07-20 18:40:59 | `a54e3700` | style: Pint でオンボーディング関連のスタイルを整える |
| 2026-07-20 18:41:00 | `6f4a9e3f` | fix: 契約IDが無い環境では default プロジェクトキーを維持する |
| 2026-07-20 18:41:01 | `3ccaac01` | ci: テストジョブのタイムアウトを45分に延長する |
| 2026-07-20 18:41:02 | `aa179e3d` | fix: CIがテスト途中でハングする問題を解消する |
| 2026-07-20 18:41:55 | `6f338225` | Merge pull request #103 from kosukesh34/feature/multi-tenant-onboarding-liff |
| 2026-07-20 18:52:24 | `6dcacef0` | chore: project_key の default リテラルフォールバックを削除する |
| 2026-07-20 18:59:28 | `0190c5dc` | style: Pint で FQCN を import に直す |
| 2026-07-20 18:59:29 | `49aa04fb` | Merge pull request #104 from kosukesh34/chore/remove-default-project-key-fallback |
| 2026-07-20 18:59:30 | `3e262c30` | feat: 会員登録モード設定と退会・LIFF登録フォーム自動表示を追加 |
| 2026-07-20 18:59:31 | `84236911` | style: Pint で AppServiceProvider の MemberRegistrationMode import を修正 |
| 2026-07-20 18:59:32 | `65d35e78` | fix: 会員登録・プロフィール編集をタブなしの一枚画面で表示する |
| 2026-07-20 18:59:33 | `4fe94944` | Merge pull request #105 from kosukesh34/feature/member-registration-mode |
| 2026-07-20 18:59:34 | `b3bff355` | improve: オンボーディング中央寄せと管理画面アクセントを青に変更する |
| 2026-07-20 18:59:35 | `4c85fd37` | Merge pull request #106 from kosukesh34/feature/onboarding-layout-admin-blue-accent |
| 2026-07-20 19:48:33 | `bedb9e34` | feat: LIFFネイティブ品質改善と外部ページタブ・実データテンプレを追加する |
| 2026-07-20 19:52:38 | `b2fd8ea5` | style: Pint で ShopSetting と sanitizer テストのスタイルを直す |
| 2026-07-20 19:56:23 | `2432e67a` | Merge pull request #107 from kosukesh34/feature/liff-native-quality-and-link-tabs |
| 2026-07-20 20:01:31 | `4966a7e8` | feat: 外部ページタブで戻る導線を追加し新規タブ遷移を抑止する |
| 2026-07-20 20:04:22 | `52c3986d` | Merge pull request #108 from kosukesh34/feature/liff-link-tab-back-and-no-popups |
| 2026-07-20 21:23:25 | `1a9e3eff` | feat: 案内メール受取フラグを会員プロフィールとスマレジに連携する |
| 2026-07-20 21:29:59 | `ce87d3cc` | Merge pull request #109 from kosukesh34/feature/mail-receive-flag-smaregi-sync |
| 2026-07-20 22:38:17 | `3ec2af20` | feat: ランク別ポイント付与とスマレジ取引の付与・取消・返品を連携する |
| 2026-07-20 22:54:55 | `f17b5ed7` | style: Pint の指摘を修正する |
| 2026-07-20 22:58:38 | `0ead661c` | Merge pull request #110 from kosukesh34/feature/smaregi-rank-rate-and-inbound-awards |
| 2026-07-20 23:55:24 | `cfda590a` | refactor: スマレジ取引 inbound 同期を責務分割する |
| 2026-07-20 23:59:19 | `5c7c34a0` | Merge pull request #111 from kosukesh34/refactor/smaregi-inbound-sync-decomposition |
| 2026-07-21 18:55:43 | `dc73b0d8` | feat: スマレジと注文の双方向連携を追加する |
| 2026-07-21 18:55:44 | `9ff8ed94` | style: Pint の指摘を修正する |
| 2026-07-21 21:51:49 | `cd098b2e` | feat: トップにプロダクト紹介LPを追加する |
| 2026-07-21 21:58:12 | `09716a01` | chore: LPの店舗風景写真を機能に合う内容へ差し替える |
| 2026-07-21 22:33:10 | `d40d68b6` | fix: ロゴマーキーをホバー中も一定速度で流す |
| 2026-07-21 22:44:16 | `1a3ef747` | test: ルート未ログイン時はランディング表示を期待する |
| 2026-07-21 22:48:11 | `cdc39434` | Merge pull request #113 from kosukesh34/feature/landing-page |
| 2026-07-22 18:12:50 | `8c3d8c87` | feat: iPad待ち受けの連携表示と再呼出・入店を整える |
| 2026-07-22 18:18:31 | `676bf6c3` | style: Pint の指摘を修正する |
| 2026-07-22 18:22:17 | `62e4e705` | Merge pull request #114 from kosukesh34/feature/waiting-ipad-queue-ux |
| 2026-07-22 18:22:18 | `f1b6bd5e` | fix: 管理画面の会員ランク・プロフィール変更をスマレジへ反映する |
| 2026-07-22 18:22:19 | `d84ca2ec` | style: Pint の指摘を修正する |
| 2026-07-22 18:22:20 | `f3d1b769` | Merge pull request #116 from kosukesh34/fix/admin-member-smaregi-profile-sync |
| 2026-07-22 20:04:29 | `9d0b0d0e` | Merge branch 'main' into feature/smaregi-order-bidirectional-sync |
| 2026-07-22 20:08:22 | `06ec2351` | Merge pull request #112 from kosukesh34/feature/smaregi-order-bidirectional-sync |
| 2026-07-22 19:49:41 | `f85b2ed2` | fix: 会員ランクのポイント付与率をスマレジ顧客へ反映する |
| 2026-07-22 21:58:06 | `e19ba7ac` | Merge pull request #118 from kosukesh34/fix/smaregi-member-rank-point-rate |
| 2026-07-22 23:10:47 | `257278b0` | feat: Reggit トップLPを刷新し導入ブランドロゴを精緻化する |
| 2026-07-22 23:15:11 | `2005f2b5` | Merge pull request #119 from kosukesh34/feature/lp-ultimate-refresh |
| 2026-07-22 23:51:07 | `40dd00a1` | feat: LIFF顧客画面をLPと同系の店舗トーンに統一する |
| 2026-07-22 23:58:27 | `3fdbf67a` | fix: storeテーマの背景強制とbutton_shape回帰を解消する |
| 2026-07-23 18:02:24 | `3af3020f` | Merge pull request #120 from kosukesh34/feature/liff-lp-design-unify |
| 2026-07-23 18:10:57 | `35e1209b` | feat: LP導入ロゴの一部を色地＋白文字バッジに差し替える |
| 2026-07-23 18:15:14 | `3b24100b` | Merge pull request #121 from kosukesh34/feature/lp-colored-logo-badges |
| 2026-07-23 18:33:57 | `d44665c5` | feat: LIFF顧客画面をiOS HIG固定UIに刷新する |
| 2026-07-23 18:39:51 | `f49d5923` | Merge pull request #123 from kosukesh34/feature/liff-ios-hig-refresh |
| 2026-07-23 20:51:20 | `079ed882` | refactor: LIFF固定UIの重複整理と表示崩れを直す |
| 2026-07-23 21:52:36 | `4c8499e7` | feat: デモのガチャ・スクラッチ導線と会員証・オーダー見た目を整える |
| 2026-07-24 18:58:23 | `653cf4c0` | feat: /liffデモと店舗LIFFのUIを分離する |
| 2026-07-24 18:58:24 | `831edd0e` | Merge pull request #124 from kosukesh34/feature/liff-demo-store-separation |
| 2026-07-24 18:58:25 | `7192297b` | fix: 店舗LIFFをクラシックUIに戻しデモ固定UIは維持する |
| 2026-07-24 18:58:26 | `506897ce` | Merge pull request #125 from kosukesh34/fix/liff-store-classic-ui-restore |
| 2026-07-24 18:58:27 | `22daa1a1` | feat: クーポンガチャ権利とピンク筐体UIを追加する |
| 2026-07-24 18:58:28 | `041349e9` | fix: ガチャを未消化権利があるときだけ表示する |
| 2026-07-24 18:58:29 | `44c8881c` | fix: 0円取引のガチャ付与とポップアップ抑止の取りこぼしを直す |
| 2026-07-24 18:58:30 | `6f26bfa6` | fix: TEMPポイント倍率を削除し Pint 指摘を直す |
| 2026-07-24 18:58:31 | `9432dafd` | Merge pull request #128 from kosukesh34/feature/lottery-gacha-entitlement |
| 2026-07-24 19:43:17 | `f83ca087` | feat: ガチャハズレ・結果UIとStripe決済前スマレジ同期の修正 (#130) |
| 2026-07-24 20:15:59 | `5fff3a32` | fix: ガチャ権利を注文作成時ではなく取引完了時に付与する |
| 2026-07-24 20:21:58 | `8db4b96a` | style: Pint でテストメソッド名の casing を直す |
| 2026-07-24 20:26:19 | `a4f264ba` | Merge pull request #131 from kosukesh34/fix/lottery-grant-on-transaction-complete |
| 2026-07-24 21:10:36 | `7b2a7987` | feat: ギャラリーを使えるようにし、スマホ幅表示とLPナビを整える |
| 2026-07-24 21:16:38 | `51c176c6` | Merge pull request #132 from kosukesh34/feature/liff-gallery-usable-phone-layout |
| 2026-07-25 18:54:32 | `1a6d1df2` | feat: 注文ミッションタイムラインと受取可LINE通知を追加 |
| 2026-07-25 18:58:39 | `4be787cc` | style: Pint で注文通知まわりのフォーマットを修正 |
| 2026-07-25 19:02:19 | `b0ed12ce` | Merge pull request #133 from kosukesh34/feature/order-mission-timeline-ready-notify |
| 2026-07-25 20:37:03 | `b0e01cfc` | feat: スマレジ商品画像登録APIを追加する |
| 2026-07-25 20:37:02 | `b9b8d62c` | fix: 会員サマリーの白背景上書きをやめて表示を直す |
| 2026-07-25 20:39:24 | `df69d30d` | fix: 会員証バーコードが枠内で縮む問題を直す |
| 2026-07-25 20:39:26 | `32d8351d` | fix: 注文数量ボタンを押しやすいサイズに直す |
| 2026-07-25 20:39:25 | `82bb065f` | fix: キッチン準備状況をLIFF注文画面へ自動反映する |
| 2026-07-25 20:39:27 | `59511451` | fix: 会員証Walletサマリーを起動時のみ更新する |
| 2026-07-25 20:39:35 | `e413145b` | fix: Skipアプリの本番URL解決とブリッジビルドを直す |
| 2026-07-25 20:39:34 | `e29af8c4` | chore: 商品サンプル画像を追加する |
| 2026-07-25 20:39:37 | `3e45290f` | chore: public/.htaccess を追加する |
| 2026-07-25 20:39:36 | `abb97705` | chore: フロントエンドのビルド成果物を更新する |
| 2026-07-25 20:44:17 | `b7a231e0` | Merge pull request #134 from kosukesh34/feature/liff-kitchen-wallet-and-ui-fixes |
| 2026-07-26 09:32:15 | `02aa2869` | feat: スプラッシュ統一・受け渡し完了表示・deploy CLI を追加する |
| 2026-07-26 09:38:44 | `1f77bc86` | Merge pull request #135 from kosukesh34/feature/splash-handoff-and-deploy-cli |
| 2026-07-28 18:17:39 | `26fdd1ca` | chore: 常時デザインルールを削除しデプロイ手順を追加する |
| 2026-07-28 18:21:34 | `e264d485` | Merge pull request #137 from kosukesh34/chore/remove-design-rules-and-add-deploy-docs |
| 2026-07-28 18:47:36 | `b68e9dfc` | fix: 機能フラグOFF保存時に他機能までONになる不具合を直す |
| 2026-07-28 18:50:43 | `4b7a234c` | fix: 機能設定の説明文を実態（管理ナビは残る）に合わせる |
| 2026-07-28 18:51:27 | `8401a9e9` | fix: 効かない決済機能フラグを設定画面から外す |
| 2026-07-28 18:53:47 | `68f65c8b` | fix: ガチャLIFFの無言失敗・無効種表示・無料表記を直す |
| 2026-07-28 18:56:48 | `da3cab3f` | Merge pull request #139 from kosukesh34/fix/features-settings-copy |
| 2026-07-28 18:57:38 | `42ff0560` | style: Pint に合わせて機能フラグテストのメソッド名を直す |
| 2026-07-28 18:59:10 | `15b5a8b1` | fix: ガチャON/OFFをガチャ設定画面に一本化する |
| 2026-07-28 18:59:11 | `2b0dbb3f` | merge: origin/main |
| 2026-07-28 18:59:12 | `3404ab30` | merge: origin/main |
| 2026-07-28 18:59:13 | `c8ced60e` | fix: LIFFタブ／機能フラグの表記をオーダー・会員証に揃える |
| 2026-07-28 18:59:14 | `f88348cb` | merge: origin/main |
| 2026-07-28 18:59:15 | `917a760e` | Merge pull request #140 from kosukesh34/fix/hide-unused-payments-feature-flag |
| 2026-07-28 18:59:16 | `70eeed58` | merge: origin/main |
| 2026-07-28 18:59:17 | `8f23eff8` | Merge pull request #138 from kosukesh34/fix/feature-flags-false-on-save |
| 2026-07-28 18:59:18 | `57e5225d` | merge: origin/main |
| 2026-07-28 18:59:19 | `4c64c4e6` | Merge pull request #141 from kosukesh34/fix/lottery-liff-ux |
| 2026-07-28 18:59:20 | `825d2574` | merge: origin/main を取り込み ShopSetting の競合を解消する |
| 2026-07-28 18:59:21 | `8b0cf2d3` | Merge pull request #142 from kosukesh34/fix/lottery-single-source |
| 2026-07-28 18:59:22 | `159a5363` | merge: origin/main |
| 2026-07-28 18:59:23 | `95d44fd3` | Merge pull request #143 from kosukesh34/fix/liff-tab-label-order |
| 2026-07-28 18:59:24 | `c9bf5ea2` | fix: 機能設定からクーポンデフォルト画像の二重編集を外す |
| 2026-07-28 18:59:25 | `acaf8ca9` | chore: 到達不能な旧 liff-builder 画面と関連JSを削除する |
| 2026-07-28 18:59:54 | `fd4deda5` | fix: ホーム設定保存時にLIFFの外部リンクタブを消さない |
| 2026-07-28 18:59:55 | `b1c28f7a` | chore: 本番ドメイン表記を reggit.jp に揃える |
| 2026-07-28 18:59:56 | `e2dfcde4` | Merge pull request #144 from kosukesh34/fix/remove-duplicate-coupon-default-image |
| 2026-07-28 18:59:57 | `0ffd05df` | merge: origin/main |
| 2026-07-28 18:59:58 | `ee5ba2f5` | Merge pull request #145 from kosukesh34/chore/remove-dead-liff-builder |
| 2026-07-28 18:59:59 | `86ddddb8` | merge: origin/main |
| 2026-07-28 19:00:00 | `524e5180` | Merge pull request #146 from kosukesh34/fix/liff-designer-stop-menu-overwrite |
| 2026-07-28 19:00:01 | `66731338` | merge: origin/main |
| 2026-07-28 19:00:02 | `9177686d` | Merge pull request #147 from kosukesh34/chore/align-production-domain-reggit-jp |
| 2026-07-28 22:43:09 | `f2de1c82` | chore: 未使用の liff_builder_blocks PHPアクセサを削除する |
| 2026-07-28 22:44:34 | `feb70c5e` | chore: 機能ON/OFFを機能設定に集約し基本設定から重複UIを除去する |
| 2026-07-28 22:45:52 | `1681bed8` | chore: 詳細設定のメイン色をLIFFデザイン主導の読み取り専用にする |
| 2026-07-28 22:47:28 | `87cbba24` | Merge pull request #148 from kosukesh34/chore/remove-liff-builder-php-accessors |
| 2026-07-28 22:47:32 | `aa0913e3` | merge: origin/main |
| 2026-07-28 22:52:03 | `9975ccf2` | Merge pull request #149 from kosukesh34/chore/consolidate-feature-flags-ui |
| 2026-07-28 22:52:06 | `e0d8b47f` | merge: origin/main |
| 2026-07-28 22:56:13 | `772eced6` | Merge pull request #150 from kosukesh34/chore/advanced-primary-colors-readonly |
| 2026-07-30 18:36:41 | `a6f29b83` | feat: 起動 Splash で LIFF 到達性を確認してから WebView を開く |
| 2026-08-04 18:29:42 | `bd1849d3` | chore: 管理サイドバーを運用優先に再編しダッシュボードを整理する |
| 2026-08-04 18:37:24 | `8dcb01aa` | Merge pull request #151 from kosukesh34/chore/admin-nav-dashboard-ia |
| 2026-08-04 18:37:55 | `7765ea4d` | merge: origin/main を取り込み管理画面ダッシュボード整理を反映する |
| 2026-08-04 18:38:55 | `d495ed8e` | fix: ネイティブ起動ロゴの二重表示をやめ Splash を連続表示する |
| 2026-08-04 18:43:36 | `cc032178` | Merge pull request #152 from kosukesh34/feat/splash-liff-reachability-gate |
| 2026-08-06 17:56:35 | `e4ab3da5` | chore: スマレジ取引区分と Webhook action を Enum 化する |
| 2026-08-06 18:15:24 | `7ef7ff71` | feat: Playwright と Maestro の E2E を GitHub Actions に追加する |
| 2026-08-06 18:22:54 | `3582b86f` | Merge pull request #153 from kosukesh34/feat/e2e-playwright-maestro |
| 2026-08-06 18:29:06 | `0f561ce4` | fix: E2E Web CI で storage/framework 未作成によるログイン 500 を直す |
| 2026-08-06 18:34:02 | `3ed238f2` | Merge pull request #154 from kosukesh34/fix/e2e-web-ci-healthcheck |
| 2026-08-06 18:19:29 | `bb91f872` | Merge pull request #156 from kosukesh34/chore/smaregi-transaction-webhook-enums |
| 2026-08-06 18:30:01 | `0ce87d52` | chore: スマレジ区分・Webhook の残りの直書きを Enum に揃える |
| 2026-08-06 18:35:40 | `f772c707` | style: Pint で SmaregiWebhookNotificationService の import 順を直す |
| 2026-08-06 18:40:05 | `c876fd2b` | Merge pull request #157 from kosukesh34/chore/smaregi-remaining-enum-literals |
| 2026-08-07 10:39:01 | `be75b691` | chore: Cursor に Ponytail ルールを導入する |
| 2026-08-07 10:40:15 | `4ba9b840` | Merge pull request #158 from kosukesh34/chore/add-ponytail-cursor-rule |
| 2026-08-07 12:47:18 | `dd9eec02` | refactor: スマレジAPIのHTTP核心と取引会員解決を分離する |
| 2026-08-07 12:52:22 | `5045f340` | style: Pint でテストメソッド名の casing を直す |
| 2026-08-07 12:55:55 | `dae0243b` | Merge pull request #159 from kosukesh34/refactor/smaregi-api-client-extract |
| 2026-08-07 15:47:09 | `f7b7d8e9` | chore: 運用基盤の観測性・Unitスイート・アーキテクチャ文書を整える |
| 2026-08-07 16:09:41 | `f23e4d67` | style: Pint で health ルートと LogHelper テストの整形を直す |
| 2026-08-07 16:17:09 | `2fb2fb9d` | fix: Unit suite 有効化に伴う既存テストの修正 |
| 2026-08-07 16:22:41 | `016c0e08` | chore: CI の Maestro mobile ジョブを当面スキップする |
| 2026-08-07 16:28:15 | `b0efcc70` | Merge pull request #160 from kosukesh34/chore/foundation-observability-docs |
| 2026-08-07 15:49:07 | `15542180` | refactor: ShopSetting を Repository / Store に寄せて Settings を完全 DDD 化する |
| 2026-08-07 16:36:51 | `8fe43283` | fix: Settings DDD 化後に残った ShopSetting 参照を直す |
| 2026-08-07 16:43:34 | `9595af68` | style: Pint で Settings DDD 化後の import 順などを直す |
| 2026-08-07 15:49:18 | `2465e0a6` | chore: CI でスマレジ連携テストを必須化し secrets 手順を文書化する |
| 2026-08-07 15:49:18 | `d8c2cd17` | feat: ProjectPlan による管理画面機能ゲートを追加する |
| 2026-08-07 15:49:47 | `4b328b75` | feat: LIFF 顧客フロントを React features に移行する |
| 2026-08-07 16:51:13 | `a97fefdc` | Merge pull request #161 from kosukesh34/refactor/settings-full-ddd |
| 2026-08-07 16:51:43 | `51938387` | Merge branch 'main' into chore/smaregi-ci-mandatory |
| 2026-08-07 16:55:51 | `71ddfbc0` | fix: SMAREGI secrets 未設定時は CI でもスキップする |
| 2026-08-07 17:05:03 | `e416f32f` | Merge pull request #162 from kosukesh34/chore/smaregi-ci-mandatory |
| 2026-08-07 17:27:01 | `89b14ac2` | chore: Playwright E2E を main への push 時のみ実行する |
| 2026-08-07 17:29:19 | `29ebaeb7` | chore: Playwright E2E を ver1.0.0 へのマージ時のみ実行する |
| 2026-08-07 17:33:04 | `94e921cc` | chore: Playwright E2E を main へのマージ時のみ実行する |
| 2026-08-07 17:37:12 | `4aa1c501` | docs: 日常マージ先は ver1.0.0、E2E は main push のみと明記する |
| 2026-08-07 17:44:04 | `95a91022` | Merge pull request #164 from kosukesh34/feat/liff-react-migration |
| 2026-08-07 17:44:08 | `74623420` | Merge pull request #166 from kosukesh34/chore/e2e-web-main-only |
| 2026-08-07 18:51:35 | `f45cf126` | improve: LPの導入ロゴとPlatform連携ロゴを整える |
| 2026-08-07 18:51:56 | `dd6cd15c` | Merge pull request #167 from kosukesh34/feature/landing-page-polish |
| 2026-08-08 18:31:41 | `e60eb8e3` | fix: LIFFデモのくじ（スクラッチ・ルーレット・ガチャ）表示と着地判定を直す |
| 2026-08-08 18:51:28 | `8a218d76` | Merge pull request #168 from kosukesh34/fix/liff-lottery-demo-play |
| 2026-08-08 21:47:26 | `435b1363` | docs: add Cursor Cloud environment setup instructions (AGENTS.md) |
| 2026-08-08 22:11:10 | `72cec249` | Merge pull request #169 from kosukesh34/cursor/setup-dev-environment-8289 |
| 2026-08-08 23:22:26 | `149442c3` | docs: スマレジSHOPアプリUIデザイン参考資料を追加 |
| 2026-08-08 23:26:34 | `7b492129` | docs: 店舗アプリUI参考を全件URL付きで拡充（905件） |
| 2026-08-08 23:37:28 | `e411c08c` | feat: 店舗アプリスクショ学習を反映し菓子・土産OSテンプレを追加 |
| 2026-08-09 00:02:23 | `b5c95a3f` | docs: 全カテゴリの公式アプリ有無をiTunes調査で拡充 |
| 2026-08-09 00:11:28 | `dc8126a5` | feat: スクショ学習に沿い店舗テンプレのホームIAと見た目を改善 |
| 2026-08-09 00:17:19 | `9f96bec1` | feat: 店舗アプリUI学習データをシステム管理画面で閲覧できるようにする |
| 2026-08-09 13:16:35 | `5d65d936` | feat: LIFF App Studio の適用・保存と店舗画面反映を直す |
| 2026-08-09 13:28:37 | `8e295139` | Merge branch 'cursor/smaregi-app-ui-design-ref-4364' into ver1.0.0 |
| 2026-08-10 08:16:10 | `3fdb71a8` | fix: LIFF を React ホスト + Manager/Builder 描画に戻しデザイン一致を保つ |
| 2026-08-10 08:17:00 | `207c8b49` | Merge pull request #172 from kosukesh34/ver1.0.0 |
| 2026-08-10 21:46:43 | `cd842ccf` | feat: LIFF を単一 React ツリー（FrontendApp）に統合する |
| 2026-08-10 21:47:34 | `45696103` | Merge pull request #173 from kosukesh34/ver1.0.0 |
| 2026-08-11 04:14:23 | `21083cae` | feat: LINE Flex お知らせ・クーポン通知に画像(hero)を載せる |
| 2026-08-11 04:21:07 | `330609ba` | Merge pull request #174 from kosukesh34/feature/line-flex-hero-image |
| 2026-08-11 04:28:38 | `28a89062` | fix: CI失敗とLINE画像URLの不具合を直す |
| 2026-08-11 04:33:41 | `51f0f4be` | Merge pull request #175 from kosukesh34/fix/ci-liff-demo-and-line-image-url |
| 2026-08-11 05:20:28 | `24452c55` | fix: 決済・配信・スマレジの信頼境界と管理画面の表示不具合を直す |
| 2026-08-11 05:26:30 | `90f68086` | Merge pull request #176 from kosukesh34/fix/security-payment-admin-smaregi |
| 2026-08-11 09:38:42 | `d9c11e75` | feat: DesignMockベースのLIFF UIへ全面刷新し店舗APIへ接続する |
| 2026-08-11 16:24:43 | `451f5bda` | feat: reggit startup で KitchenMonitor を別 iPad で起動する |
| 2026-08-11 18:07:59 | `315ab23a` | feat: LIFFデザイナーと注文UIを改善する |
| 2026-08-11 18:09:51 | `39e76f45` | Merge pull request #177 from kosukesh34/feature/reggit-startup-kitchen-ipad |
| 2026-08-11 20:19:26 | `5518fd96` | feat: LIFF UI に Stripe・会員・ガチャを接続する |
| 2026-08-11 20:20:07 | `369e8e08` | Merge pull request #178 from kosukesh34/feature/liff-stripe-member-bind |
| 2026-08-11 21:03:38 | `9824d187` | feat: LIFFの会員・注文・順番待ち連携を改善する |
| 2026-08-11 22:09:02 | `7702bfba` | fix: LIFF会員登録UIのCSSを復元する |
| 2026-08-11 22:09:43 | `382524f2` | Merge pull request #179 from kosukesh34/fix/member-register-css |
| 2026-08-11 23:45:33 | `f5ec80fb` | fix: LIFF機能CSSの不足とお知らせ記事UIを直す |
| 2026-08-11 23:45:47 | `b2c9bf49` | Merge pull request #180 from kosukesh34/fix/liff-feature-css-and-news-article |
| 2026-08-12 00:23:48 | `c3b798b7` | fix: LIFF会員証・クーポンUIとモバイル決済を直す |
| 2026-08-12 00:32:57 | `f49b5bf8` | Merge pull request #181 from kosukesh34/fix/liff-member-coupon-ui-and-payment |
| 2026-08-12 07:35:08 | `739f9419` | fix: LIFF会員証表示と起動時フラッシュ・スケルトンを直す |
| 2026-08-12 07:36:07 | `aec4ab69` | Merge pull request #182 from kosukesh34/fix/liff-membership-boot-and-skeleton |
| 2026-08-12 07:36:45 | `165c99f1` | chore: 本番デプロイ時のフロントビルド成果物を同期する |
| 2026-08-12 13:27:13 | `8ccc6644` | fix: LIFF機能CSSの欠落を補い顧客シェルE2Eを追加する |
| 2026-08-12 13:27:13 | `a9571c58` | fix: LIFF店舗デザイン切替と会員証・機能UIを整える |
| 2026-08-12 13:27:13 | `036805cb` | feat: LINE名を分離し外部タブ・くじUIを整える |
| 2026-08-12 13:27:13 | `6913eae6` | fix: 当たりモーダルのレイアウトと閉じ方を整える |
| 2026-08-12 13:33:40 | `160a42a2` | chore: 本番デプロイ時のフロントビルド成果物を同期する |
| 2026-08-12 14:48:56 | `5e93784c` | fix: Pint が落ちるテストメソッド名の連続大文字を解消する |
| 2026-08-12 18:01:34 | `1183ccea` | feat: LIFFヘッダーロゴをアップロード設定に統一する |
| 2026-08-12 18:09:26 | `8df115a9` | Merge pull request #183 from kosukesh34/feat/liff-header-logo-upload |
| 2026-08-12 18:18:12 | `10d5d4b7` | feat: Wallet画像をアップロード＋プレビュー表示に統一する |
| 2026-08-12 18:23:48 | `185b1d4f` | Merge pull request #184 from kosukesh34/feat/wallet-image-upload-preview |
| 2026-08-12 20:42:03 | `ae6c88ee` | feat: add Japanese LIFF showcase and simple rewards design |
| 2026-08-12 22:08:37 | `aca267d9` | feat: add QR-based stamp granting flow |
| 2026-08-12 22:09:01 | `0a3935b6` | feat: add operational LIFF store templates |
| 2026-08-12 22:09:34 | `173eb196` | feat: improve LIFF customization and member card designs |
| 2026-08-12 22:09:47 | `f4722b98` | chore: refresh production frontend assets |
| 2026-08-13 00:11:39 | `d41b6255` | fix: 順番待ちLINE通知を店舗トークンで送り、クーポンバーコード表示を整える |
| 2026-08-13 00:12:40 | `3aa710e1` | Merge pull request #185 from kosukesh34/fix/queue-line-token-and-coupon-barcode |
| 2026-08-13 00:29:15 | `4d2a92da` | chore: refresh production frontend assets |
| 2026-08-13 00:54:28 | `c9e6aef2` | fix: 順番待ち呼び出し通知をLINE経路で届くようにする |
| 2026-08-13 00:56:38 | `4c5567f2` | Merge pull request #186 from kosukesh34/fix/queue-call-notify-channels |
| 2026-08-13 08:33:05 | `dca6a74a` | fix: ネイティブ会員の順番待ちが未連携になる問題を修正 |
| 2026-08-13 08:33:16 | `e61f275d` | Merge pull request #187 from kosukesh34/fix/native-member-queue-unlink |
| 2026-08-13 08:49:45 | `a9df8fd8` | fix: LIFF初期タブと順番待ちトークン取得をHashRouterに揃える |
| 2026-08-13 08:52:37 | `3a78bb41` | chore: 本番未使用の旧LIFFシェルを削除する |
| 2026-08-13 08:55:31 | `3525a6ca` | refactor: 未使用のQueueControllerを消し待ち状況のJSONをOutputに寄せる |
| 2026-08-13 08:57:24 | `45a50b0c` | chore: DesignLabのinnerHTML二重描画を削除する |
| 2026-08-13 09:11:05 | `482d3a89` | fix: 本番LIFFのギャラリーと注文履歴でカフェモックを出さない |
| 2026-08-13 09:11:05 | `4f55e98b` | fix: Skipネイティブのタブ遷移をHashRouterに揃えテストを強化 |
| 2026-08-13 09:16:40 | `65eda3ab` | Merge pull request #188 from kosukesh34/fix/liff-boot-and-queue-token |
| 2026-08-13 11:52:52 | `7f856f01` | refactor: 店舗未解決時に project_id=1 へ落とさず UseCase に寄せる |
| 2026-08-13 12:00:04 | `6ac5ca86` | Merge pull request #189 from kosukesh34/refactor/project-context-and-usecase |
| 2026-08-13 13:58:40 | `483df1c6` | fix: LIFF起動のちらつきとスタンプ付与QRを直す |
| 2026-08-13 14:31:00 | `bd035189` | fix: Designerテーマclassをbodyに最初から付ける |
| 2026-08-13 14:46:41 | `7509ba56` | Merge pull request #190 from kosukesh34/fix/liff-stamp-grant |
| 2026-08-13 18:03:06 | `4b539d1c` | feat: スタンプ押す前画像・ホームレイアウトと注文画面の交換メニューを直す |
| 2026-08-13 19:16:22 | `c4d90917` | test: MySQL JSONのキー順差を許容して達成特典の保存テストを通す |
| 2026-08-13 19:32:01 | `a1af71fe` | Merge pull request #191 from kosukesh34/feat/stamp-home-layout-and-order-fix |
| 2026-08-13 21:11:11 | `c6bc7a9f` | fix: LIFFのアプリ内カメラでスタンプ付与QRを読めるようにする |
| 2026-08-13 21:11:15 | `c7f11f61` | feat: スタンプ特典をクーポンとガチャのみにし到達時に自動付与する |
| 2026-08-13 21:19:45 | `d58f7f19` | Merge pull request #192 from kosukesh34/feat/stamp-qr-scan-and-coupon-gacha |
| 2026-08-13 21:57:27 | `54ae9dc1` | fix: LIFF起動時の画面切り替えを止める |
| 2026-08-13 23:29:55 | `5a717a4d` | Merge pull request #193 from kosukesh34/fix/liff-boot-screen-switch |
| 2026-08-14 00:57:10 | `1d535296` | feat: スタンプカードの周回と付与QRの利用上限を追加する |
| 2026-08-14 01:10:03 | `c7ff4441` | fix: 周回スタンプの一括付与を1件の履歴にし取消で巻き戻す |
| 2026-08-14 01:17:40 | `435a1b78` | Merge pull request #194 from kosukesh34/feature/stamp-cycle-and-grant-qr |
| 2026-08-14 01:45:41 | `110fc288` | feat: スタンプ達成クーポンを周ごとに配布し取引キャンセルで未使用分を取り消す |
| 2026-08-14 01:46:32 | `04b2800d` | Merge pull request #195 from kosukesh34/feature/stamp-coupon-revoke-on-cancel |
| 2026-08-14 11:36:09 | `8d81cc55` | feat: 画像アップロードをD&Dに統一しLPに会員登録画面を追加する |
| 2026-08-14 11:51:04 | `9c1fff09` | Merge pull request #196 from kosukesh34/feature/admin-image-dropzone-and-lp-register |
| 2026-08-14 12:23:28 | `9e0ec2d2` | refactor: 画面へ渡すプロパティが3つ以上なら View クラスにまとめる |
| 2026-08-14 12:35:18 | `06828459` | Merge pull request #197 from kosukesh34/refactor/view-props-threshold-3 |
| 2026-08-14 14:00:04 | `8f139e8b` | feat: 管理画面の会員一覧・詳細の検索並び替えと表示を整える |
| 2026-08-14 14:01:03 | `202e0640` | Merge pull request #198 from kosukesh34/feature/admin-member-list-and-detail-ux |
| 2026-08-14 17:03:37 | `b5ba659d` | feat: クーポン作成と予約配信を本番で動くようにする |
| 2026-08-14 17:05:58 | `dd7b2aa3` | fix: 本番 crontab 登録が空になる不具合を直す |
| 2026-08-15 00:04:50 | `04b1b772` | feat: クーポン発行条件をスマレジと揃え、画像フィットを追加する |
| 2026-08-15 00:10:00 | `c3d8a38a` | feat: お知らせ配信の予約・対象プレビューと会員の通知設定を追加する |
| 2026-08-15 00:15:15 | `52a22d80` | feat: 登録時配信を条件達成と予約のスケジュール配信に拡張する |
| 2026-08-15 00:16:58 | `7f0e81b0` | Merge pull request #199 from kosukesh34/feature/coupon-create-and-cron |
| 2026-08-15 00:19:07 | `1fadd016` | Merge pull request #200 from kosukesh34/feature/coupon-smaregi-issue-conditions |
| 2026-08-15 00:20:26 | `296a063d` | Merge pull request #201 from kosukesh34/feature/announcement-delivery-and-notification |
| 2026-08-15 00:20:40 | `8e3dac30` | Merge pull request #202 from kosukesh34/feature/scheduled-deliveries |
| 2026-08-15 00:49:10 | `2846d4d0` | fix: クーポンプレビューの表示と作成ボタンのコントラストを直す |
| 2026-08-15 01:16:35 | `e41e0746` | Merge pull request #203 from kosukesh34/fix/coupon-preview-ui |
| 2026-08-15 01:24:29 | `5fa30bbb` | fix: 会員更新の通知設定を既存テストが送るようにする |
| 2026-08-15 01:30:21 | `70817f36` | Merge pull request #204 from kosukesh34/fix/admin-member-update-ci |
| 2026-08-15 13:43:35 | `a81a8a13` | feat: クーポン配信をお知らせと同じ画面にし、通知の有無を選べるようにする |
| 2026-08-15 13:59:33 | `f2e40497` | Merge pull request #205 from kosukesh34/feature/coupon-announcement-delivery-studio |
| 2026-08-15 19:03:13 | `8b580735` | feat: プロフィールをスマレジ会員項目に揃え任意項目を同期する |
| 2026-08-15 19:04:52 | `984c57ae` | Merge pull request #206 from kosukesh34/feature/profile-smaregi-custom-fields |
| 2026-08-16 00:04:15 | `f4425d2f` | feat: ランク期間集計とLIFF会員証の即時反映を追加する |
| 2026-08-16 00:14:57 | `59bde071` | fix: CIでウォレット購読認可とPintが落ちないようにする |
| 2026-08-16 00:20:51 | `ab8708f9` | Merge pull request #207 from kosukesh34/feature/loyalty-ranks-and-liff-wallet |
| 2026-08-16 01:51:08 | `654e247f` | feat: Xserver 上の LIFF を Cloudflare トンネル経由の Reverb にする |
| 2026-08-16 01:59:07 | `542e1bcb` | Merge pull request #208 from kosukesh34/fix/liff-reverb-xserver-tunnel |
| 2026-08-18 23:36:56 | `9ad8d429` | fix: スタンプ通算と残高ロック、ポイント取消の下限を直す |
| 2026-08-18 23:37:22 | `1553d8c2` | fix: ランク同期をロックし昇格クーポンの二重発行を止める |
| 2026-08-18 23:37:28 | `f156dfe3` | fix: 予約お知らせとスケジュール配信の二重送信を止める |
| 2026-08-18 23:37:32 | `b7a8b522` | fix: LINE Webhook を店舗チャネルシークレットで解決する |
| 2026-08-19 08:27:45 | `56834426` | Merge pull request #209 from kosukesh34/cursor/fix-loyalty-races-and-webhook-tenant-334d |
| 2026-08-20 20:50:59 | `ec592a3a` | fix: LINE Webhook の reply を店舗トークンにし共有 secret を拒否する |
| 2026-08-20 20:51:03 | `36ddb7f5` | fix: スマレジのスタンプ取消を会員ロック後に再チェックする |
| 2026-08-20 20:51:09 | `ad756237` | fix: ランク同期とお知らせ送信をロックの外へ出し GET me の副作用を外す |
| 2026-08-20 20:51:15 | `eca0ec02` | fix: LIFF は ID トークンを検証し X-Line-User-Id だけでは本人確認しない |
| 2026-08-20 20:51:19 | `1a6cba7e` | chore: 未使用の AuthenticateLineUser を消し Kernel の alias 重複を整理する |
| 2026-08-20 22:34:39 | `397195ca` | Merge pull request #210 from kosukesh34/cursor/fix-review-followups-except-kitchen-240b |
| 2026-08-21 23:02:30 | `1b2a1eec` | feat: ランク設定で会員証デザインを選びエンドユーザーへ反映する |
| 2026-08-21 23:03:27 | `9ca9d720` | Merge pull request #213 from kosukesh34/feature/ranks-member-card-design-picker |
| 2026-08-22 08:44:23 | `07a383d4` | fix: SettingsRanksRequest の Pint fully_qualified_strict_types を直す |
| 2026-08-22 08:48:58 | `f2a6daaa` | Merge pull request #214 from kosukesh34/fix/pint-settings-ranks-request |
| 2026-08-22 12:26:28 | `2b8d5db2` | fix: ランク付与率のデフォルト入力と登録・同期まわりを整える |
| 2026-08-22 12:26:48 | `dee827cc` | Merge pull request #215 from kosukesh34/feature/rank-defaults-and-max-limit |
| 2026-08-22 13:51:02 | `a7c76b7e` | fix: MemberAdmin の DI 循環を解消し設定画面の説明枠を削除する |
| 2026-08-22 13:57:26 | `6793d402` | Merge pull request #216 from kosukesh34/fix/member-admin-di-cycle-and-remove-settings-guides |
| 2026-08-22 18:39:41 | `249ef2c1` | feat: 顧客アプリのプッシュを OneSignal から FCM HTTP v1 へ切り替える |
| 2026-08-22 18:40:15 | `477ac81b` | Merge pull request #217 from kosukesh34/feature/onesignal-to-fcm-skip |
| 2026-08-22 19:41:43 | `0cef0b1a` | feat: プッシュ経路を FCM のみにし OneSignal を撤去する |
| 2026-08-22 19:42:04 | `57cb41b1` | Merge pull request #218 from kosukesh34/feature/fcm-only-remove-onesignal |
| 2026-08-30 17:50:30 | `f9a10a04` | feat: 商品画像をスマレジへ自動反映し、既存分はバッチで送る |
| 2026-08-30 17:52:34 | `f3bdd24f` | Merge pull request #222 from kosukesh34/feature/smaregi-product-images |
| 2026-08-30 18:47:29 | `981dbdd7` | chore: デフォルト商品を本番と同じ名前・画像にする |
| 2026-08-30 18:50:34 | `844a9a94` | Merge pull request #223 from kosukesh34/chore/align-default-product-catalog |
| 2026-08-30 19:30:58 | `eb579364` | feat: 使わない管理画面を削除し、店舗切り替えに検索を付ける |
| 2026-08-30 19:32:17 | `de9138a4` | Merge pull request #224 from kosukesh34/feat/store-switcher-search-and-remove-unused-admin |
| 2026-09-05 15:50:28 | `8477e353` | feat: LP・LIFF・プロフィール設定を整え、EB経路とメールテストを整理する |
| 2026-09-05 15:59:04 | `dbb0a5eb` | fix: お知らせ配信の確認パネルを右側 sticky に戻す |
| 2026-09-05 21:45:56 | `169dc8b2` | Merge pull request #226 from kosukesh34/feat/lp-liff-profile-mail-and-remove-eb |
| 2026-09-10 21:33:22 | `2babf19e` | feat: 会員プロフィール画像を追加し、会員詳細と配信画面のUIを整える |
| 2026-09-10 21:39:07 | `80e5cee7` | Merge pull request #227 from kosukesh34/feature/member-profile-image-and-admin-ux |
| 2026-09-10 22:18:51 | `cb7753df` | feat: LP導入登録で店舗と管理者を自動発行しLIFF URLを表示する |
| 2026-09-10 22:37:09 | `e32c5254` | Merge pull request #228 from kosukesh34/feature/lp-landing-auto-provision |
| 2026-09-19 17:27:58 | `2200f1fe` | docs: add Reggit product purpose |
| 2026-09-20 01:58:55 | `9ac1824c` | feat: LPをエディトリアルデザインへ刷新 |
| 2026-09-20 02:03:13 | `88644c3e` | test: LP刷新後の画像構成を検証 |
| 2026-09-20 02:10:38 | `96b48eb7` | Merge pull request #233 from kosukesh34/codex/landing-editorial-redesign |
| 2026-09-20 02:01:31 | `d71a7ce1` | feat: ホーム・会員証・商品設定を拡張 |
| 2026-09-20 02:02:12 | `36b7ece0` | feat: ネイティブ向け決済・クーポン・同期APIを追加 |
| 2026-09-20 02:09:00 | `53f5c943` | fix: APIルートのコントローラーimportを整理 |
| 2026-09-20 02:04:22 | `9390492c` | feat: ネイティブ顧客アプリとLIFF体験を刷新 |
| 2026-09-20 02:15:28 | `fee63cc5` | Merge pull request #234 from kosukesh34/codex/admin-home-and-product-settings |
| 2026-09-20 02:22:02 | `d5c1bccb` | Merge pull request #235 from kosukesh34/codex/native-commerce-and-realtime-api |
| 2026-09-20 02:23:16 | `848d6fa1` | Merge pull request #236 from kosukesh34/codex/native-client-and-liff-experience |
| 2026-09-20 10:45:49 | `348c198c` | fix: address review findings across merged features |
| 2026-09-20 19:32:28 | `92823eaf` | Merge pull request #237 from kosukesh34/codex/review-fixes-233-236 |
| 2026-09-23 12:35:49 | `8b1a2f97` | feat: configure native navigation and purchase reward games |
| 2026-09-23 19:50:19 | `d2ede4f1` | Merge pull request #238 from kosukesh34/codex/existing-native-lottery-settings |
| 2026-09-23 20:02:55 | `f367cab4` | fix: enforce API authorization and secure orders and UI |
| 2026-09-23 20:03:10 | `6a806724` | Merge remote-tracking branch 'origin/main' into codex/security-review-remediation |
| 2026-09-23 20:03:52 | `6eb833bd` | docs: record successful iOS device builds |
| 2026-09-23 20:08:41 | `cc550510` | chore: omit generated Xcode package resolution |
| 2026-09-23 20:09:56 | `1f7793f2` | Merge pull request #239 from kosukesh34/codex/security-review-remediation |
| 2026-09-23 20:39:46 | `5a22ef65` | Harden tenant and identity boundaries and payment session flow |
| 2026-09-23 20:51:22 | `16f702db` | Merge pull request #240 from kosukesh34/codex/architecture-hardening |
| 2026-09-23 21:19:10 | `0c6100be` | Fail closed for unbound HTTP tenant queries |
| 2026-09-23 21:34:13 | `ab268819` | Merge pull request #241 from kosukesh34/codex/tenant-scope-hardening |
| 2026-09-24 22:30:00 | `df596cca` | fix(security): スマレジ利用者契約通知のトークン検証と店舗登録の権限を強化 |
| 2026-09-24 22:30:00 | `8ce1a7b1` | feat(reservation): 予約コースを追加 |
| 2026-09-24 22:30:00 | `c835350e` | feat(admin): 店舗設定にスタッフ・権限管理を追加 |
| 2026-09-24 22:30:00 | `f350d1f1` | refactor(admin): 設定一覧を刷新し、スマレジ連携・ギャラリー設定画面を廃止 |
| 2026-09-24 22:30:00 | `25d1aae5` | feat(home): ホーム画像の複数枚登録とネイティブナビ設定を調整 |
| 2026-09-24 22:30:00 | `a55d1e71` | feat(native): ネイティブアプリにアプリ情報・ホーム画像スライド・会員証のリアルタイム更新を追加 |
| 2026-09-24 22:30:00 | `d8d2c640` | refactor(member): 会員一覧クエリを分離し、会員詳細の閲覧でデータを更新しない |
| 2026-09-24 22:30:00 | `77d88485` | style(member): 会員詳細のサマリーを整理しUIを改善 |
| 2026-09-24 22:30:00 | `68e3f0ae` | feat(smaregi): スマレジの商品オプションをモバイルオーダーの選択肢として同期 |
| 2026-09-24 22:30:00 | `6265fc6d` | refactor(product): 商品一覧・詳細の取得を ProductReadRepository 経由にする |
| 2026-09-24 22:30:00 | `29e1b977` | test: 画面巡回E2Eと複数端末の注文フローテストを追加 |
| 2026-09-24 22:30:00 | `741da96a` | style(liff): ガチャ結果ポップアップのサイズを縮小 |
| 2026-09-24 22:30:00 | `65b238ca` | chore(build): Vite ビルド成果物を更新 |
| 2026-09-24 22:30:00 | `789fa78d` | fix(smaregi): 会員登録時の氏名プレースホルダ（— / ー）を会員情報に取り込まない |
| 2026-09-24 22:30:00 | `746a4f7b` | feat(product): 販売停止の商品を在庫付きで表示し、オプションのマイナス料金を許可する |
| 2026-09-24 22:30:00 | `13963cef` | feat(liff): LINE専用の画面設定をやめ、お客様画面をSkipと同じ構成にする |
| 2026-09-24 22:30:00 | `c01e4d53` | feat(member): 機種変更用の引き継ぎコードを発行・利用できるようにする |
| 2026-09-24 22:30:00 | `0e5c746c` | feat(member): スマレジの既存会員を確認メールでアプリ会員に結びつける |
| 2026-09-24 22:30:00 | `2bf6ee44` | feat(analytics): 分析条件に合う会員へクーポンを配信できるようにする |
| 2026-09-24 22:30:00 | `c8f5293f` | feat(help): 店舗向けヘルプとシステム管理のヘルプ記事を追加する |
| 2026-09-24 22:30:00 | `95b01344` | chore(build): Vite ビルド成果物を更新 |
| 2026-09-24 22:30:00 | `e7d92804` | fix: ローカル変更を統合し会員引き継ぎ画面を修正する |
| 2026-09-24 22:30:49 | `7490c007` | fix(member): 確認メールで店舗コンテキストを復元する |
| 2026-09-24 22:31:39 | `1e5c9b00` | style: CIの整形チェックに合わせる |
| 2026-09-24 22:33:08 | `7c756a15` | chore(build): レビュー修正後のVite成果物を更新 |
| 2026-09-24 22:36:18 | `ebe6bcf4` | fix(member): セルフ連携にスマレジ登録メールの確認を必須にする |
| 2026-09-24 22:40:33 | `49dcc24b` | docs(smaregi): 利用者契約通知URLに認証トークンを記載する |
| 2026-09-24 22:47:12 | `389e76d5` | test(liff): 現行のマイページ導線でE2Eを確認する |
| 2026-09-24 22:52:53 | `be5bbd45` | test: wait for LIFF login prompt before navigation check |
| 2026-09-24 22:57:38 | `4619bf1a` | test: handle optional LIFF login prompt in navigation smoke |
| 2026-09-24 22:59:04 | `76e16019` | test: use shared queue navigation across demo settings |
| 2026-09-24 23:04:47 | `41f5fa71` | Merge pull request #242 from kosukesh34/codex/publish-reviewed-20260924 |
| 2026-09-24 23:08:30 | `00950066` | fix(deploy): preserve production storage symlink |
| 2026-09-24 23:12:47 | `6cb5072d` | fix(deploy): create storage link only when absent |
| 2026-09-24 23:17:37 | `ec83a434` | test: use unique queue numbers in stats fixtures |
| 2026-09-24 23:25:21 | `37056c43` | test: generate unique queue numbers in factories |
| 2026-09-24 23:30:18 | `9cb8d8d0` | Merge pull request #243 from kosukesh34/codex/preserve-production-storage-link |
| 2026-09-25 17:01:26 | `fd745c78` | feat: unify Skip and web customer app design |
| 2026-09-26 20:38:48 | `f63df464` | feat: add native store search |
| 2026-09-26 20:45:08 | `1f665256` | chore: sync local notification and asset changes |
| 2026-09-26 20:52:37 | `7955892e` | refactor: enforce application layer dependency direction |
| 2026-09-26 20:52:41 | `2f5dacc0` | refactor: centralize Smaregi sync eligibility rule |
| 2026-09-26 20:54:57 | `13d3c792` | refactor: isolate admin coupon Smaregi sync endpoints |
| 2026-09-26 21:05:01 | `da07b399` | refactor: isolate admin coupon delivery endpoints |
| 2026-09-26 21:19:38 | `d41164ee` | test: align browser checks with tenant UI |
| 2026-09-26 21:27:31 | `667f16ee` | refactor: isolate admin member activity endpoints |
| 2026-09-26 21:32:53 | `364cfb10` | refactor: isolate admin announcement delivery endpoints |
| 2026-09-26 21:36:42 | `57a1c1b9` | refactor: isolate admin coupon bulk issue endpoints |
| 2026-09-26 21:41:48 | `17274104` | refactor: isolate admin reservation status endpoints |
| 2026-09-26 21:46:30 | `acf32510` | refactor: isolate admin product mutations |
| 2026-09-26 21:49:43 | `b6fa5ff9` | refactor: isolate admin stamp grant QR endpoints |
| 2026-09-26 21:56:33 | `433c3417` | refactor: isolate admin member history endpoints |
| 2026-09-26 22:00:41 | `09236178` | refactor: isolate admin member index endpoints |
| 2026-09-26 22:11:02 | `56e1dfb8` | refactor: separate admin read and mutation endpoints |
| 2026-09-26 22:18:40 | `e14d55a1` | refactor: establish admin mutation boundaries |
| 2026-09-26 22:25:45 | `2cf7815d` | refactor: move settings persistence into use cases |
| 2026-09-26 22:46:44 | `6a8177c0` | refactor: extract member history query service |
| 2026-09-26 22:58:33 | `a3dc6806` | refactor: complete member service responsibility split |
| 2026-09-26 23:18:16 | `b93a87ec` | refactor: enforce application boundaries and testability |
| 2026-09-26 23:25:50 | `6f5b7bfb` | refactor: move admin help and analysis reads into use cases |
| 2026-09-27 00:03:41 | `44ef2b95` | refactor: improve architecture boundaries and frontend typing |
| 2026-09-27 10:14:26 | `95c1dab3` | refactor: strengthen application boundaries |
| 2026-09-27 11:31:23 | `68d11ccb` | refactor: narrow settings and frontend responsibilities (#246) |
| 2026-09-27 11:41:33 | `e3e16af0` | refactor: segregate queue and reservation settings (#247) |
| 2026-09-27 11:52:43 | `65425c15` | refactor: isolate notification settings contracts (#248) |
| 2026-09-27 11:58:43 | `8228cb97` | refactor: isolate stamp settings contract (#249) |
| 2026-09-27 12:05:51 | `fcb1cf6e` | refactor: isolate wallet settings contract (#250) |
| 2026-09-27 12:15:17 | `cf76d83b` | refactor: split admin settings contracts in batch (#251) |
| 2026-09-27 12:56:04 | `ba9e3bf7` | refactor: narrow member settings dependencies in batch (#252) |
| 2026-09-27 13:07:51 | `1d5f50de` | refactor: eliminate broad settings dependencies (#253) |
| 2026-09-27 16:41:41 | `d1c33bc1` | refactor: extract member state templates (#254) |
| 2026-09-27 16:48:58 | `2fa64a62` | refactor: extract postal address policy (#255) |
| 2026-09-27 16:57:56 | `24e69c36` | refactor: extract point history formatter (#256) |
| 2026-09-27 17:07:59 | `30037010` | refactor: extract member profile form renderer (#257) |
| 2026-09-27 17:17:53 | `73d89bf9` | perf: split frontend routes into lazy chunks (#258) |
| 2026-09-27 17:27:45 | `07449947` | refactor: isolate frontend navigation and bootstrap (#259) |
| 2026-09-27 18:09:52 | `af9ef775` | refactor architecture boundaries and transactional safety |
| 2026-09-27 19:24:05 | `cf9531ce` | centralize post-commit event dispatch |
| 2026-09-27 20:06:25 | `bf57962f` | harden member mutation error boundaries |
| 2026-09-27 22:28:16 | `6089a3dc` | feat: secure and streamline store iPad apps |
| 2026-09-27 22:32:05 | `9dd1715f` | Merge pull request #260 from kosukesh34/codex/complete-architecture-remediation |
| 2026-09-28 20:50:51 | `41e7bf54` | feat: integrate reviewed store and admin updates |
| 2026-09-28 20:56:45 | `121ad7e1` | Merge pull request #261 from kosukesh34/codex/review-merge-20260928 |
| 2026-09-29 16:19:29 | `19df3c7b` | feat: update Skip realtime payment and admin UI |
| 2026-09-29 16:22:31 | `73a72e90` | chore: ignore Skip derived data |
| 2026-09-30 08:20:24 | `aa885152` | fix: support Android Skip app build |
| 2026-09-30 08:30:35 | `77b394a4` | fix: use requested project key in iOS app |
| 2026-09-30 08:50:23 | `8d4bd23e` | feat: confirm Stripe checkout after mobile return |
| 2026-09-30 15:14:30 | `e443dd3b` | feat: add admin password reset and self-service password changes |
| 2026-09-30 15:14:30 | `5a8e0bca` | feat: separate project accounts and scope staff branch permissions |
| 2026-09-30 15:14:30 | `7b56c7c7` | feat: simplify synchronized store selection and enforce store limits |
| 2026-09-30 15:14:30 | `56414504` | feat: notify mobile customers of order and queue status changes |
| 2026-09-30 15:14:31 | `8c0fd854` | feat: support configured member registration fields in native apps |
| 2026-09-30 15:14:31 | `f8808c6a` | fix: refresh native screens and route incoming push notifications |
| 2026-09-30 15:15:40 | `ec098a40` | feat: automatically present purchase lotteries and add game previews |
| 2026-09-30 15:15:40 | `438dceb5` | refactor: centralize mobile development and production connection settings |
| 2026-09-30 15:41:10 | `c6cd9fdf` | fix: wait for user interaction before playing lottery games |
| 2026-10-01 08:44:53 | `5e39b22f` | feat: separate store operation, app design and member settings |
| 2026-10-01 08:44:53 | `17b5feb9` | feat: improve system publishing and add help article previews |
| 2026-10-01 08:44:53 | `4d64dd1a` | feat: honor external web page settings and support browser popups |
| 2026-10-01 08:44:53 | `4dc02f77` | feat: show announcement cards, protected images and tab badges |
| 2026-10-01 08:44:53 | `5c4bfd91` | feat: refine lottery animations and scratch reveal timing |
| 2026-10-01 08:44:53 | `b2a97587` | chore: launch the native iOS app in Release configuration |
| 2026-10-04 18:11:44 | `c44ccc2b` | Add QR issuance hub for app links, mobile orders, points and stamps |
| 2026-10-06 13:49:58 | `e2bdd50a` | fix(auth): invalidate admin sessions after password changes |
| 2026-10-06 13:49:58 | `ca4cf1dd` | fix(tenancy): restrict shared mutations and trust authenticated queue clients |
| 2026-10-06 13:49:59 | `1fb3b5d2` | fix(members): bind existing-member confirmation and revoke transferred sessions |
| 2026-10-06 13:49:59 | `501f7649` | fix(reservations): serialize capacity checks and writes |
| 2026-10-06 13:49:59 | `bd7c8d42` | fix(smaregi): restrict deletion to owned product image files |
| 2026-10-06 13:50:00 | `3ea15b6c` | feat(admin): unify member identifiers and simplify member editing |
| 2026-10-06 13:50:00 | `4bf496dd` | feat(analytics): configure RFM thresholds per project |
| 2026-10-06 14:00:38 | `8f02e1d0` | feat(delivery): share audience filters and save member audience groups |
| 2026-10-06 14:00:39 | `668a5431` | feat(orders): persist option selections and restore native reorder carts |
| 2026-10-06 14:00:39 | `44e15b2d` | fix(mobile): align native layouts and replay Android push tokens |
| 2026-10-06 14:00:39 | `c2f3aae1` | fix(wallet): broadcast committed changes and refresh native reward state |
| 2026-10-06 14:00:39 | `85600369` | feat(admin): improve help tooltips and guard unsaved profile changes |
| 2026-10-06 14:00:40 | `6cc40bd5` | build(web): regenerate production assets |
| 2026-10-06 14:00:40 | `be2d8631` | chore(skills): add security audit workflow and report validators |
| 2026-10-06 14:01:04 | `d1796726` | style(routes): keep member and audience endpoints beside related routes |
| 2026-10-06 14:23:54 | `8c176955` | fix: secure admin seeding and session API CSRF protection |
| 2026-10-06 14:36:32 | `3d472746` | docs: consolidate project documentation and remove redundant guides |
| 2026-10-06 15:40:55 | `ccdb0044` | feat: upgrade Laravel 13 and add email-free card payments |
| 2026-10-06 23:47:18 | `eb2bf49f` | Merge pull request #264 from kosukesh34/codex/publish-20261006-01 |
| 2026-10-06 23:50:21 | `7dfb030d` | fix(auth): exclude admin guards from customer user resolution |
| 2026-10-06 23:51:21 | `7d5b632c` | merge: include standalone customer user guard in member session changes |
| 2026-10-07 00:04:57 | `0246121d` | Merge pull request #265 from kosukesh34/codex/publish-20261006-02 |
| 2026-10-07 00:05:04 | `3c47dbf2` | Merge pull request #266 from kosukesh34/codex/publish-20261006-03 |
| 2026-10-07 00:05:11 | `83a85111` | Merge pull request #267 from kosukesh34/codex/publish-20261006-04 |
| 2026-10-07 00:05:17 | `7b960d4c` | Merge pull request #268 from kosukesh34/codex/publish-20261006-05 |
| 2026-10-07 00:05:24 | `8aa8381c` | Merge pull request #269 from kosukesh34/codex/publish-20261006-06 |
| 2026-10-07 00:05:32 | `e5cfc247` | Merge pull request #270 from kosukesh34/codex/publish-20261006-07 |
| 2026-10-07 00:05:38 | `3202cd4b` | Merge pull request #271 from kosukesh34/codex/publish-20261006-08 |
| 2026-10-07 00:05:45 | `c166305a` | Merge pull request #272 from kosukesh34/codex/publish-20261006-09 |
| 2026-10-07 00:05:52 | `bc26323e` | Merge pull request #273 from kosukesh34/codex/publish-20261006-10 |
| 2026-10-07 00:05:59 | `9acb6f6e` | Merge pull request #274 from kosukesh34/codex/publish-20261006-11 |
| 2026-10-07 00:06:06 | `45efb9df` | Merge pull request #275 from kosukesh34/codex/publish-20261006-12 |
| 2026-10-07 00:06:13 | `b5611805` | Merge pull request #276 from kosukesh34/codex/publish-20261006-13 |
| 2026-10-07 00:06:20 | `f41993bf` | Merge pull request #277 from kosukesh34/codex/publish-20261006-14 |
| 2026-10-07 00:06:27 | `b8dfcd03` | Merge pull request #278 from kosukesh34/codex/publish-20261006-15 |
| 2026-10-07 00:06:34 | `ecd297e5` | Merge pull request #279 from kosukesh34/codex/publish-20261006-16 |
| 2026-10-07 00:06:41 | `e6efd65f` | Merge pull request #280 from kosukesh34/codex/publish-20261006-17 |
| 2026-10-07 00:06:49 | `572ca6a3` | Merge pull request #281 from kosukesh34/codex/publish-20261006-18 |

---

## 全マージ PR 一覧（日時付き・時系列）

マージ日時はすべて **JST（UTC+9）**。

| マージ日時（JST） | PR | タイトル |
|---|---:|---|
| 2026-01-25 16:25:14 | #1 | リファクタリング |
| 2026-01-25 16:50:07 | #2 | 管理画面のUIの修正 |
| 2026-01-25 20:13:24 | #3 | 予約機能の会員制限とセキュリティ改善 |
| 2026-01-31 19:19:52 | #4 | 予約のキャンセル・編集、会員証・LIFF・予約カレンダーUI改善 |
| 2026-02-01 11:14:08 | #6 | feat: DB設計・責務分離・キャンセル処理・会員発行クーポン対応 |
| 2026-02-14 23:16:31 | #7 | Admin/LIFF enhancements |
| 2026-02-15 01:38:51 | #8 | feature: 管理画面・API・UseCaseリファクタリング |
| 2026-02-15 13:47:30 | #9 | feat: プラン機能・機能ON/OFF設定・システム管理者ナビゲーション改善 |
| 2026-02-22 11:54:27 | #10 | feat: Apple Wallet 会員証パス（.pkpass）対応 |
| 2026-02-22 15:34:16 | #11 | refactor: DTOをData(Result/View/Input)にリネーム、PHPDoc具体型化、不要doc削除 |
| 2026-02-26 15:43:15 | #12 | feat: multi-tenant admin/LIFF/queue hardening and ops improvements |
| 2026-02-26 18:03:00 | #13 | feat: 会員プロフィールに郵便番号で住所自動入力（日本郵便API） |
| 2026-02-26 19:02:41 | #14 | docs: README を追加（アプリ概要・環境構築・API・iPad 連携） |
| 2026-02-28 13:29:22 | #15 | feat: 管理画面設定・Google Wallet・会員編集・アナウンス配信など |
| 2026-02-28 13:29:41 | #16 | feat: Google Wallet 会員証をバーコード・日本語・クロスプラットフォーム統一 |
| 2026-02-28 17:08:25 | #18 | Add Skip mobile webview app and device registration/push foundation |
| 2026-02-28 17:15:08 | #17 | fix: スマレジクーポン登録・クーポン管理改善 |
| 2026-03-01 21:06:25 | #19 | feat: スタンプ・ランク・退会・モバイルアプリ・Android WebView 対応 |
| 2026-03-02 14:42:49 | #20 | chore: CODING_STANDARDS 準拠と CI/レビュー設定 |
| 2026-03-02 14:48:31 | #21 | Refine LIFF designer and stabilize checkout/payment flows |
| 2026-03-03 21:50:15 | #22 | Admin設定・Smaregi連携・モバイルアプリ・会員退会などの改善 |
| 2026-03-03 22:13:08 | #23 | feat: 会員証に拡大ボタンを追加（バーコード/QR拡大表示） |
| 2026-03-14 10:11:09 | #24 | feat: 会員証拡大ボタン & プロジェクト設定移行・リファクタ |
| 2026-03-14 17:42:18 | #25 | feat: 登録後配信・お知らせセグメント・クーポン配信の追加 |
| 2026-03-14 20:04:06 | #26 | feat: 予約CRUD・キュー一覧・AppConfig API・LIFF・iOS待ち番アプリ・フロント改善 |
| 2026-03-14 22:51:48 | #27 | feat: CRM機能・RFM分析・会員タグ・サンプルデータシーダー・スマレジ同期修正 |
| 2026-03-17 19:51:20 | #28 | feat: AWS EB deployment setup and mobile queue notification/link updates |
| 2026-03-21 16:58:54 | #29 | 最新のバージョンにアップデート |
| 2026-03-21 21:49:22 | #31 | アプリ名の変更（Reggit への統一） |
| 2026-03-22 12:35:43 | #32 | 非本番でデモ用管理ユーザー（demo_sys）をシード・同期で確実に反映 |
| 2026-04-01 23:36:59 | #34 | feat(seeder): free food images and expanded default products |
| 2026-05-30 16:58:15 | #41 | PR #37 へ CI修正・Codex対応を追加 |
| 2026-05-31 18:40:14 | #37 | Fix Smaregi point sync and wallet pass updates |
| 2026-05-31 19:09:49 | #43 | fix: LIFF 会員識別の修正・退会ユーザー非復元・PR ワークフロー |
| 2026-05-31 19:48:03 | #44 | fix: テスト実行時に開発 DB (reggit) が RefreshDatabase で消える |
| 2026-05-31 19:52:24 | #45 | chore: コーディング規約とレビュー手順をアーキテクチャ重視に更新 |
| 2026-05-31 21:22:02 | #46 | fix: 順番待ち通知のURLを正しいLIFF形式に修正 |
| 2026-05-31 22:11:06 | #47 | feat: LIFF UI刷新・Wallet追加修正・プロフィール編集改善 |
| 2026-05-31 22:27:17 | #48 | fix: スマレジ商品重複排除と在庫同期の修正 |
| 2026-06-05 14:09:47 | #53 | feat: ネイティブアプリのクーポン通知・Apple Wallet・シェル改善 |
| 2026-06-05 15:03:55 | #54 | chore: アプリ名とネイティブ識別子を Reggit に統一 |
| 2026-06-10 09:35:28 | #55 | chore: Apple Wallet デフォルト画像を Reggit ブランドに差し替え |
| 2026-06-12 20:05:55 | #62 | feat: OneSignal の collapse_id でプッシュ通知を置き換え可能に |
| 2026-06-13 22:17:08 | #63 | feat: 会員クーポン使用と管理画面一括配布の拡充 |
| 2026-06-13 22:34:37 | #64 | feat: クーポン利用開始前の表示とスタンプ・一括配布UIを改善 |
| 2026-06-22 09:23:55 | #66 | refactor: Skip ネイティブアプリの SOLID 再構成と品質改善 |
| 2026-06-22 09:32:37 | #65 | feat: LIFF商品詳細・オプション注文とUI刷新 |
| 2026-07-08 17:00:01 | #67 | feat: LIFFデザイナーのテーマ・テンプレート・タブ編集を拡張 |
| 2026-07-08 17:35:25 | #68 | feat: migrate frontend/admin to Vite React TypeScript |
| 2026-07-10 14:17:04 | #69 | feat: legacy JS の Vite/TS 移行と LIFFデザイナーの React 化 |
| 2026-07-11 00:50:05 | #70 | refactor: Clean Architecture / DDD へドメイン層を導入し UseCase を整理する |
| 2026-07-17 22:01:23 | #71 | feat: LIFFデザイナーのiPhoneベゼル・詳細レイアウト・ページ遷移 |
| 2026-07-18 17:05:17 | #72 | feat: LIFF App Studio テンプレ刷新とネイティブ風画面体験 |
| 2026-07-18 18:22:59 | #74 | feat: 順番待ち一覧の表示・採番・再呼び出しを改善する |
| 2026-07-18 18:30:20 | #75 | feat: 会員チャネル・セグメント配信とスマレジ登録必須化 |
| 2026-07-18 18:55:20 | #76 | feat: お知らせの削除修正・画像設定とLIFFデザイン改善 |
| 2026-07-18 19:05:15 | #77 | fix: 会員登録ボタン連打で複数会員が作成される |
| 2026-07-18 22:01:06 | #78 | fix: Android ネイティブタブ表示とプルリロードを改善する |
| 2026-07-19 00:04:35 | #79 | feat: お知らせプッシュ取消しと管理画面既読数を追加する |
| 2026-07-19 00:15:22 | #80 | fix: Android でネイティブタブが再表示されない問題を修正する |
| 2026-07-19 00:34:02 | #81 | feat: お知らせ既読から会員詳細へ遷移し、LINE配信を追加する |
| 2026-07-19 00:54:16 | #82 | feat: 登録チャネルの iOS/Android 表示分離と会員詳細リンク |
| 2026-07-19 01:36:00 | #83 | feat: 来店・スタンプ調整と日次番号採番を追加する |
| 2026-07-19 02:04:03 | #96 | feat: スマレジ Webhook のカスタムヘッダーをミドルウェアで検証する |
| 2026-07-19 02:47:52 | #98 | feat: スマレジPOS取引明細・購入商品セグメント配信 |
| 2026-07-19 16:11:15 | #100 | feat: 順番待ちの起動アプリ選択とネイティブ連携・表示を整備する |
| 2026-07-20 01:02:19 | #101 | feat: クーポン配信のランクアップ達成・詳細確認を追加する |
| 2026-07-20 02:31:19 | #102 | feat: 会員詳細の履歴強化と配信詳細の会員リスト統合 |
| 2026-07-20 14:41:55 | #103 | feat: マルチテナント対応（オンボーディング・LIFF設定・端末プロジェクト選択） |
| 2026-07-20 15:02:46 | #104 | chore: project_key の default フォールバック削除 |
| 2026-07-20 16:43:00 | #105 | feat: 会員登録モード設定と退会・LIFF登録フォーム自動表示 |
| 2026-07-20 17:13:47 | #106 | improve: オンボーディング中央寄せと管理画面アクセントを青に変更 |
| 2026-07-20 19:56:23 | #107 | feat: LIFFネイティブ品質改善と外部ページタブ・実データテンプレ |
| 2026-07-20 20:04:22 | #108 | feat: 外部ページタブの戻る導線と新規タブ抑止 |
| 2026-07-20 21:29:59 | #109 | feat: 案内メール受取フラグを会員プロフィールとスマレジに連携する |
| 2026-07-20 22:58:39 | #110 | feat: ランク別ポイント付与とスマレジ取引の付与・取消・返品連携 |
| 2026-07-20 23:59:20 | #111 | refactor: スマレジ取引 inbound 同期を責務分割する |
| 2026-07-21 22:48:12 | #113 | feat: トップにプロダクト紹介LPを追加する |
| 2026-07-22 00:22:18 | #114 | feat: iPad待ち受けの連携表示と再呼出・入店を整える |
| 2026-07-22 17:22:12 | #116 | fix: 管理画面の会員ランク・プロフィール変更をスマレジへ反映する |
| 2026-07-22 20:08:22 | #112 | feat: スマレジと注文の双方向連携 |
| 2026-07-22 21:58:07 | #118 | fix: 会員ランクのポイント付与率をスマレジ顧客へ反映する |
| 2026-07-22 23:15:12 | #119 | feat: トップLP刷新と導入ブランドロゴ精緻化 |
| 2026-07-23 00:02:24 | #120 | feat: LIFF顧客画面をLPと同系の店舗トーンに統一する |
| 2026-07-23 00:15:15 | #121 | feat: LP導入ロゴの一部を色地＋白文字に |
| 2026-07-23 15:39:51 | #123 | feat: LIFF顧客画面をiOS HIG固定UIに刷新する |
| 2026-07-24 09:03:39 | #124 | feat: /liffデモと店舗LIFFのUIを分離する |
| 2026-07-24 11:55:20 | #125 | fix: 店舗LIFFをクラシックUIに戻しデモ固定UIは維持する |
| 2026-07-24 18:32:45 | #128 | feat: クーポンガチャ権利とピンク筐体UIを追加する |
| 2026-07-24 19:43:18 | #130 | feat: ガチャハズレ・結果UIとStripe決済前スマレジ同期の修正 |
| 2026-07-24 20:26:19 | #131 | fix: ガチャ権利を取引完了時に付与する |
| 2026-07-24 21:16:38 | #132 | feat: ギャラリー利用導線とスマホ幅・LPナビの改善 |
| 2026-07-25 19:02:20 | #133 | feat: 注文ミッションタイムラインと受取可LINE通知 |
| 2026-07-25 20:44:17 | #134 | fix: LIFF注文反映・会員証UIとSkip本番接続の修正 |
| 2026-07-26 09:38:45 | #135 | feat: スプラッシュ統一・受け渡し完了表示・deploy CLI |
| 2026-07-28 15:21:35 | #137 | chore: 常時デザインルール削除とデプロイ手順ドキュメント |
| 2026-07-28 15:56:49 | #139 | fix: 機能設定の説明文を実態に合わせる |
| 2026-07-28 16:14:34 | #140 | fix: 効かない決済機能フラグを設定画面から外す |
| 2026-07-28 16:21:09 | #138 | fix: 機能フラグOFF保存時に他機能までONになる不具合を直す |
| 2026-07-28 16:25:32 | #141 | fix: ガチャLIFFの無言失敗・無効種表示・無料表記を直す |
| 2026-07-28 16:44:35 | #142 | fix: ガチャON/OFFをガチャ設定画面に一本化する |
| 2026-07-28 16:50:54 | #143 | fix: LIFFタブ／機能フラグの表記をオーダー・会員証に揃える |
| 2026-07-28 17:01:46 | #144 | fix: 機能設定からクーポンデフォルト画像の二重編集を外す |
| 2026-07-28 17:05:52 | #145 | chore: 到達不能な旧 liff-builder 画面と関連JSを削除する |
| 2026-07-28 17:10:00 | #146 | fix: ホーム設定保存時にLIFFの外部リンクタブを消さない |
| 2026-07-28 17:14:37 | #147 | chore: 本番ドメイン表記を reggit.jp に揃える |
| 2026-07-28 22:47:29 | #148 | chore: 未使用の liff_builder_blocks PHPアクセサを削除する |
| 2026-07-28 22:52:04 | #149 | chore: 機能ON/OFFを機能設定に集約し基本設定から重複UIを除去する |
| 2026-07-28 22:56:14 | #150 | chore: 詳細設定のメイン色をLIFFデザイン主導の読み取り専用にする |
| 2026-08-04 11:37:25 | #151 | chore: 管理サイドバーを運用優先に再編しダッシュボードを整理する |
| 2026-08-04 11:43:36 | #152 | feat: 起動 Splash で LIFF 到達性確認と二重表示防止 |
| 2026-08-06 15:22:55 | #153 | feat: Playwright と Maestro の E2E を GitHub Actions に追加する |
| 2026-08-06 15:34:02 | #154 | fix: E2E Web CI のヘルスチェック失敗を直す |
| 2026-08-06 18:19:30 | #156 | chore: スマレジ取引区分と Webhook action を Enum 化する |
| 2026-08-06 18:40:06 | #157 | chore: スマレジ区分・Webhook の残りの直書きを Enum に揃える |
| 2026-08-07 10:40:15 | #158 | chore: Cursor に Ponytail ルールを導入する |
| 2026-08-07 12:55:55 | #159 | refactor: スマレジAPIのHTTP核心と取引会員解決を分離する |
| 2026-08-07 16:28:16 | #160 | chore: 運用基盤の観測性・Unitスイート・アーキテクチャ文書を整える |
| 2026-08-07 16:51:13 | #161 | refactor: ShopSetting を Repository / Store に寄せて Settings を完全 DDD 化する |
| 2026-08-07 17:05:04 | #162 | chore: CI でスマレジ連携テストを必須化し secrets 手順を文書化する |
| 2026-08-07 17:44:04 | #164 | feat: LIFF 顧客フロントを React features に移行する |
| 2026-08-07 17:44:08 | #166 | chore: Playwright E2E は main push のみ（マージ先は ver1.0.0） |
| 2026-08-07 18:51:56 | #167 | improve: LPの導入ロゴとPlatform連携ロゴを整える |
| 2026-08-08 18:51:28 | #168 | fix: LIFFデモのくじ表示・ルーレット着地判定を直す |
| 2026-08-08 22:11:11 | #169 | docs: Cloud Agent 開発環境セットアップ手順（AGENTS.md） |
| 2026-08-10 08:17:00 | #172 | feat: ver1.0.0 を main に取り込み（LIFF React ホスト復旧含む） |
| 2026-08-10 08:17:02 | #170 | docs: スマレジSHOPアプリUIデザイン参考資料を追加 |
| 2026-08-10 21:47:34 | #173 | feat: LIFF を単一 React ツリー（FrontendApp）に統合する |
| 2026-08-11 04:21:07 | #174 | feat: LINE Flex お知らせ・クーポン通知に画像を載せる |
| 2026-08-11 04:33:42 | #175 | fix: CI失敗とLINE画像URLの不具合を直す |
| 2026-08-11 05:26:30 | #176 | fix: 決済・配信・スマレジの信頼境界と管理画面表示を直す |
| 2026-08-11 18:09:51 | #177 | feat: reggit startup で KitchenMonitor を別 iPad で起動 |
| 2026-08-11 20:20:08 | #178 | feat: LIFF UI に Stripe・会員・ガチャを接続する |
| 2026-08-11 22:09:43 | #179 | fix: LIFF会員登録UIのCSSを復元する |
| 2026-08-11 23:45:48 | #180 | fix: LIFF機能CSSの不足とお知らせ記事UIを直す |
| 2026-08-12 00:32:57 | #181 | fix: LIFF会員証・クーポンUIとモバイル決済を直す |
| 2026-08-12 07:36:07 | #182 | fix: LIFF会員証表示と起動時フラッシュ・スケルトンを直す |
| 2026-08-12 18:09:26 | #183 | feat: LIFFヘッダーロゴをアップロード設定に統一する |
| 2026-08-12 18:23:48 | #184 | feat: Wallet画像をアップロード＋プレビュー表示に統一する |
| 2026-08-13 00:12:40 | #185 | fix: 順番待ちLINE通知の店舗トークン対応とクーポンバーコード表示 |
| 2026-08-13 00:56:38 | #186 | fix: 順番待ち呼び出し通知が届かない問題を修正 |
| 2026-08-13 08:33:16 | #187 | fix: ネイティブ会員の順番待ちが未連携になる問題を修正 |
| 2026-08-13 09:16:41 | #188 | fix: LIFF初期タブと順番待ちをHashRouterに揃え、本番でデモ画面を出さない |
| 2026-08-13 12:00:04 | #189 | refactor: 店舗未解決時に project_id=1 へ落とさず UseCase に寄せる |
| 2026-08-13 14:46:42 | #190 | fix: LIFF起動のちらつきとスタンプ付与QRを直す |
| 2026-08-13 19:32:01 | #191 | feat: スタンプ押す前画像・ホームレイアウトと注文画面の交換メニューを直す |
| 2026-08-13 21:19:45 | #192 | feat: LIFFでスタンプQRを読み、特典をクーポン/ガチャに限定する |
| 2026-08-13 23:29:55 | #193 | fix: LIFF起動時の画面切り替えを止める |
| 2026-08-14 01:17:40 | #194 | feat: スタンプカードの周回と付与QRの利用上限を追加する |
| 2026-08-14 01:46:33 | #195 | feat: スタンプ達成クーポンを周ごとに配布し取引キャンセルで未使用分を取り消す |
| 2026-08-14 11:51:04 | #196 | feat: 画像アップロードをD&Dに統一しLPに会員登録画面を追加する |
| 2026-08-14 12:35:18 | #197 | refactor: 画面へ渡すプロパティが3つ以上なら View クラスにまとめる |
| 2026-08-14 14:01:03 | #198 | feat: 管理画面の会員一覧・詳細の検索並び替えと表示を整える |
| 2026-08-15 00:16:58 | #199 | feat: クーポン作成と予約クーポン配信を本番で動かす |
| 2026-08-15 00:19:07 | #200 | feat: クーポン発行条件をスマレジと揃え、画像フィットを追加する |
| 2026-08-15 00:20:26 | #201 | feat: お知らせ配信の予約・対象プレビューと会員の通知設定を追加する |
| 2026-08-15 00:20:41 | #202 | feat: 登録時配信を条件達成と予約のスケジュール配信に拡張する |
| 2026-08-15 01:16:36 | #203 | fix: クーポンプレビューの表示と作成ボタンのコントラストを直す |
| 2026-08-15 01:30:21 | #204 | fix: 会員更新の通知設定を既存テストが送るようにする |
| 2026-08-15 13:59:34 | #205 | feat: クーポン配信をお知らせと同じ画面にし、通知の有無を選べるようにする |
| 2026-08-15 19:04:52 | #206 | feat: プロフィールをスマレジ会員項目に揃え任意項目を同期する |
| 2026-08-16 00:20:51 | #207 | feat: ランク期間集計とLIFF会員証の即時反映を追加する |
| 2026-08-16 01:59:07 | #208 | feat: Xserver で LIFF の Reverb をトンネル経由にする |
| 2026-08-19 08:27:45 | #209 | fix: スタンプ・ランク・配信の競合と LINE Webhook の店舗取り違え |
| 2026-08-20 22:34:39 | #210 | fix: Webhook店舗トークン・LIFF ID検証・ランク同期の副作用を直す |
| 2026-08-21 23:03:27 | #213 | feat: ランク設定で会員証デザインを選びエンドユーザーへ反映する |
| 2026-08-22 08:48:59 | #214 | fix: SettingsRanksRequest の Pint 失敗を直す |
| 2026-08-22 12:26:48 | #215 | fix: ランク付与率のデフォルト入力と登録・同期まわりを整える |
| 2026-08-22 13:57:26 | #216 | fix: MemberAdmin の DI 循環を解消し設定画面の説明枠を削除する |
| 2026-08-22 18:40:15 | #217 | feat: 顧客アプリのプッシュを OneSignal から FCM HTTP v1 へ切り替える |
| 2026-08-22 19:42:04 | #218 | feat: プッシュ経路を FCM のみにし OneSignal を撤去する |
| 2026-08-30 17:52:34 | #222 | feat: 商品画像をスマレジへ自動反映し、既存分はバッチで送る |
| 2026-08-30 18:50:34 | #223 | chore: デフォルト商品を本番と同じ名前・画像にする |
| 2026-08-30 19:32:17 | #224 | feat: 使わない管理画面を削除し、店舗切り替えに検索を付ける |
| 2026-09-05 21:45:56 | #226 | feat: LP・LIFF・プロフィール設定を整え、EB経路とメールテストを整理する |
| 2026-09-10 21:39:08 | #227 | feat: 会員プロフィール画像を追加し、会員詳細と配信画面のUIを整える |
| 2026-09-10 22:37:09 | #228 | feat: LP導入登録で店舗とLIFF URLを自動発行する |
| 2026-09-20 02:10:38 | #233 | feat: LPをエディトリアルデザインへ刷新 |
| 2026-09-20 02:15:28 | #234 | feat: ホーム・会員証・商品設定を拡張 |
| 2026-09-20 02:22:03 | #235 | feat: ネイティブ向け決済・クーポン・同期APIを追加 |
| 2026-09-20 02:23:16 | #236 | feat: ネイティブ顧客アプリとLIFF体験を刷新 |
| 2026-09-20 19:32:30 | #237 | fix: address review findings across merged features |
| 2026-09-23 19:50:19 | #238 | feat: ネイティブ画面設定と購入特典ゲームを追加 |
| 2026-09-23 20:09:56 | #239 | fix: API認可・決済・在庫・UIのセキュリティ問題を修正 |
| 2026-09-23 20:51:22 | #240 | Harden architecture boundaries and payment session flow |
| 2026-09-23 21:34:13 | #241 | Fail closed for unbound HTTP tenant queries |
| 2026-09-24 23:04:47 | #242 | feat: ローカル変更を統合し店舗・会員・顧客体験を更新 |
| 2026-09-24 23:30:18 | #243 | fix(deploy): preserve production storage symlink |
| 2026-09-26 23:18:17 | #244 | refactor: enforce application boundaries and testability |
| 2026-09-26 23:25:50 | #245 | refactor: move admin help and analysis reads into use cases |
| 2026-09-27 11:31:24 | #246 | refactor: narrow settings and frontend responsibilities |
| 2026-09-27 11:41:33 | #247 | refactor: segregate queue and reservation settings |
| 2026-09-27 11:52:43 | #248 | refactor: isolate notification settings contracts |
| 2026-09-27 11:58:44 | #249 | refactor: isolate stamp settings contract |
| 2026-09-27 12:05:51 | #250 | refactor: isolate wallet settings contract |
| 2026-09-27 12:15:17 | #251 | refactor: split admin settings contracts in batch |
| 2026-09-27 12:56:05 | #252 | refactor: narrow member settings dependencies in batch |
| 2026-09-27 13:07:51 | #253 | refactor: eliminate broad settings dependencies |
| 2026-09-27 16:41:42 | #254 | refactor: extract member state templates |
| 2026-09-27 16:48:58 | #255 | refactor: extract postal address policy |
| 2026-09-27 16:57:56 | #256 | refactor: extract point history formatter |
| 2026-09-27 17:07:59 | #257 | refactor: extract member profile form renderer |
| 2026-09-27 17:17:54 | #258 | perf: split frontend routes into lazy chunks |
| 2026-09-27 17:27:45 | #259 | refactor: isolate frontend navigation and bootstrap |
| 2026-09-27 22:32:05 | #260 | アーキテクチャ境界を改善し店舗iPad認証を強化 |
| 2026-09-28 20:56:46 | #261 | feat: review and integrate store and admin updates |
| 2026-10-06 23:47:19 | #264 | fix(auth): invalidate admin sessions after password changes |
| 2026-10-07 00:04:57 | #265 | fix(tenancy): restrict shared mutations and trust authenticated queue clients |
| 2026-10-07 00:05:04 | #266 | fix(members): bind existing-member confirmation and revoke transferred sessions |
| 2026-10-07 00:05:11 | #267 | fix(reservations): serialize capacity checks and writes |
| 2026-10-07 00:05:18 | #268 | fix(smaregi): restrict deletion to owned product image files |
| 2026-10-07 00:05:25 | #269 | feat(admin): unify member identifiers and simplify member editing |
| 2026-10-07 00:05:32 | #270 | feat(analytics): configure RFM thresholds per project |
| 2026-10-07 00:05:39 | #271 | feat(delivery): share audience filters and save member audience groups |
| 2026-10-07 00:05:45 | #272 | feat(orders): persist option selections and restore native reorder carts |
| 2026-10-07 00:05:52 | #273 | fix(mobile): align native layouts and replay Android push tokens |
| 2026-10-07 00:06:00 | #274 | fix(wallet): broadcast committed changes and refresh native reward state |
| 2026-10-07 00:06:07 | #275 | feat(admin): improve help tooltips and guard unsaved profile changes |
| 2026-10-07 00:06:14 | #276 | build(web): regenerate production assets |
| 2026-10-07 00:06:20 | #277 | chore(skills): add security audit workflow and report validators |
| 2026-10-07 00:06:27 | #278 | style(routes): keep member and audience endpoints beside related routes |
| 2026-10-07 00:06:34 | #279 | fix: secure admin seeding and session API CSRF protection |
| 2026-10-07 00:06:41 | #280 | docs: consolidate project documentation and remove redundant guides |
| 2026-10-07 00:06:49 | #281 | feat: upgrade Laravel 13 and add email-free card payments |

---

## 補足

- PR 番号は連番だが、未マージ／クローズのみの番号があるため、一覧の番号は歯抜けになる。
- Merge PR コミットは「全コミット一覧」と「全マージ PR 一覧」の両方に現れる（前者は git、後者は GitHub メタデータ）。
- 関連ドキュメント: [プロダクト](PRODUCT.md) / [アーキテクチャ](ARCHITECTURE.md) / [開発手順](DEVELOPMENT.md)
