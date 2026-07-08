const LEGACY_SCRIPT_PATHS = [
    '/js/vendor/JsBarcode.all.min.js',
    '/js/vendor/qrcode.min.js',
    '/js/shared/constants.js',
    '/js/shared/utils/apiClient.js',
    '/js/shared/utils/domHelper.js',
    '/js/shared/utils/tooltip.js',
    '/js/shared/utils/toast.js',
    '/js/shared/utils/confirmDialog.js',
    '/js/frontend/managers/CartManager.js',
    '/js/frontend/managers/ProductManager.js',
    '/js/frontend/managers/OrderManager.js',
    '/js/frontend/managers/DeviceManager.js',
    '/js/frontend/managers/MemberManager.js',
    '/js/frontend/managers/StampsManager.js',
    '/js/frontend/managers/CouponsManager.js',
    '/js/frontend/managers/QueueManager.js',
    '/js/frontend/managers/AnnouncementManager.js',
    '/js/frontend/managers/GalleryManager.js',
    '/js/frontend/managers/liff-blocks/render-premium-blocks.js',
    '/js/frontend/managers/LiffLayoutManager.js',
    '/js/frontend/managers/CheckoutManager.js',
    '/js/frontend/managers/ReservationManager.js',
    '/js/frontend/stripe.js',
    '/js/frontend/liff-init.js',
    '/js/frontend/app.js',
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

export async function loadLegacyFrontendScripts(): Promise<void> {
    if (window.__LEGACY_FRONTEND_SCRIPTS_LOADED__ === true) {
        return;
    }

    for (const path of LEGACY_SCRIPT_PATHS) {
        // Keep original load order to avoid regressions.
        // eslint-disable-next-line no-await-in-loop
        await appendScript(path);
    }

    window.__LEGACY_FRONTEND_SCRIPTS_LOADED__ = true;
}
