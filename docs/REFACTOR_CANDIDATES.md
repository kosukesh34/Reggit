# リファクタ候補一覧

調査日: 2026-09-10（`main` @ `e32c525`、PR #228 マージ後）。  
目的: 実装せず、**影響 × 工数**で優先した具体候補を残す。大規模リファクタはこの文書の対象外。

参照した規約: [CODING_STANDARDS.md](../CODING_STANDARDS.md)、[AGENTS.md](../AGENTS.md)、[CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md)、[DDD.md](./DDD.md)、[ARCHITECTURE.md](./ARCHITECTURE.md)。  
`docs/WHY.md` はリポジトリに無いため未使用。

## 優先度の定義

| 優先度 | 意味 |
|--------|------|
| **P0** | 放置すると不具合や二重実装が複利で増える。次の機能追加の前に方針を決める |
| **P1** | 機能追加のたびに触る巨大ファイル。分割すればレビューとテストが楽になる |
| **P2** | 価値はあるが、触る画面・API と一緒でよい |
| **クイックウィン** | 1 PR・低リスク。先に片付けると以降の調査ノイズが減る |

工数は「ファイル数とテスト境界」の目安。カレンダー日数は見積もらない。

## サマリー

| ID | 優先度 | 候補 | 主な場所 |
|----|--------|------|----------|
| P0-1 | P0 | LIFF の React と legacy Manager 二重実装 | `resources/js/frontend/managers/`, `resources/js/liff-ui/` |
| P0-2 | P0 | 会員ポイントの Domain / Eloquent 二重書き込み | `app/Domain/Member/`, `app/Services/MemberService.php` |
| P1-1 | P1 | `MemberAdminService` / `MemberService` の巨大化 | `app/Services/Admin/MemberAdminService.php` |
| P1-2 | P1 | `ShopSettingStore` 神クラスと薄い Repository | `app/Infrastructure/Persistence/Eloquent/Settings/` |
| P1-3 | P1 | 本番オーダーが `OrderDemo`、showcase が本番バンドルに同梱 | `resources/js/liff-ui/pages/FeaturePage.tsx` |
| P1-4 | P1 | LINE Webhook / 順番待ち QR が通知インフラを抱える | `app/UseCases/Line/`, `app/UseCases/Queue/` |
| P1-5 | P1 | env テンプレートの分裂 | `.env.example`, `env.example`, `env.testing.example` |
| P1-6 | P1 | 管理画面配信・クーポンの Blade + 巨大 vanilla TS | `resources/js/admin/announcementDelivery.ts` |
| P2-1 | P2 | Repository が 3 系統 | `app/Repositories/`, `app/Domain/*/Repositories/` |
| P2-2 | P2 | OneSignal 撤去後の残骸 | `app/Models/Announcement.php`, `mobile/reggit-skip/` |
| P2-3 | P2 | UseCases 配下の Service と FCM の `new` | `app/UseCases/Queue/QueueStatsService.php` |
| P2-4 | P2 | Feature テストの巨大化と `project_id => 1` | `tests/Feature/QueueApiTest.php` |

推奨する着手順: **クイックウィン → P0-1 の方針決定 → P0-2 → P1-3（オーダー画面の名前と入口）→ 触ったドメインの P1 巨大クラス**。

---

## クイックウィン

いずれも本番挙動を変えずに済む。まとめて 1 PR でも、項目ごとに分けてもよい。

### Q1. 店舗ログインの死んだ Controller / View

- **なぜ**: PR #224 で店舗切り替えを直したあと、ルートから外れた実装が残っている。
- **場所**: `app/Http/Controllers/Admin/Auth/StoreLoginController.php`（ルート未登録）、`resources/views/admin/project-login-select.blade.php`（`view()` 参照なし）。現行ログインは `routes/web.php` の `admin.project.login` → `AdminLoginController`。
- **やり方**:
  - ルート・テストに参照が無いことを再確認して削除する。
  - `tests/Feature/AdminStoreSwitcherTest.php` が旧 UI 非表示をまだ守っているなら、その回帰だけ残す。
