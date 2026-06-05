# コーディング規約

このドキュメントは、Reggit プロジェクトのコーディング規約を定義します。

**アーキテクチャ（層の分離・依存の向き）を最優先**に確認し、詳細は [docs/CLEAN_ARCHITECTURE.md](docs/CLEAN_ARCHITECTURE.md) と [docs/CODE_RULES_COMPLIANCE.md](docs/CODE_RULES_COMPLIANCE.md) を参照してください。PR・レビュー手順は [docs/MR_AND_CODE_REVIEW_PROCEDURE.md](docs/MR_AND_CODE_REVIEW_PROCEDURE.md) です。

## 0. 基本方針

1. **変更は最小限**にする。保守性・可読性を優先し、冗長なコードは避ける。
2. **既存コードにあえてテストを追加する必要はない**。新規・修正したコードには原則テストを追加する。既存テストで足りる場合は PR に理由を書く。
3. **既存の書き方に合わせる**。同じ機能は同じ関数・同じ取得方法を使う。参照実装は **system-admin / Admin 配下** を優先する。
4. **新規のコメントアウトは不要**。レビューは変更した部分を中心に行う。
5. **null について**: DB 上 null になり得ても、**実際の動作・表示に関係しない**場合は、過剰な null チェックは書かない。必要ならマイグレーション・スキーマで NOT NULL を確認してから判断する。
6. **デグレを最優先で防ぐ**。ソート順の消失・一覧の並び替え・意図しないコード削除は、意図的な変更か PR 本文で明示する。

## 1. ログ出力

### 基本方針
- ログ形式を統一し、調査時に有用な情報のみを出力する
- 余計なログやエラーは極力出さない
- トラブルシューティングの効率化・ノイズ削減

### 実装方法
- `Log::info()` で成功時の重要な操作を記録
- `Log::error()` でエラー時の情報を記録
- ログには配列形式でコンテキスト情報を含める
- 検索しやすい文言を使う（例: プロジェクトIDをキーに含める）
- スマレジPOSのログ形式がある場合はその踏襲を検討する

```php
// 良い例
Log::info('Reservation created', [
    'project_id' => $project->id,
    'reservation_id' => $reservation->id,
    'user_id' => $user->id,
]);

Log::error('Reservation creation failed', [
    'project_id' => $project->id ?? null,
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
- **新しく触る・新規作成**するコードには基本的にテストコードを作成する
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

### PHPDoc の型
- **`mixed` は避ける**: 引数・戻り値は可能な限り具体的な型で書く。やむを得ず「何でもあり」な配列の値だけ `array<string, mixed>` とする（例: ログコンテキスト、ルートパラメータ）。
- **配列の形が決まっているとき**: `@param array<int, string>` や `@return array{key: string, count: int}` のようにキー・要素の型を書く。
- **クラス・Data の array プロパティ**: コンストラクタの上に `@param array<キー型, 値型>` または `@param array{キー: 型, ...}` で説明する。

```php
// 良い例
public function createReservation(User $user, array $data): Reservation
{
    // ...
}

/** @return array{orders: int, revenue: float} */
private function buildSummary(): array { ... }

/** @param array<string, mixed> $context ログ用のキー・値（値は任意型） */
public static function error(string $message, array $context = []): void { ... }
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

## 17. 他コードへの準拠・StrHelper

- provider メソッド名など、既存の命名・パターンに準拠する。他ファイルを参考にコードを書く。
- null/空文字の判定は `StrHelper` を使う。`empty()` や直接比較より `StrHelper::isEmptyStringOrNull()` などを優先する。

```php
// 良い例
$canEditLinePlan = !StrHelper::isEmptyStringOrNull($project->smaregi_contract_id);
```

## 18. アーキテクチャ（最優先）

レビュー・実装では **まず層と依存の向き** を確認する。文法上動くコードでも、責務の分散やテスト容易性が崩れていれば修正対象とする。

