import { useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { LIFF_UI_NAVIGATE, legacyTabForPath, pathForLegacyTab, type LiffUiNavigateDetail } from './tabPath';

type AppWithSwitchTab = {
  currentTab?: string;
  switchTab?: (tab: string) => unknown;
};

function syncLegacyCurrentTab(pathname: string): void {
  const tab = legacyTabForPath(pathname);
  if (tab === null) {
    return;
  }
  const app = window.app as AppWithSwitchTab | undefined;
  if (app === undefined) {
    return;
  }
  app.currentTab = tab;
  document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
}

/**
 * 旧 Managers の switchTab / hash 遷移を HashRouter に橋渡しする。
 */
export function useLegacyTabBridge(): void {
  const navigate = useNavigate();
  const location = useLocation();

  useEffect(() => {
    syncLegacyCurrentTab(location.pathname);
  }, [location.pathname]);

  useEffect(() => {
    const onNavigate = (event: Event): void => {
      const detail = (event as CustomEvent<LiffUiNavigateDetail>).detail;
      if (detail?.path === undefined || detail.path === '') {
        return;
      }
      if (location.pathname !== detail.path) {
        navigate(detail.path, { replace: detail.replace === true });
      }
      syncLegacyCurrentTab(detail.path);
    };
    document.addEventListener(LIFF_UI_NAVIGATE, onNavigate);

    const app = window.app as AppWithSwitchTab | undefined;
    if (app === undefined || typeof app.switchTab !== 'function') {
      return () => {
        document.removeEventListener(LIFF_UI_NAVIGATE, onNavigate);
      };
    }

    const original = app.switchTab.bind(app);
    app.switchTab = (tab: string) => {
      const path = pathForLegacyTab(tab);
      if (path !== null) {
        app.currentTab = (tab === 'membership' || tab === 'profile') ? 'member' : tab;
        if (location.pathname !== path) {
          navigate(path);
        }
        document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
        return Promise.resolve();
      }
      return original(tab);
    };

    return () => {
      document.removeEventListener(LIFF_UI_NAVIGATE, onNavigate);
      app.switchTab = original;
    };
  }, [location.pathname, navigate]);
}
