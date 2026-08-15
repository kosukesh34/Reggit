import React, { useEffect } from 'react';
import { AnnouncementView } from '../../features/announcements/AnnouncementView';
import { GalleryView } from '../../features/gallery/GalleryView';
import { MemberView } from '../../features/member/MemberView';
import { OrdersView } from '../../features/orders/OrdersView';
import { QueueView } from '../../features/queue/QueueView';
import { ReservationsView } from '../../features/reservations/ReservationsView';
import { ProductsGrid } from '../../features/products/ProductsGrid';
import { StampsView } from '../../features/stamps/StampsView';

type DataBlockMountProps = {
    source: string;
};

type AppLoaders = {
    couponsManager?: { load?: () => void | Promise<void> };
};

function runWhenAppReady(fn: (app: AppLoaders) => boolean): () => void {
    const tryRun = (): boolean => {
        const app = window.app as AppLoaders | undefined;
        if (app === undefined || app === null) {
            return false;
        }
        return fn(app);
    };
    if (tryRun()) {
        return () => undefined;
    }
    const onReady = (): void => {
        tryRun();
    };
    document.addEventListener('liff-ready', onReady);
    const timer = window.setInterval(() => {
        if (tryRun()) {
            window.clearInterval(timer);
        }
    }, 50);
    const stop = window.setTimeout(() => window.clearInterval(timer), 5000);
    return () => {
        document.removeEventListener('liff-ready', onReady);
        window.clearInterval(timer);
        window.clearTimeout(stop);
    };
}

function ProductsDataEmbed(): JSX.Element {
    return (
        <div className="products-data-mount" data-react-products="1">
            <ProductsGrid embed />
        </div>
    );
}

function MountAndLoad({ className, load }: { className: string; load: (app: AppLoaders) => boolean }): JSX.Element {
    useEffect(() => runWhenAppReady(load), [load]);
    return <div className={className} />;
}

const loadCoupons = (app: AppLoaders): boolean => {
    if (typeof app.couponsManager?.load !== 'function') {
        return false;
    }
    void app.couponsManager.load();
    return true;
};

export function DataBlockMount({ source }: DataBlockMountProps): JSX.Element | null {
    if (source === 'products') {
        return <ProductsDataEmbed />;
    }
    if (source === 'coupons') {
        return <MountAndLoad className="coupons-data-mount" load={loadCoupons} />;
    }
    if (source === 'stamps') {
        return (
            <div className="stamps-data-mount" data-react-owned="1">
                <StampsView />
            </div>
        );
    }
    if (source === 'orders') {
        return (
            <div className="orders-data-mount" data-react-owned="1">
                <OrdersView />
            </div>
        );
    }
    if (source === 'queue') {
        return (
            <div className="queue-data-mount" data-react-owned="1">
                <QueueView />
            </div>
        );
    }
    if (source === 'member' || source === 'member_card' || source === 'profile') {
        return (
            <div className="member-section member-card-mount" data-react-owned="1">
                <MemberView />
            </div>
        );
    }
    if (source === 'gallery') {
        return (
            <div className="gallery-data-mount" data-react-owned="1">
                <GalleryView embed />
            </div>
        );
    }
    if (source === 'announcements') {
        return (
            <div className="announcements-data-mount" data-react-owned="1">
                <AnnouncementView />
            </div>
        );
    }
    if (source === 'reservations') {
        return (
            <div className="reservations-data-mount reservations-section" data-react-owned="1">
                <ReservationsView />
            </div>
        );
    }
    return null;
}
