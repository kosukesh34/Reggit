# コーディング規則 準拠状況レポート

## 調査日
2025年3月実施（2026年3月 再確認・一部リファクタ実施）

## 1. 規則準拠の確認結果（抜粋）

| 項目 | 状態 | 備考 |
|------|------|------|
| empty() の使用制限 | ✅ | app/ 内に `empty()` の直接使用なし |
| 厳密比較 (===) | ✅ | app/ 内に `==` の使用なし |
| bool変数名 (xxxFlg/xxxBool 禁止) | ✅ | 該当命名なし |
| get〇〇Attribute の制限 | ✅ | 使用なし（getDefaultAttributes は Eloquent アクセサではない） |
| 定数・Enum | ✅ | EnumBase 継承の Enum クラスを利用（App\Enums\*） |
| View・DTO | 要確認 | 4つ以上渡す場合は DTO 化を推奨 |
| 関数15行・クラス100行目安 | ⚠️ | 一部ファイルが超過（下記「500行超ファイル」参照） |
| **UseCase から UseCase を呼ばない** | ✅ | 対応済み：MemberAddPointsUseCase は SmaregiApiService を利用するよう変更 |
| **Input を try で囲まない** | ✅ | 対応済み：UpdateLiffDesignerInput::fromRequest で ValidationException に統一 |
| **Controller の DI** | ✅ | UseCase/Service は __construct 注入。Input の new は DTO として許容 |
| **トランザクション** | ✅ | OrderService は複数テーブル更新のため DB::transaction 使用で妥当 |
| **Validator を UseCase で使わない** | ✅ | UseCase 内に Validator::make なし |
| **@param mixed 禁止** | ✅ | 該当なし |
| **Carbon 変換** | ⚠️ | 本プロジェクトに DateUtil クラスなし。Carbon::parse を直接使用している箇所あり（必要なら DateUtil 導入を検討） |

## 2. 500行超のファイル一覧と対応

以下のファイルが「1ファイル500行程度まで」の目安を超えているため、分割・リファクタの対象とする。

| ファイル | 行数 | 対応 |
|----------|------|------|
| `public/js/frontend/managers/MemberManager.js` | 1583 | テンプレート・Wallet処理を別ファイルに分割 |
| `app/Models/ShopSetting.php` | 1232 | LIFFデフォルトを `ShopSettingLiffDefaults` に分離 |
| `public/js/admin/liff-designer.js` | 1065 | モジュール分割を推奨（要対応） |
| `resources/views/admin/stamps/create.blade.php` | 1015 | パーシャル化を推奨（要対応） |
| `public/js/frontend/app.js` | 941 | 初期化・タブ別に分割を推奨（要対応） |
| `public/js/frontend/managers/ReservationManager.js` | 906 | 責務ごとに分割を推奨（要対応） |
| `app/Http/Controllers/AdminController.php` | 817 | UseCase/Service 委譲済み。サブコントローラ分割を推奨（要対応） |
| `app/Services/WalletPassService.php` | 645 | 要対応 |
| `app/Services/MemberService.php` | 542 | 要対応 |
| `app/Services/GoogleWalletPassService.php` | 515 | 要対応 |
| `resources/views/index.blade.php` | 507 | 要対応 |

## 3. SwiftUI（MVVM）の確認

- **View**: `ContentView` は表示のみ。`@State var viewModel = MobileWebShellViewModel.default` で ViewModel を保持し、`viewModel.liffURL` / `viewModel.webConfiguration` / `viewModel.retryLoad()` 等を参照。ビジネスロジックは持たない。
- **ViewModel**: `MobileWebShellViewModel` は `@Observable` で状態（`isLoading`, `showWhiteScreenFallback`, `webViewReloadKey` 等）と振る舞い（`onAppear`, `onNavigationFinished`, `retryLoad` 等）を担当。UseCase（`BuildNativeInjectionScriptUseCase`）や Repository に依存。
- **Model**: 設定は `MobileWebShellConfig` / `BundleMobileWebConfigRepository` で表現。

