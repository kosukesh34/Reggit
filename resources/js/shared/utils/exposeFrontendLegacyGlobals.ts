import { AppConstants } from '../constants';
import { ApiClient } from './apiClient';
import { DomHelper } from './domHelper';
import { ToastManager } from './toast';
import { ConfirmDialog } from './confirmDialog';
import { TooltipManager } from './tooltip';
import JsBarcode from 'jsbarcode';
import { QRCodeCompat } from './qrCodeCompat';
import { AnnouncementManager } from '../../frontend/managers/AnnouncementManager';
import { GalleryManager } from '../../frontend/managers/GalleryManager';
import { CartManager } from '../../frontend/managers/CartManager';
import { ProductManager } from '../../frontend/managers/ProductManager';
import { OrderManager } from '../../frontend/managers/OrderManager';
import { DeviceManager } from '../../frontend/managers/DeviceManager';
import { StampsManager } from '../../frontend/managers/StampsManager';
import { CheckoutManager } from '../../frontend/managers/CheckoutManager';
import { QueueManager } from '../../frontend/managers/QueueManager';
import { CouponsManager } from '../../frontend/managers/CouponsManager';
import { LiffLayoutManager } from '../../frontend/managers/LiffLayoutManager';
import { ReservationManager } from '../../frontend/managers/ReservationManager';
import { MemberManager } from '../../frontend/managers/MemberManager';
import { StripePayment } from './stripePayment';
import '../../frontend/managers/liff-blocks/renderPremiumBlocks';
import './liffInit';

/**
 * Expose ES modules on window for runtime globals used by inline handlers
 * and remaining non-bundled integrations.
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
    window.AnnouncementManager = AnnouncementManager;
    window.GalleryManager = GalleryManager;
    window.CartManager = CartManager;
    window.ProductManager = ProductManager;
    window.OrderManager = OrderManager;
    window.DeviceManager = DeviceManager;
    window.StampsManager = StampsManager;
    window.CheckoutManager = CheckoutManager;
    window.QueueManager = QueueManager;
    window.CouponsManager = CouponsManager;
    window.LiffLayoutManager = LiffLayoutManager;
    window.ReservationManager = ReservationManager;
    window.MemberManager = MemberManager;
    window.StripePayment = StripePayment;
}