- **リスク**: 低（死コード削除）。
- **完了条件**: 上記 2 ファイルが消え、`php artisan route:list` に `StoreLoginController` が無く、店舗ログイン Feature テストが通る。

### Q2. 未使用の `AdminCouponCreateFormUseCase`

- **なぜ**: 定義と自己参照以外に呼び出しが無い。クーポン新規は別 UseCase / Blade 経路。
- **場所**: `app/UseCases/Admin/Coupon/AdminCouponCreateFormUseCase.php`
- **やり方**: 参照ゼロを grep して削除。`AdminCouponEditFormUseCase` は残す。
- **リスク**: 低。
- **完了条件**: クラスファイルが消え、クーポン作成画面の Feature テストが通る。

### Q3. スマレジ実接続テストがダミー資格情報でスキップされない

- **なぜ**: `tests/Feature/SmaregiApiIntegrationTest.php` は `SMAREGI_CLIENT_ID` / `SECRET` が空のときだけ skip する。`env.testing.example` は `test_client_id` / `test_client_secret` を入れるため、ローカルで `.env.testing` をコピーすると空判定が効かない。[AGENTS.md](../AGENTS.md) が「3 件失敗は想定内」と書いている状態。
- **場所**: `env.testing.example`、`tests/Feature/SmaregiApiIntegrationTest.php`、`phpunit.xml`、`config/smaregi.php`（未設定時の `contract_id` デフォルト `sb_skx928r2`）。
- **やり方**:
  - `env.testing.example` のダミー資格情報を空にする。または skip 判定を「実在しそうな値」ではなく明示フラグ（例: `SMAREGI_INTEGRATION=1`）にする。
  - 既定の `php artisan test` から `@group smaregi-integration` を外す（`phpunit.xml` の exclude、または CI だけ `--group`）。
- **リスク**: 低。実接続を走らせたい開発者向け手順を README / SMAREGI_SETUP に 1 行足す。
- **完了条件**: 資格情報なしで `php artisan test --exclude-group smaregi-integration` 相当が緑。実接続は明示実行のときだけ走る。

### Q4. 未使用の QR ライブラリ 2 つ

- **なぜ**: 本番コードは `qrcode` だけを import している。`qrcodejs` / `qrcodejs2` は `package.json` に残るだけ。
- **場所**: `package.json`（`qrcodejs`, `qrcodejs2`）。利用側: `resources/js/shared/utils/qrCodeCompat.ts`、`resources/js/admin/stampGrantQr.ts`、`resources/js/liff-ui/components/membership/MembershipQr.tsx`。
- **やり方**: 未使用 2 パッケージを外して `npm install`。
- **リスク**: 低。古い Blade の CDN 参照が残っていないことだけ確認する。
- **完了条件**: `package.json` から消え、`npm run build` が通る。

### Q5. アーキテクチャ文書がコードと食い違う

- **なぜ**: 層の話をするたびに誤った前提でレビューしてしまう。
- **場所**:
  - [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) §6.2 は多数の `handle(Request …)` UseCase を「Input 化候補」としているが、`app/UseCases` に `handle(Request` は **0 件**（`OrderStoreUseCase` は既に `OrderStoreInput`）。
  - [DDD.md](./DDD.md) §6 は「legacy managers / `app.ts` 削除（W2 完了）」と書くが、`resources/js/frontend/managers/MemberManager.ts`（3471 行）と `resources/js/frontend/app.ts`（2040 行）は現役。
- **やり方**: §6.2 を「Input 化済み」に更新する。DDD W2 は未完了に戻し、P0-1 を残作業にする。
- **リスク**: なし（文書のみ）。
- **完了条件**: 上記 2 箇所がリポジトリの実ファイルと一致する。

---

## P0

### P0-1. LIFF の React 画面と legacy Manager が并存している

