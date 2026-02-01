# データベーススキーマ一覧（全テーブル・全カラム）

複数店舗（プロジェクト）対応のため、全テーブルに **project_id** を外部キーとして持たせています。  
**project_key** は URL・API などで店舗を一意に識別するための文字列、**project_id** は `projects.id` への外部キーです。

**細かなDB定義**
- 全カラムに **COMMENT** を付与（MySQL。マイグレーション `2026_01_31_400001_add_column_comments`）
- **projects** に論理削除（**deleted_at**）
- **shop_settings** に **type** カラムのインデックス（設定種別での検索用）

**責務の分離・個人情報・ポイント**
- **個人情報（PII）**: 氏名・メール・電話は **users**、誕生日・住所は **members** にのみ保持する。注文・予約・順番待ちなどは **user_id / member_id** で参照し、氏名・連絡先を他テーブルに重複保存しない。**queue_entries** の guest_name / guest_phone は会員外ゲスト用の最小限のみ。
- **ポイント**: **正は point_transactions のみ**。members.points は表示用キャッシュ。増減は必ず point_transactions 経由で行い、必要に応じて members.points を再計算（`recalcPointsFromTransactions()`）する。キャンセル時は refunded（返却）・reversed（獲得取り消し）で履歴を残す。
- **プロジェクト**: 全テーブルで **project_id** によりテナント分離。クエリは可能な限り project スコープで行う。

---

## データベース設計の考え方

設計時に「何を正とするか」「誰のデータか」「履歴を残すか」を決め、責務を分けると一貫しやすい。

### 1. 設計時に問うこと

| 問い | 意味 | 例 |
|------|------|-----|
| **何が正（ソースオブトゥルース）か** | その値の「本当の出どころ」を1つに決める。他はキャッシュや表示用。 | ポイントの正は point_transactions。members.points は合計のキャッシュ。 |
| **誰のデータか（スコープ）** | どの単位で分離するか。マルチテナントなら project_id で必ずスコープする。 | 全テーブルに project_id。order_number は (project_id, order_number) でユニーク。 |
| **履歴を残すか** | 削除後も参照されるなら論理削除。取引は「追加のみ」で履歴になる。 | users / members は deleted_at。ポイントは point_transactions に追加するだけ。 |
| **参照かスナップショットか** | 将来変わりうる値は ID 参照、当時の値を残すならスナップショット。 | 注文時の単価は order_items.price に保存。商品マスタは product_id で参照。 |

### 2. 責務の分離の置き方

- **テナント**: 全テーブルに project_id。クエリは `where('project_id', $projectId)` を基本にする。
- **個人情報（PII）**: 名前・連絡先は users / members にだけ持つ。他テーブルは user_id / member_id だけ持ち、JOIN で取得する。重複すると更新漏れ・不整合が出る。
- **取引・履歴**: 「正」を1テーブルに決め、増減はそこにだけ書く。残高などは合計で求めるか、必要ならキャッシュ用カラムを1つ持つ（再計算可能にしておく）。

### 3. テーブルを分ける基準

- **1テーブル1役割**: ユーザー、会員、注文、予約、ポイント履歴など、役割ごとにテーブルを分ける。混ぜるとクエリと制約が複雑になる。
- **多対多・中間データ**: 会員とスタンプカードの進捗 → member_stamps。クーポン利用履歴 → coupon_redemptions。履歴は「追加のみ」のテーブルにすると責務がはっきりする。

### 4. 論理削除（deleted_at）の使い分け

- **付ける**: 削除後も「誰の注文か」「誰の予約か」を参照したいもの。users, members, projects。
- **付けない**: 注文・予約・ポイント履歴はステータスや type で「キャンセル済み」など表現し、行は残す。物理削除は原則しない（監査・問い合わせ用）。

### 5. 型・インデックス・名前

- **型**: ID は bigint unsigned。金額は decimal。フラグは tinyint(1) や boolean。enum は値が固定なら DB の enum でも可。
- **インデックス**: 外部キー・一覧の絞り込み・日付範囲はインデックスを検討。ユニークは (project_id, ビジネスキー) で取る。
- **名前**: テーブル・カラムに COMMENT を付けておくと、あとで「何のデータか」が分かりやすい。

