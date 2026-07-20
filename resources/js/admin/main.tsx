import React, { useEffect } from 'react';
import '../../css/app.css';
import '../../css/admin/liff-designer/react.css';
import { createRoot } from 'react-dom/client';
import { AdminSidebar } from './components/AdminSidebar';
import { exposeAdminLegacyGlobals } from '../shared/utils/exposeAdminLegacyGlobals';
import { LiffDesignerApp, readDesignerConfigFromDom } from './liff-designer/LiffDesignerApp';
import './admin';
import './couponBulkIssue';
import './memberDetailHistory';
import './liffBuilderPage';

function AdminBootstrap(): null {
    useEffect(() => {
        try {
            exposeAdminLegacyGlobals();
        } catch (error) {
            // eslint-disable-next-line no-console
            console.error('[admin-bootstrap] failed:', error);
        }
    }, []);

    return null;
}

function mountLiffDesigner(): void {
    const root = document.getElementById('liff-designer-root');
    if (root === null) {
        return;
    }
    const config = readDesignerConfigFromDom();
    createRoot(root).render(<LiffDesignerApp config={config} />);
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

if (document.body.dataset.pageKind === 'liff-designer') {
    mountLiffDesigner();
}