- **なぜ大事か**: 顧客向け LIFF の中核。会員・注文・クーポンの修正が「React 側だけ直したつもり」で Manager 側に残る。`docs/DDD.md` は W2 完了と書いており、実態と文書が逆向き。
- **場所**:
  - 起動: `resources/js/frontend/main.tsx` が `./app`（legacy）を side-effect import したうえで `liff-ui` の `HashRouter` を載せる。Vite 入力は `resources/js/frontend/main.tsx` と `resources/js/admin/main.tsx` のみ（`vite.config.js`）。
  - 巨大 Manager: `MemberManager.ts`（3471）、`app.ts`（2040）、`CouponsManager.ts`（1251）、`ReservationManager.ts`（1132）。
  - React 側も Manager を呼ぶ: `liff-ui/pages/MembershipPage.tsx`、`ProfilePage.tsx`、`frontend/features/member/api.ts` の `getMemberManager()`。
  - ルーティング橋: `liff-ui/navigation/tabPath.ts`、`useLegacyTabBridge.ts`。未使用エントリ `liff-ui/main.tsx`（`BrowserRouter`、Vite 入力に無い）。
- **やり方**:
  - タブ単位で「描画の正」を 1 つに決める。会員証は `MembershipPage`、履歴系は既に live で `*View` に寄せている（`FeaturePage.tsx` の coupon/stamp/queue/reservation/gallery/orderHistory）。
  - Manager から HTML 文字列生成（`innerHTML` / `onclick="app.switchTab"`）を削り、API・Wallet・LIFF identity だけ残す。
  - `switchTab` を HashRouter の `navigate` に一本化するまで、新規タブを Manager に足さない。
- **リスク**: 高。会員登録・Wallet・ディープリンク・ネイティブ WebView が Manager に残っている。画面契約テスト `tests/Unit/LiffBootScreenContractTest.php` がソース文字列に依存しているので、移動と同時に更新する。
- **完了条件**: 本番タブの描画が `window.app.*Manager` の HTML に依存しない。`MemberManager.ts` がネットワーク／Wallet に縮小されているか削除されている。`DDD.md` の W2 記述が実態と一致する。

### P0-2. 会員ポイントが Domain 集約と Eloquent の二系統で動く

- **なぜ大事か**: ポイントは決済・スマレジ inbound・管理画面調整が交わる。片系だけ直すと台帳と表示がずれる。
- **場所**:
  - Domain（加算／減算の正）: `app/Domain/Member/Aggregates/Member.php` の `earnPoints` / `usePoints`。利用例: `app/Services/OrderService.php`、`app/UseCases/Admin/Member/AdminMemberUpdateUseCase.php`、`app/Services/Smaregi/SmaregiCustomerPointsInboundSyncService.php`、`SmaregiInboundAwardApplier.php`。
  - Eloquent レガシー: `app/Models/Member.php` の `syncPointsLedger()`。`app/Services/MemberService.php`（LIFF の `getMemberData` など）と `app/Services/Admin/MemberAdminService.php` は Domain 集約を使わず Eloquent の `syncPointsLedger` を呼ぶ。
- **やり方**:
  - ポイントの更新はすべて `Domain\Member\Repositories\MemberRepository` 経由にする。Eloquent `Member` は読みモデル／永続化に限定する。
  - `MemberService::getMemberData` の inbound 同期と台帳バックフィルを、既存の Smaregi inbound Service + Domain リポジトリに寄せる（UseCase から UseCase は呼ばない）。
- **リスク**: 高。表示ポイント・有効期限・スマレジ差分の回帰が必要。マルチテナントの `project_id` スコープテストは必須。
- **完了条件**: `app/Services/MemberService.php` と `MemberAdminService.php` から Eloquent のポイント更新が消え、注文確定・管理画面加減算・スマレジ inbound の既存テストが通る。新規のポイント変更が Domain 集約だけを通る。

---

## P1

### P1-1. `MemberAdminService` と `MemberService` が複数ドメインを抱えている

