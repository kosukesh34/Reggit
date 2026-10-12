import {
  ArrowDown,
  ArrowLeft,
  ArrowUp,
  Check,
  Eye,
  ExternalLink,
  LayoutGrid,
  Palette,
  Plus,
  RotateCcw,
  Settings2,
  SlidersHorizontal,
  Trash2,
  X,
  ChevronDown,
} from "lucide-react";
import { useCallback, useEffect, useRef, useState, type CSSProperties, type ReactNode } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { appConfig } from "../config/appConfig";
import { designerTemplates, studioDesignerTemplates, templatesByGroup } from "../config/appTemplates";
import { designPresets } from "../config/designPresets";
import { appTemplatePatch, designPresetLayoutPatch, resolveAppBrand, resolveHomeLayout } from "../config/customization";
import { tabLabelMap, tabOptions } from "../config/navigation";
import { useAppConfig } from "../context/AppConfigContext";
import { themeMap } from "../themes/themes";
import { isLinkTabType, type FeatureType, type TabType } from "../types/app";
import type { AppCustomization, HeaderButtonIcon, HeaderButtonMode, HeaderRightButton, HeaderShortcut, HomeLayout } from "../types/customization";
import { resolveCustomizationColors } from "../utils/customization";

type DesignerSaveState = "saved" | "unsaved" | "saving" | "error";

interface AdminCustomizePageProps {
  previewUrl?: string;
  showMemberAppLink?: boolean;
  embedded?: boolean;
  saveState?: DesignerSaveState;
}

const sectionLinks = [
  { id: "design", label: "デザイン", icon: Palette },
  { id: "layout", label: "レイアウト", icon: LayoutGrid },
  { id: "features", label: "機能設定", icon: SlidersHorizontal },
  { id: "navigation", label: "ナビゲーション", icon: Settings2 },
] as const;

const ADVANCED_STORAGE_KEY = "reggit-designer-advanced";

const headerShortcutOptions: Array<{
  value: HeaderShortcut;
  label: string;
  feature?: FeatureType;
}> = [
  { value: "news", label: "お知らせ", feature: "news" },
  { value: "membership", label: "会員証", feature: "membership" },
  { value: "profile", label: "プロフィール", feature: "membership" },
  { value: "coupon", label: "クーポン", feature: "coupon" },
  { value: "stamp", label: "スタンプ", feature: "stamp" },
  { value: "reservation", label: "予約", feature: "reservation" },
  { value: "queue", label: "順番待ち", feature: "queue" },
  { value: "mobileOrder", label: "モバイルオーダー", feature: "mobileOrder" },
  { value: "none", label: "表示しない" },
];

const headerButtonModeOptions: Array<{ value: HeaderButtonMode; label: string }> = [
  { value: "shortcut", label: "既存ショートカット" },
  { value: "custom", label: "カスタムボタン" },
  { value: "none", label: "表示しない" },
];

const headerButtonIconOptions: Array<{ value: HeaderButtonIcon; label: string }> = [
  { value: "bell", label: "お知らせ" },
  { value: "userRound", label: "プロフィール" },
  { value: "creditCard", label: "会員証" },
  { value: "ticket", label: "クーポン" },
  { value: "stamp", label: "スタンプ" },
  { value: "calendarDays", label: "予約" },
  { value: "qrCode", label: "順番待ち" },
  { value: "shoppingBag", label: "モバイル注文" },
  { value: "menu", label: "メニュー" },
  { value: "home", label: "ホーム" },
  { value: "moreHorizontal", label: "その他" },
];

const saveStateLabels: Record<DesignerSaveState, string> = {
  saved: "保存済み",
  unsaved: "未保存の変更あり",
  saving: "保存中…",
  error: "保存できませんでした",
};

const previewPathByTab: Record<TabType, string> = {
  home: "/home",
  membership: "/membership",
  profile: "/profile",
  mobileOrder: "/mobileOrder",
  dineInOrder: "/dineInOrder",
  reservation: "/reservation",
  queue: "/queue",
  coupon: "/coupon",
  stamp: "/stamp",
  point: "/point",
  menu: "/menu",
  store: "/storeSearch",
  news: "/news",
  more: "/more",
  link_1: "/link_1",
  link_2: "/link_2",
  link_3: "/link_3",
};

function buildEmbeddedPreviewUrl(baseUrl: string): string {
  const separator = baseUrl.includes("?") ? "&" : "?";
  return `${baseUrl}${separator}designer_live_preview=1&designer_embed=1#/home`;
}

