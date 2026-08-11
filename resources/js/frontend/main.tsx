import './liffUiFlag';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { HashRouter } from 'react-router-dom';
import '../../css/app.css';
import './app';
import { CheckoutModal } from './features/cart/CheckoutModal';
import { App } from '../liff-ui/app/App';
import '../liff-ui/styles/tokens.css';
import '../liff-ui/styles/globals.css';
import '../../css/frontend/member-legacy.css';

const mountNode = document.getElementById('app-container') ?? document.getElementById('react-frontend-root');
if (mountNode !== null) {
  mountNode.replaceChildren();
  mountNode.classList.add('reggit-liff-ui-root');
  createRoot(mountNode).render(
    <React.StrictMode>
      <HashRouter>
        <App />
        <CheckoutModal />
      </HashRouter>
    </React.StrictMode>,
  );
}
