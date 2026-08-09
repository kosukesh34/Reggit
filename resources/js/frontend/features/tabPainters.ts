type AppPaintTarget = NonNullable<Window['app']> & {
    loadProducts?: () => void | Promise<void>;
    loadOrdersTab?: () => void | Promise<void>;
    loadMemberTab?: () => void | Promise<void>;
    checkMembershipAndLoadReservations?: () => void | Promise<void>;
    couponsManager?: { load?: () => void | Promise<void> };
    stampsManager?: { load?: () => void | Promise<void> };
    queueManager?: { load?: () => void | Promise<void> };
    galleryManager?: { load?: () => void | Promise<void> };
    announcementManager?: { load?: () => void | Promise<void> };
    designLabManager?: { load?: () => void | Promise<void> };
};

function callLoad(target: { load?: () => void | Promise<void> } | undefined): void {
    if (target !== undefined && typeof target.load === 'function') {
        void target.load();
    }
}

export function paintCoupons(app: AppPaintTarget): void {
    callLoad(app.couponsManager);
}

export function paintStamps(app: AppPaintTarget): void {
    callLoad(app.stampsManager);
}

export function paintQueue(app: AppPaintTarget): void {
    callLoad(app.queueManager);
}

export function paintOrders(app: AppPaintTarget): void {
    if (typeof app.loadOrdersTab === 'function') {
        void app.loadOrdersTab();
    }
}

export function paintMember(app: AppPaintTarget): void {
    if (typeof app.loadMemberTab === 'function') {
        void app.loadMemberTab();
    }
}

export function paintReservations(app: AppPaintTarget): void {
    if (typeof app.checkMembershipAndLoadReservations === 'function') {
        void app.checkMembershipAndLoadReservations();
    }
}

export function paintGallery(app: AppPaintTarget): void {
    callLoad(app.galleryManager);
}

export function paintAnnouncements(app: AppPaintTarget): void {
    callLoad(app.announcementManager);
}

export function paintDesignLab(app: AppPaintTarget): void {
    callLoad(app.designLabManager);
}
