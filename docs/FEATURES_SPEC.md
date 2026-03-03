# 機能一覧・仕様（細かい仕様決め用）

各機能ごとのサブ機能と仕様をまとめています。追加された機能は末尾で明示しています。

---

## 1. 管理画面全体

| 大分類 | 機能 | ルート例 | 説明 |
|--------|------|----------|------|
| 認証 | プロジェクト選択 | `admin.login` | ログインするプロジェクトを選択（GET） |
| 認証 | プロジェクト別ログイン | `admin.project.login` | 指定プロジェクトのログイン（GET/POST） |
| 認証 | ログアウト | `admin.logout` | セッション破棄 |
| ダッシュボード | ダッシュボード | `admin.dashboard` | 統計・直近注文など |
| 注文 | 注文一覧 | `admin.orders` | 注文一覧表示 |
| 注文 | 注文詳細 | `admin.orders.detail` | 注文詳細・ステータス更新・完了 |
| 商品 | 商品一覧・CRUD | `admin.products` 等 | 商品の一覧・作成・編集・削除 |
| 会員 | 会員一覧・詳細 | `admin.members` / `admin.members.detail` | 会員一覧・詳細（最終来店日・購入履歴・ランク等） |
| 予約 | 予約一覧・詳細・ステータス | `admin.reservations` 等 | 予約一覧・詳細・ステータス更新・完了 |
| 順番待ち | 順番待ち一覧・ステータス | `admin.queue` / `admin.queue.status` | 待ち状況・ステータス更新 |
| スタンプ | スタンプCRUD | `admin.stamps` 等 | スタンプ一覧・作成・編集・削除 |
| クーポン | クーポンCRUD | `admin.coupons` 等 | クーポン一覧・作成・編集・削除 |
| お知らせ | お知らせCRUD | `admin.announcements` 等 | お知らせ一覧・作成・編集・削除 |
| 設定 | 基本設定 | `admin.settings.basic` | 営業時間・予約枠・定休日・機能フラグ等 |
| 設定 | 詳細設定 | `admin.settings.advanced` | LINEテーマ色等 |
| 設定 | ホーム設定 | `admin.settings.home` | LIFFのタブ表示ON/OFF・並び順・ラベル・アイコン（旧メニュー設定は統合済み。`admin.settings.menu` はホームへリダイレクト） |
| 設定 | 機能設定 | `admin.settings.features` | 機能の有効/無効 |
| 設定 | ランク設定 | `admin.settings.ranks` | 会員ランク設定 |
| 設定 | **LIFFデザイナー** | `admin.settings.liff-designer` | LINE画面のブロック編集（後述） |
| 分析 | 分析 | `admin.analytics` | 分析画面 |
| ギャラリー | カテゴリ・アイテムCRUD | `admin.gallery.categories` 等 | ギャラリーカテゴリ・画像管理 |

---

## 2. LIFFデザイナー（LINE画面デザイン）

**URL**: `/admin/{project_key}/settings/liff-designer`  
**権限**: `liff_designer.manage`

### 2.1 画面構成

| 機能 | 説明 | 仕様メモ |
|------|------|----------|
| 左パネル | 画面タブ一覧・テンプレート・保存した画面 | タブは「ホーム設定」で表示にしたもののみ表示 |
| 中央 | スマホプレビュー（タブごとのブロック一覧） | ドラッグ＆ドロップで並び替え・追加 |
| 右パネル | ブロック追加パレット・ブロック設定・画面設定・カスタムCSS | ブロック選択時に設定フォーム表示 |

### 2.2 画面タブ（編集対象）

| タブキー | 表示名 | 対応機能フラグ | 備考 |
|----------|--------|----------------|------|
| home | ホーム | feature_home_enabled | カスタムレイアウト可能 |
| products | 商品 | feature_orders_enabled | データブロック中心 |
| orders | 注文 | feature_orders_enabled | 注文履歴等 |
| stamps | スタンプ | feature_stamps_enabled | |
| coupons | クーポン | feature_coupons_enabled | |
| queue | 順番待ち | feature_queue_enabled | |
| reservations | 予約 | feature_reservations_enabled | |
| member | 会員 | feature_members_enabled | |
| gallery | ギャラリー | feature_gallery_enabled | |
| announcements | お知らせ | feature_announcements_enabled | |

