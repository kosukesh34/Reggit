# Reggit 全体アーキテクチャ

他の開発者が「このアプリが何で、どう設計されているか」を一通り把握するための設計図です。

**読み方（全体 → 細部）**

1. **大枠・レーン・CRUD** → 先に [PROCESS_FLOWS.md](./PROCESS_FLOWS.md)（L0→L3）
2. **層・依存・テナント・イベント** → この文書（以下）
3. **UseCase 規約** → [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md)
4. **Domain 境界** → [DDD.md](./DDD.md)
5. **機能仕様の文言** → [FEATURES_SPEC.md](./FEATURES_SPEC.md)

---

## 1. プロダクト概要

**Reggit** は LINE（LIFF）を起点にした、店舗向けのマルチテナント SaaS です。

| 利用者 | 主な画面 / API |
|--------|----------------|
| 顧客 | LIFF / ネイティブ WebView（注文・会員証・予約・順番待ち・スタンプ・クーポン） |
| 店舗管理者 | `/admin/{project_key}/…`（注文・会員・設定・LIFF デザイナー） |
| システム管理者 | `/system-admin/…`（プロジェクト・アカウント・API トークン） |
| 店舗デバイス | Kitchen API / 順番待ち iPad |
| 外部システム | スマレジ Webhook・IdP、Stripe Webhook、LINE Webhook |

技術スタックの要約: PHP 8.4 / Laravel 12 / MySQL 8 / Vite + React 18（LIFF・Admin 一部）。

---

## 2. システム全体図

```mermaid
flowchart TB
  subgraph actors [アクター]
    Customer[顧客 LINE / ネイティブ]
    StoreAdmin[店舗管理者]
    SysAdmin[システム管理者]
    KitchenDev[キッチン / iPad]
  end

  subgraph edge [入口]
    LIFF["/liff/{project_key}"]
    AdminUI["/admin/{project_key}"]
    SysUI["/system-admin"]
    ApiCust["/api/* bind.project"]
    ApiV1["/api/v1/{project_key}"]
    KitchenAPI["/api/kitchen/{project_key}"]
    Hooks["Webhook / IdP"]
  end

  subgraph app [Reggit Laravel]
    MW[Middleware\n認証 / project_key 束縛]
    Ctrl[Controllers]
    UC[UseCases]
    Dom[Domain]
    Infra[Infrastructure]
    Svc[Services]
    Jobs[Jobs / Listeners]
    DB[(MySQL)]
  end

  subgraph ext [外部]
    Smaregi[スマレジ]
    Stripe[Stripe]
    LINE[LINE]
    Wallet[Apple / Google Wallet]
    JP[日本郵便]
    Push[FCM]
  end

  Customer --> LIFF
  Customer --> ApiCust
  StoreAdmin --> AdminUI
  SysAdmin --> SysUI
  KitchenDev --> KitchenAPI
  Smaregi --> Hooks
  Stripe --> Hooks
  LINE --> Hooks

  LIFF --> MW
  AdminUI --> MW
  SysUI --> MW
  ApiCust --> MW
  ApiV1 --> MW
  KitchenAPI --> MW
  Hooks --> MW

  MW --> Ctrl --> UC
  UC --> Dom
  UC --> Svc
  UC --> Infra
  Dom --> Infra
  Svc --> Jobs
  Infra --> DB
  Jobs --> Smaregi
  Jobs --> LINE
  Jobs --> Push
  Svc --> Stripe
  Svc --> Wallet
  Svc --> JP
  Svc --> Smaregi
```

---

## 3. リクエストの標準パイプライン

すべての機能は、原則この一本道です。

```mermaid
flowchart LR
  R[HTTP Request] --> MW[Middleware]
  MW --> AC[AdminContext に Project セット]
  AC --> C[Controller]
  C --> IN[Input / FormRequest]
  IN --> UC[UseCase::handle]
  UC --> DOM[Domain Policy / Aggregate]
  UC --> REPO[Repository IF]
  UC --> SVC[Service]
  REPO --> ELO[Eloquent + ProjectScope]
  SVC --> EXT[外部 API]
  UC --> EVT[Domain Event]
  EVT --> LIS[Listener / Job]
  UC --> OUT[Output / View / JSON]
  OUT --> RES[HTTP Response]
```

### 層の責務（必守）

| 層 | 置いてよいもの | 置いてはいけないもの |
|----|----------------|----------------------|
| Controller | 受付・バリデーション・Input 組み立て・レスポンス | 業務判断・外部 API 直叩き |
| UseCase | 「何をするか」1 本のオーケストレーション | `Request` 依存、UseCase→UseCase 呼び出し |
| Domain | ルール・Policy・Repository IF・Event | Eloquent / HTTP |
| Infrastructure | Eloquent 実装・Event Listener | 画面ロジック |
| Service | 外部連携・再利用技術処理 | Controller から直呼び（原則 UseCase 経由） |

