import { ArrowRight } from "lucide-react";
import { Link } from "react-router-dom";
import type { HomeContent } from "../../types/app";

interface HeroBannerProps {
  title: string;
  subtitle: string;
  content: HomeContent;
  showImage: boolean;
  showStatus: boolean;
  showCta: boolean;
}

export function HeroBanner({ title, subtitle, content, showImage, showStatus, showCta }: HeroBannerProps) {
  return (
    <article className={`hero-banner${showImage ? "" : " hero-banner--no-image"}`}>
      <div className="hero-banner__media">
        {showImage ? <img className="hero-banner__image" src={content.heroImage} alt="" /> : null}
        <div className="hero-banner__shade" />
        {showStatus ? <div className="hero-banner__status">
          <span className="hero-banner__status-dot" />
          {content.statusLabel}
        </div> : null}
      </div>
      <div className="hero-banner__content">
        <div className="hero-banner__eyebrow">{content.heroEyebrow}</div>
        <h1 className="hero-banner__title">{title}</h1>
        <p className="hero-banner__subtitle">{subtitle}</p>
        {showCta ? <Link className="hero-banner__cta" to={`/${content.primaryActionFeature}`}>
          {content.primaryActionLabel}
          <ArrowRight size={17} aria-hidden="true" />
        </Link> : null}
      </div>
    </article>
  );
}
