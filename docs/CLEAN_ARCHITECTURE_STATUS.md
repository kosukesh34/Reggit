# クリーンアーキテクチャ採用状況

プロジェクト全体で「Controller → UseCase → Service/Repository」の責務分離がどこまで行われているかをまとめたドキュメントです。

---

## 結論

**大部分の Controller でクリーンアーキテクチャ（Controller → UseCase → Service）が採用されています。**  
API・管理画面（Admin）・Web・認証の各所で UseCase に委譲済み。ルート未参照だった **AdminController** / **LoginController** / **AccountController** は削除済み。

---

## 1. クリーンアーキテクチャを採用している Controller（UseCase 使用）

以下の Controller は UseCase に処理を委譲しており、責務分離ができています。

### API（bind.project / Web）

| Controller | UseCase |
|------------|---------|
| MemberMeController | MemberMeUseCase |
| MemberUpdateController | MemberUpdateUseCase |
| MemberRegisterController | MemberRegisterUseCase |
| MemberPointsController | MemberPointsUseCase |
| MemberPointHistoryController | MemberPointHistoryUseCase |
| MemberPointCardController | MemberPointCardUseCase |
| MemberAddPointsController | MemberAddPointsUseCase |
| GoogleWalletUrlController | GetGoogleWalletUrlUseCase |
| WalletPassController | WalletPassDownloadUseCase |
| WalletPassUrlController | WalletPassUrlIssueUseCase |
| ProductController | ProductIndexUseCase, ProductShowUseCase |
| OrderController | OrderIndexUseCase, OrderShowUseCase, OrderStoreUseCase |
| ReservationIndexController | ReservationIndexUseCase |
| ReservationStoreController | ReservationStoreUseCase |
| ReservationUpdateController | ReservationUpdateUseCase |
| ReservationCancelController | ReservationCancelUseCase |
| ReservationAvailableDatesController | ReservationAvailableDatesUseCase |
| AnnouncementController | AnnouncementIndexUseCase, AnnouncementMarkReadUseCase |
| CouponController (Api/V1) | CouponMyCouponsUseCase |
| StampController | StampIndexUseCase |
| QueueStoreController | QueueStoreUseCase |
| QueueMeController | QueueMeUseCase |
| QueueCancelController | QueueCancelUseCase |
| QueueCallNextController | QueueCallNextUseCase |
| PaymentCreateIntentController | (PaymentCreateIntentUseCase) |
| PaymentConfirmController | PaymentConfirmUseCase |
| PaymentWebhookController | PaymentWebhookUseCase |
| ContactController | ContactStoreUseCase |
| GalleryController | GalleryIndexUseCase |

### API v1（Bearer トークン / project_key 付き）

上記と同様に、Api/V1 配下の各 Controller（MemberMe, MemberPoints, Order, Product, Reservation, Stamp, Announcement, Coupon, Payment, Queue 等）は対応する UseCase を利用しています。

### 管理画面（Admin）のうち UseCase 使用

| Controller | UseCase |
|------------|---------|
| ApiTokenController | ApiToken* UseCase 群 |
| SmaregiController | GetSmaregiSettingsUseCase, GetSmaregiPosDataUseCase, UpdateSmaregiLinkUseCase |
| FeaturesController | GetFeatureSettingsUseCase, UpdateFeatureSettingsUseCase |
| LiffDesignerController | GetLiffDesignerUseCase, UpdateLiffDesignerUseCase |
| Gallery CategoryController / ItemController | GalleryCategory* / GalleryItem* UseCase 群 |
| AccountUpdateController, AccountStoreController, AccountIndexController, AccountFormController, AccountDestroyController | Account* UseCase / RouteContextResolver |
| AuditLogController | AuditLogIndexUseCase, AuditLogShowUseCase |
| RanksController | GetRankSettingsUseCase, UpdateRankSettingsUseCase 等 |
| AnalyticsController | AdminAnalyticsUseCase |
| SystemAdminDashboardController | SystemAdminDashboardUseCase |
| ProjectController | Project* UseCase 群 |
| QueueStatusController, QueueQrStatusController | (Queue 関連 UseCase) |
| AdminLoginController, StoreLoginController, SystemAdminLoginController, LogoutController | GetAdminDashboardRedirectUseCase, AdminLoginUseCase, SystemAdminLoginUseCase, AdminLogoutUseCase |

---

## 2. UseCase を参照していない Controller（未採用）

以下の **37 ファイル** は UseCase を参照しておらず、ロジックが Controller 内や Service 直叩きに残っている可能性があります。

### 管理画面（Admin）