→ **SwiftUI は MVVM 構成**。不要なコメントは削除済み（基本コメント不要の方針に合わせて Swift 側の /// および // を削除。`/* SKIP @bridge */` は Skip 用のため残置）。

## 4. SOLID 原則との整合

| 原則 | 状態 | 備考 |
|------|------|------|
| **S** 単一責務 | ✅ | Controller は入出力・UseCase 呼び出しに限定。UseCase/Service に処理を委譲。ShopSetting は設定ファサードとして一貫。 |
| **O** 開放閉鎖 | ✅ | Enum による定数管理、Interface による差し替え可能な設計（必要な箇所で適用）。 |
| **L** リスコフ | ✅ | 継承は EnumBase や Model など、置換可能な使い方に限定。 |
| **I** インターフェース分離 | 要確認 | 大きな Interface に依存しているクラスがなければ問題なし。 |
| **D** 依存性逆転 | ✅ | Controller/UseCase は __construct で UseCase/Service を注入。new で依存を生成しない（Input/DTO の new は許容）。 |

## 5. 実施したリファクタ（2026年3月）

1. **UseCase から UseCase を呼ばない**
   - `MemberAddPointsUseCase` が `SmaregiSyncCustomerPointsUseCase` を呼んでいたのを解消。
   - `SmaregiApiService::syncCustomerPointsForProject()` を追加し、`MemberAddPointsUseCase` は `SmaregiApiService` のみを依存に変更。

2. **Input を try で囲まない**
   - `LiffDesignerController::update` で `UpdateLiffDesignerInput` の生成を try-catch で囲んでいたのを廃止。
   - `UpdateLiffDesignerInput::fromRequest(Request)` を追加し、検証・サニタイズ失敗時は `ValidationException` を投げる設計に変更。Controller は `$input = UpdateLiffDesignerInput::fromRequest($request);` のみで try 不要。

## 6. DB テーブル構成の確認

### 6.1 マルチテナント（プロジェクト分離）

- **projects**: ルート。`project_key`, `name`, `is_active`, `plan` 等。
- **主要エンティティ**（users, members, products, orders, order_items, reservations, point_transactions, stamp_cards, member_stamps, coupons, coupon_redemptions, announcements, queue_entries 等）はすべて **project_id** を FK で持ち、`constrained('projects')->onDelete('cascade')` で整合性を担保。
- **一意制約**: 他プロジェクトと衝突しないよう `(project_id, xxx)` の複合ユニークを採用。
  - 例: `(project_id, order_number)`, `(project_id, member_number)`, `(project_id, line_user_id)`.
- **Model**: Order, Member, Product, User, Reservation 等は **BelongsToProject** trait を利用し、**ProjectScope** グローバルスコープで `AdminContext::getCurrentProjectId()` が設定されている場合は自動で project_id が付与される。管理画面からの参照は「現在選択中のプロジェクト」に限定される。

### 6.2 設定テーブル（正規化済み）

- 旧 EAV の **shop_settings** は廃止。設定種別ごとに正規化テーブルに分割済み（マイグレーション `2026_03_03_100000_create_project_settings_tables_by_key`）。
- **project_business_settings**, **project_line_liff_settings**, **project_feature_flags**, **project_admin_ui_settings**, **project_profile_smaregi_settings**, **project_member_points_settings**, **project_member_rank_settings**, **project_wallet_settings**, **project_announcement_settings** はすべて **project_id を PK** にし、1 プロジェクト 1 行で管理。
- **ShopSetting** モデルはこれらをキーで解決するファサード。実体は各 Settings モデル（ProjectBusinessSetting 等）。

### 6.3 指摘・推奨

- **DashboardService**: Order, Product, Member 等は BelongsToProject により ProjectScope がかかるため、管理画面から呼ばれる限り「現在のプロジェクト」で集計される。コンソールや API で AdminContext 未設定の場合はスコープがかからず全件になるため、その経路で呼ぶ場合は project_id を明示的に渡す設計を推奨。
- 新規テーブル追加時は、他テーブルと同様に **project_id** を付け、FK と必要に応じて複合ユニークを検討すること。

## 7. SOLID 原則の詳細確認（再調査）

