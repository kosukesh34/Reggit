import { ChevronRight, MapPin, Navigation } from "lucide-react";
import { Link } from "react-router-dom";
import type { StoreSummary } from "../../types/app";

interface StoreSummaryProps {
  summary: StoreSummary;
}

export function StoreSummaryCard({ summary }: StoreSummaryProps) {
  return (
    <section className="home-section home-section--last" aria-labelledby="store-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="store-title">よく行く店舗</h2>
        <Link className="section-head__link" to="/storeSearch">店舗を探す</Link>
      </div>
      <Link className="store-summary" to="/storeSearch">
        <span className="store-summary__icon"><MapPin size={20} aria-hidden="true" /></span>
        <span className="store-summary__body">
          <strong>{summary.name}</strong>
          <span>{summary.openHours} · {summary.access}</span>
          <span className="store-summary__status"><span /> 営業中</span>
        </span>
        <span className="store-summary__route">
          <Navigation size={15} aria-hidden="true" />
          経路
        </span>
        <ChevronRight size={16} aria-hidden="true" />
      </Link>
      <div className="store-summary__address">
        <span>{summary.address}</span>
        <span>{summary.phone}</span>
      </div>
    </section>
  );
}
