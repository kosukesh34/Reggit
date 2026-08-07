import '../../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { LiffBootstrap } from './hooks/useLiffBootstrap';
import { FrontendChrome } from './components/FrontendChrome';
import { FrontendTabFooter, FrontendTabLayout } from './components/FrontendTabLayout';
import { AppConstants } from '../shared/constants';
import { ApiClient } from '../shared/utils/apiClient';
import { DomHelper } from '../shared/utils/domHelper';
import { ToastManager } from '../shared/utils/toast';
import { ConfirmDialog } from '../shared/utils/confirmDialog';
import { TooltipManager } from '../shared/utils/tooltip';
import JsBarcode from 'jsbarcode';
import { QRCodeCompat } from '../shared/utils/qrCodeCompat';
import { StripePayment } from '../shared/utils/stripePayment';

function exposeSharedGlobals(): void {
    window.AppConstants = AppConstants;
    window.ApiClient = ApiClient;
    window.DomHelper = DomHelper;
    window.ToastManager = ToastManager;
    window.ConfirmDialog = ConfirmDialog;
    window.TooltipManager = TooltipManager;
    window.JsBarcode = JsBarcode;
    window.QRCode = QRCodeCompat as unknown as typeof window.QRCode;
    window.StripePayment = StripePayment;
}

exposeSharedGlobals();

const mount = document.getElementById('react-frontend-root');
if (mount !== null) {
    createRoot(mount).render(<LiffBootstrap />);
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