### 18.1 層の依存（一方向のみ）

```
Controller → UseCase → Service / Repository → Model
```

| 層 | 責務 | 禁止 |
|----|------|------|
| **Controller** | validate → Input 組み立て → UseCase 呼び出し → レスポンス | ビジネスロジック、`new` による依存生成 |
| **UseCase** | 1 ユースケースのオーケストレーション | 他 UseCase の呼び出し、`Request` への依存 |
| **Service** | 再利用可能なドメイン外・技術処理 | UseCase / Controller への依存 |
| **Input / View / Result** | 入出力の型と最小限の検証 | UseCase のロジックの肥大化 |

### 18.2 `new` について（文法ではなく設計）

- Controller や UseCase 内で `new MemberRank()` のように **その場で依存を生成する** のは避ける。
- 問題になるのは主に次の点である。
  1. 依存関係が隠れる（何に依存しているか見えにくい）
  2. テストでモック差し替えがしづらい
  3. ロジックが Controller / Model に戻りやすい
  4. system-admin 等の既存実装とスタイルがずれる
- **許容**: `new UpdateProjectInput(...)` など **入出力用 DTO / Input の生成**（データの詰め替えのみ）。
- **禁止**: ビジネスロジックを持つ Model / Service / UseCase を `new` して利用する。

### 18.3 依存の注入（Laravel コンテナ）

- **Controller / UseCase / Service** の依存は Laravel コンテナ経由で渡す（`__construct` または **メソッド注入**）。`new` や、避けたい `app()` の乱用はしない。
- 1 アクションだけ使う UseCase は、コンストラクタに 8 個並べず **メソッド引数で注入** してもよい（system-admin の `SystemAdminDashboardController` 等）。

```php
// 良い例: コンストラクタ注入
public function __construct(
    private AdminCouponIndexUseCase $indexUseCase,
) {}

// 良い例: メソッド注入（アクションごとに UseCase が異なる場合）
public function dashboard(SystemAdminDashboardUseCase $useCase): View
{
    $dto = $useCase->handle();
    // ...
}
```

```php
// 悪い例: Controller でビジネス依存を new
$deadline = (new MemberRank())->getRankUpDeadlineDate(...);

// 悪い例: UseCase から別 UseCase を呼ぶ
$this->otherUseCase->handle($input); // → Service に共通化する
```

### 18.4 UseCase のルール

- **UseCase から UseCase を呼ばない**。複数ユースケースにまたがる処理は **Service** に寄せる。
- **UseCase 内で `Validator::make` を使わない**。検証は Input（または FormRequest）に集約する。
- 新規は `handle(○○Input $input): ○○Result|○○View` を推奨。入出力は UseCases 配下の **Data** に **Input / Result / View** として置く（「DTO」という名前は使わない）。
- 詳細は [docs/CLEAN_ARCHITECTURE.md](docs/CLEAN_ARCHITECTURE.md)。

### 18.5 static の使用

- **原則使わない**。ViewItem / UseCase / Service のメソッドを、理由なく `static` にしない。
- `$this` を使わないからといって `static` にしない。インスタンスメソッドでよいことが多い。
- 例外: EnumBase、純粋なユーティリティで **状態に依存しない** ことが明確な場合のみ。

### 18.6 SOLID（レビュー時の観点）

| 原則 | 本プロジェクトでの確認 |
|------|------------------------|
| **S** 単一責務 | Controller は入出力のみ。UseCase は 1 ユースケース。 |
| **O** 開放閉鎖 | Enum・Interface で拡張。既存分岐のコピペ増殖を避ける。 |
| **L** リスコフ | 継承は EnumBase・Model 等、置換可能な範囲に限定。 |
| **I** インターフェース分離 | 巨大 Interface に依存しない。 |
| **D** 依存性逆転 | 具象を `new` せず、コンテナ注入。UseCase → Service の一方向。 |

