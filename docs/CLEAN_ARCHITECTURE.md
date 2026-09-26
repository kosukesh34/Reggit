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

- UseCase は `Request` を直接受け取らない。HTTP の値は Controller / `Http/Support` の Factory で Input またはプリミティブへ変換する。
- Service が所有する操作専用の Input / Output は `Services/<Context>/Data` に置く。Service から `UseCases` への逆依存を作らない。
- 一つのファイルが長くなったら UseCase に分割する。Controller に直接処理を書かず UseCase に実装する（CODING_STANDARDS と同一方針）。
- 層間依存の最低条件は `tests/Unit/Architecture/LayerDependencyTest.php` で継続的に検証する。

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

- UseCase から `Illuminate\Http\Request` への直接依存は解消済み。HTTP コンテキストは Controller / Factory で変換する。
- 複数値を受け取る処理は `*Input`、画面表示用は `*View`、処理結果は `*Output` を使用する。単純な識別子や絞り込み値はプリミティブ引数も許容する。
- Service が実処理を所有する Admin 認証とスマレジ通知のデータ型は、それぞれ `Services/Admin/Data`、`Services/Smaregi/Data` に置く。
- `UploadedFile` や HTTP Response などのフレームワーク型が一部の UseCase に残るため、ファイル／レスポンスのポート抽象化は今後の段階的な改善対象とする。
- 一部の UseCase は Eloquent Model を返す。現行方針では許容するが、外部 API や複数画面で契約を共有する場合は Output 型を優先する。

### 6.3 今後の進め方

- **新規 UseCase**: 必ず **Input**（または明確なプリミティブ）で受け取り、**Request を渡さない**。
- **HTTP 型の残存**: `UploadedFile` / `Response` を境界インターフェースへ置き換え、Application 層の Laravel 依存をさらに減らす。
- **巨大クラス**: 設定ストア、会員管理、LIFF 管理クラスを、変更理由ごとの小さな協調オブジェクトへ段階的に分割する。
- **フロントエンド**: TypeScript の `strict` 有効化と巨大 Manager の feature 単位分割を、小さな範囲から進める。

## 7. Admin 薄ラッパー UseCase の方針

Admin 配下には `handle()` が Service や Model に 1 行委譲するだけの UseCase が多い。これは **意図的な設計** であり、削除して Controller から Service を直叩きしない。

### 7.1 三つのパターン

| パターン | 例 | ロジックの置き場 | いつ使うか |
|---------|-----|----------------|-----------|
| **A. UseCase にアプリケーションロジック** | `AccountStoreUseCase`, `AccountIndexUseCase` | UseCase 内（Model 操作・権限・sync） | 横断的な管理画面操作で Service 化していない領域 |
| **B. UseCase → *AdminService** | `AdminProductStoreUseCase`, `AdminOrderCompleteUseCase` | `ProductAdminService` 等 | 管理画面 CRUD・状態変更を Service に集約済みのドメイン |
| **C. UseCase → SettingsService 等** | `GetMenuSettingsUseCase`, `UpdateMenuSettingsUseCase` | `SettingsService` | 店舗設定の getter/updater |

**参照実装**

- パターン A: `app/UseCases/Admin/Account/`
- パターン B: `app/UseCases/Admin/Product/`, `Order/`, `Coupon/`
- パターン C: `app/UseCases/Admin/Settings/`（`SettingsService` 委譲分）

### 7.2 薄ラッパーを維持する理由

1. **レイヤー規約の一貫性** — `Controller → UseCase → Service` を崩さない
2. **テスト境界** — Feature テストは Controller 経由、Unit テストは UseCase / Service を差し替え可能
3. **可読性** — 1 エンドポイント = 1 UseCase で意図が名前から分かる

### 7.3 やらないこと

- 薄ラッパーを削除して **Controller → Service 直呼び** にする
- 複数エンドポイントを無理に **1 つのファサード UseCase** にまとめる（Settings getter 群の統合など）
- **UseCase から UseCase を呼ぶ**（複数操作は Service に寄せる）

### 7.4 Settings 系の追加ルール

- `ShopSetting` への読み書きは **`SettingsService` 経由** に揃える（薄い getter/updater は Service に委譲）
- **View 組み立て**や **複数データソースの結合**は UseCase に残す（例: `GetBasicSettingsUseCase` が `SettingsService` + `ShopSetting::getFeatureFlags` を結合）
- **入力の正規化・バリデーション後の変換**は UseCase に残す（例: `UpdateProfileSettingsUseCase`）

