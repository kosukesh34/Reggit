# スマレジ・プラットフォームAPI POS リファレンス

このドキュメントを読めば、**スマレジPOS API の呼び出し方・型・エラー・本アプリでの実装**を一通り理解できます。外部の仕様書を開かずに実装・調査ができるようにしています。

**公式仕様書（参照用）**: [スマレジ・プラットフォームAPI POS](https://developers.smaregi.dev/platform-api-reference/apis/pos/)

---

## 目次

1. [このドキュメントの読み方](#このドキュメントの読み方)
2. [データ型の定義](#データ型の定義)
3. [概要（エンドポイント・認証・スコープ）](#概要エンドポイント認証スコープ)
4. [クーポン API（完全仕様）](#クーポンcoupons)
5. [その他リソース（店舗・会員・取引など）](#その他リソース)
6. [本アプリからの呼び出し方](#本アプリからの呼び出し方)
7. [エラーとトラブルシュート](#エラーとトラブルシュート)

---

## このドキュメントの読み方

- **「型」**: すべて [データ型の定義](#データ型の定義) の表記に統一しています。`string (format: int)` は「JSON では string だが、意味は整数」です。
- **「必須」**: リクエストで省略できない項目です。
- **`{contract_id}`**: URL や path に含まれるプレースホルダ。実際には契約ID（例: `sb_skx928r2`）に置き換えます。
- **本アプリ**: このリポジトリ（Reggit）を指します。実装ファイルパスやコード例はこのリポジトリ基準です。

---

## データ型の定義

API は **JSON** でやり取りします。仕様で「string format: int」などと書かれる項目は、JSON 上は **string** で送受信します。

| 型表記 | JSON の型 | 説明・例 |
|--------|-----------|----------|
| **string** | `string` | 通常の文字列。最大長がある場合は「≤N文字」と記載。 |
| **string (format: int)** | `string` | 数値を表す文字列。例: `"123"`, `"0"`。**送信時も数値ではなく文字列で送る**。 |
| **string (format: date)** | `string` | 日付。形式は `YYYY-MM-DD`。例: `"2026-02-28"`。 |
| **string (format: date-time)** | `string` | 日時。形式は `YYYY-MM-DDThh:mm:ssTZD`。例: `"2026-02-28T12:00:00+09:00"`。 |
| **integer** | `number` | 整数。クエリパラメータ（`limit`, `page`）では数値のまま指定。 |
| **array** | `array` | 配列。要素の型は文脈で記載。 |
| **object** | `object` | オブジェクト。キーと値の組み合わせ。 |

**重要**: レスポンスの ID・金額・日付は、多くの場合 **string** で返ります。リクエストボディで「string format: int」とある項目は、**必ず文字列で送信**してください（例: `"awardValue": "10"`）。

**エラー時（4xx/5xx）のレスポンス** は次の形の `object` です。

```json
{
  "type": "about:blank",
  "title": "Bad Request",
  "detail": "エラー内容の説明文",
  "status": 400
}
```

- `type`, `title`: string  
- `detail`: string（省略時あり）  
- `status`: integer（HTTP ステータスコード）

---

## 概要（エンドポイント・認証・スコープ）

### エンドポイント

| 環境 | Base URL |
|------|----------|
| サンドボックス | `https://api.smaregi.dev/{contract_id}/pos` |
| 本番 | `https://api.smaregi.jp/{contract_id}/pos` |

例: クーポン一覧取得は `GET https://api.smaregi.dev/sb_skx928r2/pos/coupons?limit=10&page=1` のように、Base URL の直後に path（`pos/coupons`）とクエリを付けます。

**契約ID（contract_id）** は、スマレジ連携でプロジェクトに紐づけた契約のIDです。本アプリでは管理画面「設定 → スマレジ連携」で紐付け、`SmaregiApiService::getContractIdForProject($projectId)` で取得します。

### 認証（AppAccessToken）

1. **トークン取得**: `POST https://id.smaregi.dev/app/{contract_id}/token`  
   - Content-Type: `application/x-www-form-urlencoded`  
   - Body: `grant_type=client_credentials&scope=pos.transactions:read&scope=pos.transactions:write&...`（必要なスコープを指定）  
   - Authorization: `Basic` + Base64(クライアントID:クライアントシークレット)

2. **レスポンス**: `{ "access_token": "xxx", "expires_in": 3600 }` の形式。`access_token` を次のリクエストで使う。

3. **API 呼び出し時**: ヘッダーに `Authorization: Bearer {access_token}` を付与。本アプリでは `SmaregiApiService` がトークン取得・キャッシュ・付与まで行います。

**スコープ** は `.env` の `SMAREGI_SCOPE` にカンマ区切りで指定します。クーポンには `pos.transactions:read` と `pos.transactions:write` が必要です。

### ページネーション（一覧取得共通）

| パラメータ | 型 | 範囲 | 初期値 |
|------------|-----|------|--------|
| `limit` | integer | 1 〜 1000 | 100 |
| `page` | integer | 1 以上 | 1 |
| `sort` | string | パラメータ名。降順は `パラメータ名:desc`。カンマ区切りで複数指定可 | 各APIの主キー:asc |

一覧取得の **レスポンス** は API により次のいずれかです。

- **配列**: `[{ ... }, { ... }]`
- **オブジェクト**: `{ "total": 100, "coupons": [ ... ] }` のように `total`（integer）とリスト用キー（array）を持つ

### 対象プラン

利用可能なプランは API ごとに異なります。**クーポン登録** は プレミアム / プレミアムプラス / フードビジネス / リテールビジネス で利用可能です。スタンダードでは利用できない場合があります。

### 主なスコープ一覧

| スコープ | 内容 |
|----------|------|
| pos.stores:read / write | 店舗 |
| pos.products:read / write | 商品・部門 |
| pos.customers:read / write | 会員・ポイント |
| pos.stock:read / write | 在庫 |
| pos.transactions:read / write | 取引・予算・精算・日次締め・**クーポン** |
| pos.staffs:read / write | スタッフ |
| pos.suppliers:read / write | 仕入先 |

---

## クーポン（coupons）

### エンドポイント一覧

| 操作 | Method | path | 必要スコープ |
|------|--------|------|--------------|
| 一覧取得 | GET | `pos/coupons` | pos.transactions:read |
| 1件取得 | GET | `pos/coupons/{coupon_id}` | pos.transactions:read |
| 登録 | POST | `pos/coupons` | pos.transactions:write |
| 更新 | PATCH | `pos/coupons/{coupon_id}` | pos.transactions:write |
| 削除 | DELETE | `pos/coupons/{coupon_id}` | pos.transactions:write |

`{coupon_id}` は登録または一覧取得で返る `couponId`（string）です。

---

### GET 一覧取得

**リクエスト**: `GET {Base URL}/pos/coupons?limit=10&page=1` など。クエリは任意。  
**レスポンス**: 配列、または `{ "total": number, "coupons": array }` の形。各要素は下記「レスポンス項目」の object。

#### クエリパラメータ（すべて任意）

| パラメータ | 型 | 説明 |
|------------|-----|------|
| `fields` | string | 取得項目をカンマ区切りで指定。省略時は全項目。 |
| `sort` | string | 並び順。例: `couponId`, `updDateTime:desc` |
| `limit` | integer | 1〜1000。省略時は 100。 |
| `page` | integer | 1 以上。省略時は 1。 |
| `serial_number` | string | シリアル番号で絞り込み。 |
| `award_type` | string | 特典タイプ。`1`, `2`, `3`, `P1`〜`P4`, `M1`〜`M4` |
| `apply_condition_division` | string | `0` または `1` |
| `start_date` | string (format: date) | YYYY-MM-DD |
| `end_date` | string (format: date) | YYYY-MM-DD |
| `applicable-from`, `applicable-to` | string (format: date) | 利用可能期間で検索。YYYY-MM-DD |
| `coupon_division` | string | `0` または `1` |

---

### GET 1件取得

**リクエスト**: `GET {Base URL}/pos/coupons/{coupon_id}`  
**レスポンス**: 単一の object。下記「レスポンス項目」と同じ形。

---

### レスポンス項目（一覧・1件・登録成功時共通）

いずれも **JSON では string 型** で返ります。未設定項目は null または省略されることがあります。

| 項目 | 型 | 説明 |
|------|-----|------|
| couponId | string | クーポンID。更新・削除で使用。 |
| couponName | string | クーポン名（最大85文字） |
| couponDivision | string | `"0"`=通常, `"1"`=giftee |
| serialNumber | string | シリアル番号（最大20文字）。端末入力用。 |
| summary | string | 概要（最大255文字） |
| awardType | string | 特典タイプ（下記表参照） |
| awardValue | string (format: int) | 特典値。数値を表す文字列。 |
| applyConditionDivision | string | `"0"`=全適用, `"1"`=対象商品含む場合のみ |
| minTargetPrice | string (format: int) | 最低対象金額 |
| maxTargetPrice | string (format: int) | 最高対象金額 |
| startDate | string (format: date) | 利用可能開始日。YYYY-MM-DD |
| endDate | string (format: date) | 利用可能終了日。YYYY-MM-DD |
| combineDivision | string | `"1"`=併用不可, `"2"`=一部併用可, `"3"`=併用可 |
| insDateTime | string (format: date-time) | 作成日時 |
| updDateTime | string (format: date-time) | 更新日時 |

---

### POST 登録（クーポン新規作成）

**リクエスト**: `POST {Base URL}/pos/coupons`  
**Content-Type**: `application/json`  
**Body**: 下記の object。数値・日付も **文字列** で送ること。

#### リクエストボディ項目

| 項目 | 必須 | 型 | 制約 | 説明 |
|------|------|-----|------|------|
| couponName | ○ | string | ≤85文字 | クーポン名 |
| serialNumber | ○ | string | ≤20文字。giftee 時は7桁 | シリアル番号（端末入力用） |
| awardType | ○ | string | 下記表の列挙値のみ | 特典タイプ |
| awardValue | ○ | string (format: int) | awardType に応じた範囲（下記表） | 値引額・割引率など。例: `"10"` |
| applyConditionDivision | ○ | string | `"0"` または `"1"` | 0=全適用, 1=対象商品含む場合のみ |
| couponDivision | - | string | `"0"` または `"1"` | 0=通常, 1=giftee（プレミアムプラス以上） |
| summary | - | string | ≤255文字 | 概要 |
| minTargetPrice | - | string (format: int) | ≤9999999999 | 最低対象金額 |
| maxTargetPrice | - | string (format: int) | ≤9999999999 | 最高対象金額 |
| startDate | - | string (format: date) | YYYY-MM-DD | 利用可能開始日 |
| endDate | - | string (format: date) | YYYY-MM-DD | 利用可能終了日 |
| combineDivision | - | string | `"1"`, `"2"`, `"3"` | 省略時は API 側で 3。1=併用不可, 2=一部併用可, 3=併用可 |

#### awardType の一覧と awardValue の意味・範囲

| awardType | 意味 | awardValue の内容 | 範囲 |
|-----------|------|-------------------|------|
| `"1"` | 値引 | 値引き額（円） | -9999999999 〜 9999999999 |
| `"2"` | 割引 | 割引率（%） | 0 〜 100 |
| `"3"` | 商品付与 | 0 固定 | 0 のみ |
| `"P1"` | 0ポイント | 0 固定 | 0 のみ |
| `"P2"` | ポイント指定（上書き） | ポイント数 | 0 〜 999999999 |
| `"P3"` | ポイント追加（加算） | 差分ポイント数 | 0 〜 999999999 |
| `"P4"` | ポイントN倍 | 乗算する値 | 1 〜 999999999 |
| `"M1"` | 0マイル | 0 固定 | 0 のみ |
| `"M2"` | マイル指定（上書き） | マイル数 | 0 〜 999999999 |
| `"M3"` | マイル追加（加算） | 差分マイル数 | 0 〜 999999999 |
| `"M4"` | マイルN倍 | 乗算する値 | 1 〜 999999999 |

#### POST 登録 リクエスト例（JSON）

```json
{
  "couponName": "10%オフクーポン",
  "serialNumber": "WELCOME10",
  "awardType": "2",
  "awardValue": "10",
  "applyConditionDivision": "0",
  "couponDivision": "0",
  "combineDivision": "3",
  "startDate": "2026-02-28",
  "endDate": "2026-12-31"
}
```

#### POST 登録 レスポンス（200 成功時）

- **型**: object（単一クーポン）
- **内容**: 上記「レスポンス項目」と同様のキーを持つ object。`couponId` を保存し、更新・削除時に使用する。

**レスポンス例（抜粋）**:

```json
{
  "couponId": "12345678-1234-1234-1234-123456789012",
  "couponName": "10%オフクーポン",
  "serialNumber": "WELCOME10",
  "awardType": "2",
  "awardValue": "10",
  "applyConditionDivision": "0",
  "startDate": "2026-02-28",
  "endDate": "2026-12-31",
  "insDateTime": "2026-02-28T10:00:00+09:00",
  "updDateTime": "2026-02-28T10:00:00+09:00"
}
```

---

### PATCH 更新

**リクエスト**: `PATCH {Base URL}/pos/coupons/{coupon_id}`  
**Content-Type**: `application/json`  
**Body**: 変更したい項目だけを object で送る。型・制約は POST と同じ。  
**レスポンス**: 200 時は更新後のクーポン object。

---

### DELETE 削除

**リクエスト**: `DELETE {Base URL}/pos/coupons/{coupon_id}`  
**レスポンス**: 204 No Content など。body は空のことが多い。

---

### クーポン API で 400 になりうる条件

- シリアル番号が既に登録されている（重複）
- couponDivision が `"1"`（giftee）なのに serialNumber が7文字でない
- minTargetPrice > maxTargetPrice
- endDate が startDate より前
- awardType に対する awardValue が範囲外（例: awardType=2 で 0 未満または 100 超）
- その他、仕様で定められた項目の制約違反

---

## その他リソース

以下は path と操作の一覧です。各リソースの詳細な Request/Response 項目は公式仕様書の該当ページを参照してください。

### 店舗（stores）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/stores` |
| 1件取得 | GET | `pos/stores/{store_id}` |
| 登録 | POST | `pos/stores` |
| 更新 | PATCH | `pos/stores/{store_id}` |
| 削除 | DELETE | `pos/stores/{store_id}` |

### 部門（categories）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/categories` |
| 登録 | POST | `pos/categories` |
| 取得 | GET | `pos/categories/{category_id}` |
| 更新 | PATCH | `pos/categories/{category_id}` |
| 削除 | DELETE | `pos/categories/{category_id}` |

### 部門グループ（category_groups）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/category_groups` |
| 登録 | POST | `pos/category_groups` |
| 取得 | GET | `pos/category_groups/{id}` |
| 更新 | PATCH | `pos/category_groups/{id}` |
| 削除 | DELETE | `pos/category_groups/{id}` |

### 商品（products）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/products` |
| 登録 | POST | `pos/products` |
| 一括登録 | POST | `pos/products/bulk` |
| 一括更新 | PATCH | `pos/products/bulk` |
| 取得 | GET | `pos/products/{product_id}` |
| 更新 | PATCH | `pos/products/{product_id}` |
| 削除 | DELETE | `pos/products/{product_id}` |
| 商品価格一覧 | GET | `pos/products/{product_id}/prices` |
| 店舗商品価格一覧 | GET | `pos/products/{product_id}/store_prices` |

### 会員（customers）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/customers` |
| 登録 | POST | `pos/customers` |
| 一括登録 | POST | `pos/customers/bulk` |
| 一括更新 | PATCH | `pos/customers/bulk` |
| 取得 | GET | `pos/customers/{customer_id}` |
| 更新 | PATCH | `pos/customers/{customer_id}` |
| 削除 | DELETE | `pos/customers/{customer_id}` |
| 会員ポイント一覧 | GET | `pos/customers/{customer_id}/points` |
| 会員ポイント更新 | PATCH | `pos/customers/{customer_id}/points` |
| 会員ポイント相対値更新 | POST | `pos/customers/{customer_id}/point/add` |

### 取引（transactions）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/transactions` |
| 登録 | POST | `pos/transactions` |
| 取得 | GET | `pos/transactions/{transaction_id}` |
| 更新 | PATCH | `pos/transactions/{transaction_id}` |
| 取消 | POST | `pos/transactions/{transaction_id}/cancel` |
| 返品取消 | POST | `pos/transactions/{transaction_id}/return_cancel` |
| 取引明細取得 | GET | `pos/transactions/{transaction_id}/details` |

**一覧取得の注意**: 次のいずれかのペアが **必須** です。`transaction_date_time-from`/`to`, `terminal_tran_date_time-from`/`to`, `sum_date-from`/`to`, `upd_date_time-from`/`to`。日付は string (format: date) または date-time 形式。

### 在庫（stocks）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/stock` |
| 更新 | PATCH | `pos/stock/{product_id}` |
| 相対値更新 | POST | `pos/stock/{product_id}/add` |
| 一括相対値更新 | POST | `pos/stock/add` |

### 会員ランク（customer_ranks）

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/customer_ranks` |
| 登録 | POST | `pos/customer_ranks` |
| 取得 | GET | `pos/customer_ranks/{rank}` |
| 更新 | PATCH | `pos/customer_ranks/{rank}` |
| 削除 | DELETE | `pos/customer_ranks/{rank}` |

### 支払方法（payment_methods）

本アプリの `SmaregiApiService::getPaymentMethods()` では **`pos/payment-methods`**（ハイフン）を使用しています。公式仕様の path が異なる場合は、実際のレスポンスに合わせて path を確認してください。

| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/payment-methods` |
| 登録 | POST | `pos/payment-methods` |
| 取得 | GET | `pos/payment-methods/{id}` |
| 更新 | PATCH | `pos/payment-methods/{id}` |
| 削除 | DELETE | `pos/payment-methods/{id}` |

### その他（パスのみ）

- セール: `pos/sales`
- 取置き: `pos/layaways`
- 仮販売: `pos/pending_transactions`
- 予算: `pos/budgets/monthly`, `pos/budgets/daily`
- レジ端末: `pos/terminals`
- スタッフ: `pos/staffs`（一覧・取得のみ。登録は別フロー）
- 精算: `pos/settlements`
- 日次締め: `pos/daily_summaries`
- 個別アプリ専用支払方法: `pos/app_payment_methods`
- 役割・役職: `pos/roles`
- 仕入先: `pos/suppliers`

---

## 本アプリからの呼び出し方

### 共通の流れ

1. `SmaregiApiService` を取得し、プロジェクトに紐づく契約IDを取得する。
2. GET の場合は `request()` または `getCoupons()` などに path とクエリを渡す。POST/PATCH の場合はボディ（array）を渡す。
3. 戻り値が `null` の場合はトークン取得失敗または API エラー。成功時は `array`（JSON をデコードしたもの）が返る。

### コード例

```php
use App\Services\Smaregi\SmaregiApiService;

$service = app(SmaregiApiService::class);
$contractId = $service->getContractIdForProject($projectId);
if ($contractId === null || $contractId === '') {
    // 契約未紐付け
    return;
}

// クーポン一覧取得（GET）
$data = $service->getCoupons($contractId, ['limit' => 10, 'page' => 1]);
if ($data === null) {
    // スコープ不足 or API エラー。storage/logs/laravel.log の [Smaregi] を確認
    return;
}

// クーポン登録（POST）
$body = [
    'couponName' => '10%オフ',
    'serialNumber' => 'WELCOME10',
    'awardType' => '2',
    'awardValue' => '10',
    'applyConditionDivision' => '0',
    'couponDivision' => '0',
    'combineDivision' => '3',
];
$result = $service->createCoupon($contractId, $body);
if ($result === null) {
    // 登録失敗（400 など）。ログに request_body_sent が出力される
    return;
}
$couponId = $result['couponId'] ?? $result['coupon_id'] ?? null;
```

### クーポン登録・更新の実装箇所

- **ボディ組み立て**: `app/Services/Smaregi/SmaregiCouponSyncService.php` の `buildBodyFromArray()`。  
  フォームの `code`, `name`, `type`, `value`, `start_at`, `end_at`, `min_order_amount`, `description` などを、API の `serialNumber`, `couponName`, `awardType`, `awardValue`, `startDate`, `endDate`, `minTargetPrice`, `summary` などに変換している。
- **登録**: `CouponAdminService::create()` が、スマレジ契約ありの場合に先に `SmaregiCouponSyncService::registerToSmaregi()` を呼ぶ。**API が成功した場合のみ** DB に保存し、`smaregi_coupon_id` を付与する。失敗時は `RuntimeException` を投げ、**DB には保存しない**。コントローラーで catch し、作成画面にリダイレクトして `session('error')` でメッセージを表示する。
- **更新**: `CouponAdminService::update()` が先に `updateOnSmaregi()` または `registerToSmaregi()` を呼ぶ。**API が成功した場合のみ** DB を更新する。失敗時は `RuntimeException` を投げ、**DB は更新しない**。コントローラーで catch し、編集画面にリダイレクトしてエラーメッセージを表示する。
- **削除**: `CouponAdminService::delete()` が、スマレジ契約ありで `smaregi_coupon_id` がある場合に先に `SmaregiCouponSyncService::deleteOnSmaregi()` を呼ぶ。**API が成功した場合のみ** DB から削除する。失敗時は `RuntimeException` を投げ、コントローラーで catch して一覧にリダイレクトし `session('error')` で表示する。

### 商品登録・更新・削除の実装箇所

- **ボディ組み立て**: `app/Services/Smaregi/SmaregiProductRegisterService.php` の `buildBodyFromArray()`。  
  フォームの `name`, `price`, `description`, `is_available` などを、API の `productName`, `price`（string）, `displayFlag`, `description` などに変換。`categoryId` はスマレジの部門一覧の先頭を利用（登録前にスマレジで部門が1件以上必要）。
- **登録**: `ProductAdminService::create()` が、スマレジ契約ありの場合に先に `SmaregiProductRegisterService::registerToSmaregi()` を呼ぶ。**API が成功した場合のみ** DB に保存し、`smaregi_product_id` を付与する。失敗時は `RuntimeException` を投げ、**DB には保存しない**。コントローラーで catch し、作成画面にリダイレクトして `session('error')` でメッセージを表示する。
- **更新**: `ProductAdminService::update()` が先に `updateOnSmaregi()` を呼ぶ（既存商品に `smaregi_product_id` がない場合は `registerToSmaregi()` で新規登録）。**API が成功した場合のみ** DB を更新する。
- **削除**: `ProductAdminService::delete()` が、スマレジ連携済み商品の場合は先に `deleteOnSmaregi()` を呼ぶ。**API が成功した場合のみ** DB から削除する。
- **スコープ**: 商品の登録・更新・削除には `.env` の `SMAREGI_SCOPE` に `pos.products:write` および部門取得のため `pos.products:read` が必要。
- **在庫**: 商品の作成・更新時に `stock` が含まれる場合、`SmaregiProductRegisterService::syncStockToSmaregi()` で先頭店舗の在庫を `pos/stock/{product_id}` に反映する。`pos.stock:write` スコープが必要。店舗が0件の場合は在庫同期はスキップされる。

### 会員ランク設定の実装箇所

- **同期**: 設定画面でランク・ポイント設定を保存する際、`UpdateRankSettingsUseCase::handle()` が**DB保存の前に** `SmaregiCustomerRankSyncService::sync()` を呼ぶ。スマレジ契約がある場合、ランク一覧を `pos/customer_ranks` に登録・更新・削除してから、ShopSetting に保存する。失敗時は `RuntimeException` を投げ、RanksController で catch して `session('error')` で表示する。スコープは `pos.customers:write`。

### 会員（退会）の実装箇所

- **退会**: `MemberWithdrawUseCase::handle()` が、会員に `smaregi_customer_id` がありかつプロジェクトにスマレジ契約がある場合、先に `SmaregiApiService::deleteCustomer()` を呼ぶ。**API が成功した場合のみ** 会員・ユーザーをソフトデリートする。失敗時は `RuntimeException` を投げ、退会APIのコントローラーで catch して 500 でメッセージを返す。会員の**新規登録・更新**は `MemberService::registerMember()` および `MemberAdminService::updateMemberProfile()` で、いずれもスマレジへ先に登録/更新してからDBを保存している。

### 店舗・部門のAPI

- `SmaregiApiService` に `createStore` / `updateStore` / `deleteStore`、`createDepartment` / `updateDepartment` / `deleteDepartment` を用意済み。管理画面で店舗・部門のCRUDを行う場合は、同様に「スマレジへ先に登録・更新・削除してからDB」の流れで連携できる。

### スマレジに該当APIがないデータ（独自機能）

スタンプカード・お知らせ・ギャラリー・予約設定・待ち行列など、スマレジPOS API に直接対応するエンドポイントがないデータは、**DB のみ**で管理し、スマレジ同期対象外である。これらはアプリ独自の機能としてそのまま利用できる。

---

## エラーとトラブルシュート

### トークン取得に失敗する（null）

- `.env` の `SMAREGI_CLIENT_ID`, `SMAREGI_CLIENT_SECRET` が設定されているか確認する。
- 契約IDが正しいか、管理画面のスマレジ連携で確認する。
- サンドボックス利用時は `id.smaregi.dev` へ接続できるか（ファイアウォール・VPN）を確認する。
- ログ: `storage/logs/laravel.log` で `[Smaregi]` を検索する。

### クーポン登録で 400 Bad Request

- **レスポンスの `detail`** を確認する。例: 「シリアル番号が重複」「awardValue は 0 以上で入力」など。
- 本アプリでは、失敗時にログに **送信したボディ** が `request_body_sent` として出力される。型と必須項目が仕様どおりか確認する。
- 確認ポイント: `awardValue` は文字列か（例: `"10"`）。`awardType` が `"2"` のとき `awardValue` は 0〜100 か。`startDate` / `endDate` は YYYY-MM-DD か。`serialNumber` が20文字以内か。

### 403 Forbidden

- スコープ不足の可能性。`.env` の `SMAREGI_SCOPE` に `pos.transactions:read` / `pos.transactions:write`（クーポン）、`pos.products:read` / `pos.products:write`（商品）が含まれているか確認する。
- プラン制限（例: giftee はプレミアムプラス以上）に違反していないか確認する。

### 商品登録に失敗する

- スマレジに**部門（カテゴリ）が1件以上**登録されていないと商品登録は失敗する。先にスマレジ管理画面で部門を作成する。
- `pos.products:write` スコープが `SMAREGI_SCOPE` に含まれているか確認する。
- ログに `[Smaregi]` で API エラー内容が出力される。`storage/logs/laravel.log` を確認する。

### 一覧取得で 404 や空になる

- path の表記が公式と異なる場合がある（例: `pos/payment-methods` と `pos/payment_methods`）。本アプリで使っている path は `SmaregiApiService` を参照する。
- 取引一覧は日付範囲のクエリが必須。`sum_date-from` と `sum_date-to` などを指定しているか確認する。

---

このドキュメントで、API の型・リクエスト/レスポンス・本アプリの実装・エラー対応まで一通り把握できます。追加の詳細は [公式 POS 仕様書](https://developers.smaregi.dev/platform-api-reference/apis/pos/) の各 API ページを参照してください。