- **なぜ大事か**: 会員詳細・プロフィール画像（PR #227）・クーポン付与・スタンプ・来店・スマレジ顧客更新が 1 クラスに入っている。PR の差分が常に巨大になる。
- **場所**: `app/Services/Admin/MemberAdminService.php`（1109 行）、`app/Services/MemberService.php`（749 行）。プロフィール画像は `MemberProfileImageUseCase` → `MemberService` の `Storage` 操作。管理画面 Controller は比較的薄い（`Admin/MemberController.php` 326 行）が、一覧 View 合成と CSV ストリームは Controller 側。
- **やり方**:
  - 書き込みを関心事で分ける（プロフィール画像、クーポン付与、スタンプ調整、来店）。既存の `*AdminService` パターン（Product / Coupon）に合わせる。
  - ポイントは P0-2 に任せる。ここでは読み取り一覧と CSV を `AdminMemberListUseCase` / `AdminMemberExportUseCase` に閉じる（export UseCase は既にある）。
- **リスク**: 中。管理会員・LIFF プロフィールの Feature テストが厚い（`tests/Feature/AdminMembersTest.php` 737 行）。
- **完了条件**: 新規の会員変更が 200 行超の Service メソッド追加にならない。プロフィール画像とポイント以外の CRUD が名前で追える。

### P1-2. `ShopSettingStore` が設定の単一巨大実装になっている

- **なぜ大事か**: 店舗設定はほぼ全機能が読む。1182 行の static ストアに、577 行の `EloquentShopSettingRepository` がほぼ 1 対 1 で委譲している。クラスコメントは「外部からの直接参照禁止」だが、テストは `ShopSettingStore::setValue` を直接叩く。
- **場所**: `app/Infrastructure/Persistence/Eloquent/Settings/ShopSettingStore.php`、`EloquentShopSettingRepository.php`、`app/Domain/Settings/Repositories/ShopSettingRepository.php`。キーマップが `ProjectBusinessSetting` 等のテーブル分割と密結合。
- **やり方**:
  - 新規キーは Repository インターフェースにメソッドを足し、Store の public static を増やさない。
  - テストは Repository（または既存の Settings UseCase）経由に寄せる。Store の分割は「営業時間 / LIFF / 会員ランク」などテーブル境界でよい。
- **リスク**: 中〜高。設定キー名の typo とデフォルト値のデグレが出やすい。一括のクラス分割より、触ったキー群だけ移す。
- **完了条件**: 新規設定の読み書きが `ShopSettingRepository` 経由。Store の public API が増えていない。既存 Settings Feature テストが通る。

### P1-3. 本番モバイルオーダーが `OrderDemo` のまま / showcase が本番バンドルに載る

- **なぜ大事か**: 名前が Demo でも、live 時は `RuntimeDataContext.createOrder` と Stripe `checkoutManager` を呼ぶ本番 UI。一方 `ProductsPage` はルーターから参照されず、契約テストが **OrderDemo を使うこと** を固定している。showcase 用 config（`additionalStores` / `industryGallery` など約 1900 行）が顧客 LIFF と同じエントリに入る。
- **場所**:
  - `resources/js/liff-ui/pages/FeaturePage.tsx`（live 分岐は coupon/stamp/queue/reservation/gallery/orderHistory のみ。`mobileOrder` / `dineInOrder` は常に `OrderDemo`）。
  - `resources/js/liff-ui/features/demo/OrderDemo.tsx`（425 行、live catalog 対応済み）。
  - `resources/js/frontend/features/products/ProductsPage.tsx`（孤立）。
  - `tests/Unit/LiffBootScreenContractTest.php` の `test_店舗オーダーはプロダクトページではなくオーダーデモを使うこと`。
  - `resources/js/liff-ui/config/{additionalStores,industryGallery,customization,projectStore}.ts`。
- **やり方**:
  - 短期: `OrderDemo` を本番コンポーネント名に改名し、デモ専用データ（`orderProfiles` の izakaya/fastfood）を live から外す。
  - 中期: showcase / industry gallery を動的 import または別 Vite エントリにする。
  - `ProductsPage` を使うなら契約テストを書き換え、使わないなら削除する。
- **リスク**: 中。カート・オプション・卓番・Stripe の回帰が必要。改名だけなら低。
- **完了条件**: live の注文画面にデモ用固定メニューが混ざらない。未使用の `ProductsPage` が使われるか消えている。顧客 LIFF の初期 JS に industry gallery カタログが含まれない（または明示的に code-split）。

