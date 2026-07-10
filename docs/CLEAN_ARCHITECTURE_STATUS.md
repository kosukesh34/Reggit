# クリーンアーキテクチャ採用状況

プロジェクト全体で「Controller → UseCase → Service」の責務分離がどこまで行われているかをまとめたドキュメントです。

---

## 結論

**ほぼすべての Controller でクリーンアーキテクチャ（Controller → UseCase → Service）が採用されています。**

- API・管理画面（Admin）・Web・Webhook・Device/AppConfig は UseCase 委譲済み
- レガシー Controller（`AdminController`, `Admin/LoginController`, `Admin/AccountController`, `Api/V1/MemberController` 等）は削除済み
- UseCase 間の呼び出しはなし
- `Request` 依存が残る UseCase は解消済み（Auth 含む）

---

## 1. レイヤー構成（目標パターン）

```
Controller → FormRequest / InputFactory → Input or User
UseCase    → Service / Model（Request 非依存）
Service    → 永続化・外部連携
```

Factory の例:

- `app/Http/Support/QueueMeInputFactory.php`
- `app/Http/Support/ClientDebugLogInputFactory.php`
- `app/Http/Support/SmaregiNotificationInputFactory.php`
- `app/Http/Support/LineWebhookInputFactory.php`
- `app/Http/Support/AppleWalletPasskitInputFactory.php`
- `app/Http/Support/AdminAuthInputFactory.php`
- `app/Http/Support/AdminSettingsInputFactory.php`

信頼できる参照実装: `Admin/Account/AccountStoreController`, `LiffDesignerController`, system-admin / `Admin/Account` 配下

---

## 2. 主要ドメインの UseCase 化状況

| 領域 | 状態 |
|------|------|
| Member（登録・更新・ポイント・退会） | ✅ `MemberProfileRequest` + UseCase |
| Order / Reservation / Queue | ✅ `User` 受け取り |
| Coupon / Stamp / Announcement | ✅ |
| Admin（会員・注文・予約・クーポン・お知らせ等） | ✅ |
| Smaregi 設定・Webhook / IdP | ✅ Input 化済み |
| Line Webhook | ✅ `LineWebhookInput` |
| Device / AppConfig | ✅ |
| Wallet Pass（ダウンロード・URL発行） | ✅ |
| Apple Wallet PassKit Web Service | ✅ `Passkit*UseCase` + `AppleWalletPasskitService` |
| Payment | ✅ |
| Auth（ログイン・ログアウト） | ✅ `AdminAuthInputFactory` + Input 化済み |
| Admin 薄ラッパー UseCase | ✅ 方針統一済み（`CLEAN_ARCHITECTURE.md` §7） |
| Repository 層 | 🔄 段階導入中（`Project` / `AdminUser` / `UserDevice` / `User`） |
| DDD（Domain 層） | ✅ 約 **98%**（コア完了。Admin UI 設定の静的 API・読みモデル Eloquent は意図的残置。`docs/DDD.md`） |

---

## 3. Controller で Service を直接使う箇所

| 箇所 | 理由 |
|------|------|
| 各所の `UserService` | ユーザー解決用（許容） |
| （なし） | PassKit は `AppleWalletPasskitService` 経由で UseCase 化済み |

---

## 4. Request 依存

UseCase 層から `Illuminate\Http\Request` への直接依存は解消済み。

`RouteContextResolver` は `app/Http/Support/` に移動（Controller 支援用）。

---

## 5. 削除済みレガシー

- `app/Http/Controllers/AdminController.php`
- `app/Http/Controllers/Admin/LoginController.php`
- `app/Http/Controllers/Admin/AccountController.php`
- `app/Http/Controllers/Api/V1/MemberController.php`
- `app/Http/Controllers/Api/V1/ReservationController.php`
- `app/Http/Controllers/Api/V1/QueueController.php`
- 仮想 POS 機能（`/admin/{project_key}/settings/smaregi/pos-data` 関連一式）

---

## 6. 今後の対応案（優先度順）

1. **Repository 層の拡張** — `ShopSetting` / 各 `*AdminService` の重複クエリから順次切り出し
2. **レガシー JS** `public/js/admin/liff-designer/` — React 移行未完了のため慎重に

---

*最終更新: 2026-07-10*
