# スマレジSHOP アプリUIデザイン参考資料（全店舗URL完全版）

スマレジSHOPのUI改善・競合分析向け資料。**店舗・ブランドは省略せず全件掲載**している。

- 全店舗一覧CSV: [`docs/research/smaregi_shop_app_ui_all_stores.csv`](research/smaregi_shop_app_ui_all_stores.csv)
- 件数: **905** ブランド／チェーン（カテゴリ別に全件。同名ブランドが複数カテゴリに出る場合あり）

> **URL方針**  
> - 公式アプリのApp Store直リンクが確認できたものは直URLを記載  
> - それ以外は App Store / Google Play の検索URLを**全件**付与（UI確認の導線）  
> - ストア側の配信停止・ID変更があり得るため、実装前に再確認すること

---

## 1. 調査の目的

| 目的 | 使い方 |
|------|--------|
| スマレジSHOPのUI改善 | 有名店舗アプリの画面構成・導線を参考にする |
| 競合比較 | 店舗アプリ / LINE / 飲食DX の機能差を把握する |
| 営業・市場の母集団 | スマレジ顧客になり得る業界・ブランドを洗い出す |

特に見たいUI観点: 会員証 / ポイント / スタンプ / クーポン / 店舗検索 / モバイルオーダー / 予約 / EC / Push / LINE連携

---

## 2. 競合構造（店舗DX / 顧客接点）

```text
                 店舗DX / 顧客接点
                       │
        ┌──────────────┼──────────────┐
        │              │              │
     独自アプリ       LINE           飲食DX
        │              │              │
   Yappli CRM       アイリッジ       dinii
   GMOおみせアプリ   EPG            トレタ
   TenpoApp          Fannaly        ebica
   みせプリ          Enlyt          TableCheck
   toypo / STORES
```

---

## 3. 業界分類とスマレジ適合度

| # | 大分類 | 適合度 |
|---|--------|--------|
| ① | アパレル・ファッション | ★★★★★ |
| ② | 雑貨・小売 | ★★★★★ |
| ③ | 食品・飲料小売 | ★★★★★ |
| ④ | 美容・コスメ | ★★★★★ |
| ⑤ | 飲食 | ★★★★★ |
| ⑥ | 美容・サービス | ★★★★☆ |
| ⑦ | ホビー・エンタメ | ★★★★☆ |
| ⑧ | 専門小売 | ★★★★☆ |
| ⑨ | 催事・イベント | ★★★★☆ |
| ⑩ | BtoB・卸売 | ★★★☆☆ |

狙いやすい条件: SKU多い / 複数店舗 / 会員管理重要 / 店舗+EC / 催事・ポップアップ多い

---

## 4. カテゴリ別件数

| 業界カテゴリ | 件数 |
|--------------|------|
| お土産・菓子 | 141 |
| その他店舗・サービス | 18 |
| アパレル・ファッション | 93 |
| カフェ・珈琲 | 43 |
| グローバル小売 | 73 |
| グローバル飲食 | 40 |
| コスメ・ドラッグ・美容 | 44 |
| ジム・フィットネス | 31 |
| スポーツ・アウトドア | 18 |
| ペット | 14 |
| ホテル・宿泊 | 15 |
| レジャー・施設 | 34 |
| 催事・イベント・観光物販 | 14 |
| 家電・デジタル | 14 |
| 書店・ホビー・エンタメ | 30 |
| 百貨店・商業施設 | 18 |
| 美容・サロン | 23 |
| 自動車・バイク・自転車 | 14 |
| 花・園芸 | 12 |
| 雑貨・家具・生活用品 | 39 |
| 食品スーパー・食品小売 | 48 |
| 飲食・ファストフード | 129 |
| **合計** | **905** |

---

## 5. 全店舗・ブランド一覧（省略なし）

各行に App Store / Google Play のUI確認URLを付与。直リンク確認済みは「確認状況」列に明示。

### 5.1 お土産・菓子（141件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 551蓬莱 | 蓬莱 | 要確認 | https://apps.apple.com/jp/search?term=551%E8%93%AC%E8%8E%B1 | https://play.google.com/store/search?q=551%E8%93%AC%E8%8E%B1&c=apps&hl=ja |  |
| 2 | BAKE | BAKE INC | 要確認 | https://apps.apple.com/jp/search?term=BAKE | https://play.google.com/store/search?q=BAKE&c=apps&hl=ja |  |
| 3 | BAKE CHEESE TART | BAKE | 要確認 | https://apps.apple.com/jp/search?term=BAKE%20CHEESE%20TART | https://play.google.com/store/search?q=BAKE%20CHEESE%20TART&c=apps&hl=ja |  |
| 4 | GODIVA | ゴディバ ジャパン | 確認済み | https://apps.apple.com/jp/app/%E3%82%B4%E3%83%87%E3%82%A3%E3%83%90-godiva-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1543392103 | https://play.google.com/store/search?q=GODIVA&c=apps&hl=ja | GODIVA Club、スタンプ、ポイント/ランク、店舗検索 |
| 5 | Henri Charpentier | アンリ・シャルパンティエ | 要確認 | https://apps.apple.com/jp/search?term=Henri%20Charpentier | https://play.google.com/store/search?q=Henri%20Charpentier&c=apps&hl=ja |  |
| 6 | KITTEお土産 | JPタワー | 要確認 | https://apps.apple.com/jp/search?term=KITTE%E3%81%8A%E5%9C%9F%E7%94%A3 | https://play.google.com/store/search?q=KITTE%E3%81%8A%E5%9C%9F%E7%94%A3&c=apps&hl=ja |  |
| 7 | LeTAO | 小樽洋菓子舗ルタオ | 確認済み | https://apps.apple.com/jp/app/letao-%E5%B0%8F%E6%A8%BD%E6%B4%8B%E8%8F%93%E5%AD%90%E8%88%97%E3%83%AB%E3%82%BF%E3%82%AA-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1459057727 | https://play.google.com/store/search?q=LeTAO&c=apps&hl=ja | EC、クーポン、店舗情報（配信状況は要再確認） |
| 8 | Lindt | リンツ＆シュプルングリー ジャパン | 確認済み | https://apps.apple.com/jp/app/%E3%83%AA%E3%83%B3%E3%83%84%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA-my-lindt-club/id1569279518 | https://play.google.com/store/search?q=Lindt&c=apps&hl=ja | My Lindt Club、特典、ブランド体験 |
| 9 | PRESS BUTTER SAND | BAKE | 要確認 | https://apps.apple.com/jp/search?term=PRESS%20BUTTER%20SAND | https://play.google.com/store/search?q=PRESS%20BUTTER%20SAND&c=apps&hl=ja |  |
| 10 | ROYCE | ロイズコンフェクト | 要確認 | https://apps.apple.com/jp/search?term=ROYCE | https://play.google.com/store/search?q=ROYCE&c=apps&hl=ja |  |
| 11 | ROYCE' | ロイズコンフェクト | 要確認 | https://apps.apple.com/jp/search?term=ROYCE%27 | https://play.google.com/store/search?q=ROYCE%27&c=apps&hl=ja |  |
| 12 | ありあけ | ありあけ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%82%E3%82%8A%E3%81%82%E3%81%91 | https://play.google.com/store/search?q=%E3%81%82%E3%82%8A%E3%81%82%E3%81%91&c=apps&hl=ja |  |
| 13 | ありあけ横濱ハーバー | ありあけ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%82%E3%82%8A%E3%81%82%E3%81%91%E6%A8%AA%E6%BF%B1%E3%83%8F%E3%83%BC%E3%83%90%E3%83%BC | https://play.google.com/store/search?q=%E3%81%82%E3%82%8A%E3%81%82%E3%81%91%E6%A8%AA%E6%BF%B1%E3%83%8F%E3%83%BC%E3%83%90%E3%83%BC&c=apps&hl=ja |  |
| 14 | きのとや | きのとや | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8D%E3%81%AE%E3%81%A8%E3%82%84 | https://play.google.com/store/search?q=%E3%81%8D%E3%81%AE%E3%81%A8%E3%82%84&c=apps&hl=ja |  |
| 15 | きのとや 新千歳空港 | きのとや | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8D%E3%81%AE%E3%81%A8%E3%82%84%20%E6%96%B0%E5%8D%83%E6%AD%B3%E7%A9%BA%E6%B8%AF | https://play.google.com/store/search?q=%E3%81%8D%E3%81%AE%E3%81%A8%E3%82%84%20%E6%96%B0%E5%8D%83%E6%AD%B3%E7%A9%BA%E6%B8%AF&c=apps&hl=ja |  |
| 16 | じゃがポックル | カルビーポテト | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%98%E3%82%83%E3%81%8C%E3%83%9D%E3%83%83%E3%82%AF%E3%83%AB | https://play.google.com/store/search?q=%E3%81%98%E3%82%83%E3%81%8C%E3%83%9D%E3%83%83%E3%82%AF%E3%83%AB&c=apps&hl=ja |  |
| 17 | ずんだシェイク | ずんだ茶寮 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9A%E3%82%93%E3%81%A0%E3%82%B7%E3%82%A7%E3%82%A4%E3%82%AF | https://play.google.com/store/search?q=%E3%81%9A%E3%82%93%E3%81%A0%E3%82%B7%E3%82%A7%E3%82%A4%E3%82%AF&c=apps&hl=ja |  |
| 18 | ずんだ茶寮 | ずんだ茶寮 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9A%E3%82%93%E3%81%A0%E8%8C%B6%E5%AF%AE | https://play.google.com/store/search?q=%E3%81%9A%E3%82%93%E3%81%A0%E8%8C%B6%E5%AF%AE&c=apps&hl=ja |  |
| 19 | ずんだ餅 | ずんだ茶寮 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9A%E3%82%93%E3%81%A0%E9%A4%85 | https://play.google.com/store/search?q=%E3%81%9A%E3%82%93%E3%81%A0%E9%A4%85&c=apps&hl=ja |  |
| 20 | ずんだ餅 ずんだ茶寮 | ずんだ茶寮 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9A%E3%82%93%E3%81%A0%E9%A4%85%20%E3%81%9A%E3%82%93%E3%81%A0%E8%8C%B6%E5%AF%AE | https://play.google.com/store/search?q=%E3%81%9A%E3%82%93%E3%81%A0%E9%A4%85%20%E3%81%9A%E3%82%93%E3%81%A0%E8%8C%B6%E5%AF%AE&c=apps&hl=ja |  |
| 21 | たねや | たねや | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9F%E3%81%AD%E3%82%84 | https://play.google.com/store/search?q=%E3%81%9F%E3%81%AD%E3%82%84&c=apps&hl=ja |  |
| 22 | ちんすこう 新垣カミ菓子店 | 新垣カミ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A1%E3%82%93%E3%81%99%E3%81%93%E3%81%86%20%E6%96%B0%E5%9E%A3%E3%82%AB%E3%83%9F%E8%8F%93%E5%AD%90%E5%BA%97 | https://play.google.com/store/search?q=%E3%81%A1%E3%82%93%E3%81%99%E3%81%93%E3%81%86%20%E6%96%B0%E5%9E%A3%E3%82%AB%E3%83%9F%E8%8F%93%E5%AD%90%E5%BA%97&c=apps&hl=ja |  |
| 23 | とらや | 虎屋 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A8%E3%82%89%E3%82%84 | https://play.google.com/store/search?q=%E3%81%A8%E3%82%89%E3%82%84&c=apps&hl=ja |  |
| 24 | とらや工房 | 虎屋 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A8%E3%82%89%E3%82%84%E5%B7%A5%E6%88%BF | https://play.google.com/store/search?q=%E3%81%A8%E3%82%89%E3%82%84%E5%B7%A5%E6%88%BF&c=apps&hl=ja |  |
| 25 | ねんりん家 | ねんりん家 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AD%E3%82%93%E3%82%8A%E3%82%93%E5%AE%B6 | https://play.google.com/store/search?q=%E3%81%AD%E3%82%93%E3%82%8A%E3%82%93%E5%AE%B6&c=apps&hl=ja |  |
| 26 | ねんりん家 バームクーヘン | ねんりん家 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AD%E3%82%93%E3%82%8A%E3%82%93%E5%AE%B6%20%E3%83%90%E3%83%BC%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3 | https://play.google.com/store/search?q=%E3%81%AD%E3%82%93%E3%82%8A%E3%82%93%E5%AE%B6%20%E3%83%90%E3%83%BC%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3&c=apps&hl=ja |  |
| 27 | ひよ子 | ひよ子 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%B2%E3%82%88%E5%AD%90 | https://play.google.com/store/search?q=%E3%81%B2%E3%82%88%E5%AD%90&c=apps&hl=ja |  |
| 28 | ふくや | ふくや | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%B5%E3%81%8F%E3%82%84 | https://play.google.com/store/search?q=%E3%81%B5%E3%81%8F%E3%82%84&c=apps&hl=ja |  |
| 29 | ふんわりチーズケーキ | りくろーおじさん | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%B5%E3%82%93%E3%82%8F%E3%82%8A%E3%83%81%E3%83%BC%E3%82%BA%E3%82%B1%E3%83%BC%E3%82%AD | https://play.google.com/store/search?q=%E3%81%B5%E3%82%93%E3%82%8F%E3%82%8A%E3%83%81%E3%83%BC%E3%82%BA%E3%82%B1%E3%83%BC%E3%82%AD&c=apps&hl=ja |  |
| 30 | もち吉 | もち吉 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%82%E3%81%A1%E5%90%89 | https://play.google.com/store/search?q=%E3%82%82%E3%81%A1%E5%90%89&c=apps&hl=ja |  |
| 31 | もみじ饅頭 にしき堂 | にしき堂 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%82%E3%81%BF%E3%81%98%E9%A5%85%E9%A0%AD%20%E3%81%AB%E3%81%97%E3%81%8D%E5%A0%82 | https://play.google.com/store/search?q=%E3%82%82%E3%81%BF%E3%81%98%E9%A5%85%E9%A0%AD%20%E3%81%AB%E3%81%97%E3%81%8D%E5%A0%82&c=apps&hl=ja |  |
| 32 | やまや | やまやコミュニケーションズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%84%E3%81%BE%E3%82%84 | https://play.google.com/store/search?q=%E3%82%84%E3%81%BE%E3%82%84&c=apps&hl=ja |  |
| 33 | りくろーおじさんの店 | りくろーおじさん | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%8A%E3%81%8F%E3%82%8D%E3%83%BC%E3%81%8A%E3%81%98%E3%81%95%E3%82%93%E3%81%AE%E5%BA%97 | https://play.google.com/store/search?q=%E3%82%8A%E3%81%8F%E3%82%8D%E3%83%BC%E3%81%8A%E3%81%98%E3%81%95%E3%82%93%E3%81%AE%E5%BA%97&c=apps&hl=ja |  |
| 34 | わかさいも本舗 | わかさいも | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%8F%E3%81%8B%E3%81%95%E3%81%84%E3%82%82%E6%9C%AC%E8%88%97 | https://play.google.com/store/search?q=%E3%82%8F%E3%81%8B%E3%81%95%E3%81%84%E3%82%82%E6%9C%AC%E8%88%97&c=apps&hl=ja |  |
| 35 | アンテノール | アンテノール | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%B3%E3%83%86%E3%83%8E%E3%83%BC%E3%83%AB | https://play.google.com/store/search?q=%E3%82%A2%E3%83%B3%E3%83%86%E3%83%8E%E3%83%BC%E3%83%AB&c=apps&hl=ja |  |
| 36 | アンデルセン | タカキベーカリー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%B3%E3%83%87%E3%83%AB%E3%82%BB%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%B3%E3%83%87%E3%83%AB%E3%82%BB%E3%83%B3&c=apps&hl=ja |  |
| 37 | アンリ・シャルパンティエ | アンリ・シャルパンティエ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%B3%E3%83%AA%E3%83%BB%E3%82%B7%E3%83%A3%E3%83%AB%E3%83%91%E3%83%B3%E3%83%86%E3%82%A3%E3%82%A8 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%B3%E3%83%AA%E3%83%BB%E3%82%B7%E3%83%A3%E3%83%AB%E3%83%91%E3%83%B3%E3%83%86%E3%82%A3%E3%82%A8&c=apps&hl=ja |  |
| 38 | エシレ・メゾン デュ ブール | エシレ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%82%B7%E3%83%AC%E3%83%BB%E3%83%A1%E3%82%BE%E3%83%B3%20%E3%83%87%E3%83%A5%20%E3%83%96%E3%83%BC%E3%83%AB | https://play.google.com/store/search?q=%E3%82%A8%E3%82%B7%E3%83%AC%E3%83%BB%E3%83%A1%E3%82%BE%E3%83%B3%20%E3%83%87%E3%83%A5%20%E3%83%96%E3%83%BC%E3%83%AB&c=apps&hl=ja |  |
| 39 | カカオサンパカ | カカオサンパカ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%82%AB%E3%82%AA%E3%82%B5%E3%83%B3%E3%83%91%E3%82%AB | https://play.google.com/store/search?q=%E3%82%AB%E3%82%AB%E3%82%AA%E3%82%B5%E3%83%B3%E3%83%91%E3%82%AB&c=apps&hl=ja |  |
| 40 | クラブハリエ | たねや | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%A9%E3%83%96%E3%83%8F%E3%83%AA%E3%82%A8 | https://play.google.com/store/search?q=%E3%82%AF%E3%83%A9%E3%83%96%E3%83%8F%E3%83%AA%E3%82%A8&c=apps&hl=ja |  |
| 41 | クラブハリエ バームクーヘン | たねや | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%A9%E3%83%96%E3%83%8F%E3%83%AA%E3%82%A8%20%E3%83%90%E3%83%BC%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%AF%E3%83%A9%E3%83%96%E3%83%8F%E3%83%AA%E3%82%A8%20%E3%83%90%E3%83%BC%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3&c=apps&hl=ja |  |
| 42 | クルミッ子 | 鎌倉紅谷 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%AB%E3%83%9F%E3%83%83%E5%AD%90 | https://play.google.com/store/search?q=%E3%82%AF%E3%83%AB%E3%83%9F%E3%83%83%E5%AD%90&c=apps&hl=ja |  |
| 43 | コロンバン | コロンバン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%AD%E3%83%B3%E3%83%90%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%AD%E3%83%B3%E3%83%90%E3%83%B3&c=apps&hl=ja |  |
| 44 | ゴンチャロフ | ゴンチャロフ製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B4%E3%83%B3%E3%83%81%E3%83%A3%E3%83%AD%E3%83%95 | https://play.google.com/store/search?q=%E3%82%B4%E3%83%B3%E3%83%81%E3%83%A3%E3%83%AD%E3%83%95&c=apps&hl=ja |  |
| 45 | サンメルシー | サンメルシー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%A1%E3%83%AB%E3%82%B7%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%A1%E3%83%AB%E3%82%B7%E3%83%BC&c=apps&hl=ja |  |
| 46 | シャトレーゼ | シャトレーゼ | 確認済み | https://apps.apple.com/jp/app/%E3%82%B7%E3%83%A3%E3%83%88%E3%83%AC%E3%83%BC%E3%82%BC%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id844024333 | https://play.google.com/store/search?q=%E3%82%B7%E3%83%A3%E3%83%88%E3%83%AC%E3%83%BC%E3%82%BC&c=apps&hl=ja | カシポ会員証、WEB予約・店頭受取、クーポン、店舗検索、Push |
| 47 | シュガーバターの木 | グレープストーン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B7%E3%83%A5%E3%82%AC%E3%83%BC%E3%83%90%E3%82%BF%E3%83%BC%E3%81%AE%E6%9C%A8 | https://play.google.com/store/search?q=%E3%82%B7%E3%83%A5%E3%82%AC%E3%83%BC%E3%83%90%E3%82%BF%E3%83%BC%E3%81%AE%E6%9C%A8&c=apps&hl=ja |  |
| 48 | シーキューブ | シーキューブ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B7%E3%83%BC%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%96 | https://play.google.com/store/search?q=%E3%82%B7%E3%83%BC%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%96&c=apps&hl=ja |  |
| 49 | ジャン＝ポール・エヴァン | ジャン＝ポール・エヴァン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A3%E3%83%B3%EF%BC%9D%E3%83%9D%E3%83%BC%E3%83%AB%E3%83%BB%E3%82%A8%E3%83%B4%E3%82%A1%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A3%E3%83%B3%EF%BC%9D%E3%83%9D%E3%83%BC%E3%83%AB%E3%83%BB%E3%82%A8%E3%83%B4%E3%82%A1%E3%83%B3&c=apps&hl=ja |  |
| 50 | ダロワイヨ | ダロワイヨ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%80%E3%83%AD%E3%83%AF%E3%82%A4%E3%83%A8 | https://play.google.com/store/search?q=%E3%83%80%E3%83%AD%E3%83%AF%E3%82%A4%E3%83%A8&c=apps&hl=ja |  |
| 51 | チーズケーキファクトリー | 各社 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%81%E3%83%BC%E3%82%BA%E3%82%B1%E3%83%BC%E3%82%AD%E3%83%95%E3%82%A1%E3%82%AF%E3%83%88%E3%83%AA%E3%83%BC | https://play.google.com/store/search?q=%E3%83%81%E3%83%BC%E3%82%BA%E3%82%B1%E3%83%BC%E3%82%AD%E3%83%95%E3%82%A1%E3%82%AF%E3%83%88%E3%83%AA%E3%83%BC&c=apps&hl=ja |  |
| 52 | ドンク | ドンク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%B3%E3%82%AF | https://play.google.com/store/search?q=%E3%83%89%E3%83%B3%E3%82%AF&c=apps&hl=ja |  |
| 53 | ドンク panier | ドンク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%B3%E3%82%AF%20panier | https://play.google.com/store/search?q=%E3%83%89%E3%83%B3%E3%82%AF%20panier&c=apps&hl=ja |  |
| 54 | ドーナツオン | ダスキン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%BC%E3%83%8A%E3%83%84%E3%82%AA%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%89%E3%83%BC%E3%83%8A%E3%83%84%E3%82%AA%E3%83%B3&c=apps&hl=ja |  |
| 55 | バウムクーヘンの治一郎 | 治一郎 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%82%A6%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3%E3%81%AE%E6%B2%BB%E4%B8%80%E9%83%8E | https://play.google.com/store/search?q=%E3%83%90%E3%82%A6%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3%E3%81%AE%E6%B2%BB%E4%B8%80%E9%83%8E&c=apps&hl=ja |  |
| 56 | バターバット | BAKE | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%90%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 57 | パティスリー・サダハル・アオキ | サダハル・アオキ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC%E3%83%BB%E3%82%B5%E3%83%80%E3%83%8F%E3%83%AB%E3%83%BB%E3%82%A2%E3%82%AA%E3%82%AD | https://play.google.com/store/search?q=%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC%E3%83%BB%E3%82%B5%E3%83%80%E3%83%8F%E3%83%AB%E3%83%BB%E3%82%A2%E3%82%AA%E3%82%AD&c=apps&hl=ja |  |
| 58 | パティスリー・サダハル・アオキ・パリ | サダハル・アオキ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC%E3%83%BB%E3%82%B5%E3%83%80%E3%83%8F%E3%83%AB%E3%83%BB%E3%82%A2%E3%82%AA%E3%82%AD%E3%83%BB%E3%83%91%E3%83%AA | https://play.google.com/store/search?q=%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC%E3%83%BB%E3%82%B5%E3%83%80%E3%83%8F%E3%83%AB%E3%83%BB%E3%82%A2%E3%82%AA%E3%82%AD%E3%83%BB%E3%83%91%E3%83%AA&c=apps&hl=ja |  |
| 59 | ビアードパパ | シュークリーム専門店ビアードパパ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%82%A2%E3%83%BC%E3%83%89%E3%83%91%E3%83%91 | https://play.google.com/store/search?q=%E3%83%93%E3%82%A2%E3%83%BC%E3%83%89%E3%83%91%E3%83%91&c=apps&hl=ja |  |
| 60 | ピエール・エルメ・パリ | ピエール・エルメ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%94%E3%82%A8%E3%83%BC%E3%83%AB%E3%83%BB%E3%82%A8%E3%83%AB%E3%83%A1%E3%83%BB%E3%83%91%E3%83%AA | https://play.google.com/store/search?q=%E3%83%94%E3%82%A8%E3%83%BC%E3%83%AB%E3%83%BB%E3%82%A8%E3%83%AB%E3%83%A1%E3%83%BB%E3%83%91%E3%83%AA&c=apps&hl=ja |  |
| 61 | フォション | フォション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A9%E3%82%B7%E3%83%A7%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%95%E3%82%A9%E3%82%B7%E3%83%A7%E3%83%B3&c=apps&hl=ja |  |
| 62 | フランセ | フランセ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%83%A9%E3%83%B3%E3%82%BB | https://play.google.com/store/search?q=%E3%83%95%E3%83%A9%E3%83%B3%E3%82%BB&c=apps&hl=ja |  |
| 63 | ブールミッシュ | ブールミッシュ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%96%E3%83%BC%E3%83%AB%E3%83%9F%E3%83%83%E3%82%B7%E3%83%A5 | https://play.google.com/store/search?q=%E3%83%96%E3%83%BC%E3%83%AB%E3%83%9F%E3%83%83%E3%82%B7%E3%83%A5&c=apps&hl=ja |  |
| 64 | ポンパドウル | ポンパドウル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%83%B3%E3%83%91%E3%83%89%E3%82%A6%E3%83%AB | https://play.google.com/store/search?q=%E3%83%9D%E3%83%B3%E3%83%91%E3%83%89%E3%82%A6%E3%83%AB&c=apps&hl=ja |  |
| 65 | マキシム・ド・パリ | マキシム | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%82%AD%E3%82%B7%E3%83%A0%E3%83%BB%E3%83%89%E3%83%BB%E3%83%91%E3%83%AA | https://play.google.com/store/search?q=%E3%83%9E%E3%82%AD%E3%82%B7%E3%83%A0%E3%83%BB%E3%83%89%E3%83%BB%E3%83%91%E3%83%AA&c=apps&hl=ja |  |
| 66 | マリベル | マリベル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%AA%E3%83%99%E3%83%AB | https://play.google.com/store/search?q=%E3%83%9E%E3%83%AA%E3%83%99%E3%83%AB&c=apps&hl=ja |  |
| 67 | ミスタードーナツ | ダスキン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%89%E3%83%BC%E3%83%8A%E3%83%84 | https://play.google.com/store/search?q=%E3%83%9F%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%89%E3%83%BC%E3%83%8A%E3%83%84&c=apps&hl=ja |  |
| 68 | ミスド | ダスキン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%82%B9%E3%83%89 | https://play.google.com/store/search?q=%E3%83%9F%E3%82%B9%E3%83%89&c=apps&hl=ja |  |
| 69 | ミニヨン | 各社 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%83%8B%E3%83%A8%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%9F%E3%83%8B%E3%83%A8%E3%83%B3&c=apps&hl=ja |  |
| 70 | メリーチョコレート | メリーチョコレートカムパニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A1%E3%83%AA%E3%83%BC%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%83%A1%E3%83%AA%E3%83%BC%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 71 | モロゾフ | モロゾフ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A2%E3%83%AD%E3%82%BE%E3%83%95 | https://play.google.com/store/search?q=%E3%83%A2%E3%83%AD%E3%82%BE%E3%83%95&c=apps&hl=ja |  |
| 72 | ユーハイム | ユーハイム | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%BC%E3%83%8F%E3%82%A4%E3%83%A0 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%BC%E3%83%8F%E3%82%A4%E3%83%A0&c=apps&hl=ja |  |
| 73 | ユーハイム バウムクーヘン | ユーハイム | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%BC%E3%83%8F%E3%82%A4%E3%83%A0%20%E3%83%90%E3%82%A6%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%BC%E3%83%8F%E3%82%A4%E3%83%A0%20%E3%83%90%E3%82%A6%E3%83%A0%E3%82%AF%E3%83%BC%E3%83%98%E3%83%B3&c=apps&hl=ja |  |
| 74 | ヨックモック | ヨックモック | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A8%E3%83%83%E3%82%AF%E3%83%A2%E3%83%83%E3%82%AF | https://play.google.com/store/search?q=%E3%83%A8%E3%83%83%E3%82%AF%E3%83%A2%E3%83%83%E3%82%AF&c=apps&hl=ja |  |
| 75 | ラ ブティック ドゥ ジョエル・ロブション | ロブション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%20%E3%83%96%E3%83%86%E3%82%A3%E3%83%83%E3%82%AF%20%E3%83%89%E3%82%A5%20%E3%82%B8%E3%83%A7%E3%82%A8%E3%83%AB%E3%83%BB%E3%83%AD%E3%83%96%E3%82%B7%E3%83%A7%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A9%20%E3%83%96%E3%83%86%E3%82%A3%E3%83%83%E3%82%AF%20%E3%83%89%E3%82%A5%20%E3%82%B8%E3%83%A7%E3%82%A8%E3%83%AB%E3%83%BB%E3%83%AD%E3%83%96%E3%82%B7%E3%83%A7%E3%83%B3&c=apps&hl=ja |  |
| 76 | ラデュレ | ラデュレ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%83%87%E3%83%A5%E3%83%AC | https://play.google.com/store/search?q=%E3%83%A9%E3%83%87%E3%83%A5%E3%83%AC&c=apps&hl=ja |  |
| 77 | リトルマーメイド | 神戸屋 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AA%E3%83%88%E3%83%AB%E3%83%9E%E3%83%BC%E3%83%A1%E3%82%A4%E3%83%89 | https://play.google.com/store/search?q=%E3%83%AA%E3%83%88%E3%83%AB%E3%83%9E%E3%83%BC%E3%83%A1%E3%82%A4%E3%83%89&c=apps&hl=ja |  |
| 78 | リングスター | 神戸風月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AA%E3%83%B3%E3%82%B0%E3%82%B9%E3%82%BF%E3%83%BC | https://play.google.com/store/search?q=%E3%83%AA%E3%83%B3%E3%82%B0%E3%82%B9%E3%82%BF%E3%83%BC&c=apps&hl=ja |  |
| 79 | ルタオ パティスリー | ルタオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AB%E3%82%BF%E3%82%AA%20%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC | https://play.google.com/store/search?q=%E3%83%AB%E3%82%BF%E3%82%AA%20%E3%83%91%E3%83%86%E3%82%A3%E3%82%B9%E3%83%AA%E3%83%BC&c=apps&hl=ja |  |
| 80 | ロイズ | ロイズコンフェクト | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%82%A4%E3%82%BA | https://play.google.com/store/search?q=%E3%83%AD%E3%82%A4%E3%82%BA&c=apps&hl=ja |  |
| 81 | ロイズチョコレートワールド | ロイズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%82%A4%E3%82%BA%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89 | https://play.google.com/store/search?q=%E3%83%AD%E3%82%A4%E3%82%BA%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89&c=apps&hl=ja |  |
| 82 | ロイズ生チョコレート | ロイズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%82%A4%E3%82%BA%E7%94%9F%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%83%AD%E3%82%A4%E3%82%BA%E7%94%9F%E3%83%81%E3%83%A7%E3%82%B3%E3%83%AC%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 83 | ヴィ・ド・フランス | 神戸屋 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%B4%E3%82%A3%E3%83%BB%E3%83%89%E3%83%BB%E3%83%95%E3%83%A9%E3%83%B3%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%B4%E3%82%A3%E3%83%BB%E3%83%89%E3%83%BB%E3%83%95%E3%83%A9%E3%83%B3%E3%82%B9&c=apps&hl=ja |  |
| 84 | 不二家 | 不二家 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%8D%E4%BA%8C%E5%AE%B6 | https://play.google.com/store/search?q=%E4%B8%8D%E4%BA%8C%E5%AE%B6&c=apps&hl=ja |  |
| 85 | 中川政七商店 | 中川政七商店 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%AD%E5%B7%9D%E6%94%BF%E4%B8%83%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E4%B8%AD%E5%B7%9D%E6%94%BF%E4%B8%83%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 86 | 久世福商店 | サンクゼール | 要確認 | https://apps.apple.com/jp/search?term=%E4%B9%85%E4%B8%96%E7%A6%8F%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E4%B9%85%E4%B8%96%E7%A6%8F%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 87 | 亀屋万年堂 | 亀屋万年堂 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%80%E5%B1%8B%E4%B8%87%E5%B9%B4%E5%A0%82 | https://play.google.com/store/search?q=%E4%BA%80%E5%B1%8B%E4%B8%87%E5%B9%B4%E5%A0%82&c=apps&hl=ja |  |
| 88 | 二◯加煎餅 | にいか煎餅 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%8C%E2%97%AF%E5%8A%A0%E7%85%8E%E9%A4%85 | https://play.google.com/store/search?q=%E4%BA%8C%E2%97%AF%E5%8A%A0%E7%85%8E%E9%A4%85&c=apps&hl=ja |  |
| 89 | 二鶴堂 | 二鶴堂 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%8C%E9%B6%B4%E5%A0%82 | https://play.google.com/store/search?q=%E4%BA%8C%E9%B6%B4%E5%A0%82&c=apps&hl=ja |  |
| 90 | 井筒八ッ橋本舗 | 井筒八ッ橋本舗 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%95%E7%AD%92%E5%85%AB%E3%83%83%E6%A9%8B%E6%9C%AC%E8%88%97 | https://play.google.com/store/search?q=%E4%BA%95%E7%AD%92%E5%85%AB%E3%83%83%E6%A9%8B%E6%9C%AC%E8%88%97&c=apps&hl=ja |  |
| 91 | 京橋千疋屋 | 千疋屋 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%AC%E6%A9%8B%E5%8D%83%E7%96%8B%E5%B1%8B | https://play.google.com/store/search?q=%E4%BA%AC%E6%A9%8B%E5%8D%83%E7%96%8B%E5%B1%8B&c=apps&hl=ja |  |
| 92 | 人形焼 人形町 | 各老舗 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%BA%E5%BD%A2%E7%84%BC%20%E4%BA%BA%E5%BD%A2%E7%94%BA | https://play.google.com/store/search?q=%E4%BA%BA%E5%BD%A2%E7%84%BC%20%E4%BA%BA%E5%BD%A2%E7%94%BA&c=apps&hl=ja |  |
| 93 | 仙台名物牛たん | 各社 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BB%99%E5%8F%B0%E5%90%8D%E7%89%A9%E7%89%9B%E3%81%9F%E3%82%93 | https://play.google.com/store/search?q=%E4%BB%99%E5%8F%B0%E5%90%8D%E7%89%A9%E7%89%9B%E3%81%9F%E3%82%93&c=apps&hl=ja |  |
| 94 | 伊藤久右衛門 | 伊藤久右衛門 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BC%8A%E8%97%A4%E4%B9%85%E5%8F%B3%E8%A1%9B%E9%96%80 | https://play.google.com/store/search?q=%E4%BC%8A%E8%97%A4%E4%B9%85%E5%8F%B3%E8%A1%9B%E9%96%80&c=apps&hl=ja |  |
| 95 | 八ツ橋 | 聖護院八ッ橋総本店 | 要確認 | https://apps.apple.com/jp/search?term=%E5%85%AB%E3%83%84%E6%A9%8B | https://play.google.com/store/search?q=%E5%85%AB%E3%83%84%E6%A9%8B&c=apps&hl=ja |  |
| 96 | 六花亭 | 六花亭 | 要確認 | https://apps.apple.com/jp/search?term=%E5%85%AD%E8%8A%B1%E4%BA%AD | https://play.google.com/store/search?q=%E5%85%AD%E8%8A%B1%E4%BA%AD&c=apps&hl=ja |  |
| 97 | 千鳥屋宗家 | 千鳥屋 | 要確認 | https://apps.apple.com/jp/search?term=%E5%8D%83%E9%B3%A5%E5%B1%8B%E5%AE%97%E5%AE%B6 | https://play.google.com/store/search?q=%E5%8D%83%E9%B3%A5%E5%B1%8B%E5%AE%97%E5%AE%B6&c=apps&hl=ja |  |
| 98 | 千鳥饅頭総本舗 | 千鳥屋 | 要確認 | https://apps.apple.com/jp/search?term=%E5%8D%83%E9%B3%A5%E9%A5%85%E9%A0%AD%E7%B7%8F%E6%9C%AC%E8%88%97 | https://play.google.com/store/search?q=%E5%8D%83%E9%B3%A5%E9%A5%85%E9%A0%AD%E7%B7%8F%E6%9C%AC%E8%88%97&c=apps&hl=ja |  |
| 99 | 博多通りもん | 明月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E5%8D%9A%E5%A4%9A%E9%80%9A%E3%82%8A%E3%82%82%E3%82%93 | https://play.google.com/store/search?q=%E5%8D%9A%E5%A4%9A%E9%80%9A%E3%82%8A%E3%82%82%E3%82%93&c=apps&hl=ja |  |
| 100 | 如水庵 | 如水庵 | 要確認 | https://apps.apple.com/jp/search?term=%E5%A6%82%E6%B0%B4%E5%BA%B5 | https://play.google.com/store/search?q=%E5%A6%82%E6%B0%B4%E5%BA%B5&c=apps&hl=ja |  |
| 101 | 崎陽軒 | 崎陽軒 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B4%8E%E9%99%BD%E8%BB%92 | https://play.google.com/store/search?q=%E5%B4%8E%E9%99%BD%E8%BB%92&c=apps&hl=ja |  |
| 102 | 崎陽軒シウマイ | 崎陽軒 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B4%8E%E9%99%BD%E8%BB%92%E3%82%B7%E3%82%A6%E3%83%9E%E3%82%A4 | https://play.google.com/store/search?q=%E5%B4%8E%E9%99%BD%E8%BB%92%E3%82%B7%E3%82%A6%E3%83%9E%E3%82%A4&c=apps&hl=ja |  |
| 103 | 文明堂 | 文明堂東京 | 要確認 | https://apps.apple.com/jp/search?term=%E6%96%87%E6%98%8E%E5%A0%82 | https://play.google.com/store/search?q=%E6%96%87%E6%98%8E%E5%A0%82&c=apps&hl=ja |  |
| 104 | 明月堂 | 明月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E6%98%8E%E6%9C%88%E5%A0%82 | https://play.google.com/store/search?q=%E6%98%8E%E6%9C%88%E5%A0%82&c=apps&hl=ja |  |
| 105 | 札幌農学校 | きのとや | 要確認 | https://apps.apple.com/jp/search?term=%E6%9C%AD%E5%B9%8C%E8%BE%B2%E5%AD%A6%E6%A0%A1 | https://play.google.com/store/search?q=%E6%9C%AD%E5%B9%8C%E8%BE%B2%E5%AD%A6%E6%A0%A1&c=apps&hl=ja |  |
| 106 | 杏仁豆腐の上野風月堂 | 上野風月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%8F%E4%BB%81%E8%B1%86%E8%85%90%E3%81%AE%E4%B8%8A%E9%87%8E%E9%A2%A8%E6%9C%88%E5%A0%82 | https://play.google.com/store/search?q=%E6%9D%8F%E4%BB%81%E8%B1%86%E8%85%90%E3%81%AE%E4%B8%8A%E9%87%8E%E9%A2%A8%E6%9C%88%E5%A0%82&c=apps&hl=ja |  |
| 107 | 東京ばな奈 | グレープストーン | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88&c=apps&hl=ja |  |
| 108 | 東京ばな奈カフェ | グレープストーン | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 109 | 東京ミルクチーズ工場 | グレープストーン | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%83%9F%E3%83%AB%E3%82%AF%E3%83%81%E3%83%BC%E3%82%BA%E5%B7%A5%E5%A0%B4 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%83%9F%E3%83%AB%E3%82%AF%E3%83%81%E3%83%BC%E3%82%BA%E5%B7%A5%E5%A0%B4&c=apps&hl=ja |  |
| 110 | 東京風月堂 | 東京風月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E9%A2%A8%E6%9C%88%E5%A0%82 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E9%A2%A8%E6%9C%88%E5%A0%82&c=apps&hl=ja |  |
| 111 | 東京駅グランスタ銘品 | JR東日本クロスステーション | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E9%A7%85%E3%82%B0%E3%83%A9%E3%83%B3%E3%82%B9%E3%82%BF%E9%8A%98%E5%93%81 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E9%A7%85%E3%82%B0%E3%83%A9%E3%83%B3%E3%82%B9%E3%82%BF%E9%8A%98%E5%93%81&c=apps&hl=ja |  |
| 112 | 柳月 | 柳月 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9F%B3%E6%9C%88 | https://play.google.com/store/search?q=%E6%9F%B3%E6%9C%88&c=apps&hl=ja |  |
| 113 | 柳月三方六 | 柳月 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9F%B3%E6%9C%88%E4%B8%89%E6%96%B9%E5%85%AD | https://play.google.com/store/search?q=%E6%9F%B3%E6%9C%88%E4%B8%89%E6%96%B9%E5%85%AD&c=apps&hl=ja |  |
| 114 | 榮太樓總本鋪 | 榮太樓 | 要確認 | https://apps.apple.com/jp/search?term=%E6%A6%AE%E5%A4%AA%E6%A8%93%E7%B8%BD%E6%9C%AC%E9%8B%AA | https://play.google.com/store/search?q=%E6%A6%AE%E5%A4%AA%E6%A8%93%E7%B8%BD%E6%9C%AC%E9%8B%AA&c=apps&hl=ja |  |
| 115 | 治一郎 | 治一郎 | 要確認 | https://apps.apple.com/jp/search?term=%E6%B2%BB%E4%B8%80%E9%83%8E | https://play.google.com/store/search?q=%E6%B2%BB%E4%B8%80%E9%83%8E&c=apps&hl=ja |  |
| 116 | 清水羊羹 京菓匠 | 各社 | 要確認 | https://apps.apple.com/jp/search?term=%E6%B8%85%E6%B0%B4%E7%BE%8A%E7%BE%B9%20%E4%BA%AC%E8%8F%93%E5%8C%A0 | https://play.google.com/store/search?q=%E6%B8%85%E6%B0%B4%E7%BE%8A%E7%BE%B9%20%E4%BA%AC%E8%8F%93%E5%8C%A0&c=apps&hl=ja |  |
| 117 | 牛たんの牛たん炭焼利久 | 利久 | 要確認 | https://apps.apple.com/jp/search?term=%E7%89%9B%E3%81%9F%E3%82%93%E3%81%AE%E7%89%9B%E3%81%9F%E3%82%93%E7%82%AD%E7%84%BC%E5%88%A9%E4%B9%85 | https://play.google.com/store/search?q=%E7%89%9B%E3%81%9F%E3%82%93%E3%81%AE%E7%89%9B%E3%81%9F%E3%82%93%E7%82%AD%E7%84%BC%E5%88%A9%E4%B9%85&c=apps&hl=ja |  |
| 118 | 白い恋人 | 石屋製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA | https://play.google.com/store/search?q=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA&c=apps&hl=ja |  |
| 119 | 白い恋人ソフト | 石屋製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%82%BD%E3%83%95%E3%83%88 | https://play.google.com/store/search?q=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%82%BD%E3%83%95%E3%83%88&c=apps&hl=ja |  |
| 120 | 白い恋人パーク | 石屋製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%91%E3%83%BC%E3%82%AF | https://play.google.com/store/search?q=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%91%E3%83%BC%E3%82%AF&c=apps&hl=ja |  |
| 121 | 白い恋人ブラック | 石屋製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%96%E3%83%A9%E3%83%83%E3%82%AF | https://play.google.com/store/search?q=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%96%E3%83%A9%E3%83%83%E3%82%AF&c=apps&hl=ja |  |
| 122 | 白い恋人ホワイト | 石屋製菓 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%9B%E3%83%AF%E3%82%A4%E3%83%88 | https://play.google.com/store/search?q=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA%E3%83%9B%E3%83%AF%E3%82%A4%E3%83%88&c=apps&hl=ja |  |
| 123 | 石村萬盛堂 | 石村萬盛堂 | 要確認 | https://apps.apple.com/jp/search?term=%E7%9F%B3%E6%9D%91%E8%90%AC%E7%9B%9B%E5%A0%82 | https://play.google.com/store/search?q=%E7%9F%B3%E6%9D%91%E8%90%AC%E7%9B%9B%E5%A0%82&c=apps&hl=ja |  |
| 124 | 神戸屋キッチン | 神戸屋 | 要確認 | https://apps.apple.com/jp/search?term=%E7%A5%9E%E6%88%B8%E5%B1%8B%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3 | https://play.google.com/store/search?q=%E7%A5%9E%E6%88%B8%E5%B1%8B%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3&c=apps&hl=ja |  |
| 125 | 神戸風月堂 | 神戸風月堂 | 要確認 | https://apps.apple.com/jp/search?term=%E7%A5%9E%E6%88%B8%E9%A2%A8%E6%9C%88%E5%A0%82 | https://play.google.com/store/search?q=%E7%A5%9E%E6%88%B8%E9%A2%A8%E6%9C%88%E5%A0%82&c=apps&hl=ja |  |
| 126 | 紅芋タルト 御菓子御殿 | 御菓子御殿 | 要確認 | https://apps.apple.com/jp/search?term=%E7%B4%85%E8%8A%8B%E3%82%BF%E3%83%AB%E3%83%88%20%E5%BE%A1%E8%8F%93%E5%AD%90%E5%BE%A1%E6%AE%BF | https://play.google.com/store/search?q=%E7%B4%85%E8%8A%8B%E3%82%BF%E3%83%AB%E3%83%88%20%E5%BE%A1%E8%8F%93%E5%AD%90%E5%BE%A1%E6%AE%BF&c=apps&hl=ja |  |
| 127 | 紫野和久傳 | 和久傳 | 要確認 | https://apps.apple.com/jp/search?term=%E7%B4%AB%E9%87%8E%E5%92%8C%E4%B9%85%E5%82%B3 | https://play.google.com/store/search?q=%E7%B4%AB%E9%87%8E%E5%92%8C%E4%B9%85%E5%82%B3&c=apps&hl=ja |  |
| 128 | 舟和 | 舟和 | 要確認 | https://apps.apple.com/jp/search?term=%E8%88%9F%E5%92%8C | https://play.google.com/store/search?q=%E8%88%9F%E5%92%8C&c=apps&hl=ja |  |
| 129 | 花園万頭 | 花園万頭 | 要確認 | https://apps.apple.com/jp/search?term=%E8%8A%B1%E5%9C%92%E4%B8%87%E9%A0%AD | https://play.google.com/store/search?q=%E8%8A%B1%E5%9C%92%E4%B8%87%E9%A0%AD&c=apps&hl=ja |  |
| 130 | 茅乃舎 | 久原本家 | 要確認 | https://apps.apple.com/jp/search?term=%E8%8C%85%E4%B9%83%E8%88%8E | https://play.google.com/store/search?q=%E8%8C%85%E4%B9%83%E8%88%8E&c=apps&hl=ja |  |
| 131 | 萩の月 | 菓匠三全 | 要確認 | https://apps.apple.com/jp/search?term=%E8%90%A9%E3%81%AE%E6%9C%88 | https://play.google.com/store/search?q=%E8%90%A9%E3%81%AE%E6%9C%88&c=apps&hl=ja |  |
| 132 | 萩の月ソフト | 菓匠三全 | 要確認 | https://apps.apple.com/jp/search?term=%E8%90%A9%E3%81%AE%E6%9C%88%E3%82%BD%E3%83%95%E3%83%88 | https://play.google.com/store/search?q=%E8%90%A9%E3%81%AE%E6%9C%88%E3%82%BD%E3%83%95%E3%83%88&c=apps&hl=ja |  |
| 133 | 萩月 | 菓匠三全 | 要確認 | https://apps.apple.com/jp/search?term=%E8%90%A9%E6%9C%88 | https://play.google.com/store/search?q=%E8%90%A9%E6%9C%88&c=apps&hl=ja |  |
| 134 | 虎屋 | 虎屋 | 要確認 | https://apps.apple.com/jp/search?term=%E8%99%8E%E5%B1%8B | https://play.google.com/store/search?q=%E8%99%8E%E5%B1%8B&c=apps&hl=ja |  |
| 135 | 西洋菓子舗不二家 | 不二家 | 要確認 | https://apps.apple.com/jp/search?term=%E8%A5%BF%E6%B4%8B%E8%8F%93%E5%AD%90%E8%88%97%E4%B8%8D%E4%BA%8C%E5%AE%B6 | https://play.google.com/store/search?q=%E8%A5%BF%E6%B4%8B%E8%8F%93%E5%AD%90%E8%88%97%E4%B8%8D%E4%BA%8C%E5%AE%B6&c=apps&hl=ja |  |
| 136 | 豊島屋 | 豊島屋 | 要確認 | https://apps.apple.com/jp/search?term=%E8%B1%8A%E5%B3%B6%E5%B1%8B | https://play.google.com/store/search?q=%E8%B1%8A%E5%B3%B6%E5%B1%8B&c=apps&hl=ja |  |
| 137 | 赤福 | 赤福 | 要確認 | https://apps.apple.com/jp/search?term=%E8%B5%A4%E7%A6%8F | https://play.google.com/store/search?q=%E8%B5%A4%E7%A6%8F&c=apps&hl=ja |  |
| 138 | 銀座ウエスト | ウエスト | 要確認 | https://apps.apple.com/jp/search?term=%E9%8A%80%E5%BA%A7%E3%82%A6%E3%82%A8%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E9%8A%80%E5%BA%A7%E3%82%A6%E3%82%A8%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 139 | 銀座コージーコーナー | 銀座コージーコーナー | 確認済み | https://apps.apple.com/jp/app/%E9%8A%80%E5%BA%A7%E3%82%B3%E3%83%BC%E3%82%B8%E3%83%BC%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%BC-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id6446264862 | https://play.google.com/store/search?q=%E9%8A%80%E5%BA%A7%E3%82%B3%E3%83%BC%E3%82%B8%E3%83%BC%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%BC&c=apps&hl=ja | 会員証/マイル、クーポン、店舗検索、ネット予約、EC（Yappli系） |
| 140 | 鎌倉紅谷 | 鎌倉紅谷 | 要確認 | https://apps.apple.com/jp/search?term=%E9%8E%8C%E5%80%89%E7%B4%85%E8%B0%B7 | https://play.google.com/store/search?q=%E9%8E%8C%E5%80%89%E7%B4%85%E8%B0%B7&c=apps&hl=ja |  |
| 141 | 鶴屋吉信 | 鶴屋吉信 | 要確認 | https://apps.apple.com/jp/search?term=%E9%B6%B4%E5%B1%8B%E5%90%89%E4%BF%A1 | https://play.google.com/store/search?q=%E9%B6%B4%E5%B1%8B%E5%90%89%E4%BF%A1&c=apps&hl=ja |  |