このプロジェクトでは、上記を「責務の分離・個人情報・ポイント」のルールとして冒頭に要約し、全テーブルで揃えている。

---

## 1. テーブル一覧（概要）

| テーブル名 | 用途 |
|-----------|------|
| **projects** | 店舗・プロジェクト（project_key / project_id の親） |
| users | 利用者（LINE/モバイルアプリ・プロジェクト別） |
| user_devices | 端末情報（モバイルアプリ用・1ユーザー複数端末可） |
| members | 会員（ポイント・会員証） |
| products | 商品マスタ |
| orders | 注文（モバイル・店内） |
| order_items | 注文明細 |
| reservations | 予約 |
| point_transactions | ポイント履歴 |
| shop_settings | 店舗設定（キー・値・プロジェクト別） |
| stamp_cards | スタンプカード定義 |
| member_stamps | 会員ごとのスタンプ進捗 |
| coupons | クーポン定義 |
| member_coupons | 会員に発行されたクーポン（1会員1枚につき1行） |
| coupon_redemptions | クーポン利用履歴 |
| announcements | お知らせ |
| queue_entries | 順番待ちエントリ |

---

## 1.1 責務分離チェック一覧（全テーブル）

以下のルールが全テーブルで満たされていることを確認する。

| テーブル | project_id | PII の扱い | ポイント正 | 論理削除 | 備考 |
|----------|:---------:|:----------:|:----------:|:--------:|------|
| **projects** | －（親） | なし | － | ○ deleted_at | 店舗マスタ。子はすべて project_id でスコープ。 |
| **users** | ○ | ○ 名前・メール・電話はここだけ | － | ○ deleted_at | 利用者。他テーブルは user_id 参照のみ。 |
| **user_devices** | ○ | なし | － | なし | 端末情報。user_id 参照。 |
| **members** | ○ | ○ 誕生日・住所はここだけ | キャッシュのみ | ○ deleted_at | 会員。ポイント正は point_transactions。 |
| **products** | ○ | なし（商品名はマスタ） | － | なし | 商品マスタ。 |
| **orders** | ○ | なし（user_id 参照） | － | なし | 注文。ステータスでキャンセル表現。 |
| **order_items** | ○ | なし | － | なし | 明細。単価はスナップショット。 |
| **reservations** | ○ | なし（user_id 参照） | － | なし | 予約。ステータスでキャンセル表現。 |
| **point_transactions** | ○ | なし | ○ **正** | なし | 取引ごとの増減のみ。残高は合計で算出。 |
| **shop_settings** | ○ | なし | － | なし | 店舗設定キー・値。 |
| **stamp_cards** | ○ | なし | － | なし | スタンプカード定義。 |
| **member_stamps** | ○ | なし | － | なし | 会員×カードの進捗。 |
| **coupons** | ○ | なし | － | なし | クーポン定義。 |
| **member_coupons** | ○ | なし | － | なし | 会員に発行されたクーポン（1行=1会員が1枚）。発行時にスナップショット保存可。 |
| **coupon_redemptions** | ○ | なし | － | なし | クーポン利用履歴。member_coupon_id で発行クーポンと紐づけ可。 |
| **announcements** | ○ | なし | － | なし | お知らせ。 |
| **queue_entries** | ○ | △ guest_name/phone は会員外のみ最小限 | － | なし | 順番待ち。会員は member_id 参照。 |

- **PII**: 氏名・メール・電話・住所・誕生日は users / members にのみ保持。他テーブルは ID 参照のみ。
- **ポイント正**: 増減の記録は point_transactions のみ。members.points は表示用キャッシュで再計算可能。

---

## 1.2 スマレジAPI対応（POS 仕様）

