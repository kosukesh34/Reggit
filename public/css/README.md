# CSS ファイル構造

このディレクトリは、アプリケーションのCSSファイルを管理画面とユーザー側で分離して整理しています。

## ディレクトリ構造

```
public/css/
├── frontend/    # ユーザー側（フロントエンド）のCSS
│   └── style.css
│
└── admin/       # 管理画面のCSS
    └── admin.css
```

## ファイルの役割

### Frontend（ユーザー側）

- **style.css**: ユーザー側のメインスタイルシート
  - 商品一覧
  - カート
  - 会員証
  - 予約機能
  - レスポンシブデザイン

### Admin（管理画面）

- **admin.css**: 管理画面のスタイルシート
  - ダッシュボード
  - 商品管理
  - 注文管理
  - 会員管理
  - 店舗設定

## 使用方法

### ユーザー側（index.blade.php）

```html
<link rel="stylesheet" href="{{ asset('css/frontend/style.css') }}">
```

### 管理画面（admin/layout.blade.php）

```html
<link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
```

## 注意事項

- 新しいスタイルを追加する際は、適切なファイルに追加してください
- フロントエンドと管理画面で共通のスタイルが必要な場合は、それぞれのファイルに追加するか、共通のCSSファイルを作成してください
- CSS変数を使用して、色やサイズなどの値を一元管理しています