### 5.2 その他店舗・サービス（18件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | JINS | ジンズホールディングス | 要確認 | https://apps.apple.com/jp/search?term=JINS | https://play.google.com/store/search?q=JINS&c=apps&hl=ja |  |
| 2 | Owndays | Owndays | 要確認 | https://apps.apple.com/jp/search?term=Owndays | https://play.google.com/store/search?q=Owndays&c=apps&hl=ja |  |
| 3 | うさちゃんクリーニング | うさちゃんクリーニング | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%86%E3%81%95%E3%81%A1%E3%82%83%E3%82%93%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%81%86%E3%81%95%E3%81%A1%E3%82%83%E3%82%93%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 4 | たまひよ写真館 | ベネッセ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9F%E3%81%BE%E3%81%B2%E3%82%88%E5%86%99%E7%9C%9F%E9%A4%A8 | https://play.google.com/store/search?q=%E3%81%9F%E3%81%BE%E3%81%B2%E3%82%88%E5%86%99%E7%9C%9F%E9%A4%A8&c=apps&hl=ja |  |
| 5 | クリーニング専科 | クリーニング専科 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0%E5%B0%82%E7%A7%91 | https://play.google.com/store/search?q=%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0%E5%B0%82%E7%A7%91&c=apps&hl=ja |  |
| 6 | コンタクトのアイシティ | アイシティ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%B3%E3%82%BF%E3%82%AF%E3%83%88%E3%81%AE%E3%82%A2%E3%82%A4%E3%82%B7%E3%83%86%E3%82%A3 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%B3%E3%82%BF%E3%82%AF%E3%83%88%E3%81%AE%E3%82%A2%E3%82%A4%E3%82%B7%E3%83%86%E3%82%A3&c=apps&hl=ja |  |
| 7 | コート・ダジュール | コート・ダジュール | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%BC%E3%83%88%E3%83%BB%E3%83%80%E3%82%B8%E3%83%A5%E3%83%BC%E3%83%AB | https://play.google.com/store/search?q=%E3%82%B3%E3%83%BC%E3%83%88%E3%83%BB%E3%83%80%E3%82%B8%E3%83%A5%E3%83%BC%E3%83%AB&c=apps&hl=ja |  |
| 8 | スタジオアリス | スタジオアリス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%82%A2%E3%83%AA%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%82%A2%E3%83%AA%E3%82%B9&c=apps&hl=ja |  |
| 9 | パリミキ | 三城ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%91%E3%83%AA%E3%83%9F%E3%82%AD | https://play.google.com/store/search?q=%E3%83%91%E3%83%AA%E3%83%9F%E3%82%AD&c=apps&hl=ja |  |
| 10 | ホワイト急便 | ホワイト急便 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%AF%E3%82%A4%E3%83%88%E6%80%A5%E4%BE%BF | https://play.google.com/store/search?q=%E3%83%9B%E3%83%AF%E3%82%A4%E3%83%88%E6%80%A5%E4%BE%BF&c=apps&hl=ja |  |
| 11 | ポニークリーニング | ポニークリーニング | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%83%8B%E3%83%BC%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%83%9D%E3%83%8B%E3%83%BC%E3%82%AF%E3%83%AA%E3%83%BC%E3%83%8B%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 12 | メガネスーパー | ビジョナリーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A1%E3%82%AC%E3%83%8D%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC | https://play.google.com/store/search?q=%E3%83%A1%E3%82%AC%E3%83%8D%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC&c=apps&hl=ja |  |
| 13 | メガネトップ | メガネトップ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A1%E3%82%AC%E3%83%8D%E3%83%88%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%83%A1%E3%82%AC%E3%83%8D%E3%83%88%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 14 | ラヴィ・ファクトリー | ラヴィ・ファクトリー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%83%B4%E3%82%A3%E3%83%BB%E3%83%95%E3%82%A1%E3%82%AF%E3%83%88%E3%83%AA%E3%83%BC | https://play.google.com/store/search?q=%E3%83%A9%E3%83%B4%E3%82%A3%E3%83%BB%E3%83%95%E3%82%A1%E3%82%AF%E3%83%88%E3%83%AA%E3%83%BC&c=apps&hl=ja |  |
| 15 | リネット | リネット | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AA%E3%83%8D%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%AA%E3%83%8D%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 16 | ワタベウェディング | ワタベウェディング | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%82%BF%E3%83%99%E3%82%A6%E3%82%A7%E3%83%87%E3%82%A3%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%83%AF%E3%82%BF%E3%83%99%E3%82%A6%E3%82%A7%E3%83%87%E3%82%A3%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 17 | 快活CLUB | 快活CLUB | 要確認 | https://apps.apple.com/jp/search?term=%E5%BF%AB%E6%B4%BBCLUB | https://play.google.com/store/search?q=%E5%BF%AB%E6%B4%BBCLUB&c=apps&hl=ja |  |
| 18 | 白洋舍 | 白洋舍 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E6%B4%8B%E8%88%8D | https://play.google.com/store/search?q=%E7%99%BD%E6%B4%8B%E8%88%8D&c=apps&hl=ja |  |

