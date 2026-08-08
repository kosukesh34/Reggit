# スマレジSHOP アプリUIデザイン参考資料

スマレジSHOPのUI改善・競合分析向けに、業界候補・店舗アプリ・競合プラットフォーム・デザイン参考アプリを整理したドキュメント。

> **定義メモ**  
> 「TOP企業」は厳密な売上高ランキングではなく、規模・知名度・店舗数・POS/CRM需要を総合した主要企業リスト。  
> App Store / Google Play のURLは調査時点の導線。ストア側の配信停止・ID変更があり得るため、実装前に再確認すること。

---

## 1. 調査の目的

| 目的 | 使い方 |
|------|--------|
| スマレジSHOPのUI改善 | 有名店舗アプリの画面構成・導線を参考にする |
| 競合比較 | 店舗アプリ / LINE / 飲食DX の機能差を把握する |
| 営業・市場の母集団 | スマレジ顧客になり得る業界・ブランドを洗い出す |

特に見たいUI観点:

- 会員証 / バーコード・QR
- ポイント / スタンプ / ランク
- クーポン
- 店舗検索
- モバイルオーダー / 事前注文
- 予約
- EC / オンラインショップ
- Push通知
- LINE連携

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
        │
        └──────────────┬──────────────┘
                       │
                  顧客管理 / CRM
                       │
       ┌───────────────┼───────────────┐
       │               │               │
     会員証           ポイント          クーポン
     スタンプ         購買履歴          Push
     来店履歴         セグメント        予約
```

スマレジSHOPは「独自アプリ＋会員CRM」軸が主戦場。飲食特化DX・LINEミニアプリは**別軸の競合**として見る。

---

## 3. 業界分類とスマレジ適合度

大分類10カテゴリ（営業・市場分析用）:

| # | 大分類 | 主な業界 | 適合度 |
|---|--------|----------|--------|
| ① | アパレル・ファッション | 衣料、靴、バッグ、ジュエリー | ★★★★★ |
| ② | 雑貨・小売 | 雑貨、文具、家具、インテリア | ★★★★★ |
| ③ | 食品・飲料小売 | スーパー、専門店、菓子、パン | ★★★★★ |
| ④ | 美容・コスメ | 化粧品、ドラッグストア、健食 | ★★★★★ |
| ⑤ | 飲食 | レストラン、居酒屋、カフェ | ★★★★★ |
| ⑥ | 美容・サービス | 美容室、エステ、ジム、サロン | ★★★★☆ |
| ⑦ | ホビー・エンタメ | 書店、ゲーム、トレカ、楽器 | ★★★★☆ |
| ⑧ | 専門小売 | ペット、花、スポーツ、カー用品 | ★★★★☆ |
| ⑨ | 催事・イベント | ポップアップ、物産展、マルシェ | ★★★★☆ |
| ⑩ | BtoB・卸売 | 食品、アパレル、美容、雑貨卸 | ★★★☆☆ |

狙いやすい5条件:

1. **SKUが多い**（アパレル、雑貨、ホビー）
2. **複数店舗**（チェーン）
3. **会員管理が重要**（美容、フィットネス、コスメ）
4. **店舗＋EC**（アパレル、食品、コスメ）
5. **催事・ポップアップが多い**

> イオン / マクドナルド / ユニクロ級は規模は大きいが既存POS・基幹が巨大なため、**導入可能性**では中堅チェーン（店舗数十〜百程度）の方が現実的。

---

## 4. UI調査用ブランド母集団（業界横断）

前回整理した業界横断リスト（約200ブランド超）。カテゴリ別の代表例:

| 業界カテゴリ | 代表ブランド例 |
|--------------|----------------|
| 飲食・ファストフード | すき家、スシロー、マクドナルド、吉野家、丸亀製麺、鳥貴族、山岡家 |
| カフェ・珈琲 | スターバックス、コメダ、ドトール、タリーズ、ブルーボトル |
| ジム・フィットネス | コナミ、chocoZAP、エニタイム、LAVA、FEELCYCLE、Dr.stretch |
| アパレル | ユニクロ、GU、しまむら、BEAMS、UNITED ARROWS |
| 雑貨・家具 | ニトリ、Francfranc、Loft、3COINS、DAISO |
| 家電 | ヨドバシ、ビックカメラ、ヤマダ |
| 食品スーパー | イオン、成城石井、カルディ、北野エース、オーケー |
| コスメ・ドラッグ | マツキヨ、ウエルシア、@cosme、コスメキッチン |
| 書店・ホビー | 紀伊國屋、TSUTAYA、アニメイト、ポケモンセンター |
| スポーツ | アルペン、ゼビオ、モンベル、ワークマン |
| ペット | Coo&RIKU、ペットプラス、イオンペット |
| 花・園芸 | 日比谷花壇、青山フラワーマーケット |
| ホテル | 東横INN、ドーミーイン、アパホテル |
| 美容・サロン | QBハウス、EARTH、TBC、ミュゼ |
| 自動車・バイク | オートバックス、イエローハット、2りんかん |
| 百貨店・商業施設 | 三越伊勢丹、高島屋、ルミネ、PARCO |

各ブランドのストア確認用URLパターン:

- App Store: `https://apps.apple.com/jp/search?term={ブランド名}`
- Google Play: `https://play.google.com/store/search?q={ブランド名}&c=apps&hl=ja`