### 7.5 新規 Admin 機能を追加するとき

1. 既存ドメインに `*AdminService` があれば **パターン B**
2. 店舗設定の変更なら **パターン C**（`SettingsService` にメソッド追加 → 薄い UseCase）
3. 上記に当てはまらず横断的な管理操作なら **パターン A**（Account 系を参考に UseCase 内で完結）

## 8. Repository 層の方針

永続化クエリを Eloquent から切り出すため、段階的に Repository を導入する。

### 8.1 ディレクトリ構成

```
app/Repositories/
  Contracts/          # インターフェース（例: ProjectRepository）
  Eloquent/           # Eloquent 実装（例: EloquentProjectRepository）
```

`AppServiceProvider::register()` で Contract → Eloquent 実装を `bind` する。

### 8.2 いつ Repository を挟むか

| 導入する | 導入しない（当面） |
|---------|-------------------|
| 複数 Service / UseCase / Middleware から同じクエリが重複する | 1 箇所だけの単発クエリ |
| テストで永続化を差し替えたい | Account 系のように UseCase 内 Model 操作が明確な領域 |
| プロジェクト解決・ユーザー解決など横断的な読み取り | View Composer / Seeder / Artisan Command（インフラ寄り） |

### 8.3 依存の向き

```
UseCase → Service → Repository → Eloquent Model
UseCase → Repository → Eloquent Model   （Service が不要な読み取りのみの場合も可）
```

- Repository は **UseCase / Service** から利用する。Controller から直接呼ばない。
- Repository は UseCase に依存しない。
- 戻り値は原則 **Eloquent Model**（本プロジェクトはフル DDD の Entity 分離は未導入）。

### 8.4 導入済み

| Repository | 主な利用元 |
|------------|-----------|
| `ProjectRepository` | `AppConfigService`, `AdminAuthService`, `BindProjectFromRequest`, `BindAdminProject`, `SetAdminProjectFromQuery` |
| `AdminUserRepository` | `AdminAuthService` |
| `UserDeviceRepository` | `DeviceService`, `GetDeviceSessionUseCase`, `UserService` |
| `UserRepository` | `UserService`, `GetDeviceSessionUseCase` |

### 8.5 今後の候補

- `ShopSetting` の静的 API が散在しているため、設定読み書き用 Repository（または SettingsService への集約継続）を検討
- `*AdminService` 内の重複クエリから順次切り出し

## 9. DDD 移行（段階導入）

クリーンアーキテクチャ完了後、**Bounded Context 単位で Domain 層を導入**する。詳細は [DDD.md](./DDD.md) を参照。

### 9.1 層の追加

```
app/Domain/              # 集約・値オブジェクト・Repository インターフェース
app/Infrastructure/      # Eloquent 永続化・外部連携の実装
```

既存 `app/Repositories/` は横断的な読み取り用として当面併存する。

### 9.2 現状

主要 Bounded Context（Member / Order / Coupon / Reservation / Queue / Settings 読み取り）は Domain 導入済み。詳細・残置方針は [DDD.md](./DDD.md)。

Eloquent `Member::addPoints()` / `usePoints()` は削除済み。ポイント操作は Domain 集約、台帳バックフィルは `MemberRepository::syncPointsLedger`。

`DomainEventDispatcher` は `MulticastDomainEventDispatcher`（ログ + スマレジポイント同期リスナー）。

### 9.3 ルール（DDD 追加）

- Domain は Eloquent・`ShopSetting`・`Request` に依存しない
- 設定値は UseCase が取得し、集約メソッドの引数として渡す
- レガシー整合（`syncPointsLedger` 等）は Infrastructure の読み込み時に実行
- Repository の戻り値は **Domain 集約**（新規領域）。既存 Repository は引き続き Eloquent Model 可

### 9.4 Order パイロット

`OrderCancelUnpaidUseCase` は `Domain\Order\Aggregates\Order` でキャンセル可否を判定し、ステータス遷移を行う。`PaymentConfirmUseCase` / `PaymentWebhookUseCase` は同集約の `confirmStripePayment()` で決済確定する。ポイント返却・在庫戻しは `OrderService` が Member 集約と連携して実行する。