### 5.3 アパレル・ファッション（93件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 23区 | オンワード | 要確認 | https://apps.apple.com/jp/search?term=23%E5%8C%BA | https://play.google.com/store/search?q=23%E5%8C%BA&c=apps&hl=ja |  |
| 2 | 3COINS | パルグループホールディングス | 要確認 | https://apps.apple.com/jp/search?term=3COINS | https://play.google.com/store/search?q=3COINS&c=apps&hl=ja |  |
| 3 | 3COINS Plus | パル | 要確認 | https://apps.apple.com/jp/search?term=3COINS%20Plus | https://play.google.com/store/search?q=3COINS%20Plus&c=apps&hl=ja |  |
| 4 | ABC-MART | エービーシー・マート | 要確認 | https://apps.apple.com/jp/search?term=ABC-MART | https://play.google.com/store/search?q=ABC-MART&c=apps&hl=ja |  |
| 5 | ABC-MART GRAND STAGE | ABC-MART | 要確認 | https://apps.apple.com/jp/search?term=ABC-MART%20GRAND%20STAGE | https://play.google.com/store/search?q=ABC-MART%20GRAND%20STAGE&c=apps&hl=ja |  |
| 6 | ABCマート | ABC-MART | 要確認 | https://apps.apple.com/jp/search?term=ABC%E3%83%9E%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=ABC%E3%83%9E%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 7 | AMERICAN HOLIC | ストライプインターナショナル | 要確認 | https://apps.apple.com/jp/search?term=AMERICAN%20HOLIC | https://play.google.com/store/search?q=AMERICAN%20HOLIC&c=apps&hl=ja |  |
| 8 | AOKI | AOKIホールディングス | 要確認 | https://apps.apple.com/jp/search?term=AOKI | https://play.google.com/store/search?q=AOKI&c=apps&hl=ja |  |
| 9 | ASBee | ジーフット | 要確認 | https://apps.apple.com/jp/search?term=ASBee | https://play.google.com/store/search?q=ASBee&c=apps&hl=ja |  |
| 10 | Avail | しまむら | 要確認 | https://apps.apple.com/jp/search?term=Avail | https://play.google.com/store/search?q=Avail&c=apps&hl=ja |  |
| 11 | BAYFLOW | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=BAYFLOW | https://play.google.com/store/search?q=BAYFLOW&c=apps&hl=ja |  |
| 12 | BEAMS | ビームス | 要確認 | https://apps.apple.com/jp/search?term=BEAMS | https://play.google.com/store/search?q=BEAMS&c=apps&hl=ja |  |
| 13 | CELFORD | マッシュホールディングス | 要確認 | https://apps.apple.com/jp/search?term=CELFORD | https://play.google.com/store/search?q=CELFORD&c=apps&hl=ja |  |
| 14 | CIAOPANIC | パル | 要確認 | https://apps.apple.com/jp/search?term=CIAOPANIC | https://play.google.com/store/search?q=CIAOPANIC&c=apps&hl=ja |  |
| 15 | COCO DEAL | マッシュホールディングス | 要確認 | https://apps.apple.com/jp/search?term=COCO%20DEAL | https://play.google.com/store/search?q=COCO%20DEAL&c=apps&hl=ja |  |
| 16 | Deuxieme Classe | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=Deuxieme%20Classe | https://play.google.com/store/search?q=Deuxieme%20Classe&c=apps&hl=ja |  |
| 17 | Discoat | パル | 要確認 | https://apps.apple.com/jp/search?term=Discoat | https://play.google.com/store/search?q=Discoat&c=apps&hl=ja |  |
| 18 | EDIT.FOR LULU | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=EDIT.FOR%20LULU | https://play.google.com/store/search?q=EDIT.FOR%20LULU&c=apps&hl=ja |  |
| 19 | FRAY I.D | マッシュホールディングス | 要確認 | https://apps.apple.com/jp/search?term=FRAY%20I.D | https://play.google.com/store/search?q=FRAY%20I.D&c=apps&hl=ja |  |
| 20 | FREAK'S STORE | フリークス ストア | 要確認 | https://apps.apple.com/jp/search?term=FREAK%27S%20STORE | https://play.google.com/store/search?q=FREAK%27S%20STORE&c=apps&hl=ja |  |
| 21 | GLOBAL WORK | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=GLOBAL%20WORK | https://play.google.com/store/search?q=GLOBAL%20WORK&c=apps&hl=ja |  |
| 22 | GU | ジーユー | 要確認 | https://apps.apple.com/jp/search?term=GU | https://play.google.com/store/search?q=GU&c=apps&hl=ja |  |
| 23 | Green Parks | ストライプインターナショナル | 要確認 | https://apps.apple.com/jp/search?term=Green%20Parks | https://play.google.com/store/search?q=Green%20Parks&c=apps&hl=ja |  |
| 24 | H&M | H&M | 確認済み | https://apps.apple.com/jp/app/h-m/id834465911 | https://play.google.com/store/search?q=H%26M&c=apps&hl=ja | 会員、クーポン、EC |
| 25 | Helmut Lang | リンク・セオリー | 要確認 | https://apps.apple.com/jp/search?term=Helmut%20Lang | https://play.google.com/store/search?q=Helmut%20Lang&c=apps&hl=ja |  |
| 26 | Honeys | ハニーズホールディングス | 要確認 | https://apps.apple.com/jp/search?term=Honeys | https://play.google.com/store/search?q=Honeys&c=apps&hl=ja |  |
| 27 | ICB | オンワード | 要確認 | https://apps.apple.com/jp/search?term=ICB | https://play.google.com/store/search?q=ICB&c=apps&hl=ja |  |
| 28 | IENA | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=IENA | https://play.google.com/store/search?q=IENA&c=apps&hl=ja |  |
| 29 | JILLSTUART | オンワード | 要確認 | https://apps.apple.com/jp/search?term=JILLSTUART | https://play.google.com/store/search?q=JILLSTUART&c=apps&hl=ja |  |
| 30 | JOURNAL STANDARD | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=JOURNAL%20STANDARD | https://play.google.com/store/search?q=JOURNAL%20STANDARD&c=apps&hl=ja |  |
| 31 | JOURNAL STANDARD relume | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=JOURNAL%20STANDARD%20relume | https://play.google.com/store/search?q=JOURNAL%20STANDARD%20relume&c=apps&hl=ja |  |
| 32 | KBF | アーバンリサーチ | 要確認 | https://apps.apple.com/jp/search?term=KBF | https://play.google.com/store/search?q=KBF&c=apps&hl=ja |  |
| 33 | LEPSIM | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=LEPSIM | https://play.google.com/store/search?q=LEPSIM&c=apps&hl=ja |  |
| 34 | LOWRYS FARM | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=LOWRYS%20FARM | https://play.google.com/store/search?q=LOWRYS%20FARM&c=apps&hl=ja |  |
| 35 | MARK STYLER | マークスタイラー | 要確認 | https://apps.apple.com/jp/search?term=MARK%20STYLER | https://play.google.com/store/search?q=MARK%20STYLER&c=apps&hl=ja |  |
| 36 | PAL CLOSET | パルグループホールディングス | 要確認 | https://apps.apple.com/jp/search?term=PAL%20CLOSET | https://play.google.com/store/search?q=PAL%20CLOSET&c=apps&hl=ja |  |
| 37 | PLST | ファーストリテイリング | 要確認 | https://apps.apple.com/jp/search?term=PLST | https://play.google.com/store/search?q=PLST&c=apps&hl=ja |  |
| 38 | Right-on | ライトオン | 要確認 | https://apps.apple.com/jp/search?term=Right-on | https://play.google.com/store/search?q=Right-on&c=apps&hl=ja |  |
| 39 | SHIPS | シップス | 要確認 | https://apps.apple.com/jp/search?term=SHIPS | https://play.google.com/store/search?q=SHIPS&c=apps&hl=ja |  |
| 40 | SHOES FIT | ABC-MART | 要確認 | https://apps.apple.com/jp/search?term=SHOES%20FIT | https://play.google.com/store/search?q=SHOES%20FIT&c=apps&hl=ja |  |
| 41 | SNIDEL | マッシュホールディングス | 要確認 | https://apps.apple.com/jp/search?term=SNIDEL | https://play.google.com/store/search?q=SNIDEL&c=apps&hl=ja |  |
| 42 | SUIT SELECT | コナカ | 要確認 | https://apps.apple.com/jp/search?term=SUIT%20SELECT | https://play.google.com/store/search?q=SUIT%20SELECT&c=apps&hl=ja |  |
| 43 | Sonny Label | アーバンリサーチ | 要確認 | https://apps.apple.com/jp/search?term=Sonny%20Label | https://play.google.com/store/search?q=Sonny%20Label&c=apps&hl=ja |  |
| 44 | Spick and Span | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=Spick%20and%20Span | https://play.google.com/store/search?q=Spick%20and%20Span&c=apps&hl=ja |  |
| 45 | TOKYO BASE | TOKYO BASE | 要確認 | https://apps.apple.com/jp/search?term=TOKYO%20BASE | https://play.google.com/store/search?q=TOKYO%20BASE&c=apps&hl=ja |  |
| 46 | TOMORROWLAND | トゥモローランド | 要確認 | https://apps.apple.com/jp/search?term=TOMORROWLAND | https://play.google.com/store/search?q=TOMORROWLAND&c=apps&hl=ja |  |
| 47 | TSI | TSIホールディングス | 要確認 | https://apps.apple.com/jp/search?term=TSI | https://play.google.com/store/search?q=TSI&c=apps&hl=ja |  |
| 48 | Theory | リンク・セオリー・ジャパン | 要確認 | https://apps.apple.com/jp/search?term=Theory | https://play.google.com/store/search?q=Theory&c=apps&hl=ja |  |
| 49 | UNITED ARROWS | ユナイテッドアローズ | 要確認 | https://apps.apple.com/jp/search?term=UNITED%20ARROWS | https://play.google.com/store/search?q=UNITED%20ARROWS&c=apps&hl=ja |  |
| 50 | URBAN RESEARCH | アーバンリサーチ | 要確認 | https://apps.apple.com/jp/search?term=URBAN%20RESEARCH | https://play.google.com/store/search?q=URBAN%20RESEARCH&c=apps&hl=ja |  |
| 51 | WEGO | ウィゴー | 要確認 | https://apps.apple.com/jp/search?term=WEGO | https://play.google.com/store/search?q=WEGO&c=apps&hl=ja |  |
| 52 | ZARA | Inditex | 確認済み | https://apps.apple.com/jp/app/zara/id547951480 | https://play.google.com/store/search?q=ZARA&c=apps&hl=ja | カタログ、サイズ/在庫、店舗受取 |
| 53 | earth music&ecology | ストライプインターナショナル | 要確認 | https://apps.apple.com/jp/search?term=earth%20music%26ecology | https://play.google.com/store/search?q=earth%20music%26ecology&c=apps&hl=ja |  |
| 54 | nano・universe | TSIホールディングス | 要確認 | https://apps.apple.com/jp/search?term=nano%E3%83%BBuniverse | https://play.google.com/store/search?q=nano%E3%83%BBuniverse&c=apps&hl=ja |  |
| 55 | niko and ... | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=niko%20and%20... | https://play.google.com/store/search?q=niko%20and%20...&c=apps&hl=ja |  |
| 56 | studio CLIP | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=studio%20CLIP | https://play.google.com/store/search?q=studio%20CLIP&c=apps&hl=ja |  |
| 57 | しまむら | しまむら | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%97%E3%81%BE%E3%82%80%E3%82%89 | https://play.google.com/store/search?q=%E3%81%97%E3%81%BE%E3%82%80%E3%82%89&c=apps&hl=ja |  |
| 58 | はるやま | はるやまホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AF%E3%82%8B%E3%82%84%E3%81%BE | https://play.google.com/store/search?q=%E3%81%AF%E3%82%8B%E3%82%84%E3%81%BE&c=apps&hl=ja |  |
| 59 | アダストリア | アダストリア | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%80%E3%82%B9%E3%83%88%E3%83%AA%E3%82%A2 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%80%E3%82%B9%E3%83%88%E3%83%AA%E3%82%A2&c=apps&hl=ja |  |
| 60 | アベイル | しまむら | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%99%E3%82%A4%E3%83%AB | https://play.google.com/store/search?q=%E3%82%A2%E3%83%99%E3%82%A4%E3%83%AB&c=apps&hl=ja |  |
| 61 | アーバンリサーチ ドアーズ | アーバンリサーチ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%BC%E3%83%90%E3%83%B3%E3%83%AA%E3%82%B5%E3%83%BC%E3%83%81%20%E3%83%89%E3%82%A2%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=%E3%82%A2%E3%83%BC%E3%83%90%E3%83%B3%E3%83%AA%E3%82%B5%E3%83%BC%E3%83%81%20%E3%83%89%E3%82%A2%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 62 | オンワード | オンワードホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%B3%E3%83%AF%E3%83%BC%E3%83%89 | https://play.google.com/store/search?q=%E3%82%AA%E3%83%B3%E3%83%AF%E3%83%BC%E3%83%89&c=apps&hl=ja |  |
| 63 | キャン | キャン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%A3%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%A3%E3%83%B3&c=apps&hl=ja |  |
| 64 | クロスプラス | クロスプラス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%AD%E3%82%B9%E3%83%97%E3%83%A9%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%AF%E3%83%AD%E3%82%B9%E3%83%97%E3%83%A9%E3%82%B9&c=apps&hl=ja |  |
| 65 | コナカ | コナカ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%8A%E3%82%AB | https://play.google.com/store/search?q=%E3%82%B3%E3%83%8A%E3%82%AB&c=apps&hl=ja |  |
| 66 | シャンブル | しまむら | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B7%E3%83%A3%E3%83%B3%E3%83%96%E3%83%AB | https://play.google.com/store/search?q=%E3%82%B7%E3%83%A3%E3%83%B3%E3%83%96%E3%83%AB&c=apps&hl=ja |  |
| 67 | ジーンズメイト | ジーンズメイト | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%BC%E3%83%B3%E3%82%BA%E3%83%A1%E3%82%A4%E3%83%88 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%BC%E3%83%B3%E3%82%BA%E3%83%A1%E3%82%A4%E3%83%88&c=apps&hl=ja |  |
| 68 | タカキュー | タカキュー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BF%E3%82%AB%E3%82%AD%E3%83%A5%E3%83%BC | https://play.google.com/store/search?q=%E3%82%BF%E3%82%AB%E3%82%AD%E3%83%A5%E3%83%BC&c=apps&hl=ja |  |
| 69 | ディバロ | しまむら | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%87%E3%82%A3%E3%83%90%E3%83%AD | https://play.google.com/store/search?q=%E3%83%87%E3%82%A3%E3%83%90%E3%83%AD&c=apps&hl=ja |  |
| 70 | トイザらス | トイザらス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%88%E3%82%A4%E3%82%B6%E3%82%89%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%88%E3%82%A4%E3%82%B6%E3%82%89%E3%82%B9&c=apps&hl=ja |  |
| 71 | ハニーズ | ハニーズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%8B%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=%E3%83%8F%E3%83%8B%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 72 | バースデイ | しまむら | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%83%BC%E3%82%B9%E3%83%87%E3%82%A4 | https://play.google.com/store/search?q=%E3%83%90%E3%83%BC%E3%82%B9%E3%83%87%E3%82%A4&c=apps&hl=ja |  |
| 73 | パル | パルグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%91%E3%83%AB | https://play.google.com/store/search?q=%E3%83%91%E3%83%AB&c=apps&hl=ja |  |
| 74 | ビューティ&ユース | ユナイテッドアローズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%83%A5%E3%83%BC%E3%83%86%E3%82%A3%26%E3%83%A6%E3%83%BC%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%93%E3%83%A5%E3%83%BC%E3%83%86%E3%82%A3%26%E3%83%A6%E3%83%BC%E3%82%B9&c=apps&hl=ja |  |
| 75 | プチプラのあや | しまむら関連 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%81%E3%83%97%E3%83%A9%E3%81%AE%E3%81%82%E3%82%84 | https://play.google.com/store/search?q=%E3%83%97%E3%83%81%E3%83%97%E3%83%A9%E3%81%AE%E3%81%82%E3%82%84&c=apps&hl=ja |  |
| 76 | ベイクルーズ | ベイクルーズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%82%A4%E3%82%AF%E3%83%AB%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=%E3%83%99%E3%82%A4%E3%82%AF%E3%83%AB%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 77 | ベビーザらス | トイザらス・ベビーザらス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%83%93%E3%83%BC%E3%82%B6%E3%82%89%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%99%E3%83%93%E3%83%BC%E3%82%B6%E3%82%89%E3%82%B9&c=apps&hl=ja |  |
| 78 | マックハウス | マックハウス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%83%E3%82%AF%E3%83%8F%E3%82%A6%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%83%E3%82%AF%E3%83%8F%E3%82%A6%E3%82%B9&c=apps&hl=ja |  |
| 79 | マッシュスタイルラボ | マッシュホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%83%E3%82%B7%E3%83%A5%E3%82%B9%E3%82%BF%E3%82%A4%E3%83%AB%E3%83%A9%E3%83%9C | https://play.google.com/store/search?q=%E3%83%9E%E3%83%83%E3%82%B7%E3%83%A5%E3%82%B9%E3%82%BF%E3%82%A4%E3%83%AB%E3%83%A9%E3%83%9C&c=apps&hl=ja |  |
| 80 | ミドリヤ | ジーフット | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%83%89%E3%83%AA%E3%83%A4 | https://play.google.com/store/search?q=%E3%83%9F%E3%83%89%E3%83%AA%E3%83%A4&c=apps&hl=ja |  |
| 81 | ユナイテッドアローズ グリーンレーベル | ユナイテッドアローズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%8A%E3%82%A4%E3%83%86%E3%83%83%E3%83%89%E3%82%A2%E3%83%AD%E3%83%BC%E3%82%BA%20%E3%82%B0%E3%83%AA%E3%83%BC%E3%83%B3%E3%83%AC%E3%83%BC%E3%83%99%E3%83%AB | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8A%E3%82%A4%E3%83%86%E3%83%83%E3%83%89%E3%82%A2%E3%83%AD%E3%83%BC%E3%82%BA%20%E3%82%B0%E3%83%AA%E3%83%BC%E3%83%B3%E3%83%AC%E3%83%BC%E3%83%99%E3%83%AB&c=apps&hl=ja |  |
| 82 | ユニクロ | ファーストリテイリング | 確認済み | https://apps.apple.com/jp/app/uniqlo%E3%82%A2%E3%83%97%E3%83%AA-%E3%83%A6%E3%83%8B%E3%82%AF%E3%83%AD%E3%82%A2%E3%83%97%E3%83%AA/id460989014 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8B%E3%82%AF%E3%83%AD&c=apps&hl=ja | 商品検索、在庫、会員、EC、店舗 |
| 83 | ライトオン | ライトオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%82%A4%E3%83%88%E3%82%AA%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A9%E3%82%A4%E3%83%88%E3%82%AA%E3%83%B3&c=apps&hl=ja |  |
| 84 | ラシェリエ | パル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%82%B7%E3%82%A7%E3%83%AA%E3%82%A8 | https://play.google.com/store/search?q=%E3%83%A9%E3%82%B7%E3%82%A7%E3%83%AA%E3%82%A8&c=apps&hl=ja |  |
| 85 | ロペ | ロペ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%9A | https://play.google.com/store/search?q=%E3%83%AD%E3%83%9A&c=apps&hl=ja |  |
| 86 | ロペピクニック | ロペピクニック | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%9A%E3%83%94%E3%82%AF%E3%83%8B%E3%83%83%E3%82%AF | https://play.google.com/store/search?q=%E3%83%AD%E3%83%9A%E3%83%94%E3%82%AF%E3%83%8B%E3%83%83%E3%82%AF&c=apps&hl=ja |  |
| 87 | ワークマン | ワークマン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%83%BC%E3%82%AF%E3%83%9E%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%AF%E3%83%BC%E3%82%AF%E3%83%9E%E3%83%B3&c=apps&hl=ja |  |
| 88 | ワールド | ワールド | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89 | https://play.google.com/store/search?q=%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89&c=apps&hl=ja |  |
| 89 | 洋服の青山 | 青山商事 | 要確認 | https://apps.apple.com/jp/search?term=%E6%B4%8B%E6%9C%8D%E3%81%AE%E9%9D%92%E5%B1%B1 | https://play.google.com/store/search?q=%E6%B4%8B%E6%9C%8D%E3%81%AE%E9%9D%92%E5%B1%B1&c=apps&hl=ja |  |
| 90 | 無印良品 | 良品計画 | 要確認 | https://apps.apple.com/jp/search?term=%E7%84%A1%E5%8D%B0%E8%89%AF%E5%93%81 | https://play.google.com/store/search?q=%E7%84%A1%E5%8D%B0%E8%89%AF%E5%93%81&c=apps&hl=ja |  |
| 91 | 組曲 | オンワード | 要確認 | https://apps.apple.com/jp/search?term=%E7%B5%84%E6%9B%B2 | https://play.google.com/store/search?q=%E7%B5%84%E6%9B%B2&c=apps&hl=ja |  |
| 92 | 組曲anySiS | オンワード | 要確認 | https://apps.apple.com/jp/search?term=%E7%B5%84%E6%9B%B2anySiS | https://play.google.com/store/search?q=%E7%B5%84%E6%9B%B2anySiS&c=apps&hl=ja |  |
| 93 | 西松屋 | 西松屋チェーン | 要確認 | https://apps.apple.com/jp/search?term=%E8%A5%BF%E6%9D%BE%E5%B1%8B | https://play.google.com/store/search?q=%E8%A5%BF%E6%9D%BE%E5%B1%8B&c=apps&hl=ja |  |

### 5.4 カフェ・珈琲（43件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Afternoon Tea | サザビーリーグ | 要確認 | https://apps.apple.com/jp/search?term=Afternoon%20Tea | https://play.google.com/store/search?q=Afternoon%20Tea&c=apps&hl=ja |  |
| 2 | Afternoon Tea LOVE&TABLE | サザビーリーグ | 要確認 | https://apps.apple.com/jp/search?term=Afternoon%20Tea%20LOVE%26TABLE | https://play.google.com/store/search?q=Afternoon%20Tea%20LOVE%26TABLE&c=apps&hl=ja |  |
| 3 | Afternoon Tea TEAROOM | サザビーリーグ | 要確認 | https://apps.apple.com/jp/search?term=Afternoon%20Tea%20TEAROOM | https://play.google.com/store/search?q=Afternoon%20Tea%20TEAROOM&c=apps&hl=ja |  |
| 4 | DEAN & DELUCA | ウェルカム | 要確認 | https://apps.apple.com/jp/search?term=DEAN%20%26%20DELUCA | https://play.google.com/store/search?q=DEAN%20%26%20DELUCA&c=apps&hl=ja |  |
| 5 | DEAN & DELUCA CAFE | ウェルカム | 要確認 | https://apps.apple.com/jp/search?term=DEAN%20%26%20DELUCA%20CAFE | https://play.google.com/store/search?q=DEAN%20%26%20DELUCA%20CAFE&c=apps&hl=ja |  |
| 6 | PEANUTS Cafe | サザビーリーグ | 要確認 | https://apps.apple.com/jp/search?term=PEANUTS%20Cafe | https://play.google.com/store/search?q=PEANUTS%20Cafe&c=apps&hl=ja |  |
| 7 | PRONTO | プロントコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=PRONTO | https://play.google.com/store/search?q=PRONTO&c=apps&hl=ja |  |
| 8 | WIRED CAFE | カジュアル・レストラン・システムズ | 要確認 | https://apps.apple.com/jp/search?term=WIRED%20CAFE | https://play.google.com/store/search?q=WIRED%20CAFE&c=apps&hl=ja |  |
| 9 | むさしの森珈琲 | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%80%E3%81%95%E3%81%97%E3%81%AE%E6%A3%AE%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E3%82%80%E3%81%95%E3%81%97%E3%81%AE%E6%A3%AE%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 10 | イノダコーヒ | イノダコーヒ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%83%8E%E3%83%80%E3%82%B3%E3%83%BC%E3%83%92 | https://play.google.com/store/search?q=%E3%82%A4%E3%83%8E%E3%83%80%E3%82%B3%E3%83%BC%E3%83%92&c=apps&hl=ja |  |
| 11 | エクセルシオール カフェ | ドトール・日レスホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%B7%E3%82%AA%E3%83%BC%E3%83%AB%20%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%B7%E3%82%AA%E3%83%BC%E3%83%AB%20%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 12 | エクセルシオールカフェ | ドトール・日レス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%B7%E3%82%AA%E3%83%BC%E3%83%AB%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%B7%E3%82%AA%E3%83%BC%E3%83%AB%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 13 | カフェ・ド・クリエ | C-United | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%95%E3%82%A7%E3%83%BB%E3%83%89%E3%83%BB%E3%82%AF%E3%83%AA%E3%82%A8 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%95%E3%82%A7%E3%83%BB%E3%83%89%E3%83%BB%E3%82%AF%E3%83%AA%E3%82%A8&c=apps&hl=ja |  |
| 14 | カフェ・ベローチェ | C-United | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%95%E3%82%A7%E3%83%BB%E3%83%99%E3%83%AD%E3%83%BC%E3%83%81%E3%82%A7 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%95%E3%82%A7%E3%83%BB%E3%83%99%E3%83%AD%E3%83%BC%E3%83%81%E3%82%A7&c=apps&hl=ja |  |
| 15 | コナズ珈琲 | トリドールホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%8A%E3%82%BA%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%8A%E3%82%BA%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 16 | コメダ和喫茶 おかげ庵 | コメダ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%A1%E3%83%80%E5%92%8C%E5%96%AB%E8%8C%B6%20%E3%81%8A%E3%81%8B%E3%81%92%E5%BA%B5 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%A1%E3%83%80%E5%92%8C%E5%96%AB%E8%8C%B6%20%E3%81%8A%E3%81%8B%E3%81%92%E5%BA%B5&c=apps&hl=ja |  |
| 17 | コメダ和喫茶おかげ庵 | コメダ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%A1%E3%83%80%E5%92%8C%E5%96%AB%E8%8C%B6%E3%81%8A%E3%81%8B%E3%81%92%E5%BA%B5 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%A1%E3%83%80%E5%92%8C%E5%96%AB%E8%8C%B6%E3%81%8A%E3%81%8B%E3%81%92%E5%BA%B5&c=apps&hl=ja |  |
| 18 | コメダ珈琲 | コメダ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 19 | コメダ珈琲店 | コメダホールディングス | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja | 会員証、店舗検索、クーポン、注文/商品、キャンペーン |
| 20 | サンマルク | サンマルク | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%9E%E3%83%AB%E3%82%AF | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%9E%E3%83%AB%E3%82%AF&c=apps&hl=ja |  |
| 21 | サンマルクカフェ | サンマルクホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%9E%E3%83%AB%E3%82%AF%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%9E%E3%83%AB%E3%82%AF%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 22 | スターバックス | スターバックス コーヒー ジャパン | 確認済み | https://apps.apple.com/jp/app/%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9-%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3%E5%85%AC%E5%BC%8F%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%97%E3%83%AA/id1113037275 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9&c=apps&hl=ja | 会員証/QR、Rewards、Mobile Order & Pay、Gift、店舗検索、Inbox |
| 23 | スターバックス リザーブ | スタバ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9%20%E3%83%AA%E3%82%B6%E3%83%BC%E3%83%96 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9%20%E3%83%AA%E3%82%B6%E3%83%BC%E3%83%96&c=apps&hl=ja |  |
| 24 | タリーズコーヒー | タリーズコーヒージャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BF%E3%83%AA%E3%83%BC%E3%82%BA%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC | https://play.google.com/store/search?q=%E3%82%BF%E3%83%AA%E3%83%BC%E3%82%BA%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC&c=apps&hl=ja |  |
| 25 | ツタヤカフェ | CCC | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%84%E3%82%BF%E3%83%A4%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%83%84%E3%82%BF%E3%83%A4%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 26 | ドトール | ドトール・日レス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%88%E3%83%BC%E3%83%AB | https://play.google.com/store/search?q=%E3%83%89%E3%83%88%E3%83%BC%E3%83%AB&c=apps&hl=ja |  |
| 27 | ドトールコーヒーショップ | ドトール・日レスホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%88%E3%83%BC%E3%83%AB%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%83%89%E3%83%88%E3%83%BC%E3%83%AB%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 28 | バケット | サンマルク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%82%B1%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%90%E3%82%B1%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 29 | ブルーボトルコーヒー | Blue Bottle Coffee Japan | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%96%E3%83%AB%E3%83%BC%E3%83%9C%E3%83%88%E3%83%AB%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC | https://play.google.com/store/search?q=%E3%83%96%E3%83%AB%E3%83%BC%E3%83%9C%E3%83%88%E3%83%AB%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC&c=apps&hl=ja |  |
| 30 | プロント | プロントコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88 | https://play.google.com/store/search?q=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88&c=apps&hl=ja |  |
| 31 | プロントカフェ | プロント | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 32 | ベローチェ | C-United | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%83%AD%E3%83%BC%E3%83%81%E3%82%A7 | https://play.google.com/store/search?q=%E3%83%99%E3%83%AD%E3%83%BC%E3%83%81%E3%82%A7&c=apps&hl=ja |  |
| 33 | ポールバセット | PB | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%83%BC%E3%83%AB%E3%83%90%E3%82%BB%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%9D%E3%83%BC%E3%83%AB%E3%83%90%E3%82%BB%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 34 | 上島珈琲 | UCC | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%8A%E5%B3%B6%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E4%B8%8A%E5%B3%B6%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 35 | 上島珈琲店 | UCCグループ | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%8A%E5%B3%B6%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E4%B8%8A%E5%B3%B6%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja |  |
| 36 | 倉式珈琲店 | サンマルクホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%80%89%E5%BC%8F%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E5%80%89%E5%BC%8F%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja |  |
| 37 | 客家珈琲店 | コメダ関連 | 要確認 | https://apps.apple.com/jp/search?term=%E5%AE%A2%E5%AE%B6%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E5%AE%A2%E5%AE%B6%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja |  |
| 38 | 星乃珈琲 | 日本レストランシステム | 要確認 | https://apps.apple.com/jp/search?term=%E6%98%9F%E4%B9%83%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E6%98%9F%E4%B9%83%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 39 | 星乃珈琲店 | 日本レストランシステム | 要確認 | https://apps.apple.com/jp/search?term=%E6%98%9F%E4%B9%83%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E6%98%9F%E4%B9%83%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja |  |
| 40 | 猿田彦珈琲 | 猿田彦珈琲 | 要確認 | https://apps.apple.com/jp/search?term=%E7%8C%BF%E7%94%B0%E5%BD%A6%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E7%8C%BF%E7%94%B0%E5%BD%A6%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 41 | 珈琲所コメダ珈琲店 | コメダ | 要確認 | https://apps.apple.com/jp/search?term=%E7%8F%88%E7%90%B2%E6%89%80%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2%E5%BA%97 | https://play.google.com/store/search?q=%E7%8F%88%E7%90%B2%E6%89%80%E3%82%B3%E3%83%A1%E3%83%80%E7%8F%88%E7%90%B2%E5%BA%97&c=apps&hl=ja |  |
| 42 | 珈琲館 | 珈琲館 | 要確認 | https://apps.apple.com/jp/search?term=%E7%8F%88%E7%90%B2%E9%A4%A8 | https://play.google.com/store/search?q=%E7%8F%88%E7%90%B2%E9%A4%A8&c=apps&hl=ja |  |
| 43 | 高倉町珈琲 | 高倉町珈琲 | 要確認 | https://apps.apple.com/jp/search?term=%E9%AB%98%E5%80%89%E7%94%BA%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E9%AB%98%E5%80%89%E7%94%BA%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |

### 5.5 グローバル小売（73件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 7-Eleven | 7-Eleven | 要確認 | https://apps.apple.com/jp/search?term=7-Eleven | https://play.google.com/store/search?q=7-Eleven&c=apps&hl=ja |  |
| 2 | ASOS | ASOS | 要確認 | https://apps.apple.com/jp/search?term=ASOS | https://play.google.com/store/search?q=ASOS&c=apps&hl=ja |  |
| 3 | Aldi | Aldi | 要確認 | https://apps.apple.com/jp/search?term=Aldi | https://play.google.com/store/search?q=Aldi&c=apps&hl=ja |  |
| 4 | Amazon | Amazon | 要確認 | https://apps.apple.com/jp/search?term=Amazon | https://play.google.com/store/search?q=Amazon&c=apps&hl=ja |  |
| 5 | Amazon Fresh | Amazon | 要確認 | https://apps.apple.com/jp/search?term=Amazon%20Fresh | https://play.google.com/store/search?q=Amazon%20Fresh&c=apps&hl=ja |  |
| 6 | Anthropologie | URBN | 要確認 | https://apps.apple.com/jp/search?term=Anthropologie | https://play.google.com/store/search?q=Anthropologie&c=apps&hl=ja |  |
| 7 | Apple Store | Apple | 要確認 | https://apps.apple.com/jp/search?term=Apple%20Store | https://play.google.com/store/search?q=Apple%20Store&c=apps&hl=ja |  |
| 8 | Bath & Body Works | Bath & Body Works | 要確認 | https://apps.apple.com/jp/search?term=Bath%20%26%20Body%20Works | https://play.google.com/store/search?q=Bath%20%26%20Body%20Works&c=apps&hl=ja |  |
| 9 | Bershka | Inditex | 要確認 | https://apps.apple.com/jp/search?term=Bershka | https://play.google.com/store/search?q=Bershka&c=apps&hl=ja |  |
| 10 | Best Buy | Best Buy | 要確認 | https://apps.apple.com/jp/search?term=Best%20Buy | https://play.google.com/store/search?q=Best%20Buy&c=apps&hl=ja |  |
| 11 | Bloomingdale's | Bloomingdale's | 要確認 | https://apps.apple.com/jp/search?term=Bloomingdale%27s | https://play.google.com/store/search?q=Bloomingdale%27s&c=apps&hl=ja |  |
| 12 | Boots | Boots | 要確認 | https://apps.apple.com/jp/search?term=Boots | https://play.google.com/store/search?q=Boots&c=apps&hl=ja |  |
| 13 | Carrefour | Carrefour | 要確認 | https://apps.apple.com/jp/search?term=Carrefour | https://play.google.com/store/search?q=Carrefour&c=apps&hl=ja |  |
| 14 | Columbia | Columbia | 要確認 | https://apps.apple.com/jp/search?term=Columbia | https://play.google.com/store/search?q=Columbia&c=apps&hl=ja |  |
| 15 | Costco | Costco | 要確認 | https://apps.apple.com/jp/search?term=Costco | https://play.google.com/store/search?q=Costco&c=apps&hl=ja |  |
| 16 | Crate & Barrel | Crate & Barrel | 要確認 | https://apps.apple.com/jp/search?term=Crate%20%26%20Barrel | https://play.google.com/store/search?q=Crate%20%26%20Barrel&c=apps&hl=ja |  |
| 17 | Decathlon | Decathlon | 要確認 | https://apps.apple.com/jp/search?term=Decathlon | https://play.google.com/store/search?q=Decathlon&c=apps&hl=ja |  |
| 18 | Dick's Sporting Goods | Dick's | 要確認 | https://apps.apple.com/jp/search?term=Dick%27s%20Sporting%20Goods | https://play.google.com/store/search?q=Dick%27s%20Sporting%20Goods&c=apps&hl=ja |  |
| 19 | Finish Line | Finish Line | 要確認 | https://apps.apple.com/jp/search?term=Finish%20Line | https://play.google.com/store/search?q=Finish%20Line&c=apps&hl=ja |  |
| 20 | Foot Locker | Foot Locker | 要確認 | https://apps.apple.com/jp/search?term=Foot%20Locker | https://play.google.com/store/search?q=Foot%20Locker&c=apps&hl=ja |  |
| 21 | Free People | URBN | 要確認 | https://apps.apple.com/jp/search?term=Free%20People | https://play.google.com/store/search?q=Free%20People&c=apps&hl=ja |  |
| 22 | Galeries Lafayette | Galeries Lafayette | 要確認 | https://apps.apple.com/jp/search?term=Galeries%20Lafayette | https://play.google.com/store/search?q=Galeries%20Lafayette&c=apps&hl=ja |  |
| 23 | Gap | Gap | 要確認 | https://apps.apple.com/jp/search?term=Gap | https://play.google.com/store/search?q=Gap&c=apps&hl=ja |  |
| 24 | H&M | H&M | 確認済み | https://apps.apple.com/jp/app/h-m/id834465911 | https://play.google.com/store/search?q=H%26M&c=apps&hl=ja | 会員、クーポン、EC |
| 25 | Harrods | Harrods | 要確認 | https://apps.apple.com/jp/search?term=Harrods | https://play.google.com/store/search?q=Harrods&c=apps&hl=ja |  |
| 26 | Holland & Barrett | H&B | 要確認 | https://apps.apple.com/jp/search?term=Holland%20%26%20Barrett | https://play.google.com/store/search?q=Holland%20%26%20Barrett&c=apps&hl=ja |  |
| 27 | Home Depot | Home Depot | 要確認 | https://apps.apple.com/jp/search?term=Home%20Depot | https://play.google.com/store/search?q=Home%20Depot&c=apps&hl=ja |  |
| 28 | Häagen-Dazs | Häagen-Dazs | 要確認 | https://apps.apple.com/jp/search?term=H%C3%A4agen-Dazs | https://play.google.com/store/search?q=H%C3%A4agen-Dazs&c=apps&hl=ja |  |
| 29 | IKEA | IKEA | 確認済み | https://apps.apple.com/jp/app/ikea/id1452164827 | https://play.google.com/store/search?q=IKEA&c=apps&hl=ja | 店舗マップ、在庫、リスト、スキャン |
| 30 | JD Sports | JD | 要確認 | https://apps.apple.com/jp/search?term=JD%20Sports | https://play.google.com/store/search?q=JD%20Sports&c=apps&hl=ja |  |
| 31 | John Lewis | John Lewis | 要確認 | https://apps.apple.com/jp/search?term=John%20Lewis | https://play.google.com/store/search?q=John%20Lewis&c=apps&hl=ja |  |
| 32 | L'Oréal | L'Oréal | 要確認 | https://apps.apple.com/jp/search?term=L%27Or%C3%A9al | https://play.google.com/store/search?q=L%27Or%C3%A9al&c=apps&hl=ja |  |
| 33 | LEGO | LEGO | 要確認 | https://apps.apple.com/jp/search?term=LEGO | https://play.google.com/store/search?q=LEGO&c=apps&hl=ja |  |
| 34 | LUSH | LUSH | 要確認 | https://apps.apple.com/jp/search?term=LUSH | https://play.google.com/store/search?q=LUSH&c=apps&hl=ja |  |
| 35 | Lidl | Lidl | 要確認 | https://apps.apple.com/jp/search?term=Lidl | https://play.google.com/store/search?q=Lidl&c=apps&hl=ja |  |
| 36 | Lowe's | Lowe's | 要確認 | https://apps.apple.com/jp/search?term=Lowe%27s | https://play.google.com/store/search?q=Lowe%27s&c=apps&hl=ja |  |
| 37 | Macy's | Macy's | 要確認 | https://apps.apple.com/jp/search?term=Macy%27s | https://play.google.com/store/search?q=Macy%27s&c=apps&hl=ja |  |
| 38 | Mango | Mango | 要確認 | https://apps.apple.com/jp/search?term=Mango | https://play.google.com/store/search?q=Mango&c=apps&hl=ja |  |
| 39 | Marks & Spencer | M&S | 要確認 | https://apps.apple.com/jp/search?term=Marks%20%26%20Spencer | https://play.google.com/store/search?q=Marks%20%26%20Spencer&c=apps&hl=ja |  |
| 40 | Massimo Dutti | Inditex | 要確認 | https://apps.apple.com/jp/search?term=Massimo%20Dutti | https://play.google.com/store/search?q=Massimo%20Dutti&c=apps&hl=ja |  |
| 41 | Muji | 良品計画 | 要確認 | https://apps.apple.com/jp/search?term=Muji | https://play.google.com/store/search?q=Muji&c=apps&hl=ja |  |
| 42 | Neiman Marcus | Neiman Marcus | 要確認 | https://apps.apple.com/jp/search?term=Neiman%20Marcus | https://play.google.com/store/search?q=Neiman%20Marcus&c=apps&hl=ja |  |
| 43 | Nike | Nike | 確認済み | https://apps.apple.com/jp/app/nike%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1095459556 | https://play.google.com/store/search?q=Nike&c=apps&hl=ja | 会員、商品、店舗スキャン、ブランド体験 |
| 44 | Nordstrom | Nordstrom | 要確認 | https://apps.apple.com/jp/search?term=Nordstrom | https://play.google.com/store/search?q=Nordstrom&c=apps&hl=ja |  |
| 45 | Old Navy | Old Navy | 要確認 | https://apps.apple.com/jp/search?term=Old%20Navy | https://play.google.com/store/search?q=Old%20Navy&c=apps&hl=ja |  |
| 46 | Patagonia | Patagonia | 要確認 | https://apps.apple.com/jp/search?term=Patagonia | https://play.google.com/store/search?q=Patagonia&c=apps&hl=ja |  |
| 47 | Pottery Barn | Williams-Sonoma | 要確認 | https://apps.apple.com/jp/search?term=Pottery%20Barn | https://play.google.com/store/search?q=Pottery%20Barn&c=apps&hl=ja |  |
| 48 | Primark | Primark | 要確認 | https://apps.apple.com/jp/search?term=Primark | https://play.google.com/store/search?q=Primark&c=apps&hl=ja |  |
| 49 | Printemps | Printemps | 要確認 | https://apps.apple.com/jp/search?term=Printemps | https://play.google.com/store/search?q=Printemps&c=apps&hl=ja |  |
| 50 | Pull&Bear | Inditex | 要確認 | https://apps.apple.com/jp/search?term=Pull%26Bear | https://play.google.com/store/search?q=Pull%26Bear&c=apps&hl=ja |  |
| 51 | REI | REI | 要確認 | https://apps.apple.com/jp/search?term=REI | https://play.google.com/store/search?q=REI&c=apps&hl=ja |  |
| 52 | SHEIN | SHEIN | 要確認 | https://apps.apple.com/jp/search?term=SHEIN | https://play.google.com/store/search?q=SHEIN&c=apps&hl=ja |  |
| 53 | Sainsbury's | Sainsbury's | 要確認 | https://apps.apple.com/jp/search?term=Sainsbury%27s | https://play.google.com/store/search?q=Sainsbury%27s&c=apps&hl=ja |  |
| 54 | Saks Fifth Avenue | Saks | 要確認 | https://apps.apple.com/jp/search?term=Saks%20Fifth%20Avenue | https://play.google.com/store/search?q=Saks%20Fifth%20Avenue&c=apps&hl=ja |  |
| 55 | Samsung | Samsung | 要確認 | https://apps.apple.com/jp/search?term=Samsung | https://play.google.com/store/search?q=Samsung&c=apps&hl=ja |  |
| 56 | Selfridges | Selfridges | 要確認 | https://apps.apple.com/jp/search?term=Selfridges | https://play.google.com/store/search?q=Selfridges&c=apps&hl=ja |  |
| 57 | Sephora | Sephora | 要確認 | https://apps.apple.com/jp/search?term=Sephora | https://play.google.com/store/search?q=Sephora&c=apps&hl=ja |  |
| 58 | Stradivarius | Inditex | 要確認 | https://apps.apple.com/jp/search?term=Stradivarius | https://play.google.com/store/search?q=Stradivarius&c=apps&hl=ja |  |
| 59 | Target | Target | 要確認 | https://apps.apple.com/jp/search?term=Target | https://play.google.com/store/search?q=Target&c=apps&hl=ja |  |
| 60 | Tesco | Tesco | 要確認 | https://apps.apple.com/jp/search?term=Tesco | https://play.google.com/store/search?q=Tesco&c=apps&hl=ja |  |
| 61 | The North Face | VF | 要確認 | https://apps.apple.com/jp/search?term=The%20North%20Face | https://play.google.com/store/search?q=The%20North%20Face&c=apps&hl=ja |  |
| 62 | Trader Joe's | Trader Joe's | 要確認 | https://apps.apple.com/jp/search?term=Trader%20Joe%27s | https://play.google.com/store/search?q=Trader%20Joe%27s&c=apps&hl=ja |  |
| 63 | UNIQLO | ファーストリテイリング | 確認済み | https://apps.apple.com/jp/app/uniqlo%E3%82%A2%E3%83%97%E3%83%AA-%E3%83%A6%E3%83%8B%E3%82%AF%E3%83%AD%E3%82%A2%E3%83%97%E3%83%AA/id460989014 | https://play.google.com/store/search?q=UNIQLO&c=apps&hl=ja | 商品検索、在庫、会員、EC、店舗 |
| 64 | Ulta Beauty | Ulta Beauty | 要確認 | https://apps.apple.com/jp/search?term=Ulta%20Beauty | https://play.google.com/store/search?q=Ulta%20Beauty&c=apps&hl=ja |  |
| 65 | Uniqlo US | Fast Retailing | 要確認 | https://apps.apple.com/jp/search?term=Uniqlo%20US | https://play.google.com/store/search?q=Uniqlo%20US&c=apps&hl=ja |  |
| 66 | Urban Outfitters | URBN | 要確認 | https://apps.apple.com/jp/search?term=Urban%20Outfitters | https://play.google.com/store/search?q=Urban%20Outfitters&c=apps&hl=ja |  |
| 67 | Walmart | Walmart | 要確認 | https://apps.apple.com/jp/search?term=Walmart | https://play.google.com/store/search?q=Walmart&c=apps&hl=ja |  |
| 68 | West Elm | West Elm | 要確認 | https://apps.apple.com/jp/search?term=West%20Elm | https://play.google.com/store/search?q=West%20Elm&c=apps&hl=ja |  |
| 69 | Whole Foods Market | Amazon | 要確認 | https://apps.apple.com/jp/search?term=Whole%20Foods%20Market | https://play.google.com/store/search?q=Whole%20Foods%20Market&c=apps&hl=ja |  |
| 70 | Williams Sonoma | Williams-Sonoma | 要確認 | https://apps.apple.com/jp/search?term=Williams%20Sonoma | https://play.google.com/store/search?q=Williams%20Sonoma&c=apps&hl=ja |  |
| 71 | ZARA | Inditex | 確認済み | https://apps.apple.com/jp/app/zara/id547951480 | https://play.google.com/store/search?q=ZARA&c=apps&hl=ja | カタログ、サイズ/在庫、店舗受取 |
| 72 | adidas | adidas | 確認済み | https://apps.apple.com/jp/app/adidas-%E3%82%B9%E3%83%8B%E3%83%BC%E3%82%AB%E3%83%BC%E3%81%A8%E3%82%A2%E3%83%91%E3%83%AC%E3%83%AB%E3%81%AE%E3%82%B7%E3%83%A7%E3%83%83%E3%83%94%E3%83%B3%E3%82%B0%E3%82%A2%E3%83%97%E3%83%AA/id1266591536 | https://play.google.com/store/search?q=adidas&c=apps&hl=ja | 会員、EC、店舗 |
| 73 | lululemon | lululemon | 要確認 | https://apps.apple.com/jp/search?term=lululemon | https://play.google.com/store/search?q=lululemon&c=apps&hl=ja |  |

