import {
  ArrowDown,
  ArrowLeft,
  ArrowUp,
  Check,
  Bell,
  CalendarDays,
  Eye,
  ExternalLink,
  CreditCard,
  Home,
  LayoutGrid,
  Palette,
  Plus,
  Menu,
  MoreHorizontal,
  QrCode,
  RotateCcw,
  ShoppingBag,
  Settings2,
  SlidersHorizontal,
  Stamp,
  Ticket,
  UserRound,
  Trash2,
  X,
} from "lucide-react";
import { useCallback, useEffect, useRef, useState, type CSSProperties, type ReactNode } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { designPresets } from "../config/designPresets";
import { designPresetLayoutPatch, resolveHomeLayout } from "../config/customization";
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

const headerButtonIconOptions: Array<{ value: HeaderButtonIcon; label: string; icon: JSX.Element }> = [
  { value: "bell", label: "お知らせ", icon: <Bell size={14} /> },
  { value: "userRound", label: "プロフィール", icon: <UserRound size={14} /> },
  { value: "creditCard", label: "会員証", icon: <CreditCard size={14} /> },
  { value: "ticket", label: "クーポン", icon: <Ticket size={14} /> },
  { value: "stamp", label: "スタンプ", icon: <Stamp size={14} /> },
  { value: "calendarDays", label: "予約", icon: <CalendarDays size={14} /> },
  { value: "qrCode", label: "順番待ち", icon: <QrCode size={14} /> },
  { value: "shoppingBag", label: "モバイル注文", icon: <ShoppingBag size={14} /> },
  { value: "menu", label: "メニュー", icon: <Menu size={14} /> },
  { value: "home", label: "ホーム", icon: <Home size={14} /> },
  { value: "moreHorizontal", label: "その他", icon: <MoreHorizontal size={14} /> },
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

function buildEmbeddedPreviewUrl(baseUrl: string, tab: TabType): string {
  const separator = baseUrl.includes("?") ? "&" : "?";
  return `${baseUrl}${separator}designer_live_preview=1&designer_embed=1#${previewPathByTab[tab]}`;
}

export function AdminCustomizePage({
  previewUrl,
  showMemberAppLink = true,
  embedded = false,
  saveState = "saved",
}: AdminCustomizePageProps) {
  const { activeStore, stores, setActiveStoreId, activeThemeId, customization, updateCustomization, resetCustomization } = useAppConfig();
  const activeTheme = themeMap[activeThemeId] ?? themeMap[activeStore.themeId] ?? themeMap["minimal-light"];
  const resolvedColors = resolveCustomizationColors(customization, activeTheme);
  const appPreviewHref = previewUrl ?? '/home';
  const [activeSection, setActiveSection] = useState<(typeof sectionLinks)[number]["id"]>("design");
  const [isPreviewOpen, setPreviewOpen] = useState(false);
  const [previewTab, setPreviewTab] = useState<TabType>(customization.navigation.tabs[0] ?? "home");
  const previewFrameRef = useRef<HTMLIFrameElement>(null);
  const embeddedPreviewUrl = buildEmbeddedPreviewUrl(appPreviewHref, previewTab);

  const sendCustomizationToPreview = useCallback(() => {
    previewFrameRef.current?.contentWindow?.postMessage({
      type: "reggit:designer-preview:update",
      customization,
    }, window.location.origin);
  }, [customization]);

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
    const handlePreviewReady = (event: MessageEvent) => {
      if (event.origin !== window.location.origin || event.data?.type !== "reggit:designer-preview:ready") {
        return;
      }
      sendCustomizationToPreview();
    };
    window.addEventListener("message", handlePreviewReady);
    return () => window.removeEventListener("message", handlePreviewReady);
  }, [sendCustomizationToPreview]);

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
        {showMemberAppLink ? <Link to="/more"><ArrowLeft size={17} />会員アプリへ戻る</Link> : null}
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
          <div><span>店舗アプリ設定</span><h1>{activeStore.storeName}</h1><p>項目を変更し、プレビューを確認してから店舗へ保存します。</p></div>
          <div className="admin-main__status">
            <div className={`admin-save-state is-${saveState}`} aria-live="polite"><Check size={15} />{saveStateLabels[saveState]}</div>
            <button type="button" className="admin-preview-toggle" aria-expanded={isPreviewOpen} onClick={() => setPreviewOpen(true)}><Eye size={16} />プレビュー</button>
          </div>
        </div>

        <section className="admin-section" id="design">
          <SectionHead number="01" title="基本デザイン" description="業態に合う構成を選び、店舗のブランドカラーへ調整します。" />
          <div className="preset-grid preset-grid--compact" aria-label="基本デザインを選択">
            {designPresets.map((item) => (
              <button type="button" className={`preset-card preset-card--${item.id}${customization.designPreset === item.id ? " is-active" : ""}`} key={item.id} title={item.description} aria-label={`${item.name}：${item.description}`} aria-pressed={customization.designPreset === item.id} onClick={() => updateCustomization(designPresetLayoutPatch(item.id))}>
                <span className="preset-card__visual" style={{ "--preset-a": item.palette[0], "--preset-b": item.palette[1], "--preset-c": item.palette[2] } as CSSProperties}><i /><i /><i /></span>
                <span className="preset-card__body"><strong>{item.name}</strong></span>
                {customization.designPreset === item.id ? <span className="preset-card__check"><Check size={10} /></span> : null}
              </button>
            ))}
          </div>

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
        </section>

        <section className="admin-section" id="layout">
          <SectionHead number="02" title="レイアウトとボタン" description="情報量、主要ボタンの位置、ホーム画面の構成を調整できます。" />
          <div className="admin-settings-grid">
            <SettingsGroup title="ホームのレイアウト" description="ホーム画面の情報の並びを選びます。右のプレビューへ即時反映されます">
              <HomeLayoutPicker value={resolveHomeLayout(customization.home.layout)} onChange={(layout) => updateCustomization({ home: { layout } })} />
            </SettingsGroup>

            <SettingsGroup title="画面スタイル" description="全体の密度とボタン表現">
              <SelectRow label="コンテンツ密度" value={customization.appearance.density} onChange={(density) => updateCustomization({ appearance: { density: density as AppCustomization["appearance"]["density"] } })}><option value="compact">コンパクト</option><option value="standard">標準</option><option value="relaxed">ゆったり</option></SelectRow>
              <SelectRow label="ボタンスタイル" value={customization.appearance.buttonStyle} onChange={(buttonStyle) => updateCustomization({ appearance: { buttonStyle: buttonStyle as AppCustomization["appearance"]["buttonStyle"] } })}><option value="filled">塗り</option><option value="soft">ソフト</option><option value="outline">アウトライン</option></SelectRow>
              <SelectRow label="主要ボタンの位置" value={customization.home.primaryActionPlacement} onChange={(primaryActionPlacement) => updateCustomization({ home: { primaryActionPlacement: primaryActionPlacement as AppCustomization["home"]["primaryActionPlacement"] } })}><option value="hero">メイン画像内</option><option value="belowHero">メイン画像の下</option><option value="floating">画面下に固定</option></SelectRow>
              <SelectRow label="サービス列数" value={String(customization.home.quickActionColumns)} onChange={(quickActionColumns) => updateCustomization({ home: { quickActionColumns: Number(quickActionColumns) as 2 | 3 | 4 } })}><option value="2">2列</option><option value="3">3列</option><option value="4">4列</option></SelectRow>
            </SettingsGroup>

            <SettingsGroup title="ホームの表示内容" description="不要なセクションを非表示">
              <ToggleRow label="メイン画像" checked={customization.home.showHeroImage} onChange={(showHeroImage) => updateCustomization({ home: { showHeroImage } })} />
              <ToggleRow label="営業状況" checked={customization.home.showHeroStatus} onChange={(showHeroStatus) => updateCustomization({ home: { showHeroStatus } })} />
              <ToggleRow label="会員サマリー" checked={customization.home.showMembership} onChange={(showMembership) => updateCustomization({ home: { showMembership } })} />
              <ToggleRow label="サービスボタン" checked={customization.home.showQuickActions} onChange={(showQuickActions) => updateCustomization({ home: { showQuickActions } })} />
              <ToggleRow label="おすすめ" checked={customization.home.showFeatured} onChange={(showFeatured) => updateCustomization({ home: { showFeatured } })} />
              <ToggleRow label="クーポン" checked={customization.home.showCoupons} onChange={(showCoupons) => updateCustomization({ home: { showCoupons } })} />
              <ToggleRow label="お知らせ" checked={customization.home.showNews} onChange={(showNews) => updateCustomization({ home: { showNews } })} />
              <ToggleRow label="店舗情報" checked={customization.home.showStoreInfo} onChange={(showStoreInfo) => updateCustomization({ home: { showStoreInfo } })} />
            </SettingsGroup>
          </div>
        </section>

        <section className="admin-section" id="features">
          <SectionHead number="03" title="機能ごとの表示設定" description="状態分類、画像、確認画面など、機能の細部まで設定できます。" />
          <div className="admin-settings-grid">
            <SettingsGroup title="クーポン" description="利用済み・期限切れは「利用終了」に統一">
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

            <SettingsGroup title="ヘッダー" description="店舗切替と右上ボタンの表示">
              <ToggleRow label="店舗ロゴ" checked={customization.header.showLogo} onChange={(showLogo) => updateCustomization({ header: { showLogo } })} />
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
                  if (mode === "custom") {
                    updateHeaderRightButton({ mode });
                    return;
                  }
                  updateHeaderRightButton({ mode });
                }}
              >
                {headerButtonModeOptions.map((option) => (
                  <option value={option.value} key={option.value}>
                    {option.label}
                  </option>
                ))}
              </SelectRow>

              {customization.header.rightButton.mode === "shortcut" ? (
                <SelectRow
                  label="右上ショートカット"
                  value={customization.header.rightButton.shortcut}
                  onChange={(value) => {
                    const shortcut = value as Exclude<HeaderShortcut, "none">;
                    updateHeaderRightButton({ shortcut });
                  }}
                >
                  {headerShortcutOptions
                    .filter((option) => option.value !== "none")
                    .map((option) => (
                    <option
                      value={option.value}
                      disabled={option.feature !== undefined && !activeStore.features[option.feature]}
                      key={option.value}
                    >
                      {option.label}{option.feature !== undefined && !activeStore.features[option.feature] ? "（機能OFF）" : ""}
                    </option>
                  ))}
                </SelectRow>
              ) : null}

              {customization.header.rightButton.mode === "custom" ? (
                <>
                  <label className="admin-select">
                    <span>ボタンラベル</span>
                    <input
                      type="text"
                      value={customization.header.rightButton.label}
                      placeholder="プロフィール"
                      onChange={(event) => updateHeaderRightButton({ label: event.target.value })}
                    />
                  </label>
                  <label className="admin-select">
                    <span>遷移先パス</span>
                    <input
                      type="text"
                      value={customization.header.rightButton.path}
                      placeholder="/profile"
                      onChange={(event) => updateHeaderRightButton({ path: event.target.value })}
                    />
                  </label>
                  <SelectRow
                    label="アイコン"
                    value={customization.header.rightButton.icon}
                    onChange={(icon) => updateHeaderRightButton({ icon: icon as HeaderButtonIcon })}
                  >
                    {headerButtonIconOptions.map((option) => (
                      <option value={option.value} key={option.value}>
                        {option.label}
                      </option>
                    ))}
                  </SelectRow>
                </>
              ) : null}

              <ToggleRow label="店舗切替にデザイン名" checked={customization.header.showStoreTheme} onChange={(showStoreTheme) => updateCustomization({ header: { showStoreTheme } })} />
            </SettingsGroup>

            <SettingsGroup title="モバイルオーダー" description="商品を探しやすく、カートへ追加しやすい表示">
              <SelectRow label="商品表示" value={customization.order.catalogLayout} onChange={(catalogLayout) => updateCustomization({ order: { catalogLayout: catalogLayout as AppCustomization["order"]["catalogLayout"] } })}><option value="cards">商品カード</option><option value="compact">コンパクトリスト</option></SelectRow>
              <ToggleRow label="カテゴリで分ける" checked={customization.order.groupByCategory} onChange={(groupByCategory) => updateCustomization({ order: { groupByCategory } })} />
              <ToggleRow label="最短受取時間" checked={customization.order.showEarliestPickup} onChange={(showEarliestPickup) => updateCustomization({ order: { showEarliestPickup } })} />
              <ToggleRow label="商品画像" checked={customization.order.showItemImages} onChange={(showItemImages) => updateCustomization({ order: { showItemImages } })} />
              <SelectRow label="注文確認ボタン" value={customization.order.ctaPosition} onChange={(ctaPosition) => updateCustomization({ order: { ctaPosition: ctaPosition as AppCustomization["order"]["ctaPosition"] } })}><option value="inline">コンテンツ内</option><option value="sticky">画面下部に固定</option></SelectRow>
            </SettingsGroup>

            <SettingsGroup title="会員証デザイン" description="用途に合うレイアウトを選択。右の実画面プレビューへ即時反映されます">
              <MembershipLayoutPicker value={customization.membership.template} onChange={(template) => updateCustomization({ membership: { template } })} />
              <SelectRow label="情報の揃え位置" value={customization.membership.alignment} onChange={(alignment) => updateCustomization({ membership: { alignment: alignment as AppCustomization["membership"]["alignment"] } })}><option value="left">左揃え</option><option value="center">中央揃え</option></SelectRow>
            </SettingsGroup>

            <SettingsGroup title="会員証の表示内容" description="レジで必要な情報だけを表示">
              <ToggleRow label="店舗ロゴ" checked={customization.membership.showLogo} onChange={(showLogo) => updateCustomization({ membership: { showLogo } })} />
              <ToggleRow label="会員名" checked={customization.membership.showMemberName} onChange={(showMemberName) => updateCustomization({ membership: { showMemberName } })} />
              <ToggleRow label="会員番号" checked={customization.membership.showMemberNumber} onChange={(showMemberNumber) => updateCustomization({ membership: { showMemberNumber } })} />
              <ToggleRow label="バーコード" checked={customization.membership.showBarcode} onChange={(showBarcode) => updateCustomization({ membership: { showBarcode } })} />
              <ToggleRow label="ポイント" checked={customization.membership.showPoints} onChange={(showPoints) => updateCustomization({ membership: { showPoints } })} />
              <ToggleRow label="会員ランク" checked={customization.membership.showRank} onChange={(showRank) => updateCustomization({ membership: { showRank } })} />
              <ToggleRow label="次ランクまでのポイント" checked={customization.membership.showNextRank} onChange={(showNextRank) => updateCustomization({ membership: { showNextRank } })} />
              <ToggleRow label="会員有効期限" checked={customization.membership.showExpiry} onChange={(showExpiry) => updateCustomization({ membership: { showExpiry } })} />
            </SettingsGroup>

            <SettingsGroup title="スタンプ" description="カード形式と達成条件">
              <SelectRow label="スタンプ形式" value={customization.stamp.style} onChange={(style) => updateCustomization({ stamp: { style: style as AppCustomization["stamp"]["style"] } })}><option value="circles">丸型</option><option value="squares">スクエア</option><option value="progress">プログレスバー</option></SelectRow>
              <SelectRow label="達成スタンプ数" value={String(customization.stamp.goal)} onChange={(goal) => updateCustomization({ stamp: { goal: Number(goal) } })}><option value="5">5個</option><option value="10">10個</option><option value="12">12個</option></SelectRow>
              <ToggleRow label="特典説明" checked={customization.stamp.showRewardText} onChange={(showRewardText) => updateCustomization({ stamp: { showRewardText } })} />
            </SettingsGroup>
          </div>
        </section>

        <section className="admin-section" id="navigation">
          <SectionHead number="04" title="タブとナビゲーション" description="2〜6個の範囲で、タブの種類・順番・配置を自由に変更できます。" />
          <div className="admin-panel">
            <div className="admin-panel__title"><h3>タブ構成</h3><p>矢印で並び替え、プルダウンで機能を変更できます。</p></div>
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
            <SelectRow label="ナビゲーション配置" value={customization.navigation.position} onChange={(position) => updateCustomization({ navigation: { position: position as AppCustomization["navigation"]["position"] } })}><option value="bottom">画面下</option><option value="floating">フローティング</option><option value="top">ヘッダー下</option></SelectRow>
            <ToggleRow label="タブ名を表示" checked={customization.navigation.showLabels} onChange={(showLabels) => updateCustomization({ navigation: { showLabels } })} />
            <ToggleRow label="中央タブを強調" checked={customization.navigation.emphasizeCenterTab} onChange={(emphasizeCenterTab) => updateCustomization({ navigation: { emphasizeCenterTab } })} />
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
            onLoad={sendCustomizationToPreview}
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

