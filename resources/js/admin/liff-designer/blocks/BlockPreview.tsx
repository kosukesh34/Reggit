import React from 'react';
import { findBlockMeta } from '../catalog/blockCatalog';
import type { LiffBlock } from '../catalog/types';
import { DataMockPreview, MemberSummaryMock } from './DataMockPreview';

function baseStyle(block: LiffBlock): React.CSSProperties {
    return {
        borderRadius: Number(block.border_radius ?? 14),
        marginBottom: Number(block.margin_bottom ?? 0),
        opacity: Number(block.opacity ?? 100) / 100,
        textAlign: (block.text_align as React.CSSProperties['textAlign']) || 'left',
        background: String(block.background_color || '') || undefined,
        boxShadow: block.shadow === true ? '0 8px 24px rgba(15,23,42,0.08)' : undefined,
    };
}

export function BlockPreview({ block, friendAddUrl }: { block: LiffBlock; friendAddUrl?: string }) {
    const style = baseStyle(block);
    switch (block.type) {
        case 'hero': {
            const design = String(block.design || 'editorial');
            const image = typeof block.image_url === 'string' ? block.image_url : '';
            const bg = image
                ? undefined
                : `linear-gradient(135deg, ${String(block.gradient_start || '#0f172a')}, ${String(block.gradient_end || '#334155')})`;
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
                        {design !== 'minimal' ? <span className="ld-app-hero__eyebrow">Welcome</span> : null}
                        <strong className="ld-app-hero__title">{String(block.title || '')}</strong>
                        {block.subtitle || block.text ? (
                            <span className="ld-app-hero__sub">{String(block.subtitle || block.text || '')}</span>
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
                <div className={`ld-app-image ld-app-image--${String(block.design || 'rounded')}`} style={{ ...style }}>
                    {block.image_url ? (
                        <img src={String(block.image_url)} alt={String(block.alt || '')} />
                    ) : (
                        <div className="ld-app-image__ph" />
                    )}
                </div>
            );
        case 'button': {
            const design = String(block.design || 'cta');
            return (
                <div className="ld-app-btn-wrap" style={style}>
                    <span
                        className={`ld-app-btn ld-app-btn--${design}${block.full_width !== false ? ' is-full' : ''}`}
                        style={{
                            ['--btn-bg' as string]: String(block.color || '#0f172a'),
                            ['--btn-fg' as string]: String(block.text_color || '#fff'),
                        }}
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
                <div className={`ld-app-card ld-app-card--${design}`} style={style}>
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
                    </div>
                </div>
            );
        }
        case 'collection': {
            const items = Array.isArray(block.items) ? (block.items as Array<Record<string, unknown>>) : [];
            const design = String(block.design || 'product_shelf');
            const isGrid = block.layout === 'grid';
            return (
                <div className={`ld-app-collection ld-app-collection--${design}`} style={style}>
                    {block.section_label ? <div className="ld-app-collection__label">{String(block.section_label)}</div> : null}
                    <div className={`ld-app-collection__row${isGrid ? ' is-grid' : ''}`}>
                        {items.slice(0, 6).map((it, i) => (
                            <div key={i} className="ld-app-collection__item">
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
            const slides = Array.isArray(block.slides) ? (block.slides as Array<Record<string, unknown>>) : [];
            const first = slides[0] || {};
            return (
                <div
                    className="ld-app-slider"
                    style={{
                        ...style,
                        height: Number(block.slide_height || 160),
                        backgroundImage: first.image_url ? `url(${String(first.image_url)})` : undefined,
                    }}
                >
                    <div className="ld-app-slider__cap">
                        {block.section_label ? <em>{String(block.section_label)}</em> : null}
                        <strong>{String(first.caption || 'Campaign')}</strong>
                    </div>
                    <div className="ld-app-slider__dots">
                        {slides.slice(0, 3).map((_, i) => (
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
                            <span>{block.style === 'number' ? i + 1 : '•'}</span>
                            {t}
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
            return <hr className="ld-app-divider" style={{ ...style, borderTopStyle: String(block.divider_style || 'solid') as React.CSSProperties['borderTopStyle'], borderTopColor: String(block.divider_color || '#e2e8f0') }} />;
        case 'data':
            return (
                <div style={style}>
                    <DataMockPreview
                        source={String(block.source || 'products')}
                        design={String(block.design || '')}
                        cardDesign={String(block.card_design || '')}
                    />
                </div>
            );
        case 'member_summary':
            return (
                <div style={style}>
                    <MemberSummaryMock greeting={String(block.greeting || '')} design={String(block.design || 'rewards')} />
                </div>
            );
        case 'quick_actions': {
            const actions = Array.isArray(block.actions) ? (block.actions as Array<Record<string, unknown>>) : [];
            const design = String(block.design || 'grid');
            return (
                <div className={`ld-app-qa ld-app-qa--${design}`} style={style}>
                    {actions.slice(0, 8).map((a, i) => (
                        <div key={i} className="ld-app-qa__tile">
                            <span className="ld-app-qa__icon">
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
                <div className="ld-app-panel" style={style}>
                    <div className="ld-app-panel__title">{String(block.title || '営業時間')}</div>
                    {rows.map((r, i) => (
                        <div key={i} className="ld-app-panel__row">
                            <span>{String(r.day || '')}</span>
                            <strong>{String(r.time || '')}</strong>
                        </div>
                    ))}
                </div>
            );
        }
        case 'access':
            return (
                <div className="ld-app-panel" style={style}>
                    <div className="ld-app-panel__title">{String(block.title || '店舗情報')}</div>
                    <div className="ld-app-panel__line"><i className="fas fa-map-marker-alt" /> {String(block.address || '')}</div>
                    <div className="ld-app-panel__line"><i className="fas fa-phone" /> {String(block.phone || '')}</div>
                </div>
            );
        case 'social_links': {
            const links = Array.isArray(block.links) ? (block.links as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-social" style={style}>
                    {links.map((l, i) => (
                        <span key={i}>{String(l.type || '').slice(0, 2)}</span>
                    ))}
                </div>
            );
        }
        case 'faq': {
            const items = Array.isArray(block.items) ? (block.items as Array<Record<string, unknown>>) : [];
            return (
                <div className="ld-app-panel" style={style}>
                    <div className="ld-app-panel__title">{String(block.title || 'FAQ')}</div>
                    {items.slice(0, 3).map((it, i) => (
                        <div key={i} className="ld-app-panel__faq"><strong>Q.</strong> {String(it.q || '')}</div>
                    ))}
                </div>
            );
        }
        case 'video':
            return (
                <div className="ld-app-video" style={style}>
                    <i className="fas fa-play-circle" />
                    <span>{String(block.caption || '動画')}</span>
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
