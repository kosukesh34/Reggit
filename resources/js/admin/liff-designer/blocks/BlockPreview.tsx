import React from 'react';
import { findBlockMeta } from '../catalog/blockCatalog';
import type { LiffBlock } from '../catalog/types';
import { DataMockPreview, MemberSummaryMock } from './DataMockPreview';

function baseStyle(block: LiffBlock): React.CSSProperties {
    return {
        borderRadius: Number(block.border_radius ?? 14),
        marginBottom: Number(block.margin_bottom ?? 0),
        textAlign: (block.text_align as React.CSSProperties['textAlign']) || 'left',
        background: String(block.background_color || '') || undefined,
        boxShadow: block.shadow === true ? '0 8px 24px rgba(15,23,42,0.08)' : undefined,
    };
}

const SOCIAL_META: Record<string, { icon: string; label: string; color: string }> = {
    line: { icon: 'fab fa-line', label: 'LINE', color: '#06C755' },
    instagram: { icon: 'fab fa-instagram', label: 'Instagram', color: '#E1306C' },
    twitter: { icon: 'fab fa-x-twitter', label: 'X', color: '#0f172a' },
    x: { icon: 'fab fa-x-twitter', label: 'X', color: '#0f172a' },
    facebook: { icon: 'fab fa-facebook-f', label: 'Facebook', color: '#1877F2' },
    youtube: { icon: 'fab fa-youtube', label: 'YouTube', color: '#FF0000' },
    tiktok: { icon: 'fab fa-tiktok', label: 'TikTok', color: '#111827' },
    website: { icon: 'fas fa-globe', label: 'Web', color: '#2563eb' },
};

function previewNavProps(action: unknown): { 'data-preview-nav'?: string } {
    if (typeof action !== 'string' || !action.startsWith('#') || action.length < 2) {
        return {};
    }
    const tab = action.slice(1).split(/[?/]/)[0];
    if (tab === '') {
        return {};
    }
    return { 'data-preview-nav': tab };
}

