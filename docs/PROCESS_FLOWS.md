# Reggit 処理フロー（全体 → 細部）

**上から順に読む想定です。** いきなりドメイン詳細に飛ばず、ズームレベルを下げていきます。

| レベル | 見るもの | この文書の節 |
|--------|----------|--------------|
| **L0 大枠** | アプリ全体で誰が何をするか | [§1](#1-l0-大枠アプリ全体) |
| **L1 レーン** | 顧客 / 店舗Admin / システムAdmin / 外部 | [§2](#2-l1-利用レーンの分解) |
| **L2 共通の型** | リクエストの通る型（CRUD パターン） | [§3](#3-l2-共通パターン) |
| **L3 機能別** | 商品・注文・会員…の CRUD 詳細 | [§4](#4-l3-機能別crud詳細) |
| **L4 確認用** | ミドルウェア表・コードの探し方 | [§5](#5-l4-確認用ミドルウェアとコードの場所) |

全体アーキテクチャの層・依存は [ARCHITECTURE.md](./ARCHITECTURE.md)。権限名は [PERMISSIONS.md](./PERMISSIONS.md)。

```mermaid
flowchart TB
  L0[L0 大枠] --> L1[L1 レーン]
  L1 --> L2[L2 共通パターン]
  L2 --> L3[L3 機能別 CRUD]
  L3 --> L4[L4 ミドルウェア / コード位置]
```

---

## 1. L0 大枠（アプリ全体）

まずこれだけ見れば十分、という一枚です。

```mermaid
flowchart TB
  subgraph who [誰が使うか]
    C[顧客]
    SA[店舗管理者]
    SYS[システム管理者]
    DEV[キッチン等デバイス]
    EXT[スマレジ / Stripe / LINE]
  end

  subgraph what [Reggit がやること]
    APP[マルチテナント店舗アプリ]
    APP --> F1[注文・会員・予約・順番待ち]
    APP --> F2[スタンプ・クーポン・お知らせ]
    APP --> F3[店舗設定・LIFFデザイン]
    APP --> F4[POS連携・決済・Wallet]
  end

  C --> APP
  SA --> APP
  SYS --> APP
  DEV --> APP
  EXT <--> APP
```

**一文:** 店舗ごとに分かれた LINE 会員・オーダー SaaS。顧客は LIFF、店舗は Admin、運営は system-admin、外部は Webhook / API でつながる。

---

## 2. L1 利用レーンの分解

大枠を **4本のレーン** に分けます。以降の詳細はすべてこのどれかに属します。

```mermaid
flowchart TB
  ROOT[Reggit]

  ROOT --> LANE1[レーンA 顧客]
  ROOT --> LANE2[レーンB 店舗Admin]
  ROOT --> LANE3[レーンC システムAdmin]
  ROOT --> LANE4[レーンD 外部・デバイス]

  LANE1 --> A1["画面: /liff/{project_key}"]
  LANE1 --> A2["API: /api/* + bind.project"]
  LANE1 --> A3[機能: 注文 会員 予約 待ち クーポン…]

  LANE2 --> B1["画面: /admin/{project_key}/*"]
  LANE2 --> B2["MW: auth:admin + bind.admin.project + permission"]
  LANE2 --> B3[機能: CRUD・ステータス・設定]

  LANE3 --> C1["画面: /system-admin/*"]
  LANE3 --> C2["MW: system.admin"]
  LANE3 --> C3[機能: プロジェクト・アカウント・APIトークン]

  LANE4 --> D1[Kitchen API]
  LANE4 --> D2[スマレジ Webhook / IdP]
  LANE4 --> D3[Stripe / LINE Webhook]
```

| レーン | 入口の例 | 認証の要点 |
|--------|----------|------------|
| A 顧客 | `/liff/...`, `/api/orders` | `project_key` + LINE User ID |
| B 店舗Admin | `/admin/{pk}/products` | セッション + 権限 +（必要なら）プラン |
| C システム | `/system-admin/projects` | システム管理者のみ |
| D 外部・デバイス | `/api/smaregi/webhook`, `/api/kitchen/...` | 署名 / トークン / project_key |

---

## 3. L2 共通パターン

レーンの中で、処理の「型」はほぼ次の2〜3種類です。

### 3.1 すべてのリクエストが通る一本道

```mermaid
flowchart LR
  Req[Request] --> MW[Middleware]
  MW --> Ctrl[Controller]
  Ctrl --> UC[UseCase]
  UC --> Next[Repository / Service / Event]
  Next --> DB[(DB)]
  Next --> Ext[外部API / Job]
  UC --> Res[Response]
```

### 3.2 型① Admin CRUD（一覧・作成・更新・削除）

```mermaid
flowchart TB
  Browser[管理者] --> URL["/admin/{pk}/リソース"]
  URL --> MW["web → auth:admin → bind.admin.project → admin.audit → permission"]
  MW --> Ctrl[Admin*Controller]
  Ctrl -->|GET| R[Index / Show / Edit画面]
  Ctrl -->|POST| C[StoreUseCase]
  Ctrl -->|PUT| U[UpdateUseCase]
  Ctrl -->|DELETE| D[DeleteUseCase]
  R --> DB[(自店舗 project_id)]
  C --> DB
  U --> DB
  D --> DB
```

当てはまる例: **商品・スタンプ・クーポン・お知らせ・ギャラリー・（一部）予約**

### 3.3 型② 顧客 API（読み取り / 自分の操作）

```mermaid
flowchart TB
  UI[React LIFF] --> API["/api/..."]
  API --> MW["web → bind.project"]
  MW --> Ctrl[Controller]
  Ctrl --> User[UserService]
  Ctrl --> UC[UseCase]
  UC --> DB[(ProjectScope)]
```

当てはまる例: **商品一覧、自分の注文、会員 me、マイクーポン**

### 3.4 型③ 状態遷移（CRUD の U がステータス）

```mermaid
flowchart LR
  Create[作成] --> Status[status 更新]
  Status --> Complete[完了 / 呼出 / 入店]
```

当てはまる例: **注文・順番待ち・（予約の status/complete）**

### 3.5 型④ 設定 Save（Repository 経由）

```mermaid
flowchart LR
  Form[設定画面] --> UC[Update*SettingsUseCase]
  UC --> Repo[用途別 Settings Contract]
  Repo --> Row[Project*Setting 1行]
```

---

## 4. L3 機能別 CRUD 詳細

ここからが細部です。上の **型①〜④のどれか** に対応しています。

```mermaid
flowchart TB
  subgraph map [機能マップ - どれを深掘りするか]
    P[商品 型①+②]
    O[注文 型②+③]
    M[会員 型②+①]
    R[予約 型①+②+③]
    Q[順番待ち 型③]
    Cp[クーポン 型①+②]
    An[お知らせ 型①+②+plan]
    St[スタンプ 型①+②]
    G[ギャラリー 型①+②]
    Se[設定 型④]
    Sy[システム管理 レーンC]
  end
```

---

### 4.1 商品 Products（型① + ②）

```mermaid
flowchart TB
  subgraph admin [レーンB Admin 型①]
    A1["GET .../products\npermission:products.view"] --> AI[AdminProductIndexUseCase]
    A2["POST .../products\nproducts.manage"] --> AS[AdminProductStoreUseCase]
    A3["PUT .../products/{id}"] --> AU[AdminProductUpdateUseCase]
    A4["DELETE .../products/{id}"] --> AD[AdminProductDeleteUseCase]
  end
  subgraph cust [レーンA 顧客 型②]
    C1["GET /api/products"] --> PI[ProductIndexUseCase]
    C2["GET /api/products/{id}"] --> PS[ProductShowUseCase]
  end
```

| CRUD | Admin | 顧客 |
|------|-------|------|
| C | POST `.../products` | — |
| R | GET 一覧・編集画面 | GET `/api/products` |
| U | PUT `.../{id}` | — |
| D | DELETE `.../{id}` | — |

---

### 4.2 注文 Orders（型② + ③）

```mermaid
flowchart TB
  Cust["レーンA POST /api/orders"] --> OS[OrderStoreUseCase]
  OS --> DB[(orders)]
  OS --> Ev[Domain Event → Job]

  AdmL["レーンB GET .../orders\norders.view"] --> AI[AdminOrderIndexUseCase]
  AdmS["POST .../status\norders.update"] --> AU[AdminOrderUpdateStatusUseCase]
  AdmC["POST .../complete"] --> AC[AdminOrderCompleteUseCase]

  Kit["レーンD Kitchen API"] --> K[KitchenOrderController]
```

| 操作 | 誰 | 経路 |
|------|-----|------|
| C 作成 | 顧客 | `POST /api/orders` |
| R 一覧/詳細 | 顧客・Admin | `/api/orders`, `.../orders` |
| U 状態 | Admin / Kitchen | status / complete |
| D | 実質キャンセル系 | cancel-unpaid 等 |

---

### 4.3 会員 Members（型② + ①）

```mermaid
flowchart TB
  Reg["レーンA POST /api/members/register"] --> MR[MemberRegisterUseCase]
  Me["GET/PUT /api/members/me"] --> MM[Me / Update]
  AL["レーンB GET .../members\nmembers.view"] --> ALI[AdminMemberListUseCase]
  AD["PUT/DELETE .../members/{id}\nmembers.manage"] --> AM[AdminMember*]
```

| CRUD | 顧客 | Admin |
|------|------|-------|
| C | register | — |
| R | me / points | 一覧・詳細 |
| U | me update | 編集・付帯操作 |
| D | withdraw | delete |

---

### 4.4 予約 Reservations（型① + ② + ③）

```mermaid
flowchart LR
  subgraph cust [レーンA]
    R1[available-dates] --> R2[POST reservations]
    R2 --> R3[PUT / cancel]
  end
  subgraph admin [レーンB]
    A1[CRUD] --> A2[status / complete]
    A3[settings/reservations] --> A4[枠保存 型④]
  end
```

権限: `reservations.view` / `update`。枠は `settings.manage`。

---

### 4.5 順番待ち Queue（型③）

```mermaid
flowchart TB
  Open["/queue/open/{pk}/{token}"] --> Join["POST /api/queue"]
  Join --> Me["GET /api/queue/me"]
  Me --> Act[cancel / call-next / enter]
  Adm["Admin .../queue"] --> St[status 更新]
```

CRUD というより **登録 → 呼出 → 入店** の状態機械。

---

### 4.6 クーポン Coupons（型① + ②）

```mermaid
flowchart TB
  CRUD[Admin CRUD coupons.*] --> U1[AdminCoupon*]
  Extra[bulk-issue / Smaregi sync] --> U2[Delivery / Sync]
  My["GET /api/members/me/coupons"] --> U3[MyCoupons]
  Use["POST .../use"] --> U4[UseMemberCoupon]
```

---

### 4.7 お知らせ Announcements（型① + ② + plan）

```mermaid
flowchart TB
  CRUD[Admin CRUD] --> AU[AdminAnnouncement*]
  Del["delivery\nplan:announcement_delivery"] --> AD[StoreDelivery]
  API["GET /api/announcements"] --> AI[Index]
  Read["POST .../read"] --> AR[MarkRead]
```

---

### 4.8 スタンプ / ギャラリー

**スタンプ:** Admin 型① CRUD（`stamps.*`）+ 顧客 `GET /api/stamps`。付与は注文完了等からも。

**ギャラリー:** Admin categories/items 型① + 顧客 `GET /api/gallery`。

---

### 4.9 設定 Settings（型④）

```mermaid
flowchart LR
  GET[GET settings] --> GetUC[Get*UseCase]
  POST[POST update] --> UpdUC[Update*UseCase]
  GetUC --> Repo[用途別 Settings Contract]
  UpdUC --> Repo
  Repo --> T[(Project*Setting)]
```

LIFF デザイナーは追加で `liff_designer.manage` + `plan:liff_designer_advanced`。

---

### 4.10 システム管理（レーンC）

```mermaid
flowchart TB
  MW["system.admin"] --> P[Projects]
  MW --> A[Accounts]
  P --> PU[Project*UseCase]
  A --> AU[Account*UseCase]
```

---

## 5. L4 確認用（ミドルウェアとコードの場所）

### 5.1 ミドルウェア帯

| レーン / 型 | 並び |
|-------------|------|
| B Admin CRUD | `web` → `auth:admin` → `bind.admin.project` → `admin.audit` → `admin.permission:*` |
| B + プラン | 上記 + `admin.plan:*` |
| A 顧客 API | `/api` + `web` → `bind.project` |
| C システム | `web` → `auth:admin` → `system.admin` → `admin.audit` |
| D Kitchen / Waiting | `auth:sanctum` → `store-app.auth:{kitchen|waiting}`（Bearer + 端末鍵） |
| D スマレジ WH | `verify.smaregi.webhook` |

定義: `app/Http/Kernel.php` / `app/Http/Middleware/`。

### 5.2 コードの探し方

| 知りたいこと | 見る場所 |
|--------------|----------|
| ルートと permission | `routes/web.php`, `routes/api.php` |
| Controller → UseCase | `app/Http/Controllers/...` |
| 業務 | `app/UseCases/...` |
| 設定保存 | 用途別 Settings contract |
| 注文の副作用 | Domain Event → Jobs |

---

## 6. 関連ドキュメント

| ズーム | ドキュメント |
|--------|--------------|
| 全体設計・層 | [ARCHITECTURE.md](./ARCHITECTURE.md) |
| 機能仕様 | [FEATURES_SPEC.md](./FEATURES_SPEC.md) |
| 権限キー | [PERMISSIONS.md](./PERMISSIONS.md) |
| UseCase 規約 | [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) |

*最終更新: 2026-08-07*
