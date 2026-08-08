# 店舗アプリ UI スクショ学習メモ

iTunes Lookup API 経由で取得した公式 App Store スクリーンショット（16アプリ・109枚）を分析し、Reggit LIFF / App Studio テンプレへ反映した学習結果。

**管理画面での閲覧:** システム管理 →「店舗アプリUI学習データ」（`/system-admin/shop-app-ui-research`）

## 取得済みスクショ

- 代表カット（リポジトリ同梱）: [`_featured/`](_featured/)
- メタ情報: [`_meta/`](_meta/) / [`catalog.json`](catalog.json)
- 全枚数（エージェント成果物）: `/opt/cursor/artifacts/app-store-screenshots/`（16アプリ・109枚）

| アプリ | 枚数 |
|--------|------|
| Starbucks JP / US | 6+9 |
| シャトレーゼ | 6 |
| 銀座コージーコーナー | 4 |
| GODIVA / Lindt | 6+6 |
| UNIQLO / IKEA | 9+8 |
| マクドナルド JP | 6 |
| Chipotle / Dunkin' / Wendy's | 7+7+6 |
| Nike / adidas / ZARA / H&M | 7+6+6+10 |

---

## 学習した完璧テンプレ原則（業種横断）

### P1. 会計時は「1タップ・大バーコード」

シャトレーゼの会員証画面は:

1. ポイント残高を最大サイズで表示
2. 画面下部に「レジでバーコードをご提示ください」+ バーコードを常時露出
3. 余計なナビをカード周りに置かない

→ **会員証タブの主導線は「提示」**。ホームからも sticky CTA「会員証を提示」を置く。

### P2. ホームは「ステータス → 主役1つ → クイックアクション」

| 参考 | ホーム最上部 | 2番手 | 下部 |
|------|--------------|-------|------|
| Starbucks | 挨拶 + Star残高 + 進捗バー | Mobile Order CTA | Inbox/eTicket |
| コージーコーナー | 名前 + マイル | 商品ヒーロー | 会員証 / クーポン大ボタン |
| Chipotle | 注文状況 / Pickup | 地図・指示 | Chat |
| UNIQLO | 検索 | カテゴリグリッド | 下部タブに会員証 |

→ ホームに商品棚・統計・お知らせを全部載せない。**主役は1つ**。

### P3. ポイントは「数字」より「次の特典までの距離」

Starbucks の 25 / 90 / 130 マイルストーンと「スター リワードに交換」CTA。

→ `stats_row` に `points_to_next` を必ず入れ、交換 CTA を近くに置く。

### P4. 菓子・土産は「食欲写真 × ブランド色 × 受取」

シャトレーゼ: 茶×クリーム、カテゴリ丸写真、店頭受取  
コージーコーナー: 赤×白、ヒーローケーキ、会員証/クーポン並列  
GODIVA: 金×余白、ラグジュアリー、会員クラブ

→ 新スイート `rg-suite-sweets`（菓子・土産OS）を追加。

### P5. 注文系は「受取不安を消す」

Chipotle Pickup: 時刻・地図・「2袋」・棚イラスト・ヘルプ  
McDonald's: モバイルオーダー / デリバリーを最初に宣言

→ 注文OSホームに「受取カード」を最上部へ。

### P6. 小売は「検索・スキャン・会員証」が底部に常駐

UNIQLO: 検索バー + スキャン + 下部タブに Membership Card

→ 小売スイートの quick_actions に会員証・クーポンを固定。

---

## Reggit への反映マップ

| 学習元 | 反映先 |
|--------|--------|
| Starbucks Rewards | `rg-suite-loyalty` ホーム改善、`rg-screen-rewards` |
| シャトレーゼ / コージーコーナー | **新** `rg-suite-sweets`、`rg-screen-sweets-home`、`rg-screen-barcode-dock` |
| GODIVA / Lindt | `rg-screen-chocolate-club`、theme `chocolate` |
| McDonald's / Chipotle | `rg-suite-order` ホーム改善、`rg-screen-pickup-ready` |
| UNIQLO | `rg-suite-retail` ホーム改善、`rg-screen-category-browse` |
| Starbucks 朝 | 既存 `rg-suite-cafe` / `rg-screen-morning` 強化 |

---

## UI確認チェックリスト（スクショ検証済み版）

- [x] 会員証はホームから1アクションで出せるか（スターバックス/シャトレーゼ/コージーコーナー）
- [x] バーコードは会計時に十分なサイズか（シャトレーゼ）
- [x] クーポンは「使えるもの」が先か（コージーコーナー並列導線）
- [x] 注文完了後に受取場所・番号が分かるか（Chipotle）
- [x] 小売で検索・会員証が常に手が届くか（UNIQLO）
- [x] ブランド色が画面全体のトーンを決めているか（全アプリ共通）

---

## 次に見るべき追加アプリ（未取得）

コナミスポーツ、東横INN、ヨドバシ、丸善ジュンク堂、@cosme、セブン-イレブン、ファミリーマート、ローソン  
→ 同手順で `itunes.apple.com/lookup` から追加取得可能。
