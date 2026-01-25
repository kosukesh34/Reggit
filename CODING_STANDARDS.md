# コーディング規約

このドキュメントは、Mobile Order プロジェクトのコーディング規約を定義します。

## 1. ログ出力

### 基本方針
- ログ形式を統一し、調査時に有用な情報のみを出力する
- 余計なログやエラーは極力出さない
- トラブルシューティングの効率化・ノイズ削減

### 実装方法
- `Log::info()` で成功時の重要な操作を記録
- `Log::error()` でエラー時の情報を記録
- ログには配列形式でコンテキスト情報を含める

```php
// 良い例
Log::info('Reservation created', [
    'reservation_id' => $reservation->id,
    'user_id' => $user->id,
]);

Log::error('Reservation creation failed', [
    'user_id' => $user->id,
    'error' => $e->getMessage(),
]);
```

## 2. 定数の管理

### EnumBaseの使用
- 定数は Enum を使って管理する
- `App\Util\EnumBase` を継承したEnumライクなクラスで表現する
- 基本は `App\Util\EnumBase` を継承する（特殊な利用方法があるなら強制はしない）

```php
// 例: App\Enums\ReservationStatus
class ReservationStatus extends EnumBase
{
    protected static array $values = [
        'pending' => '予約待ち',
        'confirmed' => '予約確定',
    ];

    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
}
```

### クラス内定数
- クラス内で使用する定数は `private const` を使用
- 定数名は大文字のスネークケース

```php
class OrderService
{
    private const POINTS_EARN_RATE = 100;
}
```

## 3. マジックナンバーの禁止

- コード内に直接数値や文字列を記述せず、定数またはEnumで明示的に管理する
- 意図の明示・変更時の影響範囲の縮小

```php
// 悪い例
if ($points >= 100) { ... }

// 良い例
if ($points >= self::POINTS_EARN_RATE) { ... }
```

## 4. empty()の使用制限

- `empty()` は極力使用せず、 `isset()` や `===` などの厳密な比較で代替する
- `if ($obj)` のようなものは避ける。`if ($obj !== null)` のようにする
- ただしbool変数なら許容（`if ($isAvailable)` など）

```php
// 悪い例
if (empty($order->order_number)) { ... }

// 良い例
if ($order->order_number === null || $order->order_number === '') { ... }
```

## 5. 厳密比較

- 値の比較は厳密にする
- `==` は使用せず、常に `===` による厳密比較を行う
- View内などでの型変換の影響は考慮

```php
// 悪い例
if ($status == 'pending') { ... }

// 良い例
if ($status === ReservationStatus::PENDING) { ... }
```

## 6. bool変数名

基本は以下だが、文脈的にわかりやすいものなら何でもOK

- 状態: `isXxx`, `hasXxx`, `wasXxx`, `shouldXxx`
- 能力: `canXxx`, `allowXxx`
- 主語つき: `{subject}IsXxx`, `{subject}HasXxx`
- 状態名: `visible`, `enabled` など（属性に対応するもの）

NG：
- `xxxFlg`, `xxxBool`

```php
// 良い例
public function isPending(): bool
public function canCancel(): bool
public function hasMember(): bool
```

## 7. Viewへのプロパティ渡し

- Viewに渡すプロパティが4つ以上になる場合は、DTOを作成して渡す
- 複数のDTOに分けて渡してもOK
- データ構造の整理・保守性の向上

## 8. Viewの子コンポーネントへのprops

- `@include` などで子コンポーネントを使う際は、必要なpropsを明示的にすべて渡す
- コンポーネントの依存関係を明確にし、予期せぬ動作を防ぐ

## 9. テストコードの整備

### Feature（≒Controller）テスト
- 最低限、処理落ち（例外）が発生しないことを保証する
- 正常系と異常系のテストを実装する

### 方針・落とし所
- 既存のコードにあえてテストコードを追加する必要はない
- が「新しく触る・新規作成」するコードには基本的にテストコードを作成する
- 例外ケースは、スケジュールが厳しい時など

### Unitテスト
- Repository、UseCase、Modelなどほぼ全てのテスト
- テストケースは網羅的が理想、とはいえ効果の薄いテストをする必要はない
- 重要な観点としてテストを実装する：
  - 他の店舗のデータへの干渉・参照などクリティカルな部分に対してのテストは記載する

## 10. アクセサ(get〇〇Attribute)の制限