参照実装の優先順位: **system-admin / Admin 配下** → 顧客 API。

---

## 4. 入口（ルーティングの地図）

```mermaid
flowchart TB
  subgraph web [routes/web.php]
    W1["/liff + bind.project"]
    W2["/admin + auth:admin + bind.admin.project"]
    W3["/system-admin + system.admin"]
    W4["/smaregi/webhook, /smaregi/idp"]
    W5["/wallet-add, /google-wallet/save/{token}"]
    W6["/onboarding, /health, /queue/open/..."]
  end

  subgraph api [routes/api.php → /api]
    A1["顧客 JSON: web + bind.project\nproducts/orders/members/queue/..."]
    A2["/api/v1 + AuthenticateApiToken"]
    A3["/api/v1/{project_key} + token"]
    A4["/api/kitchen/{project_key}"]
    A5["/api/line/webhook"]
    A6["/api/smaregi/*, /api/payment/webhook"]
    A7["/api/apple-wallet/v1/..."]
  end
```

| 入口 | 認証の考え方 | テナント解決 |
|------|--------------|--------------|
| LIFF / 顧客 API | LINE User ID ヘッダ等（Laravel の user guard ではない） | `bind.project` |
| 店舗 Admin | `auth:admin` セッション + `admin.permission` + `admin.plan` | `bind.admin.project` |
| システム Admin | `auth:admin` + `system.admin` | 横断（プロジェクト CRUD） |
| API v1 | Bearer / `X-Api-Token` | トークンの project または URL `{project_key}` |
| Kitchen | project_key で店舗特定 | ルート `project_key` |
| Webhook | 署名検証ミドルウェア等 | ペイロード / 契約 ID から解決 |

登録元: `app/Providers/RouteServiceProvider.php`。

---

## 5. マルチテナント設計

**店舗 = `projects` 1 行。URL / ヘッダの `project_key` で必ず束縛する。**

```mermaid
sequenceDiagram
  participant Req as Request
  participant MW as BindProject / BindAdminProject
  participant Ctx as AdminContext
  participant UC as UseCase
  participant M as Eloquent Model
  participant Scope as ProjectScope

  Req->>MW: project_key
  MW->>Ctx: setCurrentProject(Project)
  Req->>UC: Controller 経由
  UC->>M: query / save
  M->>Scope: global scope
  Scope->>Ctx: getCurrentProjectId()
  Scope-->>M: WHERE project_id = ?
```

| 部品 | パス | 役割 |
|------|------|------|
| `bind.project` | `app/Http/Middleware/BindProjectFromRequest.php` | 顧客・LIFF・公開系 |
| `bind.admin.project` | `app/Http/Middleware/BindAdminProject.php` | Admin + 権限チェック |
| `AdminContext` | `app/Util/AdminContext.php` | リクエスト内の現在店舗 |
| `ProjectScope` | `app/Models/Scopes/ProjectScope.php` | 他店データ遮断 |
| `BelongsToProject` | `app/Models/Concerns/BelongsToProject.php` | Model に scope + 作成時 project_id |

**デグレ観点:** UseCase / Repository で「別 project_id を明示指定して読む」場合は、スコープ外しが意図的か必ず確認する。

---

## 6. 認証・認可の整理

```mermaid
flowchart TB
  subgraph adminAuth [管理画面]
    Login[Admin ログイン] --> Session[admin guard セッション]
    Session --> Perm[admin.permission]
    Session --> Plan[admin.plan PlanFeatures]
  end

  subgraph custAuth [顧客]
    LIFFInit[LIFF init] --> IdToken[X-Line-Id-Token]
    IdToken --> Verify[LINE verify ID token]
    Verify --> UserSvc[UserService で User/Member 解決]
  end

  subgraph apiAuth [外部 API]
    Token[API Token] --> Hash[token_hash 照合]
    Hash --> Ctx[AdminContext]
  end
```

プラン機能ゲート（累積）: `app/Support/PlanFeatures.php`

- `normal` ⊂ `distribution` ⊂ `premium`
- 例: 抽選・Wallet・高度分析・LIFF デザイナー高度は premium 系

---

## 7. バックエンドのディレクトリと依存の向き

