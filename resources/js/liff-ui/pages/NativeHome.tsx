import { useCallback, useEffect, useMemo, useRef, useState, type TouchEvent } from "react";
import { createPortal } from "react-dom";
import { nativeTabHref, nativeTabIconClass, useNativeConfig } from "../native/nativeConfig";
import "../styles/native-home.css";

const FALLBACK_IMAGE = "/images/liff/skip-demo-store-hero.png";
const SWIPE_THRESHOLD = 45;

function imageUrl(value: string): string {
  const trimmed = value.trim();
  return /^(?:https?:|data:|blob:|\/\/)/i.test(trimmed)
    ? trimmed
    : `/${trimmed.replace(/^\/+/, "")}`;
}

function HomeImageViewer({ images, initialIndex, projectName, onClose }: {
  images: string[];
  initialIndex: number;
  projectName: string;
  onClose: () => void;
}) {
  const [index, setIndex] = useState(initialIndex);
  const closeRef = useRef<HTMLButtonElement>(null);
  const touchStartX = useRef<number | null>(null);
  const move = useCallback(
    (step: number) => setIndex((current) => (current + step + images.length) % images.length),
    [images.length],
  );

  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeRef.current?.focus();
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
      if (event.key === "ArrowLeft" && images.length > 1) move(-1);
      if (event.key === "ArrowRight" && images.length > 1) move(1);
    };
    document.addEventListener("keydown", onKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      document.removeEventListener("keydown", onKeyDown);
    };
  }, [images.length, move, onClose]);

  const onTouchStart = (event: TouchEvent) => { touchStartX.current = event.touches[0]?.clientX ?? null; };
  const onTouchEnd = (event: TouchEvent) => {
    const start = touchStartX.current;
    touchStartX.current = null;
    if (start === null || images.length < 2) return;
    const distance = (event.changedTouches[0]?.clientX ?? start) - start;
    if (Math.abs(distance) >= SWIPE_THRESHOLD) move(distance < 0 ? 1 : -1);
  };

  return createPortal(
    <div className="native-home-viewer" role="dialog" aria-modal="true" aria-label="ホーム画像を全画面で表示">
      <div className="native-home-viewer__image" onTouchStart={onTouchStart} onTouchEnd={onTouchEnd}>
        <img src={images[index]} alt={`${projectName}のホーム画像 ${index + 1}`} />
      </div>
      <button ref={closeRef} className="native-home-viewer__close" type="button" onClick={onClose} aria-label="閉じる">
        <i className="fa-solid fa-xmark" aria-hidden="true" />
      </button>
      {images.length > 1 ? <>
        <button className="native-home-viewer__previous" type="button" onClick={() => move(-1)} aria-label="前の画像">
          <i className="fa-solid fa-chevron-left" aria-hidden="true" />
        </button>
        <button className="native-home-viewer__next" type="button" onClick={() => move(1)} aria-label="次の画像">
          <i className="fa-solid fa-chevron-right" aria-hidden="true" />
        </button>
        <div className="native-home-viewer__count" aria-live="polite">{index + 1} / {images.length}</div>
      </> : null}
    </div>, document.body,
  );
}

