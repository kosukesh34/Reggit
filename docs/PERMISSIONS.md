# 管理画面権限一覧

## ロールと権限の関係

| ロール | 説明 | 権限 |
|--------|------|------|
| **システム管理者** | 全プロジェクト・アカウント管理 | すべての操作が可能。スタッフの「細かい権限」は持たない（常に全許可）。 |
| **プロジェクト管理者** | 担当プロジェクトのみ | アカウント管理は不可。担当プロジェクト内の店舗管理は可能。 |
| **店舗管理者** | 担当店舗のみ | 同上。 |
| **スタッフ** | カスタム権限 | 下記の「スタッフ権限」のうち、付与されたもののみアクセス可能。 |

スタッフ以外は「スタッフ権限」のチェックは行わず、常に全メニューにアクセス可能（他ロールは `CheckAdminPermission` でスキップ）。

---

## スタッフ権限キー一覧

`AdminUserPermission::ALL_PERMISSIONS` および `PERMISSION_GROUPS` で定義。  
システム管理者は「アカウント作成・編集」でスタッフを選択し、以下の権限をチェックボックスで付与する。

| カテゴリ | 権限キー | 説明 | ルート例 |
|----------|----------|------|----------|
| ダッシュボード | `dashboard.view` | ダッシュボード閲覧 | admin.dashboard |
| 注文管理 | `orders.view` | 注文の閲覧 | admin.orders, admin.orders.detail |
| 注文管理 | `orders.update` | 注文ステータスの更新 | admin.orders.status, admin.orders.complete |
| 商品管理 | `products.view` | 商品の閲覧 | admin.products |
| 商品管理 | `products.manage` | 商品の作成・編集・削除 | admin.products.create, admin.products.store, ... |
| 会員管理 | `members.view` | 会員の閲覧 | admin.members, admin.members.detail |
| 会員管理 | `members.manage` | 会員の編集 | （将来拡張用） |
| 予約管理 | `reservations.view` | 予約の閲覧 | admin.reservations |
| 予約管理 | `reservations.update` | 予約ステータスの更新 | admin.reservations.status, admin.reservations.complete |
| 順番待ち | `queue.view` | 順番待ちの閲覧 | admin.queue |
| 順番待ち | `queue.update` | 順番待ちステータスの更新 | admin.queue.status |
| スタンプ | `stamps.view` | スタンプの閲覧 | admin.stamps |
| スタンプ | `stamps.manage` | スタンプの作成・編集・削除 | admin.stamps.create, ... |
| クーポン | `coupons.view` | クーポンの閲覧 | admin.coupons |
| クーポン | `coupons.manage` | クーポンの作成・編集・削除 | admin.coupons.create, ... |
| お知らせ | `announcements.view` | お知らせの閲覧 | admin.announcements |
| お知らせ | `announcements.manage` | お知らせの作成・編集・削除 | admin.announcements.create, ... |
| 分析 | `analytics.view` | 分析の閲覧 | admin.analytics |
| ギャラリー | `gallery.view` | ギャラリーの閲覧 | admin.gallery.categories, admin.gallery.items |
| ギャラリー | `gallery.manage` | ギャラリーの作成・編集・削除 | admin.gallery.*.store, ... |
| LIFFデザイン | `liff_designer.manage` | LIFFデザインの編集 | admin.settings.liff-designer |
| 設定 | `settings.view` | 設定の閲覧 | admin.settings.basic, advanced, menu, ... |
| 設定 | `settings.manage` | 設定の変更 | admin.settings.*.update |

---

## 権限の付け方（システム管理者）

1. システム管理画面で「アカウント管理」→「新規作成」または既存スタッフの「編集」。
2. 権限で「スタッフ」を選択すると「スタッフ権限」のチェックボックスが表示される。
3. 必要な機能だけにチェックを入れて保存。

これにより、スタッフは「注文の閲覧のみ」「商品の編集のみ」など、きめ細かく制限できる。