- **編集可能タブ**: メニュー設定（`frontend_menu_items`）で「表示する」にしたタブのみ。他はデザイナーに表示されないか編集不可。

### 2.3 ブロックの種類（ブロックタイプ）

| カテゴリ | タイプ | 表示名 | 主なプロパティ・仕様 |
|----------|--------|--------|------------------------|
| layout | hero | ヒーロー | title, subtitle, image_url, gradient, button_label, button_url |
| basic | heading | 見出し | text, size, text_color |
| basic | text | テキスト | text, text_color, font_size |
| basic | image | 画像 | image_url, alt, fit |
| basic | button | ボタン | text, color, action_type, action_value, full_width |
| basic | button_group | ボタン群 | buttons[] (text, action_type, action_value) |
| layout | card | カード | title, description, image_url, image_height |
| layout | collection | コレクション | items[], layout(scroll/grid), columns, source(データ連携), auto_slide |
| layout | slider | スライダー | slides[], slide_height, auto_play, slide_animation |
| layout | icon_text | アイコン+文字 | rows[] (icon, text, color) |
| layout | list | リスト | list_items[] または items[], title |
| layout | banner | バナー | お知らせバナー |
| content | opening_hours | 営業時間 | title, lines[] (day, hours) |
| content | store_info | 店舗情報 | title, address, phone, hours_text |
| content | faq | よくある質問 | title, items[] (q, a) |
| content | social_links | SNSリンク | title, links[] (type, url, label) |
| content | badge | バッジ | text, number, color |
| content | contact_card | コンタクトカード | title, phone, email, address |
| basic | spacer | 余白 | スペース |
| basic | divider | 区切り線 | 区切り線 |
| data | data | データ | source(products/orders/stamps/coupons/announcements/queue/reservations/gallery/member_card) |
| basic | form | フォーム | title, form_fields[], form_button_label |
| basic | embed | 埋め込み | embed_type(map等), embed_value |

- 共通プロパティ（多くのブロック）: border_radius, padding, margin_bottom, text_align, background_color, opacity, shadow, visibility_condition(always/logged_in/logged_out)

### 2.4 ボタン・アクション種類（action_type）

| 値 | 説明 |
|----|------|
| internal_tab | 画面切り替え（アプリ内タブ） |
| member_card | 会員証を表示 |
| url | リンクを開く（同一画面） |
| url_external | リンクを開く（外部ブラウザ） |
| tel | 電話をかける |
| mailto | メールを送る |
| map | 地図を開く |
| copy | テキストをコピー |
| line_share | LINEでシェア |
| share_native | 共有メニュー |
| scan_qr | QRコードをスキャン |
| close_liff | LIFFを閉じる |
| login | ログインする |
| logout | ログアウトする |

### 2.5 テンプレートから始める

| 機能 | 説明 | 仕様 |
|------|------|------|
| テンプレート一覧 | プリセット画面の一覧 | サーバー側で固定リスト（ShopSetting::defaultLiffDesignerTemplates） |
| テンプレート適用 | 選択したテンプレートを「現在選択中のタブ」に適用 | 既存ブロックは上書き（または置換）。テンプレートは id, name, blocks[] を持つ |

**テンプレート例**: シンプルホーム、ストーリー、会員案内、注文フロー、お問い合わせ、【サンプル】カフェ用ホーム、レストラン用ホーム、店舗案内ページ、美容室・サロン用、注文フロー（シンプル）、ウェルカム画面（一式） など。

### 2.6 保存した画面（最大10件）

| 機能 | 説明 | 仕様 |
|------|------|------|
| 保存 | 現在のレイアウト＋タブ設定を名前付きで保存 | 名前必須・最大50文字。保存件数上限10件、超えたら古いものを削除。 |
| 一覧表示 | 保存済み画面のリスト | id, name, created_at 等を表示 |
| 読み込み | 保存した画面を現在の編集状態に反映 | 読み込むと現在の内容は上書き。確認ダイアログ推奨。 |