function SettingsGroup({ title, description, children }: { title: string; description: string; children: ReactNode }) {
  return <div className="admin-panel"><div className="admin-panel__title"><h3>{title}</h3><p>{description}</p></div>{children}</div>;
}

function ToggleRow({ label, checked, onChange }: { label: string; checked: boolean; onChange: (checked: boolean) => void }) {
  return <label className="admin-control"><span>{label}</span><input type="checkbox" checked={checked} onChange={(event) => onChange(event.target.checked)} /><i /></label>;
}

function SelectRow({ label, value, onChange, children }: { label: string; value: string; onChange: (value: string) => void; children: ReactNode }) {
  return <label className="admin-select"><span>{label}</span><select value={value} onChange={(event) => onChange(event.target.value)}>{children}</select></label>;
}

const homeLayouts: Array<{ id: HomeLayout; name: string; description: string }> = [
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

const membershipLayouts: Array<{ id: AppCustomization["membership"]["template"]; name: string; description: string }> = [
  { id: "classic", name: "スタンダード", description: "会員情報を見やすく縦に整理" },
  { id: "split", name: "スプリット", description: "ポイントとコードを左右に分離" },
  { id: "barcodeFocus", name: "レジ提示", description: "バーコードを最も大きく表示" },
  { id: "pointFocus", name: "ポイント", description: "ポイント残高を主役に表示" },
  { id: "pass", name: "デジタルパス", description: "中央配置の上質な会員パス" },
  { id: "minimal", name: "ミニマル", description: "装飾を抑えたコンパクト構成" },
];

function MembershipLayoutPicker({ value, onChange }: { value: AppCustomization["membership"]["template"]; onChange: (value: AppCustomization["membership"]["template"]) => void }) {
  return (
    <div className="membership-layout-control">
      <div className="membership-layout-control__label">会員証レイアウト <small>1つ選択</small></div>
      <div className="membership-layout-grid">
        {membershipLayouts.map((layout) => (
          <button type="button" className={`membership-layout-option${value === layout.id ? " is-active" : ""}`} aria-pressed={value === layout.id} onClick={() => onChange(layout.id)} key={layout.id}>
            <span className={`membership-layout-option__preview membership-layout-option__preview--${layout.id}`}><i /><i /><i /><i /></span>
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
