# スマレジ POS API エンドポイント一覧

[スマレジ・プラットフォームAPI POS仕様書](https://developers.smaregi.dev/platform-api-reference/apis/pos/) に掲載のエンドポイントを、`SmaregiApiService::request($contractId, $method, $path, $params)` で呼び出す際の **path** 一覧です。

使い方:
```php
$service = app(\App\Services\Smaregi\SmaregiApiService::class);
$contractId = $service->getContractIdForProject($projectId);
// GET（クエリは第4引数）
$data = $service->request($contractId, 'GET', 'pos/部門パス', ['limit' => 10, 'page' => 1]);
// POST/PATCH（ボディは第4引数）
$data = $service->request($contractId, 'POST', 'pos/部門パス', ['key' => 'value']);
```

---

## 部門（departments）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/departments` |
| 登録 | POST | `pos/departments` |
| 取得 | GET | `pos/departments/{department_id}` |
| 更新 | PATCH | `pos/departments/{department_id}` |
| 削除 | DELETE | `pos/departments/{department_id}` |

## 部門グループ（department_groups）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/department_groups` |
| 登録 | POST | `pos/department_groups` |
| 取得 | GET | `pos/department_groups/{id}` |
| 更新 | PATCH | `pos/department_groups/{id}` |
| 削除 | DELETE | `pos/department_groups/{id}` |

## 商品（products）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/products` |
| 登録 | POST | `pos/products` |
| 一括登録 | POST | `pos/products/bulk` |
| 一括更新 | PATCH | `pos/products/bulk` |
| 取得 | GET | `pos/products/{product_id}` |
| 更新 | PATCH | `pos/products/{product_id}` |
| 削除 | DELETE | `pos/products/{product_id}` |
| 商品価格一覧 | GET | `pos/products/{product_id}/prices` |
| 店舗商品価格一覧 | GET | `pos/products/{product_id}/store_prices` |

## セール（sales）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/sales` |
| 登録 | POST | `pos/sales` |
| 取得 | GET | `pos/sales/{sale_id}` |
| 更新 | PATCH | `pos/sales/{sale_id}` |
| 削除 | DELETE | `pos/sales/{sale_id}` |

## 会員（customers）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/customers` |
| 登録 | POST | `pos/customers` |
| 一括登録 | POST | `pos/customers/bulk` |
| 一括更新 | PATCH | `pos/customers/bulk` |
| 取得 | GET | `pos/customers/{customer_id}` |
| 更新 | PATCH | `pos/customers/{customer_id}` |
| 削除 | DELETE | `pos/customers/{customer_id}` |
| 会員ポイント一覧 | GET | `pos/customers/{customer_id}/points` |
| 会員ポイント更新 | PATCH | `pos/customers/{customer_id}/points` |
| 会員ポイント相対値更新 | POST | `pos/customers/{customer_id}/point/add` |
| 会員ポイント一括相対値更新 | POST | `pos/customers/point/add` |

## 会員ランク（customer_ranks）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/customer_ranks` |
| 登録 | POST | `pos/customer_ranks` |
| 取得 | GET | `pos/customer_ranks/{rank}` |
| 更新 | PATCH | `pos/customer_ranks/{rank}` |
| 削除 | DELETE | `pos/customer_ranks/{rank}` |

## 在庫（stocks）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/stocks` |
| 更新 | PATCH | `pos/stocks/{product_id}` |
| 相対値更新 | POST | `pos/stocks/{product_id}/add` |
| 一括相対値更新 | POST | `pos/stocks/add` |

## 取引（transactions）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/transactions` |
| 登録 | POST | `pos/transactions` |
| 取得 | GET | `pos/transactions/{transaction_id}` |
| 更新 | PATCH | `pos/transactions/{transaction_id}` |
| 取消 | POST | `pos/transactions/{transaction_id}/cancel` |
| 返品取消 | POST | `pos/transactions/{transaction_id}/return_cancel` |
| 取引明細取得 | GET | `pos/transactions/{transaction_id}/details` |
| 取引明細CSV作成 | POST | `pos/transactions/details/out_file_async` |

## 取置き（layaways）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/layaways` |
| 登録 | POST | `pos/layaways` |
| 取得 | GET | `pos/layaways/{layaway_id}` |
| 返品取消 | POST | `pos/layaways/{layaway_id}/return_cancel` |

## 仮販売（pending_transactions）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/pending_transactions` |
| 登録 | POST | `pos/pending_transactions` |
| 取得 | GET | `pos/pending_transactions/{id}` |
| 削除 | DELETE | `pos/pending_transactions/{id}` |
| 状態変更 | PATCH | `pos/pending_transactions/{id}/status` |

## 予算（budgets）
| 操作 | Method | path |
|------|--------|------|
| 月別予算一覧 | GET | `pos/budgets/monthly` |
| 日別予算取得 | GET | `pos/budgets/daily` |
| 日別予算登録 | POST | `pos/budgets/daily` |
| 日別予算更新 | PATCH | `pos/budgets/daily` |
| 日別予算削除 | DELETE | `pos/budgets/daily` |

## 店舗（stores）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/stores` |
| 登録 | POST | `pos/stores` |
| 取得 | GET | `pos/stores/{store_id}` |
| 更新 | PATCH | `pos/stores/{store_id}` |
| 削除 | DELETE | `pos/stores/{store_id}` |

## レジ端末（terminals）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/terminals` |
| 登録 | POST | `pos/terminals` |
| 取得 | GET | `pos/terminals/{terminal_id}` |
| 更新 | PATCH | `pos/terminals/{terminal_id}` |
| 削除 | DELETE | `pos/terminals/{terminal_id}` |
| 印刷 | POST | `pos/terminals/{terminal_id}/print` |
| 通知 | POST | `pos/terminals/{terminal_id}/notifications` |

## スタッフ（staffs）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/staffs` |
| 取得 | GET | `pos/staffs/{staff_id}` |

## 精算（settlements）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/settlements` |
| 精算実行 | POST | `pos/settlements` |

## 日次締め（daily_summaries）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/daily_summaries` |
| 締め実行 | POST | `pos/daily_summaries` |

## 支払方法（payment_methods）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/payment_methods` |
| 登録 | POST | `pos/payment_methods` |
| 取得 | GET | `pos/payment_methods/{payment_method_id}` |
| 更新 | PATCH | `pos/payment_methods/{payment_method_id}` |
| 削除 | DELETE | `pos/payment_methods/{payment_method_id}` |

## 個別アプリ専用支払方法（app_payment_methods）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/app_payment_methods` |
| 登録 | POST | `pos/app_payment_methods` |
| 削除 | DELETE | `pos/app_payment_methods/{id}` |

## クーポン（coupons）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/coupons` |
| 登録 | POST | `pos/coupons` |
| 取得 | GET | `pos/coupons/{coupon_id}` |
| 更新 | PATCH | `pos/coupons/{coupon_id}` |
| 削除 | DELETE | `pos/coupons/{coupon_id}` |

## 役割・役職（roles）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/roles` |
| 登録 | POST | `pos/roles` |
| 取得 | GET | `pos/roles/{role_id}` |
| 更新 | PATCH | `pos/roles/{role_id}` |
| 削除 | DELETE | `pos/roles/{role_id}` |

## 仕入先（suppliers）
| 操作 | Method | path |
|------|--------|------|
| 一覧取得 | GET | `pos/suppliers` |
| 登録 | POST | `pos/suppliers` |
| 取得 | GET | `pos/suppliers/{supplier_id}` |
| 更新 | PATCH | `pos/suppliers/{supplier_id}` |
| 削除 | DELETE | `pos/suppliers/{supplier_id}` |

---

上記以外の細かいパス（商品画像・セール店舗・バンドル販売・ロス・発注・入荷・出庫など）は [POS仕様書](https://developers.smaregi.dev/platform-api-reference/apis/pos/) の目次から該当APIのページを開き、**Path** に記載のパスを `pos/` に続けて指定してください。  
例: 仕様書に `/products/{product_id}/images` とあれば、path は `pos/products/{product_id}/images`（`{product_id}` を実際のIDに置き換え）。

必要なスコープは各APIの「Authorizations」を確認し、`.env` の `SMAREGI_SCOPE` およびスマレジ・デベロッパーズのスコープ設定で有効にしてください。
