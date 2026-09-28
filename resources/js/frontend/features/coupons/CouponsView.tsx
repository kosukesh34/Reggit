import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { applyDemoCouponUsageState, readDemoWonCoupons } from '../../demo/demoCoupons';
import { isDemoDataMode, isFeatureUiShell } from '../../demo/demoMode';
import { ensureLiffIdentityForLoad } from '../../demo/ensureIdentity';
import { isMembershipRequired } from '../../utils/membershipPolicy';
import { LIFF_COUPONS_RELOAD, LIFF_COUPONS_UPDATED } from '../products/types';
import { subscribeProjectDataUpdates } from '../../realtime/liffEcho';
import {
    type CouponRecord,
    formatCouponDate,
    getDaysUntil,
    groupCoupons,
    isRecentlyIssued,
    normalizeCouponImageFit,
    normalizeCouponStatus,
    normalizeUseDisplay,
    sortHistoryCoupons,
    sortVisibleCoupons,
} from './couponHelpers';

type CouponsManagerLike = {
    getDesignerSampleCoupons: () => CouponRecord[];
    getDefaultCouponDesign: () => string;
    openDetail: (id: number | string) => Promise<void>;
    useCouponFromUi?: (opts: {
        memberCouponId: string;
        couponName: string;
        useDisplay: string;
        code: string;
    }) => Promise<void>;
    cache?: CouponRecord[];
    lastDefaultImage?: string | null;
};

type LoadState = 'idle' | 'loading' | 'ready' | 'login' | 'error';

type DeviceCoupon = {
    id: number;
    name: string;
    description?: string;
    image_url?: string | null;
    valid_until?: string | null;
};

const COUPON_FORM_PREVIEW_ID = '__reggit_coupon_form_preview__';

function adaptCouponMediaRatio(image: HTMLImageElement): void {
    const media = image.parentElement;
    if (media === null || image.naturalWidth <= 0 || image.naturalHeight <= 0) return;
    const ratio = Math.min(3.2, Math.max(0.6, image.naturalWidth / image.naturalHeight));
    media.style.setProperty('--coupon-media-aspect', String(ratio));
}

const STATUS_LABELS: Record<string, string> = {
    available: '利用可能',
    upcoming: '開始前',
    used: '使用済み',
    expired: '期限切れ',
};

function getManager(): CouponsManagerLike | null {
    const app = window.app as { couponsManager?: CouponsManagerLike } | undefined;
    return app?.couponsManager ?? null;
}

async function fetchCoupons(): Promise<{
    state: LoadState;
    coupons: CouponRecord[];
    defaultImage: string | null;
    message: string;
}> {
    const manager = getManager();
    if (isDemoDataMode()) {
        const samples = manager?.getDesignerSampleCoupons() ?? [];
        const coupons = applyDemoCouponUsageState([...readDemoWonCoupons(), ...samples]);
        return { state: 'ready', coupons, defaultImage: null, message: '' };
    }

    const canCallApi = await ensureLiffIdentityForLoad({ waitNativeDevice: true });
    if (!canCallApi) {
        if (isDemoDataMode() || !isMembershipRequired()) {
            const samples = manager?.getDesignerSampleCoupons() ?? [];
            return {
                state: 'ready',
                coupons: applyDemoCouponUsageState([...readDemoWonCoupons(), ...samples]),
                defaultImage: null,
                message: '',
            };
        }
        return { state: 'login', coupons: [], defaultImage: null, message: '' };
    }

    const apiClient = (window.app as { apiClient?: { get: (url: string) => Promise<{ success?: boolean; error?: string; data?: unknown }> } } | undefined)?.apiClient;
    if (apiClient === undefined) {
        return { state: 'error', coupons: [], defaultImage: null, message: 'APIクライアントが未初期化です' };
    }

    const result = await apiClient.get(AppConstants.API_ENDPOINTS.MEMBER_COUPONS);
    if (result.success !== true) {
        return {
            state: 'error',
            coupons: [],
            defaultImage: null,
            message: result.error || 'クーポンの読み込みに失敗しました',
        };
    }
    const data = result.data as {
        coupons?: CouponRecord[];
        default_image_url?: string | null;
        message?: string;
    } | null;
    const coupons = data?.coupons ?? [];
    const defaultImage = data?.default_image_url ?? null;
    const message = data?.message || '';
    if (coupons.length === 0 && message !== '' && !isMembershipRequired()) {
        const samples = manager?.getDesignerSampleCoupons() ?? [];
        return {
            state: 'ready',
            coupons: applyDemoCouponUsageState([...readDemoWonCoupons(), ...samples]),
            defaultImage,
            message: '',
        };
    }
    if (manager !== null) {
        manager.cache = coupons;
        manager.lastDefaultImage = defaultImage;
    }
    return { state: 'ready', coupons, defaultImage, message };
}