- 保存データ: `layouts`（タブ別ブロック配列）、`tab_settings`（タブ別背景色・背景画像等）。`shop_settings` の `liff_saved_screens`（JSON）に保存。

### 2.7 デザインの保存（LIFF全体）

| 機能 | 説明 | 仕様 |
|------|------|------|
| 保存するボタン | レイアウト・カスタムCSS・タブ設定をDBに保存 | POST `admin.settings.liff-designer.update`。layouts_json, custom_css, tab_settings_json を送信。 |
| 元に戻す / やり直し | 編集履歴の undo/redo | クライアント側のみ。最大50件履歴。 |
| プレビュー | 編集中の見た目を確認 | 同一画面内のスマホフレームで表示。 |
| 実際の画面を表示 | LIFFの実画面を別タブで開く | `/liff/{project_key}` を target="_blank" で開く。 |

### 2.8 画面設定（タブ単位）

| 項目 | 説明 |
|------|------|
| 背景色 | タブごとの背景色（例: #f5f5f5） |
| 背景画像 | タブごとの背景画像URL |

### 2.9 カスタムCSS

- 上級者向け。任意のCSSを追加できる。通常はブロック・画面設定で十分。

---

## 3. 設定まわり（基本・メニュー・ホーム・機能・ランク）

- **基本設定**: 営業時間、予約枠、定休日、休業日、事前予約日数、予約枠上限、順番待ち平均時間、機能フラグ（チェックボックス一覧）。
- **メニュー設定**: フロント（LIFF）に表示するタブのON/OFF。ここで「表示する」にしたタブがLIFFデザイナーで編集可能になる。
- **ホーム設定**: ホーム画面のタブ構成など。
- **機能設定**: 各機能（注文・予約・スタンプ・クーポン・会員・お知らせ・順番待ち・ギャラリー・決済等）の有効/無効。
- **ランク設定**: 会員ランクの設定。

---

## 4. お客様向け（LIFF）

- **LIFFメイン画面**: `/liff/{project_key}`。タブ＋ブロックで構成された画面。レイアウト・タブ設定は `liff_tab_layouts` / `liff_tab_settings` / `liff_custom_css` を参照。
- 表示タブ・ブロック内容は管理画面のLIFFデザイナーおよびメニュー設定・機能フラグに従う。

---

## 5. システム管理者向け

- ダッシュボード、プロジェクトCRUD、アカウントCRUD、APIトークン、監査ログ 等。`/system-admin/*`。

---

## 6. 追加された機能（主にLIFFデザイナー周り）

以下は、既存の「注文・商品・会員・設定」などに加えて**追加・拡張された機能**です。

| 追加機能 | 概要 | 備考 |
|----------|------|------|
| **LIFFデザイナー画面** | LINE画面をブロックで編集する管理画面 | タブ・ブロック・ドラッグ、プレビュー、保存 |
| **ブロック型レイアウト** | ヒーロー・テキスト・ボタン・コレクション・スライダー・営業時間・店舗情報・FAQ・SNS・バッジ・コンタクトカード・埋め込み等のブロック | 多数のブロックタイプと設定項目 |
| **テンプレートから始める** | プリセット（シンプルホーム、カフェ用、レストラン用、店舗案内、サロン用等）を選択して現在タブに適用 | サーバー側テンプレート一覧 |
| **保存した画面（最大10件）** | 現在のレイアウト＋タブ設定を名前付きで保存し、一覧から読み込み | 保存・一覧・読み込み。上限10件で古いものから削除 |
| **タブ別画面設定** | タブごとの背景色・背景画像 | tab_settings |
| **カスタムCSS** | 上級者向けの自由記述CSS | liff_custom_css |
| **編集可能タブの制限** | メニュー設定で「表示する」にしたタブのみデザイナーで編集可能 | editableTabKeys |
| **Undo/Redo** | 編集履歴の元に戻す・やり直し（クライアント側、最大50件） | デザイナー画面のみ |
| **コレクションのスワイプ・自動スライド** | コレクションブロックのレイアウト・自動スライド設定 | ブロック仕様の一部 |
| **スライダーアニメーション** | スライダーのアニメーション種類・速度 | ブロック仕様の一部 |
| **ボタンアクションの細分化** | internal_tab, member_card, url, tel, mailto, map, copy, line_share, close_liff, login, logout 等 | action_type / action_value |
| **表示条件（visibility_condition）** | 常に表示 / ログイン時のみ / 未ログイン時のみ | ブロック共通オプション |