| 原則 | 状態 | 詳細 |
|------|------|------|
| **S 単一責務** | ✅ | Controller は入出力・UseCase/Service 呼び出しに限定。UseCase は 1 ユースケース、Service はドメイン別に分割。ShopSetting は設定ファサードとして一貫。 |
| **O 開放閉鎖** | ✅ | Enum で定数管理。PaymentGateway は Interface で拡張可能。設定テーブル追加は新モデル＋KEY_TO_MODEL の追加で既存コードを変更せず拡張。 |
| **L リスコフ** | ✅ | 継承は EnumBase、Model、trait（BelongsToProject）等、置換可能な用法に限定。 |
| **I インターフェース分離** | ✅ | PaymentGateway は createPaymentIntent / retrievePaymentIntent / constructWebhookEvent の 3 メソッドのみ。過剰な Interface はなし。 |
| **D 依存性逆転** | ✅ | 対応済み。OrderAdminService は CouponIssueService をコンストラクタで必須注入に変更。MemberAdminService は MemberRankCalculator / MemberPointsExpiryCalculator をコンストラクタで注入し、findOrFail 内の app() を廃止。 |

## 8. コードの依存関係

### 8.1 望ましい依存の向き（一方向）

- **Controller** → UseCase / Service（入出力とオーケストレーションのみ）
- **UseCase** → Service, Model, Input/Output DTO（ユースケースのオーケストレーション）
- **Service** → Model, 他 Service（ドメイン・インフラ処理）
- **Model** → 他 Model（リレーション）、Util/設定ファサード（例: ShopSetting）は許容

逆方向（例: Model → Controller、Service → UseCase）や循環依存は避ける。

### 8.2 現状の依存の流れ

| レイヤー | 依存先 | 状態 |
|----------|--------|------|
| **API/フロント系 Controller** | UseCase, Request, Util | ✅ 一方向。UseCase に処理を委譲。 |
| **AdminController** | Service のみ（UseCase なし） | ✅ 一方向。従来型の Controller → Service。 |
| **QueueStatusController** | QueueStatusUseCase | ✅ 対応済み。UseCase 経由に変更。 |
| **DeviceRegisterController** | UserService, DeviceService | ✅ 一方向。シンプルな登録のため Service 直叩き。 |
| **UseCase** | Service, Model, DTO, Util | ✅ 一方向。UseCase 同士の呼び出しはなし（対応済み）。 |
| **Service** | Model, 他 Service（例: OrderService → MemberAdminService） | ✅ 一方向。循環なし。 |
| **Model** | ShopSetting 等の設定、他 Model | ✅ 一方向。ビジネスロジックの一部で設定参照は許容範囲。 |

### 8.3 循環依存

- **確認結果**: 循環依存は検出されていない。
- OrderService → MemberAdminService、CouponAdminService → SmaregiCouponSyncService など、Service 間は一方向のみ。

### 8.4 名前空間・層の向きで気になる点

| 項目 | 内容 | 推奨 |
|------|------|------|
| **LineService** | 旧 `App\Http\Services\LineService` | ✅ 対応済み。`App\Services\Line\LineService` に移動し、参照をすべて更新。旧ファイルは削除。 |

### 8.5 まとめ

- **依存の向き**: 全体として一方向（Controller → UseCase/Service → Model）で、循環はなし。
- **対応済み**: (1) QueueStatusController は QueueStatusUseCase 経由に変更。(2) LineService は `App\Services\Line\LineService` に移動済み。

## 9. 今後の推奨

- 新規・修正コードでは上記規則に従う。
- 500行超のファイルは、責務ごとに分割するか、既存の UseCase/Service/DTO に寄せる。
- コードレビュー時は「変更した部分」を中心に、上記規則との整合を確認する。
- 日付比較・Carbon 変換を統一する場合は `DateUtil::nullableCarbon` 等のユーティリティ導入を検討する。
- Service 内の `app(SomeClass::class)` は、可能な範囲でコンストラクタ引数に寄せ、依存を明示する。
- 依存関係の詳細は「8. コードの依存関係」を参照。
