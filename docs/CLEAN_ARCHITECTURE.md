# クリーンアーキテクチャ（本プロジェクトの方針）

このドキュメントは、Reggit プロジェクトにおけるクリーンアーキテクチャの層の分け方と、UseCase の設計方針を定義します。

## 1. 層の構成

```
[HTTP]  Controller（入力・レスポンス整形のみ）
    ↓ Input / プリミティブ
[Application]  UseCase（アプリケーションのユースケース＝「何をするか」）
    ↓
[Domain]  Model / Entity（ドメインルール）
[Infrastructure]  Service / Repository / Eloquent（永続化・外部連携）
```

- **Controller**: リクエストの受付・バリデーション・**Input の組み立て**・UseCase 呼び出し・レスポンス返却のみ。ビジネスロジックを書かない。
- **UseCase**: 一つの「ユースケース」（例: 注文を確定する、会員証パスを発行する）を担当。**Request に依存せず**、Input クラスまたはプリミティブを受け取り、Output / View またはドメイン型を返す。
- **Service**: 再利用可能な技術的な処理（例: .pkpass の組み立て、決済 Gateway 呼び出し）。UseCase から利用される。
- **Repository**: 永続化の抽象化。必要に応じて Eloquent の代わりに Repository を挟む（現状は Eloquent を直接利用している箇所も可）。

## 2. UseCase のルール

### 2.1 インターフェース

- メソッド名は原則 **`handle`**。
- 引数: **Input** クラスまたは必要な **プリミティブ / ドメイン型** のみ。`Illuminate\Http\Request` を渡さない。
- 戻り値: **Output** / **View** またはドメインの Model / 値。画面用は `*View`、API/処理結果は `*Output` など役割で命名する。

```php
// 良い例: Input で受け取る
public function handle(OrderStoreInput $input): OrderOutput

// 良い例: プリミティブで十分な場合
public function handle(int $projectId): LiffDesignerView

// 避ける: Request をそのまま渡す（Controller で Input に詰めてから渡す）
public function handle(Request $request, array $validated): OrderOutput
```

### 2.2 Input

- 新規 UseCase では、必要なパラメータを **Input** クラスにまとめる。
- バリデーションは **Controller / FormRequest** で行い、Input のコンストラクタでは型・最小限の不変条件のみ。Laravel の `ValidationException` は Controller 側で投げる。
- 既存で `Request` を引数にしている UseCase は、リファクタ時に Input 化を検討する。

### 2.3 Output / View（出力）

- 戻り値を複数返したい場合や、API/View 用の形を揃えたい場合は **Output**（処理結果）や **View**（画面用データ）クラスを使う。
- 例: `PaymentConfirmOutput`、`WalletPassDownloadOutput`、`LiffDesignerView`、`AccountFormView`。

### 2.4 依存の向き

- UseCase は **Service / Repository / Model** に依存してよい。**Controller / Request** に依存しない。
- Service は UseCase に依存しない。UseCase → Service の一方向。
- **UseCase から UseCase を呼ばない**。複数ユースケースにまたがる処理は Service に寄せる（例: ポイント同期は `SmaregiApiService` 等）。
- Controller / UseCase でビジネス依存を **`new` しない**。Laravel コンテナのコンストラクタ注入またはメソッド注入を使う（Input/DTO の `new` は可）。詳細は [CODING_STANDARDS.md](../CODING_STANDARDS.md) §18。

## 3. Controller のルール

- 処理の流れ: **validate → Input 組み立て → UseCase::handle(Input) → レスポンス整形**。
- 例外処理は Controller で行い、UseCase はビジネス上の例外（例: 権限なし）を投げるだけにする。
- 既存コードで Controller にロジックが残っている場合は、段階的に UseCase に移す。
- 1 アクションだけの UseCase は **メソッド注入** でもよい。コンストラクタに UseCase を大量に並べて肥大化させない。
- 参照実装は **system-admin / Admin 配下** の Controller を優先する。

## 4. ディレクトリ構成（目安）

```
app/
  Http/Controllers/     # 入力・出力のみ
  UseCases/
    Order/
      OrderStoreUseCase.php
      Data/                    # 入出力用クラス（DTO の代わりに役割で命名）
        OrderStoreInput.php    # 入力
        OrderOutput.php        # 出力（処理結果）
    Member/
      WalletPass/
        WalletPassDownloadUseCase.php
        Data/
          WalletPassDownloadInput.php
          WalletPassDownloadOutput.php
  Services/             # 技術的な処理（署名、外部API など）
  Models/                # Eloquent / ドメイン
```

**命名**: 「DTO」ではなく、役割が分かる名前にする。  
- **Input** … UseCase への入力  
- **Output** … 処理の結果（成功/失敗とデータ）  
- **View** … 画面表示用のデータ  
- **Data** フォルダ … 上記の入出力クラスを置く場所（旧 Dto フォルダ）

## 5. 既存コードとの整合

- 既に `handle(Request $request, ...)` になっている UseCase は、**新規・変更時**に Input 化を検討する。
- 一つのファイルが長くなったら UseCase に分割する。Controller に直接処理を書かず UseCase に実装する（CODING_STANDARDS と同一方針）。

---

## 6. 現状の整理（ファイル構成・Input/Output 使用状況）

### 6.1 ファイルの機能別の分け方

