import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { ArrowLeft, Check, ExternalLink, RotateCcw, Save } from 'lucide-react';
import { AdminSidebar } from './components/AdminSidebar';
import { exposeAdminLegacyGlobals } from '../shared/utils/exposeAdminLegacyGlobals';
import { MemoryRouter } from 'react-router-dom';
import { AppConfigProvider, useAppConfig } from '../liff-ui/context/AppConfigContext';
import { AdminCustomizePage } from '../liff-ui/pages/AdminCustomizePage';
import { buildProjectStore } from '../liff-ui/config/projectStore';
import { tabLabelMap } from '../liff-ui/config/navigation';
import type { AppCustomization } from '../liff-ui/types/customization';
import './admin';
import './couponForm';
import './announcementDelivery';
import './couponBulkIssue';
import './scheduledDelivery';
import './memberDetailHistory';
import './memberLineQr';
import './stampGrantQr';
import './queueRealtime';

type DesignerDomConfig = {
  formAction: string;
  csrfToken: string;
  layouts: Record<string, unknown>;
  detailLayouts: Record<string, unknown>;
  tabSettings: Record<string, unknown>;
  customCss: string;
  menuItems: unknown[];
  backUrl: string;
};

const memberCardDesignByTemplate: Record<AppCustomization['membership']['template'], string> = {
  classic: 'default',
  split: 'split_banner',
  barcodeFocus: 'logo_header',
  pointFocus: 'logo_top',
  pass: 'emblem',
  minimal: 'minimal_line',
  simple: 'compact',
  ticket: 'ticket',
  wallet: 'wallet',
};

function customizationFingerprint(customization: AppCustomization): string {
  return JSON.stringify(customization, (_key, value: unknown) => {
    if (value === null || Array.isArray(value) || typeof value !== 'object') return value;
    return Object.keys(value as Record<string, unknown>)
      .sort()
      .reduce<Record<string, unknown>>((sorted, key) => {
        sorted[key] = (value as Record<string, unknown>)[key];
        return sorted;
      }, {});
  });
}

function readDesignerConfigFromDom(): DesignerDomConfig {
  const el = document.getElementById('ldDesignerConfig');
  if (el === null || el.textContent === null || el.textContent.trim() === '') {
    return {
      formAction: '',
      csrfToken: '',
      layouts: {},
      detailLayouts: {},
      tabSettings: {},
      customCss: '',
      menuItems: [],
      backUrl: '',
    };
  }
  try {
    const parsed = JSON.parse(el.textContent) as Partial<DesignerDomConfig>;
    return {
      formAction: String(parsed.formAction ?? ''),
      csrfToken: String(parsed.csrfToken ?? ''),
      layouts: (parsed.layouts as Record<string, unknown>) ?? {},
      detailLayouts: (parsed.detailLayouts as Record<string, unknown>) ?? {},
      tabSettings: (parsed.tabSettings as Record<string, unknown>) ?? {},
      customCss: String(parsed.customCss ?? ''),
      menuItems: Array.isArray(parsed.menuItems) ? parsed.menuItems : [],
      backUrl: String(parsed.backUrl ?? ''),
    };
  } catch {
    return {
      formAction: '',
      csrfToken: '',
      layouts: {},
      detailLayouts: {},
      tabSettings: {},
      customCss: '',
      menuItems: [],
      backUrl: '',
    };
  }
}

function readXsrfToken(): string {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  if (match === null || match[1] === undefined) {
    return '';
  }
  try {
    return decodeURIComponent(match[1]);
  } catch {
    return match[1];
  }
}

