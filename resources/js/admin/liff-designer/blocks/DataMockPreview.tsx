import React, { useMemo } from 'react';
import { DATA_SOURCES } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';
import {
    categoryLabel,
    formatPrice,
    readPreviewData,
    type PreviewCoupon,
    type PreviewProduct,
    type PreviewStamp,
} from '../preview/previewCatalog';

function CouponItem({
    icon,
    title,
    meta,
    available,
    value,
    mediaStyle = 'image',
    imageUrl,
}: {
    icon: string;
    title: string;
    meta: string;
    available: boolean;
    value: string;
    mediaStyle?: string;
    imageUrl?: string;
}) {
    return (
        <div className={`ld-crm-coupon ld-crm-coupon--media-${mediaStyle}${available ? ' is-on' : ''}`}>
            {mediaStyle !== 'none' ? (
                <div
                    className={`ld-crm-coupon__media ld-crm-coupon__media--${mediaStyle}`}
                    aria-hidden
                    style={mediaStyle === 'image' && imageUrl ? { backgroundImage: `url(${imageUrl})` } : undefined}
                >
                    {mediaStyle === 'icon' ? <i className={`fas ${icon}`} /> : (mediaStyle === 'image' && imageUrl ? null : <span>{value}</span>)}
                </div>
            ) : null}
            <div className="ld-crm-coupon__value">
                <strong>{value}</strong>
            </div>
            <div className="ld-crm-coupon__body">
                <div className="ld-crm-coupon__title">
                    <i className={`fas ${icon}`} />
                    <strong>{title}</strong>
                </div>
                <span>{meta}</span>
            </div>
            <em>{available ? '利用可' : '使用済'}</em>
        </div>
    );
}

function CouponsMock({
    mediaStyle = 'image',
    showAvailable = true,
    showHistory = true,
    coupons,
}: {
    mediaStyle?: string;
    showAvailable?: boolean;
    showHistory?: boolean;
    coupons: PreviewCoupon[];
}) {
    const items = coupons
        .filter((item) => (item.available ? showAvailable : showHistory))
        .map((item) => ({
            icon: item.available ? 'fa-ticket-alt' : 'fa-history',
            title: item.title,
            meta: item.meta,
            available: item.available,
            value: item.available ? 'ON' : 'USED',
            mediaStyle,
            imageUrl: item.image_url || undefined,
        }));

    return (
        <>
            {showAvailable && showHistory ? (
                <div className="ld-crm-coupon-tabs" aria-hidden>
                    <span className="is-on">未使用</span>
                    <span>利用済み</span>
                </div>
            ) : null}
            {items.length === 0 ? (
                <div className="ld-crm-empty">表示するクーポンがありません</div>
            ) : (
                items.map((item) => (
                    <CouponItem key={`${item.title}-${item.value}`} {...item} />
                ))
            )}
        </>
    );
}

