import React from 'react';
import { DATA_SOURCES } from '../catalog/blockCatalog';

function CouponItem({ icon, title, meta, available }: { icon: string; title: string; meta: string; available: boolean }) {
    return (
        <div className={`ld-mock-coupon${available ? ' is-on' : ''}`}>
            <div className="ld-mock-coupon__icon"><i className={`fas ${icon}`} /></div>
            <div className="ld-mock-coupon__body">
                <div className="ld-mock-coupon__title">{title}</div>
                <div className="ld-mock-coupon__meta">{meta}</div>
            </div>
            <span className="ld-mock-coupon__status">{available ? '利用可' : '使用済'}</span>
        </div>
    );
}

function ProductCard({ img, name, price }: { img: string; name: string; price: string }) {
    return (
        <div className="ld-mock-prod">
            <div className="ld-mock-prod__img" style={{ backgroundImage: `url(${img})` }} />
            <div className="ld-mock-prod__name">{name}</div>
            <div className="ld-mock-prod__row">
                <span className="ld-mock-prod__price">¥{price}</span>
                <span className="ld-mock-prod__add"><i className="fas fa-plus" /></span>
            </div>
        </div>
    );
}

const MOCKS: Record<string, () => React.ReactNode> = {
    member_card: () => (
        <div className="ld-mock-card">
            <div className="ld-mock-card__head">
                <div>
                    <div className="ld-mock-card__shop">reggit メンバーズ</div>
                    <div className="ld-mock-card__name">デモ 太郎</div>
                </div>
                <span className="ld-mock-card__rank">シルバー</span>
            </div>
            <div className="ld-mock-card__points"><span>1,280</span> pt</div>
            <div className="ld-mock-card__barcode">
                {Array.from({ length: 34 }).map((_, i) => (
                    <i key={i} style={{ width: `${(i % 4) + 1}px` }} />
                ))}
            </div>
            <div className="ld-mock-card__no">No. M999001</div>
            <div className="ld-mock-card__wallet-btns">
                <span className="ld-mock-wallet-btn ld-mock-wallet-btn--apple"><i className="fab fa-apple" /> Apple Walletに追加</span>
                <span className="ld-mock-wallet-btn ld-mock-wallet-btn--google"><i className="fab fa-google" /> Google Walletに追加</span>
            </div>
        </div>
    ),
    coupons: () => (
        <>
            <CouponItem icon="fa-mug-hot" title="ドリンク1杯無料" meta="〜2027/6/30" available />
            <CouponItem icon="fa-percent" title="会計から10%OFF" meta="〜2027/6/30" available />
            <CouponItem icon="fa-gift" title="デザートプレゼント" meta="使用済み" available={false} />
        </>
    ),
    stamps: () => {
        const dots = [];
        for (let i = 0; i < 10; i += 1) {
            dots.push(
                <span key={i} className={`ld-mock-stamp${i < 7 ? ' is-on' : ''}`}>
                    {i < 7 ? <i className="fas fa-star" /> : i + 1}
                </span>,
            );
        }
        return (
            <div className="ld-mock-stampcard">
                <div className="ld-mock-stampcard__head"><span>来店スタンプ</span><strong>7 / 10</strong></div>
                <div className="ld-mock-stampcard__grid">{dots}</div>
                <div className="ld-mock-stampcard__foot"><i className="fas fa-gift" /> あと3個で特典ゲット</div>
            </div>
        );
    },
    products: () => (
        <div className="ld-mock-grid2">
            <ProductCard img="https://images.unsplash.com/photo-1550547660-d9450f859349?w=300" name="シグネチャーバーガー" price="980" />
            <ProductCard img="https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=300" name="季節のサラダ" price="680" />
        </div>
    ),
    orders: () => (
        <>
            <div className="ld-mock-order">
                <div className="ld-mock-order__top">
                    <span className="ld-mock-order__no">#1042</span>
                    <span className="ld-mock-order__status is-progress">受取準備中</span>
                </div>
                <div className="ld-mock-order__items">シグネチャーバーガー × 1 ほか</div>
                <div className="ld-mock-order__total">合計 <strong>¥1,660</strong></div>
            </div>
            <div className="ld-mock-order">
                <div className="ld-mock-order__top">
                    <span className="ld-mock-order__no">#1031</span>
                    <span className="ld-mock-order__status is-done">受取完了</span>
                </div>
                <div className="ld-mock-order__items">季節のサラダ × 2</div>
                <div className="ld-mock-order__total">合計 <strong>¥1,360</strong></div>
            </div>
        </>
    ),
    queue: () => (
        <div className="ld-mock-queue">
            <div className="ld-mock-queue__label">あなたの番号</div>
            <div className="ld-mock-queue__num">A-12</div>
            <div className="ld-mock-queue__wait"><i className="fas fa-users" /> 前に 3 組 / 約 15 分</div>
        </div>
    ),
    reservations: () => (
        <>
            <div className="ld-mock-resv">
                <div className="ld-mock-resv__date"><span className="ld-mock-resv__d">6/20</span><span className="ld-mock-resv__t">18:30</span></div>
                <div className="ld-mock-resv__body"><div>2名 / 店内</div><span className="ld-mock-resv__status is-ok">確定</span></div>
            </div>
            <div className="ld-mock-resv">
                <div className="ld-mock-resv__date"><span className="ld-mock-resv__d">6/27</span><span className="ld-mock-resv__t">12:00</span></div>
                <div className="ld-mock-resv__body"><div>4名 / テラス</div><span className="ld-mock-resv__status is-wait">確認中</span></div>
            </div>
        </>
    ),
    gallery: () => (
        <div className="ld-mock-gallery">
            {[
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=300',
                'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=300',
                'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=300',
            ].map((url) => (
                <div key={url} className="ld-mock-gallery__img" style={{ backgroundImage: `url(${url})` }} />
            ))}
        </div>
    ),
    announcements: () => (
        <>
            <div className="ld-mock-ann"><span className="ld-mock-ann__date">6/13</span><span className="ld-mock-ann__title">夏季限定メニューが登場しました</span></div>
            <div className="ld-mock-ann"><span className="ld-mock-ann__date">6/01</span><span className="ld-mock-ann__title">営業時間変更のお知らせ</span></div>
        </>
    ),
};