---

## 5. お土産・菓子アプリ（追加）

スマレジSHOPの「物販＋会員＋クーポン＋予約/受取」参考に向く領域。

| ブランド | 地域/企業 | 公式アプリ | App Store / UI確認 | Google Play | UIで見るポイント |
|----------|-----------|------------|--------------------|-------------|------------------|
| シャトレーゼ | 山梨 | あり | [App Store](https://apps.apple.com/jp/app/%E3%82%B7%E3%83%A3%E3%83%88%E3%83%AC%E3%83%BC%E3%82%BC%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id844024333) | [検索](https://play.google.com/store/search?q=%E3%82%B7%E3%83%A3%E3%83%88%E3%83%AC%E3%83%BC%E3%82%BC&c=apps&hl=ja) | カシポ（会員証）、WEB予約・店頭受取、クーポン、店舗検索、Push |
| 銀座コージーコーナー | 東京 | あり（Yappli系） | [App Store](https://apps.apple.com/jp/app/%E9%8A%80%E5%BA%A7%E3%82%B3%E3%83%BC%E3%82%B8%E3%83%BC%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%BC-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id6446264862) | [Play](https://play.google.com/store/apps/details?id=li.yapp.app567F8DDB&hl=ja) | 会員証/マイル、クーポン、店舗検索、ネット予約、EC |
| GODIVA | ベルギー発 / 日本 | あり | [App Store](https://apps.apple.com/jp/app/%E3%82%B4%E3%83%87%E3%82%A3%E3%83%90-godiva-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1543392103) | [Play](https://play.google.com/store/apps/details?id=jp.co.godiva&hl=ja) | GODIVA Club会員証、スタンプ、ポイント/ランク、店舗検索、キャンペーン |
| Lindt | スイス発 | あり | [App Store](https://apps.apple.com/jp/app/%E3%83%AA%E3%83%B3%E3%83%84%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA-my-lindt-club/id1569279518) | [検索](https://play.google.com/store/search?q=Lindt&c=apps&hl=ja) | 会員クラブ、クーポン/特典、ブランド体験UI |
| LeTAO | 北海道 | あり（配信状況は要再確認） | [App Store](https://apps.apple.com/jp/app/letao-%E5%B0%8F%E6%A8%BD%E6%B4%8B%E8%8F%93%E5%AD%90%E8%88%97%E3%83%AB%E3%82%BF%E3%82%AA-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1459057727) / [公式DL案内](https://www.letao.jp/ext/appdl.html) | [検索](https://play.google.com/store/search?q=LeTAO&c=apps&hl=ja) | EC、クーポン、店舗情報、キャンペーン |
| 東京ばな奈 / シュガーバターの木 | グレープストーン | 要確認 | [検索](https://apps.apple.com/jp/search?term=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88) | [検索](https://play.google.com/store/search?q=%E6%9D%B1%E4%BA%AC%E3%81%B0%E3%81%AA%E5%A5%88&c=apps&hl=ja) | 土産物販・ギフト導線 |
| 白い恋人 / ROYCE' | 北海道 | 要確認 | [検索: 白い恋人](https://apps.apple.com/jp/search?term=%E7%99%BD%E3%81%84%E6%81%8B%E4%BA%BA) / [検索: ROYCE](https://apps.apple.com/jp/search?term=ROYCE) | 同上 | EC・ギフト・店舗 |
| PRESS BUTTER SAND / BAKE | 東京 | 要確認 | [検索](https://apps.apple.com/jp/search?term=PRESS%20BUTTER%20SAND) | [検索](https://play.google.com/store/search?q=PRESS%20BUTTER%20SAND&c=apps&hl=ja) | ポップアップ/土産×D2C |
| ねんりん家 / ヨックモック | 東京 | 要確認 | [ねんりん家](https://apps.apple.com/jp/search?term=%E3%81%AD%E3%82%93%E3%82%8A%E3%82%93%E5%AE%B6) / [ヨックモック](https://apps.apple.com/jp/search?term=%E3%83%A8%E3%83%83%E3%82%AF%E3%83%A2%E3%83%83%E3%82%AF) | 同上 | ギフト・季節商品 |
| ビアードパパ | 大阪 | 要確認 | [検索](https://apps.apple.com/jp/search?term=%E3%83%93%E3%82%A2%E3%83%BC%E3%83%89%E3%83%91%E3%83%91) | [検索](https://play.google.com/store/search?q=%E3%83%93%E3%82%A2%E3%83%BC%E3%83%89%E3%83%91%E3%83%91&c=apps&hl=ja) | チェーン菓子×クーポン |
| 赤福 / 551蓬莱 / クラブハリエ | 地方銘菓 | 要確認 | 各ブランド名でストア検索 | 同上 | 地域土産・百貨店催事導線 |

### 菓子・土産アプリから取るべきUIパターン

1. **会員証が1タップで出る**（会計時に最優先）
2. **予約→店頭受取**（ケーキ・ギフト）
3. **季節/限定商品のホーム訴求**（Pushと連動）
4. **ECと店舗在庫の両立**（取り置き/配送の分岐）
5. **ギフト導線**（のし・メッセージ・受け取り）

---

## 6. グローバル / 超有名アプリ（追加）

世界展開ブランドは「完成度の高い店舗アプリUI」の参考になる。

### 6.1 グローバル小売

| ブランド | 展開 | App Store / UI確認 | Google Play | UIで見るポイント |
|----------|------|--------------------|-------------|------------------|
| UNIQLO | 日本/グローバル | [App Store](https://apps.apple.com/jp/app/uniqlo%E3%82%A2%E3%83%97%E3%83%AA-%E3%83%A6%E3%83%8B%E3%82%AF%E3%83%AD%E3%82%A2%E3%83%97%E3%83%AA/id460989014) | [検索](https://play.google.com/store/search?q=UNIQLO&c=apps&hl=ja) | 商品検索、在庫、会員、EC、店舗 |
| ZARA | スペイン/グローバル | [App Store](https://apps.apple.com/jp/app/zara/id547951480) | [検索](https://play.google.com/store/search?q=ZARA&c=apps&hl=ja) | カタログUI、サイズ/在庫、店舗受取 |
| H&M | スウェーデン/グローバル | [App Store](https://apps.apple.com/jp/app/h-m/id834465911) | [検索](https://play.google.com/store/search?q=H%26M&c=apps&hl=ja) | 会員、クーポン、EC |
| Nike | 米国/グローバル | [App Store](https://apps.apple.com/jp/app/nike%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1095459556) | [検索](https://play.google.com/store/search?q=Nike&c=apps&hl=ja) | 会員、商品、店舗、ブランド体験 |
| adidas | ドイツ/グローバル | [App Store](https://apps.apple.com/jp/app/adidas-%E3%82%B9%E3%83%8B%E3%83%BC%E3%82%AB%E3%83%BC%E3%81%A8%E3%82%A2%E3%83%91%E3%83%AC%E3%83%AB%E3%81%AE%E3%82%B7%E3%83%A7%E3%83%83%E3%83%94%E3%83%B3%E3%82%B0%E3%82%A2%E3%83%97%E3%83%AA/id1266591536) | [検索](https://play.google.com/store/search?q=adidas&c=apps&hl=ja) | 会員、EC、店舗 |
| IKEA | スウェーデン/グローバル | [App Store](https://apps.apple.com/jp/app/ikea/id1452164827) | [検索](https://play.google.com/store/search?q=IKEA&c=apps&hl=ja) | 店舗マップ、在庫、リスト、スキャン |
| Sephora | フランス/グローバル | [検索](https://apps.apple.com/jp/search?term=Sephora) | [検索](https://play.google.com/store/search?q=Sephora&c=apps&hl=ja) | 美容会員、ポイント、店舗/EC |
| Decathlon | フランス/グローバル | [Developer](https://apps.apple.com/jp/developer/decathlon/id495106229) | [検索](https://play.google.com/store/search?q=Decathlon&c=apps&hl=ja) | スポーツ小売の店舗体験 |
| Walmart / Target / Costco | 米国 | 各検索URL | 同上 | スーパー/量販の会員・在庫・スキャンUI |
| 7-Eleven | グローバル | [検索](https://apps.apple.com/jp/search?term=7-Eleven) | [検索](https://play.google.com/store/search?q=7-Eleven&c=apps&hl=ja) | コンビニアプリの決済/クーポン |

### 6.2 グローバル飲食・カフェ

| ブランド | App Store / UI確認 | Google Play | UIで見るポイント |
|----------|--------------------|-------------|------------------|
| Starbucks Japan | [App Store](https://apps.apple.com/jp/app/%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9-%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3%E5%85%AC%E5%BC%8F%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%97%E3%83%AA/id1113037275) / [公式](https://www.starbucks.co.jp/mobile-app/) | [検索](https://play.google.com/store/search?q=Starbucks&c=apps&hl=ja) | Pay（会員証QR）、Rewards、Mobile Order & Pay、Gift、店舗検索、Inbox |
| McDonald's Japan | [App Store](https://apps.apple.com/jp/app/mcdonalds-japan/id413618155) | [検索](https://play.google.com/store/search?q=McDonald%27s&c=apps&hl=ja) | モバイルオーダー、クーポン、店舗 |
| KFC / Burger King / Domino's / Pizza Hut / Subway | 各ストア検索 | 同上 | 注文・クーポン・店舗受取の定石UI |
| Costa Coffee / Tim Hortons | 各ストア検索 | 同上 | カフェRewards型 |

### 6.3 レジャー・施設（参考）

| ブランド | UI確認 | 見るポイント |
|----------|--------|--------------|
| 東京ディズニーリゾート | [公式アプリ案内](https://s.disneyhotels.jp/tdr/app.html) | 地図、待ち時間、チケット、パーソナライズ |
| Universal Studios Japan | [App Store](https://apps.apple.com/us/app/universal-studios-japan/id532097000) | 施設内導線、待ち時間、購入 |

---

## 7. 優先して画面を見るアプリ（デザイン参考トップ）

実装・デザイン検討時は、まずこの10本を見る。

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

（○=中核、△=弱い/部分、×=主機能ではない）

---

## 8. 競合プラットフォーム調査（21サービス）

### 8.1 店舗アプリ制作・CRM・モバイルオーダー

| プラットフォーム | 概要 | 主要機能 | スマレジSHOPとの比較観点 | 公式 | 事例 |
|------------------|------|----------|--------------------------|------|------|
| [dinii](https://www.dinii.jp/) | 飲食DX一体型 | POS / モバイルオーダー / キャッシュレス / CRM | 飲食特化の注文〜CRM一体化 | [dinii.jp](https://www.dinii.jp/) | [事例](https://dinii.jp/casestudy/) |
| [Yappli CRM](https://yapp.li/crm/) | ノーコード店舗アプリ＋CRM | 会員 / ポイント / ランク / 電子マネー / クーポン / スタンプ / Push / セグメント | **最も直接的な比較対象** | [yapp.li/crm](https://yapp.li/crm/) | [Voice](https://yapp.li/voice.html) |
| [GMOおみせアプリ](https://gmo-app.jp/) | 店舗公式アプリ作成 | 会員証 / ポイント / スタンプ / クーポン / Push | 業種横断で近い | [gmo-app.jp](https://gmo-app.jp/) | [事例](https://gmo-app.jp/works_list.html) |
| [店舗アプリ（TenpoApp）](https://tenpoapp.com/) | 店舗・施設向けPF | Push / スタンプ / クーポン / 予約 / QR | 飲食以外（レジャー・ホテル）も強い | [tenpoapp.com](https://tenpoapp.com/) | [事例](https://tenpoapp.com/case/) |
| [みせプリ](https://misepuri.com/lp5/) | 中小向け店舗アプリ | 会員証 / クーポン / スタンプ / Push | 低価格・個店向けの比較 | [misepuri.com](https://misepuri.com/) | [事例](https://misepuri.com/casestudy/detail.php?cate=1) |
| [App Publisher](https://emberpoint.com/lp/apppublisher02.html) | EmberPoint | 公式アプリ / EC連携 / CRM | D2C・メーカー寄り | [emberpoint](https://emberpoint.com/lp/apppublisher02.html) | タカギ等 |
| [アプリンク（UPLINK）](https://www.uplink-app.com/) | USEN系店舗アプリ | 会員証 / スタンプ / クーポン / Push | 店舗集客・販促系 | [uplink-app.com](https://www.uplink-app.com/) | — |
| [toypo](https://toypo.me/) | リピーターCRMミニアプリ | スタンプ / クーポン / 会員証 / サブスク / 分析 | リピート特化の軽量競合 | [toypo.me](https://toypo.me/) | [事例](https://toypo.me/release/yossix) |
| [STORES ブランドアプリ](https://stores.jp/brandedapp) | STORES連携ブランドアプリ | 会員 / ポイント / クーポン / POS・EC連携 | エコシステム型競合 | [stores.jp](https://stores.jp/brandedapp) | — |

### 8.2 順番待ち

| プラットフォーム | 概要 | 公式 |
|------------------|------|------|
| [Airウェイト](https://airregi.jp/wait/) | 受付・順番待ち・LINE呼び出し | [LINE呼び出しFAQ](https://faq.airwait.jp/hc/ja/articles/900007104323) |
| [JUNBANMACHI](https://junbanmachi.jp/) | 順番待ち・受付管理 | [junbanmachi.jp](https://junbanmachi.jp/) |

### 8.3 飲食予約・台帳

| プラットフォーム | 概要 | 公式 | 事例 |
|------------------|------|------|------|
| [TableCheck](https://www.tablecheck.com/ja/join/) | 予約・顧客管理・インバウンド | [tablecheck.com](https://www.tablecheck.com/ja/) | [Success Stories](https://www.tablecheck.com/ja/join/success-stories/) |
| [トレタ](https://toreta.in/toreta-daicho/) | 予約台帳〜LINE・モバイルオーダー | [toreta.in](https://toreta.in/) | [店舗規模別](https://toreta.in/stores_range/1-10stores/) |
| [ebica](https://www.ebica.jp/) | 予約・集客・AI電話 | [ebica.jp](https://www.ebica.jp/) | [事例](https://www.ebica.jp/casestudy/) |

### 8.4 LINEミニアプリ

| プラットフォーム | 概要 | 公式 |
|------------------|------|------|
| [Fannaly](https://fannaly.jp/) | LINEミニアプリCRM | [fannaly.jp](https://fannaly.jp/) |
| [Enlyt](https://enlyt.co.jp/lp-line/) | LINEミニアプリ開発 | [enlyt.co.jp](https://enlyt.co.jp/lp-line/) |
| [アイリッジ](https://lineminiapp.iridge.jp/) | LINEミニアプリ（商業施設・ポイント） | [lineminiapp.iridge.jp](https://lineminiapp.iridge.jp/) |
| [EPG](https://www.epg.co.jp/service/line-mini-app/) | LINE順番待ち・予約・友だち化 | [epg.co.jp](https://www.epg.co.jp/service/line-mini-app/) |
| [Zen-web](https://zen-web.co.jp/) | LINE/Web開発 | [zen-web.co.jp](https://zen-web.co.jp/) |

### 8.5 その他

| サービス | 概要 | URL |
|----------|------|-----|
| Pokepay | デジタルウォレット・会員・ポイント | [事例記事](https://pokepay.jp/ja/entries/97) |
| Amazing Pocket | サイネージ/メニュー表示 | [食堂メニュー](https://www.amazing-pocket.com/digital-signage/system/cafeteriamenu.html) |

---

## 9. スマレジSHOP向けデザイン示唆

### 9.1 ホーム画面の役割分担

有名アプリはホームを「何でも置けるダッシュボード」にしない。

| 優先度 | ホームに置くもの | 参考 |
|--------|------------------|------|
| 必須 | 会員証への最短導線 | Starbucks Pay、シャトレーゼ カシポ |
| 必須 | 今使えるクーポン/特典 | コージーコーナー、GODIVA |
| 推奨 | 近くの店舗 | ほぼ全業種 |
| 推奨 | 今週の主役コンテンツ（1つ） | 季節商品・キャンペーン |
| 避けがち | 統計・メタ情報の羅列 | — |

### 9.2 業種別の主導線

| 業種 | 主導線 | サブ導線 |
|------|--------|----------|
| カフェ/ファストフード | モバイルオーダー → 受取 | 会員証、Rewards |
| 菓子/土産 | 会員証 → クーポン / 予約受取 | EC、ギフト |
| アパレル/雑貨 | 商品検索 → 在庫/店舗 | 会員、クーポン |
| ジム/サロン | 会員証 → 予約/スケジュール | クーポン |
| ホテル | 予約 → QRチェックイン | 会員・ポイント |
| 家電/書店 | 検索 → 在庫/取り置き | ポイント |

### 9.3 画面設計チェックリスト（UI確認用）

各参考アプリを見るとき、次を記録する。

- [ ] 会員証は何タップで出せるか
- [ ] バーコード/QRのコントラスト・サイズは十分か
- [ ] クーポンは「使えるものだけ」が先に出るか
- [ ] 店舗検索は地図 / リスト / 営業時間のどれが主か
- [ ] 注文/予約の完了画面は不安を残さないか（番号・時刻・場所）
- [ ] Push許諾のタイミングは初回起動直後か、価値提示後か
- [ ] ECと店舗の切り替えは明示されているか
- [ ] 未ログインでも価値が見えるか（カタログ/店舗情報）

---

## 10. 次の調査ステップ

1. **優先10アプリ**のApp Storeスクショを見ながら、上記チェックリストを埋める
2. Yappli / GMO / toypo / dinii の公式画面と導入事例UIを同じ表で比較する
3. 結果を次の表に落とす（スマレジSHOPの現状列を追加）

| ブランド | 業界 | 会員証 | ポイント | スタンプ | クーポン | 店舗検索 | モバイル注文 | 予約 | EC | Push | UI特徴 | スマレジSHOPとの差 |
|----------|------|--------|----------|----------|----------|----------|--------------|------|----|------|--------|-------------------|
| （記入） | | | | | | | | | | | | |

4. 必要なら、業界TOP企業リストを「導入可能性A/B/C」付き営業リストへ変換する

---

## 11. 参考リンクまとめ（クイックアクセス）

### デザイン最優先

- [Starbucks Japan App Store](https://apps.apple.com/jp/app/%E3%82%B9%E3%82%BF%E3%83%BC%E3%83%90%E3%83%83%E3%82%AF%E3%82%B9-%E3%82%B8%E3%83%A3%E3%83%91%E3%83%B3%E5%85%AC%E5%BC%8F%E3%83%A2%E3%83%90%E3%82%A4%E3%83%AB%E3%82%A2%E3%83%97%E3%83%AA/id1113037275)
- [シャトレーゼ公式アプリ](https://apps.apple.com/jp/app/%E3%82%B7%E3%83%A3%E3%83%88%E3%83%AC%E3%83%BC%E3%82%BC%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id844024333)
- [銀座コージーコーナー公式アプリ](https://apps.apple.com/jp/app/%E9%8A%80%E5%BA%A7%E3%82%B3%E3%83%BC%E3%82%B8%E3%83%BC%E3%82%B3%E3%83%BC%E3%83%8A%E3%83%BC-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id6446264862)
- [GODIVA公式アプリ](https://apps.apple.com/jp/app/%E3%82%B4%E3%83%87%E3%82%A3%E3%83%90-godiva-%E5%85%AC%E5%BC%8F%E3%82%A2%E3%83%97%E3%83%AA/id1543392103)
- [UNIQLOアプリ](https://apps.apple.com/jp/app/uniqlo%E3%82%A2%E3%83%97%E3%83%AA-%E3%83%A6%E3%83%8B%E3%82%AF%E3%83%AD%E3%82%A2%E3%83%97%E3%83%AA/id460989014)

### プラットフォーム

- [Yappli CRM](https://yapp.li/crm/)
- [GMOおみせアプリ](https://gmo-app.jp/)
- [dinii](https://www.dinii.jp/)
- [toypo](https://toypo.me/)
- [TenpoApp](https://tenpoapp.com/)
- [TableCheck](https://www.tablecheck.com/ja/join/)
- [トレタ](https://toreta.in/toreta-daicho/)
- [アイリッジ LINEミニアプリ](https://lineminiapp.iridge.jp/)

---

*最終更新: 調査内容をデザイン参考用に再整理（お土産・菓子 / グローバル有名アプリURLを含む）*