### 5.6 グローバル飲食（40件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Burger King | Burger King | 要確認 | https://apps.apple.com/jp/search?term=Burger%20King | https://play.google.com/store/search?q=Burger%20King&c=apps&hl=ja |  |
| 2 | Café Nero | Caffè Nero | 要確認 | https://apps.apple.com/jp/search?term=Caf%C3%A9%20Nero | https://play.google.com/store/search?q=Caf%C3%A9%20Nero&c=apps&hl=ja |  |
| 3 | Cava | Cava | 要確認 | https://apps.apple.com/jp/search?term=Cava | https://play.google.com/store/search?q=Cava&c=apps&hl=ja |  |
| 4 | Chick-fil-A | Chick-fil-A | 要確認 | https://apps.apple.com/jp/search?term=Chick-fil-A | https://play.google.com/store/search?q=Chick-fil-A&c=apps&hl=ja |  |
| 5 | Chipotle | Chipotle | 確認済み | https://apps.apple.com/us/app/chipotle/id327228455 | https://play.google.com/store/search?q=Chipotle&c=apps&hl=ja | Order/Rewards、スキャン会員 |
| 6 | Costa Coffee | Costa Coffee | 要確認 | https://apps.apple.com/jp/search?term=Costa%20Coffee | https://play.google.com/store/search?q=Costa%20Coffee&c=apps&hl=ja |  |
| 7 | Domino's | Domino's | 要確認 | https://apps.apple.com/jp/search?term=Domino%27s | https://play.google.com/store/search?q=Domino%27s&c=apps&hl=ja |  |
| 8 | Domino's Pizza | Domino's | 要確認 | https://apps.apple.com/jp/search?term=Domino%27s%20Pizza | https://play.google.com/store/search?q=Domino%27s%20Pizza&c=apps&hl=ja |  |
| 9 | Dunkin' | Dunkin' | 確認済み | https://apps.apple.com/us/app/dunkin/id1056813463 | https://play.google.com/store/search?q=Dunkin%27&c=apps&hl=ja | Order ahead、Dunkin Rewards |
| 10 | Dutch Bros | Dutch Bros | 要確認 | https://apps.apple.com/jp/search?term=Dutch%20Bros | https://play.google.com/store/search?q=Dutch%20Bros&c=apps&hl=ja |  |
| 11 | Eataly | Eataly | 要確認 | https://apps.apple.com/jp/search?term=Eataly | https://play.google.com/store/search?q=Eataly&c=apps&hl=ja |  |
| 12 | Five Guys | Five Guys | 要確認 | https://apps.apple.com/jp/search?term=Five%20Guys | https://play.google.com/store/search?q=Five%20Guys&c=apps&hl=ja |  |
| 13 | Greggs | Greggs | 要確認 | https://apps.apple.com/jp/search?term=Greggs | https://play.google.com/store/search?q=Greggs&c=apps&hl=ja |  |
| 14 | In-N-Out Burger | In-N-Out | 要確認 | https://apps.apple.com/jp/search?term=In-N-Out%20Burger | https://play.google.com/store/search?q=In-N-Out%20Burger&c=apps&hl=ja |  |
| 15 | Itsu | Itsu | 要確認 | https://apps.apple.com/jp/search?term=Itsu | https://play.google.com/store/search?q=Itsu&c=apps&hl=ja |  |
| 16 | KFC | KFC | 要確認 | https://apps.apple.com/jp/search?term=KFC | https://play.google.com/store/search?q=KFC&c=apps&hl=ja |  |
| 17 | Krispy Kreme | Krispy Kreme | 要確認 | https://apps.apple.com/jp/search?term=Krispy%20Kreme | https://play.google.com/store/search?q=Krispy%20Kreme&c=apps&hl=ja |  |
| 18 | Leon | Leon | 要確認 | https://apps.apple.com/jp/search?term=Leon | https://play.google.com/store/search?q=Leon&c=apps&hl=ja |  |
| 19 | Little Caesars | Little Caesars | 要確認 | https://apps.apple.com/jp/search?term=Little%20Caesars | https://play.google.com/store/search?q=Little%20Caesars&c=apps&hl=ja |  |
| 20 | McDonald's | McDonald's | 確認済み | https://apps.apple.com/us/app/mcdonalds/id922103212 | https://play.google.com/store/search?q=McDonald%27s&c=apps&hl=ja | US版モバイルオーダー・特典 |
| 21 | Nando's | Nando's | 要確認 | https://apps.apple.com/jp/search?term=Nando%27s | https://play.google.com/store/search?q=Nando%27s&c=apps&hl=ja |  |
| 22 | Olive Garden | Darden | 要確認 | https://apps.apple.com/jp/search?term=Olive%20Garden | https://play.google.com/store/search?q=Olive%20Garden&c=apps&hl=ja |  |
| 23 | Panda Express | Panda Express | 要確認 | https://apps.apple.com/jp/search?term=Panda%20Express | https://play.google.com/store/search?q=Panda%20Express&c=apps&hl=ja |  |
| 24 | Panera Bread | Panera | 要確認 | https://apps.apple.com/jp/search?term=Panera%20Bread | https://play.google.com/store/search?q=Panera%20Bread&c=apps&hl=ja |  |
| 25 | Papa John's | Papa John's | 要確認 | https://apps.apple.com/jp/search?term=Papa%20John%27s | https://play.google.com/store/search?q=Papa%20John%27s&c=apps&hl=ja |  |
| 26 | Peet's Coffee | Peet's | 要確認 | https://apps.apple.com/jp/search?term=Peet%27s%20Coffee | https://play.google.com/store/search?q=Peet%27s%20Coffee&c=apps&hl=ja |  |
| 27 | Pizza Hut | Pizza Hut | 要確認 | https://apps.apple.com/jp/search?term=Pizza%20Hut | https://play.google.com/store/search?q=Pizza%20Hut&c=apps&hl=ja |  |
| 28 | PizzaExpress | PizzaExpress | 要確認 | https://apps.apple.com/jp/search?term=PizzaExpress | https://play.google.com/store/search?q=PizzaExpress&c=apps&hl=ja |  |
| 29 | Popeyes | Popeyes | 要確認 | https://apps.apple.com/jp/search?term=Popeyes | https://play.google.com/store/search?q=Popeyes&c=apps&hl=ja |  |
| 30 | Pret A Manger | Pret | 要確認 | https://apps.apple.com/jp/search?term=Pret%20A%20Manger | https://play.google.com/store/search?q=Pret%20A%20Manger&c=apps&hl=ja |  |
| 31 | Shake Shack | Shake Shack | 要確認 | https://apps.apple.com/jp/search?term=Shake%20Shack | https://play.google.com/store/search?q=Shake%20Shack&c=apps&hl=ja |  |
| 32 | Starbucks | Starbucks | 確認済み | https://apps.apple.com/us/app/starbucks/id331177714 | https://play.google.com/store/search?q=Starbucks&c=apps&hl=ja | US版: Order ahead、Rewards、Pay、Gift、店舗 |
| 33 | Subway | Subway | 要確認 | https://apps.apple.com/jp/search?term=Subway | https://play.google.com/store/search?q=Subway&c=apps&hl=ja |  |
| 34 | Sweetgreen | Sweetgreen | 要確認 | https://apps.apple.com/jp/search?term=Sweetgreen | https://play.google.com/store/search?q=Sweetgreen&c=apps&hl=ja |  |
| 35 | Taco Bell | Taco Bell | 要確認 | https://apps.apple.com/jp/search?term=Taco%20Bell | https://play.google.com/store/search?q=Taco%20Bell&c=apps&hl=ja |  |
| 36 | Tim Hortons | Tim Hortons | 要確認 | https://apps.apple.com/jp/search?term=Tim%20Hortons | https://play.google.com/store/search?q=Tim%20Hortons&c=apps&hl=ja |  |
| 37 | Wagamama | Wagamama | 要確認 | https://apps.apple.com/jp/search?term=Wagamama | https://play.google.com/store/search?q=Wagamama&c=apps&hl=ja |  |
| 38 | Wendy's | Wendy's | 確認済み | https://apps.apple.com/us/app/wendys/id540518599 | https://play.google.com/store/search?q=Wendy%27s&c=apps&hl=ja | Offers、Rewards、Delivery |
| 39 | Wingstop | Wingstop | 要確認 | https://apps.apple.com/jp/search?term=Wingstop | https://play.google.com/store/search?q=Wingstop&c=apps&hl=ja |  |
| 40 | YO! Sushi | YO! | 要確認 | https://apps.apple.com/jp/search?term=YO%21%20Sushi | https://play.google.com/store/search?q=YO%21%20Sushi&c=apps&hl=ja |  |

### 5.7 コスメ・ドラッグ・美容（44件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | @cosme TOKYO | アイスタイル | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%40cosme%20TOKYO | https://play.google.com/store/search?q=%40cosme%20TOKYO&c=apps&hl=ja | 口コミ、ランキング、商品検索、EC、店舗購買 |
| 2 | @cosme store | アイスタイル | 要確認 | https://apps.apple.com/jp/search?term=%40cosme%20store | https://play.google.com/store/search?q=%40cosme%20store&c=apps&hl=ja |  |
| 3 | Aesop | イソップ | 要確認 | https://apps.apple.com/jp/search?term=Aesop | https://play.google.com/store/search?q=Aesop&c=apps&hl=ja |  |
| 4 | DHC | DHC | 要確認 | https://apps.apple.com/jp/search?term=DHC | https://play.google.com/store/search?q=DHC&c=apps&hl=ja |  |
| 5 | IPSA | IPSA | 要確認 | https://apps.apple.com/jp/search?term=IPSA | https://play.google.com/store/search?q=IPSA&c=apps&hl=ja |  |
| 6 | LUSH | LUSH JAPAN | 要確認 | https://apps.apple.com/jp/search?term=LUSH | https://play.google.com/store/search?q=LUSH&c=apps&hl=ja |  |
| 7 | PLAZA コスメ | スタイリングライフ | 要確認 | https://apps.apple.com/jp/search?term=PLAZA%20%E3%82%B3%E3%82%B9%E3%83%A1 | https://play.google.com/store/search?q=PLAZA%20%E3%82%B3%E3%82%B9%E3%83%A1&c=apps&hl=ja |  |
| 8 | RMK | RMK | 要確認 | https://apps.apple.com/jp/search?term=RMK | https://play.google.com/store/search?q=RMK&c=apps&hl=ja |  |
| 9 | SHIRO | SHIRO | 要確認 | https://apps.apple.com/jp/search?term=SHIRO | https://play.google.com/store/search?q=SHIRO&c=apps&hl=ja |  |
| 10 | SUQQU | SUQQU | 要確認 | https://apps.apple.com/jp/search?term=SUQQU | https://play.google.com/store/search?q=SUQQU&c=apps&hl=ja |  |
| 11 | THE BODY SHOP | ボディショップ | 要確認 | https://apps.apple.com/jp/search?term=THE%20BODY%20SHOP | https://play.google.com/store/search?q=THE%20BODY%20SHOP&c=apps&hl=ja |  |
| 12 | THREE | THREE | 要確認 | https://apps.apple.com/jp/search?term=THREE | https://play.google.com/store/search?q=THREE&c=apps&hl=ja |  |
| 13 | アインズ＆トルペ | アインホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%82%A4%E3%83%B3%E3%82%BA%EF%BC%86%E3%83%88%E3%83%AB%E3%83%9A | https://play.google.com/store/search?q=%E3%82%A2%E3%82%A4%E3%83%B3%E3%82%BA%EF%BC%86%E3%83%88%E3%83%AB%E3%83%9A&c=apps&hl=ja |  |
| 14 | ウエルシア | ウエルシアホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A6%E3%82%A8%E3%83%AB%E3%82%B7%E3%82%A2 | https://play.google.com/store/search?q=%E3%82%A6%E3%82%A8%E3%83%AB%E3%82%B7%E3%82%A2&c=apps&hl=ja |  |
| 15 | ウエルシア薬局 | ウエルシア | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A6%E3%82%A8%E3%83%AB%E3%82%B7%E3%82%A2%E8%96%AC%E5%B1%80 | https://play.google.com/store/search?q=%E3%82%A6%E3%82%A8%E3%83%AB%E3%82%B7%E3%82%A2%E8%96%AC%E5%B1%80&c=apps&hl=ja |  |
| 16 | オルビス | ポーラ・オルビスHD | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%AB%E3%83%93%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%AA%E3%83%AB%E3%83%93%E3%82%B9&c=apps&hl=ja |  |
| 17 | カワチ薬品 | カワチ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%AF%E3%83%81%E8%96%AC%E5%93%81 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%AF%E3%83%81%E8%96%AC%E5%93%81&c=apps&hl=ja |  |
| 18 | キリン堂 | キリン堂 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%AA%E3%83%B3%E5%A0%82 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%AA%E3%83%B3%E5%A0%82&c=apps&hl=ja |  |
| 19 | クスリのアオキ | クスリのアオキホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%82%B9%E3%83%AA%E3%81%AE%E3%82%A2%E3%82%AA%E3%82%AD | https://play.google.com/store/search?q=%E3%82%AF%E3%82%B9%E3%83%AA%E3%81%AE%E3%82%A2%E3%82%AA%E3%82%AD&c=apps&hl=ja |  |
| 20 | クリエイトSD | クリエイトSDホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%AA%E3%82%A8%E3%82%A4%E3%83%88SD | https://play.google.com/store/search?q=%E3%82%AF%E3%83%AA%E3%82%A8%E3%82%A4%E3%83%88SD&c=apps&hl=ja |  |
| 21 | クリエイトエス・ディー | クリエイトSD | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%83%AA%E3%82%A8%E3%82%A4%E3%83%88%E3%82%A8%E3%82%B9%E3%83%BB%E3%83%87%E3%82%A3%E3%83%BC | https://play.google.com/store/search?q=%E3%82%AF%E3%83%AA%E3%82%A8%E3%82%A4%E3%83%88%E3%82%A8%E3%82%B9%E3%83%BB%E3%83%87%E3%82%A3%E3%83%BC&c=apps&hl=ja |  |
| 22 | ゲンキー | ゲンキー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B2%E3%83%B3%E3%82%AD%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B2%E3%83%B3%E3%82%AD%E3%83%BC&c=apps&hl=ja |  |
| 23 | ココカラファイン | マツキヨココカラ＆カンパニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B3%E3%82%AB%E3%83%A9%E3%83%95%E3%82%A1%E3%82%A4%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B3%E3%82%AB%E3%83%A9%E3%83%95%E3%82%A1%E3%82%A4%E3%83%B3&c=apps&hl=ja |  |
| 24 | コスメキッチン | マッシュビューティーラボ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B9%E3%83%A1%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B9%E3%83%A1%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3&c=apps&hl=ja |  |
| 25 | コスモス | コスモス薬品 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B9%E3%83%A2%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B9%E3%83%A2%E3%82%B9&c=apps&hl=ja |  |
| 26 | コスモス薬品 | コスモス薬品 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B9%E3%83%A2%E3%82%B9%E8%96%AC%E5%93%81 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B9%E3%83%A2%E3%82%B9%E8%96%AC%E5%93%81&c=apps&hl=ja |  |
| 27 | サツドラ | サツドラホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%84%E3%83%89%E3%83%A9 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%84%E3%83%89%E3%83%A9&c=apps&hl=ja |  |
| 28 | サンドラッグ | サンドラッグ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0&c=apps&hl=ja |  |
| 29 | ザグザグ | ザグザグ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B6%E3%82%B0%E3%82%B6%E3%82%B0 | https://play.google.com/store/search?q=%E3%82%B6%E3%82%B0%E3%82%B6%E3%82%B0&c=apps&hl=ja |  |
| 30 | ショップイン | 東京ドーム | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%E3%82%A4%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%E3%82%A4%E3%83%B3&c=apps&hl=ja |  |
| 31 | スギ薬局 | スギホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%AE%E8%96%AC%E5%B1%80 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%AE%E8%96%AC%E5%B1%80&c=apps&hl=ja |  |
| 32 | ツルハ | ツルハ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%84%E3%83%AB%E3%83%8F | https://play.google.com/store/search?q=%E3%83%84%E3%83%AB%E3%83%8F&c=apps&hl=ja |  |
| 33 | ツルハドラッグ | ツルハホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%84%E3%83%AB%E3%83%8F%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0 | https://play.google.com/store/search?q=%E3%83%84%E3%83%AB%E3%83%8F%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0&c=apps&hl=ja |  |
| 34 | トモズ | トモズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%88%E3%83%A2%E3%82%BA | https://play.google.com/store/search?q=%E3%83%88%E3%83%A2%E3%82%BA&c=apps&hl=ja |  |
| 35 | ドラッグイレブン | ツルハ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3&c=apps&hl=ja |  |
| 36 | ドラッグモリ | モリ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0%E3%83%A2%E3%83%AA | https://play.google.com/store/search?q=%E3%83%89%E3%83%A9%E3%83%83%E3%82%B0%E3%83%A2%E3%83%AA&c=apps&hl=ja |  |
| 37 | ハピコム | ウエルシア関連 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%94%E3%82%B3%E3%83%A0 | https://play.google.com/store/search?q=%E3%83%8F%E3%83%94%E3%82%B3%E3%83%A0&c=apps&hl=ja |  |
| 38 | ファンケル | ファンケル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A1%E3%83%B3%E3%82%B1%E3%83%AB | https://play.google.com/store/search?q=%E3%83%95%E3%82%A1%E3%83%B3%E3%82%B1%E3%83%AB&c=apps&hl=ja |  |
| 39 | マツキヨ | マツキヨココカラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%84%E3%82%AD%E3%83%A8 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%84%E3%82%AD%E3%83%A8&c=apps&hl=ja |  |
| 40 | マツモトキヨシ | マツキヨココカラ＆カンパニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%84%E3%83%A2%E3%83%88%E3%82%AD%E3%83%A8%E3%82%B7 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%84%E3%83%A2%E3%83%88%E3%82%AD%E3%83%A8%E3%82%B7&c=apps&hl=ja |  |
| 41 | ロクシタン | ロクシタンジャポン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%82%AF%E3%82%B7%E3%82%BF%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%AD%E3%82%AF%E3%82%B7%E3%82%BF%E3%83%B3&c=apps&hl=ja |  |
| 42 | ロフト コスメ | ロフト | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%95%E3%83%88%20%E3%82%B3%E3%82%B9%E3%83%A1 | https://play.google.com/store/search?q=%E3%83%AD%E3%83%95%E3%83%88%20%E3%82%B3%E3%82%B9%E3%83%A1&c=apps&hl=ja |  |
| 43 | 富士薬品 | 富士薬品 | 要確認 | https://apps.apple.com/jp/search?term=%E5%AF%8C%E5%A3%AB%E8%96%AC%E5%93%81 | https://play.google.com/store/search?q=%E5%AF%8C%E5%A3%AB%E8%96%AC%E5%93%81&c=apps&hl=ja |  |
| 44 | 資生堂パーラー | 資生堂 | 要確認 | https://apps.apple.com/jp/search?term=%E8%B3%87%E7%94%9F%E5%A0%82%E3%83%91%E3%83%BC%E3%83%A9%E3%83%BC | https://play.google.com/store/search?q=%E8%B3%87%E7%94%9F%E5%A0%82%E3%83%91%E3%83%BC%E3%83%A9%E3%83%BC&c=apps&hl=ja |  |

### 5.8 ジム・フィットネス（31件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 24/7 Workout | RIZAP関連 | 要確認 | https://apps.apple.com/jp/search?term=24/7%20Workout | https://play.google.com/store/search?q=24/7%20Workout&c=apps&hl=ja |  |
| 2 | Apple GYM | Apple GYM | 要確認 | https://apps.apple.com/jp/search?term=Apple%20GYM | https://play.google.com/store/search?q=Apple%20GYM&c=apps&hl=ja |  |
| 3 | BEYOND | ワールドフィット | 要確認 | https://apps.apple.com/jp/search?term=BEYOND | https://play.google.com/store/search?q=BEYOND&c=apps&hl=ja |  |
| 4 | BEYOND GYM | ワールドフィット | 要確認 | https://apps.apple.com/jp/search?term=BEYOND%20GYM | https://play.google.com/store/search?q=BEYOND%20GYM&c=apps&hl=ja |  |
| 5 | Dr.stretch | nobitel | 要確認 | https://apps.apple.com/jp/search?term=Dr.stretch | https://play.google.com/store/search?q=Dr.stretch&c=apps&hl=ja |  |
| 6 | FEELCYCLE | FEEL CONNECTION | 要確認 | https://apps.apple.com/jp/search?term=FEELCYCLE | https://play.google.com/store/search?q=FEELCYCLE&c=apps&hl=ja |  |
| 7 | FIT EASY | フィットイージー | 要確認 | https://apps.apple.com/jp/search?term=FIT%20EASY | https://play.google.com/store/search?q=FIT%20EASY&c=apps&hl=ja |  |
| 8 | FIT365 | ウェルネスフロンティア | 要確認 | https://apps.apple.com/jp/search?term=FIT365 | https://play.google.com/store/search?q=FIT365&c=apps&hl=ja |  |
| 9 | JOYFIT | ウェルネスフロンティア | 要確認 | https://apps.apple.com/jp/search?term=JOYFIT | https://play.google.com/store/search?q=JOYFIT&c=apps&hl=ja |  |
| 10 | Pilates K | LIFE CREATE | 要確認 | https://apps.apple.com/jp/search?term=Pilates%20K | https://play.google.com/store/search?q=Pilates%20K&c=apps&hl=ja |  |
| 11 | RIZAP | RIZAP | 要確認 | https://apps.apple.com/jp/search?term=RIZAP | https://play.google.com/store/search?q=RIZAP&c=apps&hl=ja |  |
| 12 | THE PERSONAL GYM | RITA-STYLE | 要確認 | https://apps.apple.com/jp/search?term=THE%20PERSONAL%20GYM | https://play.google.com/store/search?q=THE%20PERSONAL%20GYM&c=apps&hl=ja |  |
| 13 | chocoZAP | RIZAP | 要確認 | https://apps.apple.com/jp/search?term=chocoZAP | https://play.google.com/store/search?q=chocoZAP&c=apps&hl=ja |  |
| 14 | loIve | LIFE CREATE | 要確認 | https://apps.apple.com/jp/search?term=loIve | https://play.google.com/store/search?q=loIve&c=apps&hl=ja |  |
| 15 | zen place | ZEN PLACE | 要確認 | https://apps.apple.com/jp/search?term=zen%20place | https://play.google.com/store/search?q=zen%20place&c=apps&hl=ja |  |
| 16 | かたぎり塾 | caname | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8B%E3%81%9F%E3%81%8E%E3%82%8A%E5%A1%BE | https://play.google.com/store/search?q=%E3%81%8B%E3%81%9F%E3%81%8E%E3%82%8A%E5%A1%BE&c=apps&hl=ja |  |
| 17 | アクトス | アクトス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%82%AF%E3%83%88%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%A2%E3%82%AF%E3%83%88%E3%82%B9&c=apps&hl=ja |  |
| 18 | エニタイム | Fast Fitness Japan | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%83%8B%E3%82%BF%E3%82%A4%E3%83%A0 | https://play.google.com/store/search?q=%E3%82%A8%E3%83%8B%E3%82%BF%E3%82%A4%E3%83%A0&c=apps&hl=ja |  |
| 19 | エニタイムフィットネス | Fast Fitness Japan | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%83%8B%E3%82%BF%E3%82%A4%E3%83%A0%E3%83%95%E3%82%A3%E3%83%83%E3%83%88%E3%83%8D%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%A8%E3%83%8B%E3%82%BF%E3%82%A4%E3%83%A0%E3%83%95%E3%82%A3%E3%83%83%E3%83%88%E3%83%8D%E3%82%B9&c=apps&hl=ja |  |
| 20 | カーブス | カーブスジャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%BC%E3%83%96%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%BC%E3%83%96%E3%82%B9&c=apps&hl=ja |  |
| 21 | コナミスポーツ | コナミスポーツ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%8A%E3%83%9F%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%8A%E3%83%9F%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84&c=apps&hl=ja |  |
| 22 | コナミスポーツクラブ | コナミスポーツ | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%8A%E3%83%9F%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AF%E3%83%A9%E3%83%96 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%8A%E3%83%9F%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AF%E3%83%A9%E3%83%96&c=apps&hl=ja | 会員証、利用回数、施設検索、タイムテーブル、予約 |
| 23 | ゴールドジム | THINKフィットネス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B4%E3%83%BC%E3%83%AB%E3%83%89%E3%82%B8%E3%83%A0 | https://play.google.com/store/search?q=%E3%82%B4%E3%83%BC%E3%83%AB%E3%83%89%E3%82%B8%E3%83%A0&c=apps&hl=ja |  |
| 24 | スポーツクラブNAS | スポーツクラブNAS | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AF%E3%83%A9%E3%83%96NAS | https://play.google.com/store/search?q=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AF%E3%83%A9%E3%83%96NAS&c=apps&hl=ja |  |
| 25 | セントラルスポーツ | セントラルスポーツ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BB%E3%83%B3%E3%83%88%E3%83%A9%E3%83%AB%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84 | https://play.google.com/store/search?q=%E3%82%BB%E3%83%B3%E3%83%88%E3%83%A9%E3%83%AB%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84&c=apps&hl=ja |  |
| 26 | チョコザップ | RIZAP | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%81%E3%83%A7%E3%82%B3%E3%82%B6%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%83%81%E3%83%A7%E3%82%B3%E3%82%B6%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 27 | ティップネス | ティップネス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%86%E3%82%A3%E3%83%83%E3%83%97%E3%83%8D%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%86%E3%82%A3%E3%83%83%E3%83%97%E3%83%8D%E3%82%B9&c=apps&hl=ja |  |
| 28 | フィットプレイス | フィットイージー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A3%E3%83%83%E3%83%88%E3%83%97%E3%83%AC%E3%82%A4%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%95%E3%82%A3%E3%83%83%E3%83%88%E3%83%97%E3%83%AC%E3%82%A4%E3%82%B9&c=apps&hl=ja |  |
| 29 | ホットヨガLAVA | LAVA International | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%83%E3%83%88%E3%83%A8%E3%82%ACLAVA | https://play.google.com/store/search?q=%E3%83%9B%E3%83%83%E3%83%88%E3%83%A8%E3%82%ACLAVA&c=apps&hl=ja |  |
| 30 | メガロス | 野村不動産ライフ＆スポーツ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A1%E3%82%AC%E3%83%AD%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%A1%E3%82%AC%E3%83%AD%E3%82%B9&c=apps&hl=ja |  |
| 31 | ルネサンス | ルネサンス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AB%E3%83%8D%E3%82%B5%E3%83%B3%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%AB%E3%83%8D%E3%82%B5%E3%83%B3%E3%82%B9&c=apps&hl=ja |  |

