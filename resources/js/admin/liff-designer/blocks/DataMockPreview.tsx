import React from 'react';
import { DATA_SOURCES } from '../catalog/blockCatalog';

function CouponItem({
    icon,
    title,
    meta,
    available,
    value,
}: {
    icon: string;
    title: string;
    meta: string;
    available: boolean;
    value: string;
}) {
    return (
        <div className={`ld-crm-coupon${available ? ' is-on' : ''}`}>
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
    slotStyle = '',
    filledStyle = '',
    accentColor = '',
}: {
    slotStyle?: string;
    filledStyle?: string;
    accentColor?: string;
}) {
    const radius = slotStyle === 'square' ? 8 : slotStyle === 'rounded' ? 12 : 999;
    const accent = accentColor !== '' ? accentColor : undefined;
    const filledIcon = filledStyle === 'icon' ? 'fa-stamp' : filledStyle === 'solid' ? 'fa-check' : 'fa-check';
    const dots = [];
    for (let i = 0; i < 10; i += 1) {
        dots.push(
            <span
                key={i}
                className={`ld-crm-stamp${i < 7 ? ' is-on' : ''}`}
                style={{
                    borderRadius: radius,
                    ...(i < 7 && accent ? { background: accent, borderColor: accent } : {}),
                }}
            >
                {i < 7 ? <i className={`fas ${filledIcon}`} /> : i + 1}
            </span>,
        );
    }
    return (
        <div className="ld-crm-stampcard">
            <div className="ld-crm-stampcard__head">
                <div>
                    <span>来店スタンプ</span>
                    <strong>7 / 10</strong>
                </div>
                <em style={accent ? { color: accent } : undefined}>あと3で特典</em>
            </div>
            <div className="ld-crm-stampcard__grid">{dots}</div>
            <div className="ld-crm-stampcard__bar">
                <i style={{ width: '70%', ...(accent ? { background: accent } : {}) }} />
            </div>
            <div className="ld-crm-stampcard__reward">
                <i className="fas fa-gift" style={accent ? { color: accent } : undefined} />
                満了でドリンク無料クーポン
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
    coupons: () => (
        <>
            <CouponItem icon="fa-mug-hot" title="ドリンク1杯無料" meta="〜2027/6/30 · 会員限定" available value="FREE" />
            <CouponItem icon="fa-percent" title="会計から10%OFF" meta="平日限定 · 〜今月末" available value="10%" />
            <CouponItem icon="fa-gift" title="デザートプレゼント" meta="使用済み" available={false} value="GIFT" />
        </>
    ),
    stamps: () => <StampCardMock />,
    products: () => (
        <div className="ld-crm-grid2">
            <ProductCard img="https://images.unsplash.com/photo-1550547660-d9450f859349?w=300" name="シグネチャーバーガー" price="980" tag="BEST" />
            <ProductCard img="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300" name="季節のサラダ" price="680" tag="NEW" />
            <ProductCard img="https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=300" name="本日のパスタ" price="1,100" />
            <ProductCard img="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=300" name="カフェラテ" price="480" />
        </div>
    ),
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
    stampSlotStyle,
    stampFilledStyle,
    stampAccentColor,
    shopName,
    asGuest = false,
}: {
    source: string;
    design?: string;
    cardDesign?: string;
    stampSlotStyle?: string;
    stampFilledStyle?: string;
    stampAccentColor?: string;
    shopName?: string;
    asGuest?: boolean;
}) {
    const label = DATA_SOURCES.find((d) => d.value === source)?.label || source;
    const variant = design || '';
    const render =
        source === 'stamps'
            ? () => (
                <StampCardMock
                    slotStyle={stampSlotStyle || ''}
                    filledStyle={stampFilledStyle || ''}
                    accentColor={stampAccentColor || ''}
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
                <div className={`ld-crm-wallet ld-crm-wallet--${cardDesign || 'default'}`}>
                    <div className="ld-crm-wallet__top">
                        <div>
                            <span className="ld-crm-wallet__shop">{shopName && shopName.trim() !== '' ? shopName : 'Members'}</span>
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
