import { Link } from "react-router-dom";
import type { NewsItem } from "../../types/app";
import { formatDisplayDate } from "../../utils/date";

interface NewsSectionProps {
  news: NewsItem[];
  compact?: boolean;
}

export function NewsSection({ news, compact = false }: NewsSectionProps) {
  const items = news.slice(0, compact ? 3 : 2);

  return (
    <section className="home-section" aria-labelledby="news-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="news-title">お知らせ</h2>
        <Link className="section-head__link" to="/news">すべて見る</Link>
      </div>
      <div className={`news-list${compact ? " news-list--compact" : ""}`}>
        {items.length > 0 ? items.map((item) => (
          <Link className="news-row" to={`/news?announcement=${encodeURIComponent(item.id)}`} key={item.id}>
            <span className="news-row__date">{formatDisplayDate(item.date, "yyyy.MM.dd", "日付未設定")}</span>
            <span className="news-row__title">{item.title}</span>
          </Link>
        )) : <p className="empty-copy">現在お知らせはありません。</p>}
      </div>
    </section>
  );
}