### 18.7 バリデーションの役割分担（二重化を避ける）

- **FormRequest / Input**: 入力の型・ルール（UseCase に渡る前に完了していること）。
- **UseCase**: 「検証済み Input が来る」前提。追加条件は Validator ではなく **条件分岐**。
- Request と Input と UseCase で **同じルールを 3 回書かない**。既存の同種 Input（例: コンストラクタ第 2 引数でモード切替）を参考にする。

### 18.8 テストの層

| 種別 | 担保すること |
|------|----------------|
| **Feature（≒ Controller）** | 処理落ちしない、HTTP ステータス・認可・**モード別のスキップ**（例: OwlSolution でバリデーション省略） |
| **Unit（Input / UseCase / Service）** | バリデーションロジック・ドメイン計算。Controller 経路の挙動は Feature に任せる |
| **共通** | 他店舗（`project_id`）への干渉は必須。効果の薄いテストは書かない |

## 20. 例外処理

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

## 21. バリデーション

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

## 22. トランザクション処理

- **複数テーブルをまたぐ更新**で整合性を保ちたい場合にのみトランザクションを使う。単一テーブルだけの更新で、複数テーブル対応の名残のトランザクションは削除してよい。
- `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` を使用する。

## 23. 日付の扱い

- **Carbon への変換**: 導入済みの場合は `DateUtil::nullableCarbon($value)` を使う。未導入の箇所は `Carbon::parse` の乱立を避け、共通化を検討する。
- **表示**: 画面用の整形は View / ViewItem 側のヘルパ（例: `formatDateTime`）に任せ、**表示のためだけに Carbon 化しない**。
- **比較**: 日付の大小比較は `$a > $b` ではなく、Carbon インスタンスなら **`$a->gt($b)`** 等のメソッドを使う。比較のためだけ Carbon 化する。
- **null**: カラムが NOT NULL とスキーマで確認できたら、不要な null ガードは書かない（§0 参照）。

## 24. UI（HTML）

- 意図がない限り、**ページ遷移に `button` を使わない**。遷移は `<a>` を使う。
- **`readonly`** は意図がないなら使わない。詳細表示と編集の兼用を検討する。

## 25. コードの簡潔さ

- **近道**: 無駄な中間変数・遠回りの分岐を避ける。使い捨ての値はそのまま渡してよい。
- **共通化**: 同じロジックの関数・同じ見た目の CSS はまとめる（重複コピペを増やさない）。
- **bool の返却**: API・JSON では `0` / `1` ではなく **`true` / `false`** を返す。

## 26. フロントエンド（JavaScript）

### クラス構造
- Managerクラス: 特定の機能を管理（CartManager, ProductManager, ReservationManager）
- 各Managerは独立して動作し、必要に応じてappインスタンスを受け取る

### API通信
- `ApiClient` クラスを使用してAPI通信を行う
- エラーハンドリングを統一

### 定数管理
- `AppConstants` オブジェクトで定数を管理
- APIエンドポイント、DOM要素ID、HTTPメソッドなどを定義

## 27. その他

- 可能な限りコードを増やさない
- Laravelのみで実装できそうな機能はそれを追加する必要は全くない
- 他の部分のコードを参考にコード規則には従うようにする
- 変更は最小限に、コードは保守性と可読性がなく冗長ではないのが最低条件です
- 基本的に新規のコメントアウトは不要なのと、コードレビューを行うのは変更した部分だけでお願いします（影響範囲を小さくするため）

---

## コードレビュー指摘事項（繰り返さないためのまとめ）

**目的**: 過去のレビュー指摘を共通ルールとして共有し、同じ指摘を繰り返さないようにする。

### 1. ルートパラメータの冗長な null/空チェック

