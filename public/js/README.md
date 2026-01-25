# JavaScript ファイル構造

このディレクトリは、アプリケーションのJavaScriptファイルを管理画面とユーザー側で分離して整理しています。

## ディレクトリ構造

```
public/js/
├── frontend/          # ユーザー側（フロントエンド）のJavaScript
│   ├── app.js        # メインアプリケーション
│   ├── stripe.js     # Stripe決済関連
│   └── managers/     # 各機能のマネージャークラス
│       ├── CartManager.js
│       ├── ProductManager.js
│       └── ReservationManager.js
│
├── admin/            # 管理画面のJavaScript
│   └── admin.js      # 管理画面のメインスクリプト
│
└── shared/           # 共通のJavaScript（フロントエンドと管理画面で共有）
    ├── constants.js  # 定数定義
    └── utils/        # ユーティリティクラス
        ├── apiClient.js
        ├── confirmDialog.js
        ├── domHelper.js
        ├── toast.js
        └── tooltip.js
```

## ファイルの役割

### Frontend（ユーザー側）

- **app.js**: ユーザー側のメインアプリケーション。タブ切り替え、初期化処理など
- **stripe.js**: Stripe決済処理
- **managers/**: 各機能を管理するクラス
  - **CartManager.js**: カート機能の管理
  - **ProductManager.js**: 商品表示・フィルタリングの管理
  - **ReservationManager.js**: 予約機能の管理

### Admin（管理画面）

- **admin.js**: 管理画面のメインスクリプト。削除確認ダイアログなど

### Shared（共通）

- **constants.js**: アプリケーション全体で使用する定数（APIエンドポイント、要素IDなど）
- **utils/**: 共通のユーティリティクラス
  - **apiClient.js**: API通信のラッパー
  - **confirmDialog.js**: 確認ダイアログ
  - **domHelper.js**: DOM操作のヘルパー
  - **toast.js**: トースト通知
  - **tooltip.js**: ツールチップ

## 使用方法

### ユーザー側（index.blade.php）

```html
<script src="{{ asset('js/shared/constants.js') }}"></script>
<script src="{{ asset('js/shared/utils/apiClient.js') }}"></script>
<script src="{{ asset('js/shared/utils/domHelper.js') }}"></script>
<script src="{{ asset('js/shared/utils/tooltip.js') }}"></script>
<script src="{{ asset('js/shared/utils/toast.js') }}"></script>
<script src="{{ asset('js/shared/utils/confirmDialog.js') }}"></script>
<script src="{{ asset('js/frontend/managers/CartManager.js') }}"></script>
<script src="{{ asset('js/frontend/managers/ProductManager.js') }}"></script>
<script src="{{ asset('js/frontend/managers/ReservationManager.js') }}"></script>
<script src="{{ asset('js/frontend/stripe.js') }}"></script>
<script src="{{ asset('js/frontend/app.js') }}"></script>
```

### 管理画面（admin/layout.blade.php）

```html
<script src="{{ asset('js/shared/utils/confirmDialog.js') }}"></script>
<script src="{{ asset('js/admin/admin.js') }}"></script>
```

## 注意事項

- 新しいファイルを追加する際は、適切なディレクトリに配置してください
- フロントエンドと管理画面の両方で使用する機能は `shared/` に配置してください
- ファイル名は `PascalCase.js`（クラス）または `camelCase.js`（ユーティリティ）を使用してください