```mermaid
flowchart TB
  Http["app/Http\nControllers / Middleware / Requests"]
  UC["app/UseCases\nAdmin / Member / Order / ..."]
  Domain["app/Domain\nMember Order Coupon Reservation Queue Settings Shared"]
  Infra["app/Infrastructure\nPersistence/Eloquent\nEvents"]
  Services["app/Services\nSmaregi Line Wallet ..."]
  Models["app/Models\n+ Models/Settings"]
  Jobs["app/Jobs"]

  Http --> UC
  UC --> Domain
  UC --> Services
  UC --> Infra
  Infra --> Domain
  Infra --> Models
  Services --> Models
  Services --> Jobs
  Domain -.->|IF のみ依存| Infra
```

### Bounded Context（Domain）

| Context | 主な関心 |
|---------|----------|
| Member | ポイント台帳、ランク、`PointsEarned` |
| Order | 作成・在庫・決済確定・スタンプ、同期イベント |
| Coupon | 利用・発行・配信セグメント Policy |
| Reservation | 予約・空き枠・ステータス |
| Queue | 順番待ち・呼出・入店 |
| Settings | `ShopSettingRepository`（設定の唯一の入口） |

---

## 8. 設定（Settings）の設計

旧 EAV / `ShopSetting` 静的ファサードは廃止済み。  
**読み書きは必ず `ShopSettingRepository` 経由。**

```mermaid
flowchart LR
  UC[UseCase / Service] --> IF[ShopSettingRepository]
  IF --> Impl[EloquentShopSettingRepository]
  Impl --> Store[ShopSettingStore]
  Store --> T1[ProjectBusinessSetting]
  Store --> T2[ProjectLineLiffSetting]
  Store --> T3[ProjectFeatureFlags]
  Store --> T4[ProjectMember* / Wallet / ...]
```

| ファイル | 役割 |
|----------|------|
| `app/Domain/Settings/Repositories/ShopSettingRepository.php` | IF |
| `app/Domain/Settings/ShopSettingKeys.php` | 定数（ORDER_MODE 等） |
| `app/Infrastructure/.../EloquentShopSettingRepository.php` | 実装 |
| `app/Infrastructure/.../ShopSettingStore.php` | KEY → 正規化テーブル |
| `app/Models/Settings/Project*Setting.php` | 1 project = 各テーブル 1 行 |

---

## 9. ドメインイベントと非同期

```mermaid
flowchart LR
  UC[UseCase] --> Disp[DomainEventDispatcher]
  Disp --> Multi[MulticastDomainEventDispatcher]
  Multi --> Log[LoggingDomainEventListener]
  Multi --> N1[NotifyOnOrderConfirmed]
  Multi --> N2[NotifyOnOrderReady]
  Multi --> S1[SyncOrderPointsToSmaregi]
  Multi --> S2[SyncOrderTransactionToSmaregi]
  Multi --> S3[SyncPointsEarnedToSmaregi]
  S1 --> Job[ShouldQueue Job]
  S2 --> Job
  Job --> Smaregi[スマレジ API]
```

代表イベント:

| Event | 主な副作用 |
|-------|------------|
| `OrderConfirmed` / `OrderReady` | LINE 等通知 |
| `OrderPointsSyncRequested` | 注文ポイントのスマレジ同期（冪等カラムあり） |
| `OrderTransactionSyncRequested` | 取引のスマレジ同期 |
| `PointsEarned` | ポイント変動のスマレジ同期 |

本番の予約配信・同期は **キューワーカー前提**（`QUEUE_CONNECTION=sync` は開発向け）。

---

## 10. フロントエンド設計

```mermaid
flowchart TB
  Blade[Blade: line.blade / native.blade / admin layout]
  Vite[Vite]
  FE["resources/js/frontend/main.tsx"]
  AD["resources/js/admin/main.tsx"]

  Blade --> Vite
  Vite --> FE
  Vite --> AD

  FE --> Roots["複数 root\nchrome / tabs / footer / bootstrap"]
  Roots --> Store[frontendStore\napiClient / pageRouter / tab]
  Roots --> Pages["features/*Page.tsx\nhome products orders member ..."]

  AD --> Sidebar[AdminSidebar]
  AD --> Designer[liff-designer/LiffDesignerApp]
```

| 領域 | パス | メモ |
|------|------|------|
| LIFF 入口 | `resources/js/frontend/main.tsx` | React タブページ |
| 共有状態 | `resources/js/frontend/context/frontendStore.ts` | 複数 root 間でタブ共有 |
| 機能ページ | `resources/js/frontend/features/*/ ` | タブ単位の UI |
| API クライアント | `resources/js/shared/utils/apiClient.ts` | LINE User ID 付与 |
| Admin デザイナー | `resources/js/admin/liff-designer/` | ブロック編集 UI |

顧客の業務 API は `/api/...`（`bind.project`）を叩く。

---

