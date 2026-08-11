import { CalendarDays, ChevronLeft, ChevronRight, Heart, MapPin, Navigation, RotateCcw } from "lucide-react";
import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import type { FeatureType, NewsItem, StoreSummary } from "../../types/app";
import type { RuntimeCatalogItem, RuntimeHistoryItem } from "../../types/runtime";

interface CatalogItem {
  id: string;
  name: string;
  description: string;
  price: string;
}

const defaultCatalogItems: CatalogItem[] = [
  { id: "p1", name: "季節のシグネチャー", description: "期間限定のおすすめ", price: "¥680" },
  { id: "p2", name: "クラシックセレクション", description: "長く愛される定番", price: "¥520" },
  { id: "p3", name: "デイリーエッセンシャル", description: "毎日にちょうどいい一品", price: "¥380" },
];

const catalogItemsByStore: Record<string, CatalogItem[]> = {
  store_izakaya: [
    { id: "i1", name: "炭火焼き鳥 五本盛り", description: "今夜のおすすめ串", price: "¥980" },
    { id: "i2", name: "だし巻き玉子", description: "注文ごとに焼き上げ", price: "¥680" },
    { id: "i3", name: "塩ゆで枝豆", description: "最初の一品", price: "¥420" },
  ],
  store_fastfood: [
    { id: "q1", name: "ゴールデンダブルチーズ", description: "人気 No.1 バーガー", price: "¥690" },
    { id: "q2", name: "スパイシーチキンセット", description: "期間限定セット", price: "¥780" },
    { id: "q3", name: "クリスピーポテト M", description: "揚げたてサイド", price: "¥320" },
  ],
};

const galleryItems = [
  { title: "季節のおすすめ", category: "SEASONAL", description: "今の季節におすすめの商品・スタイルをご紹介します。店舗でのご相談も承っています。" },
  { title: "定番セレクション", category: "STANDARD", description: "長く選ばれている定番を、使い方や組み合わせとともにご紹介します。" },
  { title: "スタッフセレクト", category: "STAFF PICK", description: "店舗スタッフが実際におすすめする組み合わせとポイントをご覧いただけます。" },
  { title: "店内のご案内", category: "IN STORE", description: "店舗の雰囲気や設備、サービスの利用イメージをご紹介します。" },
  { title: "新着コレクション", category: "NEW", description: "入荷したばかりの商品・サービスをまとめてご確認いただけます。" },
  { title: "会員限定セレクト", category: "MEMBERS", description: "会員の方だけにご案内している限定商品と特典です。" },
] as const;

interface CollectionDemoProps {
  feature: FeatureType;
  featureImage: string;
  heroImage: string;
  news: NewsItem[];
  stores: StoreSummary[];
  storeId: string;
  orderPath: string;
  liveCatalog?: RuntimeCatalogItem[];
  history?: RuntimeHistoryItem[];
}