export function BlockPreview({
    block,
    friendAddUrl,
    shopName,
    asGuest = false,
    memberCardDesign,
    memberCardBgStyle,
    memberCardGloss,
    memberCardSurface,
    couponScreenDesign,
    couponMediaStyle,
    couponShowAvailable,
    couponShowHistory,
    stampScreenDesign,
    stampSlotStyle,
    stampFilledStyle,
    stampAccentColor,
}: {
    block: LiffBlock;
    friendAddUrl?: string;
    shopName?: string;
    asGuest?: boolean;
    memberCardDesign?: string;
    memberCardBgStyle?: string;
    memberCardGloss?: string;
    memberCardSurface?: string;
    couponScreenDesign?: string;
    couponMediaStyle?: string;
    couponShowAvailable?: boolean;
    couponShowHistory?: boolean;
    stampScreenDesign?: string;
    stampSlotStyle?: string;
    stampFilledStyle?: string;
    stampAccentColor?: string;
}) {
    const style = baseStyle(block);
    const resolvedCardDesign =
        String(block.card_design || '') !== ''
            ? String(block.card_design)
            : String(memberCardDesign || '');
    const resolvedStampDesign =
        String(block.stamp_design || '') !== ''
            ? String(block.stamp_design)
            : String(stampScreenDesign || '');
    const resolvedStampSlot =
        String(block.stamp_slot_style || '') !== ''
            ? String(block.stamp_slot_style)
            : String(stampSlotStyle || '');
    const resolvedStampFilled =
        String(block.stamp_filled_style || '') !== ''
            ? String(block.stamp_filled_style)
            : String(stampFilledStyle || '');
    const resolvedStampAccent =
        String(block.stamp_accent_color || '') !== ''
            ? String(block.stamp_accent_color)
            : String(stampAccentColor || '');
    switch (block.type) {
        case 'hero': {
            const design = String(block.design || 'editorial');
            const image = typeof block.image_url === 'string' ? block.image_url : '';
            const bg = image
                ? undefined
                : `linear-gradient(135deg, ${String(block.gradient_start || '#0f172a')}, ${String(block.gradient_end || '#334155')})`;
            const cta = String(block.button_label || block.cta_label || '');
            const heroAction = block.action || (cta !== '' ? '#products' : '');
            return (
                <div
                    className={`ld-app-hero ld-app-hero--${design}`}
                    style={{
                        ...style,
                        background: bg,
                        backgroundImage: image ? `url(${image})` : undefined,
                    }}
                >
                    <div className="ld-app-hero__scrim" />
                    <div className="ld-app-hero__content">
                        {design !== 'minimal' ? (
                            <span className="ld-app-hero__eyebrow">{String(block.eyebrow || 'Featured')}</span>
                        ) : null}
                        <strong className="ld-app-hero__title">{String(block.title || '')}</strong>
                        {block.subtitle || block.text ? (
                            <span className="ld-app-hero__sub">{String(block.subtitle || block.text || '')}</span>
                        ) : null}
                        {cta !== '' ? (
                            <span className="ld-app-hero__cta" {...previewNavProps(heroAction)}>
                                {cta}
                            </span>
                        ) : null}
                    </div>
                </div>
            );
        }
        case 'heading': {
            const size = block.size === 'large' ? 'lg' : block.size === 'small' ? 'sm' : 'md';
            const design = String(block.design || 'plain');
            return (
                <div
                    className={`ld-app-heading ld-app-heading--${size} ld-app-heading--${design}`}
                    style={{ ...style, color: String(block.text_color || '') || undefined }}
                >
                    {String(block.text || '')}
                </div>
            );
        }
        case 'text':
            return (
                <div
                    className={`ld-app-text ld-app-text--${String(block.design || 'body')}`}
                    style={{ ...style, fontSize: Number(block.font_size || 13), color: String(block.text_color || '') || undefined }}
                >
                    {String(block.text || block.body || '')}
                </div>
            );
        case 'image':
            return (
                <div
                    className={`ld-app-image ld-app-image--${String(block.design || 'rounded')}`}
                    style={{
                        ...style,
                        height: block.image_height !== undefined ? Number(block.image_height) : undefined,
                    }}
                >
                    {block.image_url ? (
                        <img
                            src={String(block.image_url)}
                            alt={String(block.alt || '')}
                            style={
                                block.image_height !== undefined
                                    ? { height: '100%', width: '100%', objectFit: (String(block.fit || 'cover') as React.CSSProperties['objectFit']) }
                                    : undefined
                            }
                        />
                    ) : (
                        <div className="ld-app-image__ph" />
                    )}
                </div>
            );
        case 'button': {
            const design = String(block.design || 'cta');
            return (
                <div className={`ld-app-btn-wrap${design === 'sticky' ? ' ld-app-btn-wrap--sticky' : ''}`} style={style}>
                    <span
                        className={`ld-app-btn ld-app-btn--${design === 'sticky' ? 'cta' : design}${design === 'sticky' ? ' ld-app-btn--sticky' : ''}${block.full_width !== false ? ' is-full' : ''}`}
                        style={{
                            ['--btn-bg' as string]: String(block.color || '#0f172a'),
                            ['--btn-fg' as string]: String(block.text_color || '#fff'),
                        }}
                        {...previewNavProps(block.action)}
                    >
                        {String(block.text || 'ボタン')}
                    </span>
                </div>
            );
        }
        case 'card': {
            const design = String(block.design || 'editorial');
            const image = typeof block.image_url === 'string' ? block.image_url : '';
            return (
                <div className={`ld-app-card ld-app-card--${design}`} style={style} {...previewNavProps(block.action)}>
                    <div
                        className="ld-app-card__media"
                        style={{
                            backgroundImage: image ? `url(${image})` : undefined,
                            minHeight: Number(block.image_height || (design === 'horizontal' ? 88 : 140)),
                        }}
                    />
                    <div className="ld-app-card__body">
                        <strong>{String(block.title || '')}</strong>
                        {block.description ? <span>{String(block.description)}</span> : null}
                        {block.action ? (
                            <em className="ld-app-card__link">
                                詳しく見る <i className="fas fa-arrow-right" />
                            </em>
                        ) : null}
                    </div>
                </div>
            );
        }
        case 'collection': {
            const items = Array.isArray(block.items) ? (block.items as Array<Record<string, unknown>>) : [];
            const design = String(block.design || 'product_shelf');
            if (design === 'chip_scroll') {
                return (
                    <div className="ld-app-collection ld-app-collection--chip_scroll" style={style}>
                        {block.section_label ? (
                            <div className="ld-app-collection__head">
                                <div className="ld-app-collection__label">{String(block.section_label)}</div>
                            </div>
                        ) : null}
                        <div className="ld-app-chip-scroll">
                            {items.slice(0, 8).map((it, i) => (
                                <span key={i} className="ld-app-chip" {...previewNavProps(it.action || block.item_action)}>
                                    {String(it.title || '')}
                                </span>
                            ))}
                        </div>
                    </div>
                );
            }
            const isGrid = block.layout === 'grid';
            return (
                <div className={`ld-app-collection ld-app-collection--${design}`} style={style}>
                    {block.section_label ? (
                        <div className="ld-app-collection__head">
                            <div className="ld-app-collection__label">{String(block.section_label)}</div>
                            <span
                                className="ld-app-collection__more"
                                {...previewNavProps(block.item_action || block.more_action || '#gallery')}
                            >
                                すべて
                            </span>
                        </div>
                    ) : null}
                    <div className={`ld-app-collection__row${isGrid ? ' is-grid' : ''}`}>
                        {items.slice(0, 6).map((it, i) => (
                            <div
                                key={i}
                                className="ld-app-collection__item"
                                {...previewNavProps(it.action || block.item_action)}
                            >
                                <div
                                    className="ld-app-collection__img"
                                    style={{
                                        height: Number(block.item_image_height || 96),
                                        backgroundImage: it.image_url ? `url(${String(it.image_url)})` : undefined,
                                    }}
                                >
                                    {it.badge ? <em>{String(it.badge)}</em> : null}
                                </div>
                                <strong>{String(it.title || '')}</strong>
                                {it.description ? <span>{String(it.description)}</span> : null}
                            </div>
                        ))}
                    </div>
                </div>
            );
        }
        case 'slider': {
            const isGallery = String(block.source || '') === 'gallery';
            const slides = Array.isArray(block.slides) ? (block.slides as Array<Record<string, unknown>>) : [];
            const first = slides[0] || {};
            return (
                <div
                    className="ld-app-slider"
                    style={{
                        ...style,
                        height: Number(block.slide_height || 168),
                        backgroundImage: !isGallery && first.image_url ? `url(${String(first.image_url)})` : undefined,
                    }}
                >
                    <div className="ld-app-slider__cap">
                        {block.section_label ? <em>{String(block.section_label)}</em> : null}
                        <strong>{isGallery ? 'ギャラリー（実データ）' : String(first.caption || 'Campaign')}</strong>
                    </div>
                    <div className="ld-app-slider__dots">
                        {(isGallery ? [0, 1, 2] : (slides.length > 0 ? slides : [0, 1, 2])).slice(0, 4).map((_, i) => (
                            <i key={i} className={i === 0 ? 'is-on' : ''} />
                        ))}
                    </div>
                </div>
            );
        }
        case 'icon_text': {
            const rows = Array.isArray(block.rows) ? (block.rows as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-icon-text" style={style}>
                    {rows.map((r, i) => (
                        <div key={i} className="ld-app-icon-text__row">
                            <span className="ld-app-icon-text__icon" style={{ color: String(r.color || '#2563eb') }}>
                                <i className={`fas ${String(r.icon || 'fa-check')}`} />
                            </span>
                            <span>{String(r.text || '')}</span>
                        </div>
                    ))}
                </div>
            );
        }
        case 'list': {
            const items = Array.isArray(block.items) ? block.items.map(String) : [];
            return (
                <div className={`ld-app-list${block.style === 'number' ? ' is-number' : ''}`} style={style}>
                    {items.map((t, i) => (
                        <div key={i} className="ld-app-list__item">
                            <span className="ld-app-list__mark">{block.style === 'number' ? i + 1 : <i className="fas fa-check" />}</span>
                            <em>{t}</em>
                        </div>
                    ))}
                </div>
            );
        }
        case 'banner': {
            const design = String(block.design || 'promo');
            return (
                <div
                    className={`ld-app-banner ld-app-banner--${design}`}
                    style={{
                        ...style,
                        ['--banner-bg' as string]: String(block.color || '#0f172a'),
                        ['--banner-fg' as string]: String(block.text_color || '#fff'),
                    }}
                    {...previewNavProps(block.action)}
                >
                    <i className={`fas ${String(block.icon || 'fa-bolt')}`} />
                    <span>{String(block.text || '')}</span>
                    <i className="fas fa-chevron-right ld-app-banner__chev" />
                </div>
            );
        }
        case 'spacer':
            return <div style={{ height: Number(block.height || 16) }} />;
        case 'divider':
            return (
                <hr
                    className="ld-app-divider"
                    style={{
                        ...style,
                        borderTopStyle: String(block.divider_style || 'solid') as React.CSSProperties['borderTopStyle'],
                        borderTopColor: String(block.divider_color || '#e2e8f0'),
                    }}
                />
            );
        case 'data':
            return (
                <div style={style}>
                    <DataMockPreview
                        source={String(block.source || 'products')}
                        design={String(block.design || '')}
                        cardDesign={resolvedCardDesign}
                        cardBgStyle={memberCardBgStyle}
                        cardGloss={memberCardGloss}
                        cardSurface={memberCardSurface}
                        couponScreenDesign={couponScreenDesign}
                        couponMediaStyle={couponMediaStyle}
                        couponShowAvailable={couponShowAvailable}
                        couponShowHistory={couponShowHistory}
                        stampDesign={resolvedStampDesign}
                        stampSlotStyle={resolvedStampSlot}
                        stampFilledStyle={resolvedStampFilled}
                        stampAccentColor={resolvedStampAccent}
                        shopName={shopName}
                        asGuest={asGuest}
                    />
                </div>
            );
        case 'member_summary':
            return (
                <div style={style}>
                    <MemberSummaryMock
                        greeting={String(block.greeting || '')}
                        design={String(block.design || 'rewards')}
                        asGuest={asGuest}
                    />
                </div>
            );
        case 'stats_row': {
            const items = Array.isArray(block.items) ? (block.items as Array<Record<string, unknown>>) : [];
            const sampleByField: Record<string, string> = {
                points: '1,280',
                rank: 'ゴールド',
                points_to_next: '720',
            };
            return (
                <div className="ld-app-stats" style={style}>
                    {block.section_label ? (
                        <div className="ld-app-stats__label">{String(block.section_label)}</div>
                    ) : null}
                    <div className="ld-app-stats__grid">
                        {items.slice(0, 6).map((it, i) => {
                            const field = String(it.field || '');
                            const label = String(it.label || '');
                            let display = String(it.value || '');
                            if (field === 'points' || field === 'rank' || field === 'points_to_next') {
                                display = sampleByField[field] || display;
                            } else if (label === 'ポイント' || label === '保有ポイント' || label === '所持ポイント') {
                                display = sampleByField.points;
                            } else if (label === 'ランク' || label === '会員ランク' || label === '現在のランク') {
                                display = sampleByField.rank;
                            } else if (/次回特典|次のランク|特典まで|ランクアップまで/.test(label)) {
                                display = sampleByField.points_to_next;
                            }
                            return (
                                <div key={i} className="ld-app-stats__cell">
                                    <strong>{display}</strong>
                                    <span>
                                        {label}
                                        {it.hint ? ` ${String(it.hint)}` : ''}
                                    </span>
                                </div>
                            );
                        })}
                    </div>
                </div>
            );
        }
        case 'quick_actions': {
            const actions = Array.isArray(block.actions) ? (block.actions as Array<Record<string, unknown>>) : [];
            const design = String(block.design || 'grid');
            return (
                <div className={`ld-app-qa ld-app-qa--${design}`} style={style}>
                    {actions.slice(0, 8).map((a, i) => (
                        <div key={i} className="ld-app-qa__tile" {...previewNavProps(a.action)}>
                            <span className="ld-app-qa__icon" style={{ ['--qa-accent' as string]: String(a.color || '') || undefined }}>
                                <i className={`fas ${String(a.icon || 'fa-circle')}`} />
                            </span>
                            <em>{String(a.label || '')}</em>
                        </div>
                    ))}
                </div>
            );
        }
        case 'friend_add': {
            const url = String(block.url || friendAddUrl || '');
            return (
                <div className="ld-app-btn-wrap" style={style}>
                    <span className="ld-app-btn ld-app-btn--cta is-full" style={{ ['--btn-bg' as string]: String(block.color || '#06C755') }}>
                        <i className="fab fa-line" /> {String(block.text || '友だち追加')}
                    </span>
                    {url === '' ? <div className="ld-app-warn">URL未設定</div> : null}
                </div>
            );
        }
        case 'hours': {
            const rows = Array.isArray(block.rows) ? (block.rows as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-storecard" style={style}>
                    <div className="ld-app-storecard__head">
                        <i className="fas fa-clock" />
                        <strong>{String(block.title || '営業時間')}</strong>
                        <em className="is-open">本日営業中</em>
                    </div>
                    <div className="ld-app-storecard__rows">
                        {rows.map((r, i) => (
                            <div key={i} className="ld-app-storecard__row">
                                <span>{String(r.day || '')}</span>
                                <strong>{String(r.time || '')}</strong>
                            </div>
                        ))}
                    </div>
                </div>
            );
        }
        case 'access':
            return (
                <div className="ld-app-storecard" style={style}>
                    <div className="ld-app-storecard__head">
                        <i className="fas fa-map-marker-alt" />
                        <strong>{String(block.title || '店舗情報')}</strong>
                    </div>
                    <div className="ld-app-storecard__map" aria-hidden />
                    <div className="ld-app-storecard__lines">
                        <div className="ld-app-storecard__line">
                            <i className="fas fa-location-dot" />
                            <span>{String(block.address || '')}</span>
                        </div>
                        <div className="ld-app-storecard__line">
                            <i className="fas fa-phone" />
                            <span>{String(block.phone || '')}</span>
                        </div>
                    </div>
                    <div className="ld-app-storecard__actions">
                        <span><i className="fas fa-route" /> ルート</span>
                        <span><i className="fas fa-phone" /> 電話</span>
                    </div>
                </div>
            );
        case 'social_links': {
            const links = Array.isArray(block.links) ? (block.links as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-social" style={style}>
                    {links.map((l, i) => {
                        const key = String(l.type || '').toLowerCase();
                        const meta = SOCIAL_META[key] || { icon: 'fas fa-link', label: key || 'Link', color: '#64748b' };
                        return (
                            <span key={i} className="ld-app-social__chip" style={{ ['--social' as string]: meta.color }}>
                                <i className={meta.icon} />
                                {meta.label}
                            </span>
                        );
                    })}
                </div>
            );
        }
        case 'faq': {
            const items = Array.isArray(block.items) ? (block.items as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-faq" style={style}>
                    <div className="ld-app-faq__title">{String(block.title || 'よくある質問')}</div>
                    {items.slice(0, 4).map((it, i) => (
                        <details key={i} className="ld-app-faq__item" open={i === 0}>
                            <summary>
                                <strong>Q</strong>
                                <span>{String(it.q || '')}</span>
                                <i className="fas fa-chevron-down" />
                            </summary>
                            <p>{String(it.a || '詳しくはお店までお問い合わせください。')}</p>
                        </details>
                    ))}
                </div>
            );
        }
        case 'video':
            return (
                <div
                    className="ld-app-video"
                    style={{
                        ...style,
                        backgroundImage: block.poster_url ? `url(${String(block.poster_url)})` : undefined,
                    }}
                >
                    <div className="ld-app-video__play">
                        <i className="fas fa-play" />
                    </div>
                    <span>{String(block.caption || 'Store movie')}</span>
                </div>
            );
        case 'button_group': {
            const buttons = Array.isArray(block.buttons) ? (block.buttons as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-btn-group" style={style}>
                    {buttons.map((b, i) => (
                        <span
                            key={i}
                            className={`ld-app-btn ld-app-btn--${String(b.variant || 'cta')}`}
                            style={{ ['--btn-bg' as string]: String(b.color || '#0f172a') }}
                        >
                            {String(b.text || '')}
                        </span>
                    ))}
                </div>
            );
        }
        default: {
            const meta = findBlockMeta(block.type);
            return (
                <div className="ld-app-fallback" style={style}>
                    未対応: {meta?.label || block.type}
                </div>
            );
        }
    }
}