function openDetail(id: number | string): void {
    if (String(id) === COUPON_FORM_PREVIEW_ID) {
        return;
    }
    const manager = getManager();
    if (manager !== null) {
        void manager.openDetail(id);
    }
}

function openCouponUse(opts: {
    memberCouponId: string;
    couponName: string;
    useDisplay: string;
    code: string;
}): void {
    if (opts.memberCouponId === COUPON_FORM_PREVIEW_ID) {
        return;
    }
    const manager = getManager();
    if (manager !== null && typeof manager.useCouponFromUi === 'function') {
        void manager.useCouponFromUi(opts);
    }
}

function ClassicCouponCard({
    cp,
    defaultImage,
}: {
    cp: CouponRecord;
    defaultImage: string | null;
}): JSX.Element {
    const status = normalizeCouponStatus(cp.status);
    const manager = getManager();
    const design = cp.design_template || manager?.getDefaultCouponDesign() || 'ticket';
    const themeColor = cp.theme_color && /^#[0-9A-Fa-f]{6}$/.test(cp.theme_color) ? cp.theme_color : '';
    const label = STATUS_LABELS[status] || status;
    const validFrom = formatCouponDate(cp.valid_from);
    const validUntil = formatCouponDate(cp.valid_until);
    const issuedAt = formatCouponDate(cp.issued_at);
    const usedAt = formatCouponDate(cp.used_at);
    const daysUntilExpiry = status === 'available' ? getDaysUntil(cp.valid_until) : null;
    let validText = '';
    if (validFrom && validUntil) validText = `${validFrom} - ${validUntil}`;
    else if (validUntil) validText = `有効期限: ${validUntil}`;
    else if (validFrom) validText = `${validFrom} から利用可能`;
    const imageUrl = cp.image_url || defaultImage;
    const imageFit = normalizeCouponImageFit(cp.image_fit);
    const iconClass = design === 'barcode' ? 'fa-barcode' : design === 'qr' ? 'fa-qrcode' : 'fa-ticket-alt';
    const code = (cp.code && String(cp.code).trim()) ? cp.code : '';
    const useDisplay = normalizeUseDisplay(cp.use_display, code);
    const canShowCode = useDisplay === 'barcode' || useDisplay === 'qr' || useDisplay === 'both';
    const showUseButton = status === 'available' && (canShowCode || code !== '');
    const showNewBadge = status === 'available' && isRecentlyIssued(cp.issued_at);
    const style = themeColor ? ({ ['--coupon-accent' as string]: themeColor } as React.CSSProperties) : undefined;

    let imageBlock: JSX.Element;
    if (design === 'barcode') {
        imageBlock = (
            <div className="coupon-item-barcode">
                <div className="coupon-item-barcode-lines" />
                <span className="coupon-item-barcode-code">{cp.coupon_name || 'クーポン'}</span>
            </div>
        );
    } else if (design === 'qr') {
        imageBlock = (
            <div className="coupon-item-qr">
                <div className="coupon-item-qr-grid" />
            </div>
        );
    } else if (imageUrl) {
        imageBlock = (
            <div className={`coupon-item-image coupon-item-image--fit-${imageFit}`}>
                <img src={imageUrl} alt="クーポン" onLoad={(event) => adaptCouponMediaRatio(event.currentTarget)} />
            </div>
        );
    } else {
        imageBlock = (
            <div className="coupon-item-image coupon-item-image--icon">
                <i className={`fas ${iconClass}`} aria-hidden="true" />
            </div>
        );
    }

    return (
        <div
            className={`coupon-item coupon-item--${status} coupon-item--design-${design} coupon-item--link`}
            data-coupon-id={String(cp.id)}
            style={style}
            role="button"
            tabIndex={0}
            onClick={(e) => {
                if ((e.target as HTMLElement).closest('.coupon-use-btn')) {
                    return;
                }
                openDetail(cp.id);
            }}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openDetail(cp.id);
                }
            }}
        >
            {imageBlock}
            <div className="coupon-item-header">
                <span className="coupon-item-name">
                    <i className={`fas ${iconClass} coupon-item-name-icon`} aria-hidden="true" />
                    {cp.coupon_name || 'クーポン'}
                    {showNewBadge ? <span className="coupon-item-badge coupon-item-badge--new">NEW</span> : null}
                    {daysUntilExpiry !== null && daysUntilExpiry >= 0 && daysUntilExpiry <= 7 ? (
                        <span className="coupon-item-badge coupon-item-badge--urgent">あと{daysUntilExpiry}日</span>
                    ) : null}
                </span>
                <span className={`coupon-item-status coupon-item-status--${status}`}>{label}</span>
            </div>
            {validText !== '' ? (
                <p className={`coupon-item-valid${daysUntilExpiry !== null && daysUntilExpiry >= 0 && daysUntilExpiry <= 7 ? ' coupon-item-valid--urgent' : ''}`}>
                    {validText}
                </p>
            ) : null}
            {cp.description && String(cp.description).trim() !== '' ? (
                <p className="coupon-item-description">{cp.description}</p>
            ) : null}
            {(issuedAt || (status === 'used' && usedAt)) ? (
                <div className="coupon-item-meta">
                    {status === 'used' && usedAt ? (
                        <span>
                            <i className="fas fa-check" aria-hidden="true" /> 使用日: {usedAt}
                        </span>
                    ) : null}
                    {issuedAt ? (
                        <span>
                            <i className="fas fa-plus-circle" aria-hidden="true" /> 発行日: {issuedAt}
                        </span>
                    ) : null}
                </div>
            ) : null}
            {status === 'upcoming' && validFrom !== '' ? (
                <p className="coupon-item-unavailable">
                    <i className="fas fa-clock" aria-hidden="true" />
                    {validFrom}から利用できます
                </p>
            ) : null}
            {showUseButton ? (
                <div className="coupon-item-actions">
                    <button
                        type="button"
                        className="btn btn-primary coupon-use-btn"
                        onClick={(e) => {
                            e.stopPropagation();
                            openCouponUse({
                                memberCouponId: String(cp.id),
                                couponName: cp.coupon_name || 'クーポン',
                                useDisplay,
                                code,
                            });
                        }}
                    >
                        <i className="fas fa-hand-holding-usd me-1" aria-hidden="true" />
                        使用する
                    </button>
                </div>
            ) : null}
        </div>
    );
}

