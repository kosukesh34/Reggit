# 全機能ヘルプ記事

2026-10-12時点。既存10記事に、操作手順・処理の仕組み・注意点を持つ70記事を追加し、計80記事を標準データとして用意しています。

## 保存・表示

- 保存先：`help_articles`。全プロジェクト共通です。
- 閲覧：店舗管理画面の「ヘルプ」。公開記事をタイトル・要約・本文で検索できます。
- 編集：システム管理画面の「ヘルプ記事」。公開・非公開とプレビューに対応します。
- 原稿：`database/seeders/data/help-articles.json`。全80記事に画面リンクと画像を用意し、新規70記事は番号付き手順・完了確認・失敗時の対処を説明します。今回撮影した画面画像は56枚です。

## DBへの追加

対象環境を確認してから、ヘルプだけを投入します。DatabaseSeeder全体は実行しません。

```sh
docker compose exec app php artisan db:seed --class=HelpArticleSeeder
```

HelpArticleSeederは未登録記事を追加し、旧標準原稿と一致する記事だけを詳しい原稿に更新します。タイトル・要約・カテゴリ・本文の組合せを旧原稿のSHA-256と照合します。管理者がいずれかを編集した記事は保持し、公開状態と手動の表示順も維持します。記事の削除はしません。

旧原稿の指紋は `help-article-baselines.json`、画面と説明の対応は `help-screen-guides.json` に保存します。今後の更新時も変更前の原稿の指紋を追加して、編集済み原稿の保護を維持してください。

本文の `{project_key}` は閲覧中の店舗に置き換わります。システム管理プレビューで店舗指定がない場合は `/admin` の選択入口へ案内します。外部・プロトコル相対・実行可能なURLをリンクとして表示しません。

画像は隔離されたローカルデモ環境で撮影し、デモ例であることを表示しています。画像をクリックすると拡大できます。再撮影は `HELP_BASE_URL=http://127.0.0.1:8091 node scripts/capture-help-feature-screens.mjs` を使用します。localhost以外を拒否し、業務の保存操作は実行しません。

## 確認元・範囲

画面の入口と機能範囲は `routes/web.php`、`routes/api.php`、`AdminSettingsNavigation`、`PlanFeatures` と各画面で確認しています。業務処理の詳細は会員登録・既存会員連携・退会、注文・決済、予約、順番待ち、特典・通知のUseCase/Serviceおよび既存の運用資料に基づきます。

スマレジの既存会員連携は、申請・確認UseCase、Matcher、Writerと両方のControllerを確認しています。問い合わせはContactStoreUseCaseでDB保存を確認しています。QRの条件は発行画面とStampGrantLimitで確認しています。

UI/APIの操作を機能単位でまとめたヘルプです。個々のエンドポイントごとの仕様書ではありません。利用可能な機能はプラン・店舗・権限・外部接続設定により異なります。外部APIの実接続、実決済、メールやPushの実送信は検証していません。システム管理専用の項目にはその入口を明記しています。

## 記事一覧

