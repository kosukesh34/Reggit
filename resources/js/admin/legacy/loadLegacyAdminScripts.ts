const COMMON_ADMIN_SCRIPTS = [
    '/js/shared/utils/confirmDialog.js',
    '/js/admin/admin.js',
];

const LIFF_DESIGNER_SCRIPTS = [
    '/js/admin/liff-designer/modules/block-designs.js',
    '/js/admin/liff-designer/modules/data-preview.js',
    '/js/admin/liff-designer/modules/theme-panel.js',
    '/js/admin/liff-designer/modules/template-preview.js',
    '/js/admin/liff-designer/modules/template-manager.js',
    '/js/admin/liff-designer/modules/drag-drop.js',
    '/js/admin/liff-designer/modules/ui-events.js',
    '/js/admin/liff-designer/modules/io.js',
    '/js/admin/liff-designer/modules/onboarding.js',
    '/js/admin/liff-designer/modules/panel-resizer.js',
    '/js/admin/liff-designer/modules/panel-drawers.js',
    '/js/admin/liff-designer.js',
];

const COUPON_BULK_ISSUE_SCRIPTS = [
    '/js/admin/coupon-bulk-issue.js',
];

function appendScript(path: string): Promise<void> {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[data-legacy-path="${path}"]`);
        if (existing !== null) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = path;
        script.async = false;
        script.dataset.legacyPath = path;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${path}`));
        document.body.appendChild(script);
    });
}

function getPageSpecificScripts(): string[] {
    const body = document.body;
    if (body.dataset.pageKind === 'liff-designer') {
        return LIFF_DESIGNER_SCRIPTS;
    }

    if (body.dataset.pageKind === 'coupon-bulk-issue') {
        return COUPON_BULK_ISSUE_SCRIPTS;
    }

    return [];
}

export async function loadLegacyAdminScripts(): Promise<void> {
    if (window.__LEGACY_ADMIN_SCRIPTS_LOADED__ === true) {
        return;
    }

    const allScripts = [...COMMON_ADMIN_SCRIPTS, ...getPageSpecificScripts()];
    for (const path of allScripts) {
        // Keep original load order.
        // eslint-disable-next-line no-await-in-loop
        await appendScript(path);
    }

    window.__LEGACY_ADMIN_SCRIPTS_LOADED__ = true;
}
