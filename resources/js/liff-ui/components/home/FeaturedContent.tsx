import { ArrowUpRight } from "lucide-react";
import { Link } from "react-router-dom";
import type { HomeContent } from "../../types/app";

interface FeaturedContentProps {
  content: HomeContent;
}

export function FeaturedContent({ content }: FeaturedContentProps) {
  return (
    <section className="home-section" aria-labelledby="featured-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="featured-title">今月のおすすめ</h2>
        <Link className="section-head__link" to={`/${content.featuredFeature}`}>すべて見る</Link>
      </div>
      <Link className="featured-content" to={`/${content.featuredFeature}`}>
        <img className="featured-content__image" src={content.featuredImage} alt="" loading="lazy" />
        <span className="featured-content__body">
          <span className="featured-content__eyebrow">おすすめ</span>
          <strong className="featured-content__title">{content.featuredTitle}</strong>
          <span className="featured-content__description">{content.featuredDescription}</span>
          <span className="featured-content__action">
            {content.featuredActionLabel}
            <ArrowUpRight size={15} aria-hidden="true" />
          </span>
        </span>
      </Link>
    </section>
  );
}
