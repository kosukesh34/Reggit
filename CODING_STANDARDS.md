# コーディング規約

層の設計は [アーキテクチャ](docs/ARCHITECTURE.md)、テスト・レビュー手順は [開発手順](docs/DEVELOPMENT.md) に集約しています。

## 変更の基本

- 変更範囲を小さく保ち、同種の既存実装に合わせる。管理機能は system-admin / Admin 配下を参照する。
- 一覧の順序、既存分岐、API フィールドを意図せず消さない。仕様変更は PR に理由と影響を書く。
- 一度しか使わない抽象化や不要な依存を増やさない。コメントアウトした旧コードは残さない。
- 関数約15行・クラス約100行を分割検討の目安にする。行数のためだけの分割はしない。

## 型・命名・比較

| 対象 | 規約 |
|---|---|
| クラス | `PascalCase` |
| メソッド・変数 | `lowerCamelCase`。DB カラムに対応する Eloquent 属性は snake_case |
| 定数 | `UPPER_SNAKE_CASE`。クラス内だけの値は `private const` |
| bool | `isXxx`、`hasXxx`、`canXxx` など意味を示す。`xxxFlg` / `xxxBool` は使わない |
| 比較 | `===` / `!==` を使用。bool はそのまま条件に使ってよい |
| 空値 | `empty()` による型の混同を避け、既存の `StrHelper` を使用 |
| 配列 | PHPDoc に `array<int, string>` や `array{key: string, count: int}` と形を示す |

引数と戻り値の型を明示し、`mixed` はログなど値が不定の境界に限定します。API の bool は `0` / `1` ではなく `true` / `false` を返します。業務状態は既存の `EnumBase` 系を使用し、外部 API のパラメータまで過剰に定数化しません。

スキーマとルートで保証された値の冗長な null チェックは不要です。任意入力、外部入力、権限・所有者のチェックは省略しません。`getXxxAttribute` アクセサは原則追加せず、必要なら責務を明示します。

## 入出力・依存

- Controller は入力検証、Input 組み立て、UseCase 呼び出し、レスポンス整形を担当する。
- UseCase は `Request` を受け取らず、Input / プリミティブ / ドメイン型を受け取る。他の UseCase を呼ばず、共通処理は Service へ置く。
- ビジネス依存はコンストラクタまたはメソッドで注入する。Controller / UseCase 内で Service・Model・UseCase を `new` しない。Input / View / Output の生成は可。
- 入出力クラスは用途に応じて `*Input`、`*View`、`*Output` と命名し、`DTO` というクラス名を付けない。
- 画面に渡す値が3つ以上なら表示用クラスを検討する。ラベル・日時は表示用に整形し、Blade の子コンポーネントへ必要な props を明示する。
- `static` は Enum や状態を持たない純粋なユーティリティに限定する。

## 検証・例外・整合性

入力ルールは FormRequest / HTTP 境界で検証し、Input は型と不変条件を表します。同じルールを Request・Input・UseCase に重複させず、UseCase で `Validator::make` を呼びません。既存 Input の検証例外を不要な try-catch で囲みません。

業務エラーと想定外エラーを区別し、必要な境界だけで catch します。クライアントには適切な HTTP ステータスと安全なメッセージを返し、詳細はログへ残します。ログは検索可能な文言と `project_id`・対象IDなどのコンテキストを使用し、秘密情報を含めません。

複数更新の整合性が必要な場合はトランザクションを使用します。単一テーブルでも在庫・決済など check-and-act の競合制御にロックが必要な場合は維持します。フレームワークのトランザクション closure を優先し、外部通信とイベントの実行時点を明示します。

日時の表示は View 側で整形し、比較は Carbon の `gt()` などを使います。既存の `DateUtil` がある箇所はそれを使用し、同じ変換を繰り返しません。

## フロントエンド

- TypeScript と `const` を基本とし、再代入だけ `let` を使う。`var` は追加しない。
- JavaScript のエントリは `resources/js/frontend/main.tsx` と `resources/js/admin/main.tsx`。Vite で配信し、`public/js` に classic script を追加しない。
- 共通通信は `ApiClient`、定数は既存の `AppConstants` を使用する。Cookie 認証の更新リクエストには CSRF トークンを送る。
- React の feature と既存 Manager の責務を分け、画面を移行して検証してから旧処理を削除する。
- CSS は既存の機能別ファイルへ置き、重複やインラインスタイルを増やさない。LIFF の読み込み元は `_head-assets.blade.php` を確認する。
- 遷移は `<a>`、操作は `<button>`。`readonly` は意図のある場合だけ使用する。
- 動的文字列を HTML として挿入しない。`textContent`、React の通常描画、Blade の `{{ }}` を優先する。

変更した挙動には有効な回帰テストを用意し、既存テストで十分ならその理由を PR に記載します。特にテナント・店舗・所有者の境界、状態遷移、二重処理を確認します。