| 層 | 構成 | 機能で分かれているか |
|----|------|----------------------|
| **UseCases/** | `Order/`, `Member/`, `Reservation/`, `Queue/`, `Payment/`, `Product/`, `Gallery/`, `Coupon/`, `Contact/`, `Announcement/`, `Stamp/`, `Line/`, `Admin/Account/`, `Admin/Settings/`, `Admin/Project/`, `Admin/Gallery/`, `Admin/ApiToken/`, `Admin/AuditLog/`, `Admin/Analytics/`, `Member/WalletPass/` など | **○ 機能（ドメイン）ごとにディレクトリ分けされている**。各機能配下に UseCase と `Data/` がある。 |
| **Http/Controllers/** | `Admin/`, `Api/V1/`, `Member/`, `Reservation/`, `Queue/`, `Payment/` など | **△ エリア・種別で分かれている**。Admin はさらに Account/, Settings/, Gallery/ などで分割。API は V1 配下にリソース別。 |
| **Models/** | フラット（Order, Member, Product など単一ディレクトリ） | **△ 機能名でクラスが分かれているがフォルダ分けはなし**。 |
| **Services/** | `Admin/` 配下に AdminAuthService, ReservationAdminService など。その他はフラット（OrderService, MemberService, WalletPassService など） | **△ 一部は Admin などでサブディレクトリ、多くはフラット**。 |

**まとめ**: UseCase 層は**機能（ドメイン）単位でディレクトリ分け**されている。Controller は「Web/API/Admin」などの入り口とリソースで分かれており、UseCase ほど細かい機能単位ではないが、Admin 配下は Account, Settings などで分割されている。

### 6.2 クリーンアーキテクチャ：UseCase の Input / Output 使用状況

#### Input を**使用している** UseCase（Request に依存しない）

| UseCase | Input | Output |
|---------|--------|--------|
| `AccountStoreUseCase` | `AccountStoreInput` | `AdminUser` |
| `AccountUpdateUseCase` | `AccountUpdateInput` | `AdminUser` |
| `WalletPassDownloadUseCase` | `WalletPassDownloadInput` | `WalletPassDownloadOutput` |
| `SaveLiffScreenUseCase` | `SaveLiffScreenInput` | `SaveLiffScreenOutput` |
| `UpdateLiffDesignerUseCase` | `UpdateLiffDesignerInput` | `void` |

#### 引数が**プリミティブ or ドメイン型のみ**の UseCase（Request 非依存で OK）

例: `PaymentConfirmUseCase`(paymentIntentId, orderId), `GetLiffDesignerUseCase`(projectId), `ProductIndexUseCase`(category, availableOnly, includeCategories), `GalleryIndexUseCase`(categoryId), `QueueCancelUseCase`(id), `WalletPassUrlIssueUseCase`(userId, projectId, projectKey), 多くの Admin 系（projectId, id, array payload など）。

#### **Request を直接受けている** UseCase（リファクタ候補：Input 化）

| UseCase | 現在の handle 引数 | 推奨 |
|---------|---------------------|------|
| `OrderStoreUseCase` | `Request $request, array $validated` | `OrderStoreInput` を新設し Controller で組み立て |
| `OrderShowUseCase` | `Request $request, int $id` | `int $id` のみでよい（Request は不要） |
| `OrderIndexUseCase` | `Request $request, int $perPage` | `OrderIndexInput` または `int $perPage` + projectId など |
| `ReservationIndexUseCase` | `Request $request` | `ReservationIndexInput`（perPage, フィルタなど） |
| `ReservationStoreUseCase` | `Request $request, array $validated` | `ReservationStoreInput` |
| `ReservationUpdateUseCase` | `Request $request, int $id, array $validated` | `ReservationUpdateInput` |
| `ReservationCancelUseCase` | `Request $request, int $reservationId` | `int $reservationId` + 必要なら User など |
| `QueueStoreUseCase` | `Request $request, int $partySize, ?string $guestName, ?string $guestPhone` | `QueueStoreInput` |
| `QueueQrStatusUseCase` | `Request $request, string $token` | `string $token` + 必要なら lineUserId など |
| `QueueMeUseCase` | `Request $request` | `QueueMeInput`（user や project 情報を Controller で解決して渡す） |
| `MemberMeUseCase` | `Request $request` | 同上 |
| `MemberRegisterUseCase` | `Request $request, array $validated` | `MemberRegisterInput` |
| `MemberUpdateUseCase` | `Request $request, array $validated` | `MemberUpdateInput` |
| `MemberPointsUseCase` | `Request $request` | Input またはプリミティブ |
| `MemberPointHistoryUseCase` | `Request $request, int $perPage` | 同上 |
| `MemberPointCardUseCase` | `Request $request` | 同上 |
| `MemberAddPointsUseCase` | `Request $request, int $points, ?string $description` | 同上 |
| `StampIndexUseCase` | `Request $request` | Input またはプリミティブ |
| `CouponMyCouponsUseCase` | `Request $request` | 同上 |
| `LineWebhookHandleUseCase` | `Request $request` | `LineWebhookInput`（body, signature など） |

#### Output（戻り値）

- **出力（Output / View）を使用している例**: `WalletPassDownloadOutput`, `PaymentConfirmOutput`, `OrderOutput`, `MemberData`, `QueueEntry`, `ReservationActionOutput`, `LiffDesignerView`, `RankSettingsView` など。多くの UseCase が **Output/View または Model** を返している。
- 一部は `Order`, `Product`, `AdminUser` など **Model をそのまま返している**（許容）。

### 6.3 今後の進め方

- **新規 UseCase**: 必ず **Input**（または明確なプリミティブ）で受け取り、**Request を渡さない**。
- **既存の `handle(Request $request, ...)`**: リファクタや機能変更のタイミングで、上表を参考に **Input 化**を検討する。
- **UpdateLiffDesignerUseCase**: `UpdateLiffDesignerInput` を UseCase の引数で受け取り、Controller で Input を組み立てて渡す形に変更済み。