| スラッグ | 項目 |
| --- | --- |
| transfer | 会員を別の端末へ引き継ぐ |
| coupon | クーポンを配信する |
| analytics | 分析を確認して全員に配信する |
| home | お客様向けの画面を変える |
| withdraw | Skipで退会する |
| delivery-cancel | 確認画面のキャンセルでは配信されない |
| delivery-immediate-error | 即時配信が失敗したとき |
| delivery-scheduled-error | 予約配信が失敗したとき |
| delivery-sent-no-undo | 配布済みはまとめて取り消せない |
| existing-link-rejected | 既存会員連携できないとき |
| feature-overview | 全機能の入口とヘルプの読み方 |
| admin-login | 管理画面へのログイン・ログアウト・パスワード変更 |
| dashboard-guide | ダッシュボードと運営からの案内 |
| stores-guide | 利用店舗の同期・選択と複数店舗運用 |
| features-guide | 利用機能と契約プランを確認する |
| member-registration | 会員登録と会員証を使う |
| member-registration-settings | 会員登録方法・プロフィール項目・会員番号を設定する |
| member-profile | お客様がプロフィール・画像・通知設定を変更する |
| member-management | 会員検索・詳細・編集・CSV出力 |
| member-history | 会員の注文・取引・来店・ポイント履歴を確認する |
| member-tags-memos | 会員タグ・メモ・手動来店記録を管理する |
| points-guide | ポイントの獲得・利用・期限・履歴 |
| point-qr | ポイント付与QRを発行・受け取る |
| ranks-guide | 会員ランクの判定・特典を設定する |
| existing-member-link | スマレジの既存会員をアプリに連携する |
| existing-member-store | 管理画面の「店舗で連携する」とお客様の連携の違い |
| member-transfer-guide | 会員引き継ぎの仕組みと旧端末の扱い |
| withdrawal-guide | 退会の操作とスマレジ会員への影響 |
| products-guide | 商品・価格・在庫・オプションを管理する |
| orders-customer | お客様が商品を注文し注文履歴を確認する |
| orders-staff | 店舗で注文を受け付け調理・受取完了にする |
| order-payment-timing | 現金注文とオンライン決済で特典が付くタイミング |
| unpaid-cancel | 未決済オンライン注文の取消とポイント返却 |
| payments-settings | 決済・入金先・Stripeを設定する |
| reservations-customer | お客様が予約・変更・キャンセルする |
| reservations-staff | 予約一覧・作成・変更・状態管理 |
| reservations-settings | 予約時間枠・受付上限・コースを設定する |
| queue-customer | お客様が順番待ちを受付・確認・取消する |
| queue-staff | 順番待ちの呼出・再呼出・入店・取消 |
| queue-settings | 順番待ちの待ち時間・受付条件を設定する |
| store-devices | Kitchen・WaitingのiPadを登録・再登録する |
| stamps-guide | スタンプカード・達成特典・終了方式を設定する |
| stamp-grant | スタンプを手動・QRで付与する |
| coupons-definition | クーポンを作成・編集して利用状況を確認する |
| coupons-use | お客様がクーポンを表示・使用する |
| coupons-bulk | クーポンの一括発行・個別発行・取消 |
| device-coupons | 端末1回限定クーポンを受け取る |
| coupon-smaregi | スマレジとクーポンを同期する |
| lottery-guide | ガチャ・スクラッチ・ルーレットを設定・利用する |
| rewards-cancellation | POSの会計取消・返品で特典はどう戻るか |
| announcements-guide | お知らせを作成・公開・編集する |
| announcement-delivery | お知らせを対象者へ即時・予約配信する |
| audience-groups | 配信対象の条件・グループ・プレビューを使う |
| after-registration-guide | 登録後配信・定期配信・ランク到達配信を設定する |
| delivery-recovery | 配信失敗・保留・通知未完了を確認する |
| notifications-guide | LINE・Push・会員の通知設定を確認する |
| analytics-guide | 売上・会員・特典の分析を確認する |
| rfm-guide | RFMで会員を分類して施策に使う |
| analysis-api | 分析連携・APIトークン・外部からの配信要求 |
| home-design-guide | ホーム・メニュー・タブ・機能の並びを変更する |
| branding-guide | 店舗名・ロゴ・画像・アプリの配色を変更する |
| member-card-design | 会員証のデザイン・画像を設定する |
| liff-designer-guide | LIFFデザイナーで画面ブロックを編集する |
| admin-design-guide | 管理画面の配色を変更する |
| wallet-guide | Apple Wallet・Google Walletに会員証を追加する |
| line-connection-guide | LINEログイン・LIFF・友だち追加・Messaging APIを設定する |
| smaregi-guide | スマレジの会員・商品・取引・ポイント同期を理解する |
| store-operation-settings | 営業時間・注文方法・通知などの店舗運用設定 |
| staff-permissions | スタッフを追加して操作権限を設定する |
| audit-log-guide | 操作履歴・監査ログを確認する |
| gallery-guide | ギャラリーのカテゴリ・画像・紹介を管理する |
| contact-guide | お客様が問い合わせを送信する |
| legal-guide | 利用規約・プライバシーポリシーを確認する |
| help-management | ヘルプの検索・公開・編集・プレビュー |
| onboarding-guide | 新規導入のメール認証・登録・管理画面への移動 |
| system-projects | システム管理者がプロジェクト・プラン・アプリリンクを管理する |
| system-accounts | システム管理者が管理アカウントを管理する |
| system-notices | システム管理者が運営からの案内を公開する |
| native-session | ネイティブアプリの端末登録・ログイン状態を確認する |
| entry-qr-guide | LINE・アプリダウンロードの案内QRを保存する |

## 検証と本番反映

関連PHPテストは17件・792アサーションが成功しました。専用のローカルデモ環境で全80記事の画像、店舗別リンク、スマートフォン表示を確認するE2E 2件も成功しました。画像の参照先56枚の存在を確認し、画面のデモ表示と本文の読みやすさを視覚確認しています。

必須の全ローカルCIは最新PR HEADに対してマージスクリプトで実行し、対象HEADとログをPRへ記録します。GitHub Actionsは使用しません。本番の記事は事前にバックアップし、コミット済みmainのデプロイ後、ヘルプだけをトランザクションでseedして確認します。外部APIの実接続、実決済、実メールやPush送信はヘルプの検証対象外です。

旧記事の更新マイグレーションが現在のSeeder原稿を参照しているため、新しいスラッグを旧本文の更新対象から除く互換修正を含みます。新しいスキーマ変更はありません。