### P1-4. LINE Webhook と順番待ち QR が通知・ユーザー作成まで UseCase に持つ

- **なぜ大事か**: UseCase はオーケストレーション、通知文面と HTTP は Service、が規約。ここが太いと LINE/FCM 変更が注文・順番待ちのドメインテストを巻き込む。
- **場所**:
  - `app/UseCases/Line/LineWebhookHandleUseCase.php`（329 行）: 署名、プロジェクト解決、イベント分岐、Flex 返信。
  - `app/UseCases/Queue/QueueQrStatusUseCase.php`（317 行）: QR 紐付け、User 作成、LINE Flex、FCM。
  - 隣接: `app/Services/PushNotificationService.php` が `new FcmHttpV1Client`（P2-3）。
- **やり方**:
  - Webhook は「イベント種別 → ドメイン処理」を UseCase、返信ビルドを `LineService`（または reply builder）へ。
  - QR 紐付けと「リンク通知」を分ける。通知は既存 `PushNotificationService` / `LineService` に寄せる。
- **リスク**: 中。署名検証とマルチテナントの channel secret 取り違えが致命。既存 `tests/Unit/UseCases/Line/LineWebhookHandleUseCaseTest.php`、`QueueQrStatusNotifyTest.php` を入口にする。
- **完了条件**: UseCase から Flex JSON 組み立てと FCM 送信の詳細が消え、同じ Feature/Unit が通る。

### P1-5. env テンプレートが 3 系統ある

- **なぜ大事か**: README は `.env.example`、CI（`.github/workflows/ci.yml`）は `env.example` を優先コピー。キーが片方にしか無い（`.env.example` に SMAREGI、`env.example` に `DEMO_*` / `NATIVE_APP_*` / `LINE_CHANNEL_ID`）。`docs/ENV_PRODUCTION_TEMPLATE.md` が 317 行でさらに別集合。
- **場所**: `.env.example`（111 行）、`env.example`（90 行）、`env.testing.example`（47 行）、`docs/ENV_PRODUCTION_TEMPLATE.md`、`config/*.php` の `env()`。
- **やり方**:
  - 正本を 1 つにする（README の `.env.example` か、CI が使う `env.example`）。もう一方は薄いラッパーか削除。
  - `BROADCAST_CONNECTION` と `BROADCAST_DRIVER` の二重をやめる。
  - Q3 と同時に testing 用ダミー資格情報を空にする。
- **リスク**: 中。Docker / Cloud Agent / CI のコピー手順が壊れる。`AGENTS.md` と README のコマンドを同時更新する。
- **完了条件**: 新規クローンがファイル 1 つで起動手順を満たす。CI と README が同じファイル名を指す。未使用キーがドキュメントに「config のみ」と書いてある。

### P1-6. 管理画面の配信・クーポンが Blade + 1100 行の vanilla TS

- **なぜ大事か**: 店舗管理で事故りやすい画面（配信対象プレビュー、一括発行）。型が無く、クーポン側がお知らせ用 DOM id を流用している。
- **場所**: `resources/js/admin/announcementDelivery.ts`（1103 行）、`resources/js/admin/couponBulkIssue.ts`（628 行）。`app/Http/Controllers/Admin/CouponController.php`（383 行、コンストラクタで Announcement の preview UseCase まで注入）。React 島は LIFF デザイナー（`liff-ui/pages/AdminCustomizePage.tsx`）だけ。
- **やり方**:
  - 全管理画面の React 化はしない（規約どおり最小）。配信プレビュー API の JS をクーポンとお知らせで共有モジュール化し、DOM id を画面ごとに分ける。
  - `CouponController` は CRUD / 一括発行 / スマレジ同期 / 配信プレビューでクラスを分ける（薄 UseCase は残す）。
- **リスク**: 中。プレビュー件数とセグメント条件のデグレ。
- **完了条件**: クーポン一括発行画面が `announcementDeliveryConfig` に依存しない。配信プレビューの変更がお知らせとクーポンで二重にならない。

---

## P2

### P2-1. 永続化が 3 系統