[スマレジ・プラットフォームAPI POS](https://developers.smaregi.dev/platform-api-reference/apis/pos/) 連携を想定した**概念マッピング**。外部IDはテーブルには持たず、契約IDは .env や shop_settings で管理する想定。

**エンドポイント**: サンドボックス `https://api.smaregi.dev/{contract_id}/pos`、本番 `https://api.smaregi.jp/{contract_id}/pos`。  
**契約ID（contract_id）** は API のスコープ単位。1 project = 1 契約として対応する場合は、契約IDを .env や shop_settings の key で持つ。

| スマレジ POS リソース | 当システム | 備考 |
|----------------------|------------|------|
| 契約・店舗 | projects | 契約IDは DB には持たず .env / shop_settings で運用可。 |
| 商品 | products | 商品一覧取得・登録・更新。 |
| 会員 | members | 会員一覧取得・登録。会員ポイントは point_transactions を正として同期。 |
| 取引 | orders | 取引登録・取得。取引取消・打消取消は当システムのキャンセル処理と連携可能。 |
| クーポン | coupons | クーポン一覧取得・登録。 |

**認証**: AppAccessToken（client_credentials）または UserAccessToken（authorization_code）。  
スコープ例: `pos.products:read/write`, `pos.customers:read/write`, `pos.transactions:read/write`, `pos.stores:read/write` 等。  
クライアントID・シークレット・契約IDは .env 等で管理する。

---

## 2. 全テーブル・全カラム一覧

### 2.1 projects（店舗・プロジェクト）

論理削除（softDeletes）と全カラムへの COMMENT 付与を行っています。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK（これが project_id として他テーブルから参照される） |
| project_key | varchar(64) | NO | - | **UNIQUE** URL・API識別用（英数字など） |
| name | varchar(255) | NO | - | 店舗・プロジェクト名 |
| description | varchar(255) | YES | - | 説明 |
| is_active | tinyint(1) | NO | 1 | 有効/無効 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |
| deleted_at | timestamp | YES | - | 論理削除日時（NULL=有効） |

---

### 2.2 users（ユーザー）

LINE・モバイルアプリ問わず「利用者」を表す。1プロジェクト1行（同一LINEユーザーが別店舗では別行）。論理削除・最終ログイン日時あり。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| line_user_id | varchar(255) | NO | - | **(project_id, line_user_id) で UNIQUE**（LINE のみ運用時は必須） |
| name | varchar(255) | YES | - | 表示名（LINE/アプリから取得したスナップショット） |
| email | varchar(255) | YES | - | メール（モバイルアプリで会員紐付け等に利用） |
| phone | varchar(255) | YES | - | 電話番号 |
| profile_image_url | varchar(255) | YES | - | プロフィール画像URL |
| email_verified_at | timestamp | YES | - | メール認証日時 |
| remember_token | varchar(100) | YES | - | 認証用 |
| last_login_at | timestamp | YES | - | 最終ログイン日時（LINE/アプリ問わず更新推奨） |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |
| deleted_at | timestamp | YES | - | 論理削除日時（NULL=有効） |

---

### 2.3 user_devices（端末情報）

モバイルアプリ対応用。1ユーザーが複数端末を持てる。プッシュ通知・アプリ版管理に利用。LINE のみ運用時は未使用でよい。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| user_id | bigint unsigned | NO | - | **FK → users.id** CASCADE |
| platform | varchar(20) | NO | - | ios / android 等 |
| device_identifier | varchar(255) | YES | - | 端末一意ID（アプリ側で生成） |
| push_token | varchar(500) | YES | - | プッシュ通知用トークン |
| os_version | varchar(50) | YES | - | OSバージョン |
| app_version | varchar(50) | YES | - | アプリバージョン |
| last_used_at | timestamp | YES | - | 最終利用日時 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.4 members（会員）

「会員証」を表す。1 User : 1 Member（会員登録済みユーザーに1行）。ポイント・スタンプ・クーポン利用・注文履歴は member_id で参照。退会後も履歴を残すため論理削除（deleted_at）を使用。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| user_id | bigint unsigned | NO | - | FK → users.id CASCADE |
| member_number | varchar(255) | NO | - | **(project_id, member_number) で UNIQUE** 会員番号（10桁数字等） |
| points | int | NO | 0 | 保有ポイント（表示用キャッシュ。正は point_transactions の合計） |
| status | enum('active','inactive') | NO | 'active' | 会員状態 |
| rank | varchar(20) | NO | 'bronze' | ランク（bronze/silver/gold/platinum。ポイント閾値で更新） |
| birthday | date | YES | - | 誕生日 |
| address | text | YES | - | 住所 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |
| deleted_at | timestamp | YES | - | 論理削除日時（NULL=有効。退会時セット） |

---

### 2.5 products（商品）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| name | varchar(255) | NO | - | 商品名 |
| description | text | YES | - | 説明 |
| price | decimal(10,2) | NO | - | 単価 |
| image_url | varchar(255) | YES | - | 画像URL |
| category | enum('food','drink','dessert','side','other') | NO | 'other' | カテゴリ |
| is_available | tinyint(1) | NO | 1 | 販売可否 |
| stock | int | NO | 0 | 在庫数 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.6 orders（注文）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| user_id | bigint unsigned | NO | - | FK → users.id CASCADE |
| order_number | varchar(255) | NO | - | **(project_id, order_number) で UNIQUE** |
| order_type | varchar(20) | NO | 'mobile' | mobile / in_store |
| total_amount | decimal(10,2) | NO | - | 合計金額 |
| status | enum | NO | 'pending' | pending, confirmed, preparing, ready, completed, cancelled |
| payment_method | enum | YES | - | cash, card, line_pay, points, stripe |
| notes | text | YES | - | 備考 |
| points_used | int | NO | 0 | 利用ポイント |
| points_earned | int | NO | 0 | 付与ポイント |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.7 order_items（注文明細）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| order_id | bigint unsigned | NO | - | FK → orders.id CASCADE |
| product_id | bigint unsigned | NO | - | FK → products.id CASCADE |
| quantity | int | NO | - | 数量 |
| price | decimal(10,2) | NO | - | 単価（スナップショット） |
| notes | text | YES | - | 備考 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.8 reservations（予約）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| user_id | bigint unsigned | NO | - | FK → users.id CASCADE |
| reservation_number | varchar(255) | NO | - | **(project_id, reservation_number) で UNIQUE** |
| reserved_at | datetime | NO | - | 予約日時 |
| number_of_people | int | NO | 1 | 人数 |
| status | enum('pending','confirmed','cancelled','completed') | NO | 'pending' | 予約状態 |
| notes | text | YES | - | 備考 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.9 point_transactions（ポイント履歴）

取引ごとのポイント増減のみを記録する。保有残高はこの合計で算出し、members.points はキャッシュ。キャンセル時は refunded（使用分返却）・reversed（獲得分取り消し）で履歴を残す。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| member_id | bigint unsigned | NO | - | FK → members.id CASCADE |
| type | enum('earned','used','expired','refunded','reversed') | NO | 'earned' | earned=獲得, used=使用, expired=失効, refunded=返却, reversed=取り消し（キャンセル等） |
| points | int | NO | - | ポイント数（正負。reversed は負で記録） |
| description | text | YES | - | 説明 |
| order_id | bigint unsigned | YES | - | FK → orders.id SET NULL |
| expires_at | date | YES | - | 有効期限 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.10 shop_settings（店舗設定）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| key | varchar(255) | NO | - | **(project_id, key) で UNIQUE** 設定キー |
| value | text | YES | - | 値 |
| type | varchar(255) | NO | 'string' | string / integer / boolean / json |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

**主な key**: business_hours_start, business_hours_end, reservation_time_slots, closed_days, closed_dates, advance_booking_days, reservation_capacity_per_slot, line_primary_color, line_primary_dark, line_success_color, line_danger_color

---

### 2.11 stamp_cards（スタンプカード定義）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| name | varchar(255) | NO | - | カード名 |
| required_stamps | int | NO | 10 | 達成に必要なスタンプ数 |
| reward_description | varchar(255) | YES | - | 特典説明 |
| is_active | tinyint(1) | NO | 1 | 有効/無効 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.12 member_stamps（会員スタンプ進捗）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| member_id | bigint unsigned | NO | - | FK → members.id CASCADE |
| stamp_card_id | bigint unsigned | NO | - | FK → stamp_cards.id CASCADE |
| current_stamps | int | NO | 0 | 現在のスタンプ数 |
| completed_at | timestamp | YES | - | 達成日時 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |
| - | - | - | - | **(member_id, stamp_card_id) UNIQUE** |

---

### 2.13 coupons（クーポン）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| code | varchar(50) | NO | - | **(project_id, code) で UNIQUE** クーポンコード |
| name | varchar(255) | YES | - | 表示名 |
| type | varchar(20) | NO | 'percent' | percent / fixed 等 |
| value | decimal(10,2) | NO | 0 | 割引率(%) または 固定額 |
| min_order_amount | decimal(10,2) | YES | - | 最低注文金額 |
| usage_limit | int | YES | - | 総利用回数上限（NULL=無制限） |
| used_count | int | NO | 0 | 利用回数 |
| start_at | timestamp | YES | - | 利用開始日時 |
| end_at | timestamp | YES | - | 利用終了日時 |
| is_active | tinyint(1) | NO | 1 | 有効/無効 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.14 member_coupons（会員に発行されたクーポン）

1会員1枚につき1行。発行時に1行追加、利用時に status=used, used_at, order_id を更新。

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| member_id | bigint unsigned | NO | - | FK → members.id CASCADE |
| coupon_id | bigint unsigned | NO | - | FK → coupons.id CASCADE |
| coupon_name_snapshot | varchar(255) | YES | - | 発行時のクーポン名（表示用スナップショット） |
| status | enum('unused','used') | NO | 'unused' | 未使用 / 使用済み |
| issued_at | timestamp | NO | - | 発行日時 |
| valid_from | timestamp | YES | - | 有効開始 |
| valid_until | timestamp | YES | - | 有効期限 |
| used_at | timestamp | YES | - | 利用日時 |
| order_id | bigint unsigned | YES | - | FK → orders.id SET NULL |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.15 coupon_redemptions（クーポン利用履歴）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| coupon_id | bigint unsigned | NO | - | FK → coupons.id CASCADE |
| member_id | bigint unsigned | NO | - | FK → members.id CASCADE |
| member_coupon_id | bigint unsigned | YES | - | FK → member_coupons.id SET NULL |
| order_id | bigint unsigned | YES | - | FK → orders.id SET NULL |
| used_at | timestamp | NO | - | 利用日時 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.16 announcements（お知らせ）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| title | varchar(255) | NO | - | タイトル |
| body | text | YES | - | 本文 |
| published_at | timestamp | YES | - | 公開日時 |
| expires_at | timestamp | YES | - | 掲載終了日時 |
| is_pinned | tinyint(1) | NO | 0 | 固定表示 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

### 2.17 queue_entries（順番待ち）

| カラム名 | 型 | NULL | デフォルト | 備考 |
|----------|-----|------|------------|------|
| id | bigint unsigned | NO | AUTO | PK |
| **project_id** | **bigint unsigned** | **NO** | - | **FK → projects.id** CASCADE |
| member_id | bigint unsigned | YES | - | FK → members.id SET NULL |
| guest_name | varchar(255) | YES | - | ゲスト名 |
| guest_phone | varchar(20) | YES | - | 電話番号 |
| party_size | int | NO | 1 | 人数 |
| status | varchar(20) | NO | 'waiting' | waiting / called / entered / cancelled 等 |
| queue_number | int | YES | - | 待ち番号 |
| called_at | timestamp | YES | - | 呼出日時 |
| entered_at | timestamp | YES | - | 入店日時 |
| created_at | timestamp | YES | - | 作成日時 |
| updated_at | timestamp | YES | - | 更新日時 |

---

## 3. project_key と project_id の使い分け

| 項目 | 説明 |
|------|------|
| **project_id** | `projects.id`（bigint）。全テーブルの外部キーとして使用。リレーション・JOIN は必ず project_id で行う。 |
| **project_key** | 文字列（例: `default`, `shop_tokyo`）。URL・API・LIFF の識別子として使用。人間が読める・変更しにくいキーとして運用。 |

新規レコード作成時は、現在の店舗に応じて **project_id** を必ずセットしてください。  
（管理画面・API で「現在のプロジェクト」をセッションやリクエストで持つ想定です。）

---

## 4. マイグレーション

- `2026_01_31_300001_create_projects_table.php` … projects テーブル作成とデフォルト店舗（project_key=default, id=1）の投入
- `2026_01_31_300002_add_project_id_to_all_tables.php` … 上記以外の全テーブルに project_id 追加・既存データを project_id=1 に紐づけ・UNIQUE を (project_id, ...) に変更
- `2026_01_31_500001_drop_chat_tables.php` … チャット機能を使わないため、chat_messages / chat_rooms を DROP（既存DBでテーブルがある場合のみ）

---

*最終更新: スマレジAPI対応は概念マッピングのみ（テーブルに外部IDは持たない）*
