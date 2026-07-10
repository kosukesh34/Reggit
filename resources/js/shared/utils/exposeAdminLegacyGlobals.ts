import { ConfirmDialog } from './confirmDialog';
import { ToastManager } from './toast';

export function exposeAdminLegacyGlobals(): void {
    window.ConfirmDialog = ConfirmDialog;
    window.ToastManager = ToastManager;
}