## 11. 代表ユースケースの流れ

### 11.1 顧客が注文する

```mermaid
sequenceDiagram
  participant UI as LIFF Products/Checkout
  participant API as /api/orders
  participant C as Order Controller
  participant UC as OrderStoreUseCase
  participant Dom as Order Domain
  participant DB as MySQL
  participant Pay as Stripe optional
  participant Ev as Domain Events
  participant Sm as Smaregi Jobs

  UI->>API: POST 注文
  API->>C: bind.project
  C->>UC: OrderStoreInput
  UC->>Dom: 検証・在庫・作成
  Dom->>DB: orders / items
  opt オンライン決済
    UC->>Pay: PaymentIntent
  end
  UC->>Ev: OrderConfirmed 等
  Ev->>Sm: ポイント / 取引同期 Job
  UC-->>UI: 注文結果 JSON
```

### 11.2 店舗が設定を保存する

```mermaid
sequenceDiagram
  participant A as 店舗管理者
  participant AC as Admin Settings Controller
  participant UC as Update*SettingsUseCase
  participant Repo as ShopSettingRepository
  participant DB as Project*Setting

  A->>AC: POST /admin/{project_key}/settings/...
  AC->>AC: auth + permission + plan
  AC->>UC: payload
  UC->>Repo: set*
  Repo->>DB: upsert by project_id
  UC-->>A: redirect / JSON
```

### 11.3 スマレジ連携（双方向のイメージ）

```mermaid
flowchart LR
  subgraph out [Reggit → スマレジ]
    OrderUC[注文 / ポイント UseCase] --> EvOut[Domain Event]
    EvOut --> JobOut[Outbound Job]
    JobOut --> APIOut[SmaregiApiService]
  end

  subgraph inb [スマレジ → Reggit]
    WH[Webhook / IdP] --> CtrlIn[Smaregi Controller]
    CtrlIn --> UCIn[Smaregi UseCase]
    UCIn --> JobIn[Inbound Sync Job]
    JobIn --> DB2[(会員 / 取引反映)]
  end
```

---

## 12. データモデルの中心

```mermaid
erDiagram
  PROJECT ||--o{ ADMIN_USER_PROJECT : grants
  PROJECT ||--o{ MEMBER : has
  PROJECT ||--o{ ORDER : has
  PROJECT ||--o{ PRODUCT : has
  PROJECT ||--|| PROJECT_BUSINESS_SETTING : settings
  PROJECT ||--|| PROJECT_LINE_LIFF_SETTING : settings
  PROJECT ||--|| PROJECT_FEATURE_FLAGS : settings
  MEMBER ||--o{ ORDER : places
  MEMBER ||--o{ POINT_TRANSACTION : ledger
  MEMBER ||--o{ MEMBER_COUPON : holds
  ORDER ||--o{ ORDER_ITEM : contains
```

マルチテナントの軸は常に **`projects.id` / `project_key`**。

---

## 13. 設計判断の早見表

| やりたいこと | 置く場所 |
|--------------|----------|
| 画面・API の入口 | `Http/Controllers` |
| バリデーション | FormRequest / Controller（UseCase ではやらない） |
| 業務の一連の流れ | `UseCases/...` |
| 不変条件・権限ポリシー | `Domain/...` |
| DB アクセス抽象 | `Domain/.../Repositories` + `Infrastructure/Persistence` |
| スマレジ・LINE・Wallet | `Services/...`（UseCase から呼ぶ） |
| 店舗設定の読み書き | `ShopSettingRepository` のみ |
| 非同期副作用 | Domain Event → Listener / Job |
| LIFF UI | `resources/js/frontend/features` |
| Admin LIFF デザイン | `resources/js/admin/liff-designer` |

---

## 14. 関連ドキュメント

| ドキュメント | 内容 |
|--------------|------|
| [ARCHITECTURE.md](./ARCHITECTURE.md) | 全体設計図 |
| [PROCESS_FLOWS.md](./PROCESS_FLOWS.md) | **CRUD・機能別の処理フロー図** |
| [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) | UseCase / Input / 依存ルール |
| [DDD.md](./DDD.md) | Bounded Context・イベント |
| [FEATURES_SPEC.md](./FEATURES_SPEC.md) | 機能・画面仕様 |
| [PERMISSIONS.md](./PERMISSIONS.md) | Admin 権限 |
| [SMAREGI_SETUP.md](./SMAREGI_SETUP.md) | スマレジ接続 |
| [SECURITY.md](./SECURITY.md) | セキュリティ |
| [CODING_STANDARDS.md](../CODING_STANDARDS.md) | コーディング規約 |

---

*最終更新: 2026-08-07*
