import type { JSX } from 'react';
import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { fetchGallery, type GalleryItem } from "../../../frontend/features/gallery/api";

function hasImage(item: GalleryItem): boolean {
  return item.image_url !== undefined && item.image_url !== "";
}

function galleryAdminHref(): string {
  return `/admin/${encodeURIComponent(String(window.__PROJECT_KEY__ || "default"))}/gallery/items`;
}

function DesignerEmpty(): JSX.Element {
  return (
    <section className="home-section" aria-labelledby="gallery-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="gallery-title">おすすめ商品</h2>
      </div>
      <div className="home-gallery-empty">
        <p>店舗の写真を追加すると、ホームのおすすめに並びます。</p>
        <a className="button button--outline button--md" href={galleryAdminHref()} target="_top" rel="noreferrer">
          写真を追加
        </a>
      </div>
    </section>
  );
}

export function HomeGallery() {
  const [items, setItems] = useState<GalleryItem[] | null>(null);
  const isDesignerPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true;

  useEffect(() => {
    let cancelled = false;
    void fetchGallery(null).then((data) => {
      if (!cancelled) {
        setItems(data.items.filter(hasImage).slice(0, 4));
      }
    }).catch(() => {
      if (!cancelled) {
        setItems([]);
      }
    });
    return () => {
      cancelled = true;
    };
  }, []);

  if (items === null) {
    return null;
  }
  if (items.length === 0) {
    return isDesignerPreview ? <DesignerEmpty /> : null;
  }

  return (
    <section className="home-section" aria-labelledby="gallery-title">
      <div className="section-head section-head--plain">
        <h2 className="section-head__title" id="gallery-title">おすすめ商品</h2>
        <Link className="section-head__link" to="/gallery">すべて見る</Link>
      </div>
      <div className="home-gallery">
        {items.map((item) => (
          <Link className="home-gallery__item" to="/gallery" key={String(item.id)}>
            <img src={item.image_url} alt="" loading="lazy" />
            {item.title !== undefined && item.title !== "" ? <strong>{item.title}</strong> : null}
            {item.description !== undefined && item.description !== "" ? <span>{item.description}</span> : null}
          </Link>
        ))}
      </div>
    </section>
  );
}