### 5.9 スポーツ・アウトドア（18件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Victoria | ゼビオホールディングス | 要確認 | https://apps.apple.com/jp/search?term=Victoria | https://play.google.com/store/search?q=Victoria&c=apps&hl=ja |  |
| 2 | アルペン | アルペン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%AB%E3%83%9A%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%AB%E3%83%9A%E3%83%B3&c=apps&hl=ja |  |
| 3 | キャスティング | ワールドスポーツ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%A3%E3%82%B9%E3%83%86%E3%82%A3%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%A3%E3%82%B9%E3%83%86%E3%82%A3%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 4 | ゴルフ5 | アルペン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B4%E3%83%AB%E3%83%955 | https://play.google.com/store/search?q=%E3%82%B4%E3%83%AB%E3%83%955&c=apps&hl=ja |  |
| 5 | ゴルフパートナー | ゴルフパートナー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B4%E3%83%AB%E3%83%95%E3%83%91%E3%83%BC%E3%83%88%E3%83%8A%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B4%E3%83%AB%E3%83%95%E3%83%91%E3%83%BC%E3%83%88%E3%83%8A%E3%83%BC&c=apps&hl=ja |  |
| 6 | スポーツオーソリティ | イオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AA%E3%83%BC%E3%82%BD%E3%83%AA%E3%83%86%E3%82%A3 | https://play.google.com/store/search?q=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%AA%E3%83%BC%E3%82%BD%E3%83%AA%E3%83%86%E3%82%A3&c=apps&hl=ja |  |
| 7 | スポーツデポ | アルペン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%83%87%E3%83%9D | https://play.google.com/store/search?q=%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%83%87%E3%83%9D&c=apps&hl=ja |  |
| 8 | スーパースポーツゼビオ | ゼビオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%BC%E3%83%93%E3%82%AA | https://play.google.com/store/search?q=%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84%E3%82%BC%E3%83%93%E3%82%AA&c=apps&hl=ja |  |
| 9 | ゼビオ | ゼビオホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BC%E3%83%93%E3%82%AA | https://play.google.com/store/search?q=%E3%82%BC%E3%83%93%E3%82%AA&c=apps&hl=ja |  |
| 10 | ヒマラヤ | ヒマラヤ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%92%E3%83%9E%E3%83%A9%E3%83%A4 | https://play.google.com/store/search?q=%E3%83%92%E3%83%9E%E3%83%A9%E3%83%A4&c=apps&hl=ja |  |
| 11 | ヒマラヤスポーツ | ヒマラヤ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%92%E3%83%9E%E3%83%A9%E3%83%A4%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84 | https://play.google.com/store/search?q=%E3%83%92%E3%83%9E%E3%83%A9%E3%83%A4%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84&c=apps&hl=ja |  |
| 12 | モンベル | モンベル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A2%E3%83%B3%E3%83%99%E3%83%AB | https://play.google.com/store/search?q=%E3%83%A2%E3%83%B3%E3%83%99%E3%83%AB&c=apps&hl=ja |  |
| 13 | ワークマン | ワークマン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%83%BC%E3%82%AF%E3%83%9E%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%AF%E3%83%BC%E3%82%AF%E3%83%9E%E3%83%B3&c=apps&hl=ja |  |
| 14 | ヴィクトリアゴルフ | ゼビオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%B4%E3%82%A3%E3%82%AF%E3%83%88%E3%83%AA%E3%82%A2%E3%82%B4%E3%83%AB%E3%83%95 | https://play.google.com/store/search?q=%E3%83%B4%E3%82%A3%E3%82%AF%E3%83%88%E3%83%AA%E3%82%A2%E3%82%B4%E3%83%AB%E3%83%95&c=apps&hl=ja |  |
| 15 | 好日山荘 | 好日山荘 | 要確認 | https://apps.apple.com/jp/search?term=%E5%A5%BD%E6%97%A5%E5%B1%B1%E8%8D%98 | https://play.google.com/store/search?q=%E5%A5%BD%E6%97%A5%E5%B1%B1%E8%8D%98&c=apps&hl=ja |  |
| 16 | 石井スポーツ | ヨドバシホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E7%9F%B3%E4%BA%95%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84 | https://play.google.com/store/search?q=%E7%9F%B3%E4%BA%95%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%84&c=apps&hl=ja |  |
| 17 | 釣具のキャスティング | ワールドスポーツ | 要確認 | https://apps.apple.com/jp/search?term=%E9%87%A3%E5%85%B7%E3%81%AE%E3%82%AD%E3%83%A3%E3%82%B9%E3%83%86%E3%82%A3%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E9%87%A3%E5%85%B7%E3%81%AE%E3%82%AD%E3%83%A3%E3%82%B9%E3%83%86%E3%82%A3%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 18 | 釣具のポイント | タカミヤ | 要確認 | https://apps.apple.com/jp/search?term=%E9%87%A3%E5%85%B7%E3%81%AE%E3%83%9D%E3%82%A4%E3%83%B3%E3%83%88 | https://play.google.com/store/search?q=%E9%87%A3%E5%85%B7%E3%81%AE%E3%83%9D%E3%82%A4%E3%83%B3%E3%83%88&c=apps&hl=ja |  |

### 5.10 ペット（14件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Coo&RIKU | Coo&RIKU | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=Coo%26RIKU | https://play.google.com/store/search?q=Coo%26RIKU&c=apps&hl=ja | 商品、ペット情報、店舗検索、購入/会員系導線 |
| 2 | PEPPY | ペピイ | 要確認 | https://apps.apple.com/jp/search?term=PEPPY | https://play.google.com/store/search?q=PEPPY&c=apps&hl=ja |  |
| 3 | PETEMO | イオンペット | 要確認 | https://apps.apple.com/jp/search?term=PETEMO | https://play.google.com/store/search?q=PETEMO&c=apps&hl=ja |  |
| 4 | ひごペットフレンドリー | ひごペットフレンドリー | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%B2%E3%81%94%E3%83%9A%E3%83%83%E3%83%88%E3%83%95%E3%83%AC%E3%83%B3%E3%83%89%E3%83%AA%E3%83%BC | https://play.google.com/store/search?q=%E3%81%B2%E3%81%94%E3%83%9A%E3%83%83%E3%83%88%E3%83%95%E3%83%AC%E3%83%B3%E3%83%89%E3%83%AA%E3%83%BC&c=apps&hl=ja |  |
| 5 | イオンペット | イオンペット | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%9A%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%9A%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 6 | コーナン ペットプラザ | コーナン商事 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%B3%20%E3%83%9A%E3%83%83%E3%83%88%E3%83%97%E3%83%A9%E3%82%B6 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%B3%20%E3%83%9A%E3%83%83%E3%83%88%E3%83%97%E3%83%A9%E3%82%B6&c=apps&hl=ja |  |
| 7 | チャーム | チャーム | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%81%E3%83%A3%E3%83%BC%E3%83%A0 | https://play.google.com/store/search?q=%E3%83%81%E3%83%A3%E3%83%BC%E3%83%A0&c=apps&hl=ja |  |
| 8 | ペッツファースト | ペッツファースト | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%84%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%84%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 9 | ペットエコ | ペットエコ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%82%A8%E3%82%B3 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%82%A8%E3%82%B3&c=apps&hl=ja |  |
| 10 | ペットショップ ワンラブ | ONELOVE | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%20%E3%83%AF%E3%83%B3%E3%83%A9%E3%83%96 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%20%E3%83%AF%E3%83%B3%E3%83%A9%E3%83%96&c=apps&hl=ja |  |
| 11 | ペットフォレスト | ペットフォレスト | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%83%95%E3%82%A9%E3%83%AC%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%83%95%E3%82%A9%E3%83%AC%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 12 | ペットプラス | AHB | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%83%97%E3%83%A9%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%83%97%E3%83%A9%E3%82%B9&c=apps&hl=ja |  |
| 13 | ペットランド | ペットランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 14 | ペットワールド アミーゴ | アミーゴ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%88%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89%20%E3%82%A2%E3%83%9F%E3%83%BC%E3%82%B4 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%88%E3%83%AF%E3%83%BC%E3%83%AB%E3%83%89%20%E3%82%A2%E3%83%9F%E3%83%BC%E3%82%B4&c=apps&hl=ja |  |

### 5.11 ホテル・宿泊（15件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | JR九州ホテルズ | JR九州ホテルズ | 要確認 | https://apps.apple.com/jp/search?term=JR%E4%B9%9D%E5%B7%9E%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA | https://play.google.com/store/search?q=JR%E4%B9%9D%E5%B7%9E%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA&c=apps&hl=ja |  |
| 2 | アパホテル | アパホテル | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%91%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E3%82%A2%E3%83%91%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 3 | スーパーホテル | スーパーホテル | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 4 | ダイワロイネットホテル | 大和ハウスリアルティマネジメント | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%80%E3%82%A4%E3%83%AF%E3%83%AD%E3%82%A4%E3%83%8D%E3%83%83%E3%83%88%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E3%83%80%E3%82%A4%E3%83%AF%E3%83%AD%E3%82%A4%E3%83%8D%E3%83%83%E3%83%88%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 5 | ドーミーイン | 共立メンテナンス | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%BC%E3%83%9F%E3%83%BC%E3%82%A4%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%89%E3%83%BC%E3%83%9F%E3%83%BC%E3%82%A4%E3%83%B3&c=apps&hl=ja | 予約、会員/ポイント、クーポン、QRチェックイン |
| 6 | プリンスホテル | 西武・プリンスホテルズワールドワイド | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%AA%E3%83%B3%E3%82%B9%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E3%83%97%E3%83%AA%E3%83%B3%E3%82%B9%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 7 | ホテルオークラ | ホテルオークラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%86%E3%83%AB%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%9B%E3%83%86%E3%83%AB%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%A9&c=apps&hl=ja |  |
| 8 | ホテルメトロポリタン | JR東日本ホテルズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%86%E3%83%AB%E3%83%A1%E3%83%88%E3%83%AD%E3%83%9D%E3%83%AA%E3%82%BF%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%9B%E3%83%86%E3%83%AB%E3%83%A1%E3%83%88%E3%83%AD%E3%83%9D%E3%83%AA%E3%82%BF%E3%83%B3&c=apps&hl=ja |  |
| 9 | リッチモンドホテル | ロイヤルホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AA%E3%83%83%E3%83%81%E3%83%A2%E3%83%B3%E3%83%89%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E3%83%AA%E3%83%83%E3%83%81%E3%83%A2%E3%83%B3%E3%83%89%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 10 | ルートインホテルズ | ルートインジャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AB%E3%83%BC%E3%83%88%E3%82%A4%E3%83%B3%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA | https://play.google.com/store/search?q=%E3%83%AB%E3%83%BC%E3%83%88%E3%82%A4%E3%83%B3%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA&c=apps&hl=ja |  |
| 11 | 三井ガーデンホテル | 三井不動産ホテルマネジメント | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%89%E4%BA%95%E3%82%AC%E3%83%BC%E3%83%87%E3%83%B3%E3%83%9B%E3%83%86%E3%83%AB | https://play.google.com/store/search?q=%E4%B8%89%E4%BA%95%E3%82%AC%E3%83%BC%E3%83%87%E3%83%B3%E3%83%9B%E3%83%86%E3%83%AB&c=apps&hl=ja |  |
| 12 | 星野リゾート | 星野リゾート | 要確認 | https://apps.apple.com/jp/search?term=%E6%98%9F%E9%87%8E%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E6%98%9F%E9%87%8E%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 13 | 東急ホテルズ | 東急ホテルズ | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%80%A5%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA | https://play.google.com/store/search?q=%E6%9D%B1%E6%80%A5%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA&c=apps&hl=ja |  |
| 14 | 東横INN | 東横イン | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%A8%AAINN | https://play.google.com/store/search?q=%E6%9D%B1%E6%A8%AAINN&c=apps&hl=ja | 会員証、予約、ホテル検索、QRチェックイン、ポイント |
| 15 | 西鉄ホテルズ | 西鉄ホテルズ | 要確認 | https://apps.apple.com/jp/search?term=%E8%A5%BF%E9%89%84%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA | https://play.google.com/store/search?q=%E8%A5%BF%E9%89%84%E3%83%9B%E3%83%86%E3%83%AB%E3%82%BA&c=apps&hl=ja |  |

### 5.12 レジャー・施設（34件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Disneyland | Disney | 要確認 | https://apps.apple.com/jp/search?term=Disneyland | https://play.google.com/store/search?q=Disneyland&c=apps&hl=ja |  |
| 2 | GiGO | GENDA GiGO Entertainment | 要確認 | https://apps.apple.com/jp/search?term=GiGO | https://play.google.com/store/search?q=GiGO&c=apps&hl=ja |  |
| 3 | LEGOLAND | Merlin Entertainments | 要確認 | https://apps.apple.com/jp/search?term=LEGOLAND | https://play.google.com/store/search?q=LEGOLAND&c=apps&hl=ja |  |
| 4 | SeaWorld | SeaWorld | 要確認 | https://apps.apple.com/jp/search?term=SeaWorld | https://play.google.com/store/search?q=SeaWorld&c=apps&hl=ja |  |
| 5 | Six Flags | Six Flags | 要確認 | https://apps.apple.com/jp/search?term=Six%20Flags | https://play.google.com/store/search?q=Six%20Flags&c=apps&hl=ja |  |
| 6 | TOHOシネマズ | TOHOシネマズ | 要確認 | https://apps.apple.com/jp/search?term=TOHO%E3%82%B7%E3%83%8D%E3%83%9E%E3%82%BA | https://play.google.com/store/search?q=TOHO%E3%82%B7%E3%83%8D%E3%83%9E%E3%82%BA&c=apps&hl=ja |  |
| 7 | Universal Orlando Resort | Universal | 要確認 | https://apps.apple.com/jp/search?term=Universal%20Orlando%20Resort | https://play.google.com/store/search?q=Universal%20Orlando%20Resort&c=apps&hl=ja |  |
| 8 | Universal Studios Japan | ユー・エス・ジェイ | 要確認 | https://apps.apple.com/jp/search?term=Universal%20Studios%20Japan | https://play.google.com/store/search?q=Universal%20Studios%20Japan&c=apps&hl=ja |  |
| 9 | Walt Disney World | Disney | 要確認 | https://apps.apple.com/jp/search?term=Walt%20Disney%20World | https://play.google.com/store/search?q=Walt%20Disney%20World&c=apps&hl=ja |  |
| 10 | namco | バンダイナムコアミューズメント | 要確認 | https://apps.apple.com/jp/search?term=namco | https://play.google.com/store/search?q=namco&c=apps&hl=ja |  |
| 11 | まねきねこ | コシダカホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%BE%E3%81%AD%E3%81%8D%E3%81%AD%E3%81%93 | https://play.google.com/store/search?q=%E3%81%BE%E3%81%AD%E3%81%8D%E3%81%AD%E3%81%93&c=apps&hl=ja |  |
| 12 | よみうりランド | よみうりランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%88%E3%81%BF%E3%81%86%E3%82%8A%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%88%E3%81%BF%E3%81%86%E3%82%8A%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 13 | ららぽーとTOKYO-BAY | 三井不動産 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%89%E3%82%89%E3%81%BD%E3%83%BC%E3%81%A8TOKYO-BAY | https://play.google.com/store/search?q=%E3%82%89%E3%82%89%E3%81%BD%E3%83%BC%E3%81%A8TOKYO-BAY&c=apps&hl=ja |  |
| 14 | イオンシネマ | イオンエンターテイメント | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3%E3%82%B7%E3%83%8D%E3%83%9E | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3%E3%82%B7%E3%83%8D%E3%83%9E&c=apps&hl=ja |  |
| 15 | イオンモール幕張新都 | イオンモール | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%A2%E3%83%BC%E3%83%AB%E5%B9%95%E5%BC%B5%E6%96%B0%E9%83%BD | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%A2%E3%83%BC%E3%83%AB%E5%B9%95%E5%BC%B5%E6%96%B0%E9%83%BD&c=apps&hl=ja |  |
| 16 | オリエンタルランド | オリエンタルランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%AA%E3%82%A8%E3%83%B3%E3%82%BF%E3%83%AB%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%AA%E3%83%AA%E3%82%A8%E3%83%B3%E3%82%BF%E3%83%AB%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 17 | カラオケ館 | カラオケ館 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%A9%E3%82%AA%E3%82%B1%E9%A4%A8 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%A9%E3%82%AA%E3%82%B1%E9%A4%A8&c=apps&hl=ja |  |
| 18 | キャナルシティ | 福岡地所 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%A3%E3%83%8A%E3%83%AB%E3%82%B7%E3%83%86%E3%82%A3 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%A3%E3%83%8A%E3%83%AB%E3%82%B7%E3%83%86%E3%82%A3&c=apps&hl=ja |  |
| 19 | サンリオ | サンリオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA&c=apps&hl=ja |  |
| 20 | サンリオピューロランド | サンリオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA%E3%83%94%E3%83%A5%E3%83%BC%E3%83%AD%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA%E3%83%94%E3%83%A5%E3%83%BC%E3%83%AD%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 21 | ジョイサウンド | エクシング | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A7%E3%82%A4%E3%82%B5%E3%82%A6%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A7%E3%82%A4%E3%82%B5%E3%82%A6%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 22 | タイトー | タイトー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BF%E3%82%A4%E3%83%88%E3%83%BC | https://play.google.com/store/search?q=%E3%82%BF%E3%82%A4%E3%83%88%E3%83%BC&c=apps&hl=ja |  |
| 23 | ナガシマスパーランド | ナガシマリゾート | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8A%E3%82%AC%E3%82%B7%E3%83%9E%E3%82%B9%E3%83%91%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%83%8A%E3%82%AC%E3%82%B7%E3%83%9E%E3%82%B9%E3%83%91%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 24 | ハウステンボス | ハウステンボス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%82%A6%E3%82%B9%E3%83%86%E3%83%B3%E3%83%9C%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%8F%E3%82%A6%E3%82%B9%E3%83%86%E3%83%B3%E3%83%9C%E3%82%B9&c=apps&hl=ja |  |
| 25 | ハーモニーランド | サンリオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%BC%E3%83%A2%E3%83%8B%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%83%8F%E3%83%BC%E3%83%A2%E3%83%8B%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 26 | ビッグエコー | 第一興商 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%83%83%E3%82%B0%E3%82%A8%E3%82%B3%E3%83%BC | https://play.google.com/store/search?q=%E3%83%93%E3%83%83%E3%82%B0%E3%82%A8%E3%82%B3%E3%83%BC&c=apps&hl=ja |  |
| 27 | ユナイテッド・シネマ | ユナイテッド・シネマ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%8A%E3%82%A4%E3%83%86%E3%83%83%E3%83%89%E3%83%BB%E3%82%B7%E3%83%8D%E3%83%9E | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8A%E3%82%A4%E3%83%86%E3%83%83%E3%83%89%E3%83%BB%E3%82%B7%E3%83%8D%E3%83%9E&c=apps&hl=ja |  |
| 28 | ユニバーサル・スタジオ・ジャパン | USJ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%8B%E3%83%90%E3%83%BC%E3%82%B5%E3%83%AB%E3%83%BB%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%83%BB%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8B%E3%83%90%E3%83%BC%E3%82%B5%E3%83%AB%E3%83%BB%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%83%BB%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3&c=apps&hl=ja |  |
| 29 | ラウンドワン | ラウンドワン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%82%A6%E3%83%B3%E3%83%89%E3%83%AF%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A9%E3%82%A6%E3%83%B3%E3%83%89%E3%83%AF%E3%83%B3&c=apps&hl=ja |  |
| 30 | 富士急ハイランド | 富士急行 | 要確認 | https://apps.apple.com/jp/search?term=%E5%AF%8C%E5%A3%AB%E6%80%A5%E3%83%8F%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E5%AF%8C%E5%A3%AB%E6%80%A5%E3%83%8F%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 31 | 東京ディズニーシー | オリエンタルランド | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%82%B7%E3%83%BC | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%82%B7%E3%83%BC&c=apps&hl=ja |  |
| 32 | 東京ディズニーランド | オリエンタルランド | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 33 | 東京ディズニーリゾート | オリエンタルランド | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 34 | 極楽湯 | 極楽湯ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E6%A5%B5%E6%A5%BD%E6%B9%AF | https://play.google.com/store/search?q=%E6%A5%B5%E6%A5%BD%E6%B9%AF&c=apps&hl=ja |  |

### 5.13 催事・イベント・観光物販（14件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | ANA FESTA | 全日空商事 | 要確認 | https://apps.apple.com/jp/search?term=ANA%20FESTA | https://play.google.com/store/search?q=ANA%20FESTA&c=apps&hl=ja |  |
| 2 | JAL PLAZA | JALUX | 要確認 | https://apps.apple.com/jp/search?term=JAL%20PLAZA | https://play.google.com/store/search?q=JAL%20PLAZA&c=apps&hl=ja |  |
| 3 | エキュート | JR東日本クロスステーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%82%A8%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 4 | キデイランド催事 | キデイランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%87%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89%E5%82%AC%E4%BA%8B | https://play.google.com/store/search?q=%E3%82%AD%E3%83%87%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89%E5%82%AC%E4%BA%8B&c=apps&hl=ja |  |
| 5 | グランスタ | JR東日本クロスステーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B0%E3%83%A9%E3%83%B3%E3%82%B9%E3%82%BF | https://play.google.com/store/search?q=%E3%82%B0%E3%83%A9%E3%83%B3%E3%82%B9%E3%82%BF&c=apps&hl=ja |  |
| 6 | サンリオピューロランド | サンリオ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA%E3%83%94%E3%83%A5%E3%83%BC%E3%83%AD%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%B3%E3%83%AA%E3%82%AA%E3%83%94%E3%83%A5%E3%83%BC%E3%83%AD%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 7 | ポップアップストア | 各ブランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%83%83%E3%83%97%E3%82%A2%E3%83%83%E3%83%97%E3%82%B9%E3%83%88%E3%82%A2 | https://play.google.com/store/search?q=%E3%83%9D%E3%83%83%E3%83%97%E3%82%A2%E3%83%83%E3%83%97%E3%82%B9%E3%83%88%E3%82%A2&c=apps&hl=ja |  |
| 8 | ユニバーサル・スタジオ・ジャパン | ユー・エス・ジェイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%8B%E3%83%90%E3%83%BC%E3%82%B5%E3%83%AB%E3%83%BB%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%83%BB%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8B%E3%83%90%E3%83%BC%E3%82%B5%E3%83%AB%E3%83%BB%E3%82%B9%E3%82%BF%E3%82%B8%E3%82%AA%E3%83%BB%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3&c=apps&hl=ja |  |
| 9 | 動物園ショップ | 各動物園 | 要確認 | https://apps.apple.com/jp/search?term=%E5%8B%95%E7%89%A9%E5%9C%92%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E5%8B%95%E7%89%A9%E5%9C%92%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 10 | 東京ディズニーリゾート | オリエンタルランド | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%83%87%E3%82%A3%E3%82%BA%E3%83%8B%E3%83%BC%E3%83%AA%E3%82%BE%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 11 | 水族館ショップ | 各水族館 | 要確認 | https://apps.apple.com/jp/search?term=%E6%B0%B4%E6%97%8F%E9%A4%A8%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E6%B0%B4%E6%97%8F%E9%A4%A8%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 12 | 海の中道海浜公園 | 国営公園 | 要確認 | https://apps.apple.com/jp/search?term=%E6%B5%B7%E3%81%AE%E4%B8%AD%E9%81%93%E6%B5%B7%E6%B5%9C%E5%85%AC%E5%9C%92 | https://play.google.com/store/search?q=%E6%B5%B7%E3%81%AE%E4%B8%AD%E9%81%93%E6%B5%B7%E6%B5%9C%E5%85%AC%E5%9C%92&c=apps&hl=ja |  |
| 13 | 百貨店催事 | 各百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BE%E8%B2%A8%E5%BA%97%E5%82%AC%E4%BA%8B | https://play.google.com/store/search?q=%E7%99%BE%E8%B2%A8%E5%BA%97%E5%82%AC%E4%BA%8B&c=apps&hl=ja |  |
| 14 | 道の駅 | 各運営法人 | 要確認 | https://apps.apple.com/jp/search?term=%E9%81%93%E3%81%AE%E9%A7%85 | https://play.google.com/store/search?q=%E9%81%93%E3%81%AE%E9%A7%85&c=apps&hl=ja |  |

### 5.14 家電・デジタル（14件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | Joshin | 上新電機 | 要確認 | https://apps.apple.com/jp/search?term=Joshin | https://play.google.com/store/search?q=Joshin&c=apps&hl=ja |  |
| 2 | PC DEPOT | ピーシーデポコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=PC%20DEPOT | https://play.google.com/store/search?q=PC%20DEPOT&c=apps&hl=ja |  |
| 3 | エディオン | エディオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%83%87%E3%82%A3%E3%82%AA%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A8%E3%83%87%E3%82%A3%E3%82%AA%E3%83%B3&c=apps&hl=ja |  |
| 4 | ケーズデンキ | ケーズホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B1%E3%83%BC%E3%82%BA%E3%83%87%E3%83%B3%E3%82%AD | https://play.google.com/store/search?q=%E3%82%B1%E3%83%BC%E3%82%BA%E3%83%87%E3%83%B3%E3%82%AD&c=apps&hl=ja |  |
| 5 | コジマ | ビックカメラグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B8%E3%83%9E | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B8%E3%83%9E&c=apps&hl=ja |  |
| 6 | ジョーシン | 上新電機 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A7%E3%83%BC%E3%82%B7%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A7%E3%83%BC%E3%82%B7%E3%83%B3&c=apps&hl=ja |  |
| 7 | ソフマップ | ビックカメラグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BD%E3%83%95%E3%83%9E%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%82%BD%E3%83%95%E3%83%9E%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 8 | ノジマ | ノジマ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8E%E3%82%B8%E3%83%9E | https://play.google.com/store/search?q=%E3%83%8E%E3%82%B8%E3%83%9E&c=apps&hl=ja |  |
| 9 | ビックカメラ | ビックカメラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%83%83%E3%82%AF%E3%82%AB%E3%83%A1%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%93%E3%83%83%E3%82%AF%E3%82%AB%E3%83%A1%E3%83%A9&c=apps&hl=ja |  |
| 10 | ベスト電器 | エディオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%82%B9%E3%83%88%E9%9B%BB%E5%99%A8 | https://play.google.com/store/search?q=%E3%83%99%E3%82%B9%E3%83%88%E9%9B%BB%E5%99%A8&c=apps&hl=ja |  |
| 11 | ミドリ電機 | エディオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%83%89%E3%83%AA%E9%9B%BB%E6%A9%9F | https://play.google.com/store/search?q=%E3%83%9F%E3%83%89%E3%83%AA%E9%9B%BB%E6%A9%9F&c=apps&hl=ja |  |
| 12 | ヤマダデンキ | ヤマダホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A4%E3%83%9E%E3%83%80%E3%83%87%E3%83%B3%E3%82%AD | https://play.google.com/store/search?q=%E3%83%A4%E3%83%9E%E3%83%80%E3%83%87%E3%83%B3%E3%82%AD&c=apps&hl=ja |  |
| 13 | ヤマダ電機 | ヤマダホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A4%E3%83%9E%E3%83%80%E9%9B%BB%E6%A9%9F | https://play.google.com/store/search?q=%E3%83%A4%E3%83%9E%E3%83%80%E9%9B%BB%E6%A9%9F&c=apps&hl=ja |  |
| 14 | ヨドバシカメラ | ヨドバシカメラ | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E3%83%A8%E3%83%89%E3%83%90%E3%82%B7%E3%82%AB%E3%83%A1%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%A8%E3%83%89%E3%83%90%E3%82%B7%E3%82%AB%E3%83%A1%E3%83%A9&c=apps&hl=ja | 商品検索、バーコード、在庫、価格、ポイント、購入 |