function navigationToMenuItems(customization: AppCustomization): Array<Record<string, unknown>> {
  const tabToLegacy: Record<string, string> = {
    home: 'home',
    membership: 'member',
    mobileOrder: 'products',
    dineInOrder: 'products',
    reservation: 'reservations',
    queue: 'queue',
    coupon: 'coupons',
    stamp: 'stamps',
    point: 'member',
    menu: 'products',
    store: 'home',
    news: 'announcements',
    more: 'member',
  };
  const seen = new Set<string>();
  const items: Array<Record<string, unknown>> = [];
  customization.navigation.tabs
    .forEach((tab) => {
      const legacyTab = tabToLegacy[tab] ?? 'home';
      if (seen.has(legacyTab)) {
        return;
      }
      seen.add(legacyTab);
      items.push({
        tab: legacyTab,
        label: tabLabelMap[tab].slice(0, 12),
        icon: 'fa-circle',
        order: items.length,
        enabled: true,
      });
    });
  return items.slice(0, 5);
}

async function saveCustomization(config: DesignerDomConfig, customization: AppCustomization): Promise<string> {
  if (config.formAction === '' || config.csrfToken === '') {
    throw new Error('保存設定が見つかりません。ページを再読み込みしてください');
  }
  const projectStore = buildProjectStore();
  const previousGlobal = (config.tabSettings._global as Record<string, unknown> | undefined) ?? {};
  const tabSettings = {
    ...config.tabSettings,
    _global: {
      ...previousGlobal,
      app_display_name: projectStore.storeName,
      app_customization: customization,
      member_card_design: memberCardDesignByTemplate[customization.membership.template],
      accent_color: customization.colors.primary ?? previousGlobal.accent_color,
      products_group_by_category: customization.order.groupByCategory,
    },
  };

  const body = new FormData();
  body.append('_token', config.csrfToken);
  // この画面では旧レイアウトを編集しないため、既存値をそのまま保持する。
  body.append('layouts_json', JSON.stringify(config.layouts));
  body.append('detail_layouts_json', JSON.stringify(config.detailLayouts));
  body.append('tab_settings_json', JSON.stringify(tabSettings));
  body.append('menu_items_json', JSON.stringify(navigationToMenuItems(customization)));
  body.append('custom_css', config.customCss ?? '');

  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-CSRF-TOKEN': config.csrfToken,
  };
  const xsrf = readXsrfToken();
  if (xsrf !== '') {
    headers['X-XSRF-TOKEN'] = xsrf;
  }

  const response = await fetch(config.formAction, {
    method: 'POST',
    body,
    headers,
    credentials: 'same-origin',
  });
  const contentType = response.headers.get('content-type') || '';
  const isJson = contentType.includes('application/json');
  let data: { message?: string } = {};
  if (isJson) {
    try {
      data = await response.json() as { message?: string };
    } catch {
      data = {};
    }
  }
  if (!response.ok) {
    throw new Error(data.message || `保存に失敗しました（${response.status}）`);
  }
  return data.message || '保存しました';
}

