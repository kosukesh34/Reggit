import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { LIFF_UI_NAVIGATE, pathForLegacyTab, type LiffUiNavigateDetail } from './tabPath';

type AppWithSwitchTab = {
  switchTab?: (tab: string) => unknown;
};

/**
 * 旧 Managers の switchTab / hash 遷移を HashRouter に橋渡しする。
 */
export function useLegacyTabBridge(): void {
  const navigate = useNavigate();

  useEffect(() => {
    const onNavigate = (event: Event): void => {
      const detail = (event as CustomEvent<LiffUiNavigateDetail>).detail;
      if (detail?.path === undefined || detail.path === '') {
        return;
      }
      navigate(detail.path, { replace: detail.replace === true });
      document.body.classList.toggle('liff-lottery-ui', detail.path === '/designlab');
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
        navigate(path);
        document.body.classList.toggle('liff-lottery-ui', tab === 'designlab');
        return Promise.resolve();
      }
      return original(tab);
    };

    return () => {
      document.removeEventListener(LIFF_UI_NAVIGATE, onNavigate);
      app.switchTab = original;
    };
  }, [navigate]);
}