### 5.15 書店・ホビー・エンタメ（30件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | HMV | ローソンエンタテインメント | 要確認 | https://apps.apple.com/jp/search?term=HMV | https://play.google.com/store/search?q=HMV&c=apps&hl=ja |  |
| 2 | TSUTAYA | カルチュア・コンビニエンス・クラブ | 要確認 | https://apps.apple.com/jp/search?term=TSUTAYA | https://play.google.com/store/search?q=TSUTAYA&c=apps&hl=ja |  |
| 3 | WonderGOO | ワンダーコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=WonderGOO | https://play.google.com/store/search?q=WonderGOO&c=apps&hl=ja |  |
| 4 | くまざわ書店 | くまざわ書店 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8F%E3%81%BE%E3%81%96%E3%82%8F%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E3%81%8F%E3%81%BE%E3%81%96%E3%82%8F%E6%9B%B8%E5%BA%97&c=apps&hl=ja |  |
| 5 | まんだらけ | まんだらけ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%BE%E3%82%93%E3%81%A0%E3%82%89%E3%81%91 | https://play.google.com/store/search?q=%E3%81%BE%E3%82%93%E3%81%A0%E3%82%89%E3%81%91&c=apps&hl=ja |  |
| 6 | アニメイト | アニメイト | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%8B%E3%83%A1%E3%82%A4%E3%83%88 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%8B%E3%83%A1%E3%82%A4%E3%83%88&c=apps&hl=ja |  |
| 7 | イエローサブマリン | イエローサブマリン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%82%B5%E3%83%96%E3%83%9E%E3%83%AA%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%82%B5%E3%83%96%E3%83%9E%E3%83%AA%E3%83%B3&c=apps&hl=ja |  |
| 8 | カメラのキタムラ | キタムラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%A1%E3%83%A9%E3%81%AE%E3%82%AD%E3%82%BF%E3%83%A0%E3%83%A9 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%A1%E3%83%A9%E3%81%AE%E3%82%AD%E3%82%BF%E3%83%A0%E3%83%A9&c=apps&hl=ja |  |
| 9 | カードボックス | カードボックス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%BC%E3%83%89%E3%83%9C%E3%83%83%E3%82%AF%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%BC%E3%83%89%E3%83%9C%E3%83%83%E3%82%AF%E3%82%B9&c=apps&hl=ja |  |
| 10 | カードラボ | カードラボ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%BC%E3%83%89%E3%83%A9%E3%83%9C | https://play.google.com/store/search?q=%E3%82%AB%E3%83%BC%E3%83%89%E3%83%A9%E3%83%9C&c=apps&hl=ja |  |
| 11 | ゲオ | ゲオホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B2%E3%82%AA | https://play.google.com/store/search?q=%E3%82%B2%E3%82%AA&c=apps&hl=ja |  |
| 12 | タワーレコード | タワーレコード | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BF%E3%83%AF%E3%83%BC%E3%83%AC%E3%82%B3%E3%83%BC%E3%83%89 | https://play.google.com/store/search?q=%E3%82%BF%E3%83%AF%E3%83%BC%E3%83%AC%E3%82%B3%E3%83%BC%E3%83%89&c=apps&hl=ja |  |
| 13 | トレトク | トレトク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%88%E3%83%AC%E3%83%88%E3%82%AF | https://play.google.com/store/search?q=%E3%83%88%E3%83%AC%E3%83%88%E3%82%AF&c=apps&hl=ja |  |
| 14 | ハードオフ | ハードオフコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%BC%E3%83%89%E3%82%AA%E3%83%95 | https://play.google.com/store/search?q=%E3%83%8F%E3%83%BC%E3%83%89%E3%82%AA%E3%83%95&c=apps&hl=ja |  |
| 15 | ブックオフ | ブックオフグループホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%96%E3%83%83%E3%82%AF%E3%82%AA%E3%83%95 | https://play.google.com/store/search?q=%E3%83%96%E3%83%83%E3%82%AF%E3%82%AA%E3%83%95&c=apps&hl=ja |  |
| 16 | ホビーオフ | ハードオフコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%93%E3%83%BC%E3%82%AA%E3%83%95 | https://play.google.com/store/search?q=%E3%83%9B%E3%83%93%E3%83%BC%E3%82%AA%E3%83%95&c=apps&hl=ja |  |
| 17 | ポケモンセンター | ポケモン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%82%B1%E3%83%A2%E3%83%B3%E3%82%BB%E3%83%B3%E3%82%BF%E3%83%BC | https://play.google.com/store/search?q=%E3%83%9D%E3%82%B1%E3%83%A2%E3%83%B3%E3%82%BB%E3%83%B3%E3%82%BF%E3%83%BC&c=apps&hl=ja |  |
| 18 | マップカメラ | マップカメラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%83%E3%83%97%E3%82%AB%E3%83%A1%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%83%E3%83%97%E3%82%AB%E3%83%A1%E3%83%A9&c=apps&hl=ja |  |
| 19 | ヴィレッジヴァンガード | ヴィレッジヴァンガード | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%B4%E3%82%A3%E3%83%AC%E3%83%83%E3%82%B8%E3%83%B4%E3%82%A1%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%89 | https://play.google.com/store/search?q=%E3%83%B4%E3%82%A3%E3%83%AC%E3%83%83%E3%82%B8%E3%83%B4%E3%82%A1%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%89&c=apps&hl=ja |  |
| 20 | 三省堂書店 | 三省堂書店 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%89%E7%9C%81%E5%A0%82%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E4%B8%89%E7%9C%81%E5%A0%82%E6%9B%B8%E5%BA%97&c=apps&hl=ja |  |
| 21 | 丸善ジュンク堂書店 | 丸善CHIホールディングス | 確認済み(機能把握) | https://apps.apple.com/jp/search?term=%E4%B8%B8%E5%96%84%E3%82%B8%E3%83%A5%E3%83%B3%E3%82%AF%E5%A0%82%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E4%B8%B8%E5%96%84%E3%82%B8%E3%83%A5%E3%83%B3%E3%82%AF%E5%A0%82%E6%9B%B8%E5%BA%97&c=apps&hl=ja | 商品検索、店舗在庫、取り置き、取り寄せ、ポイント |
| 22 | 山野楽器 | 山野楽器 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B1%B1%E9%87%8E%E6%A5%BD%E5%99%A8 | https://play.google.com/store/search?q=%E5%B1%B1%E9%87%8E%E6%A5%BD%E5%99%A8&c=apps&hl=ja |  |
| 23 | 島村楽器 | 島村楽器 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B3%B6%E6%9D%91%E6%A5%BD%E5%99%A8 | https://play.google.com/store/search?q=%E5%B3%B6%E6%9D%91%E6%A5%BD%E5%99%A8&c=apps&hl=ja |  |
| 24 | 文教堂 | 文教堂グループホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E6%96%87%E6%95%99%E5%A0%82 | https://play.google.com/store/search?q=%E6%96%87%E6%95%99%E5%A0%82&c=apps&hl=ja |  |
| 25 | 有隣堂 | 有隣堂 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9C%89%E9%9A%A3%E5%A0%82 | https://play.google.com/store/search?q=%E6%9C%89%E9%9A%A3%E5%A0%82&c=apps&hl=ja |  |
| 26 | 未来屋書店 | イオン | 要確認 | https://apps.apple.com/jp/search?term=%E6%9C%AA%E6%9D%A5%E5%B1%8B%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E6%9C%AA%E6%9D%A5%E5%B1%8B%E6%9B%B8%E5%BA%97&c=apps&hl=ja |  |
| 27 | 石橋楽器 | 石橋楽器店 | 要確認 | https://apps.apple.com/jp/search?term=%E7%9F%B3%E6%A9%8B%E6%A5%BD%E5%99%A8 | https://play.google.com/store/search?q=%E7%9F%B3%E6%A9%8B%E6%A5%BD%E5%99%A8&c=apps&hl=ja |  |
| 28 | 紀伊國屋書店 | 紀伊國屋書店 | 要確認 | https://apps.apple.com/jp/search?term=%E7%B4%80%E4%BC%8A%E5%9C%8B%E5%B1%8B%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E7%B4%80%E4%BC%8A%E5%9C%8B%E5%B1%8B%E6%9B%B8%E5%BA%97&c=apps&hl=ja |  |
| 29 | 蔦屋書店 | CCC | 要確認 | https://apps.apple.com/jp/search?term=%E8%94%A6%E5%B1%8B%E6%9B%B8%E5%BA%97 | https://play.google.com/store/search?q=%E8%94%A6%E5%B1%8B%E6%9B%B8%E5%BA%97&c=apps&hl=ja |  |
| 30 | 駿河屋 | エーツー | 要確認 | https://apps.apple.com/jp/search?term=%E9%A7%BF%E6%B2%B3%E5%B1%8B | https://play.google.com/store/search?q=%E9%A7%BF%E6%B2%B3%E5%B1%8B&c=apps&hl=ja |  |

### 5.16 百貨店・商業施設（18件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | PARCO | パルコ | 要確認 | https://apps.apple.com/jp/search?term=PARCO | https://play.google.com/store/search?q=PARCO&c=apps&hl=ja |  |
| 2 | そごう・西武 | そごう・西武 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9D%E3%81%94%E3%81%86%E3%83%BB%E8%A5%BF%E6%AD%A6 | https://play.google.com/store/search?q=%E3%81%9D%E3%81%94%E3%81%86%E3%83%BB%E8%A5%BF%E6%AD%A6&c=apps&hl=ja |  |
| 3 | ららぽーと | 三井不動産商業マネジメント | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%89%E3%82%89%E3%81%BD%E3%83%BC%E3%81%A8 | https://play.google.com/store/search?q=%E3%82%89%E3%82%89%E3%81%BD%E3%83%BC%E3%81%A8&c=apps&hl=ja |  |
| 4 | アトレ | アトレ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%88%E3%83%AC | https://play.google.com/store/search?q=%E3%82%A2%E3%83%88%E3%83%AC&c=apps&hl=ja |  |
| 5 | イオンモール | イオンモール | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%A2%E3%83%BC%E3%83%AB | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%A2%E3%83%BC%E3%83%AB&c=apps&hl=ja |  |
| 6 | ルミネ | ルミネ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AB%E3%83%9F%E3%83%8D | https://play.google.com/store/search?q=%E3%83%AB%E3%83%9F%E3%83%8D&c=apps&hl=ja |  |
| 7 | 三井アウトレットパーク | 三井不動産商業マネジメント | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%89%E4%BA%95%E3%82%A2%E3%82%A6%E3%83%88%E3%83%AC%E3%83%83%E3%83%88%E3%83%91%E3%83%BC%E3%82%AF | https://play.google.com/store/search?q=%E4%B8%89%E4%BA%95%E3%82%A2%E3%82%A6%E3%83%88%E3%83%AC%E3%83%83%E3%83%88%E3%83%91%E3%83%BC%E3%82%AF&c=apps&hl=ja |  |
| 8 | 三越伊勢丹 | 三越伊勢丹ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%89%E8%B6%8A%E4%BC%8A%E5%8B%A2%E4%B8%B9 | https://play.google.com/store/search?q=%E4%B8%89%E8%B6%8A%E4%BC%8A%E5%8B%A2%E4%B8%B9&c=apps&hl=ja |  |
| 9 | 丸井 | 丸井グループ | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B8%E4%BA%95 | https://play.google.com/store/search?q=%E4%B8%B8%E4%BA%95&c=apps&hl=ja |  |
| 10 | 京王百貨店 | 京王百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%AC%E7%8E%8B%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E4%BA%AC%E7%8E%8B%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 11 | 大丸松坂屋 | J.フロント リテイリング | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A7%E4%B8%B8%E6%9D%BE%E5%9D%82%E5%B1%8B | https://play.google.com/store/search?q=%E5%A4%A7%E4%B8%B8%E6%9D%BE%E5%9D%82%E5%B1%8B&c=apps&hl=ja |  |
| 12 | 小田急百貨店 | 小田急百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B0%8F%E7%94%B0%E6%80%A5%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E5%B0%8F%E7%94%B0%E6%80%A5%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 13 | 東急百貨店 | 東急百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%80%A5%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E6%9D%B1%E6%80%A5%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 14 | 東武百貨店 | 東武百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%AD%A6%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E6%9D%B1%E6%AD%A6%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 15 | 松屋 | 松屋 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%BE%E5%B1%8B | https://play.google.com/store/search?q=%E6%9D%BE%E5%B1%8B&c=apps&hl=ja |  |
| 16 | 近鉄百貨店 | 近鉄百貨店 | 要確認 | https://apps.apple.com/jp/search?term=%E8%BF%91%E9%89%84%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E8%BF%91%E9%89%84%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 17 | 阪急阪神百貨店 | エイチ・ツー・オー リテイリング | 要確認 | https://apps.apple.com/jp/search?term=%E9%98%AA%E6%80%A5%E9%98%AA%E7%A5%9E%E7%99%BE%E8%B2%A8%E5%BA%97 | https://play.google.com/store/search?q=%E9%98%AA%E6%80%A5%E9%98%AA%E7%A5%9E%E7%99%BE%E8%B2%A8%E5%BA%97&c=apps&hl=ja |  |
| 18 | 高島屋 | 高島屋 | 要確認 | https://apps.apple.com/jp/search?term=%E9%AB%98%E5%B3%B6%E5%B1%8B | https://play.google.com/store/search?q=%E9%AB%98%E5%B3%B6%E5%B1%8B&c=apps&hl=ja |  |

### 5.17 美容・サロン（23件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | AUBE HAIR | AUBE | 要確認 | https://apps.apple.com/jp/search?term=AUBE%20HAIR | https://play.google.com/store/search?q=AUBE%20HAIR&c=apps&hl=ja |  |
| 2 | Agu. hair | AB&Company | 要確認 | https://apps.apple.com/jp/search?term=Agu.%20hair | https://play.google.com/store/search?q=Agu.%20hair&c=apps&hl=ja |  |
| 3 | Ash | アッシュ | 要確認 | https://apps.apple.com/jp/search?term=Ash | https://play.google.com/store/search?q=Ash&c=apps&hl=ja |  |
| 4 | EARTH | アースホールディングス | 要確認 | https://apps.apple.com/jp/search?term=EARTH | https://play.google.com/store/search?q=EARTH&c=apps&hl=ja |  |
| 5 | FASTNAIL | FASTNAIL | 要確認 | https://apps.apple.com/jp/search?term=FASTNAIL | https://play.google.com/store/search?q=FASTNAIL&c=apps&hl=ja |  |
| 6 | QBハウス | キュービーネットホールディングス | 要確認 | https://apps.apple.com/jp/search?term=QB%E3%83%8F%E3%82%A6%E3%82%B9 | https://play.google.com/store/search?q=QB%E3%83%8F%E3%82%A6%E3%82%B9&c=apps&hl=ja |  |
| 7 | Re.Ra.Ku | メディロム | 要確認 | https://apps.apple.com/jp/search?term=Re.Ra.Ku | https://play.google.com/store/search?q=Re.Ra.Ku&c=apps&hl=ja |  |
| 8 | TAYA | 田谷 | 要確認 | https://apps.apple.com/jp/search?term=TAYA | https://play.google.com/store/search?q=TAYA&c=apps&hl=ja |  |
| 9 | TBC | TBCグループ | 要確認 | https://apps.apple.com/jp/search?term=TBC | https://play.google.com/store/search?q=TBC&c=apps&hl=ja |  |
| 10 | たかの友梨 | たかの友梨ビューティクリニック | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%9F%E3%81%8B%E3%81%AE%E5%8F%8B%E6%A2%A8 | https://play.google.com/store/search?q=%E3%81%9F%E3%81%8B%E3%81%AE%E5%8F%8B%E6%A2%A8&c=apps&hl=ja |  |
| 11 | てもみん | グローバルスポーツ医学研究所 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A6%E3%82%82%E3%81%BF%E3%82%93 | https://play.google.com/store/search?q=%E3%81%A6%E3%82%82%E3%81%BF%E3%82%93&c=apps&hl=ja |  |
| 12 | りらくる | りらく | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%8A%E3%82%89%E3%81%8F%E3%82%8B | https://play.google.com/store/search?q=%E3%82%8A%E3%82%89%E3%81%8F%E3%82%8B&c=apps&hl=ja |  |
| 13 | エルセーヌ | エルセーヌ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A8%E3%83%AB%E3%82%BB%E3%83%BC%E3%83%8C | https://play.google.com/store/search?q=%E3%82%A8%E3%83%AB%E3%82%BB%E3%83%BC%E3%83%8C&c=apps&hl=ja |  |
| 14 | キレイモ | キレイモ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%AC%E3%82%A4%E3%83%A2 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%AC%E3%82%A4%E3%83%A2&c=apps&hl=ja |  |
| 15 | ジェイエステティック | ジェイエステ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%82%A7%E3%82%A4%E3%82%A8%E3%82%B9%E3%83%86%E3%83%86%E3%82%A3%E3%83%83%E3%82%AF | https://play.google.com/store/search?q=%E3%82%B8%E3%82%A7%E3%82%A4%E3%82%A8%E3%82%B9%E3%83%86%E3%83%86%E3%82%A3%E3%83%83%E3%82%AF&c=apps&hl=ja |  |
| 16 | ストラッシュ | ストラッシュ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%88%E3%83%A9%E3%83%83%E3%82%B7%E3%83%A5 | https://play.google.com/store/search?q=%E3%82%B9%E3%83%88%E3%83%A9%E3%83%83%E3%82%B7%E3%83%A5&c=apps&hl=ja |  |
| 17 | ソシエ | ソシエ・ワールド | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BD%E3%82%B7%E3%82%A8 | https://play.google.com/store/search?q=%E3%82%BD%E3%82%B7%E3%82%A8&c=apps&hl=ja |  |
| 18 | ネイルクイック | ネイルクイック | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8D%E3%82%A4%E3%83%AB%E3%82%AF%E3%82%A4%E3%83%83%E3%82%AF | https://play.google.com/store/search?q=%E3%83%8D%E3%82%A4%E3%83%AB%E3%82%AF%E3%82%A4%E3%83%83%E3%82%AF&c=apps&hl=ja |  |
| 19 | ミュゼプラチナム | MPH | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%83%A5%E3%82%BC%E3%83%97%E3%83%A9%E3%83%81%E3%83%8A%E3%83%A0 | https://play.google.com/store/search?q=%E3%83%9F%E3%83%A5%E3%82%BC%E3%83%97%E3%83%A9%E3%83%81%E3%83%8A%E3%83%A0&c=apps&hl=ja |  |
| 20 | メンズTBC | TBCグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A1%E3%83%B3%E3%82%BATBC | https://play.google.com/store/search?q=%E3%83%A1%E3%83%B3%E3%82%BATBC&c=apps&hl=ja |  |
| 21 | ラフィネ | ボディワーク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%83%95%E3%82%A3%E3%83%8D | https://play.google.com/store/search?q=%E3%83%A9%E3%83%95%E3%82%A3%E3%83%8D&c=apps&hl=ja |  |
| 22 | 美容室イレブンカット | エム・ワイ・ケー | 要確認 | https://apps.apple.com/jp/search?term=%E7%BE%8E%E5%AE%B9%E5%AE%A4%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3%E3%82%AB%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E7%BE%8E%E5%AE%B9%E5%AE%A4%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3%E3%82%AB%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 23 | 銀座カラー | 銀座カラー | 要確認 | https://apps.apple.com/jp/search?term=%E9%8A%80%E5%BA%A7%E3%82%AB%E3%83%A9%E3%83%BC | https://play.google.com/store/search?q=%E9%8A%80%E5%BA%A7%E3%82%AB%E3%83%A9%E3%83%BC&c=apps&hl=ja |  |

### 5.18 自動車・バイク・自転車（14件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 2りんかん | G-7ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=2%E3%82%8A%E3%82%93%E3%81%8B%E3%82%93 | https://play.google.com/store/search?q=2%E3%82%8A%E3%82%93%E3%81%8B%E3%82%93&c=apps&hl=ja |  |
| 2 | DAIWA CYCLE | ダイワサイクル | 要確認 | https://apps.apple.com/jp/search?term=DAIWA%20CYCLE | https://play.google.com/store/search?q=DAIWA%20CYCLE&c=apps&hl=ja |  |
| 3 | アップガレージ | アップガレージ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%83%E3%83%97%E3%82%AC%E3%83%AC%E3%83%BC%E3%82%B8 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%83%E3%83%97%E3%82%AC%E3%83%AC%E3%83%BC%E3%82%B8&c=apps&hl=ja |  |
| 4 | イエローハット | イエローハット | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 5 | イエローハット バイク館 | バイク館イエローハット | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88%20%E3%83%90%E3%82%A4%E3%82%AF%E9%A4%A8 | https://play.google.com/store/search?q=%E3%82%A4%E3%82%A8%E3%83%AD%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88%20%E3%83%90%E3%82%A4%E3%82%AF%E9%A4%A8&c=apps&hl=ja |  |
| 6 | イオンバイク | イオンバイク | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%90%E3%82%A4%E3%82%AF | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3%E3%83%90%E3%82%A4%E3%82%AF&c=apps&hl=ja |  |
| 7 | オートバックス | オートバックスセブン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%BC%E3%83%88%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%AA%E3%83%BC%E3%83%88%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9&c=apps&hl=ja |  |
| 8 | サイクルベースあさひ | あさひ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%82%A4%E3%82%AF%E3%83%AB%E3%83%99%E3%83%BC%E3%82%B9%E3%81%82%E3%81%95%E3%81%B2 | https://play.google.com/store/search?q=%E3%82%B5%E3%82%A4%E3%82%AF%E3%83%AB%E3%83%99%E3%83%BC%E3%82%B9%E3%81%82%E3%81%95%E3%81%B2&c=apps&hl=ja |  |
| 9 | ジェームス | G-7ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%82%A7%E3%83%BC%E3%83%A0%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%B8%E3%82%A7%E3%83%BC%E3%83%A0%E3%82%B9&c=apps&hl=ja |  |
| 10 | タイヤ館 | ブリヂストンリテールジャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BF%E3%82%A4%E3%83%A4%E9%A4%A8 | https://play.google.com/store/search?q=%E3%82%BF%E3%82%A4%E3%83%A4%E9%A4%A8&c=apps&hl=ja |  |
| 11 | ナップス | ナップス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8A%E3%83%83%E3%83%97%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%8A%E3%83%83%E3%83%97%E3%82%B9&c=apps&hl=ja |  |
| 12 | バイク王 | バイク王＆カンパニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%82%A4%E3%82%AF%E7%8E%8B | https://play.google.com/store/search?q=%E3%83%90%E3%82%A4%E3%82%AF%E7%8E%8B&c=apps&hl=ja |  |
| 13 | レッドバロン | レッドバロン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AC%E3%83%83%E3%83%89%E3%83%90%E3%83%AD%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%AC%E3%83%83%E3%83%89%E3%83%90%E3%83%AD%E3%83%B3&c=apps&hl=ja |  |
| 14 | ワイズロード | ワイ・インターナショナル | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%82%A4%E3%82%BA%E3%83%AD%E3%83%BC%E3%83%89 | https://play.google.com/store/search?q=%E3%83%AF%E3%82%A4%E3%82%BA%E3%83%AD%E3%83%BC%E3%83%89&c=apps&hl=ja |  |

### 5.19 花・園芸（12件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | DCM 園芸 | DCM | 要確認 | https://apps.apple.com/jp/search?term=DCM%20%E5%9C%92%E8%8A%B8 | https://play.google.com/store/search?q=DCM%20%E5%9C%92%E8%8A%B8&c=apps&hl=ja |  |
| 2 | カインズ園芸 | カインズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%82%A4%E3%83%B3%E3%82%BA%E5%9C%92%E8%8A%B8 | https://play.google.com/store/search?q=%E3%82%AB%E3%82%A4%E3%83%B3%E3%82%BA%E5%9C%92%E8%8A%B8&c=apps&hl=ja |  |
| 3 | サカタのタネ | サカタのタネ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%82%AB%E3%82%BF%E3%81%AE%E3%82%BF%E3%83%8D | https://play.google.com/store/search?q=%E3%82%B5%E3%82%AB%E3%82%BF%E3%81%AE%E3%82%BF%E3%83%8D&c=apps&hl=ja |  |
| 4 | ニコライバーグマン | ニコライ バーグマン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8B%E3%82%B3%E3%83%A9%E3%82%A4%E3%83%90%E3%83%BC%E3%82%B0%E3%83%9E%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%8B%E3%82%B3%E3%83%A9%E3%82%A4%E3%83%90%E3%83%BC%E3%82%B0%E3%83%9E%E3%83%B3&c=apps&hl=ja |  |
| 5 | フラワーショップ 花次郎 | 花次郎 | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%83%A9%E3%83%AF%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%20%E8%8A%B1%E6%AC%A1%E9%83%8E | https://play.google.com/store/search?q=%E3%83%95%E3%83%A9%E3%83%AF%E3%83%BC%E3%82%B7%E3%83%A7%E3%83%83%E3%83%97%20%E8%8A%B1%E6%AC%A1%E9%83%8E&c=apps&hl=ja |  |
| 6 | プロトリーフ | プロトリーフ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%AD%E3%83%88%E3%83%AA%E3%83%BC%E3%83%95 | https://play.google.com/store/search?q=%E3%83%97%E3%83%AD%E3%83%88%E3%83%AA%E3%83%BC%E3%83%95&c=apps&hl=ja |  |
| 7 | ユニディ | アイリスプラザ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A6%E3%83%8B%E3%83%87%E3%82%A3 | https://play.google.com/store/search?q=%E3%83%A6%E3%83%8B%E3%83%87%E3%82%A3&c=apps&hl=ja |  |
| 8 | 京成バラ園芸 | 京成バラ園芸 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%AC%E6%88%90%E3%83%90%E3%83%A9%E5%9C%92%E8%8A%B8 | https://play.google.com/store/search?q=%E4%BA%AC%E6%88%90%E3%83%90%E3%83%A9%E5%9C%92%E8%8A%B8&c=apps&hl=ja |  |
| 9 | 日比谷花壇 | 日比谷花壇 | 要確認 | https://apps.apple.com/jp/search?term=%E6%97%A5%E6%AF%94%E8%B0%B7%E8%8A%B1%E5%A3%87 | https://play.google.com/store/search?q=%E6%97%A5%E6%AF%94%E8%B0%B7%E8%8A%B1%E5%A3%87&c=apps&hl=ja |  |
| 10 | 第一園芸 | 第一園芸 | 要確認 | https://apps.apple.com/jp/search?term=%E7%AC%AC%E4%B8%80%E5%9C%92%E8%8A%B8 | https://play.google.com/store/search?q=%E7%AC%AC%E4%B8%80%E5%9C%92%E8%8A%B8&c=apps&hl=ja |  |
| 11 | 花キューピット | 花キューピット | 要確認 | https://apps.apple.com/jp/search?term=%E8%8A%B1%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%94%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E8%8A%B1%E3%82%AD%E3%83%A5%E3%83%BC%E3%83%94%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 12 | 青山フラワーマーケット | パーク・コーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E9%9D%92%E5%B1%B1%E3%83%95%E3%83%A9%E3%83%AF%E3%83%BC%E3%83%9E%E3%83%BC%E3%82%B1%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E9%9D%92%E5%B1%B1%E3%83%95%E3%83%A9%E3%83%AF%E3%83%BC%E3%83%9E%E3%83%BC%E3%82%B1%E3%83%83%E3%83%88&c=apps&hl=ja |  |

### 5.20 雑貨・家具・生活用品（39件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | 3COINS | パルグループ | 要確認 | https://apps.apple.com/jp/search?term=3COINS | https://play.google.com/store/search?q=3COINS&c=apps&hl=ja |  |
| 2 | Can Do | キャンドゥ | 要確認 | https://apps.apple.com/jp/search?term=Can%20Do | https://play.google.com/store/search?q=Can%20Do&c=apps&hl=ja |  |
| 3 | DAISO | 大創産業 | 要確認 | https://apps.apple.com/jp/search?term=DAISO | https://play.google.com/store/search?q=DAISO&c=apps&hl=ja |  |
| 4 | DCM | DCMホールディングス | 要確認 | https://apps.apple.com/jp/search?term=DCM | https://play.google.com/store/search?q=DCM&c=apps&hl=ja |  |
| 5 | Francfranc | Francfranc | 要確認 | https://apps.apple.com/jp/search?term=Francfranc | https://play.google.com/store/search?q=Francfranc&c=apps&hl=ja |  |
| 6 | IDC OTSUKA | 大塚家具関連 | 要確認 | https://apps.apple.com/jp/search?term=IDC%20OTSUKA | https://play.google.com/store/search?q=IDC%20OTSUKA&c=apps&hl=ja |  |
| 7 | IKEA | IKEA Japan | 確認済み | https://apps.apple.com/jp/app/ikea/id1452164827 | https://play.google.com/store/search?q=IKEA&c=apps&hl=ja | 店舗マップ、在庫、リスト、スキャン |
| 8 | KEYUCA | 河淳 | 要確認 | https://apps.apple.com/jp/search?term=KEYUCA | https://play.google.com/store/search?q=KEYUCA&c=apps&hl=ja |  |
| 9 | Loft | ロフト | 要確認 | https://apps.apple.com/jp/search?term=Loft | https://play.google.com/store/search?q=Loft&c=apps&hl=ja |  |
| 10 | Olympic | Olympicグループ | 要確認 | https://apps.apple.com/jp/search?term=Olympic | https://play.google.com/store/search?q=Olympic&c=apps&hl=ja |  |
| 11 | PLAZA | スタイリングライフ・ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=PLAZA | https://play.google.com/store/search?q=PLAZA&c=apps&hl=ja |  |
| 12 | Seria | セリア | 要確認 | https://apps.apple.com/jp/search?term=Seria | https://play.google.com/store/search?q=Seria&c=apps&hl=ja |  |
| 13 | アクタス | アクタス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%82%AF%E3%82%BF%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%A2%E3%82%AF%E3%82%BF%E3%82%B9&c=apps&hl=ja |  |
| 14 | アフタヌーンティー・リビング | サザビーリーグ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%83%95%E3%82%BF%E3%83%8C%E3%83%BC%E3%83%B3%E3%83%86%E3%82%A3%E3%83%BC%E3%83%BB%E3%83%AA%E3%83%93%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%82%A2%E3%83%95%E3%82%BF%E3%83%8C%E3%83%BC%E3%83%B3%E3%83%86%E3%82%A3%E3%83%BC%E3%83%BB%E3%83%AA%E3%83%93%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 15 | カインズ | カインズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%82%A4%E3%83%B3%E3%82%BA | https://play.google.com/store/search?q=%E3%82%AB%E3%82%A4%E3%83%B3%E3%82%BA&c=apps&hl=ja |  |
| 16 | キデイランド | キデイランド | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%87%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89 | https://play.google.com/store/search?q=%E3%82%AD%E3%83%87%E3%82%A4%E3%83%A9%E3%83%B3%E3%83%89&c=apps&hl=ja |  |
| 17 | ケーヨーデイツー | ケーヨー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B1%E3%83%BC%E3%83%A8%E3%83%BC%E3%83%87%E3%82%A4%E3%83%84%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B1%E3%83%BC%E3%83%A8%E3%83%BC%E3%83%87%E3%82%A4%E3%83%84%E3%83%BC&c=apps&hl=ja |  |
| 18 | コメリ | コメリ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%A1%E3%83%AA | https://play.google.com/store/search?q=%E3%82%B3%E3%83%A1%E3%83%AA&c=apps&hl=ja |  |
| 19 | コーナン | コーナン商事 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%B3&c=apps&hl=ja |  |
| 20 | ジュンテンドー | ジュンテンドー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A5%E3%83%B3%E3%83%86%E3%83%B3%E3%83%89%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A5%E3%83%B3%E3%83%86%E3%83%B3%E3%83%89%E3%83%BC&c=apps&hl=ja |  |
| 21 | ジョイフル本田 | ジョイフル本田 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A7%E3%82%A4%E3%83%95%E3%83%AB%E6%9C%AC%E7%94%B0 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A7%E3%82%A4%E3%83%95%E3%83%AB%E6%9C%AC%E7%94%B0&c=apps&hl=ja |  |
| 22 | スタディオクリップ | スタディオクリップ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%BF%E3%83%87%E3%82%A3%E3%82%AA%E3%82%AF%E3%83%AA%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%BF%E3%83%87%E3%82%A3%E3%82%AA%E3%82%AF%E3%83%AA%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 23 | ナフコ | ナフコ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8A%E3%83%95%E3%82%B3 | https://play.google.com/store/search?q=%E3%83%8A%E3%83%95%E3%82%B3&c=apps&hl=ja |  |
| 24 | ニトリ | ニトリホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8B%E3%83%88%E3%83%AA | https://play.google.com/store/search?q=%E3%83%8B%E3%83%88%E3%83%AA&c=apps&hl=ja |  |
| 25 | ハンズ | ハンズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%B3%E3%82%BA | https://play.google.com/store/search?q=%E3%83%8F%E3%83%B3%E3%82%BA&c=apps&hl=ja |  |
| 26 | ハンズマン | ハンズマン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%B3%E3%82%BA%E3%83%9E%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%8F%E3%83%B3%E3%82%BA%E3%83%9E%E3%83%B3&c=apps&hl=ja |  |
| 27 | パスポート | パスポート | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%91%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%83%91%E3%82%B9%E3%83%9D%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 28 | ビバホーム | アークランズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%83%90%E3%83%9B%E3%83%BC%E3%83%A0 | https://play.google.com/store/search?q=%E3%83%93%E3%83%90%E3%83%9B%E3%83%BC%E3%83%A0&c=apps&hl=ja |  |
| 29 | ブルーブルーエ | ブルーブルーエ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%96%E3%83%AB%E3%83%BC%E3%83%96%E3%83%AB%E3%83%BC%E3%82%A8 | https://play.google.com/store/search?q=%E3%83%96%E3%83%AB%E3%83%BC%E3%83%96%E3%83%AB%E3%83%BC%E3%82%A8&c=apps&hl=ja |  |
| 30 | ワンズテラス | ワンズテラス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AF%E3%83%B3%E3%82%BA%E3%83%86%E3%83%A9%E3%82%B9 | https://play.google.com/store/search?q=%E3%83%AF%E3%83%B3%E3%82%BA%E3%83%86%E3%83%A9%E3%82%B9&c=apps&hl=ja |  |
| 31 | ヴィレッジヴァンガード | ヴィレッジヴァンガード | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%B4%E3%82%A3%E3%83%AC%E3%83%83%E3%82%B8%E3%83%B4%E3%82%A1%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%89 | https://play.google.com/store/search?q=%E3%83%B4%E3%82%A3%E3%83%AC%E3%83%83%E3%82%B8%E3%83%B4%E3%82%A1%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%89&c=apps&hl=ja |  |
| 32 | 山新 | 山新 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B1%B1%E6%96%B0 | https://play.google.com/store/search?q=%E5%B1%B1%E6%96%B0&c=apps&hl=ja |  |
| 33 | 島忠 | ニトリホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%B3%B6%E5%BF%A0 | https://play.google.com/store/search?q=%E5%B3%B6%E5%BF%A0&c=apps&hl=ja |  |
| 34 | 島忠ホームズ | ニトリホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%B3%B6%E5%BF%A0%E3%83%9B%E3%83%BC%E3%83%A0%E3%82%BA | https://play.google.com/store/search?q=%E5%B3%B6%E5%BF%A0%E3%83%9B%E3%83%BC%E3%83%A0%E3%82%BA&c=apps&hl=ja |  |
| 35 | 東京インテリア家具 | 東京インテリア | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%82%A4%E3%83%B3%E3%83%86%E3%83%AA%E3%82%A2%E5%AE%B6%E5%85%B7 | https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%82%A4%E3%83%B3%E3%83%86%E3%83%AA%E3%82%A2%E5%AE%B6%E5%85%B7&c=apps&hl=ja |  |
| 36 | 東急ハンズ | ハンズ | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%80%A5%E3%83%8F%E3%83%B3%E3%82%BA | https://play.google.com/store/search?q=%E6%9D%B1%E6%80%A5%E3%83%8F%E3%83%B3%E3%82%BA&c=apps&hl=ja |  |
| 37 | 無印良品 | 良品計画 | 要確認 | https://apps.apple.com/jp/search?term=%E7%84%A1%E5%8D%B0%E8%89%AF%E5%93%81 | https://play.google.com/store/search?q=%E7%84%A1%E5%8D%B0%E8%89%AF%E5%93%81&c=apps&hl=ja |  |
| 38 | 私の部屋リビング | 私の部屋 | 要確認 | https://apps.apple.com/jp/search?term=%E7%A7%81%E3%81%AE%E9%83%A8%E5%B1%8B%E3%83%AA%E3%83%93%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E7%A7%81%E3%81%AE%E9%83%A8%E5%B1%8B%E3%83%AA%E3%83%93%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 39 | 綿半 | 綿半ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E7%B6%BF%E5%8D%8A | https://play.google.com/store/search?q=%E7%B6%BF%E5%8D%8A&c=apps&hl=ja |  |

