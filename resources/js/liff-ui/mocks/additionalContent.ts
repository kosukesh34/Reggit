import type { CouponItem, MemberProfile, NewsItem, StoreSummary } from "../types/app";

export const additionalMemberProfiles: Record<string, MemberProfile> = {
  store_bakery: { name: "高橋 美咲", number: "000123401", rank: "Bread Lover", points: 1260, expiration: "2027-04-30", nextRankLabel: "あと240ptで Baker's Circle" },
  store_fitness: { name: "伊藤 健", number: "000123402", rank: "Active", points: 3420, expiration: "2027-08-31", nextRankLabel: "今月あと2回来館でランクアップ" },
  store_flower: { name: "渡辺 葵", number: "000123403", rank: "Bloom", points: 760, expiration: "2027-05-31", nextRankLabel: "あと3回のご利用で特典を獲得" },
  store_hotel: { name: "中村 直子", number: "000123404", rank: "Suite", points: 12800, expiration: "2027-12-31", nextRankLabel: "次の滞在でレイトチェックアウト無料" },
  store_pets: { name: "小林 海", number: "000123405", rank: "Best Friend", points: 1890, expiration: "2027-02-28", nextRankLabel: "あと110ptでケア特典を獲得" },
  store_clinic: { name: "加藤 亮", number: "000123406", rank: "Wellness", points: 0, expiration: "2027-07-31", nextRankLabel: "次回の定期チェックは11月です" },
  store_market: { name: "吉田 恵", number: "000123407", rank: "Local Plus", points: 3150, expiration: "2027-03-31", nextRankLabel: "あと350ptで送料無料特典" },
  store_dining: { name: "山本 誠", number: "000123408", rank: "Regular", points: 1840, expiration: "2027-09-30", nextRankLabel: "あと160ptでお食事特典を獲得" },
  store_izakaya: { name: "松本 拓也", number: "000123409", rank: "常連", points: 960, expiration: "2027-08-31", nextRankLabel: "あと2回来店で裏メニュー特典" },
  store_fastfood: { name: "井上 さくら", number: "000123410", rank: "GOLD BITE", points: 2380, expiration: "2027-08-31", nextRankLabel: "あと620ptでPLATINUM BITE" },
};

export const additionalCoupons: Record<string, CouponItem[]> = {
  store_bakery: [
    { id: "b1", title: "焼きたてパン 100円OFF", description: "店内焼き上げ商品に利用できます", validUntil: "2026-09-30", image: "/assets/cafe-feature.webp", featured: true },
    { id: "b2", title: "秋の新作パン先行特典", description: "9月から利用できる会員限定クーポン", validFrom: "2026-09-01", validUntil: "2026-10-15", image: "/assets/cafe-hero.webp" },
  ],
  store_fitness: [
    { id: "f1", title: "パーソナル体験 20% OFF", description: "初回セッションに利用できます", validUntil: "2026-10-31", image: "/assets/apparel-hero.webp", featured: true },
    { id: "f2", title: "プロテインドリンク無料", description: "トレーニング後にご利用ください", validUntil: "2026-09-30", image: "/assets/apparel-feature.webp" },
  ],
  store_flower: [
    { id: "l1", title: "季節のブーケ 10% OFF", description: "店頭受取・予約注文で利用可能", validUntil: "2026-09-20", image: "/assets/salon-feature.webp", featured: true },
    { id: "l2", title: "花の定期便 初月特典", description: "9月開始の定期便が対象です", validFrom: "2026-09-01", validUntil: "2026-10-31", image: "/assets/salon-hero.webp" },
  ],
  store_hotel: [
    { id: "h1", title: "朝食アップグレード無料", description: "会員限定の滞在特典です", validUntil: "2026-11-30", image: "/assets/cafe-hero.webp", featured: true },
    { id: "h2", title: "レイトチェックアウト", description: "空室状況に応じて13時まで延長", validUntil: "2026-12-20", image: "/assets/apparel-feature.webp" },
  ],
  store_pets: [
    { id: "p1", title: "グルーミング 15% OFF", description: "全犬種の基本コースが対象です", validUntil: "2026-09-30", image: "/assets/salon-feature.webp", featured: true },
    { id: "p2", title: "おやつサンプルプレゼント", description: "税込3,000円以上のお買い物で利用可能", validUntil: "2026-10-15", image: "/assets/cafe-feature.webp" },
  ],
  store_clinic: [
    { id: "k1", title: "ウェルネス相談のご案内", description: "会員向けのコンディション相談です", validUntil: "2026-10-31", image: "/assets/salon-hero.webp", featured: true },
  ],
  store_market: [
    { id: "m1", title: "旬の野菜セット 10% OFF", description: "今週のファーマーズセレクトが対象", validUntil: "2026-09-10", image: "/assets/cafe-feature.webp", featured: true },
    { id: "m2", title: "配送料無料クーポン", description: "税込5,000円以上の注文で利用可能", validUntil: "2026-10-31", image: "/assets/apparel-hero.webp" },
  ],
  store_dining: [
    { id: "d1", title: "平日ランチ 10% OFF", description: "月曜から金曜のランチタイムに利用できます", validUntil: "2026-09-30", image: "/assets/dining-feature.webp", featured: true },
    { id: "d2", title: "季節のデザートプレゼント", description: "9月から使える会員限定特典", validFrom: "2026-09-01", validUntil: "2026-10-31", image: "/assets/dining-hero.webp" },
  ],
  store_izakaya: [
    { id: "i1", title: "乾杯ドリンク 1杯無料", description: "17時以降、店内飲食でご利用いただけます", validUntil: "2026-09-30", image: "/assets/izakaya-feature.webp", featured: true },
    { id: "i2", title: "焼き鳥 五本盛り 200円引き", description: "会員証のご提示でテーブル会計に適用", validUntil: "2026-10-31", image: "/assets/izakaya-hero.webp" },
  ],
  store_fastfood: [
    { id: "q1", title: "ポテト M 無料", description: "バーガーセットのモバイルオーダーで利用可能", validUntil: "2026-09-15", image: "/assets/fastfood-feature.webp", featured: true },
    { id: "q2", title: "ダブルチーズセット 150円OFF", description: "ランチタイムを除く全時間帯で利用できます", validUntil: "2026-10-31", image: "/assets/fastfood-hero.webp" },
  ],
};