function ProductsMock({
    products,
    groupByCategory,
}: {
    products: PreviewProduct[];
    groupByCategory: boolean;
}) {
    if (products.length === 0) {
        return <div className="ld-crm-empty">商品がまだ登録されていません</div>;
    }

    if (!groupByCategory) {
        return (
            <div className="ld-crm-grid2">
                {products.slice(0, 6).map((p) => (
                    <ProductCard
                        key={String(p.id)}
                        img={p.image_url || '/images/liff/product-placeholder.jpg'}
                        name={p.name}
                        price={formatPrice(p.price)}
                    />
                ))}
            </div>
        );
    }

    const groups = new Map<string, PreviewProduct[]>();
    products.forEach((p) => {
        const key = p.category || 'other';
        const rows = groups.get(key) || [];
        rows.push(p);
        groups.set(key, rows);
    });

    return (
        <div className="ld-crm-products-grouped">
            {Array.from(groups.entries()).map(([key, rows]) => (
                <div key={key} className="ld-crm-products-grouped__section">
                    <div className="ld-crm-products-grouped__head">
                        <strong>{categoryLabel(key)}</strong>
                        <span>{rows.length}</span>
                    </div>
                    <div className="ld-crm-grid2">
                        {rows.slice(0, 4).map((p) => (
                            <ProductCard
                                key={String(p.id)}
                                img={p.image_url || '/images/liff/product-placeholder.jpg'}
                                name={p.name}
                                price={formatPrice(p.price)}
                            />
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}

function ProductCard({ img, name, price, tag }: { img: string; name: string; price: string; tag?: string }) {
    return (
        <div className="ld-crm-prod">
            <div className="ld-crm-prod__img" style={{ backgroundImage: `url(${img})` }}>
                {tag ? <em>{tag}</em> : null}
            </div>
            <strong>{name}</strong>
            <div className="ld-crm-prod__row">
                <span>¥{price}</span>
                <button type="button" tabIndex={-1} aria-label="追加">
                    <i className="fas fa-plus" />
                </button>
            </div>
        </div>
    );
}

function StampCardMock({
    design = 'grid_card',
    slotStyle = '',
    filledStyle = '',
    accentColor = '',
    stamp,
}: {
    design?: string;
    slotStyle?: string;
    filledStyle?: string;
    accentColor?: string;
    stamp?: PreviewStamp | null;
}) {
    const layout = design !== '' ? design : 'grid_card';
    const radius = slotStyle === 'square' ? 6 : slotStyle === 'rounded' ? 12 : 999;
    const accent = accentColor !== '' ? accentColor : '#16a34a';
    const filledIcon = filledStyle === 'icon' ? 'fa-stamp' : filledStyle === 'solid' ? 'fa-check' : 'fa-certificate';
    const total = stamp && stamp.required > 0
        ? stamp.required
        : (layout === 'minimal' ? 5 : layout === 'circle_board' ? 8 : 10);
    const filled = Math.min(total, Math.max(1, Math.floor(total * 0.7)));
    const stampName = stamp?.name || 'スタンプカード';
    const rewardText = stamp?.reward || '満了でドリンク無料クーポン';
    const dots = [];
    for (let i = 0; i < total; i += 1) {
        const isOn = i < filled;
        dots.push(
            <span
                key={i}
                className={`ld-crm-stamp${isOn ? ' is-on' : ''}`}
                style={{
                    borderRadius: radius,
                    ...(isOn ? { background: accent, borderColor: accent, color: '#fff' } : {}),
                }}
            >
                {isOn ? <i className={`fas ${filledIcon}`} /> : i + 1}
            </span>,
        );
    }

    const remain = Math.max(0, total - filled);
    const progress = `${Math.round((filled / Math.max(total, 1)) * 100)}%`;

    if (layout === 'wallet') {
        return (
            <div className="ld-crm-stampcard ld-crm-stampcard--wallet" style={{ ['--stamp-accent' as string]: accent }}>
                <div className="ld-crm-stampcard__wallet-top">
                    <span>{stampName}</span>
                    <strong>{filled} / {total}</strong>
                </div>
                <div className="ld-crm-stampcard__strip">{dots}</div>
                <p className="ld-crm-stampcard__wallet-foot">あと{remain}で特典</p>
            </div>
        );
    }

    if (layout === 'circle_board') {
        return (
            <div className="ld-crm-stampcard ld-crm-stampcard--board" style={{ ['--stamp-accent' as string]: accent }}>
                <div className="ld-crm-stampcard__board-ring">
                    <div className="ld-crm-stampcard__board-core">
                        <em>{filled}</em>
                        <span>/ {total}</span>
                    </div>
                    <div className="ld-crm-stampcard__board-dots">{dots}</div>
                </div>
                <p className="ld-crm-stampcard__board-label">{rewardText || `あと${remain}で特典ゲット`}</p>
            </div>
        );
    }

    if (layout === 'minimal') {
        return (
            <div className="ld-crm-stampcard ld-crm-stampcard--minimal" style={{ ['--stamp-accent' as string]: accent }}>
                <div className="ld-crm-stampcard__head">
                    <div>
                        <span>{stampName}</span>
                        <strong>{filled} / {total}</strong>
                    </div>
                </div>
                <div className="ld-crm-stampcard__row">{dots}</div>
                <div className="ld-crm-stampcard__bar">
                    <i style={{ width: progress, background: accent }} />
                </div>
            </div>
        );
    }

    return (
        <div className="ld-crm-stampcard ld-crm-stampcard--grid" style={{ ['--stamp-accent' as string]: accent }}>
            <div className="ld-crm-stampcard__head">
                <div>
                    <span>{stampName}</span>
                    <strong>{filled} / {total}</strong>
                </div>
                <em style={{ color: accent, background: `${accent}22` }}>あと{remain}で特典</em>
            </div>
            <div className="ld-crm-stampcard__grid">{dots}</div>
            <div className="ld-crm-stampcard__bar">
                <i style={{ width: progress, background: accent }} />
            </div>
            <div className="ld-crm-stampcard__reward" style={{ color: accent, background: `${accent}14` }}>
                <i className="fas fa-gift" />
                {rewardText}
            </div>
        </div>
    );
}

const MOCKS: Record<string, () => React.ReactNode> = {
    member_card: () => (
        <div className="ld-crm-wallet">
            <div className="ld-crm-wallet__top">
                <div>
                    <span className="ld-crm-wallet__shop">reggit Members</span>
                    <strong>デモ 太郎</strong>
                </div>
                <em className="ld-crm-wallet__rank">Gold</em>
            </div>
            <div className="ld-crm-wallet__stats">
                <div>
                    <span>Points</span>
                    <strong>1,280</strong>
                </div>
                <div>
                    <span>Visits</span>
                    <strong>24</strong>
                </div>
                <div>
                    <span>Rank</span>
                    <strong>68%</strong>
                </div>
            </div>
            <div className="ld-crm-wallet__barcode">
                {Array.from({ length: 28 }).map((_, i) => (
                    <i key={i} style={{ width: `${(i % 4) + 1}px` }} />
                ))}
            </div>
            <div className="ld-crm-wallet__no">No. M999001</div>
            <div className="ld-crm-wallet__btns">
                <span>
                    <i className="fab fa-apple" /> Apple Wallet
                </span>
                <span>
                    <i className="fab fa-google" /> Google Wallet
                </span>
            </div>
        </div>
    ),
    stamps: () => <StampCardMock />,
    orders: () => (
        <>
            <div className="ld-crm-order is-live">
                <div className="ld-crm-order__top">
                    <strong>#1042</strong>
                    <em>受取準備中</em>
                </div>
                <div className="ld-crm-order__steps">
                    <span className="is-done">受付</span>
                    <span className="is-on">調理</span>
                    <span>受取</span>
                </div>
                <div className="ld-crm-order__items">シグネチャーバーガー × 1 ほか</div>
                <div className="ld-crm-order__total">
                    合計 <strong>¥1,660</strong>
                </div>
            </div>
            <div className="ld-crm-order">
                <div className="ld-crm-order__top">
                    <strong>#1031</strong>
                    <em className="is-done">受取完了</em>
                </div>
                <div className="ld-crm-order__items">季節のサラダ × 2</div>
                <div className="ld-crm-order__total">
                    合計 <strong>¥1,360</strong>
                </div>
            </div>
        </>
    ),
    queue: () => (
        <div className="ld-crm-queue">
            <div className="ld-crm-queue__label">あなたの番号</div>
            <div className="ld-crm-queue__num">A-12</div>
            <div className="ld-crm-queue__meta">
                <span>
                    <i className="fas fa-users" /> 前に 3 組
                </span>
                <span>
                    <i className="fas fa-clock" /> 約 15 分
                </span>
            </div>
            <div className="ld-crm-queue__pulse">呼び出し待機中</div>
        </div>
    ),
    reservations: () => (
        <>
            <div className="ld-crm-resv">
                <div className="ld-crm-resv__date">
                    <strong>20</strong>
                    <span>6月 · 土</span>
                </div>
                <div className="ld-crm-resv__body">
                    <strong>18:30 · 2名</strong>
                    <span>店内テーブル · 窓際</span>
                </div>
                <em className="is-ok">確定</em>
            </div>
            <div className="ld-crm-resv">
                <div className="ld-crm-resv__date">
                    <strong>27</strong>
                    <span>6月 · 土</span>
                </div>
                <div className="ld-crm-resv__body">
                    <strong>12:00 · 4名</strong>
                    <span>テラス席</span>
                </div>
                <em className="is-wait">確認中</em>
            </div>
            <button type="button" className="ld-crm-resv__cta" tabIndex={-1}>
                <i className="fas fa-plus" /> 新しい予約
            </button>
        </>
    ),
    gallery: () => (
        <div className="ld-crm-gallery">
            {[
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=300',
                'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=300',
                'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=300',
                'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=300',
            ].map((url) => (
                <div key={url} style={{ backgroundImage: `url(${url})` }} />
            ))}
        </div>
    ),
    announcements: () => (
        <>
            <article className="ld-crm-news is-featured">
                <div
                    className="ld-crm-news__media"
                    style={{ backgroundImage: 'url(https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600)' }}
                />
                <div className="ld-crm-news__body">
                    <div className="ld-crm-news__meta">
                        <span>6/13</span>
                        <em>NEW</em>
                    </div>
                    <strong>夏季限定メニューが登場</strong>
                    <p>冷製パスタとクラフトソーダをラインナップに追加しました。</p>
                </div>
            </article>
            <div className="ld-crm-ann">
                <span>6/01</span>
                <div>
                    <strong>営業時間変更のお知らせ</strong>
                    <p>平日のラストオーダーを21:30に変更します。</p>
                </div>
            </div>
            <div className="ld-crm-ann">
                <span>5/20</span>
                <div>
                    <strong>ポイント2倍デー開催</strong>
                    <em>HOT</em>
                </div>
            </div>
        </>
    ),
    announcement_detail: () => (
        <article className="ld-crm-news is-featured">
            <div
                className="ld-crm-news__media"
                style={{ backgroundImage: 'url(https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=600)' }}
            />
            <div className="ld-crm-news__body">
                <div className="ld-crm-news__meta">
                    <span>2026/06/13 10:00</span>
                </div>
                <strong>夏季限定メニューが登場</strong>
                <p>冷製パスタとクラフトソーダをラインナップに追加しました。店頭・アプリの両方からご注文いただけます。</p>
            </div>
        </article>
    ),
    product_detail: () => (
        <article className="ld-crm-news is-featured">
            <div className="ld-crm-news__body">
                <strong>カプチーノ</strong>
                <p>¥480 · オプション選択とカート追加はこの画面で行います。</p>
            </div>
        </article>
    ),
    coupon_detail: () => (
        <article className="ld-crm-news is-featured">
            <div className="ld-crm-news__body">
                <strong>ドリンク1杯無料</strong>
                <p>有効期限・利用コード・使用ボタンがここに表示されます。</p>
            </div>
        </article>
    ),
    gallery_detail: () => (
        <article className="ld-crm-news is-featured">
            <div
                className="ld-crm-news__media"
                style={{ backgroundImage: 'url(https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=600)' }}
            />
            <div className="ld-crm-news__body">
                <strong>店内フォト</strong>
                <p>選択したギャラリー画像の拡大表示です。</p>
            </div>
        </article>
    ),
    stamp_detail: () => (
        <article className="ld-crm-news is-featured">
            <div className="ld-crm-news__body">
                <strong>スタンプラリー</strong>
                <p>3 / 10 スタンプ · 進捗と特典説明を表示します。</p>
            </div>
        </article>
    ),
};

export function SampleDataShell({
    design,
    children,
}: {
    label?: string;
    design?: string;
    children: React.ReactNode;
}) {
    const variant = design && design !== '' ? design : 'auto';
    return (
        <div className={`ld-crm-shell ld-crm-shell--${variant} ld-crm-shell--plain`}>
            <div className="ld-crm-shell__body">{children}</div>
        </div>
    );
}

export function DataMockPreview({
    source,
    design,
    cardDesign,
    cardBgStyle,
    cardGloss,
    cardSurface,
    stampDesign,
    stampSlotStyle,
    stampFilledStyle,
    stampAccentColor,
    couponScreenDesign,
    couponMediaStyle,
    couponShowAvailable,
    couponShowHistory,
    shopName,
    asGuest = false,
}: {
    source: string;
    design?: string;
    cardDesign?: string;
    cardBgStyle?: string;
    cardGloss?: string;
    cardSurface?: string;
    stampDesign?: string;
    stampSlotStyle?: string;
    stampFilledStyle?: string;
    stampAccentColor?: string;
    couponScreenDesign?: string;
    couponMediaStyle?: string;
    couponShowAvailable?: boolean;
    couponShowHistory?: boolean;
    shopName?: string;
    asGuest?: boolean;
}) {
    const { config, state } = useDesigner();
    const preview = useMemo(() => readPreviewData(config), [config]);
    const groupByCategory = state.globalSettings.products_group_by_category !== false;
    const label = DATA_SOURCES.find((d) => d.value === source)?.label || source;
    const variant = source === 'coupons' ? (design || couponScreenDesign || '') : (design || '');
    const memberName = String(
        (preview.member?.user as { name?: string } | undefined)?.name
        || 'プレビュー会員',
    );
    const memberPoints = Number(preview.member?.points ?? 1280);
    const memberRank = String(preview.member?.current_rank_label || 'Gold');
    const memberNo = String(
        (preview.member?.member as { member_number?: string } | undefined)?.member_number
        || '000123456789',
    );
    const render =
        source === 'stamps'
            ? () => (
                <StampCardMock
                    design={stampDesign || ''}
                    slotStyle={stampSlotStyle || ''}
                    filledStyle={stampFilledStyle || ''}
                    accentColor={stampAccentColor || ''}
                    stamp={preview.stamps[0] || null}
                />
            )
            : source === 'coupons'
              ? () => (
                  <CouponsMock
                      mediaStyle={couponMediaStyle || 'image'}
                      showAvailable={couponShowAvailable !== false}
                      showHistory={couponShowHistory !== false}
                      coupons={preview.coupons}
                  />
              )
              : source === 'products'
                ? () => (
                    <ProductsMock
                        products={preview.products}
                        groupByCategory={groupByCategory}
                    />
                  )
              : MOCKS[source] || (() => <div className="ld-crm-empty">このデータは実際の利用者画面に表示されます</div>);
    if (source === 'member_card' || source === 'member') {
        if (asGuest) {
            return (
                <SampleDataShell label={label} design={variant || cardDesign || 'premium'}>
                    <div className="ld-crm-guest-card">
                        <p className="ld-crm-guest-card__title">会員証は登録後に表示</p>
                        <p className="ld-crm-guest-card__sub">ポイント・バーコード・ランクは会員になると使えます</p>
                        <span className="ld-crm-guest-card__cta" data-preview-nav="member">
                            会員登録へ
                        </span>
                    </div>
                </SampleDataShell>
            );
        }
        return (
            <SampleDataShell label={label} design={variant || cardDesign || 'premium'}>
                <div
                    className={[
                        'ld-crm-wallet',
                        `ld-crm-wallet--${cardDesign || 'default'}`,
                        `ld-crm-wallet--bg-${cardBgStyle || 'gradient'}`,
                        `ld-crm-wallet--gloss-${cardGloss || 'soft'}`,
                        `ld-crm-wallet--surface-${cardSurface || 'plastic'}`,
                    ].join(' ')}
                >
                    <div className="ld-crm-wallet__shine" aria-hidden />
                    <div className="ld-crm-wallet__top">
                        <div>
                            <span className="ld-crm-wallet__shop">{shopName && shopName.trim() !== '' ? shopName : 'Members'}</span>
                            <strong>{memberName}</strong>
                        </div>
                        <em className="ld-crm-wallet__rank">{memberRank}</em>
                    </div>
                    <div className="ld-crm-wallet__stats">
                        <div>
                            <span>Points</span>
                            <strong>{memberPoints.toLocaleString('ja-JP')}</strong>
                        </div>
                        <div>
                            <span>Visits</span>
                            <strong>24</strong>
                        </div>
                        <div>
                            <span>Rank</span>
                            <strong>{memberRank}</strong>
                        </div>
                    </div>
                    <div className="ld-crm-wallet__barcode">
                        {Array.from({ length: 28 }).map((_, i) => (
                            <i key={i} style={{ width: `${(i % 4) + 1}px` }} />
                        ))}
                    </div>
                    <div className="ld-crm-wallet__no">No. {memberNo}</div>
                    <div className="ld-crm-wallet__btns">
                        <span>
                            <i className="fab fa-apple" /> Apple Wallet
                        </span>
                        <span>
                            <i className="fab fa-google" /> Google Wallet
                        </span>
                    </div>
                </div>
            </SampleDataShell>
        );
    }
    if (asGuest && (source === 'coupons' || source === 'stamps' || source === 'reservations')) {
        return (
            <SampleDataShell label={label} design={variant}>
                <div className="ld-crm-guest-card">
                    <p className="ld-crm-guest-card__title">{label}は会員向け</p>
                    <p className="ld-crm-guest-card__sub">ゲスト表示では登録後の機能は使えません。キャンバス上部で「会員表示」に切り替えると中身を確認できます</p>
                    <span className="ld-crm-guest-card__cta" data-preview-nav="member">
                        会員登録へ
                    </span>
                </div>
            </SampleDataShell>
        );
    }
    return <SampleDataShell label={label} design={variant}>{render()}</SampleDataShell>;
}

export function GuestMemberCard({ greeting }: { greeting?: string }) {
    return (
        <div className="ld-crm-member ld-crm-member--guest">
            <p className="ld-crm-member__eyebrow">Members</p>
            <strong className="ld-crm-member__hello">{greeting && greeting !== '' ? greeting : 'ようこそ'}</strong>
            <p className="ld-crm-member__guest-sub">会員登録でポイント・クーポン・先行予約が使えます</p>
            <span className="ld-crm-member__guest-cta" data-preview-nav="member">
                <i className="fas fa-user-plus" /> 会員になる
            </span>
        </div>
    );
}

export function MemberSummaryMock({
    greeting,
    design,
    asGuest = false,
}: {
    greeting?: string;
    design?: string;
    asGuest?: boolean;
}) {
    if (asGuest) {
        return <GuestMemberCard greeting={greeting} />;
    }

    const hello = greeting || 'こんにちは、デモ 太郎さん';
    const variant = design || 'rewards';

    if (variant === 'wallet') {
        return (
            <div className="ld-crm-member ld-crm-member--wallet">
                <div className="ld-crm-member__wallet-row">
                    <div className="ld-crm-member__avatar">太</div>
                    <div className="ld-crm-member__wallet-copy">
                        <span className="ld-crm-member__hello">{hello}</span>
                        <em className="ld-crm-member__rank">Gold Member</em>
                    </div>
                    <div className="ld-crm-member__wallet-pt">
                        <strong>1,280</strong>
                        <span>pt</span>
                    </div>
                </div>
                <div className="ld-crm-member__track">
                    <i style={{ width: '68%' }} />
                </div>
                <div className="ld-crm-member__meta">
                    <span>次のランクまで 220 pt</span>
                    <span>今月 +180</span>
                </div>
            </div>
        );
    }

    if (variant === 'emblem') {
        return (
            <div className="ld-crm-member ld-crm-member--emblem">
                <i className="fas fa-crown ld-crm-member__crown" />
                <span className="ld-crm-member__hello">{hello}</span>
                <em className="ld-crm-member__rank">Gold Member</em>
                <div className="ld-crm-member__points">
                    <strong>1,280</strong>
                    <span>pt</span>
                </div>
            </div>
        );
    }

    if (variant === 'tier') {
        return (
            <div className="ld-crm-member ld-crm-member--tier">
                <em className="ld-crm-member__rank"><i className="fas fa-gem" /> Gold</em>
                <span className="ld-crm-member__hello">{hello}</span>
                <div className="ld-crm-member__points">
                    <strong>1,280</strong>
                    <span>pt</span>
                </div>
                <div className="ld-crm-member__track">
                    <i style={{ width: '68%' }} />
                </div>
            </div>
        );
    }

    return (
        <div className={`ld-crm-member ld-crm-member--${variant}`}>
            <div className="ld-crm-member__top">
                <div>
                    <span className="ld-crm-member__hello">{hello}</span>
                    <em className="ld-crm-member__rank">Gold Member</em>
                </div>
                <div className="ld-crm-member__avatar">太</div>
            </div>
            {variant === 'split' ? (
                <div className="ld-crm-member__split">
                    <div>
                        <strong>1,280</strong>
                        <span>ポイント</span>
                    </div>
                    <div>
                        <strong>24</strong>
                        <span>来店</span>
                    </div>
                </div>
            ) : (
                <>
                    <div className="ld-crm-member__points">
                        <strong>1,280</strong>
                        <span>pt</span>
                    </div>
                    {variant !== 'minimal' ? (
                        <div className="ld-crm-member__track">
                            <i style={{ width: '68%' }} />
                        </div>
                    ) : null}
                    {variant !== 'minimal' && variant !== 'compact' ? (
                        <div className="ld-crm-member__meta">
                            <span>次のランクまで 220 pt</span>
                            <span>今月 +180</span>
                        </div>
                    ) : null}
                </>
            )}
        </div>
    );
}
