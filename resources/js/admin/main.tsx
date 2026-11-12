import { useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import { AdminSidebar } from './components/AdminSidebar';
import { exposeAdminLegacyGlobals } from '../shared/utils/exposeAdminLegacyGlobals';
import './admin';
import './couponForm';
import './announcementDelivery';
import './scheduledDelivery';
import './memberDetailHistory';
import './stampGrantQr';
import './issuedQr';
import './surveyBuilder';
import './promoPopupPreview';
import './queueRealtime';

function AdminBootstrap(): null {
  useEffect(() => {
    try {
      exposeAdminLegacyGlobals();
    } catch (error) {

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

// Firebase SDK はチャット画面だけで読み込む
if (document.getElementById('admin-chat-root') !== null) {
  void import('./chat').then(({ mountAdminChat }) => mountAdminChat());
}