- ~~`Admin/AccountController.php`~~ … 削除済み（Admin/Account/*Controller が使用されている）
- `Admin/AnnouncementController.php` … UseCase 委譲済み（AdminAnnouncement*UseCase 群）
- `Admin/Auth/AdminLoginController.php` … UseCase 委譲済み（GetAdminDashboardRedirectUseCase, AdminLoginUseCase）
- `Admin/Auth/LogoutController.php` … UseCase 委譲済み（AdminLogoutUseCase）
- `Admin/Auth/StoreLoginController.php` … UseCase 委譲済み（GetAdminDashboardRedirectUseCase, AdminLoginUseCase）
- `Admin/Auth/SystemAdminLoginController.php` … UseCase 委譲済み（GetAdminDashboardRedirectUseCase, SystemAdminLoginUseCase）
- `Admin/CouponController.php` … UseCase 委譲済み（AdminCoupon*UseCase 群）
- `Admin/DashboardController.php` … UseCase 委譲済み（AdminDashboardIndexUseCase）
- ~~`Admin/LoginController.php`~~ … 削除済み（Admin/Auth/AdminLoginController 等が使用されている）
- `Admin/MemberController.php` … UseCase 委譲済み（AdminMember*UseCase 群）
- `Admin/OrderController.php` … UseCase 委譲済み（AdminOrder*UseCase 群）
- `Admin/ProductController.php` … UseCase 委譲済み（AdminProduct*UseCase 群）
- `Admin/QueueController.php` … UseCase 委譲済み（AdminQueue*UseCase 群）
- `Admin/ReservationController.php` … UseCase 委譲済み（AdminReservation*UseCase 群）
- `Admin/Settings/AdvancedController.php` … UseCase 委譲済み（GetAdvancedSettingsUseCase, UpdateAdvancedSettingsUseCase）
- `Admin/Settings/AnnouncementsController.php` … UseCase 委譲済み（Get/UpdateAnnouncementsSettingsUseCase）
- `Admin/Settings/BasicController.php` … UseCase 委譲済み（GetBasicSettingsUseCase, UpdateBasicSettingsUseCase, UpdateBasicFeatureFlagsUseCase）
- `Admin/Settings/CouponsController.php` … UseCase 委譲済み（Get/UpdateCouponsSettingsUseCase）
- `Admin/Settings/GalleryController.php` … UseCase 委譲済み（Get/UpdateGallerySettingsUseCase）
- `Admin/Settings/HomeController.php` … UseCase 委譲済み（GetHomeSettingsUseCase, UpdateHomeSettingsUseCase）
- `Admin/Settings/MenuController.php` … UseCase 委譲済み（Get/UpdateMenuSettingsUseCase）
- `Admin/Settings/ProfileController.php` … UseCase 委譲済み（Get/UpdateProfileSettingsUseCase）
- `Admin/Settings/QueueController.php` … UseCase 委譲済み（Get/UpdateQueueSettingsUseCase）
- `Admin/Settings/ReservationsController.php` … UseCase 委譲済み（GetReservationOnlySettingsUseCase, UpdateReservationOnlySettingsUseCase）
- `Admin/Settings/WalletController.php` … UseCase 委譲済み（Get/UpdateWalletSettingsUseCase, WalletAssetService）
- `Admin/StampController.php` … UseCase 委譲済み（AdminStamp*UseCase 群）
- ~~`AdminController.php`~~ … 削除済み（DashboardController 等の専用 Controller が使用されている）

### API（レガシー・UseCase 委譲済み）

- `Api/V1/MemberController.php` … UseCase 委譲済み（MemberMeUseCase 等）
- `Api/V1/ReservationController.php` … UseCase 委譲済み（ReservationIndexUseCase 等）
- `Api/V1/QueueController.php` … UseCase 委譲済み（QueueStoreUseCase, QueueMeUseCase）

### Web / その他

- `CouponController.php`（Web 版 myCoupons） … UseCase 委譲済み（CouponMyCouponsUseCase）
- `Diagnostics/ClientDebugController.php` … UseCase 委譲済み（ClientDebugLogUseCase）
- `LiffController.php` … UseCase 委譲済み（LiffIndexUseCase）
- `PostalCodeController.php` … UseCase 委譲済み（PostalCodeSearchUseCase）
- `QueueController.php`（Web 版） … UseCase 委譲済み（QueueStoreUseCase, QueueMeUseCase）
- `StampController.php`（Web 版） … UseCase 委譲済み（StampIndexUseCase）

---

## 3. 今後の対応案

1. **ルートで使われているか確認**  
   `Api/V1/MemberController.php` や `Api/V1/ReservationController.php` 等がルートで参照されていなければ、削除または「UseCase 版に寄せる」方針を検討する。

2. **管理画面の重要画面から UseCase 化**  
   利用頻度の高い Admin（会員編集・予約・注文・ダッシュボード等）から、Input/Output を定義し UseCase に処理を移す。

3. **既存パターンに合わせる**  
   既に UseCase 化されている Controller（例: `GoogleWalletUrlController` → `GetGoogleWalletUrlUseCase`）と同じ形で、  
   「Controller：入力組み立て・UseCase 呼び出し・レスポンス変換のみ」にそろえる。

---

*最終確認日: 本ドキュメント作成時点のコードベースに基づく*