### 5.21 食品スーパー・食品小売（48件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | AKOMEYA TOKYO | AKOMEYA | 要確認 | https://apps.apple.com/jp/search?term=AKOMEYA%20TOKYO | https://play.google.com/store/search?q=AKOMEYA%20TOKYO&c=apps&hl=ja |  |
| 2 | いかりスーパーマーケット | いかり | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%84%E3%81%8B%E3%82%8A%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%83%9E%E3%83%BC%E3%82%B1%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%81%84%E3%81%8B%E3%82%8A%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC%E3%83%9E%E3%83%BC%E3%82%B1%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 3 | いなげや | いなげや | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%84%E3%81%AA%E3%81%92%E3%82%84 | https://play.google.com/store/search?q=%E3%81%84%E3%81%AA%E3%81%92%E3%82%84&c=apps&hl=ja |  |
| 4 | イオン | イオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%AA%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A4%E3%82%AA%E3%83%B3&c=apps&hl=ja |  |
| 5 | イズミ | イズミ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%82%BA%E3%83%9F | https://play.google.com/store/search?q=%E3%82%A4%E3%82%BA%E3%83%9F&c=apps&hl=ja |  |
| 6 | イトーヨーカドー | セブン&アイ・ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A4%E3%83%88%E3%83%BC%E3%83%A8%E3%83%BC%E3%82%AB%E3%83%89%E3%83%BC | https://play.google.com/store/search?q=%E3%82%A4%E3%83%88%E3%83%BC%E3%83%A8%E3%83%BC%E3%82%AB%E3%83%89%E3%83%BC&c=apps&hl=ja |  |
| 7 | オークワ | オークワ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%AF | https://play.google.com/store/search?q=%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%AF&c=apps&hl=ja |  |
| 8 | オーケー | オーケー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%BC%E3%82%B1%E3%83%BC | https://play.google.com/store/search?q=%E3%82%AA%E3%83%BC%E3%82%B1%E3%83%BC&c=apps&hl=ja |  |
| 9 | カルディコーヒーファーム | キャメル珈琲 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AB%E3%83%AB%E3%83%87%E3%82%A3%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC%E3%83%95%E3%82%A1%E3%83%BC%E3%83%A0 | https://play.google.com/store/search?q=%E3%82%AB%E3%83%AB%E3%83%87%E3%82%A3%E3%82%B3%E3%83%BC%E3%83%92%E3%83%BC%E3%83%95%E3%82%A1%E3%83%BC%E3%83%A0&c=apps&hl=ja |  |
| 10 | キヨスク | JR各社 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AD%E3%83%A8%E3%82%B9%E3%82%AF | https://play.google.com/store/search?q=%E3%82%AD%E3%83%A8%E3%82%B9%E3%82%AF&c=apps&hl=ja |  |
| 11 | コストコ | コストコホールセールジャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B9%E3%83%88%E3%82%B3 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B9%E3%83%88%E3%82%B3&c=apps&hl=ja |  |
| 12 | コープ | 日本生活協同組合連合会 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%BC%E3%83%97 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%BC%E3%83%97&c=apps&hl=ja |  |
| 13 | サミット | サミット | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%9F%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%9F%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 14 | セイコーマート | セイコーマート | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BB%E3%82%A4%E3%82%B3%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%82%BB%E3%82%A4%E3%82%B3%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 15 | セブン-イレブン | セブン&アイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BB%E3%83%96%E3%83%B3-%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%BB%E3%83%96%E3%83%B3-%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3&c=apps&hl=ja |  |
| 16 | セブンイレブン | セブン&アイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%BB%E3%83%96%E3%83%B3%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%BB%E3%83%96%E3%83%B3%E3%82%A4%E3%83%AC%E3%83%96%E3%83%B3&c=apps&hl=ja |  |
| 17 | ダイエー | イオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%80%E3%82%A4%E3%82%A8%E3%83%BC | https://play.google.com/store/search?q=%E3%83%80%E3%82%A4%E3%82%A8%E3%83%BC&c=apps&hl=ja |  |
| 18 | デイリーヤマザキ | ヤマザキ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%87%E3%82%A4%E3%83%AA%E3%83%BC%E3%83%A4%E3%83%9E%E3%82%B6%E3%82%AD | https://play.google.com/store/search?q=%E3%83%87%E3%82%A4%E3%83%AA%E3%83%BC%E3%83%A4%E3%83%9E%E3%82%B6%E3%82%AD&c=apps&hl=ja |  |
| 19 | トライアル | トライアルホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%88%E3%83%A9%E3%82%A4%E3%82%A2%E3%83%AB | https://play.google.com/store/search?q=%E3%83%88%E3%83%A9%E3%82%A4%E3%82%A2%E3%83%AB&c=apps&hl=ja |  |
| 20 | ナチュラルローソン | ローソン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8A%E3%83%81%E3%83%A5%E3%83%A9%E3%83%AB%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%8A%E3%83%81%E3%83%A5%E3%83%A9%E3%83%AB%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3&c=apps&hl=ja |  |
| 21 | ニューデイズ | JR東日本クロスステーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8B%E3%83%A5%E3%83%BC%E3%83%87%E3%82%A4%E3%82%BA | https://play.google.com/store/search?q=%E3%83%8B%E3%83%A5%E3%83%BC%E3%83%87%E3%82%A4%E3%82%BA&c=apps&hl=ja |  |
| 22 | ファミマ | ファミリーマート | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A1%E3%83%9F%E3%83%9E | https://play.google.com/store/search?q=%E3%83%95%E3%82%A1%E3%83%9F%E3%83%9E&c=apps&hl=ja |  |
| 23 | ファミリーマート | ファミリーマート | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A1%E3%83%9F%E3%83%AA%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%83%95%E3%82%A1%E3%83%9F%E3%83%AA%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 24 | ベイシア | ベイシア | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%82%A4%E3%82%B7%E3%82%A2 | https://play.google.com/store/search?q=%E3%83%99%E3%82%A4%E3%82%B7%E3%82%A2&c=apps&hl=ja |  |
| 25 | ベルク | ベルク | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%99%E3%83%AB%E3%82%AF | https://play.google.com/store/search?q=%E3%83%99%E3%83%AB%E3%82%AF&c=apps&hl=ja |  |
| 26 | ポプラ | ポプラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9D%E3%83%97%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%9D%E3%83%97%E3%83%A9&c=apps&hl=ja |  |
| 27 | マックスバリュ | イオン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%83%E3%82%AF%E3%82%B9%E3%83%90%E3%83%AA%E3%83%A5 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%83%E3%82%AF%E3%82%B9%E3%83%90%E3%83%AA%E3%83%A5&c=apps&hl=ja |  |
| 28 | マミーマート | マミーマート | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%9F%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%9F%E3%83%BC%E3%83%9E%E3%83%BC%E3%83%88&c=apps&hl=ja |  |
| 29 | マルエツ | マルエツ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9E%E3%83%AB%E3%82%A8%E3%83%84 | https://play.google.com/store/search?q=%E3%83%9E%E3%83%AB%E3%82%A8%E3%83%84&c=apps&hl=ja |  |
| 30 | ミニストップ | ミニストップ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9F%E3%83%8B%E3%82%B9%E3%83%88%E3%83%83%E3%83%97 | https://play.google.com/store/search?q=%E3%83%9F%E3%83%8B%E3%82%B9%E3%83%88%E3%83%83%E3%83%97&c=apps&hl=ja |  |
| 31 | ヤオコー | ヤオコー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A4%E3%82%AA%E3%82%B3%E3%83%BC | https://play.google.com/store/search?q=%E3%83%A4%E3%82%AA%E3%82%B3%E3%83%BC&c=apps&hl=ja |  |
| 32 | ヨークベニマル | セブン&アイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A8%E3%83%BC%E3%82%AF%E3%83%99%E3%83%8B%E3%83%9E%E3%83%AB | https://play.google.com/store/search?q=%E3%83%A8%E3%83%BC%E3%82%AF%E3%83%99%E3%83%8B%E3%83%9E%E3%83%AB&c=apps&hl=ja |  |
| 33 | ライフ | ライフコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%82%A4%E3%83%95 | https://play.google.com/store/search?q=%E3%83%A9%E3%82%A4%E3%83%95&c=apps&hl=ja |  |
| 34 | ロピア | OICグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%94%E3%82%A2 | https://play.google.com/store/search?q=%E3%83%AD%E3%83%94%E3%82%A2&c=apps&hl=ja |  |
| 35 | ローソン | ローソン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3&c=apps&hl=ja |  |
| 36 | ローソンストア100 | ローソン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3%E3%82%B9%E3%83%88%E3%82%A2100 | https://play.google.com/store/search?q=%E3%83%AD%E3%83%BC%E3%82%BD%E3%83%B3%E3%82%B9%E3%83%88%E3%82%A2100&c=apps&hl=ja |  |
| 37 | 万代 | 万代 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%87%E4%BB%A3 | https://play.google.com/store/search?q=%E4%B8%87%E4%BB%A3&c=apps&hl=ja |  |
| 38 | 久世福商店 | サンクゼール | 要確認 | https://apps.apple.com/jp/search?term=%E4%B9%85%E4%B8%96%E7%A6%8F%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E4%B9%85%E4%B8%96%E7%A6%8F%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 39 | 北野エース | エース | 要確認 | https://apps.apple.com/jp/search?term=%E5%8C%97%E9%87%8E%E3%82%A8%E3%83%BC%E3%82%B9 | https://play.google.com/store/search?q=%E5%8C%97%E9%87%8E%E3%82%A8%E3%83%BC%E3%82%B9&c=apps&hl=ja |  |
| 40 | 平和堂 | 平和堂 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B9%B3%E5%92%8C%E5%A0%82 | https://play.google.com/store/search?q=%E5%B9%B3%E5%92%8C%E5%A0%82&c=apps&hl=ja |  |
| 41 | 成城石井 | 成城石井 | 要確認 | https://apps.apple.com/jp/search?term=%E6%88%90%E5%9F%8E%E7%9F%B3%E4%BA%95 | https://play.google.com/store/search?q=%E6%88%90%E5%9F%8E%E7%9F%B3%E4%BA%95&c=apps&hl=ja |  |
| 42 | 東急ストア | 東急ストア | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%B1%E6%80%A5%E3%82%B9%E3%83%88%E3%82%A2 | https://play.google.com/store/search?q=%E6%9D%B1%E6%80%A5%E3%82%B9%E3%83%88%E3%82%A2&c=apps&hl=ja |  |
| 43 | 業務スーパー | 神戸物産 | 要確認 | https://apps.apple.com/jp/search?term=%E6%A5%AD%E5%8B%99%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC | https://play.google.com/store/search?q=%E6%A5%AD%E5%8B%99%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC&c=apps&hl=ja |  |
| 44 | 紀ノ国屋 | 紀ノ國屋 | 要確認 | https://apps.apple.com/jp/search?term=%E7%B4%80%E3%83%8E%E5%9B%BD%E5%B1%8B | https://play.google.com/store/search?q=%E7%B4%80%E3%83%8E%E5%9B%BD%E5%B1%8B&c=apps&hl=ja |  |
| 45 | 茅乃舎 | 久原本家 | 要確認 | https://apps.apple.com/jp/search?term=%E8%8C%85%E4%B9%83%E8%88%8E | https://play.google.com/store/search?q=%E8%8C%85%E4%B9%83%E8%88%8E&c=apps&hl=ja |  |
| 46 | 西友 | 西友 | 要確認 | https://apps.apple.com/jp/search?term=%E8%A5%BF%E5%8F%8B | https://play.google.com/store/search?q=%E8%A5%BF%E5%8F%8B&c=apps&hl=ja |  |
| 47 | 関西スーパー | 関西スーパーマーケット | 要確認 | https://apps.apple.com/jp/search?term=%E9%96%A2%E8%A5%BF%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC | https://play.google.com/store/search?q=%E9%96%A2%E8%A5%BF%E3%82%B9%E3%83%BC%E3%83%91%E3%83%BC&c=apps&hl=ja |  |
| 48 | 阪急オアシス | エイチ・ツー・オー リテイリング | 要確認 | https://apps.apple.com/jp/search?term=%E9%98%AA%E6%80%A5%E3%82%AA%E3%82%A2%E3%82%B7%E3%82%B9 | https://play.google.com/store/search?q=%E9%98%AA%E6%80%A5%E3%82%AA%E3%82%A2%E3%82%B7%E3%82%B9&c=apps&hl=ja |  |

### 5.22 飲食・ファストフード（129件）