export function AdminCustomizePage({
  previewUrl,
  showMemberAppLink = true,
  embedded = false,
  saveState = "saved",
}: AdminCustomizePageProps) {
  const { activeStore, stores, setActiveStoreId, activeThemeId, customization, experienceId, updateCustomization, resetCustomization } = useAppConfig();
  const activeTheme = themeMap[customization.themeId ?? ""] ?? themeMap[activeThemeId] ?? themeMap[activeStore.themeId] ?? themeMap["minimal-light"];
  const resolvedColors = resolveCustomizationColors(customization, activeTheme);
  const appPreviewHref = previewUrl ?? '/home';
  const [activeSection, setActiveSection] = useState<(typeof sectionLinks)[number]["id"]>("design");
  const [isPreviewOpen, setPreviewOpen] = useState(false);
  const [previewTab, setPreviewTab] = useState<TabType>(customization.navigation.tabs[0] ?? "home");
  const previewFrameRef = useRef<HTMLIFrameElement>(null);
  const embeddedPreviewUrl = buildEmbeddedPreviewUrl(appPreviewHref);
  const brand = resolveAppBrand(activeStore, customization);
  const projectKey = String(window.__PROJECT_KEY__ || "default");
  const galleryItemsHref = `/admin/${encodeURIComponent(projectKey)}/gallery/items`;
  const [isAdvanced, setIsAdvanced] = useState(() => {
    try {
      return window.localStorage.getItem(ADVANCED_STORAGE_KEY) === "1";
    } catch {
      return false;
    }
  });
  const setAdvancedMode = (next: boolean) => {
    setIsAdvanced(next);
    try {
      window.localStorage.setItem(ADVANCED_STORAGE_KEY, next ? "1" : "0");
    } catch {
      // ignore
    }
  };

  const sendCustomizationToPreview = useCallback(() => {
    previewFrameRef.current?.contentWindow?.postMessage({
      type: "reggit:designer-preview:update",
      customization,
    }, window.location.origin);
  }, [customization]);

  const sendPreviewNavigate = useCallback((tab: TabType) => {
    previewFrameRef.current?.contentWindow?.postMessage({
      type: "reggit:designer-preview:navigate",
      path: previewPathByTab[tab],
    }, window.location.origin);
  }, []);

  useEffect(() => {
    const hash = window.location.hash.replace(/^#/, "");
    if (hash === "") {
      return;
    }
    const featureHashes = new Set(["membership", "coupon", "stamp", "order"]);
    if (featureHashes.has(hash)) {
      setActiveSection("features");
    } else if (sectionLinks.some((section) => section.id === hash)) {
      setActiveSection(hash as (typeof sectionLinks)[number]["id"]);
    }
    window.requestAnimationFrame(() => {
      document.getElementById(hash)?.scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }, []);

  useEffect(() => {
    const sections = sectionLinks
      .map(({ id }) => document.getElementById(id))
      .filter((section): section is HTMLElement => section !== null);
    const observer = new IntersectionObserver((entries) => {
      const visible = entries
        .filter((entry) => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (visible?.target.id) setActiveSection(visible.target.id as (typeof sectionLinks)[number]["id"]);
    }, { rootMargin: "-18% 0px -62%", threshold: [0, 0.2, 0.6] });
    sections.forEach((section) => observer.observe(section));
    return () => observer.disconnect();
  }, []);

  useEffect(() => {
    if (!customization.navigation.tabs.includes(previewTab)) {
      setPreviewTab(customization.navigation.tabs[0] ?? "home");
    }
  }, [customization.navigation.tabs, previewTab]);

  useEffect(() => {
    sendCustomizationToPreview();
  }, [sendCustomizationToPreview]);

  useEffect(() => {
    sendPreviewNavigate(previewTab);
  }, [previewTab, sendPreviewNavigate]);

  useEffect(() => {
    const handlePreviewReady = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.data?.type !== "reggit:designer-preview:ready") {
        return;
      }
      sendCustomizationToPreview();
      sendPreviewNavigate(previewTab);
    };
    window.addEventListener("message", handlePreviewReady);
    return () => window.removeEventListener("message", handlePreviewReady);
  }, [previewTab, sendCustomizationToPreview, sendPreviewNavigate]);

  const updateHeaderRightButton = useCallback((patch: Partial<HeaderRightButton>) => {
    const next = { ...customization.header.rightButton, ...patch };
    updateCustomization({
      header: {
        rightButton: next,
        showNotifications: next.mode !== "none",
        shortcut: next.mode === "shortcut" ? next.shortcut : customization.header.shortcut,
      },
    });
  }, [customization.header.rightButton, customization.header.shortcut, updateCustomization]);

  const isStandardHome = experienceId === "standard";
  const templateGroups = templatesByGroup(isAdvanced ? designerTemplates(appConfig.stores) : studioDesignerTemplates(appConfig.stores));
  const studioPresetOrder = ["luxury", "minimal", "editorial", "natural"] as const;
  const visiblePresets = isAdvanced
    ? designPresets
    : studioPresetOrder
      .map((id) => designPresets.find((item) => item.id === id))
      .filter((item): item is (typeof designPresets)[number] => item !== undefined);
  const selectedTemplateId = customization.appTemplateId;

  const requestReset = () => {
    if (window.confirm(`「${activeStore.storeName}」のデザイン設定を初期状態に戻しますか？\n保存するまでは店舗アプリに反映されません。`)) {
      resetCustomization();
    }
  };

  return (
    <div className={`admin-shell${embedded ? " admin-shell--embedded" : ""}`}>
      {!embedded ? <header className="admin-header">
        <div className="admin-header__brand"><span>R</span><div><strong>Reggit 店舗アプリ管理</strong><small>店舗運用・会員施策</small></div></div>
        <div className="admin-header__actions">
          <Button variant="outline" onClick={requestReset}><RotateCcw size={16} />この店舗を初期設定へ戻す</Button>
          {previewUrl !== undefined ? (
            <a className="button button--solid button--md" href={appPreviewHref} target="_blank" rel="noreferrer">アプリを確認 <ExternalLink size={16} /></a>
          ) : (
            <Link className="button button--solid button--md" to="/home">アプリを確認 <ExternalLink size={16} /></Link>
          )}
        </div>
      </header> : null}

      <aside className="admin-sidebar">
        {showMemberAppLink ? <Link className="web-back" to="/more"><ArrowLeft size={18} aria-hidden="true" />会員アプリへ戻る</Link> : null}
        <nav aria-label="デザイン設定のセクション">
          {sectionLinks.map(({ id, label, icon: Icon }) => (
            <a href={`#${id}`} className={activeSection === id ? "is-active" : undefined} aria-current={activeSection === id ? "location" : undefined} onClick={() => setActiveSection(id)} key={id}>
              <Icon size={17} />{label}
            </a>
          ))}
        </nav>
        {embedded ? (
          <div className="admin-sidebar__store" aria-label={`編集中の店舗：${activeStore.storeName}`}>
            <span>編集中の店舗</span>
            <strong>{activeStore.storeName}</strong>
            <small>変更後は上部の保存ボタンで反映</small>
          </div>
        ) : (
          <label className="admin-sidebar__store">
            <span>編集中の店舗</span>
            <select value={activeStore.storeId} onChange={(event) => setActiveStoreId(event.target.value)}>
              {stores.map((store) => <option value={store.storeId} key={store.storeId}>{store.storeName}</option>)}
            </select>
            <small>店舗ごとに設定・端末へ一時保存されます</small>
          </label>
        )}
      </aside>

      <main className="admin-main">
        <div className="admin-main__heading">
          <div>
            <span>店舗アプリ設定</span>
            <h1>{activeStore.storeName}</h1>
            <p>{isAdvanced ? "テンプレート・配色・レイアウトを細かく調整できます。" : "こだわらない場合は配色はそのままで使えます。写真を追加するとホームのおすすめに並びます。"}</p>
          </div>
          <div className="admin-main__status">
            <label className="admin-mode-toggle">
              <input type="checkbox" checked={isAdvanced} onChange={(event) => setAdvancedMode(event.target.checked)} />
              <span>こだわり設定</span>
            </label>
            <div className={`admin-save-state is-${saveState}`} aria-live="polite"><Check size={15} />{saveStateLabels[saveState]}</div>
            <button type="button" className="admin-preview-toggle" aria-expanded={isPreviewOpen} onClick={() => setPreviewOpen(true)}><Eye size={16} />プレビュー</button>
          </div>
        </div>

        <section className="admin-section" id="design">
          <SectionHead number="01" title="アプリテンプレート" description="会員アプリの基本デザインを選びます。" />
          {templateGroups.map((group) => (
            <div className="template-group" key={group.group}>
              <p className="template-group__title">{group.label}</p>
              <div className="preset-grid" aria-label={`${group.label}テンプレートを選択`}>
                {group.items.map((item) => {
                  const selected = selectedTemplateId === item.store.storeId;
                  const heroImage = item.store.home.heroImage;
                  const isPhoto = heroImage.startsWith("/images/") || heroImage.startsWith("/assets/");
                  return (
                    <button
                      type="button"
                      className={`preset-card${selected ? " is-active" : ""}`}
                      key={item.store.storeId}
                      title={item.description}
                      aria-label={`${item.store.storeName}：${item.description}`}
                      aria-pressed={selected}
                      onClick={() => updateCustomization(appTemplatePatch(item.store))}
                    >
                      <span
                        className={`tpl-thumb${isPhoto ? " tpl-thumb--photo" : ` tpl-thumb--${item.thumb}`}`}
                        style={isPhoto
                          ? { backgroundImage: `linear-gradient(180deg, transparent, rgba(0,0,0,.55)), url("${heroImage}")` }
                          : { "--preset-a": item.palette[0], "--preset-b": item.palette[1], "--preset-c": item.palette[2] } as CSSProperties}
                        aria-hidden="true"
                      >
                        {isPhoto ? <span className="tpl-thumb__label">{item.store.storeName}</span> : (
                          <>
                            <span className="tpl-thumb__hero" />
                            <span className="tpl-thumb__row" />
                            <span className="tpl-thumb__row" />
                            <span className="tpl-thumb__row" />
                            <span className="tpl-thumb__cta" />
                          </>
                        )}
                      </span>
                      <span className="preset-card__body">
                        <strong>{isPhoto ? item.store.brandName : item.store.storeName}</strong>
                        <small>{item.description}</small>
                      </span>
                      {selected ? <span className="preset-card__check"><Check size={10} /></span> : null}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}

          <p className="template-group__title">配色</p>
          <div className="preset-grid preset-grid--compact" aria-label="配色を選択">
            {visiblePresets.map((item) => (
              <button type="button" className={`preset-card preset-card--${item.id}${customization.designPreset === item.id ? " is-active" : ""}`} key={item.id} title={item.description} aria-label={`${item.name}：${item.description}`} aria-pressed={customization.designPreset === item.id} onClick={() => updateCustomization(designPresetLayoutPatch(item.id))}>
                <span className="preset-card__visual" style={{ "--preset-a": item.palette[0], "--preset-b": item.palette[1], "--preset-c": item.palette[2] } as CSSProperties}><span /><span /><span /></span>
                <span className="preset-card__body"><strong>{item.name}</strong></span>
                {customization.designPreset === item.id ? <span className="preset-card__check"><Check size={10} /></span> : null}
              </button>
            ))}
          </div>

          {isAdvanced ? (
          <div className="admin-panel">
            <div className="admin-panel__title"><h3>カスタムカラー</h3><p>カラーピッカーまたはHEXコードで、画面の各色を自由に上書きします。</p></div>
            <div className="color-grid">
              <ColorField label="メイン" value={resolvedColors.primary} onChange={(primary) => updateCustomization({ colors: { primary } })} />
              <ColorField label="サブ" value={resolvedColors.secondary} onChange={(secondary) => updateCustomization({ colors: { secondary } })} />
              <ColorField label="アクセント" value={resolvedColors.accent} onChange={(accent) => updateCustomization({ colors: { accent } })} />
              <ColorField label="背景" value={resolvedColors.background} onChange={(background) => updateCustomization({ colors: { background } })} />
              <ColorField label="カード" value={resolvedColors.surface} onChange={(surface) => updateCustomization({ colors: { surface } })} />
              <ColorField label="本文" value={resolvedColors.textPrimary} onChange={(textPrimary) => updateCustomization({ colors: { textPrimary } })} />
              <ColorField label="補助テキスト" value={resolvedColors.textSecondary} onChange={(textSecondary) => updateCustomization({ colors: { textSecondary } })} />
            </div>
            <Button variant="ghost" size="sm" onClick={() => updateCustomization({ colors: { primary: undefined, secondary: undefined, accent: undefined, background: undefined, surface: undefined, textPrimary: undefined, textSecondary: undefined } })}>プリセットの色に戻す</Button>
          </div>
          ) : null}

          <div className="admin-panel">
            <div className="admin-panel__title"><h3>ブランド</h3><p>{isAdvanced ? "アプリ名・画像・角丸はすべてのテンプレートで編集できます。" : "アプリ名を変更できます。"}</p></div>
            <label className="admin-select">
              <span>アプリ名</span>
              <input
                type="text"
                value={brand.appName}
                onChange={(event) => updateCustomization({ brand: { appName: event.target.value } })}
              />
            </label>
            {isAdvanced ? (
              <>
            <label className="admin-select">
              <span>メイン画像</span>
              <input
                type="text"
                value={brand.heroImage}
                onChange={(event) => updateCustomization({ brand: { heroImage: event.target.value } })}
              />
            </label>
            <label className="admin-select">
              <span>角丸 {customization.brand?.radius ?? 18}px</span>
              <input
                type="range"
                min="0"
                max="30"
                value={customization.brand?.radius ?? 18}
                onChange={(event) => updateCustomization({ brand: { radius: Number(event.target.value) } })}
              />
            </label>
              </>
            ) : null}
          </div>
        </section>

        <section className="admin-section" id="layout">
          <SectionHead number="02" title="ホームの表示" description={isAdvanced ? "情報量、並び、ホームに出すブロックを調整できます。" : "ホームに出すブロックを切り替えます。"} />
          <div className="admin-settings-grid">
            {isAdvanced && isStandardHome ? (
              <SettingsGroup title="ホームのレイアウト" description="ホーム画面の情報の並びを選びます" defaultOpen>
                <HomeLayoutPicker value={resolveHomeLayout(customization.home.layout)} onChange={(layout) => updateCustomization({ home: { layout } })} />
              </SettingsGroup>
            ) : null}
            {isAdvanced && isStandardHome ? (
              <SettingsGroup title="画面スタイル" description="全体の密度とボタン表現">
                <SelectRow label="コンテンツ密度" value={customization.appearance.density} onChange={(density) => updateCustomization({ appearance: { density: density as AppCustomization["appearance"]["density"] } })}><option value="compact">コンパクト</option><option value="standard">標準</option><option value="relaxed">ゆったり</option></SelectRow>
                <SelectRow label="ボタンスタイル" value={customization.appearance.buttonStyle} onChange={(buttonStyle) => updateCustomization({ appearance: { buttonStyle: buttonStyle as AppCustomization["appearance"]["buttonStyle"] } })}><option value="filled">塗り</option><option value="soft">ソフト</option><option value="outline">アウトライン</option></SelectRow>
                <SelectRow label="サービスボタン列数" value={String(customization.home.quickActionColumns)} onChange={(quickActionColumns) => updateCustomization({ home: { quickActionColumns: Number(quickActionColumns) as 2 | 3 | 4 } })}><option value="2">2列</option><option value="3">3列</option><option value="4">4列</option></SelectRow>
              </SettingsGroup>
            ) : null}
            {isStandardHome ? (
              <SettingsGroup title="ホームの表示内容" description="不要なセクションを非表示" defaultOpen>
                <ToggleRow label="会員カード" checked={customization.home.showMembership} onChange={(showMembership) => updateCustomization({ home: { showMembership } })} />
                <ToggleRow label="サービスボタン" checked={customization.home.showQuickActions} onChange={(showQuickActions) => updateCustomization({ home: { showQuickActions } })} />
                <ToggleRow label="おすすめ商品" checked={customization.home.showGallery} onChange={(showGallery) => updateCustomization({ home: { showGallery } })} />
                <ToggleRow label="お知らせ" checked={customization.home.showNews} onChange={(showNews) => updateCustomization({ home: { showNews } })} />
                {isAdvanced ? (
                  <>
                    <ToggleRow label="メイン画像" checked={customization.home.showHeroImage} onChange={(showHeroImage) => updateCustomization({ home: { showHeroImage } })} />
                    <ToggleRow label="営業状況" checked={customization.home.showHeroStatus} onChange={(showHeroStatus) => updateCustomization({ home: { showHeroStatus } })} />
                    <ToggleRow label="クーポン" checked={customization.home.showCoupons} onChange={(showCoupons) => updateCustomization({ home: { showCoupons } })} />
                    <ToggleRow label="店舗情報" checked={customization.home.showStoreInfo} onChange={(showStoreInfo) => updateCustomization({ home: { showStoreInfo } })} />
                  </>
                ) : null}
              </SettingsGroup>
            ) : (
              <SettingsGroup title="ホームの表示内容" description="このテンプレートでは構成が固定です" defaultOpen>
                <p className="admin-panel__note">シンプル構成のテンプレートでは、ホームのブロックはテンプレート側で決まっています。</p>
              </SettingsGroup>
            )}
            <SettingsGroup title="おすすめ商品の写真" description="店舗で使う写真だけを登録します" defaultOpen>
              <p className="admin-panel__note">追加した写真がホームのおすすめ商品とギャラリー画面に表示されます。テンプレートのサンプル写真は使いません。</p>
              <a className="button button--outline button--md" href={galleryItemsHref}>写真を追加・編集</a>
            </SettingsGroup>
          </div>
        </section>

        <section className="admin-section" id="features">
          <SectionHead number="03" title="機能ごとの表示設定" description="クーポン・会員証・スタンプの表示を調整します。" />
          <div className="admin-settings-grid">
            <div id="coupon" className="admin-settings-anchor">
            <SettingsGroup title="クーポン" description="利用済み・期限切れは「利用終了」に統一" defaultOpen>
              <ToggleRow label="利用可能・利用終了タブ" checked={customization.coupon.showStatusTabs} onChange={(showStatusTabs) => updateCustomization({ coupon: { showStatusTabs } })} />
              <ToggleRow label="利用開始前も表示" checked={customization.coupon.showBeforeAvailable} onChange={(showBeforeAvailable) => updateCustomization({ coupon: { showBeforeAvailable } })} />
              <ToggleRow label="クーポン画像" checked={customization.coupon.showImage} onChange={(showImage) => updateCustomization({ coupon: { showImage } })} />
              <ToggleRow label="説明文" checked={customization.coupon.showDescription} onChange={(showDescription) => updateCustomization({ coupon: { showDescription } })} />
              <ToggleRow label="状態ラベル" checked={customization.coupon.showStatusBadge} onChange={(showStatusBadge) => updateCustomization({ coupon: { showStatusBadge } })} />
              <ToggleRow label="有効期限" checked={customization.coupon.showExpiryDate} onChange={(showExpiryDate) => updateCustomization({ coupon: { showExpiryDate } })} />
              <ToggleRow label="利用前の確認画面" checked={customization.coupon.requireConfirmation} onChange={(requireConfirmation) => updateCustomization({ coupon: { requireConfirmation } })} />
              <SelectRow label="初期タブ" value={customization.coupon.defaultStatus} onChange={(defaultStatus) => updateCustomization({ coupon: { defaultStatus: defaultStatus as AppCustomization["coupon"]["defaultStatus"] } })}><option value="available">利用可能</option><option value="ended">利用終了</option></SelectRow>
              <SelectRow label="カード表示" value={customization.coupon.layout} onChange={(layout) => updateCustomization({ coupon: { layout: layout as AppCustomization["coupon"]["layout"] } })}><option value="cards">カード</option><option value="compact">コンパクトリスト</option></SelectRow>
            </SettingsGroup>
            </div>

            <SettingsGroup title="ヘッダー" description="右上ボタンの表示">
              <ToggleRow label="店舗ロゴ" checked={customization.header.showLogo} onChange={(showLogo) => updateCustomization({ header: { showLogo } })} />
              {isAdvanced ? (
                <>
                  <ToggleRow label="会員アプリ表記" checked={customization.header.showSubtitle} onChange={(showSubtitle) => updateCustomization({ header: { showSubtitle } })} />
                  <SelectRow
                    label="右上ボタンの種類"
                    value={customization.header.rightButton.mode}
                    onChange={(value) => {
                      const mode = value as HeaderButtonMode;
                      if (mode === "shortcut") {
                        updateHeaderRightButton({ mode, shortcut: customization.header.rightButton.shortcut });
                        return;
                      }
                      updateHeaderRightButton({ mode });
                    }}
                  >
                    {headerButtonModeOptions.map((option) => (
                      <option value={option.value} key={option.value}>{option.label}</option>
                    ))}
                  </SelectRow>
                  {customization.header.rightButton.mode === "shortcut" ? (
                    <SelectRow
                      label="右上ショートカット"
                      value={customization.header.rightButton.shortcut}
                      onChange={(value) => updateHeaderRightButton({ shortcut: value as Exclude<HeaderShortcut, "none"> })}
                    >
                      {headerShortcutOptions.filter((option) => option.value !== "none").map((option) => (
                        <option value={option.value} disabled={option.feature !== undefined && !activeStore.features[option.feature]} key={option.value}>
                          {option.label}{option.feature !== undefined && !activeStore.features[option.feature] ? "（機能OFF）" : ""}
                        </option>
                      ))}
                    </SelectRow>
                  ) : null}
                  {customization.header.rightButton.mode === "custom" ? (
                    <>
                      <label className="admin-select">
                        <span>ボタンラベル</span>
                        <input type="text" value={customization.header.rightButton.label} placeholder="プロフィール" onChange={(event) => updateHeaderRightButton({ label: event.target.value })} />
                      </label>
                      <label className="admin-select">
                        <span>遷移先パス</span>
                        <input type="text" value={customization.header.rightButton.path} placeholder="/profile" onChange={(event) => updateHeaderRightButton({ path: event.target.value })} />
                      </label>
                      <SelectRow label="アイコン" value={customization.header.rightButton.icon} onChange={(icon) => updateHeaderRightButton({ icon: icon as HeaderButtonIcon })}>
                        {headerButtonIconOptions.map((option) => (
                          <option value={option.value} key={option.value}>{option.label}</option>
                        ))}
                      </SelectRow>
                    </>
                  ) : null}
                  <ToggleRow label="店舗切替にデザイン名" checked={customization.header.showStoreTheme} onChange={(showStoreTheme) => updateCustomization({ header: { showStoreTheme } })} />
                </>
              ) : (
              <SelectRow
                label="右上ボタン"
                value={customization.header.rightButton.mode === "none" ? "none" : customization.header.rightButton.shortcut}
                onChange={(value) => {
                  if (value === "none") {
                    updateHeaderRightButton({ mode: "none" });
                    return;
                  }
                  updateHeaderRightButton({ mode: "shortcut", shortcut: value as Exclude<HeaderShortcut, "none"> });
                }}
              >
                {headerShortcutOptions.map((option) => (
                  <option
                    value={option.value}
                    disabled={option.feature !== undefined && !activeStore.features[option.feature]}
                    key={option.value}
                  >
                    {option.label}{option.feature !== undefined && !activeStore.features[option.feature] ? "（機能OFF）" : ""}
                  </option>
                ))}
              </SelectRow>
              )}
            </SettingsGroup>

            {isAdvanced ? (
            <div id="order" className="admin-settings-anchor">
            <SettingsGroup title="モバイルオーダー" description="商品を探しやすく、カートへ追加しやすい表示">
              <SelectRow label="商品表示" value={customization.order.catalogLayout} onChange={(catalogLayout) => updateCustomization({ order: { catalogLayout: catalogLayout as AppCustomization["order"]["catalogLayout"] } })}><option value="cards">商品カード</option><option value="compact">コンパクトリスト</option></SelectRow>
              <ToggleRow label="カテゴリで分ける" checked={customization.order.groupByCategory} onChange={(groupByCategory) => updateCustomization({ order: { groupByCategory } })} />
              <ToggleRow label="最短受取時間" checked={customization.order.showEarliestPickup} onChange={(showEarliestPickup) => updateCustomization({ order: { showEarliestPickup } })} />
              <ToggleRow label="商品画像" checked={customization.order.showItemImages} onChange={(showItemImages) => updateCustomization({ order: { showItemImages } })} />
              <SelectRow label="注文確認ボタン" value={customization.order.ctaPosition} onChange={(ctaPosition) => updateCustomization({ order: { ctaPosition: ctaPosition as AppCustomization["order"]["ctaPosition"] } })}><option value="inline">コンテンツ内</option><option value="sticky">画面下部に固定</option></SelectRow>
            </SettingsGroup>
            </div>
            ) : null}

            <div id="membership" className="admin-settings-anchor">
            <SettingsGroup title="会員証" description="レイアウト・表示形式・ランク色はランク設定で管理" defaultOpen>
              <p className="admin-panel__note">会員証の見た目は、ランク設定の「会員証表示」タブに統合しています。二重管理を避けるため、ここでは編集しません。</p>
              <a className="button button--outline button--md" href={`/admin/${encodeURIComponent(String(window.__PROJECT_KEY__ || "default"))}/settings/ranks`}>
                ランク設定で会員証を編集
              </a>
            </SettingsGroup>
            </div>

            <div id="stamp" className="admin-settings-anchor">
            <SettingsGroup title="スタンプ" description="カード形式と達成条件">
              <SelectRow label="スタンプ形式" value={customization.stamp.style} onChange={(style) => updateCustomization({ stamp: { style: style as AppCustomization["stamp"]["style"] } })}><option value="circles">丸型</option><option value="squares">スクエア</option><option value="progress">プログレスバー</option></SelectRow>
              <SelectRow label="達成スタンプ数" value={String(customization.stamp.goal)} onChange={(goal) => updateCustomization({ stamp: { goal: Number(goal) } })}><option value="5">5個</option><option value="10">10個</option><option value="12">12個</option></SelectRow>
              <ToggleRow label="特典説明" checked={customization.stamp.showRewardText} onChange={(showRewardText) => updateCustomization({ stamp: { showRewardText } })} />
            </SettingsGroup>
            </div>
          </div>
        </section>

        <section className="admin-section" id="navigation">
          <SectionHead number="04" title="タブとナビゲーション" description="下部タブの種類と順番を変更できます。" />
          <div className="admin-settings-grid">
            <SettingsGroup title="タブ構成" description="矢印で並び替え、プルダウンで機能を変更できます。" defaultOpen>
            <TabEditor
              tabs={customization.navigation.tabs}
              onChange={(tabs) => {
                const linkUrls = { ...customization.navigation.linkUrls };
                for (const type of tabs) {
                  if (isLinkTabType(type) && (linkUrls[type] === undefined || linkUrls[type] === "")) {
                    linkUrls[type] = "https://example.com";
                  }
                }
                updateCustomization({ navigation: { tabs, linkUrls } });
              }}
            />
            {customization.navigation.tabs.filter(isLinkTabType).map((type) => (
              <label className="admin-select" key={type}>
                <span>{tabLabelMap[type]} URL</span>
                <input
                  type="url"
                  value={customization.navigation.linkUrls?.[type] ?? ""}
                  placeholder="https://"
                  onChange={(event) => updateCustomization({
                    navigation: {
                      linkUrls: {
                        ...customization.navigation.linkUrls,
                        [type]: event.target.value,
                      },
                    },
                  })}
                />
              </label>
            ))}
            <ToggleRow label="タブ名を表示" checked={customization.navigation.showLabels} onChange={(showLabels) => updateCustomization({ navigation: { showLabels } })} />
            {isAdvanced ? (
              <>
                <SelectRow label="ナビゲーション配置" value={customization.navigation.position} onChange={(position) => updateCustomization({ navigation: { position: position as AppCustomization["navigation"]["position"] } })}><option value="bottom">画面下</option><option value="floating">フローティング</option><option value="top">ヘッダー下</option></SelectRow>
                <ToggleRow label="中央タブを強調" checked={customization.navigation.emphasizeCenterTab} onChange={(emphasizeCenterTab) => updateCustomization({ navigation: { emphasizeCenterTab } })} />
              </>
            ) : null}
            </SettingsGroup>
          </div>
        </section>
      </main>

      <button type="button" className={`admin-preview__backdrop${isPreviewOpen ? " is-open" : ""}`} aria-label="プレビューを閉じる" onClick={() => setPreviewOpen(false)} />
      <aside className={`admin-preview${isPreviewOpen ? " is-open" : ""}`} aria-label="アプリプレビュー">
        <div className="admin-preview__head">
          <span><Eye size={14} />プレビュー</span>
          <select aria-label="プレビュー画面" value={previewTab} onChange={(event) => setPreviewTab(event.target.value as TabType)}>
            {customization.navigation.tabs.map((tab) => <option value={tab} key={tab}>{tabLabelMap[tab]}</option>)}
          </select>
          <button type="button" aria-label="プレビューを閉じる" onClick={() => setPreviewOpen(false)}><X size={17} /></button>
        </div>
        <div className="admin-phone-frame">
          <iframe
            ref={previewFrameRef}
            title={`${activeStore.storeName}の実画面プレビュー`}
            src={embeddedPreviewUrl}
            onLoad={() => {
              sendCustomizationToPreview();
              sendPreviewNavigate(previewTab);
            }}
          />
        </div>
        {previewUrl !== undefined ? (
          <a href={appPreviewHref} target="_blank" rel="noreferrer">実際のアプリで確認 <ExternalLink size={15} /></a>
        ) : (
          <Link to="/home">実際のアプリで確認 <ExternalLink size={15} /></Link>
        )}
      </aside>
    </div>
  );
}

function SectionHead({ number, title, description }: { number: string; title: string; description: string }) {
  return <div className="admin-section__head"><div><span>{number}</span><h2>{title}</h2></div><p>{description}</p></div>;
}

function SettingsGroup({ title, description, children, defaultOpen = false }: { title: string; description: string; children: ReactNode; defaultOpen?: boolean }) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  return (
    <div className={`admin-panel${isOpen ? " is-open" : ""}`}>
      <button type="button" className="admin-panel__toggle" aria-expanded={isOpen} onClick={() => setIsOpen((current) => !current)}>
        <div className="admin-panel__title"><h3>{title}</h3><p>{description}</p></div>
        <ChevronDown size={18} aria-hidden="true" />
      </button>
      {isOpen ? <div className="admin-panel__body">{children}</div> : null}
    </div>
  );
}

function ToggleRow({ label, checked, onChange }: { label: string; checked: boolean; onChange: (checked: boolean) => void }) {
  return <label className="admin-control"><span>{label}</span><input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} /><i /></label>;
}

function SelectRow({ label, value, onChange, children }: { label: string; value: string; onChange: (value: string) => void; children: ReactNode }) {
  return <label className="admin-select"><span>{label}</span><select value={value} onChange={(event) => onChange(event.target.value)}>{children}</select></label>;
}

const homeLayouts: Array<{ id: HomeLayout; name: string; description: string }> = [
  { id: "crm", name: "会員CRM", description: "会員証・ポイント・ギャラリーの順" },
  { id: "standard", name: "スタンダード", description: "挨拶・メイン画像・会員情報の順" },
  { id: "wallet", name: "ウォレット", description: "会員証とサービスを先頭に表示" },
  { id: "magazine", name: "マガジン", description: "メイン画像とおすすめを大きく" },
  { id: "minimal", name: "ミニマル", description: "会員証とサービス中心の簡潔構成" },
];

function HomeLayoutPicker({ value, onChange }: { value: HomeLayout; onChange: (value: HomeLayout) => void }) {
  return (
    <div className="membership-layout-control">
      <div className="membership-layout-control__label">ホームレイアウト <small>1つ選択</small></div>
      <div className="membership-layout-grid">
        {homeLayouts.map((layout) => (
          <button type="button" className={`membership-layout-option${value === layout.id ? " is-active" : ""}`} aria-pressed={value === layout.id} onClick={() => onChange(layout.id)} key={layout.id}>
            <span className={`membership-layout-option__preview home-layout-preview home-layout-preview--${layout.id}`}><i /><i /><i /><i /></span>
            <span><strong>{layout.name}</strong><small>{layout.description}</small></span>
            {value === layout.id ? <Check size={13} /> : null}
          </button>
        ))}
      </div>
    </div>
  );
}

function ColorField({ label, value, onChange }: { label: string; value: string; onChange: (value: string) => void }) {
  const normalizedValue = /^#[0-9a-f]{6}$/i.test(value) ? value.toUpperCase() : "#000000";
  const [draft, setDraft] = useState(normalizedValue);
  useEffect(() => setDraft(normalizedValue), [normalizedValue]);

  const commit = () => {
    if (/^#[0-9a-f]{6}$/i.test(draft)) onChange(draft.toUpperCase());
    else setDraft(normalizedValue);
  };

  return <label className="admin-color"><span>{label}</span><div><input type="color" value={normalizedValue} onChange={(event) => onChange(event.target.value.toUpperCase())} /><input type="text" value={draft} maxLength={7} aria-label={`${label}のHEXカラー`} onChange={(event) => setDraft(event.target.value)} onBlur={commit} onKeyDown={(event) => { if (event.key === "Enter") commit(); }} /></div></label>;
}

function TabEditor({ tabs, onChange }: { tabs: TabType[]; onChange: (tabs: TabType[]) => void }) {
  const move = (index: number, direction: -1 | 1) => {
    const next = [...tabs];
    const target = index + direction;
    if (!next[target]) return;
    [next[index], next[target]] = [next[target], next[index]];
    onChange(next);
  };
  const add = () => {
    const nextType = tabOptions.find((option) => !tabs.includes(option.type))?.type;
    if (nextType && tabs.length < 6) onChange([...tabs, nextType]);
  };

  return (
    <div className="tab-editor">
      {tabs.map((tab, index) => (
        <div className="tab-editor__row" key={`${tab}-${index}`}>
          <span>{index + 1}</span>
          <select value={tab} aria-label={`タブ${index + 1}の種類`} onChange={(event) => { const next = [...tabs]; next[index] = event.target.value as TabType; onChange(next); }}>
            {tabOptions.map((option) => <option value={option.type} disabled={tabs.includes(option.type) && option.type !== tab} key={option.type}>{option.label}</option>)}
          </select>
          <button type="button" disabled={index === 0} onClick={() => move(index, -1)} aria-label={`${tabLabelMap[tab]}を上へ`}><ArrowUp size={15} /></button>
          <button type="button" disabled={index === tabs.length - 1} onClick={() => move(index, 1)} aria-label={`${tabLabelMap[tab]}を下へ`}><ArrowDown size={15} /></button>
          <button type="button" disabled={tabs.length <= 2} onClick={() => onChange(tabs.filter((_, tabIndex) => tabIndex !== index))} aria-label={`${tabLabelMap[tab]}を削除`}><Trash2 size={15} /></button>
        </div>
      ))}
      <button type="button" className="tab-editor__add" disabled={tabs.length >= 6 || tabs.length >= tabOptions.length} onClick={add}><Plus size={15} />タブを追加（{tabs.length}/6）</button>
    </div>
  );
}
