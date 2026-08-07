import { AppConstants } from '../constants';
import { ApiClient } from './apiClient';
import { DomHelper } from './domHelper';
import { ToastManager } from './toast';
import { ConfirmDialog } from './confirmDialog';
import { TooltipManager } from './tooltip';
import JsBarcode from 'jsbarcode';
import { QRCodeCompat } from './qrCodeCompat';
import { StripePayment } from './stripePayment';

/**
 * 共有ユーティリティを window へ露出（レガシー inline / 外部連携用）。
 * Manager クラスは React へ移行済みのため露出しない。
 */
export function exposeFrontendLegacyGlobals(): void {
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