| # | ブランド/店舗チェーン | 運営会社 | 確認状況 | App Store UI確認URL | Google Play UI確認URL | UI確認ポイント |
|---|----------------------|----------|----------|---------------------|----------------------|----------------|
| 1 | CoCo壱番屋 | 壱番屋 | 要確認 | https://apps.apple.com/jp/search?term=CoCo%E5%A3%B1%E7%95%AA%E5%B1%8B | https://play.google.com/store/search?q=CoCo%E5%A3%B1%E7%95%AA%E5%B1%8B&c=apps&hl=ja |  |
| 2 | TGIフライデーズ | TGIフライデーズ | 要確認 | https://apps.apple.com/jp/search?term=TGI%E3%83%95%E3%83%A9%E3%82%A4%E3%83%87%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=TGI%E3%83%95%E3%83%A9%E3%82%A4%E3%83%87%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 3 | いきなりステーキ | ペッパーフードサービス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%84%E3%81%8D%E3%81%AA%E3%82%8A%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD | https://play.google.com/store/search?q=%E3%81%84%E3%81%8D%E3%81%AA%E3%82%8A%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD&c=apps&hl=ja |  |
| 4 | かごの屋 | クリエイト・レストランツホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8B%E3%81%94%E3%81%AE%E5%B1%8B | https://play.google.com/store/search?q=%E3%81%8B%E3%81%94%E3%81%AE%E5%B1%8B&c=apps&hl=ja |  |
| 5 | かっぱ寿司 | カッパ・クリエイト | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8B%E3%81%A3%E3%81%B1%E5%AF%BF%E5%8F%B8 | https://play.google.com/store/search?q=%E3%81%8B%E3%81%A3%E3%81%B1%E5%AF%BF%E5%8F%B8&c=apps&hl=ja |  |
| 6 | かつや | アークランドサービスホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8B%E3%81%A4%E3%82%84 | https://play.google.com/store/search?q=%E3%81%8B%E3%81%A4%E3%82%84&c=apps&hl=ja |  |
| 7 | くら寿司 | くら寿司 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%8F%E3%82%89%E5%AF%BF%E5%8F%B8 | https://play.google.com/store/search?q=%E3%81%8F%E3%82%89%E5%AF%BF%E5%8F%B8&c=apps&hl=ja |  |
| 8 | さかなや道場 | チムニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%95%E3%81%8B%E3%81%AA%E3%82%84%E9%81%93%E5%A0%B4 | https://play.google.com/store/search?q=%E3%81%95%E3%81%8B%E3%81%AA%E3%82%84%E9%81%93%E5%A0%B4&c=apps&hl=ja |  |
| 9 | しゃぶしゃぶ温野菜 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%97%E3%82%83%E3%81%B6%E3%81%97%E3%82%83%E3%81%B6%E6%B8%A9%E9%87%8E%E8%8F%9C | https://play.google.com/store/search?q=%E3%81%97%E3%82%83%E3%81%B6%E3%81%97%E3%82%83%E3%81%B6%E6%B8%A9%E9%87%8E%E8%8F%9C&c=apps&hl=ja |  |
| 10 | しゃぶ菜 | クリエイト・レストランツホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%97%E3%82%83%E3%81%B6%E8%8F%9C | https://play.google.com/store/search?q=%E3%81%97%E3%82%83%E3%81%B6%E8%8F%9C&c=apps&hl=ja |  |
| 11 | しゃぶ葉 | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%97%E3%82%83%E3%81%B6%E8%91%89 | https://play.google.com/store/search?q=%E3%81%97%E3%82%83%E3%81%B6%E8%91%89&c=apps&hl=ja |  |
| 12 | すき家 | ゼンショーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%99%E3%81%8D%E5%AE%B6 | https://play.google.com/store/search?q=%E3%81%99%E3%81%8D%E5%AE%B6&c=apps&hl=ja |  |
| 13 | すき家モバイルオーダー | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%99%E3%81%8D%E5%AE%B6%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%AA%E3%83%BC%E3%83%80%E3%83%BC | https://play.google.com/store/search?q=%E3%81%99%E3%81%8D%E5%AE%B6%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%AA%E3%83%BC%E3%83%80%E3%83%BC&c=apps&hl=ja |  |
| 14 | とりどーる | トリドール | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A8%E3%82%8A%E3%81%A9%E3%83%BC%E3%82%8B | https://play.google.com/store/search?q=%E3%81%A8%E3%82%8A%E3%81%A9%E3%83%BC%E3%82%8B&c=apps&hl=ja |  |
| 15 | とり鉄 | ジー・テイスト | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A8%E3%82%8A%E9%89%84 | https://play.google.com/store/search?q=%E3%81%A8%E3%82%8A%E9%89%84&c=apps&hl=ja |  |
| 16 | どうとんぼり神座 | どうとんぼり神座 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%A9%E3%81%86%E3%81%A8%E3%82%93%E3%81%BC%E3%82%8A%E7%A5%9E%E5%BA%A7 | https://play.google.com/store/search?q=%E3%81%A9%E3%81%86%E3%81%A8%E3%82%93%E3%81%BC%E3%82%8A%E7%A5%9E%E5%BA%A7&c=apps&hl=ja |  |
| 17 | なか卯 | ゼンショーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AA%E3%81%8B%E5%8D%AF | https://play.google.com/store/search?q=%E3%81%AA%E3%81%8B%E5%8D%AF&c=apps&hl=ja |  |
| 18 | にぎりの徳兵衛 | アトム | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AB%E3%81%8E%E3%82%8A%E3%81%AE%E5%BE%B3%E5%85%B5%E8%A1%9B | https://play.google.com/store/search?q=%E3%81%AB%E3%81%8E%E3%82%8A%E3%81%AE%E5%BE%B3%E5%85%B5%E8%A1%9B&c=apps&hl=ja |  |
| 19 | はなの舞 | チムニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AF%E3%81%AA%E3%81%AE%E8%88%9E | https://play.google.com/store/search?q=%E3%81%AF%E3%81%AA%E3%81%AE%E8%88%9E&c=apps&hl=ja |  |
| 20 | はなまるうどん | 吉野家ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AF%E3%81%AA%E3%81%BE%E3%82%8B%E3%81%86%E3%81%A9%E3%82%93 | https://play.google.com/store/search?q=%E3%81%AF%E3%81%AA%E3%81%BE%E3%82%8B%E3%81%86%E3%81%A9%E3%82%93&c=apps&hl=ja |  |
| 21 | はまぐり屋 | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AF%E3%81%BE%E3%81%90%E3%82%8A%E5%B1%8B | https://play.google.com/store/search?q=%E3%81%AF%E3%81%BE%E3%81%90%E3%82%8A%E5%B1%8B&c=apps&hl=ja |  |
| 22 | はま寿司 | ゼンショーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%AF%E3%81%BE%E5%AF%BF%E5%8F%B8 | https://play.google.com/store/search?q=%E3%81%AF%E3%81%BE%E5%AF%BF%E5%8F%B8&c=apps&hl=ja |  |
| 23 | びっくりドンキー | アレフ | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%B3%E3%81%A3%E3%81%8F%E3%82%8A%E3%83%89%E3%83%B3%E3%82%AD%E3%83%BC | https://play.google.com/store/search?q=%E3%81%B3%E3%81%A3%E3%81%8F%E3%82%8A%E3%83%89%E3%83%B3%E3%82%AD%E3%83%BC&c=apps&hl=ja |  |
| 24 | ほっともっと | プレナス | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%BB%E3%81%A3%E3%81%A8%E3%82%82%E3%81%A3%E3%81%A8 | https://play.google.com/store/search?q=%E3%81%BB%E3%81%A3%E3%81%A8%E3%82%82%E3%81%A3%E3%81%A8&c=apps&hl=ja |  |
| 25 | まいどおおきに食堂 | フジオフードグループ本社 | 要確認 | https://apps.apple.com/jp/search?term=%E3%81%BE%E3%81%84%E3%81%A9%E3%81%8A%E3%81%8A%E3%81%8D%E3%81%AB%E9%A3%9F%E5%A0%82 | https://play.google.com/store/search?q=%E3%81%BE%E3%81%84%E3%81%A9%E3%81%8A%E3%81%8A%E3%81%8D%E3%81%AB%E9%A3%9F%E5%A0%82&c=apps&hl=ja |  |
| 26 | むさしの森珈琲 | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%80%E3%81%95%E3%81%97%E3%81%AE%E6%A3%AE%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E3%82%80%E3%81%95%E3%81%97%E3%81%AE%E6%A3%AE%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 27 | やよい軒 | プレナス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%84%E3%82%88%E3%81%84%E8%BB%92 | https://play.google.com/store/search?q=%E3%82%84%E3%82%88%E3%81%84%E8%BB%92&c=apps&hl=ja |  |
| 28 | やるき茶屋 | 大庄 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%84%E3%82%8B%E3%81%8D%E8%8C%B6%E5%B1%8B | https://play.google.com/store/search?q=%E3%82%84%E3%82%8B%E3%81%8D%E8%8C%B6%E5%B1%8B&c=apps&hl=ja |  |
| 29 | や台ずし | カッパ・クリエイト関連 | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%84%E5%8F%B0%E3%81%9A%E3%81%97 | https://play.google.com/store/search?q=%E3%82%84%E5%8F%B0%E3%81%9A%E3%81%97&c=apps&hl=ja |  |
| 30 | ゆず庵 | 物語コーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%86%E3%81%9A%E5%BA%B5 | https://play.google.com/store/search?q=%E3%82%86%E3%81%9A%E5%BA%B5&c=apps&hl=ja |  |
| 31 | わらやき屋 | DDグループ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%8F%E3%82%89%E3%82%84%E3%81%8D%E5%B1%8B | https://play.google.com/store/search?q=%E3%82%8F%E3%82%89%E3%82%84%E3%81%8D%E5%B1%8B&c=apps&hl=ja |  |
| 32 | アウトバックステーキハウス | アウトバック | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A2%E3%82%A6%E3%83%88%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E3%83%8F%E3%82%A6%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%A2%E3%82%A6%E3%83%88%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E3%83%8F%E3%82%A6%E3%82%B9&c=apps&hl=ja |  |
| 33 | ウェンディーズ | WFK | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A6%E3%82%A7%E3%83%B3%E3%83%87%E3%82%A3%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=%E3%82%A6%E3%82%A7%E3%83%B3%E3%83%87%E3%82%A3%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 34 | ウェンディーズ・ファーストキッチン | WFK | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%A6%E3%82%A7%E3%83%B3%E3%83%87%E3%82%A3%E3%83%BC%E3%82%BA%E3%83%BB%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%A6%E3%82%A7%E3%83%B3%E3%83%87%E3%82%A3%E3%83%BC%E3%82%BA%E3%83%BB%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3&c=apps&hl=ja |  |
| 35 | オリーブの丘 | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AA%E3%83%AA%E3%83%BC%E3%83%96%E3%81%AE%E4%B8%98 | https://play.google.com/store/search?q=%E3%82%AA%E3%83%AA%E3%83%BC%E3%83%96%E3%81%AE%E4%B8%98&c=apps&hl=ja |  |
| 36 | ガスト | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AC%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E3%82%AC%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 37 | クア・アイナ | レインズインターナショナル | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%AF%E3%82%A2%E3%83%BB%E3%82%A2%E3%82%A4%E3%83%8A | https://play.google.com/store/search?q=%E3%82%AF%E3%82%A2%E3%83%BB%E3%82%A2%E3%82%A4%E3%83%8A&c=apps&hl=ja |  |
| 38 | グラッチェガーデンズ | すかいらーく | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B0%E3%83%A9%E3%83%83%E3%83%81%E3%82%A7%E3%82%AC%E3%83%BC%E3%83%87%E3%83%B3%E3%82%BA | https://play.google.com/store/search?q=%E3%82%B0%E3%83%A9%E3%83%83%E3%83%81%E3%82%A7%E3%82%AC%E3%83%BC%E3%83%87%E3%83%B3%E3%82%BA&c=apps&hl=ja |  |
| 39 | ケンタッキーフライドチキン | 日本KFCホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B1%E3%83%B3%E3%82%BF%E3%83%83%E3%82%AD%E3%83%BC%E3%83%95%E3%83%A9%E3%82%A4%E3%83%89%E3%83%81%E3%82%AD%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B1%E3%83%B3%E3%82%BF%E3%83%83%E3%82%AD%E3%83%BC%E3%83%95%E3%83%A9%E3%82%A4%E3%83%89%E3%83%81%E3%82%AD%E3%83%B3&c=apps&hl=ja |  |
| 40 | ココス | ゼンショーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%82%B3%E3%82%B9 | https://play.google.com/store/search?q=%E3%82%B3%E3%82%B3%E3%82%B9&c=apps&hl=ja |  |
| 41 | コナズ珈琲 | トリドールホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B3%E3%83%8A%E3%82%BA%E7%8F%88%E7%90%B2 | https://play.google.com/store/search?q=%E3%82%B3%E3%83%8A%E3%82%BA%E7%8F%88%E7%90%B2&c=apps&hl=ja |  |
| 42 | サイゼリヤ | サイゼリヤ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%82%A4%E3%82%BC%E3%83%AA%E3%83%A4 | https://play.google.com/store/search?q=%E3%82%B5%E3%82%A4%E3%82%BC%E3%83%AA%E3%83%A4&c=apps&hl=ja |  |
| 43 | サブウェイ | 日本サブウェイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B5%E3%83%96%E3%82%A6%E3%82%A7%E3%82%A4 | https://play.google.com/store/search?q=%E3%82%B5%E3%83%96%E3%82%A6%E3%82%A7%E3%82%A4&c=apps&hl=ja |  |
| 44 | ジョナサン | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A7%E3%83%8A%E3%82%B5%E3%83%B3 | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A7%E3%83%8A%E3%82%B5%E3%83%B3&c=apps&hl=ja |  |
| 45 | ジョリーパスタ | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B8%E3%83%A7%E3%83%AA%E3%83%BC%E3%83%91%E3%82%B9%E3%82%BF | https://play.google.com/store/search?q=%E3%82%B8%E3%83%A7%E3%83%AA%E3%83%BC%E3%83%91%E3%82%B9%E3%82%BF&c=apps&hl=ja |  |
| 46 | スガキヤ | スガキコシステムズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%AC%E3%82%AD%E3%83%A4 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%AC%E3%82%AD%E3%83%A4&c=apps&hl=ja |  |
| 47 | スシロー | FOOD & LIFE COMPANIES | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%82%B7%E3%83%AD%E3%83%BC | https://play.google.com/store/search?q=%E3%82%B9%E3%82%B7%E3%83%AD%E3%83%BC&c=apps&hl=ja |  |
| 48 | スターバックス | スターバックス コーヒー ジャパン | 確認済み | https://apps.apple.com/jp/app/%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9-%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3%E5%85%AC%E5%BC%8F%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%97%E3%83%AA/id1113037275 | https://play.google.com/store/search?q=%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9&c=apps&hl=ja | 会員証/QR、Rewards、Mobile Order & Pay、Gift、店舗検索、Inbox |
| 49 | ステーキガスト | すかいらーく | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E3%82%AC%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E3%82%AC%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 50 | ステーキ宮 | アトム | 要確認 | https://apps.apple.com/jp/search?term=%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E5%AE%AE | https://play.google.com/store/search?q=%E3%82%B9%E3%83%86%E3%83%BC%E3%82%AD%E5%AE%AE&c=apps&hl=ja |  |
| 51 | チムニー | チムニー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%81%E3%83%A0%E3%83%8B%E3%83%BC | https://play.google.com/store/search?q=%E3%83%81%E3%83%A0%E3%83%8B%E3%83%BC&c=apps&hl=ja |  |
| 52 | デニーズ | セブン&アイ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%87%E3%83%8B%E3%83%BC%E3%82%BA | https://play.google.com/store/search?q=%E3%83%87%E3%83%8B%E3%83%BC%E3%82%BA&c=apps&hl=ja |  |
| 53 | トラジ | トラジ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%88%E3%83%A9%E3%82%B8 | https://play.google.com/store/search?q=%E3%83%88%E3%83%A9%E3%82%B8&c=apps&hl=ja |  |
| 54 | ドミノ・ピザ | ドミノ・ピザ ジャパン | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%89%E3%83%9F%E3%83%8E%E3%83%BB%E3%83%94%E3%82%B6 | https://play.google.com/store/search?q=%E3%83%89%E3%83%9F%E3%83%8E%E3%83%BB%E3%83%94%E3%82%B6&c=apps&hl=ja |  |
| 55 | ハードロックカフェ | ハードロックカフェ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%8F%E3%83%BC%E3%83%89%E3%83%AD%E3%83%83%E3%82%AF%E3%82%AB%E3%83%95%E3%82%A7 | https://play.google.com/store/search?q=%E3%83%8F%E3%83%BC%E3%83%89%E3%83%AD%E3%83%83%E3%82%AF%E3%82%AB%E3%83%95%E3%82%A7&c=apps&hl=ja |  |
| 56 | バーガーキング | ビーケージャパンホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC%E3%82%AD%E3%83%B3%E3%82%B0 | https://play.google.com/store/search?q=%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC%E3%82%AD%E3%83%B3%E3%82%B0&c=apps&hl=ja |  |
| 57 | バーミヤン | すかいらーくホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%90%E3%83%BC%E3%83%9F%E3%83%A4%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%90%E3%83%BC%E3%83%9F%E3%83%A4%E3%83%B3&c=apps&hl=ja |  |
| 58 | ビッグボーイ | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%93%E3%83%83%E3%82%B0%E3%83%9C%E3%83%BC%E3%82%A4 | https://play.google.com/store/search?q=%E3%83%93%E3%83%83%E3%82%B0%E3%83%9C%E3%83%BC%E3%82%A4&c=apps&hl=ja |  |
| 59 | ピザハット | 日本ピザハット | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%94%E3%82%B6%E3%83%8F%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%94%E3%82%B6%E3%83%8F%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 60 | ピザーラ | フォーシーズ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%94%E3%82%B6%E3%83%BC%E3%83%A9 | https://play.google.com/store/search?q=%E3%83%94%E3%82%B6%E3%83%BC%E3%83%A9&c=apps&hl=ja |  |
| 61 | ファーストキッチン | WFK | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%95%E3%82%A1%E3%83%BC%E3%82%B9%E3%83%88%E3%82%AD%E3%83%83%E3%83%81%E3%83%B3&c=apps&hl=ja |  |
| 62 | フレッシュネスバーガー | フレッシュネス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%95%E3%83%AC%E3%83%83%E3%82%B7%E3%83%A5%E3%83%8D%E3%82%B9%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC | https://play.google.com/store/search?q=%E3%83%95%E3%83%AC%E3%83%83%E3%82%B7%E3%83%A5%E3%83%8D%E3%82%B9%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC&c=apps&hl=ja |  |
| 63 | プロント | プロントコーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88 | https://play.google.com/store/search?q=%E3%83%97%E3%83%AD%E3%83%B3%E3%83%88&c=apps&hl=ja |  |
| 64 | ペッパーランチ | ペッパーフードサービス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9A%E3%83%83%E3%83%91%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%81 | https://play.google.com/store/search?q=%E3%83%9A%E3%83%83%E3%83%91%E3%83%BC%E3%83%A9%E3%83%B3%E3%83%81&c=apps&hl=ja |  |
| 65 | ホテルオークラレストラン | ホテルオークラ | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%9B%E3%83%86%E3%83%AB%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%A9%E3%83%AC%E3%82%B9%E3%83%88%E3%83%A9%E3%83%B3 | https://play.google.com/store/search?q=%E3%83%9B%E3%83%86%E3%83%AB%E3%82%AA%E3%83%BC%E3%82%AF%E3%83%A9%E3%83%AC%E3%82%B9%E3%83%88%E3%83%A9%E3%83%B3&c=apps&hl=ja |  |
| 66 | マクドナルド | 日本マクドナルド | 確認済み | https://apps.apple.com/jp/app/mcdonalds-japan/id413618155 | https://play.google.com/store/search?q=%E3%83%9E%E3%82%AF%E3%83%89%E3%83%8A%E3%83%AB%E3%83%89&c=apps&hl=ja | モバイルオーダー、クーポン、店舗 |
| 67 | モスバーガー | モスフードサービス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A2%E3%82%B9%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC | https://play.google.com/store/search?q=%E3%83%A2%E3%82%B9%E3%83%90%E3%83%BC%E3%82%AC%E3%83%BC&c=apps&hl=ja |  |
| 68 | ラーメン横綱 | ギフトホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3%E6%A8%AA%E7%B6%B1 | https://play.google.com/store/search?q=%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3%E6%A8%AA%E7%B6%B1&c=apps&hl=ja |  |
| 69 | ラーメン豚山 | ギフトホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3%E8%B1%9A%E5%B1%B1 | https://play.google.com/store/search?q=%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3%E8%B1%9A%E5%B1%B1&c=apps&hl=ja |  |
| 70 | リンガーハット | リンガーハット | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AA%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88 | https://play.google.com/store/search?q=%E3%83%AA%E3%83%B3%E3%82%AC%E3%83%BC%E3%83%8F%E3%83%83%E3%83%88&c=apps&hl=ja |  |
| 71 | ロイヤルホスト | ロイヤルホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%82%A4%E3%83%A4%E3%83%AB%E3%83%9B%E3%82%B9%E3%83%88 | https://play.google.com/store/search?q=%E3%83%AD%E3%82%A4%E3%83%A4%E3%83%AB%E3%83%9B%E3%82%B9%E3%83%88&c=apps&hl=ja |  |
| 72 | ロッテリア | ゼンショーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E3%83%AD%E3%83%83%E3%83%86%E3%83%AA%E3%82%A2 | https://play.google.com/store/search?q=%E3%83%AD%E3%83%83%E3%83%86%E3%83%AA%E3%82%A2&c=apps&hl=ja |  |
| 73 | 一蘭 | 一蘭 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%80%E8%98%AD | https://play.google.com/store/search?q=%E4%B8%80%E8%98%AD&c=apps&hl=ja |  |
| 74 | 一軒め酒場 | 養老乃瀧 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%80%E8%BB%92%E3%82%81%E9%85%92%E5%A0%B4 | https://play.google.com/store/search?q=%E4%B8%80%E8%BB%92%E3%82%81%E9%85%92%E5%A0%B4&c=apps&hl=ja |  |
| 75 | 一風堂 | 力の源ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%80%E9%A2%A8%E5%A0%82 | https://play.google.com/store/search?q=%E4%B8%80%E9%A2%A8%E5%A0%82&c=apps&hl=ja |  |
| 76 | 不二家レストラン | 不二家 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%8D%E4%BA%8C%E5%AE%B6%E3%83%AC%E3%82%B9%E3%83%88%E3%83%A9%E3%83%B3 | https://play.google.com/store/search?q=%E4%B8%8D%E4%BA%8C%E5%AE%B6%E3%83%AC%E3%82%B9%E3%83%88%E3%83%A9%E3%83%B3&c=apps&hl=ja |  |
| 77 | 串家物語 | フジオフードグループ本社 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B2%E5%AE%B6%E7%89%A9%E8%AA%9E | https://play.google.com/store/search?q=%E4%B8%B2%E5%AE%B6%E7%89%A9%E8%AA%9E&c=apps&hl=ja |  |
| 78 | 丸亀シェイク | トリドール | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B8%E4%BA%80%E3%82%B7%E3%82%A7%E3%82%A4%E3%82%AF | https://play.google.com/store/search?q=%E4%B8%B8%E4%BA%80%E3%82%B7%E3%82%A7%E3%82%A4%E3%82%AF&c=apps&hl=ja |  |
| 79 | 丸亀製麺 | トリドールホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B8%E4%BA%80%E8%A3%BD%E9%BA%BA | https://play.google.com/store/search?q=%E4%B8%B8%E4%BA%80%E8%A3%BD%E9%BA%BA&c=apps&hl=ja |  |
| 80 | 丸源 | 物語 | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B8%E6%BA%90 | https://play.google.com/store/search?q=%E4%B8%B8%E6%BA%90&c=apps&hl=ja |  |
| 81 | 丸源ラーメン | 物語コーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E4%B8%B8%E6%BA%90%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3 | https://play.google.com/store/search?q=%E4%B8%B8%E6%BA%90%E3%83%A9%E3%83%BC%E3%83%A1%E3%83%B3&c=apps&hl=ja |  |
| 82 | 九州熱中屋 | DDグループ | 要確認 | https://apps.apple.com/jp/search?term=%E4%B9%9D%E5%B7%9E%E7%86%B1%E4%B8%AD%E5%B1%8B | https://play.google.com/store/search?q=%E4%B9%9D%E5%B7%9E%E7%86%B1%E4%B8%AD%E5%B1%8B&c=apps&hl=ja |  |
| 83 | 五味八珍 | 五味八珍 | 要確認 | https://apps.apple.com/jp/search?term=%E4%BA%94%E5%91%B3%E5%85%AB%E7%8F%8D | https://play.google.com/store/search?q=%E4%BA%94%E5%91%B3%E5%85%AB%E7%8F%8D&c=apps&hl=ja |  |
| 84 | 元気寿司 | FOOD & LIFE COMPANIES | 要確認 | https://apps.apple.com/jp/search?term=%E5%85%83%E6%B0%97%E5%AF%BF%E5%8F%B8 | https://play.google.com/store/search?q=%E5%85%83%E6%B0%97%E5%AF%BF%E5%8F%B8&c=apps&hl=ja |  |
| 85 | 千年の宴 | ジー・テイスト | 要確認 | https://apps.apple.com/jp/search?term=%E5%8D%83%E5%B9%B4%E3%81%AE%E5%AE%B4 | https://play.google.com/store/search?q=%E5%8D%83%E5%B9%B4%E3%81%AE%E5%AE%B4&c=apps&hl=ja |  |
| 86 | 吉野家 | 吉野家ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%90%89%E9%87%8E%E5%AE%B6 | https://play.google.com/store/search?q=%E5%90%89%E9%87%8E%E5%AE%B6&c=apps&hl=ja |  |
| 87 | 和食さと | SRSホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%92%8C%E9%A3%9F%E3%81%95%E3%81%A8 | https://play.google.com/store/search?q=%E5%92%8C%E9%A3%9F%E3%81%95%E3%81%A8&c=apps&hl=ja |  |
| 88 | 塚田農場 | エー・ピーホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%A1%9A%E7%94%B0%E8%BE%B2%E5%A0%B4 | https://play.google.com/store/search?q=%E5%A1%9A%E7%94%B0%E8%BE%B2%E5%A0%B4&c=apps&hl=ja |  |
| 89 | 壱角家 | ギフトホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%A3%B1%E8%A7%92%E5%AE%B6 | https://play.google.com/store/search?q=%E5%A3%B1%E8%A7%92%E5%AE%B6&c=apps&hl=ja |  |
| 90 | 夢庵 | すかいらーく | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A2%E5%BA%B5 | https://play.google.com/store/search?q=%E5%A4%A2%E5%BA%B5&c=apps&hl=ja |  |
| 91 | 大戸屋 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A7%E6%88%B8%E5%B1%8B | https://play.google.com/store/search?q=%E5%A4%A7%E6%88%B8%E5%B1%8B&c=apps&hl=ja |  |
| 92 | 大衆酒場てっぺん | ファイブグループ | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A7%E8%A1%86%E9%85%92%E5%A0%B4%E3%81%A6%E3%81%A3%E3%81%BA%E3%82%93 | https://play.google.com/store/search?q=%E5%A4%A7%E8%A1%86%E9%85%92%E5%A0%B4%E3%81%A6%E3%81%A3%E3%81%BA%E3%82%93&c=apps&hl=ja |  |
| 93 | 天下一品 | 天一食品商事 | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A9%E4%B8%8B%E4%B8%80%E5%93%81 | https://play.google.com/store/search?q=%E5%A4%A9%E4%B8%8B%E4%B8%80%E5%93%81&c=apps&hl=ja |  |
| 94 | 天狗 | テンアライド | 要確認 | https://apps.apple.com/jp/search?term=%E5%A4%A9%E7%8B%97 | https://play.google.com/store/search?q=%E5%A4%A9%E7%8B%97&c=apps&hl=ja |  |
| 95 | 宮本むなし | トリドール | 要確認 | https://apps.apple.com/jp/search?term=%E5%AE%AE%E6%9C%AC%E3%82%80%E3%81%AA%E3%81%97 | https://play.google.com/store/search?q=%E5%AE%AE%E6%9C%AC%E3%82%80%E3%81%AA%E3%81%97&c=apps&hl=ja |  |
| 96 | 寿司・海鮮 にぎりの徳兵衛 | アトム | 要確認 | https://apps.apple.com/jp/search?term=%E5%AF%BF%E5%8F%B8%E3%83%BB%E6%B5%B7%E9%AE%AE%20%E3%81%AB%E3%81%8E%E3%82%8A%E3%81%AE%E5%BE%B3%E5%85%B5%E8%A1%9B | https://play.google.com/store/search?q=%E5%AF%BF%E5%8F%B8%E3%83%BB%E6%B5%B7%E9%AE%AE%20%E3%81%AB%E3%81%8E%E3%82%8A%E3%81%AE%E5%BE%B3%E5%85%B5%E8%A1%9B&c=apps&hl=ja |  |
| 97 | 居酒屋 土間土間 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E5%B1%85%E9%85%92%E5%B1%8B%20%E5%9C%9F%E9%96%93%E5%9C%9F%E9%96%93 | https://play.google.com/store/search?q=%E5%B1%85%E9%85%92%E5%B1%8B%20%E5%9C%9F%E9%96%93%E5%9C%9F%E9%96%93&c=apps&hl=ja |  |
| 98 | 屋台屋 博多劇場 | 一家ホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E5%B1%8B%E5%8F%B0%E5%B1%8B%20%E5%8D%9A%E5%A4%9A%E5%8A%87%E5%A0%B4 | https://play.google.com/store/search?q=%E5%B1%8B%E5%8F%B0%E5%B1%8B%20%E5%8D%9A%E5%A4%9A%E5%8A%87%E5%A0%B4&c=apps&hl=ja |  |
| 99 | 山岡家 | 丸千代山岡家 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B1%B1%E5%B2%A1%E5%AE%B6 | https://play.google.com/store/search?q=%E5%B1%B1%E5%B2%A1%E5%AE%B6&c=apps&hl=ja |  |
| 100 | 幸楽苑 | 幸楽苑 | 要確認 | https://apps.apple.com/jp/search?term=%E5%B9%B8%E6%A5%BD%E8%8B%91 | https://play.google.com/store/search?q=%E5%B9%B8%E6%A5%BD%E8%8B%91&c=apps&hl=ja |  |
| 101 | 庄や | 大庄 | 要確認 | https://apps.apple.com/jp/search?term=%E5%BA%84%E3%82%84 | https://play.google.com/store/search?q=%E5%BA%84%E3%82%84&c=apps&hl=ja |  |
| 102 | 日本海庄や | 大庄 | 要確認 | https://apps.apple.com/jp/search?term=%E6%97%A5%E6%9C%AC%E6%B5%B7%E5%BA%84%E3%82%84 | https://play.google.com/store/search?q=%E6%97%A5%E6%9C%AC%E6%B5%B7%E5%BA%84%E3%82%84&c=apps&hl=ja |  |
| 103 | 日高屋 | ハイデイ日高 | 要確認 | https://apps.apple.com/jp/search?term=%E6%97%A5%E9%AB%98%E5%B1%8B | https://play.google.com/store/search?q=%E6%97%A5%E9%AB%98%E5%B1%8B&c=apps&hl=ja |  |
| 104 | 杉玉 | FOOD & LIFE COMPANIES | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%89%E7%8E%89 | https://play.google.com/store/search?q=%E6%9D%89%E7%8E%89&c=apps&hl=ja |  |
| 105 | 来来亭 | 来来亭 | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%A5%E6%9D%A5%E4%BA%AD | https://play.google.com/store/search?q=%E6%9D%A5%E6%9D%A5%E4%BA%AD&c=apps&hl=ja |  |
| 106 | 松のや | 松屋フーズ | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%BE%E3%81%AE%E3%82%84 | https://play.google.com/store/search?q=%E6%9D%BE%E3%81%AE%E3%82%84&c=apps&hl=ja |  |
| 107 | 松屋 | 松屋フーズ | 要確認 | https://apps.apple.com/jp/search?term=%E6%9D%BE%E5%B1%8B | https://play.google.com/store/search?q=%E6%9D%BE%E5%B1%8B&c=apps&hl=ja |  |
| 108 | 温野菜 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E6%B8%A9%E9%87%8E%E8%8F%9C | https://play.google.com/store/search?q=%E6%B8%A9%E9%87%8E%E8%8F%9C&c=apps&hl=ja |  |
| 109 | 焼肉きんぐ | 物語コーポレーション | 要確認 | https://apps.apple.com/jp/search?term=%E7%84%BC%E8%82%89%E3%81%8D%E3%82%93%E3%81%90 | https://play.google.com/store/search?q=%E7%84%BC%E8%82%89%E3%81%8D%E3%82%93%E3%81%90&c=apps&hl=ja |  |
| 110 | 焼肉食べ放題 カルビ大将 | アトム | 要確認 | https://apps.apple.com/jp/search?term=%E7%84%BC%E8%82%89%E9%A3%9F%E3%81%B9%E6%94%BE%E9%A1%8C%20%E3%82%AB%E3%83%AB%E3%83%93%E5%A4%A7%E5%B0%86 | https://play.google.com/store/search?q=%E7%84%BC%E8%82%89%E9%A3%9F%E3%81%B9%E6%94%BE%E9%A1%8C%20%E3%82%AB%E3%83%AB%E3%83%93%E5%A4%A7%E5%B0%86&c=apps&hl=ja |  |
| 111 | 牛角 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E7%89%9B%E8%A7%92 | https://play.google.com/store/search?q=%E7%89%9B%E8%A7%92&c=apps&hl=ja |  |
| 112 | 甘太郎 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E7%94%98%E5%A4%AA%E9%83%8E | https://play.google.com/store/search?q=%E7%94%98%E5%A4%AA%E9%83%8E&c=apps&hl=ja |  |
| 113 | 田所商店 | トライ・インターナショナル | 要確認 | https://apps.apple.com/jp/search?term=%E7%94%B0%E6%89%80%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E7%94%B0%E6%89%80%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 114 | 町田商店 | ギフトホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E7%94%BA%E7%94%B0%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E7%94%BA%E7%94%B0%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 115 | 白木屋 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E7%99%BD%E6%9C%A8%E5%B1%8B | https://play.google.com/store/search?q=%E7%99%BD%E6%9C%A8%E5%B1%8B&c=apps&hl=ja |  |
| 116 | 磯丸水産 | クリエイト・レストランツホールディングス | 要確認 | https://apps.apple.com/jp/search?term=%E7%A3%AF%E4%B8%B8%E6%B0%B4%E7%94%A3 | https://play.google.com/store/search?q=%E7%A3%AF%E4%B8%B8%E6%B0%B4%E7%94%A3&c=apps&hl=ja |  |
| 117 | 築地銀だこ | ホットランド | 要確認 | https://apps.apple.com/jp/search?term=%E7%AF%89%E5%9C%B0%E9%8A%80%E3%81%A0%E3%81%93 | https://play.google.com/store/search?q=%E7%AF%89%E5%9C%B0%E9%8A%80%E3%81%A0%E3%81%93&c=apps&hl=ja |  |
| 118 | 肉汁餃子のダンダダン | NATTY SWANKY | 要確認 | https://apps.apple.com/jp/search?term=%E8%82%89%E6%B1%81%E9%A4%83%E5%AD%90%E3%81%AE%E3%83%80%E3%83%B3%E3%83%80%E3%83%80%E3%83%B3 | https://play.google.com/store/search?q=%E8%82%89%E6%B1%81%E9%A4%83%E5%AD%90%E3%81%AE%E3%83%80%E3%83%B3%E3%83%80%E3%83%80%E3%83%B3&c=apps&hl=ja |  |
| 119 | 華屋与兵衛 | ゼンショー | 要確認 | https://apps.apple.com/jp/search?term=%E8%8F%AF%E5%B1%8B%E4%B8%8E%E5%85%B5%E8%A1%9B | https://play.google.com/store/search?q=%E8%8F%AF%E5%B1%8B%E4%B8%8E%E5%85%B5%E8%A1%9B&c=apps&hl=ja |  |
| 120 | 藍屋 | すかいらーく | 要確認 | https://apps.apple.com/jp/search?term=%E8%97%8D%E5%B1%8B | https://play.google.com/store/search?q=%E8%97%8D%E5%B1%8B&c=apps&hl=ja |  |
| 121 | 資さんうどん | 資さん | 要確認 | https://apps.apple.com/jp/search?term=%E8%B3%87%E3%81%95%E3%82%93%E3%81%86%E3%81%A9%E3%82%93 | https://play.google.com/store/search?q=%E8%B3%87%E3%81%95%E3%82%93%E3%81%86%E3%81%A9%E3%82%93&c=apps&hl=ja |  |
| 122 | 餃子の王将 | 王将フードサービス | 要確認 | https://apps.apple.com/jp/search?term=%E9%A4%83%E5%AD%90%E3%81%AE%E7%8E%8B%E5%B0%86 | https://play.google.com/store/search?q=%E9%A4%83%E5%AD%90%E3%81%AE%E7%8E%8B%E5%B0%86&c=apps&hl=ja |  |
| 123 | 養老乃瀧 | 養老乃瀧 | 要確認 | https://apps.apple.com/jp/search?term=%E9%A4%8A%E8%80%81%E4%B9%83%E7%80%A7 | https://play.google.com/store/search?q=%E9%A4%8A%E8%80%81%E4%B9%83%E7%80%A7&c=apps&hl=ja |  |
| 124 | 魁力屋 | 魁力屋 | 要確認 | https://apps.apple.com/jp/search?term=%E9%AD%81%E5%8A%9B%E5%B1%8B | https://play.google.com/store/search?q=%E9%AD%81%E5%8A%9B%E5%B1%8B&c=apps&hl=ja |  |
| 125 | 魚べい | FOOD & LIFE COMPANIES | 要確認 | https://apps.apple.com/jp/search?term=%E9%AD%9A%E3%81%B9%E3%81%84 | https://play.google.com/store/search?q=%E9%AD%9A%E3%81%B9%E3%81%84&c=apps&hl=ja |  |
| 126 | 魚民 | コロワイド | 要確認 | https://apps.apple.com/jp/search?term=%E9%AD%9A%E6%B0%91 | https://play.google.com/store/search?q=%E9%AD%9A%E6%B0%91&c=apps&hl=ja |  |
| 127 | 鳥貴族 | エターナルホスピタリティグループ | 要確認 | https://apps.apple.com/jp/search?term=%E9%B3%A5%E8%B2%B4%E6%97%8F | https://play.google.com/store/search?q=%E9%B3%A5%E8%B2%B4%E6%97%8F&c=apps&hl=ja |  |
| 128 | 麺場田所商店 | トライ・インターナショナル | 要確認 | https://apps.apple.com/jp/search?term=%E9%BA%BA%E5%A0%B4%E7%94%B0%E6%89%80%E5%95%86%E5%BA%97 | https://play.google.com/store/search?q=%E9%BA%BA%E5%A0%B4%E7%94%B0%E6%89%80%E5%95%86%E5%BA%97&c=apps&hl=ja |  |
| 129 | 麺屋はなび | ギフト | 要確認 | https://apps.apple.com/jp/search?term=%E9%BA%BA%E5%B1%8B%E3%81%AF%E3%81%AA%E3%81%B3 | https://play.google.com/store/search?q=%E9%BA%BA%E5%B1%8B%E3%81%AF%E3%81%AA%E3%81%B3&c=apps&hl=ja |  |

---

## 6. 優先して画面を見るアプリ（デザイン参考トップ）

| 優先 | アプリ | 業界 | 参考になる理由 |
|------|--------|------|----------------|
| 1 | Starbucks Japan | カフェ | 会員証・Rewards・モバイルオーダーの完成形 |
| 2 | シャトレーゼ | 菓子 | 物販×ポイント×予約受取。スマレジSHOPに近い |
| 3 | 銀座コージーコーナー | 菓子 | Yappli系の店舗CRM UIの実例 |
| 4 | GODIVA | 菓子/ギフト | 会員ランク・スタンプ・ブランド体験 |
| 5 | UNIQLO | アパレル | 在庫・EC・店舗の横断UI |
| 6 | ヨドバシカメラ | 家電 | 検索・バーコード・在庫・価格・ポイント |
| 7 | コナミスポーツ | ジム | 会員証・施設検索・タイムテーブル・予約 |
| 8 | 東横INN | ホテル | 会員証・予約・QRチェックイン・ポイント |
| 9 | 丸善ジュンク堂 | 書店 | 店舗在庫・取り置き・ポイント |
| 10 | @cosme TOKYO | コスメ | 口コミ/ランキングと店舗購買の接続 |
| 11 | Chipotle | グローバル飲食 | Order/Rewardsの海外定石 |
| 12 | Dunkin' | グローバル飲食 | Order ahead + Rewards |
| 13 | Nike | グローバル小売 | 会員・店舗スキャン・ブランド体験 |
| 14 | IKEA | グローバル小売 | 店舗マップ・在庫・スキャン |
| 15 | Lindt | 菓子 | 会員クラブUI |
| 16 | セブン-イレブン | コンビニ | 日本最大級の店舗アプリ導線 |
| 17 | ファミリーマート | コンビニ | 会員・クーポン・決済UI |
| 18 | ローソン | コンビニ | Ponta/会員・クーポン |

### 機能マトリクス（目安）

| ブランド | 会員証 | ポイント | スタンプ | クーポン | 店舗検索 | モバイル注文 | 予約 | EC | Push |
|----------|--------|----------|----------|----------|----------|--------------|------|----|------|
| Starbucks | ○ | ○ | △ | ○ | ○ | ○ | × | ○ | ○ |
| シャトレーゼ | ○ | ○ | △ | ○ | ○ | × | ○ | △ | ○ |
| コージーコーナー | ○ | ○(マイル) | △ | ○ | ○ | × | ○ | ○ | ○ |
| GODIVA | ○ | ○ | ○ | ○ | ○ | △ | △ | ○ | ○ |
| UNIQLO | ○ | ○ | × | ○ | ○ | × | × | ○ | ○ |
| ヨドバシ | ○ | ○ | × | △ | ○ | × | × | ○ | ○ |
| コナミ | ○ | △ | × | ○ | ○ | × | ○ | × | ○ |
| 東横INN | ○ | ○ | × | ○ | ○ | × | ○ | × | ○ |
| Chipotle | ○ | ○ | × | ○ | ○ | ○ | × | × | ○ |
| Dunkin' | ○ | ○ | × | ○ | ○ | ○ | × | × | ○ |

---

## 7. 競合プラットフォーム調査（省略なし）

### 7.1 店舗アプリ制作・CRM・モバイルオーダー

| プラットフォーム | 概要 | 主要機能 | スマレジSHOPとの比較観点 | 公式URL | 導入事例/UI確認URL |
|------------------|------|----------|--------------------------|---------|-------------------|
| dinii（ダイニー） | 飲食DX一体型 | POS / モバイルオーダー / キャッシュレス / CRM | 飲食特化の注文〜CRM一体化 | https://www.dinii.jp/ | https://dinii.jp/casestudy/ |
| Yappli CRM | ノーコード店舗アプリ＋CRM | 会員 / ポイント / ランク / 電子マネー / クーポン / スタンプ / Push / セグメント | 最も直接的な比較対象 | https://yapp.li/crm/ | https://yapp.li/voice.html |
| GMOおみせアプリ | 店舗公式アプリ作成 | 会員証 / ポイント / スタンプ / クーポン / Push | 業種横断で近い | https://gmo-app.jp/ | https://gmo-app.jp/works_list.html |
| 店舗アプリ（TenpoApp） | 店舗・施設向けPF | Push / スタンプ / クーポン / 予約 / QR | 飲食以外（レジャー・ホテル）も強い | https://tenpoapp.com/ | https://tenpoapp.com/case/ |
| みせプリ | 中小向け店舗アプリ | 会員証 / クーポン / スタンプ / Push | 低価格・個店向けの比較 | https://misepuri.com/lp5/ | https://misepuri.com/casestudy/detail.php?cate=1 |
| App Publisher（EmberPoint） | アプリ開発・運用・CRM | 公式アプリ / EC連携 / CRM | D2C・メーカー寄り | https://emberpoint.com/lp/apppublisher02.html | https://emberpoint.com/news/release/release20260107.html |
| アプリンク（UPLINK / USEN） | 店舗向け公式アプリ | 会員証 / スタンプ / クーポン / Push | 店舗集客・販促系 | https://www.uplink-app.com/ | https://www.uplink-app.com/ |
| toypo（トイポ） | リピーターCRMミニアプリ | スタンプ / クーポン / 会員証 / サブスク / 分析 | リピート特化の軽量競合 | https://toypo.me/ | https://toypo.me/release/yossix |
| STORES ブランドアプリ | STORES連携ブランドアプリ | 会員 / ポイント / クーポン / POS・EC連携 | エコシステム型競合 | https://stores.jp/brandedapp | https://stores.jp/brandedapp |

### 7.2 順番待ち・呼び出し

| プラットフォーム | 概要 | 公式URL | FAQ/UI確認 |
|------------------|------|---------|------------|
| Airウェイト | 受付・順番待ち・LINE呼び出し | https://airregi.jp/wait/ | https://faq.airwait.jp/hc/ja/articles/900007104323 |
| JUNBANMACHI（順番待ち） | 順番待ち・受付管理 | https://junbanmachi.jp/ | https://junbanmachi.jp/ |

### 7.3 飲食予約・台帳

| プラットフォーム | 概要 | 公式URL | 導入事例 |
|------------------|------|---------|----------|
| TableCheck | 予約・顧客管理・インバウンド | https://www.tablecheck.com/ja/join/ | https://www.tablecheck.com/ja/join/success-stories/ |
| トレタ | 予約台帳〜LINE・モバイルオーダー | https://toreta.in/toreta-daicho/ | https://toreta.in/stores_range/1-10stores/ |
| ebica | 予約・集客・AI電話 | https://www.ebica.jp/ | https://www.ebica.jp/casestudy/ |

### 7.4 LINEミニアプリ

| プラットフォーム | 概要 | 公式URL |
|------------------|------|---------|
| Fannaly | LINEミニアプリCRM | https://fannaly.jp/ |
| Enlyt | LINEミニアプリ開発 | https://enlyt.co.jp/lp-line/ |
| アイリッジ | LINEミニアプリ（商業施設・ポイント） | https://lineminiapp.iridge.jp/ |
| EPG | LINE順番待ち・予約・友だち化 | https://www.epg.co.jp/service/line-mini-app/ |
| Zen-web | LINE/Web開発 | https://zen-web.co.jp/ |

### 7.5 その他

| サービス | 概要 | URL |
|----------|------|-----|
| Pokepay | デジタルウォレット・会員・ポイント | https://pokepay.jp/ja/entries/97 |
| Amazing Pocket | サイネージ/メニュー表示 | https://www.amazing-pocket.com/digital-signage/system/cafeteriamenu.html |

---

## 8. スマレジSHOP向けデザイン示唆

### 8.1 ホーム画面

| 優先度 | ホームに置くもの | 参考 |
|--------|------------------|------|
| 必須 | 会員証への最短導線 | Starbucks Pay、シャトレーゼ カシポ |
| 必須 | 今使えるクーポン/特典 | コージーコーナー、GODIVA |
| 推奨 | 近くの店舗 | ほぼ全業種 |
| 推奨 | 今週の主役コンテンツ（1つ） | 季節商品・キャンペーン |

### 8.2 業種別の主導線

| 業種 | 主導線 | サブ導線 |
|------|--------|----------|
| カフェ/ファストフード | モバイルオーダー → 受取 | 会員証、Rewards |
| 菓子/土産 | 会員証 → クーポン / 予約受取 | EC、ギフト |
| アパレル/雑貨 | 商品検索 → 在庫/店舗 | 会員、クーポン |
| ジム/サロン | 会員証 → 予約/スケジュール | クーポン |
| ホテル | 予約 → QRチェックイン | 会員・ポイント |
| 家電/書店 | 検索 → 在庫/取り置き | ポイント |
| コンビニ | クーポン → 決済/会員証 | ポイント |

### 8.3 UI確認チェックリスト

- [ ] 会員証は何タップで出せるか
- [ ] バーコード/QRのコントラスト・サイズは十分か
- [ ] クーポンは「使えるものだけ」が先に出るか
- [ ] 店舗検索は地図 / リスト / 営業時間のどれが主か
- [ ] 注文/予約の完了画面は不安を残さないか（番号・時刻・場所）
- [ ] Push許諾のタイミングは初回起動直後か、価値提示後か
- [ ] ECと店舗の切り替えは明示されているか
- [ ] 未ログインでも価値が見えるか（カタログ/店舗情報）

---

## 9. 次の調査ステップ

1. セクション6の優先アプリのApp Storeスクショでチェックリストを埋める
2. Yappli / GMO / toypo / dinii の公式画面を同じ表で比較する
3. 全店舗CSVをベースに「導入可能性 A/B/C」列を追加して営業リスト化する

---

*全店舗URL完全版（905件）。カテゴリ内訳はセクション4、個別URLはセクション5およびCSVを参照。*