function FixedCouponCard({
    cp,
    defaultImage,
    index,
}: {
    cp: CouponRecord;
    defaultImage: string | null;
    index: number;
}): JSX.Element {
    const status = normalizeCouponStatus(cp.status);
    const label = STATUS_LABELS[status] || status;
    const validUntil = formatCouponDate(cp.valid_until);
    const daysUntilExpiry = status === 'available' ? getDaysUntil(cp.valid_until) : null;
    const fallbackImages = [
        '/images/liff/coupon-drink.jpg',
        '/images/liff/coupon-dessert.jpg',
        '/images/liff/product-placeholder.jpg',
    ];
    const imageUrl = cp.image_url || defaultImage || fallbackImages[index % fallbackImages.length];
    const imageFit = normalizeCouponImageFit(cp.image_fit);
    const code = (cp.code && String(cp.code).trim()) ? cp.code : '';
    const useDisplay = normalizeUseDisplay(cp.use_display, code);
    const canShowCode = useDisplay === 'barcode' || useDisplay === 'qr' || useDisplay === 'both';
    const showUseButton = status === 'available' && (canShowCode || code !== '');
    const showNewBadge = status === 'available' && isRecentlyIssued(cp.issued_at);
    const expiryText = daysUntilExpiry !== null && daysUntilExpiry >= 0 && daysUntilExpiry <= 7
        ? `あと${daysUntilExpiry}日`
        : (validUntil ? `〜${validUntil}` : '');
    const desc = (cp.description && String(cp.description).trim()) ? String(cp.description).trim() : '';

    return (
        <article
            className={`ios-coupon ios-coupon--${status}`}
            data-coupon-id={String(cp.id)}
            role="button"
            tabIndex={0}
            onClick={(e) => {
                if ((e.target as HTMLElement).closest('.coupon-use-btn')) {
                    return;
                }
                openDetail(cp.id);
            }}
            onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openDetail(cp.id);
                }
            }}
        >
            <div className={`ios-coupon__media ios-coupon__media--fit-${imageFit}`}>
                <img
                    src={imageUrl}
                    alt=""
                    loading="lazy"
                    onLoad={(event) => adaptCouponMediaRatio(event.currentTarget)}
                    onError={(e) => {
                        e.currentTarget.src = '/images/liff/product-placeholder.jpg';
                    }}
                />
                <div className="ios-coupon__media-shade" />
                <div className="ios-coupon__chips">
                    <span className="ios-coupon__status">{label}</span>
                    {showNewBadge ? <span className="ios-coupon__chip ios-coupon__chip--new">NEW</span> : null}
                    {expiryText !== '' ? <span className="ios-coupon__chip">{expiryText}</span> : null}
                </div>
            </div>
            <div className="ios-coupon__body">
                <h3 className="ios-coupon__title">{cp.coupon_name || 'クーポン'}</h3>
                {desc !== '' ? <p className="ios-coupon__desc">{desc}</p> : null}
                {showUseButton ? (
                    <button
                        type="button"
                        className="ios-coupon__use coupon-use-btn"
                        onClick={(e) => {
                            e.stopPropagation();
                            openCouponUse({
                                memberCouponId: String(cp.id),
                                couponName: cp.coupon_name || 'クーポン',
                                useDisplay,
                                code,
                            });
                        }}
                    >
                        クーポンを使う
                    </button>
                ) : (
                    <span className="ios-coupon__hint">タップして詳細を見る</span>
                )}
            </div>
        </article>
    );
}

