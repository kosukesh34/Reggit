import '../../css/app.css';
import './app';
import { createRoot } from 'react-dom/client';
import { useLegacyBootstrap } from './hooks/useLegacyBootstrap';
import { FrontendChrome } from './components/FrontendChrome';
import { FrontendTabFooter, FrontendTabLayout } from './components/FrontendTabLayout';

function FrontendBootstrap(): null {
    useLegacyBootstrap();
    return null;
}

const mount = document.getElementById('react-frontend-root');
if (mount !== null) {
    createRoot(mount).render(<FrontendBootstrap />);
}

const tabsRoot = document.getElementById('react-frontend-tabs-root');
if (tabsRoot !== null) {
    createRoot(tabsRoot).render(<FrontendTabLayout />);
}

const chromeRoot = document.getElementById('react-frontend-chrome-root');
if (chromeRoot !== null) {
    createRoot(chromeRoot).render(<FrontendChrome />);
}

const footerRoot = document.getElementById('react-frontend-footer-root');
if (footerRoot !== null) {
    createRoot(footerRoot).render(<FrontendTabFooter />);
}