上記のうち、「保存した画面（最大10件）」「テンプレートから始める」「タブ別画面設定」「編集可能タブの制限」および各種ブロックタイプ・アクション種類は、LIFFデザイナー拡張として追加された機能です。

---

## 7. 会員情報の拡張とテーブル構成

### 7.1 会員情報に追加・紐付けしている項目

| 項目 | 説明 | 格納先・取得方法 |
|------|------|------------------|
| **最終来店日** | 会員の最終来店日（日付） | `members.last_visited_at`（日付、NULL可）。来店時に更新する処理の実装で更新。 |
| **購入履歴の紐付け** | 会員に紐づく注文一覧 | 会員は `user_id` で `users` に紐づき、注文は `orders.user_id` で同じ `users` に紐づく。`$member->orders()` で取得可能（Member モデルの hasManyThrough）。 |
| **ランク情報** | 会員ランク（bronze / silver / gold 等） | `members.rank`（文字列、デフォルト `bronze`）。ランク設定は設定画面で管理し、ラベル等は `shop_settings` のランク設定を参照。 |

- 会員詳細・一覧で「最終来店日」「購入履歴（注文一覧）」「ランク」を表示する場合は、上記カラムとリレーションを利用する。

### 7.2 会員まわりのテーブルと要素（カラム）一覧

#### members（会員）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID（FK → projects.id） |
| user_id | bigint unsigned | ユーザーID（FK → users.id）。1ユーザー1会員（同一プロジェクト） |
| member_number | string | 会員番号（プロジェクト内でユニーク） |
| points | integer | 保有ポイント（表示用キャッシュ。正は point_transactions） |
| status | enum | active / inactive |
| rank | string(20) | 会員ランク（例: bronze, silver, gold）。デフォルト bronze |
| last_visited_at | date, nullable | **最終来店日**（追加項目） |
| birthday | date, nullable | 誕生日 |
| address | text, nullable | 住所 |
| created_at, updated_at | timestamp | 作成・更新日時 |
| deleted_at | timestamp, nullable | 論理削除日時（SoftDeletes） |

#### users（お客様ユーザー・会員の元になるアカウント）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| line_user_id | string | LINE ユーザーID（プロジェクト内でユニーク） |
| name | string, nullable | 氏名 |
| email | string, nullable | メールアドレス |
| phone | string, nullable | 電話番号 |
| profile_image_url | string, nullable | プロフィール画像URL |
| last_login_at | timestamp, nullable | 最終ログイン日時 |
| created_at, updated_at | timestamp | 作成・更新日時 |
| deleted_at | timestamp, nullable | 論理削除日時 |

- 会員（members）は users に 1:1 で紐づく。注文・予約は users 経由で紐づく。