- **なぜ大事か**: 新規機能で「どこにクエリを置くか」が毎回揺れる。
- **場所**:
  - 旧: `app/Repositories/Contracts` + `Eloquent`（Project, AdminUser, User, UserDevice）。
  - DDD: `app/Domain/*/Repositories` + `app/Infrastructure/Persistence/Eloquent/*`（Member, Order, Coupon, Queue, Reservation, Settings）。
  - バイパス: Admin/Smaregi Service が Eloquent を直接使う（`MemberAdminService` など）。
- **やり方**: 横断読み取り（Project 解決）は旧 Repository のままでよい、と [CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) §8 が既に書いている。新規ドメインは Domain Repository。Service 内の重複クエリだけ切り出す。
- **リスク**: 低〜中（寄せすぎると範囲爆発）。
- **完了条件**: 新規 PR の説明で「どの系統に置いたか」が一言で分かる。第三の系統（Service 内コピペクエリ）が増えない。

### P2-2. OneSignal 撤去後の名前とカラム

- **なぜ大事か**: PR #218 で送信経路は FCM のみ。残っているのは調査時のノイズと、将来マイグレーション漏れの種。
- **場所**: `app/Models/Announcement.php` の `onesignal_notification_ids`（サービスから読み書きなし）、`database/migrations/2026_07_18_190000_add_onesignal_notification_ids_to_announcements_table.php`。テストの token 名 `onesignal-*`（`tests/Feature/CouponNotificationTest.php` 等）。iOS: `mobile/reggit-skip/Darwin/OneSignalNotificationServiceExtension/`（実装は FCM cancel だがターゲット名が OneSignal）。
- **やり方**: カラムは未使用なら drop マイグレーション。テスト文字列を `fcm-` に改名。NSE ターゲット改名は Xcode 影響が大きいのでモバイル改修のときでよい。
- **リスク**: 低（PHP/DB）。iOS 改名は中。
- **完了条件**: アプリ PHP から `onesignal` 文字列が消え（モバイル README の「旧 UUID は無効」注記は残してよい）、Announcement の fillable に旧カラムが無い。

### P2-3. UseCases ディレクトリに Service があり、FCM クライアントを `new` している

- **なぜ大事か**: 層の置き場所と DI 規約（CODING_STANDARDS §18）から外れる。テストで FCM を差し替えにくい。
- **場所**: `app/UseCases/Queue/QueueStatsService.php`、`app/UseCases/Reservation/ReservationAvailabilityService.php`、`app/UseCases/Payment/StripePaymentGateway.php`、`app/UseCases/Member/GoogleWallet/GoogleWalletSaveUrlIssuer.php`。`app/Services/PushNotificationService.php` の `new FcmHttpV1Client`。本番未使用の薄いラッパー `app/UseCases/Coupon/IssueCouponOnRankUpUseCase.php`（本番は `IssueCouponOnRankUpService` を `MemberRankSyncService` が呼ぶ。UseCase は Unit テストからのみ）。
- **やり方**: Service を `app/Services/{Queue,Reservation,Payment}/` へ移す（namespace だけ）。FCM クライアントはコンストラクタ注入。ランクアップクーポンは Service か UseCase のどちらか一方にテストを寄せ、薄い UseCase を消す。
- **リスク**: 低（移動）。FCM の DI は中（送信テストのモック境界）。
- **完了条件**: `app/UseCases` 配下に `*Service` / Gateway が無い。`PushNotificationService` が `FcmHttpV1Client` を `new` しない。

### P2-4. Feature テストが巨大で、`project_id => 1` を決め打ちしている

- **なぜ大事か**: RefreshDatabase 順が変わると落ちる。マルチテナント回帰のつもりが「最初のプロジェクト」依存になる。
- **場所**: `tests/Feature/QueueApiTest.php`（692 行、`project_id => 1` が 65 件）、`AdminAnnouncementsTest.php`（985）、`SmaregiInboundTransactionHistoryTest.php`、`LiffDesignerTest.php`、`AdminMembersTest.php`。他に Order/Coupon/Stamp の API テスト。
- **やり方**: 新規テストは Factory で作った `$project->id` を使う。巨大ファイルは画面／API 単位で分割（既存を無理に割らない。触ったときだけ）。
- **リスク**: 低。分割時にセットアップ重複が増えないようベーストレイトを使う。
- **完了条件**: 新規 Feature テストに `'project_id' => 1` が無い。Queue API のクリティカルケースが他店舗 ID を使う。

