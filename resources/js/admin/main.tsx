import React, { useCallback, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import '../../css/app.css';
import '../../css/admin/liff-designer/react.css';
import '../liff-ui/styles/tokens.css';
import '../liff-ui/styles/globals.css';
import { AdminSidebar } from './components/AdminSidebar';
import { exposeAdminLegacyGlobals } from '../shared/utils/exposeAdminLegacyGlobals';
import { MemoryRouter } from 'react-router-dom';
import { AppConfigProvider, useAppConfig } from '../liff-ui/context/AppConfigContext';
import { AdminCustomizePage } from '../liff-ui/pages/AdminCustomizePage';
import { buildProjectStore } from '../liff-ui/config/projectStore';
import type { AppCustomization } from '../liff-ui/types/customization';
import './admin';
import './couponBulkIssue';
import './memberDetailHistory';
import './memberLineQr';

type DesignerDomConfig = {
  formAction: string;
  csrfToken: string;
  tabSettings: Record<string, unknown>;
  customCss: string;
  menuItems: unknown[];
  backUrl: string;
};

function readDesignerConfigFromDom(): DesignerDomConfig {
  const el = document.getElementById('ldDesignerConfig');
  if (el === null || el.textContent === null || el.textContent.trim() === '') {
    return {
      formAction: '',
      csrfToken: '',
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
      tabSettings: (parsed.tabSettings as Record<string, unknown>) ?? {},
      customCss: String(parsed.customCss ?? ''),
      menuItems: Array.isArray(parsed.menuItems) ? parsed.menuItems : [],
      backUrl: String(parsed.backUrl ?? ''),
    };
  } catch {
    return {
      formAction: '',
      csrfToken: '',
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
    .filter((tab) => tab.enabled)
    .forEach((tab) => {
      const legacyTab = tabToLegacy[tab.type] ?? 'home';
      if (seen.has(legacyTab)) {
        return;
      }
      seen.add(legacyTab);
      items.push({
        tab: legacyTab,
        label: tab.label.slice(0, 12),
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
      accent_color: customization.colors.primary ?? previousGlobal.accent_color,
    },
  };

  const body = new FormData();
  body.append('_token', config.csrfToken);
  body.append('layouts_json', JSON.stringify({}));
  body.append('detail_layouts_json', JSON.stringify({}));
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
  const { customization, activeStore } = useAppConfig();
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState<string>();
  const [error, setError] = useState<string>();

  const onSave = useCallback(async () => {
    setSaving(true);
    setError(undefined);
    try {
      const msg = await saveCustomization(config, customization);
      setMessage(msg);
    } catch (caught) {
      setError(caught instanceof Error ? caught.message : '保存に失敗しました');
    } finally {
      setSaving(false);
    }
  }, [config, customization]);

  useEffect(() => {
    if (message === undefined) {
      return undefined;
    }
    const timer = window.setTimeout(() => setMessage(undefined), 3000);
    return () => window.clearTimeout(timer);
  }, [message]);

  return (
    <div className="reggit-admin-liff-ui">
      <div className="reggit-admin-liff-ui__toolbar">
        <a href={config.backUrl || '#'}>設定へ戻る</a>
        <div>
          <span>{activeStore.storeName}</span>
          <button type="button" className="button button--solid button--md" onClick={() => void onSave()} disabled={saving}>
            {saving ? '保存中…' : '店舗に保存'}
          </button>
        </div>
      </div>
      {message !== undefined ? <div className="reggit-admin-liff-ui__toast is-ok">{message}</div> : null}
      {error !== undefined ? <div className="reggit-admin-liff-ui__toast is-error">{error}</div> : null}
      <AdminCustomizePage
        showMemberAppLink={false}
        previewUrl={`/liff/${encodeURIComponent(String(window.__PROJECT_KEY__ || 'default'))}`}
      />
    </div>
  );
}

function mountLiffDesigner(): void {
  const root = document.getElementById('liff-designer-root');
  if (root === null) {
    return;
  }
  const config = readDesignerConfigFromDom();
  document.body.classList.add('reggit-liff-ui', 'reggit-liff-ui--admin');
  createRoot(root).render(
    <MemoryRouter>
      <AppConfigProvider isAdminStudio>
        <AdminCustomizeHost config={config} />
      </AppConfigProvider>
    </MemoryRouter>,
  );
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