function AdminCustomizeHost({ config }: { config: DesignerDomConfig }) {
  const { customization, activeStore, resetCustomization } = useAppConfig();
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string>();
  const [error, setError] = useState<string>();
  const currentFingerprint = customizationFingerprint(customization);
  const [savedFingerprint, setSavedFingerprint] = useState(() => currentFingerprint);
  const isDirty = currentFingerprint !== savedFingerprint;

  const onSave = useCallback(async () => {
    if (saving) return;
    const fingerprintToSave = currentFingerprint;
    setSaving(true);
    setError(undefined);
    setMessage(undefined);
    try {
      const msg = await saveCustomization(config, customization);
      setSavedFingerprint(fingerprintToSave);
      setMessage(msg);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : '保存に失敗しました');
    } finally {
      setSaving(false);
    }
  }, [config, currentFingerprint, customization, saving]);

  useEffect(() => {
    setError(undefined);
  }, [currentFingerprint]);

  useEffect(() => {
    const handleBeforeUnload = (event: BeforeUnloadEvent) => {
      if (!isDirty) return;
      event.preventDefault();
      event.returnValue = '';
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [isDirty]);

  useEffect(() => {
    const handleSaveShortcut = (event: KeyboardEvent) => {
      if (!(event.metaKey || event.ctrlKey) || event.key.toLowerCase() !== 's') return;
      event.preventDefault();
      if (isDirty && !saving) void onSave();
    };
    window.addEventListener('keydown', handleSaveShortcut);
    return () => window.removeEventListener('keydown', handleSaveShortcut);
  }, [isDirty, onSave, saving]);

  useEffect(() => {
    if (message === undefined) {
      return undefined;
    }
    const timer = window.setTimeout(() => setMessage(undefined), 3000);
    return () => window.clearTimeout(timer);
  }, [message]);

  const requestReset = () => {
    if (window.confirm(`「${activeStore.storeName}」のデザイン設定を初期状態に戻しますか？\n保存するまでは店舗アプリに反映されません。`)) {
      resetCustomization();
    }
  };

  const saveState = saving ? 'saving' : error !== undefined ? 'error' : isDirty ? 'unsaved' : 'saved';

  return (
    <div className="reggit-admin-liff-ui">
      <div className="reggit-admin-liff-ui__toolbar">
        <div className="reggit-admin-liff-ui__toolbar-title">
          <a href={config.backUrl || '#'} aria-label="設定へ戻る"><ArrowLeft size={17} /></a>
          <div><span>LIFFデザイン</span><strong>{activeStore.storeName}</strong></div>
        </div>
        <div className="reggit-admin-liff-ui__toolbar-actions">
          <span className={`reggit-admin-liff-ui__save-state is-${saveState}`} aria-live="polite"><Check size={14} />{saveState === 'saving' ? '保存中…' : saveState === 'error' ? '保存エラー' : isDirty ? '未保存' : '保存済み'}</span>
          <button type="button" className="reggit-admin-liff-ui__tool-button" onClick={requestReset}><RotateCcw size={15} />初期設定に戻す</button>
          <a className="reggit-admin-liff-ui__tool-button" href={`/liff/${encodeURIComponent(String(window.__PROJECT_KEY__ || 'default'))}`} target="_blank" rel="noreferrer">アプリを開く <ExternalLink size={15} /></a>
          <button type="button" className="button button--solid button--md reggit-admin-liff-ui__save-button" onClick={() => void onSave()} disabled={saving || !isDirty} title="⌘/Ctrl + S">
            <Save size={16} />{saving ? '保存中…' : '変更を保存'}
          </button>
        </div>
      </div>
      {message !== undefined ? <div className="reggit-admin-liff-ui__toast is-ok" role="status">{message}</div> : null}
      {error !== undefined ? <div className="reggit-admin-liff-ui__toast is-error" role="alert">{error}</div> : null}
      <AdminCustomizePage
        embedded
        showMemberAppLink={false}
        saveState={saveState}
        previewUrl={`/liff/${encodeURIComponent(String(window.__PROJECT_KEY__ || 'default'))}`}
      />
    </div>
  );
}

async function loadLiffDesignerStyles(): Promise<void> {
  await Promise.all([
    import('../../css/app.css'),
    import('../liff-ui/styles/tokens.css'),
    import('../liff-ui/styles/globals.css'),
    import('../liff-ui/styles/design-layout-variants.css'),
  ]);
}

function mountLiffDesigner(): void {
  const root = document.getElementById('liff-designer-root');
  if (root === null) {
    return;
  }
  const config = readDesignerConfigFromDom();
  document.body.classList.add('reggit-liff-ui', 'reggit-liff-ui--admin');
  void loadLiffDesignerStyles().then(() => {
    createRoot(root).render(
      <MemoryRouter>
        <AppConfigProvider isAdminStudio>
          <AdminCustomizeHost config={config} />
        </AppConfigProvider>
      </MemoryRouter>,
    );
  });
}

function AdminBootstrap(): null {
  useEffect(() => {
    try {
      exposeAdminLegacyGlobals();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('[admin-bootstrap] failed:', error);
    }
  }, []);
  return null;
}

const mount = document.getElementById('react-admin-root');
if (mount !== null) {
  createRoot(mount).render(
    <>
      <AdminBootstrap />
      <AdminSidebar />
    </>,
  );
}

if (document.body.dataset.pageKind === 'liff-designer') {
  mountLiffDesigner();
}