- `get〇〇Attribute` は原則使用しない。必要な場合はその意図を明示する
- モデル肥大化の防止・依存の明示

## 11. 型情報の明示

- 特に `array` などは型アノテーションで明示する
- PHPDocの有効活用
- メソッドの引数と戻り値に型ヒントを使用

```php
// 良い例
public function createReservation(User $user, array $data): Reservation
{
    // ...
}
```

## 12. 命名規則

### PHP
- メソッド名：`lowerCamelCase`
- 変数名：`lowerCamelCase`
- クラス名：`PascalCase`
- 定数名：`UPPER_SNAKE_CASE`（クラス内定数）
- Eloquentプロパティのみスネーク形式を許容（DBカラム名に合わせる）

### JavaScript
- クラス名：`PascalCase`
- メソッド名：`lowerCamelCase`
- 変数名：`lowerCamelCase`
- 定数名：`UPPER_SNAKE_CASE`（オブジェクト内の定数は `UPPER_CASE` キー）

## 13. コード量

- 一つの関数は15行程度を目安（画面スクロールが辛い、スクロールある・なしで認知負荷はだいぶ変わる）
- クラスの行数は100行を目安
- ただしテストコード・クラスなどはこの限りでない
- 厳密に守る必要はないが、推奨値に寄せることで可読性は良くなる

## 14. インラインスタイルをしない

- 一箇所などの超局所的な場合のみ認められる場合があるが基本的には違反とする
- CSSファイルに記述する

## 15. varではなく基本的にconstを使用する

- JavaScriptでは `const` を優先的に使用
- 再代入が必要な場合のみ `let` を使用
- `var` は使用しない

## 16. 変数名は意図がわかるように

- 略語は避け、意味が明確な変数名を使用する
- ループカウンタなどは `$i`, `$j` など短い名前でも可

## 17. 依存関係の注入

- コントローラーやサービスクラスでは、コンストラクタインジェクションを使用
- 依存関係はprivateプロパティで保持

```php
// 良い例
class OrderController extends Controller
{
    private OrderService $orderService;
    private UserService $userService;

    public function __construct(OrderService $orderService, UserService $userService)
    {
        $this->orderService = $orderService;
        $this->userService = $userService;
    }
}
```

## 18. 例外処理

- try-catchで例外をキャッチし、適切に処理する
- エラー時は `Log::error()` でログ出力
- 適切なHTTPステータスコードでレスポンスを返す

```php
// 良い例
try {
    $user = $this->userService->getOrCreateUser($request);
    $reservation = $this->reservationService->createReservation($user, $validated);
    return response()->json(['reservation' => $reservation], 201);
} catch (\Exception $e) {
    Log::error('Reservation creation failed: ' . $e->getMessage());
    return response()->json(['error' => '予約に失敗しました'], 400);
}
```

## 19. バリデーション

- Requestの `validate()` メソッドを使用
- バリデーションルールは明示的に記述

```php
// 良い例
$validated = $request->validate([
    'reserved_at' => 'required|date|after:now',
    'number_of_people' => 'required|integer|min:1|max:10',
    'notes' => 'nullable|string|max:500',
]);
```

## 20. トランザクション処理

- 複数のDB操作を行う場合は、トランザクションを使用
- `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` を使用

```php
// 良い例
DB::beginTransaction();
try {
    // DB操作
    DB::commit();
    return $result;
} catch (\Exception $e) {
    DB::rollBack();
    Log::error('Operation failed: ' . $e->getMessage());
    throw $e;
}
```

## 21. フロントエンド（JavaScript）

### クラス構造
- Managerクラス: 特定の機能を管理（CartManager, ProductManager, ReservationManager）
- 各Managerは独立して動作し、必要に応じてappインスタンスを受け取る

### API通信
- `ApiClient` クラスを使用してAPI通信を行う
- エラーハンドリングを統一

### 定数管理
- `AppConstants` オブジェクトで定数を管理
- APIエンドポイント、DOM要素ID、HTTPメソッドなどを定義

## 22. その他

- 可能な限りコードを増やさない
- Laravelのみで実装できそうな機能はそれを追加する必要は全くない
- 他の部分のコードを参考にコード規則には従うようにする
- 変更は最小限に、コードは保守性と可読性がなく冗長ではないのが最低条件です
- 基本的に新規のコメントアウトは不要なのと、コードレビューを行うのは変更した部分だけでお願いします（影響範囲を小さくするため）
