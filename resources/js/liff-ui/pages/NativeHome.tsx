import { useCallback, useEffect, useMemo, useRef, useState, type TouchEvent } from "react";
import { createPortal } from "react-dom";
import { type HomeGalleryItem, nativeTabHref, nativeTabIconClass, useNativeConfig } from "../native/nativeConfig";
import "../styles/native-home.css";
import { useRuntimeData } from "../context/RuntimeDataContext";
import { MembershipBarcode } from "../components/membership/MembershipBarcode";
import { BarcodeEnlargeView } from "../components/membership/BarcodeEnlargeView";
import { CenterPopup } from "../components/ui/CenterPopup";

import { BottomSheet } from "../components/ui/BottomSheet";
import { NativeState, useNativeResource } from "../native/NativeResource";
import type { Announcement } from "../../frontend/features/announcements/api";

function HomeNews() {
  const resource = useNativeResource<{ announcements: Announcement[] }>("/api/announcements", { announcements: [] });
  return <section className="native-home__news"><h2>ニュース</h2><NativeState loading={resource.loading} error={resource.error} empty={!resource.data.announcements.length} title="お知らせはありません" reload={() => void resource.reload()}>
    {resource.data.announcements.slice(0, 3).map((item) => <a key={item.id} href={`#/news?announcement=${item.id}`}>{item.title}</a>)}
  </NativeState></section>;
}

function openGalleryUrl(item: HomeGalleryItem) {
  if (!item.url || !/^https:\/\//i.test(item.url)) return;
  const liff = window.liff as { isInClient?: () => boolean; openWindow?: (input: { url: string; external: boolean }) => void } | undefined;
  if (liff?.isInClient?.() && liff.openWindow) liff.openWindow({ url: item.url, external: item.mode === "external" });
  else if (item.mode === "external") window.open(item.url, "_blank", "noopener,noreferrer");
  else window.location.assign(item.url);
}

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
  const { member } = useRuntimeData();
  const [selectedGallery, setSelectedGallery] = useState<HomeGalleryItem>();
  const homeLayout = config.home?.layout ?? "legacy";
  const [showMemberCode, setShowMemberCode] = useState(false);
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
  const allMenu = (config?.tabs ?? []).filter((tab) => !["home", "terms", "privacy"].includes(tab.key));
  const menu = homeLayout === "buttons_gallery" ? allMenu.slice(0, 2) : allMenu;
  const layout = homeLayout === "buttons_gallery" ? "grid-2" : config?.home?.menu_layout === "list"
    ? "list"
    : config?.home?.menu_layout === "grid_2_columns" ? "grid-2" : "grid-3";
  const columns = layout === "grid-2" ? 2 : 3;
  const cellCount = Math.ceil(menu.length / columns) * columns;
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

  return <div className={`native-home native-home--${homeLayout}`} aria-busy={loading}>
    {homeLayout === "legacy" ? <div className="native-home__hero-frame">
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
    </div> : null}

    {config.home?.show_member_card !== false && member.isMember && member.number && member.number !== "未発行" ?
      <button type="button" className="native-home__member" onClick={() => setShowMemberCode(true)} aria-label="会員コードを拡大して表示">
        {config.home?.image_url ? <img className="native-home__member-header" src={imageUrl(config.home.image_url)} alt={`${projectName}の会員証ヘッダー`} /> : null}
        <div className="native-home__member-body">
        {member.codeType === "qrcode" ? <div className="native-code-fallback"><strong>{member.number}</strong><MembershipBarcode value={member.number} height={39} /></div> : <MembershipBarcode value={member.number} height={56} />}
        <span>タップして拡大</span>
        </div>
      </button> : null}
    <CenterPopup open={showMemberCode} onClose={() => setShowMemberCode(false)} title="会員コード">
      {member.codeType === "qrcode" ? <div className="native-code-fallback"><strong>{member.number}</strong><MembershipBarcode value={member.number} height={39} /></div> : <BarcodeEnlargeView value={member.number} />}
    </CenterPopup>

    {homeLayout !== "gallery" && (layout === "list" ? <nav className="native-home__menu native-home__menu--list" aria-label="ホームメニュー">
      {menu.map((tab) => {
        return <a key={tab.key} className="native-home__list-item" href={nativeTabHref(tab)}>
          <i className={nativeTabIconClass(tab)} aria-hidden="true" />
          <span className="native-home__list-label"><strong>{tab.label}</strong></span>
          <i className="fa-solid fa-chevron-right" aria-hidden="true" />
        </a>;
      })}
    </nav> : <nav className={`native-home__menu native-home__menu--${layout}`} aria-label="ホームメニュー">
      {Array.from({ length: cellCount }, (_, cell) => {
        const tab = menu[cell];
        const borders = `${cell % columns < columns - 1 ? " has-right-border" : ""}${cell < cellCount - columns ? " has-bottom-border" : ""}`;
        if (!tab) return <div key={`empty-${cell}`} className={`native-home__cell native-home__cell--empty${borders}`} aria-hidden="true" />;
        return <a key={tab.key} className={`native-home__cell${borders}`} href={nativeTabHref(tab)}>
          <i className={nativeTabIconClass(tab)} aria-hidden="true" /><span>{tab.label}</span>
        </a>;
      })}
    </nav>)}

    {homeLayout === "member_news" ? <HomeNews /> : null}
    {homeLayout === "gallery" || homeLayout === "buttons_gallery" ? <section className="native-home__gallery" aria-label="ギャラリー">
      {(config.home?.gallery_items ?? []).map((item) => <button type="button" key={item.id} onClick={() => item.mode === "detail" ? setSelectedGallery(item) : openGalleryUrl(item)}><img src={imageUrl(item.image_url)} alt={item.title || "ギャラリー画像"} loading="lazy" />{item.title ? <strong>{item.title}</strong> : null}</button>)}
      {!config.home?.gallery_items?.length ? <p>ギャラリー画像はありません</p> : null}
    </section> : null}
    <BottomSheet open={!!selectedGallery} onClose={() => setSelectedGallery(undefined)} title={selectedGallery?.title}><img className="native-gallery__detail" src={selectedGallery?.image_url} alt={selectedGallery?.title ?? ""} /><p className="native-body-text">{selectedGallery?.description}</p></BottomSheet>

    {expandedIndex !== null ? <HomeImageViewer images={images} initialIndex={expandedIndex}
      projectName={projectName} onClose={closeViewer} /> : null}
  </div>;
}