export function SampleDataShell({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="ld-data-preview-wrap">
            <div className="ld-data-head">
                <span className="ld-data-head__label"><i className="fas fa-bolt" /> {label}</span>
                <span className="ld-data-head__badge">サンプル表示</span>
            </div>
            <div className="ld-data-sample">
                <div className="ld-mock">{children}</div>
            </div>
            <p className="ld-data-footnote">実データは利用者のLINE画面に表示されます。レイアウト確認用のサンプルです。</p>
        </div>
    );
}

export function DataMockPreview({ source }: { source: string }) {
    const label = DATA_SOURCES.find((d) => d.value === source)?.label || source;
    const render = MOCKS[source] || (() => <div className="ld-data-sample__empty">このデータは実際の利用者画面に表示されます</div>);
    return (
        <SampleDataShell label={label}>
            {render()}
        </SampleDataShell>
    );
}

export function MemberSummaryMock({ greeting, design }: { greeting?: string; design?: string }) {
    const hello = greeting || 'こんにちは、デモ 太郎さん';
    return (
        <SampleDataShell label="会員サマリー">
            <div className={`ld-mock-summary ld-mock-summary--${design || 'rewards'}`}>
                <div className="ld-mock-summary__top">
                    <div className="ld-mock-summary__hello">{hello}</div>
                    <span className="ld-mock-summary__rank">ゴールド</span>
                </div>
                <div className="ld-mock-summary__points">
                    <span className="ld-mock-summary__pt">1,280</span>
                    <span className="ld-mock-summary__unit">pt</span>
                </div>
                <div className="ld-mock-summary__track"><span style={{ width: '68%' }} /></div>
                <div className="ld-mock-summary__next">次のランクまで 220 pt</div>
            </div>
        </SampleDataShell>
    );
}
