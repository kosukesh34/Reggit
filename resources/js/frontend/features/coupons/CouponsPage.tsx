import React, { useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { DomHelper } from '../../../shared/utils/domHelper';
import { useFrontendApp } from '../../context/FrontendAppContext';
import { isDemoDataMode, isFixedUiShell } from '../../demo/demoMode';
import { buildBuiltinSampleCoupons } from '../../demo/sampleData';
import { applyDemoCouponUsageState, readDemoWonCoupons } from '../../demo/demoCoupons';

type CouponMediaStyle = 'image' | 'icon' | 'none';

function getCouponMediaStyle(): CouponMediaStyle {
    const value = DomHelper.getLiffGlobalSetting('coupon_media_style', 'image');
    return value === 'icon' || value === 'none' ? value : 'image';
}

function getCouponShowAvailable(): boolean {
    const raw = DomHelper.getLiffGlobalSetting('coupon_show_available', 'true');
    return raw !== '0' && raw !== 'false';
}

function getCouponShowHistory(): boolean {
    const raw = DomHelper.getLiffGlobalSetting('coupon_show_history', 'true');
    return raw !== '0' && raw !== 'false';
}

type Coupon = {
    id?: number | string;
    coupon_name?: string;
    title?: string;
    description?: string;
    status?: string;
    valid_until?: string;
    issued_at?: string;
    image_url?: string;
    code?: string;
    use_display?: string;
};

function formatDate(value?: string): string {
    if (value === undefined || value === '') {
        return '';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return date.toLocaleDateString('ja-JP');
}

function daysUntil(value?: string): number | null {
    if (value === undefined || value === '') {
        return null;
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    const diff = date.getTime() - Date.now();
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
}

function normalizeStatus(status?: string): string {
    const value = String(status || 'available').toLowerCase();
    if (value === 'upcoming' || value === 'used' || value === 'expired' || value === 'available') {
        return value;
    }
    return 'available';
}

function DemoPlayEntry({ onPlay }: { onPlay: (play: 'gacha' | 'scratch' | 'roulette') => void }): JSX.Element {
    return (
        <section className="demo-play-entry" aria-label="クーポンあそび">
            <div className="demo-play-entry__head">
                <p className="demo-play-entry__eyebrow">DEMO PLAY</p>
                <h3 className="demo-play-entry__title">クーポンを当てよう</h3>
                <p className="demo-play-entry__sub">ガチャ・スクラッチ・ルーレットで特典クーポンを獲得できます</p>
            </div>
            <div className="demo-play-entry__actions">
                <button
                    type="button"
                    className="demo-play-entry__btn demo-play-entry__btn--gacha"
                    onClick={() => onPlay('gacha')}
                >
                    <span className="demo-play-entry__btn-label">ガチャガチャ</span>
                    <span className="demo-play-entry__btn-hint">回して当てる</span>
                </button>
                <button
                    type="button"
                    className="demo-play-entry__btn demo-play-entry__btn--scratch"
                    onClick={() => onPlay('scratch')}
                >
                    <span className="demo-play-entry__btn-label">スクラッチ</span>
                    <span className="demo-play-entry__btn-hint">こすって開ける</span>
                </button>
                <button
                    type="button"
                    className="demo-play-entry__btn demo-play-entry__btn--roulette"
                    onClick={() => onPlay('roulette')}
                >
                    <span className="demo-play-entry__btn-label">ルーレット</span>
                    <span className="demo-play-entry__btn-hint">回して止める</span>
                </button>
            </div>
        </section>
    );
}

function FixedCouponsList({ coupons }: { coupons: Coupon[] }): JSX.Element {
    const { switchTab } = useFrontendApp();
    const [tab, setTab] = useState<'available' | 'history'>('available');
    const demo = isDemoDataMode();
    const mediaStyle = getCouponMediaStyle();
    const showAvailable = getCouponShowAvailable();
    const showHistory = getCouponShowHistory();
    const showTabs = showAvailable && showHistory;
    const statusLabels: Record<string, string> = {
        available: '利用可能',
        upcoming: '開始前',
        used: '使用済み',
        expired: '期限切れ',
    };

    const groups: Record<string, Coupon[]> = {
        available: [],
        upcoming: [],
        used: [],
        expired: [],
    };
    coupons.forEach((cp) => {
        const status = normalizeStatus(cp.status);
        groups[status].push(cp);
    });

    const visible = groups.available.concat(groups.upcoming);
    const history = groups.used.concat(groups.expired);
    const defaultTab: 'available' | 'history' = showAvailable && visible.length > 0
        ? 'available'
        : (showHistory ? 'history' : 'available');
    const activeTab = showTabs
        ? (visible.length > 0 || tab === 'available' ? tab : 'history')
        : defaultTab;

    const openPlay = (play: 'gacha' | 'scratch' | 'roulette') => {
        try {
            window.sessionStorage.setItem('liff_demo_play_focus', play);
        } catch {
            /* ignore */
        }
        switchTab('designlab');
    };

    const renderCoupon = (cp: Coupon, index: number) => {
        const status = normalizeStatus(cp.status);
        const label = statusLabels[status] || status;
        const validUntil = formatDate(cp.valid_until);
        const untilDays = status === 'available' ? daysUntil(cp.valid_until) : null;
        const imageUrl = (cp.image_url && cp.image_url.trim() !== '')
            ? cp.image_url
            : null;
        const expiryText = untilDays !== null && untilDays >= 0 && untilDays <= 7
            ? `あと${untilDays}日`
            : (validUntil !== '' ? `〜${validUntil}` : '');
        const name = cp.coupon_name || cp.title || 'クーポン';
        const desc = (cp.description && cp.description.trim() !== '') ? cp.description.trim() : '';

        let mediaBlock: JSX.Element | null = null;
        if (mediaStyle === 'image' && imageUrl !== null) {
            mediaBlock = (
                <div className="ios-coupon__media">
                    <img
                        src={imageUrl}
                        alt=""
                        loading="lazy"
                        onError={(e) => {
                            e.currentTarget.closest('.ios-coupon__media')?.remove();
                        }}
                    />
                    <div className="ios-coupon__media-shade" />
                    <div className="ios-coupon__chips">
                        <span className="ios-coupon__status">{label}</span>
                        {expiryText !== '' ? <span className="ios-coupon__chip">{expiryText}</span> : null}
                    </div>
                </div>
            );
        } else if (mediaStyle === 'icon') {
            mediaBlock = (
                <div className="ios-coupon__media ios-coupon__media--icon">
                    <i className="fas fa-ticket-alt" aria-hidden="true" />
                    <div className="ios-coupon__chips">
                        <span className="ios-coupon__status">{label}</span>
                    </div>
                </div>
            );
        }

        return (
            <article
                key={String(cp.id ?? `${name}-${index}`)}
                className={`ios-coupon ios-coupon--${status}${mediaStyle === 'none' ? ' ios-coupon--no-media' : ''}`}
                data-coupon-id={String(cp.id ?? '')}
            >
                {mediaBlock}
                {mediaStyle === 'image' && imageUrl === null ? (
                    <div className="ios-coupon__chips ios-coupon__chips--solo">
                        <span className="ios-coupon__status">{label}</span>
                        {expiryText !== '' ? <span className="ios-coupon__chip">{expiryText}</span> : null}
                    </div>
                ) : null}
                <div className="ios-coupon__body">
                    {mediaStyle === 'none' ? (
                        <span className="ios-coupon__status ios-coupon__status--inline">{label}</span>
                    ) : null}
                    <h3 className="ios-coupon__title">{name}</h3>
                    {desc !== '' ? <p className="ios-coupon__desc">{desc}</p> : null}
                    {status === 'available'
                        ? (
                            <button
                                type="button"
                                className="ios-coupon__use"
                                data-member-coupon-id={String(cp.id ?? '')}
                            >
                                クーポンを使う
                            </button>
                        )
                        : <span className="ios-coupon__hint">タップして詳細を見る</span>}
                </div>
            </article>
        );
    };

    return (
        <>
            {demo ? <DemoPlayEntry onPlay={openPlay} /> : null}
            <div className="ios-panel-head">
                <h2 className="ios-panel-head__title">クーポン</h2>
                <p className="ios-panel-head__sub">使えるお得な特典</p>
            </div>
            {showTabs ? (
                <div className="ios-seg" role="tablist" aria-label="クーポン一覧">
                    <button
                        type="button"
                        className={`ios-seg__btn${activeTab === 'available' ? ' is-active' : ''}`}
                        role="tab"
                        aria-selected={activeTab === 'available'}
                        onClick={() => setTab('available')}
                    >
                        利用可能 <span>{visible.length}</span>
                    </button>
                    <button
                        type="button"
                        className={`ios-seg__btn${activeTab === 'history' ? ' is-active' : ''}`}
                        role="tab"
                        aria-selected={activeTab === 'history'}
                        onClick={() => setTab('history')}
                    >
                        履歴 <span>{history.length}</span>
                    </button>
                </div>
            ) : null}
            {showAvailable && activeTab === 'available' ? (
                <div className="coupons-tab-panel is-active" role="tabpanel">
                    {visible.length > 0
                        ? <div className="ios-coupon-stack">{visible.map(renderCoupon)}</div>
                        : <div className="ios-empty ios-empty--compact"><p>利用可能なクーポンはありません</p></div>}
                </div>
            ) : null}
            {showHistory && activeTab === 'history' ? (
                <div className="coupons-tab-panel is-active" role="tabpanel">
                    {history.length > 0
                        ? <div className="ios-coupon-stack">{history.map(renderCoupon)}</div>
                        : <div className="ios-empty ios-empty--compact"><p>利用済み・期限切れのクーポンはありません</p></div>}
                </div>
            ) : null}
            {!showAvailable && !showHistory ? (
                <div className="ios-empty ios-empty--compact"><p>表示するクーポンがありません（設定を確認してください）</p></div>
            ) : null}
        </>
    );
}

export function CouponsPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const [coupons, setCoupons] = useState<Coupon[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');
    const demo = isDemoDataMode();
    const fixedUi = isFixedUiShell();

    useEffect(() => {
        let cancelled = false;
        const load = async () => {
            setStatus('loading');
            if (demo) {
                if (!cancelled) {
                    const won = readDemoWonCoupons();
                    const samples = buildBuiltinSampleCoupons();
                    const wonIds = new Set(won.map((coupon) => String(coupon.id)));
                    const merged = [
                        ...won,
                        ...samples.filter((coupon) => !wonIds.has(String(coupon.id))),
                    ];
                    setCoupons(applyDemoCouponUsageState(merged));
                    setStatus('ready');
                }
                return;
            }
            try {
                const result = await apiClient.get(AppConstants.API_ENDPOINTS.MY_COUPONS);
                const data = result.success ? result.data as { coupons?: Coupon[] } | Coupon[] : null;
                const list = Array.isArray(data) ? data : (data?.coupons ?? []);
                if (!cancelled) {
                    setCoupons(list);
                    setStatus('ready');
                }
            } catch {
                if (!cancelled) {
                    setStatus('error');
                }
            }
        };
        void load();
        return () => {
            cancelled = true;
        };
    }, [apiClient, demo]);

    if (status === 'loading') {
        return <div className="loading"><i className="fas fa-spinner fa-spin" /><p>読み込み中...</p></div>;
    }
    if (status === 'error') {
        return <div className="coupons-error"><p>クーポンの読み込みに失敗しました</p></div>;
    }

    if (fixedUi) {
        return <FixedCouponsList coupons={coupons} />;
    }

    if (coupons.length === 0) {
        return <div className="coupons-empty"><p>クーポンはまだありません</p></div>;
    }

    const mediaStyle = getCouponMediaStyle();

    return (
        <div className="coupons-list">
            {coupons.map((c) => {
                const imageUrl = (c.image_url && c.image_url.trim() !== '') ? c.image_url : null;
                return (
                    <div key={String(c.id)} className="coupon-card">
                        {mediaStyle === 'image' && imageUrl !== null ? (
                            <div className="coupon-card__media">
                                <img src={imageUrl} alt="" loading="lazy" />
                            </div>
                        ) : null}
                        {mediaStyle === 'icon' ? (
                            <div className="coupon-card__media coupon-card__media--icon">
                                <i className="fas fa-ticket-alt" aria-hidden="true" />
                            </div>
                        ) : null}
                        <h3>{c.coupon_name || c.title || 'クーポン'}</h3>
                        {c.description ? <p>{c.description}</p> : null}
                        {c.valid_until ? <p className="coupon-card__expiry">期限: {c.valid_until}</p> : null}
                    </div>
                );
            })}
        </div>
    );
}
