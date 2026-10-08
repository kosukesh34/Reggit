import { useEffect, useRef, useState, type CSSProperties } from "react";
import { Link, useLocation } from "react-router-dom";
import { useAppConfig } from "../../context/AppConfigContext";
import { useRuntimeData } from "../../context/RuntimeDataContext";
import {
  nativeTabHref,
  nativeTabIconClass,
  useNativeConfig,
  type NativeTab,
} from "../../native/nativeConfig";

import { useNativeNavigation } from "../../native/NativeNavigation";

function validColor(value: string | undefined): string {
  return value !== undefined && /^#[0-9a-f]{6}$/i.test(value) ? value : "#ffffff";
}

function foregroundFor(background: string): string {
  const rgb = [1, 3, 5].map((index) => parseInt(background.slice(index, index + 2), 16) / 255);
  const luminance = rgb
    .map((channel) => channel <= 0.04045 ? channel / 12.92 : ((channel + 0.055) / 1.055) ** 2.4)
    .reduce((sum, channel, index) => sum + channel * [0.2126, 0.7152, 0.0722][index], 0);
  return luminance > 0.179 ? "#18211c" : "#ffffff";
}

function DrawerTab({ tab, onSelect }: { tab: NativeTab; onSelect: () => void }) {
  return (
    <a className="native-drawer__item" href={nativeTabHref(tab)} onClick={onSelect}>
      <i className={nativeTabIconClass(tab)} aria-hidden="true" />
      <span>{tab.label}</span>
      <i className="fa-solid fa-chevron-right" aria-hidden="true" />
    </a>
  );
}

export function Header() {
  const { activeStore } = useAppConfig();
  const { member } = useRuntimeData();
  const { back } = useNativeNavigation();
  const { config } = useNativeConfig();
  const location = useLocation();
  const [drawerOpen, setDrawerOpen] = useState(false);
  const [infoOpen, setInfoOpen] = useState(false);
  const closeButtonRef = useRef<HTMLButtonElement>(null);

  const background = validColor(config.header?.background_color);
  const foreground = /^#[0-9a-f]{6}$/i.test(config.native_chrome_colors?.header_icon ?? "") ? config.native_chrome_colors?.header_icon : foregroundFor(background);
  const projectName = config.header?.text?.trim()
    || config.project_name?.trim()
    || activeStore.storeName;
  const drawerTabs = config.navigation?.hamburger ?? config.tabs ?? [];
  const headerTabs = (config.navigation?.header ?? []).slice(0, 1);

  useEffect(() => {
    setDrawerOpen(false);
    setInfoOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (!drawerOpen && !infoOpen) return undefined;
    const onEscape = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        setDrawerOpen(false);
        setInfoOpen(false);
      }
    };
    window.addEventListener("keydown", onEscape);
    return () => window.removeEventListener("keydown", onEscape);
  }, [drawerOpen, infoOpen]);

  useEffect(() => {
    if (drawerOpen) closeButtonRef.current?.focus();
  }, [drawerOpen]);


  const headerStyle = {
    "--native-header-background": background,
    "--native-header-foreground": foreground,
  } as CSSProperties;

  return (
    <>
      <header className="native-header" style={headerStyle}>
        {back || drawerTabs.length ? <button
          className="native-header__action"
          type="button"
          aria-label={back ? "戻る" : "メニューを開く"}
          aria-controls="native-navigation-drawer"
          aria-expanded={drawerOpen}
          onClick={back ?? (() => setDrawerOpen(true))}
        >
          <i className={`fa-solid ${back ? "fa-chevron-left" : "fa-bars"}`} aria-hidden="true" />
        </button> : <span className="native-header__action" />}
        <div className="native-header__brand">
          {config.header?.logo_url || config.header?.image_url ? <img src={config.header.image_url || config.header.logo_url} alt={projectName} /> : <span>{projectName}</span>}
        </div>
        <div className="native-header__trailing">
          {headerTabs.map((tab) => (
            <a className="native-header__action" href={nativeTabHref(tab)} key={tab.key} aria-label={tab.label}>
              <i className={nativeTabIconClass(tab)} aria-hidden="true" />
            </a>
          ))}
        </div>
      </header>

      {drawerOpen ? (
        <div className="native-drawer-layer">
          <button type="button" className="native-drawer-layer__scrim" aria-label="メニューを閉じる" onClick={() => setDrawerOpen(false)} />
          <aside id="native-navigation-drawer" className="native-drawer" role="dialog" aria-modal="true" aria-label="メニュー">
            <div className="native-drawer__heading">
              <strong>メニュー</strong>
              <button ref={closeButtonRef} type="button" aria-label="閉じる" onClick={() => setDrawerOpen(false)}>
                <i className="fa-solid fa-xmark" aria-hidden="true" />
              </button>
            </div>
            <nav className="native-drawer__list" aria-label="すべてのメニュー">
              {drawerTabs.map((tab) => <DrawerTab key={tab.key} tab={tab} onSelect={() => setDrawerOpen(false)} />)}
              {(["gacha", "scratch", "roulette"] as const).map((game, index) => <Link className="native-drawer__item" key={game} to={`/designlab?preview=${game}`} onClick={() => setDrawerOpen(false)}><i className="fa-solid fa-dice" /><span>{["ガチャ", "スクラッチ", "ルーレット"][index]}（テスト）</span><i className="fa-solid fa-chevron-right" /></Link>)}
              <Link className="native-drawer__item" to="/more/link" onClick={() => setDrawerOpen(false)}><i className="fa-solid fa-link" /><span>既存会員連携</span><i className="fa-solid fa-chevron-right" /></Link>
              <Link className="native-drawer__item" to="/more/transfer" onClick={() => setDrawerOpen(false)}>
                <i className="fa-solid fa-right-left" aria-hidden="true" />
                <span>引き継ぎ</span><i className="fa-solid fa-chevron-right" aria-hidden="true" />
              </Link>
              {member.isMember ? (
                <Link className="native-drawer__item" to="/more/withdraw" onClick={() => setDrawerOpen(false)}>
                  <i className="fa-solid fa-user-xmark" aria-hidden="true" />
                  <span>退会</span><i className="fa-solid fa-chevron-right" aria-hidden="true" />
                </Link>
              ) : null}
              <button className="native-drawer__item" type="button" onClick={() => { setDrawerOpen(false); setInfoOpen(true); }}>
                <i className="fa-solid fa-circle-info" aria-hidden="true" />
                <span>アプリ情報</span><i className="fa-solid fa-chevron-right" aria-hidden="true" />
              </button>
            </nav>
          </aside>
        </div>
      ) : null}

      {infoOpen ? (
        <div className="native-info-layer" role="presentation" onClick={() => setInfoOpen(false)}>
          <section className="native-info" role="dialog" aria-modal="true" aria-label="アプリ情報" onClick={(event) => event.stopPropagation()}>
            <div className="native-info__heading">
              <strong>アプリ情報</strong>
              <button type="button" aria-label="閉じる" onClick={() => setInfoOpen(false)}><i className="fa-solid fa-xmark" aria-hidden="true" /></button>
            </div>
            <dl>
              <div><dt>店舗</dt><dd>{projectName}</dd></div>
              <div><dt>プロジェクト</dt><dd>{config.project_key || window.__PROJECT_KEY__ || "—"}</dd></div>
              {member.isMember && member.number !== "" ? <div><dt>会員番号</dt><dd>{member.number}</dd></div> : null}
            </dl>
          </section>
        </div>
      ) : null}
    </>
  );
}