export const additionalNews: Record<string, NewsItem[]> = {
  store_bakery: [{ id: "bn1", title: "秋の焼き上がりスケジュール", date: "2026-08-10", category: "BAKE" }, { id: "bn2", title: "パンの取り置きを開始しました", date: "2026-08-05", category: "INFO" }],
  store_fitness: [{ id: "fn1", title: "9月のグループレッスンを公開", date: "2026-08-10", category: "PROGRAM" }, { id: "fn2", title: "混雑状況をアプリで確認できます", date: "2026-08-03", category: "UPDATE" }],
  store_flower: [{ id: "ln1", title: "秋色ブーケの予約を開始", date: "2026-08-09", category: "FLOWERS" }, { id: "ln2", title: "花の定期便に新コースが登場", date: "2026-08-04", category: "NEWS" }],
  store_hotel: [{ id: "hn1", title: "秋のステイプランを公開しました", date: "2026-08-08", category: "STAY" }, { id: "hn2", title: "ルーフトップ営業時間のご案内", date: "2026-08-02", category: "INFO" }],
  store_pets: [{ id: "pn1", title: "夏のグルーミングケアについて", date: "2026-08-09", category: "CARE" }, { id: "pn2", title: "新しいナチュラルフードが入荷", date: "2026-08-05", category: "SHOP" }],
  store_clinic: [{ id: "kn1", title: "夏季診療時間のお知らせ", date: "2026-08-10", category: "CLINIC" }, { id: "kn2", title: "オンライン問診に対応しました", date: "2026-08-01", category: "UPDATE" }],
  store_market: [{ id: "mn1", title: "今週の生産者とおすすめ食材", date: "2026-08-11", category: "LOCAL" }, { id: "mn2", title: "当日配送エリアを拡大しました", date: "2026-08-06", category: "DELIVERY" }],
  store_dining: [{ id: "dn1", title: "秋のディナーメニューを公開しました", date: "2026-08-11", category: "MENU" }, { id: "dn2", title: "テーブルオーダーをご利用いただけます", date: "2026-08-07", category: "SERVICE" }],
  store_izakaya: [{ id: "in1", title: "今週のおすすめ地酒を入荷しました", date: "2026-08-11", category: "今週の酒" }, { id: "in2", title: "金曜夜のご予約はお早めに", date: "2026-08-08", category: "店舗案内" }],
  store_fastfood: [{ id: "qn1", title: "スパイシーチキンが期間限定で登場", date: "2026-08-11", category: "NEW MENU" }, { id: "qn2", title: "受取ロッカー対応店舗を拡大", date: "2026-08-06", category: "SERVICE" }],
};

export const additionalStoreSummaries: Record<string, StoreSummary> = {
  store_bakery: { name: "代々木上原店", address: "東京都渋谷区西原3-8-2", openHours: "07:30 - 19:00", access: "代々木上原駅 徒歩2分", phone: "03-4100-1101" },
  store_fitness: { name: "恵比寿スタジオ", address: "東京都渋谷区恵比寿1-9-5", openHours: "06:30 - 23:00", access: "恵比寿駅 徒歩3分", phone: "03-4100-1102" },
  store_flower: { name: "中目黒店", address: "東京都目黒区上目黒2-12-3", openHours: "10:00 - 19:00", access: "中目黒駅 徒歩4分", phone: "03-4100-1103" },
  store_hotel: { name: "青山", address: "東京都港区南青山4-18-7", openHours: "24 HOURS", access: "表参道駅 徒歩6分", phone: "03-4100-1104" },
  store_pets: { name: "二子玉川店", address: "東京都世田谷区玉川3-15-6", openHours: "10:00 - 20:00", access: "二子玉川駅 徒歩5分", phone: "03-4100-1105" },
  store_clinic: { name: "丸の内院", address: "東京都千代田区丸の内2-4-1", openHours: "09:00 - 18:30", access: "東京駅 徒歩3分", phone: "03-4100-1106" },
  store_market: { name: "吉祥寺店", address: "東京都武蔵野市吉祥寺本町1-6-4", openHours: "09:00 - 21:00", access: "吉祥寺駅 徒歩4分", phone: "03-4100-1107" },
  store_dining: { name: "新宿南口店", address: "東京都新宿区新宿4-2-8", openHours: "11:00 - 22:00", access: "新宿駅 徒歩3分", phone: "03-4100-1108" },
  store_izakaya: { name: "新橋 鳥灯り店", address: "東京都港区新橋3-14-6", openHours: "17:00 - 24:00", access: "新橋駅 烏森口 徒歩3分", phone: "03-4100-1109" },
  store_fastfood: { name: "渋谷センター街店", address: "東京都渋谷区宇田川町25-4", openHours: "07:00 - 23:00", access: "渋谷駅 ハチ公口 徒歩4分", phone: "03-4100-1110" },
};