---

## 小さめのフォロー（単独 P にはしない）

触った PR に乗せる程度。

- **Wallet 中間ページのポリシーが Controller にある**: `app/Http/Controllers/WalletAddPageController.php`（196 行）の host allowlist / return URL。`ShowWalletAddPageUseCase` へ。
- **画像保存の `resolve()`**: `Admin/ProductController.php`、`Admin/Gallery/ItemController.php`、`Admin/Settings/CouponsController.php` が `resolve(AdminSavePublicImageUseCase::class)`。クーポン本体はコンストラクタ注入済み。メソッド注入に揃える。
- **LP 自動発行（PR #228）**: `app/UseCases/Landing/SubmitLandingRegistrationUseCase.php` が `uniqueProjectKey` / `uniqueLoginId` で Eloquent を直接見ている。Provisioning 側へ寄せるとテストしやすい。
- **命名**: UseCase Data は `*View` / `*Output` が混在（例: `AdminCouponFormOutput` vs `AccountFormView`）。Controller 変数名 `$dto` が残る。新規は View/Output。一括リネームはしない。
- **`EnumBase::isValid()`**: 連想配列に `in_array` するため **ラベル** を検査する。呼び出しは 0 件。直すか消す。PHP 8.1 native enum への移行は別案件。
- **LIFF Support の巨大静的カタログ**: `app/Support/LiffAppStudioTemplates.php`（2169）、`LiffSaaSTemplateKit.php`（2049）、`LiffDesignerSanitizer.php`（1000）、`LiffThemePresets.php`（893）。生成物に近い。デザイナーを触るときだけ業種単位で分割する。

---

## 最近の機能との対応

| 最近の変更 | この一覧での扱い |
|------------|------------------|
| PR #228 LP 自動プロビジョニング | 層は UseCase + `ProjectProvisioningService` で妥当。ユニークキー生成だけフォロー項目 |
| PR #227 会員プロフィール画像 | UseCase は薄い。実体は P1-1 の `MemberService` Storage |
| PR #226 LP/LIFF/プロフィール整理 | P0-1 / P1-3 が残り。legacy Manager は削除されていない |
| PR #224 店舗スイッチャー | Q1 の死コード |
| PR #223 デフォルト商品カタログ | リファクタ対象外（データ） |
| PR #222 スマレジ商品画像 | `ProductAdminService` → `SmaregiProductRegisterService` に閉じており健全 |
| PR #218 FCM のみ | P2-2 の残骸。PHP に OneSignal クライアントは無い |

---

## やらないこと（既存方針）

[CLEAN_ARCHITECTURE.md](./CLEAN_ARCHITECTURE.md) §7 と一致させる。

- Admin の **薄い UseCase ラッパーを削除**して Controller → Service 直呼びにはしない。
- 設定 getter 群を 1 つのファサード UseCase にまとめない。
- UseCase から UseCase を呼ばない（共通化は Service）。
- 管理画面 Blade の全面 React 化はしない（P1-6 は共有 JS と id の整理まで）。
- `app/Models` を Domain 集約に一括置換しない（P0-2 はポイント書き込みだけ）。

---

## 調査メモ

- `app/UseCases` に `handle(Request` は 0。Input 化は文書より進んでいる。
- UseCase 同士のコンストラクタ注入は見つからなかった。
- `getXxxAttribute` は 0。`empty()` は PassKit 周辺のみ。
- フロントは `resources/js/{admin,frontend,liff-ui,shared}`。本番 LIFF の Vite 入口は `frontend/main.tsx` のみ。
- コアテストは約 670 件が緑、スマレジ実接続 3 件は資格情報なしで失敗しうる（AGENTS.md どおり）。