export function NativeHome() {
  const { config, loading } = useNativeConfig();
  const [index, setIndex] = useState(0);
  const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
  const heroRef = useRef<HTMLButtonElement>(null);
  const touchStartX = useRef<number | null>(null);
  const ignoreNextClick = useRef(false);

  const images = useMemo(() => {
    const configured = config?.home?.image_urls?.length
      ? config.home.image_urls
      : (config?.home?.image_url ? [config.home.image_url] : []);
    const cleaned = configured.map(imageUrl).filter((url) => url !== "/");
    return cleaned.length > 0 ? cleaned : [FALLBACK_IMAGE];
  }, [config?.home?.image_url, config?.home?.image_urls]);
  const imageKey = images.join("|");
  const menu = (config?.tabs ?? []).filter((tab) => tab.key !== "home").slice(0, 6);
  const layout = config?.home?.menu_layout === "list"
    ? "list"
    : config?.home?.menu_layout === "grid_2_columns" ? "grid-2" : "grid-3";
  const columns = layout === "grid-2" ? 2 : 3;
  const selectedIndex = Math.min(index, images.length - 1);
  const projectName = config?.project_name?.trim() || "店舗";

  useEffect(() => { setIndex(0); }, [imageKey]);
  useEffect(() => {
    if (images.length < 2 || expandedIndex !== null) return undefined;
    const timer = window.setInterval(() => setIndex((current) => (current + 1) % images.length), 5000);
    return () => window.clearInterval(timer);
  }, [expandedIndex, images.length, imageKey]);

  const closeViewer = () => {
    setExpandedIndex(null);
    window.requestAnimationFrame(() => heroRef.current?.focus());
  };
  const onHeroTouchStart = (event: TouchEvent) => { touchStartX.current = event.touches[0]?.clientX ?? null; };
  const onHeroTouchEnd = (event: TouchEvent) => {
    const start = touchStartX.current;
    touchStartX.current = null;
    if (start === null || images.length < 2) return;
    const distance = (event.changedTouches[0]?.clientX ?? start) - start;
    if (Math.abs(distance) >= SWIPE_THRESHOLD) {
      ignoreNextClick.current = true;
      setIndex((current) => (current + (distance < 0 ? 1 : images.length - 1)) % images.length);
    }
  };

  return <div className="native-home" aria-busy={loading}>
    <div className="native-home__hero-frame">
      <button ref={heroRef} type="button" className="native-home__hero"
        onClick={() => {
          if (ignoreNextClick.current) { ignoreNextClick.current = false; return; }
          setExpandedIndex(selectedIndex);
        }}
        onTouchStart={onHeroTouchStart} onTouchEnd={onHeroTouchEnd} aria-label="画像を全画面で表示">
        <img src={images[selectedIndex]} alt={`${projectName}のホーム画像`} />
      </button>
      {images.length > 1 ? <div className="native-home__pages" aria-label="ホーム画像">
        {images.map((_, page) => <button key={page} type="button"
          className={`native-home__page${page === selectedIndex ? " is-active" : ""}`}
          onClick={() => setIndex(page)} aria-label={`${page + 1}枚目の画像`}
          aria-current={page === selectedIndex ? "true" : undefined} />)}
      </div> : null}
    </div>

    {layout === "list" ? <nav className="native-home__menu native-home__menu--list" aria-label="ホームメニュー">
      {menu.map((tab) => {
        return <a key={tab.key} className="native-home__list-item" href={nativeTabHref(tab)}>
          <i className={nativeTabIconClass(tab)} aria-hidden="true" />
          <span className="native-home__list-label"><strong>{tab.label}</strong><small>{tab.key}</small></span>
          <i className="fa-solid fa-chevron-right" aria-hidden="true" />
        </a>;
      })}
    </nav> : <nav className={`native-home__menu native-home__menu--${layout}`} aria-label="ホームメニュー">
      {Array.from({ length: 6 }, (_, cell) => {
        const tab = menu[cell];
        const borders = `${cell % columns < columns - 1 ? " has-right-border" : ""}${cell < 6 - columns ? " has-bottom-border" : ""}`;
        if (!tab) return <div key={`empty-${cell}`} className={`native-home__cell native-home__cell--empty${borders}`} aria-hidden="true" />;
        return <a key={tab.key} className={`native-home__cell${borders}`} href={nativeTabHref(tab)}>
          <i className={nativeTabIconClass(tab)} aria-hidden="true" /><span>{tab.label}</span>
        </a>;
      })}
    </nav>}

    {expandedIndex !== null ? <HomeImageViewer images={images} initialIndex={expandedIndex}
      projectName={projectName} onClose={closeViewer} /> : null}
  </div>;
}
