import { Calendar } from "lucide-react";
import type { NewsItem } from "../../types/app";
import { ChevronRight } from "lucide-react";
import { Link } from "react-router-dom";
import { formatDisplayDate } from "../../utils/date";

interface NewsSectionProps {
  news: NewsItem[];
}

export function NewsSection({ news }: NewsSectionProps) {
  return (
    <section className="home-section" aria-labelledby="news-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="news-title">お知らせ</h2>
        <Link className="section-head__link" to="/news">すべて見る</Link>
      </div>
      <div className="news-list">
        {news.map((item) => (
          <Link key={item.id} className="news-row" to="/news">
            <div className="news-row__body">
              <div className="news-row__meta">
                <span className="news-row__category">{item.category}</span>
                <span className="news-row__date">
                  <Calendar size={12} />
                  {formatDisplayDate(item.date, "yyyy.MM.dd", "日付未設定")}
                </span>
              </div>
              <div className="news-row__title">{item.title}</div>
            </div>
            <ChevronRight size={16} aria-hidden="true" />
          </Link>
        ))}
      </div>
    </section>
  );
}
