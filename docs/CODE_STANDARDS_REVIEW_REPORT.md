# コード規約 全体レビューレポート

CODING_STANDARDS.md に基づき、プロジェクト全体を確認した結果です。

---

## ステータス

**§4 empty()・§15 var・§5 厳密比較・§6 bool変数名・§10 アクセサ**: プロジェクト全体で**規約違反ゼロ**です。  
**UseCase の Request 依存**は、CLEAN_ARCHITECTURE.md の方針どおり「リファクタ時に Input 化」する前提で、一覧のみ記載しています。

---

## 実施日・対象

- 対象: プロジェクト全体（app/, public/js/, resources/views/, tests/ 等）
- 規約: CODING_STANDARDS.md および コードレビュー指摘事項
- クリーンアーキテクチャ: docs/CLEAN_ARCHITECTURE.md に準拠

---

## 修正済み（今回対応したもの）

### 1. JavaScript: `var` の使用（§15）

- **ファイル**: `public/js/frontend/liff-init.js`
- **内容**: `var` を `const` に変更（4箇所: existing, wrapper, text, button）
- **規約**: 「JavaScriptでは const を優先。var は使用しない」

### 2. PHP: `empty()` の使用（§4）

- **app/Services/MemberService.php**: 配列の `enabled` / `required` を `(bool)($x ?? false) === true/false` で厳密比較に変更。`$result['customerId']` は `isset` と `(string) !== ''` に変更。
- **app/Services/Admin/MemberAdminService.php**: `empty($r['key'])` を `StrHelper::isEmptyStringOrNull($r['key'] ?? null)` に変更。
- **app/Http/Controllers/Admin/Settings/SmaregiController.php**: `!empty($result['errors'])` を `isset($result['errors']) && $result['errors'] !== []` に変更。
- **app/UseCases/Queue/QueueMeUseCase.php**: `empty($params)` を `!is_array($params) || $params === []` に変更。
- **app/Support/LiffDesignerSanitizer.php**: `!empty($row['shadow'])` を `(bool)($row['shadow'] ?? false)` に変更（bool 扱い）。
- **app/Http/Services/LineService.php**: `empty($this->channelAccessToken)` / `empty($this->channelSecret)` を `StrHelper::isEmptyStringOrNull(...)` に変更。

### 3. Blade: `empty()` の使用（§4）

- **resources/views/admin/stamps/index.blade.php**: `!empty($milestone['stamp_count']) && !empty($milestone['description'])` を `isset($milestone['stamp_count'], $milestone['description']) && (string)($milestone['description'] ?? '') !== ''` に変更。
- **resources/views/admin/settings/ranks.blade.php**: `!empty($previewLogoUrl)` / `!empty($iconUrl)` を `isset(…) && (string)… !== ''` に変更（4箇所）。

---

## 残存（方針に基づく技術的負債・対象外）

### UseCase の `handle(Request $request)`（§18 / クリーンアーキテクチャ）

- **規約**: 「UseCase は Request に依存せず、Input クラスまたはプリミティブで受け取る」
- **方針**: CLEAN_ARCHITECTURE.md の「既存の handle(Request) はリファクタ時に Input 化する」に従い、新規・変更時に Input 化を検討。

**Request を引数にしている UseCase（Middleware/Controller 除く）**

- Stamp/StampIndexUseCase, Member/MemberWithdrawUseCase, Coupon/CouponMyCouponsUseCase
- Member/MemberPointCardUseCase, Member/MemberAddPointsUseCase
- Diagnostics/ClientDebugLogUseCase
- Admin/Auth/SystemAdminLoginUseCase, AdminLogoutUseCase, AdminLoginUseCase
- Smaregi/SmaregiWebhookHandleUseCase, SmaregiIdpHandleUseCase
- Reservation/*, Queue/*, Order/*, Member/*, Line/LineWebhookHandleUseCase
- Announcement/AnnouncementMarkReadUseCase, AnnouncementIndexUseCase

※ Http/Middleware および Http/Controllers の `handle(Request)` は HTTP 層のため対象外。

### その他（確認済み・違反なし）

- **§5 厳密比較**: PHP で `==` の使用は検出なし。
- **§6 bool変数名**: `xxxFlg` / `xxxBool` の使用は検出なし。`is_active` 等は規約準拠。
- **§10 アクセサ**: `getXxxAttribute` の使用は検出なし。
- **§9 テスト**: 新規・変更コードへのテスト追加は、既存方針に従い必要に応じて対応。

---

## クリーンアーキテクチャの採用について

**採用されています。**

- 層: Controller（入力・レスポンス）→ UseCase（Input/プリミティブ）→ Service / Model
- FormRequest は Controller 層でバリデーションと UseCase 用ペイロード（toPayload 等）を提供。
- UseCase の Input 化は、既存コードはリファクタ時に段階的に実施する方針（CLEAN_ARCHITECTURE.md 記載）。

---

## 今後の確認の目安

- 新規 PHP: `empty()` を使わず `isset` / `===` / `StrHelper::isEmptyStringOrNull` を優先。
- 新規 JS: `var` を使わず `const` / `let` を使用。
- 新規 UseCase: 可能な限り `handle(○○Input $input)` の形で作成。
- 定数: マジックナンバー・文字列は `private const` または Enum で管理。
