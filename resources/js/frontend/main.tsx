import './liffUiFlag';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import '../../css/app.css';
import './app';
import { CheckoutModal } from './features/cart/CheckoutModal';
import { App } from '../liff-ui/app/App';
import { normalizeLegacyLocationHash } from '../liff-ui/navigation/tabPath';
import { captureAcquisitionSource } from '../shared/utils/acquisitionSource';

captureAcquisitionSource();
normalizeLegacyLocationHash();

const mountNode = document.getElementById('app-container') ?? document.getElementById('react-frontend-root');
function mountApp(): void {
if (mountNode !== null) {
  mountNode.replaceChildren();
  mountNode.classList.add('reggit-liff-ui-root');
  createRoot(mountNode).render(
    <React.StrictMode>
      <HashRouter>
        <App checkout={<CheckoutModal />} />
      </HashRouter>
    </React.StrictMode>,
  );
}

}
if (document.querySelector('meta[name="line-login-required"]') === null || (window.__LIFF_READY__ && window.__LINE_USER_ID__)) {
  mountApp();
} else {
  document.addEventListener('liff-ready', () => {
    if (window.__LINE_USER_ID__) mountApp();
  }, { once: true });
}

import "../liff-ui/styles/native.css";