- ルートで `{projectKey}` のように**必須パラメータ**を受け取っている場合、そのパラメータが無ければ 404 となり、コントローラに到達しない。
- **対応**: ルートで必須になっているパラメータに対して、コントローラ内で `StrHelper::isEmptyStringOrNull($projectKey)` などのチェックは**不要**。ルート定義で担保されているなら削除してよい。
- **例外**: オプショナルパラメータやクエリパラメータの場合は必要に応じてチェックする。

### 2. Input クラスを try で囲まない

- Input クラス（`UpdateProjectInput` など）のインスタンス化を `try { ... } catch` で囲まない。
- バリデーションは Laravel の Validation に沿い、Input のコンストラクタで `ValidationException` を投げる設計。try で囲む必要はない。

### 3. 例外の catch は必要以上に細かくしない

- 金融システムのような厳密な後処理分岐が無い限り、多数の例外型を個別に catch する必要はない。
- `\Exception` でまとめて扱ってよい。**例外の型ごとに後処理やメッセージを変える**場合だけ、個別 catch を検討する。
- 細かい例外型を並べて同じような処理を重複させない。

### 4. static は原則使わない

- ViewItem / UseCase / Service のメソッドを、理由なく `static` にしない（§18.5）。
- 例外は「状態に依存しないユーティリティ」で、チームで説明できる場合のみ。

### 4b. Controller / UseCase で `new` しない・UseCase 連鎖しない

- ビジネス依存はコンテナ注入（§18.2–18.3）。UseCase 同士の呼び出しは Service に寄せる（§18.4）。

### 4c. バリデーションの二重化をしない

- Input と UseCase と Request で同じルールを繰り返さない（§18.7）。

### 5. View には「表示用の文字列」を渡す

- View で `$viewItem->contractStopOptions[$viewItem->contractStopValue] ?? '-'` のように「配列 + キー」でラベルを組み立てるより、ViewItem 側でラベル文字列まで用意して渡す方がよい。
- **対応**: 表示用ラベルは **ViewItem（または DTO）のプロパティとして文字列で持たせる**（例: `contractStopLabel`）。View では `{{ $viewItem->contractStopLabel }}` のように参照するだけにする。
- **例外**: フォームの select の option 一覧など、意図的に「配列 + 現在値」を渡す場合はその限りではない。

### 6. UseCase で Validator クラスを使わない

- UseCase 内で `Validator::make(...)->validate()` のように Laravel の Validator を直接使わない。Input クラス側のバリデーションと役割が被る。
- **対応**: バリデーションは **Input クラスのコンストラクタ**で行い、UseCase では「Input が渡ってくる = すでに検証済み」を前提にする。UseCase 内で追加のチェックが必要な場合は、Validator に頼らず条件分岐などで行う。

### 7. 定数化は過剰にしない

- `SQLSTATE_*` やテーブル名・カラム名の定数化が、このコードベースでは過剰と判断される場合がある。
- 定数化するなら**プロジェクト全体で一貫した方針**が必要。一箇所だけ定数化するとかえって不揃いになる。**マジックナンバー・文字列の排除**が目的なら、既存の Enum やモデル定数で足りる範囲はそちらを優先する。

### 8. トランザクションは「複数テーブル更新」のときだけ

- 単一テーブル更新でも `DB::beginTransaction()` を貼っていた場合は、「複数テーブルを更新するならトランザクション」という観点で見直す。
- **複数テーブルをまたぐ更新**で整合性を保ちたい場合にトランザクションを使う。単一テーブルだけの更新で、過去の「複数テーブル対応」の名残のトランザクションは削除してよい。

### 9. デグレ（機能・順序の消失）

- ソート条件の削除・一覧順の入れ替え・既存分岐の削除は、**意図的な仕様変更か**を確認する。
- 意図的なら PR 本文に理由を書く。意図でなければ修正する。

### 10. PHPDoc と bool 返却

- `@param mixed` は避け、配列は `@param array{key: type, ...}` のように形状を書く（§11）。
- bool は `0`/`1` ではなく `true`/`false`（§25）。