type CouponsViewProps = {
    className?: string;
};

export function CouponsView({ className = 'coupons-data-mount' }: CouponsViewProps): JSX.Element {
    const fixed = isFeatureUiShell();
    const isCouponFormPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true
        && window.__COUPON_FORM_PREVIEW__ === true;
    const [loadState, setLoadState] = useState<LoadState>('idle');
    const [coupons, setCoupons] = useState<CouponRecord[]>([]);
    const [deviceCoupons, setDeviceCoupons] = useState<DeviceCoupon[]>([]);
    const [claimingDeviceCouponId, setClaimingDeviceCouponId] = useState<number | null>(null);
    const [formPreviewCoupon, setFormPreviewCoupon] = useState<CouponRecord | null>(null);
    const [defaultImage, setDefaultImage] = useState<string | null>(null);
    const [errorMessage, setErrorMessage] = useState('');
    const [activeTab, setActiveTab] = useState<'available' | 'history'>('available');

    const reload = useCallback(async (): Promise<boolean> => {
        if (window.app?.couponsManager === undefined) {
            return false;
        }
        setLoadState('loading');
        try {
            const result = await fetchCoupons();
            let deviceOffers: DeviceCoupon[] = [];
            const apiClient = window.app?.apiClient as { get?: (url: string) => Promise<{ success?: boolean; data?: unknown }> } | undefined;
            if (apiClient?.get !== undefined) {
                const available = await apiClient.get(AppConstants.API_ENDPOINTS.DEVICE_COUPONS);
                const payload = available.data as { data?: { coupons?: DeviceCoupon[] }; coupons?: DeviceCoupon[] } | undefined;
                deviceOffers = payload?.data?.coupons ?? payload?.coupons ?? [];
                setDeviceCoupons(deviceOffers);
            }
            setCoupons(result.coupons);
            setDefaultImage(result.defaultImage);
            setErrorMessage(result.message);
            setLoadState(result.state);
            const groups = groupCoupons(result.coupons);
            const visible = sortVisibleCoupons(groups.available.concat(groups.upcoming));
            setActiveTab(visible.length > 0 || deviceOffers.length > 0 ? 'available' : 'history');
            document.dispatchEvent(new CustomEvent(LIFF_COUPONS_UPDATED, {
                detail: { coupons: result.coupons, defaultImage: result.defaultImage },
            }));
        } catch (error) {
            setErrorMessage(error instanceof Error ? error.message : 'クーポンの読み込みに失敗しました');
            setLoadState('error');
        }
        return true;
    }, []);

    const claimDeviceCoupon = useCallback(async (coupon: DeviceCoupon): Promise<void> => {
        const apiClient = window.app?.apiClient as { post?: (url: string, body: unknown) => Promise<{ success?: boolean; error?: string }> } | undefined;
        if (apiClient?.post === undefined || claimingDeviceCouponId !== null) return;
        setClaimingDeviceCouponId(coupon.id);
        const result = await apiClient.post(AppConstants.API_ENDPOINTS.DEVICE_COUPON_CLAIM(coupon.id), {});
        setClaimingDeviceCouponId(null);
        if (result.success !== true) {
            window.alert(result.error || 'クーポンを獲得できませんでした');
            return;
        }
        await reload();
    }, [claimingDeviceCouponId, reload]);

    const deviceCouponPanel = deviceCoupons.length > 0 ? (
        <section className="device-coupon-panel" aria-label="この端末で獲得できるクーポン">
            <h3><i className="fas fa-gift" aria-hidden="true" /> この端末で獲得できるクーポン</h3>
            {deviceCoupons.map((coupon) => (
                <div className="device-coupon-offer" key={coupon.id}>
                    <div>
                        <strong>{coupon.name}</strong>
                        <small>{coupon.description || '1端末につき1回だけ獲得できます'}</small>
                    </div>
                    <button type="button" className="btn btn-primary" disabled={claimingDeviceCouponId !== null} onClick={() => { void claimDeviceCoupon(coupon); }}>
                        {claimingDeviceCouponId === coupon.id ? '獲得中…' : '獲得'}
                    </button>
                </div>
            ))}
        </section>
    ) : null;

    useEffect(() => {
        let cancelled = false;
        const tryLoad = (): boolean => {
            if (cancelled) {
                return true;
            }
            void reload();
            return window.app?.couponsManager !== undefined;
        };
        if (!tryLoad()) {
            const onReady = (): void => {
                void reload();
            };
            document.addEventListener('liff-ready', onReady);
            const timer = window.setInterval(() => {
                if (tryLoad()) {
                    window.clearInterval(timer);
                }
            }, 50);
            const stop = window.setTimeout(() => window.clearInterval(timer), 5000);
            return () => {
                cancelled = true;
                document.removeEventListener('liff-ready', onReady);
                window.clearInterval(timer);
                window.clearTimeout(stop);
            };
        }
        const onReload = (): void => {
            void reload();
        };
        const unsubscribeRealtime = subscribeProjectDataUpdates((payload) => {
            if (payload.topic === 'coupons' || payload.topic === 'all') {
                void reload();
            }
        });
        document.addEventListener(LIFF_COUPONS_RELOAD, onReload);
        return () => {
            cancelled = true;
            unsubscribeRealtime();
            document.removeEventListener(LIFF_COUPONS_RELOAD, onReload);
        };
    }, [reload]);

    useEffect(() => {
        if (!isCouponFormPreview) {
            return undefined;
        }

        const receiveCouponPreview = (event: MessageEvent): void => {
            if (event.origin !== window.location.origin || event.data?.type !== 'reggit:coupon-form-preview:update') {
                return;
            }
            const incoming = event.data.coupon as Partial<CouponRecord> | undefined;
            if (incoming === undefined || incoming === null || typeof incoming !== 'object') {
                return;
            }
            setFormPreviewCoupon({
                ...incoming,
                id: COUPON_FORM_PREVIEW_ID,
                coupon_name: typeof incoming.coupon_name === 'string' ? incoming.coupon_name : 'クーポン名が入ります',
                description: typeof incoming.description === 'string' ? incoming.description : '',
                status: incoming.status === 'upcoming' ? 'upcoming' : 'available',
            });
            setActiveTab('available');
        };

        window.addEventListener('message', receiveCouponPreview);
        if (window.parent !== window) {
            window.parent.postMessage({ type: 'reggit:coupon-form-preview:ready' }, window.location.origin);
        }
        return () => window.removeEventListener('message', receiveCouponPreview);
    }, [isCouponFormPreview]);

    const displayCoupons = useMemo(
        () => formPreviewCoupon !== null ? [formPreviewCoupon] : coupons,
        [coupons, formPreviewCoupon],
    );
    const groups = useMemo(() => groupCoupons(displayCoupons), [displayCoupons]);
    const visibleCoupons = useMemo(
        () => sortVisibleCoupons(groups.available.concat(groups.upcoming)),
        [groups],
    );
    const historyCoupons = useMemo(
        () => sortHistoryCoupons(groups.used.concat(groups.expired)),
        [groups],
    );

    if (formPreviewCoupon === null && (loadState === 'loading' || loadState === 'idle')) {
        return (
            <div className={className} data-react-coupons="1">
                <div className="loading">
                    <i className="fas fa-spinner fa-spin" aria-hidden="true" />
                    <p>読み込み中...</p>
                </div>
            </div>
        );
    }

    if (formPreviewCoupon === null && loadState === 'login') {
        const isMobile = window.__MOBILE_APP_MODE__ === true;
        return (
            <div className={className} data-react-coupons="1">
                <div className="member-card-container">
                    <div className="member-card member-card--line-only">
                        <div className="member-card-header">
                            <h2 className="member-card-title">
                                <i className="fas fa-ticket-alt" aria-hidden="true" /> マイクーポン
                            </h2>
                        </div>
                        <div className="member-card-line-only-body">
                            <p className="member-card-line-only-message">
                                {isMobile
                                    ? '会員登録するとクーポンをご利用いただけます。'
                                    : 'ログインするとクーポンをご利用いただけます。'}
                            </p>
                            {isMobile ? (
                                <button
                                    type="button"
                                    className="btn btn-primary mt-2"
                                    onClick={() => window.app?.switchTab?.(AppConstants.TABS.MEMBER)}
                                >
                                    <i className="fas fa-user-plus" aria-hidden="true" /> 会員登録へ
                                </button>
                            ) : null}
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    if (formPreviewCoupon === null && loadState === 'error') {
        return (
            <div className={className} data-react-coupons="1">
                <div className="coupons-error">
                    <i className="fas fa-exclamation-circle" aria-hidden="true" />
                    <p>{errorMessage || 'クーポンの読み込みに失敗しました'}</p>
                    <button type="button" className="btn btn-secondary" onClick={() => { void reload(); }}>
                        <i className="fas fa-redo" aria-hidden="true" /> 再読み込み
                    </button>
                </div>
            </div>
        );
    }

    if (displayCoupons.length === 0 && deviceCoupons.length === 0) {
        if (fixed) {
            return (
                <div className={className} data-react-coupons="1">
                    <div className="ios-empty">
                        <div className="ios-empty__icon">
                            <i className="fas fa-ticket-alt" aria-hidden="true" />
                        </div>
                        <h3 className="ios-empty__title">クーポンはありません</h3>
                        <p className="ios-empty__text">お店から発行されるとここに表示されます</p>
                    </div>
                </div>
            );
        }
        return (
            <div className={className} data-react-coupons="1">
                <div className="coupons-empty">
                    <i className="fas fa-ticket-alt" aria-hidden="true" />
                    <p>クーポンはありません</p>
                    <p className="coupons-empty-sub">お店から発行されるとここに表示されます</p>
                </div>
            </div>
        );
    }

    if (fixed) {
        return (
            <div className={className} data-react-coupons="1">
                <div className="ios-panel-head">
                    <h2 className="ios-panel-head__title">クーポン</h2>
                    <p className="ios-panel-head__sub">使えるお得な特典</p>
                </div>
                <div className="ios-seg" role="tablist" aria-label="クーポン一覧">
                    <button
                        type="button"
                        className={`ios-seg__btn coupons-list-tab${activeTab === 'available' ? ' is-active' : ''}`}
                        role="tab"
                        data-tab="available"
                        aria-selected={activeTab === 'available'}
                        onClick={() => setActiveTab('available')}
                    >
                        利用可能 <span>{visibleCoupons.length}</span>
                    </button>
                    <button
                        type="button"
                        className={`ios-seg__btn coupons-list-tab${activeTab === 'history' ? ' is-active' : ''}`}
                        role="tab"
                        data-tab="history"
                        aria-selected={activeTab === 'history'}
                        onClick={() => setActiveTab('history')}
                    >
                        利用済み・期限切れ <span>{historyCoupons.length}</span>
                    </button>
                </div>
                <div className={`coupons-tab-panel${activeTab === 'available' ? ' is-active' : ''}`} data-panel="available" role="tabpanel">
                    {deviceCouponPanel}
                    {visibleCoupons.length > 0 ? (
                        <div className="ios-coupon-stack">
                            {visibleCoupons.map((cp, i) => (
                                <FixedCouponCard key={String(cp.id)} cp={cp} defaultImage={defaultImage} index={i} />
                            ))}
                        </div>
                    ) : (
                        <div className="ios-empty ios-empty--compact">
                            <p>利用可能なクーポンはありません</p>
                        </div>
                    )}
                </div>
                <div className={`coupons-tab-panel${activeTab === 'history' ? ' is-active' : ''}`} data-panel="history" role="tabpanel">
                    {historyCoupons.length > 0 ? (
                        <div className="ios-coupon-stack">
                            {historyCoupons.map((cp, i) => (
                                <FixedCouponCard key={String(cp.id)} cp={cp} defaultImage={defaultImage} index={i} />
                            ))}
                        </div>
                    ) : (
                        <div className="ios-empty ios-empty--compact">
                            <p>利用済み・期限切れのクーポンはありません</p>
                        </div>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className={className} data-react-coupons="1">
            <div className="coupons-header">
                <h2 className="section-title">
                    <i className="fas fa-ticket-alt" aria-hidden="true" /> マイクーポン
                </h2>
            </div>
            <div className="coupons-list-tabs" role="tablist" aria-label="クーポン一覧">
                <button
                    type="button"
                    className={`coupons-list-tab${activeTab === 'available' ? ' is-active' : ''}`}
                    role="tab"
                    data-tab="available"
                    aria-selected={activeTab === 'available'}
                    onClick={() => setActiveTab('available')}
                >
                    <i className="fas fa-check-circle" aria-hidden="true" /> 利用可能
                    <span className="coupons-list-tab-count">{visibleCoupons.length}</span>
                </button>
                <button
                    type="button"
                    className={`coupons-list-tab${activeTab === 'history' ? ' is-active' : ''}`}
                    role="tab"
                    data-tab="history"
                    aria-selected={activeTab === 'history'}
                    onClick={() => setActiveTab('history')}
                >
                    <i className="fas fa-history" aria-hidden="true" /> 利用済み・期限切れ
                    <span className="coupons-list-tab-count">{historyCoupons.length}</span>
                </button>
            </div>
            <div className={`coupons-tab-panel${activeTab === 'available' ? ' is-active' : ''}`} data-panel="available" role="tabpanel">
                {deviceCouponPanel}
                {visibleCoupons.length > 0 ? (
                    <div className="coupons-list">
                        {visibleCoupons.map((cp) => (
                            <ClassicCouponCard key={String(cp.id)} cp={cp} defaultImage={defaultImage} />
                        ))}
                    </div>
                ) : (
                    <div className="coupons-tab-empty">
                        <i className="fas fa-ticket-alt" aria-hidden="true" />
                        <p>利用可能なクーポンはありません</p>
                    </div>
                )}
            </div>
            <div className={`coupons-tab-panel${activeTab === 'history' ? ' is-active' : ''}`} data-panel="history" role="tabpanel">
                {historyCoupons.length > 0 ? (
                    <div className="coupons-list">
                        {historyCoupons.map((cp) => (
                            <ClassicCouponCard key={String(cp.id)} cp={cp} defaultImage={defaultImage} />
                        ))}
                    </div>
                ) : (
                    <div className="coupons-tab-empty">
                        <i className="fas fa-ticket-alt" aria-hidden="true" />
                        <p>利用済み・期限切れのクーポンはありません</p>
                    </div>
                )}
            </div>
        </div>
    );
}
