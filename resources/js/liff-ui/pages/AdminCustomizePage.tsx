import {
  ArrowDown,
  ArrowLeft,
  ArrowUp,
  Check,
  ExternalLink,
  LayoutGrid,
  Palette,
  Plus,
  RotateCcw,
  Settings2,
  SlidersHorizontal,
  Trash2,
} from "lucide-react";
import { useEffect, useState, type CSSProperties, type ReactNode } from "react";
import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";
import { designPresetMap, designPresets } from "../config/designPresets";
import { tabLabelMap, tabOptions } from "../config/navigation";
import { useAppConfig } from "../context/AppConfigContext";
import type { TabType } from "../types/app";
import type { AppCustomization, DesignPreset } from "../types/customization";

export function AdminCustomizePage({ previewUrl, showMemberAppLink = true }: { previewUrl?: string; showMemberAppLink?: boolean }) {
  const { activeStore, stores, setActiveStoreId, customization, updateCustomization, resetCustomization } = useAppConfig();
  const preset = designPresetMap[customization.designPreset];
  const previewPrimary = customization.colors.primary ?? preset.palette[0];
  const previewAccent = customization.colors.accent ?? preset.palette[1];
  const previewBackground = customization.colors.background ?? preset.palette[2];
  const appPreviewHref = previewUrl ?? '/home';

  return (
    <div className="admin-shell">
      <header className="admin-header">
        <div className="admin-header__brand"><span>R</span><div><strong>Reggit 店舗アプリ管理</strong><small>店舗運用・会員施策</small></div></div>
        <div className="admin-header__actions">
          <Button variant="outline" onClick={resetCustomization}><RotateCcw size={16} />この店舗を初期設定へ戻す</Button>
          {previewUrl !== undefined ? (
            <a className="button button--solid button--md" href={appPreviewHref} target="_blank" rel="noreferrer">アプリを確認 <ExternalLink size={16} /></a>
          ) : (
            <Link className="button button--solid button--md" to="/home">アプリを確認 <ExternalLink size={16} /></Link>
          )}
        </div>
      </header>

      <aside className="admin-sidebar">
        {showMemberAppLink ? <Link to="/more"><ArrowLeft size={17} />会員アプリへ戻る</Link> : null}
        <nav>
          <a href="#design"><Palette size={17} />デザイン</a>
          <a href="#layout"><LayoutGrid size={17} />レイアウト</a>
          <a href="#features"><SlidersHorizontal size={17} />機能設定</a>
          <a href="#navigation"><Settings2 size={17} />ナビゲーション</a>
        </nav>
        <label className="admin-sidebar__store">
          <span>編集中の店舗</span>
          <select value={activeStore.storeId} onChange={(event) => setActiveStoreId(event.target.value)}>
            {stores.map((store) => <option value={store.storeId} key={store.storeId}>{store.storeName}</option>)}
          </select>
          <small>店舗ごとに設定・自動保存されます</small>
        </label>
      </aside>

      <main className="admin-main">
        <div className="admin-main__heading"><div><span>店舗アプリ設定</span><h1>{activeStore.storeName}</h1><p>会員向けアプリの表示内容と運用設定を店舗単位で管理します。</p></div><div className="admin-save-state"><Check size={15} />変更を保存済み</div></div>

        <section className="admin-section" id="design">
          <SectionHead number="01" title="基本デザイン" description="業態に合う構成を選び、店舗のブランドカラーへ調整します。" />
          <div className="preset-grid preset-grid--compact" aria-label="基本デザインを選択">
            {designPresets.map((item) => (
              <button type="button" className={`preset-card preset-card--${item.id}${customization.designPreset === item.id ? " is-active" : ""}`} key={item.id} title={item.description} aria-label={`${item.name}：${item.description}`} aria-pressed={customization.designPreset === item.id} onClick={() => updateCustomization({ designPreset: item.id })}>
                <span className="preset-card__visual" style={{ "--preset-a": item.palette[0], "--preset-b": item.palette[1], "--preset-c": item.palette[2] } as CSSProperties}><i /><i /><i /></span>
                <span className="preset-card__body"><strong>{item.name}</strong></span>
                {customization.designPreset === item.id ? <span className="preset-card__check"><Check size={10} /></span> : null}
              </button>
            ))}
          </div>

          <div className="admin-panel">
            <div className="admin-panel__title"><h3>カスタムカラー</h3><p>カラーピッカーまたはHEXコードで、画面の各色を自由に上書きします。</p></div>
            <div className="color-grid">
              <ColorField label="メイン" value={customization.colors.primary ?? previewPrimary} onChange={(primary) => updateCustomization({ colors: { primary } })} />
              <ColorField label="サブ" value={customization.colors.secondary ?? "#75665A"} onChange={(secondary) => updateCustomization({ colors: { secondary } })} />
              <ColorField label="アクセント" value={customization.colors.accent ?? previewAccent} onChange={(accent) => updateCustomization({ colors: { accent } })} />
              <ColorField label="背景" value={customization.colors.background ?? previewBackground} onChange={(background) => updateCustomization({ colors: { background } })} />
              <ColorField label="カード" value={customization.colors.surface ?? "#FFFDF8"} onChange={(surface) => updateCustomization({ colors: { surface } })} />
              <ColorField label="本文" value={customization.colors.textPrimary ?? "#2C211A"} onChange={(textPrimary) => updateCustomization({ colors: { textPrimary } })} />
              <ColorField label="補助テキスト" value={customization.colors.textSecondary ?? "#75665A"} onChange={(textSecondary) => updateCustomization({ colors: { textSecondary } })} />
            </div>
            <Button variant="ghost" size="sm" onClick={() => updateCustomization({ colors: { primary: undefined, secondary: undefined, accent: undefined, background: undefined, surface: undefined, textPrimary: undefined, textSecondary: undefined } })}>プリセットの色に戻す</Button>
          </div>
        </section>

        <section className="admin-section" id="layout">
          <SectionHead number="02" title="レイアウトとボタン" description="情報量、主要ボタンの位置、ホーム画面の構成を調整できます。" />
          <div className="admin-settings-grid">
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

            <SettingsGroup title="ヘッダー" description="店舗切替と通知の表示">
              <ToggleRow label="店舗ロゴ" checked={customization.header.showLogo} onChange={(showLogo) => updateCustomization({ header: { showLogo } })} />
              <ToggleRow label="会員アプリ表記" checked={customization.header.showSubtitle} onChange={(showSubtitle) => updateCustomization({ header: { showSubtitle } })} />
              <ToggleRow label="通知ボタン" checked={customization.header.showNotifications} onChange={(showNotifications) => updateCustomization({ header: { showNotifications } })} />
              <ToggleRow label="店舗切替にデザイン名" checked={customization.header.showStoreTheme} onChange={(showStoreTheme) => updateCustomization({ header: { showStoreTheme } })} />
            </SettingsGroup>

            <SettingsGroup title="モバイルオーダー" description="受取情報と購入導線">
              <ToggleRow label="最短受取時間" checked={customization.order.showEarliestPickup} onChange={(showEarliestPickup) => updateCustomization({ order: { showEarliestPickup } })} />
              <ToggleRow label="商品画像" checked={customization.order.showItemImages} onChange={(showItemImages) => updateCustomization({ order: { showItemImages } })} />
              <SelectRow label="注文確認ボタン" value={customization.order.ctaPosition} onChange={(ctaPosition) => updateCustomization({ order: { ctaPosition: ctaPosition as AppCustomization["order"]["ctaPosition"] } })}><option value="inline">コンテンツ内</option><option value="sticky">画面下部に固定</option></SelectRow>
            </SettingsGroup>

            <SettingsGroup title="会員証・スタンプ" description="レジ提示時の見やすさと会員情報">
              <MembershipLayoutPicker value={customization.membership.template} onChange={(template) => updateCustomization({ membership: { template } })} />
              <SelectRow label="情報の揃え位置" value={customization.membership.alignment} onChange={(alignment) => updateCustomization({ membership: { alignment: alignment as AppCustomization["membership"]["alignment"] } })}><option value="left">左揃え</option><option value="center">中央揃え</option></SelectRow>
              <ToggleRow label="店舗ロゴ" checked={customization.membership.showLogo} onChange={(showLogo) => updateCustomization({ membership: { showLogo } })} />
              <ToggleRow label="会員名" checked={customization.membership.showMemberName} onChange={(showMemberName) => updateCustomization({ membership: { showMemberName } })} />
              <ToggleRow label="会員番号" checked={customization.membership.showMemberNumber} onChange={(showMemberNumber) => updateCustomization({ membership: { showMemberNumber } })} />
              <ToggleRow label="バーコード" checked={customization.membership.showBarcode} onChange={(showBarcode) => updateCustomization({ membership: { showBarcode } })} />
              <ToggleRow label="ポイント" checked={customization.membership.showPoints} onChange={(showPoints) => updateCustomization({ membership: { showPoints } })} />
              <ToggleRow label="ランク" checked={customization.membership.showRank} onChange={(showRank) => updateCustomization({ membership: { showRank } })} />
              <ToggleRow label="会員有効期限" checked={customization.membership.showExpiry} onChange={(showExpiry) => updateCustomization({ membership: { showExpiry } })} />
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
            <TabEditor tabs={customization.navigation.tabs} onChange={(tabs) => updateCustomization({ navigation: { tabs } })} />
            <SelectRow label="ナビゲーション配置" value={customization.navigation.position} onChange={(position) => updateCustomization({ navigation: { position: position as AppCustomization["navigation"]["position"] } })}><option value="bottom">画面下</option><option value="floating">フローティング</option><option value="top">ヘッダー下</option></SelectRow>
            <ToggleRow label="タブ名を表示" checked={customization.navigation.showLabels} onChange={(showLabels) => updateCustomization({ navigation: { showLabels } })} />
            <ToggleRow label="中央タブを強調" checked={customization.navigation.emphasizeCenterTab} onChange={(emphasizeCenterTab) => updateCustomization({ navigation: { emphasizeCenterTab } })} />
          </div>
        </section>
      </main>

      <aside className="admin-preview">
        <div className="admin-preview__head"><span>プレビュー</span><strong>{preset.name}</strong></div>
        <div
          className={`admin-phone admin-phone--${customization.designPreset} admin-phone--cta-${customization.home.primaryActionPlacement}`}
          data-button-style={customization.appearance.buttonStyle}
          style={{ "--preview-primary": previewPrimary, "--preview-accent": previewAccent, "--preview-background": previewBackground, "--preview-columns": customization.home.quickActionColumns } as CSSProperties}
        >
          <div className="admin-phone__header">{customization.header.showLogo ? <span>{activeStore.logoText}</span> : null}<strong>{activeStore.storeName}</strong>{customization.header.showNotifications ? <i /> : null}</div>
          <div className="admin-phone__hero">{customization.home.showHeroStatus ? <small>OPEN · TODAY</small> : null}<strong>{activeStore.heroTitle}</strong>{customization.home.primaryActionPlacement === "hero" ? <button type="button">{activeStore.home.primaryActionLabel}</button> : null}</div>
          {customization.home.primaryActionPlacement === "belowHero" ? <button className="admin-phone__primary" type="button">{activeStore.home.primaryActionLabel}</button> : null}
          {customization.home.showMembership ? <div className="admin-phone__member"><span>MEMBER</span><strong>2,450 pt</strong><i /></div> : null}
          {customization.home.showQuickActions ? <div className="admin-phone__actions">{Array.from({ length: customization.home.quickActionColumns * 2 }).map((_, index) => <i key={index} />)}</div> : null}
          {customization.home.primaryActionPlacement === "floating" ? <button className="admin-phone__primary admin-phone__primary--floating" type="button">{activeStore.home.primaryActionLabel}</button> : null}
        </div>
        <Link to="/home">実際のアプリで確認 <ExternalLink size={15} /></Link>
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

const membershipLayouts: Array<{ id: AppCustomization["membership"]["template"]; name: string; description: string }> = [
  { id: "classic", name: "標準", description: "情報を縦に整理" },
  { id: "split", name: "左右分割", description: "会員情報とコードを分離" },
  { id: "barcodeFocus", name: "コード重視", description: "レジ提示を優先" },
  { id: "pointFocus", name: "ポイント重視", description: "残高を大きく表示" },
  { id: "pass", name: "デジタルパス", description: "中央配置の会員パス" },
  { id: "minimal", name: "最小構成", description: "必要情報だけを表示" },
];

function MembershipLayoutPicker({ value, onChange }: { value: AppCustomization["membership"]["template"]; onChange: (value: AppCustomization["membership"]["template"]) => void }) {
  return (
    <div className="membership-layout-control">
      <div className="membership-layout-control__label">会員証レイアウト</div>
      <div className="membership-layout-grid">
        {membershipLayouts.map((layout) => (
          <button type="button" className={`membership-layout-option${value === layout.id ? " is-active" : ""}`} onClick={() => onChange(layout.id)} key={layout.id}>
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