export function CollectionDemo({ feature, featureImage, heroImage, news, stores, storeId, orderPath, liveCatalog, history }: CollectionDemoProps) {
  const navigate = useNavigate();
  const [favorites, setFavorites] = useState<string[]>(["p1", "p2"]);
  const [favoriteStore, setFavoriteStore] = useState(stores[0]?.name);
  const [selectedGallery, setSelectedGallery] = useState<number | null>(null);
  const [selectedNews, setSelectedNews] = useState<NewsItem | null>(null);
  const catalogItems = liveCatalog?.length
    ? liveCatalog.map((item) => ({ ...item, price: `¥${item.price.toLocaleString()}` }))
    : catalogItemsByStore[storeId] ?? defaultCatalogItems;

  const toggleFavorite = (id: string) => {
    setFavorites((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
  };

  if (feature === "gallery") {
    const selectedItem = selectedGallery === null ? null : galleryItems[selectedGallery];
    return (
      <>
        <div className="gallery-grid">
          {galleryItems.map((item, index) => (
            <button type="button" className={`gallery-item gallery-item--${(index % 3) + 1}`} key={item.title} aria-label={`${item.title}の詳細を見る`} onClick={() => setSelectedGallery(index)}>
              <img src={index % 2 === 0 ? featureImage : heroImage} alt={`${item.title}のイメージ`} />
              <span><small>{item.category}</small>{item.title}</span>
            </button>
          ))}
        </div>
        <BottomSheet open={selectedGallery !== null} onClose={() => setSelectedGallery(null)} title="ギャラリー詳細">
          {selectedItem ? <div className="gallery-detail">
            <img src={selectedGallery! % 2 === 0 ? featureImage : heroImage} alt={`${selectedItem.title}のイメージ`} />
            <div className="gallery-detail__body"><span>{selectedItem.category}</span><h2>{selectedItem.title}</h2><p>{selectedItem.description}</p></div>
            <div className="gallery-detail__actions">
              <Button variant="outline" disabled={selectedGallery === 0} onClick={() => setSelectedGallery((current) => current === null ? 0 : Math.max(0, current - 1))}><ChevronLeft size={16} />前へ</Button>
              <span>{selectedGallery! + 1} / {galleryItems.length}</span>
              <Button variant="outline" disabled={selectedGallery === galleryItems.length - 1} onClick={() => setSelectedGallery((current) => current === null ? 0 : Math.min(galleryItems.length - 1, current + 1))}>次へ<ChevronRight size={16} /></Button>
            </div>
          </div> : null}
        </BottomSheet>
      </>
    );
  }

  if (feature === "news") {
    return (
      <>
        <div className="feature-card-list">
          {news.map((item, index) => (
            <Card className="news-detail-card" padding="lg" key={item.id}>
              {index === 0 ? <img src={heroImage} alt={`${item.title}のイメージ`} /> : null}
              <div><span className="feature-success__eyebrow">{item.category} · {item.date}</span><h2>{item.title}</h2><p>店舗からのお知らせです。詳しい内容と対象条件をご確認いただけます。</p><Button variant="soft" onClick={() => setSelectedNews(item)}>詳しく見る <ChevronRight size={15} /></Button></div>
            </Card>
          ))}
        </div>
        <BottomSheet open={selectedNews !== null} onClose={() => setSelectedNews(null)} title="お知らせ詳細">
          {selectedNews ? <article className="news-sheet-detail">
            <img src={heroImage} alt={`${selectedNews.title}のイメージ`} />
            <div className="news-sheet-detail__meta"><span>{selectedNews.category}</span><time><CalendarDays size={14} />{selectedNews.date}</time></div>
            <h2>{selectedNews.title}</h2>
            <p>いつも当店をご利用いただき、ありがとうございます。</p>
            <p>店舗からの最新情報をご案内します。対象期間や利用条件をご確認のうえ、店頭またはアプリ内のサービスをご利用ください。</p>
            <div className="news-sheet-detail__notice"><strong>ご利用にあたって</strong><span>内容は予告なく変更となる場合があります。詳しくは店舗スタッフまでお問い合わせください。</span></div>
            <Button size="lg" onClick={() => setSelectedNews(null)}>内容を確認しました</Button>
          </article> : null}
        </BottomSheet>
      </>
    );
  }

  if (feature === "storeSearch") {
    return (
      <div className="feature-card-list">
        {stores.map((store, index) => (
          <Card className="store-result" padding="lg" key={store.name}>
            <div className="store-result__icon"><MapPin size={22} /></div>
            <div><span className="feature-success__eyebrow">{index === 0 ? "最寄り店舗" : "店舗"}</span><h2>{store.name}</h2><p>{store.address}<br />{store.openHours} · {store.access}</p></div>
            <div className="store-result__actions"><Button variant={favoriteStore === store.name ? "soft" : "outline"} onClick={() => setFavoriteStore(store.name)}><Heart size={16} fill={favoriteStore === store.name ? "currentColor" : "none"} />{favoriteStore === store.name ? "登録済み" : "よく行く店舗"}</Button><Button variant="ghost" ariaLabel="経路を表示"><Navigation size={18} /></Button></div>
          </Card>
        ))}
      </div>
    );
  }

  if (feature === "purchaseHistory" || feature === "orderHistory") {
    const isOrder = feature === "orderHistory";
    const liveHistory = history?.filter((item) => item.type === (isOrder ? "order" : "purchase"));
    if (liveHistory?.length) {
      return <div className="feature-card-list">{liveHistory.map((item) => <Card className="history-card" padding="lg" key={item.id}><div className="history-card__head"><span>{item.date}</span><b>{item.status}</b></div><h2>{item.title}</h2><p>{item.summary}</p><div className="history-card__footer"><strong>¥{item.total.toLocaleString()}</strong>{isOrder ? <Button variant="soft" size="sm" onClick={() => navigate(orderPath)}><RotateCcw size={15} />もう一度注文</Button> : null}</div></Card>)}</div>;
    }
    return (
      <div className="feature-card-list">
        {["2026.08.09", "2026.07.28", "2026.07.12"].map((date, index) => (
          <Card className="history-card" padding="lg" key={date}>
            <div className="history-card__head"><span>{date}</span><b>{index === 0 && isOrder ? "受取済み" : "完了"}</b></div>
            <h2>{isOrder ? `モバイルオーダー #MO-${240 - index}` : `購入明細 #PS-${1082 - index}`}</h2>
            <p>シグネチャーメニュー 他{index + 1}点</p>
            <div className="history-card__footer"><strong>¥{(1280 + index * 430).toLocaleString()}</strong><Button variant="soft" size="sm" onClick={() => navigate(orderPath)}><RotateCcw size={15} />もう一度注文</Button></div>
          </Card>
        ))}
      </div>
    );
  }

  const visibleItems = feature === "favorite" ? catalogItems.filter((item) => favorites.includes(item.id)) : catalogItems;

  return (
    <div className="catalog-grid">
      {visibleItems.map((item, index) => {
        const favorite = favorites.includes(item.id);
        return (
          <Card className="catalog-card" padding="sm" key={item.id}>
            <div className="catalog-card__image"><img src={index % 2 === 0 ? featureImage : heroImage} alt="" /><button type="button" aria-label={`${item.name}をお気に入り${favorite ? "から削除" : "に追加"}`} onClick={() => toggleFavorite(item.id)}><Heart size={17} fill={favorite ? "currentColor" : "none"} /></button></div>
            <div className="catalog-card__body"><span>{item.description}</span><h2>{item.name}</h2><strong>{item.price}</strong><Button variant="soft" size="sm" onClick={() => navigate(orderPath)}>選択する</Button></div>
          </Card>
        );
      })}
      {visibleItems.length === 0 ? <Card className="feature-empty" padding="lg"><Heart size={28} /><h2>お気に入りはありません</h2><p>メニューから気になる商品を保存できます。</p><Button onClick={() => navigate("/menu")}>メニューを見る</Button></Card> : null}
    </div>
  );
}
