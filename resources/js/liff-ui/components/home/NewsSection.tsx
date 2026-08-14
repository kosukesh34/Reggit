import { Calendar } from "lucide-react";
import type { NewsItem } from "../../types/app";
import { ChevronRight } from "lucide-react";
import { Link } from "react-router-dom";
import { formatDisplayDate } from "../../utils/date";

interface NewsSectionProps {
  news: NewsItem[];
}

export function NewsSection({ news }: NewsSectionProps) {
  const latest = news[0];

  return (
    <section className="home-section" aria-labelledby="news-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="news-title">お知らせ</h2>
        <Link className="section-head__link" to="/news">すべて見る</Link>
      </div>
      <div className="news-list">
        {latest ? (
          <Link className="news-row" to={`/news?announcement=${encodeURIComponent(latest.id)}`}>
            <div className="news-row__body">
              <div className="news-row__meta">
                <span className="news-row__category">{latest.category}</span>
                <span className="news-row__date">
                  <Calendar size={12} />
                  {formatDisplayDate(latest.date, "yyyy.MM.dd HH:mm", "日付未設定")}
                </span>
              </div>
              <div className="news-row__title">{latest.title}</div>
            </div>
            <ChevronRight size={16} aria-hidden="true" />
          </Link>
        ) : <p className="empty-copy">現在お知らせはありません。</p>}
      </div>
    </section>
  );
}
