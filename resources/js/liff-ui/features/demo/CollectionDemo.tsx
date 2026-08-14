import { CalendarDays, ChevronLeft, ChevronRight, Heart, MapPin, Navigation, RotateCcw } from "lucide-react";
import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import { BottomSheet } from "../../components/ui/BottomSheet";
import { Button } from "../../components/ui/Button";
import { Card } from "../../components/ui/Card";
import type { FeatureType, NewsItem, StoreSummary } from "../../types/app";
import type { RuntimeCatalogItem, RuntimeHistoryItem } from "../../types/runtime";
import { formatDisplayDate } from "../../utils/date";

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
  onReadNews?: (announcementId: string) => Promise<void>;
  isLive?: boolean;
}

function newsExcerpt(body: string, maxLen = 96): string {
  const text = body.replace(/\s+/g, " ").trim();
  if (text.length <= maxLen) {
    return text;
  }
  return `${text.slice(0, maxLen)}…`;
}

export function CollectionDemo({ feature, featureImage, heroImage, news, stores, storeId, orderPath, liveCatalog, history, onReadNews, isLive = false }: CollectionDemoProps) {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const [favorites, setFavorites] = useState<string[]>(["p1", "p2"]);
  const [favoriteStore, setFavoriteStore] = useState(stores[0]?.name);
  const [selectedGallery, setSelectedGallery] = useState<number | null>(null);
  const [selectedNews, setSelectedNews] = useState<NewsItem | null>(null);
  const catalogItems = isLive || (liveCatalog !== undefined && liveCatalog.length > 0)
    ? (liveCatalog ?? []).map((item) => ({ ...item, price: `¥${item.price.toLocaleString()}` }))
    : catalogItemsByStore[storeId] ?? defaultCatalogItems;

  const toggleFavorite = (id: string) => {
    setFavorites((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
  };

  const openNews = (item: NewsItem) => {
    setSelectedNews(item);
    navigate(`/news?announcement=${encodeURIComponent(item.id)}`, { replace: false });
    if (item.isRead !== true) {
      void onReadNews?.(item.id);
    }
  };

  const closeNews = () => {
    setSelectedNews(null);
    navigate("/news", { replace: true });
  };

  useEffect(() => {
    if (feature !== "news") return;
    const requestedId = searchParams.get("announcement");
    if (!requestedId) {
      setSelectedNews(null);
      return;
    }
    const requested = news.find((item) => item.id === requestedId);
    if (!requested) return;
    setSelectedNews(requested);
    if (requested.isRead !== true) {
      void onReadNews?.(requested.id);
    }
  }, [feature, news, onReadNews, searchParams]);

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
    if (selectedNews !== null) {
      const body = selectedNews.body || "店舗からのお知らせです。";
      return (
        <article className="news-article">
          <button type="button" className="news-article__back" onClick={closeNews}>
            <ChevronLeft size={18} aria-hidden="true" />
            お知らせ一覧
          </button>
          {selectedNews.image ? (
            <div className="news-article__hero">
              <img src={selectedNews.image} alt="" />
            </div>
          ) : null}
          <div className="news-article__panel">
            <div className="news-article__meta">
              <span className="news-article__category">{selectedNews.category}</span>
              <time className="news-article__date">
                <CalendarDays size={14} aria-hidden="true" />
                {formatDisplayDate(selectedNews.date, "yyyy年M月d日 HH:mm", selectedNews.date)}
              </time>
            </div>
            <h1 className="news-article__title">{selectedNews.title}</h1>
            <div className="news-article__body">{body}</div>
          </div>
        </article>
      );
    }

    return (
      <div className="news-feed">
        {news.map((item) => {
          const excerpt = newsExcerpt(item.body || "店舗からのお知らせです。");
          return (
            <button
              type="button"
              className={`news-feed-item${item.isRead === true ? "" : " news-feed-item--unread"}`}
              key={item.id}
              onClick={() => openNews(item)}
            >
              {item.image ? (
                <div className="news-feed-item__media">
                  <img src={item.image} alt="" loading="lazy" />
                </div>
              ) : (
                <div className="news-feed-item__media news-feed-item__media--placeholder" aria-hidden="true">
                  <CalendarDays size={22} />
                </div>
              )}
              <div className="news-feed-item__body">
                <div className="news-feed-item__meta">
                  <span>{item.category}</span>
                  <time>{formatDisplayDate(item.date, "yyyy.MM.dd HH:mm", item.date)}</time>
                  {item.isRead !== true ? <span className="news-feed-item__new">NEW</span> : null}
                </div>
                <h2 className="news-feed-item__title">{item.title}</h2>
                <p className="news-feed-item__excerpt">{excerpt}</p>
                <span className="news-feed-item__more">続きを読む <ChevronRight size={14} aria-hidden="true" /></span>
              </div>
            </button>
          );
        })}
        {news.length === 0 ? (
          <Card className="feature-empty" padding="lg">
            <CalendarDays size={28} />
            <h2>お知らせはありません</h2>
            <p>新しいお知らせが公開されると、ここに表示されます。</p>
          </Card>
        ) : null}
      </div>
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
    const liveHistory = (history ?? []).filter((item) => item.type === (isOrder ? "order" : "purchase"));
    if (isLive || liveHistory.length > 0) {
      if (liveHistory.length === 0) {
        return (
          <Card className="feature-empty" padding="lg">
            <RotateCcw size={28} />
            <h2>{isOrder ? "注文履歴はありません" : "購入履歴はありません"}</h2>
            <p>{isOrder ? "注文すると、ここに履歴が表示されます。" : "購入すると、ここに明細が表示されます。"}</p>
          </Card>
        );
      }
      return (
        <div className="feature-card-list">
          {liveHistory.map((item) => (
            <Card className="history-card" padding="lg" key={item.id}>
              <div className="history-card__head"><span>{item.date}</span><b>{item.status}</b></div>
              <h2>{item.title}</h2>
              <p>{item.summary}</p>
              <div className="history-card__footer">
                <strong>¥{item.total.toLocaleString()}</strong>
                {isOrder ? <Button variant="soft" size="sm" onClick={() => navigate(orderPath)}><RotateCcw size={15} />もう一度注文</Button> : null}
              </div>
            </Card>
          ))}
        </div>
      );
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
      {visibleItems.length === 0 ? (
        <Card className="feature-empty" padding="lg">
          <Heart size={28} />
          <h2>{feature === "favorite" ? "お気に入りはありません" : "商品はありません"}</h2>
          <p>{feature === "favorite" ? "メニューから気になる商品を保存できます。" : "商品が登録されると、ここに表示されます。"}</p>
          {feature === "favorite" ? <Button onClick={() => navigate("/menu")}>メニューを見る</Button> : null}
        </Card>
      ) : null}
    </div>
  );
}