#### orders（注文＝購入履歴の実体）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| user_id | bigint unsigned | ユーザーID（FK → users.id）。会員の購入履歴は member.user_id = order.user_id で紐づく |
| order_number | string | 注文番号（プロジェクト内ユニーク） |
| order_type | enum | 注文種別 |
| total_amount | decimal(10,2) | 合計金額 |
| status | enum | pending, confirmed, preparing, ready, completed, cancelled |
| payment_method | enum, nullable | cash, card, line_pay, points, stripe 等 |
| notes | text, nullable | 備考 |
| points_used | integer | 利用ポイント |
| points_earned | integer | 付与ポイント |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### order_items（注文明細）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| order_id | bigint unsigned | 注文ID（FK → orders.id） |
| product_id | bigint unsigned | 商品ID（FK → products.id） |
| quantity | integer | 数量 |
| price | decimal(10,2) | 単価 |
| notes | text, nullable | 備考 |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### point_transactions（ポイント履歴）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| member_id | bigint unsigned | 会員ID（FK → members.id） |
| type | enum | earned, used, expired, refunded, reversed 等 |
| points | integer | ポイント増減（正負） |
| description | text, nullable | 説明 |
| order_id | bigint unsigned, nullable | 紐づく注文ID（FK → orders.id） |
| expires_at | date, nullable | 有効期限 |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### member_stamps（会員のスタンプ進捗）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| member_id | bigint unsigned | 会員ID（FK → members.id） |
| stamp_card_id | bigint unsigned | スタンプカードID（FK → stamp_cards.id） |
| current_stamps | integer | 現在のスタンプ数 |
| completed_at | timestamp, nullable | 達成日時 |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### member_coupons（会員発行クーポン）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| member_id | bigint unsigned | 会員ID（FK → members.id） |
| coupon_id | bigint unsigned | クーポンID（FK → coupons.id） |
| coupon_name_snapshot | string, nullable | 発行時クーポン名スナップショット |
| status | string | unused / used 等 |
| issued_at | timestamp | 発行日時 |
| valid_from | timestamp, nullable | 有効開始 |
| valid_until | timestamp, nullable | 有効期限 |
| used_at | timestamp, nullable | 使用日時 |
| order_id | bigint unsigned, nullable | 使用した注文ID（FK → orders.id） |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### coupon_redemptions（クーポン利用履歴）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| coupon_id | bigint unsigned | クーポンID（FK → coupons.id） |
| member_id | bigint unsigned | 会員ID（FK → members.id） |
| member_coupon_id | bigint unsigned, nullable | 会員クーポンID（FK → member_coupons.id） |
| order_id | bigint unsigned, nullable | 注文ID（FK → orders.id） |
| used_at | timestamp | 利用日時 |
| created_at, updated_at | timestamp | 作成・更新日時 |

#### reservations（予約）

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint unsigned | 主キー |
| project_id | bigint unsigned | プロジェクトID |
| user_id | bigint unsigned | ユーザーID（FK → users.id）。会員は user 経由で紐づく |
| reservation_number | string | 予約番号（プロジェクト内ユニーク） |
| reserved_at | datetime | 予約日時 |
| number_of_people | integer | 人数 |
| status | enum | 予約ステータス |
| notes | text, nullable | 備考 |
| created_at, updated_at | timestamp | 作成・更新日時 |

- 会員の予約一覧は `$member->user->reservations()` で取得可能。

### 7.3 会員情報の取得例（コード）

- **最終来店日**: `$member->last_visited_at`
- **購入履歴**: `$member->orders()` または `$member->orders()->with('items')->get()`
- **ランク**: `$member->rank`。表示名はランク設定（shop_settings のランク設定）から取得。
- **ポイント履歴**: `$member->pointTransactions()`
- **スタンプ合計**: `$member->getTotalStamps()`（Member モデル）
- **予約一覧**: `$member->user->reservations()`

---

## 8. 細かい仕様を決める際の参照先

- **ルート一覧**: `docs/URL_LIST.md`
- **全体仕様・アーキテクチャ**: `docs/SPECIFICATION.md`
- **権限**: `docs/PERMISSIONS.md`
- **LIFFデザイナー（PHP）**: `app/UseCases/Admin/Settings/`（GetLiffDesignerUseCase, UpdateLiffDesignerUseCase, SaveLiffScreenUseCase）、`app/Models/ShopSetting.php`（liff_tab_layouts, liff_tab_settings, liff_saved_screens, defaultLiffDesignerTemplates）
- **LIFFデザイナー（JS）**: `public/js/admin/liff-designer.js`（ブロックタイプ・アクション・テンプレート利用・保存画面の読み込み）
- **フロントLIFF表示**: `public/js/frontend/managers/LiffLayoutManager.js`、`resources/views/index.blade.php`
- **会員・テーブル**: `app/Models/Member.php`（last_visited_at, orders()）、`database/migrations/`（members, users, orders, order_items, point_transactions, member_stamps, member_coupons, coupon_redemptions, reservations）
