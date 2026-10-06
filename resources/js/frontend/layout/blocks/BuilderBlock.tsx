import type { JSX } from 'react';
import React, { useEffect, useMemo, useState } from 'react';
import type { CSSProperties } from 'react';
import { baseStyleObject, marginBottomStyle, mergeStyles } from './blockStyles';
import { isExternalHref } from './builderNav';
import { DataBlockMount } from './DataBlockMount';
import { fetchMemberProfile, formatBannerPointsToNext, formatStatsValue, resolveStatsField } from './memberBind';
import { MemberSummaryBlock } from './MemberSummaryBlock';
import { SliderBlock } from './SliderBlock';
import type { BuilderBlockData, BuilderNavItem } from './types';

type BuilderBlockProps = {
    block: BuilderBlockData;
};

const DEFAULT_QUICK_ACTIONS: Array<{ icon: string; label: string; action: string }> = [
    { icon: 'fa-utensils', label: 'メニュー', action: '#products' },
    { icon: 'fa-ticket-alt', label: 'クーポン', action: '#coupons' },
    { icon: 'fa-stamp', label: 'スタンプ', action: '#stamps' },
    { icon: 'fa-id-card', label: '会員証', action: '#member' },
];

const SOCIAL_ICON_MAP: Record<string, string> = {
    instagram: 'fab fa-instagram',
    x: 'fab fa-x-twitter',
    facebook: 'fab fa-facebook-f',
    youtube: 'fab fa-youtube',
    tiktok: 'fab fa-tiktok',
    line: 'fab fa-line',
    website: 'fas fa-globe',
};

function externalProps(href: string): { 'data-builder-external'?: '1' } {
    return isExternalHref(href) ? { 'data-builder-external': '1' } : {};
}

function SectionHead({ label, moreHref }: { label: string; moreHref?: string }): JSX.Element | null {
    if (label === '') {
        return null;
    }
    return (
        <div className="builder-section-head">
            <span className="builder-section-head__label">{label}</span>
            {moreHref !== undefined ? (
                <a href={moreHref} className="builder-section-head__link builder-nav">すべて</a>
            ) : null}
        </div>
    );
}

function HeroBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'editorial';
    const style: CSSProperties = { ...baseStyleObject(block) };
    if (block.image_url) {
        style.backgroundImage = `url('${String(block.image_url)}')`;
    } else {
        (style as Record<string, string>)['--hero-grad-start'] = String(block.gradient_start || 'var(--primary-color)');
        (style as Record<string, string>)['--hero-grad-end'] = String(block.gradient_end || 'var(--primary-dark)');
    }
    const eyebrow = block.eyebrow != null ? String(block.eyebrow) : 'Welcome';
    const cta = String(block.button_label || block.cta_label || '');
    return (
        <section className={`builder-block builder-hero builder-hero--${design}`} style={style}>
            <div className="builder-hero__scrim" />
            <div className="builder-hero__content">
                {design !== 'minimal' ? <span className="builder-hero__eyebrow">{eyebrow}</span> : null}
                <h2 className="builder-hero__title">{String(block.title || '')}</h2>
                <p className="builder-hero__subtitle">{String(block.subtitle || block.text || '')}</p>
                {cta !== '' ? <span className="builder-hero__cta">{cta}</span> : null}
            </div>
        </section>
    );
}

function HeadingBlock({ block }: BuilderBlockProps): JSX.Element {
    const size = block.size != null ? String(block.size) : 'medium';
    const align = block.text_align != null ? String(block.text_align) : 'left';
    const design = block.design != null ? String(block.design) : 'plain';
    const textStyle: CSSProperties = {};
    if (block.text_color) {
        textStyle.color = String(block.text_color);
    }
    return (
        <section
            className={`builder-block builder-heading builder-heading--${size} builder-heading--design-${design}`}
            style={mergeStyles({ textAlign: align as CSSProperties['textAlign'] }, baseStyleObject(block))}
        >
            <h3 className="builder-heading__text" style={textStyle}>{String(block.text || '')}</h3>
        </section>
    );
}

function TextBlock({ block }: BuilderBlockProps): JSX.Element {
    const align = block.text_align != null ? String(block.text_align) : 'left';
    const design = block.design != null ? String(block.design) : 'body';
    const bodyStyle: CSSProperties = { fontSize: `${Number(block.font_size || 14)}px` };
    if (block.text_color) {
        bodyStyle.color = String(block.text_color);
    }
    return (
        <section
            className={`builder-block builder-text-block builder-text-block--design-${design}`}
            style={mergeStyles({ textAlign: align as CSSProperties['textAlign'] }, baseStyleObject(block))}
        >
            <div className="builder-text-block__body" style={bodyStyle}>
                {String(block.text || block.body || '')}
            </div>
        </section>
    );
}

function ButtonBlock({ block }: BuilderBlockProps): JSX.Element {
    const action = block.action != null ? String(block.action) : '#';
    const full = block.full_width !== false ? ' builder-btn--full' : '';
    const design = block.design != null ? String(block.design) : 'cta';
    const variantClass = design === 'outline' ? ' builder-btn--outline'
        : design === 'soft' ? ' builder-btn--soft'
            : design === 'sticky' ? ' builder-btn--cta builder-btn--sticky'
                : ' builder-btn--cta';
    const wrapClass = design === 'sticky' ? ' builder-button-wrap--sticky' : '';
    const rawColor = typeof block.color === 'string' ? block.color.trim() : '';
    const isLegacyAccent = /^#(007aff|4f46e5|5856d6|4338ca)$/i.test(rawColor);
    const color = rawColor !== '' && !isLegacyAccent ? rawColor : '';
    const btnStyle: CSSProperties & Record<string, string> = {};
    if (color !== '') {
        btnStyle['--btn-bg'] = color;
        if (block.text_color) {
            btnStyle['--btn-fg'] = String(block.text_color);
        }
        if (design === 'outline' || design === 'soft') {
            btnStyle['--btn-accent'] = color;
        }
    }
    return (
        <section className={`builder-block builder-button-wrap${wrapClass}`} style={baseStyleObject(block)}>
            <a
                href={action}
                className={`builder-btn${variantClass}${full}`}
                {...externalProps(action)}
                style={Object.keys(btnStyle).length > 0 ? btnStyle : undefined}
            >
                {String(block.text || '')}
            </a>
        </section>
    );
}

function CardMedia({ block, imgH }: { block: BuilderBlockData; imgH: number }): JSX.Element {
    const img = block.image_url
        ? <img className="builder-editorial-card__img" src={String(block.image_url)} alt="" loading="lazy" />
        : (
            <div className="builder-editorial-card__img builder-editorial-card__img--placeholder" aria-hidden="true">
                <i className="fas fa-image" />
            </div>
        );
    return (
        <div
            className="builder-editorial-card__media"
            style={{ ['--card-img-h' as string]: `${imgH}px` }}
        >
            {img}
        </div>
    );
}

function CardBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'editorial';
    const imgH = Number(block.image_height || 160);
    const action = String(block.action || block.link || '');
    const base = baseStyleObject(block);
    const desc = block.description ? <p className="builder-editorial-card__desc">{String(block.description)}</p> : null;
    const title = <h4 className="builder-editorial-card__title">{String(block.title || '')}</h4>;

    const img = block.image_url
        ? <img className="builder-editorial-card__img" src={String(block.image_url)} alt="" loading="lazy" />
        : (
            <div className="builder-editorial-card__img builder-editorial-card__img--placeholder" aria-hidden="true">
                <i className="fas fa-image" />
            </div>
        );

    let body: JSX.Element;
    let mod = '';
    if (design === 'horizontal') {
        mod = ' builder-editorial-card--horizontal';
        body = (
            <>
                <div
                    className="builder-editorial-card__media builder-editorial-card__media--horizontal"
                    style={{ ['--card-img-h' as string]: `${imgH}px` }}
                >
                    {img}
                </div>
                <div className="builder-editorial-card__body">{title}{desc}</div>
            </>
        );
    } else if (design === 'overlay') {
        mod = ' builder-editorial-card--overlay';
        body = (
            <div
                className="builder-editorial-card__media builder-editorial-card__media--overlay"
                style={{ ['--card-img-h' as string]: `${imgH}px` }}
            >
                {img}
                <div className="builder-editorial-card__overlay-text">{title}{desc}</div>
            </div>
        );
    } else if (design === 'featured') {
        mod = ' builder-editorial-card--featured';
        body = (
            <>
                <div
                    className="builder-editorial-card__media builder-editorial-card__media--featured"
                    style={{ ['--card-img-h' as string]: `${imgH}px` }}
                >
                    {img}
                    <span className="builder-editorial-card__featured-badge">Featured</span>
                </div>
                <div className="builder-editorial-card__body">{title}{desc}</div>
            </>
        );
    } else {
        body = (
            <>
                <CardMedia block={block} imgH={imgH} />
                <div className="builder-editorial-card__body">{title}{desc}</div>
            </>
        );
    }

    if (action !== '') {
        return (
            <a
                href={action}
                className={`builder-block builder-editorial-card${mod} builder-editorial-card--link builder-nav`}
                {...externalProps(action)}
                style={base}
            >
                {body}
            </a>
        );
    }
    return (
        <article className={`builder-block builder-editorial-card${mod}`} style={base}>
            {body}
        </article>
    );
}

function BannerBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'promo';
    const bg = block.color != null ? String(block.color) : '';
    const fg = block.text_color != null ? String(block.text_color) : '';
    let extra: CSSProperties = {};
    if (design === 'outline') {
        extra = bg !== '' ? { borderColor: bg, color: bg } : {};
    } else if (design === 'soft') {
        extra = bg !== ''
            ? {
                background: `color-mix(in srgb, ${bg} 14%, #ffffff)`,
                color: bg,
                border: `1px solid color-mix(in srgb, ${bg} 24%, #e5e5ea)`,
            }
            : {};
    } else {
        extra = {
            ...(bg !== '' ? { background: bg } : {}),
            ...(fg !== '' ? { color: fg } : {}),
        };
    }
    const bindField = block.field === 'points_to_next'
        || /次のランクまで/.test(String(block.text || ''))
        ? 'points_to_next'
        : '';
    const [text, setText] = useState(String(block.text || ''));
    useEffect(() => {
        if (bindField !== 'points_to_next') {
            return;
        }
        let cancelled = false;
        void fetchMemberProfile().then((data) => {
            if (!cancelled) {
                setText(formatBannerPointsToNext(data));
            }
        });
        return () => {
            cancelled = true;
        };
    }, [bindField]);

    const countdown = design === 'countdown'
        ? (
            <span className="builder-promo-banner__countdown" aria-hidden="true">
                <em>限定</em>
                <strong>24h</strong>
            </span>
        )
        : null;
    const inner = (
        <>
            {block.icon ? (
                <span className="builder-promo-banner__icon">
                    <i className={`fas ${String(block.icon)}`} aria-hidden="true" />
                </span>
            ) : null}
            <span className="builder-promo-banner__text">{text}</span>
            {countdown}
            <i className="fas fa-chevron-right builder-promo-banner__chev" aria-hidden="true" />
        </>
    );
    const action = String(block.action || block.link || '');
    const designClass = ` builder-promo-banner--${design}`;
    const bindAttrs = bindField !== ''
        ? { 'data-source': 'banner_bind', 'data-bind-field': bindField }
        : {};
    const style = mergeStyles(extra, baseStyleObject(block));
    if (action !== '') {
        return (
            <a
                href={action}
                className={`builder-block builder-promo-banner builder-promo-banner--link${designClass} builder-nav`}
                {...externalProps(action)}
                {...bindAttrs}
                style={style}
            >
                {inner}
            </a>
        );
    }
    return (
        <section className={`builder-block builder-promo-banner${designClass}`} {...bindAttrs} style={style}>
            {inner}
        </section>
    );
}

function CollectionBlock({ block }: BuilderBlockProps): JSX.Element {
    const items = useMemo(
        () => (Array.isArray(block.items) ? block.items : []) as BuilderNavItem[],
        [block.items],
    );
    const design = block.design != null ? String(block.design) : 'product_shelf';
    const base = baseStyleObject(block);

    if (design === 'chip_scroll') {
        const label = String(block.section_label || block.title || '');
        return (
            <section className="builder-block builder-collection-wrap builder-collection-wrap--design-chip_scroll" style={base}>
                <SectionHead label={label} />
                <div className="builder-chip-scroll">
                    {items.map((item, i) => {
                        const action = String(item.action || block.item_action || '#products');
                        return (
                            <a key={i} href={action} className="builder-chip builder-nav" {...externalProps(action)}>
                                {String(item.title || '')}
                            </a>
                        );
                    })}
                </div>
            </section>
        );
    }

    const isGrid = block.layout === 'grid';
    const cols = Number(block.columns || 2);
    const gap = block.gap != null ? Number(block.gap) : 12;
    const maxRows = Number(block.max_rows || 0);
    const visibleCount = (isGrid && maxRows > 0) ? maxRows * cols : items.length;
    const visible = items.slice(0, visibleCount);
    const layoutClass = isGrid ? `builder-collection--grid builder-collection--cols-${cols}` : 'builder-collection--scroll';
    const imgH = Number(block.item_image_height || (isGrid ? 120 : 130));
    const defaultItemAction = String(block.item_action || '#products');
    const label = String(block.section_label || block.title || '');
    const moreHref = String(block.more_action || block.item_action || '#products');

    return (
        <section className={`builder-block builder-collection-wrap builder-collection-wrap--design-${design}`} style={base}>
            <SectionHead label={label} moreHref={label !== '' ? moreHref : undefined} />
            <div
                className={`builder-collection ${layoutClass} builder-collection--design-${design}`}
                style={{ ['--collection-gap' as string]: `${gap}px` }}
            >
                {visible.map((item, i) => {
                    const action = String(item.action || defaultItemAction);
                    return (
                        <a key={i} href={action} className="builder-collection__item builder-nav" {...externalProps(action)}>
                            <div className="builder-collection__media" style={{ height: `${imgH}px` }}>
                                {item.badge ? <span className="builder-collection__badge">{String(item.badge)}</span> : null}
                                {item.image_url
                                    ? <img src={String(item.image_url)} alt="" loading="lazy" />
                                    : (
                                        <div className="builder-collection__placeholder">
                                            <i className="fas fa-image" />
                                        </div>
                                    )}
                            </div>
                            <div className="builder-collection__meta">
                                <span className="builder-collection__title">{String(item.title || '')}</span>
                                {block.show_description !== false && item.description
                                    ? <p className="builder-collection__desc">{String(item.description)}</p>
                                    : null}
                            </div>
                        </a>
                    );
                })}
            </div>
        </section>
    );
}

function DataBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'cards';
    const source = block.source != null ? String(block.source) : '';
    return (
        <section
            className={`builder-block builder-data-block builder-data-block--design-${design}`}
            style={baseStyleObject(block)}
            data-source={source}
            data-design={design}
            {...(block.card_design ? { 'data-card-design': String(block.card_design) } : {})}
            {...(block.stamp_design ? { 'data-stamp-design': String(block.stamp_design) } : {})}
            {...(block.stamp_slot_style ? { 'data-stamp-slot-style': String(block.stamp_slot_style) } : {})}
            {...(block.stamp_filled_style ? { 'data-stamp-filled-style': String(block.stamp_filled_style) } : {})}
            {...(block.stamp_accent_color ? { 'data-stamp-accent-color': String(block.stamp_accent_color) } : {})}
        >
            <DataBlockMount source={source} />
        </section>
    );
}

function StatsRowBlock({ block }: BuilderBlockProps): JSX.Element {
    const items = useMemo(
        () => (Array.isArray(block.items) ? block.items : []) as BuilderNavItem[],
        [block.items],
    );
    const [values, setValues] = useState<Record<number, string>>(() => {
        const init: Record<number, string> = {};
        items.forEach((it, i) => {
            init[i] = String(it.value || '');
        });
        return init;
    });

    useEffect(() => {
        const fields = items.map((it) => resolveStatsField(it));
        if (!fields.some((f) => f !== 'static')) {
            return;
        }
        let cancelled = false;
        void fetchMemberProfile().then((data) => {
            if (cancelled) {
                return;
            }
            const next: Record<number, string> = {};
            items.forEach((it, i) => {
                const field = fields[i] ?? 'static';
                next[i] = field === 'static' ? String(it.value || '') : formatStatsValue(field, data);
            });
            setValues(next);
        });
        return () => {
            cancelled = true;
        };
    }, [items]);

    return (
        <section className="builder-block builder-stats-row" data-source="stats_row" style={marginBottomStyle(block)}>
            {block.section_label ? (
                <div className="builder-section-head">
                    <span className="builder-section-head__label">{String(block.section_label)}</span>
                </div>
            ) : null}
            <div className="builder-stats-row__grid">
                {items.map((it, i) => {
                    const field = resolveStatsField(it);
                    return (
                        <div key={i} className="builder-stats-row__cell" data-stats-field={field}>
                            <span className="builder-stats-row__value">{values[i] ?? ''}</span>
                            <span className="builder-stats-row__label">
                                {String(it.label || '')}
                                {it.hint ? <> <em>{String(it.hint)}</em></> : null}
                            </span>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}

function QuickActionsBlock({ block }: BuilderBlockProps): JSX.Element {
    const actions = (Array.isArray(block.actions) && (block.actions as unknown[]).length > 0)
        ? (block.actions as Array<{ icon?: string; label?: string; action?: string }>)
        : DEFAULT_QUICK_ACTIONS;
    const design = block.design != null ? String(block.design) : 'grid';
    return (
        <section className={`builder-block builder-quick-actions builder-quick-actions--${design}`} style={marginBottomStyle(block)}>
            <div className="builder-section-head">
                <span className="builder-section-head__label">ショートカット</span>
            </div>
            <div className={`quick-actions-grid quick-actions--${design}`}>
                {actions.map((a, i) => {
                    const action = a.action || '#';
                    return (
                        <a key={i} href={action} className="quick-action-tile builder-nav" {...externalProps(action)}>
                            <span className="qa-icon">
                                <i className={`fas ${a.icon || 'fa-circle'}`} aria-hidden="true" />
                            </span>
                            <span className="qa-label">{a.label || ''}</span>
                        </a>
                    );
                })}
            </div>
        </section>
    );
}

function ImageBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'rounded';
    return (
        <section className={`builder-block builder-image-block builder-image-block--design-${design}`} style={baseStyleObject(block)}>
            {block.image_url
                ? <img className="builder-image-block__img" src={String(block.image_url)} alt={String(block.alt || '')} loading="lazy" />
                : null}
        </section>
    );
}

function FriendAddBlock({ block }: BuilderBlockProps): JSX.Element | null {
    const globalUrl = typeof window.__LINE_FRIEND_ADD_URL__ === 'string' ? window.__LINE_FRIEND_ADD_URL__ : '';
    const friendAddUrl = String(block.url || globalUrl || '');
    if (friendAddUrl === '') {
        return null;
    }
    const text = block.text != null ? String(block.text) : '友だち追加';
    const colorStyle: CSSProperties & Record<string, string> = block.color
        ? {
            '--btn-bg': String(block.color),
            ...(block.text_color ? { '--btn-fg': String(block.text_color) } : {}),
        }
        : { '--btn-bg': '#06C755' };
    return (
        <section
            className="builder-block builder-friend-add"
            style={mergeStyles({ textAlign: 'center', padding: '8px' }, baseStyleObject(block))}
            data-friend-add-url={friendAddUrl}
        >
            <a href={friendAddUrl} className="builder-btn builder-btn--friend-add" style={colorStyle} data-builder-external="1">
                <i className="fab fa-line" /> {text}
            </a>
        </section>
    );
}

function IconTextBlock({ block }: BuilderBlockProps): JSX.Element {
    const rows = (Array.isArray(block.rows) ? block.rows : []) as Array<{ icon?: string; color?: string; text?: string }>;
    return (
        <section
            className="builder-block"
            style={mergeStyles({
                background: '#fff',
                padding: '12px 14px',
                display: 'flex',
                flexDirection: 'column',
                gap: '8px',
            }, baseStyleObject(block))}
        >
            {rows.map((r, i) => (
                <div key={i} style={{ display: 'flex', alignItems: 'center', gap: '10px', fontSize: '13px', color: '#374151' }}>
                    <i
                        className={`fas ${r.icon || 'fa-check'}`}
                        style={{ color: r.color || 'var(--primary-color)', fontSize: '16px', flexShrink: 0 }}
                    />
                    <span>{String(r.text || '')}</span>
                </div>
            ))}
        </section>
    );
}

function ListBlock({ block }: BuilderBlockProps): JSX.Element {
    const items = (Array.isArray(block.items) ? block.items : []) as unknown[];
    const tag = block.style === 'number' ? 'ol' : 'ul';
    const listStyle: CSSProperties = {
        margin: 0,
        paddingLeft: '20px',
        fontSize: '13px',
        lineHeight: 1.8,
        color: block.text_color != null ? String(block.text_color) : '#374151',
    };
    const list = items.length > 0
        ? React.createElement(
            tag,
            { style: listStyle },
            items.map((item, i) => <li key={i}>{String(item)}</li>),
        )
        : null;
    return (
        <section
            className="builder-block"
            style={mergeStyles({ background: '#fff', padding: '12px 14px' }, baseStyleObject(block))}
        >
            {list}
        </section>
    );
}

function HoursBlock({ block }: BuilderBlockProps): JSX.Element {
    const rows = (Array.isArray(block.rows) ? block.rows : []) as BuilderNavItem[];
    const design = block.design != null ? String(block.design) : 'table';
    return (
        <section className={`builder-block builder-hours builder-hours--design-${design}`} style={baseStyleObject(block)}>
            {block.title ? (
                <div className="builder-block__title">
                    <i className="far fa-clock" aria-hidden="true" /> {String(block.title)}
                </div>
            ) : null}
            <div className="builder-hours__table">
                {rows.map((r, i) => (
                    <div key={i} className="builder-hours__row">
                        <span className="builder-hours__day">{String(r.day || '')}</span>
                        <span className="builder-hours__time">{String(r.time || '')}</span>
                    </div>
                ))}
            </div>
            {block.note ? <p className="builder-hours__note">{String(block.note)}</p> : null}
        </section>
    );
}

function AccessBlock({ block }: BuilderBlockProps): JSX.Element {
    const design = block.design != null ? String(block.design) : 'card';
    const phone = block.phone != null ? String(block.phone) : '';
    return (
        <section className={`builder-block builder-access builder-access--design-${design}`} style={baseStyleObject(block)}>
            {block.title ? (
                <div className="builder-block__title">
                    <i className="fas fa-store" aria-hidden="true" /> {String(block.title)}
                </div>
            ) : null}
            <div className="builder-access__rows">
                {block.address ? (
                    <div className="builder-access__row">
                        <i className="fas fa-map-marker-alt" aria-hidden="true" />
                        <span>{String(block.address)}</span>
                    </div>
                ) : null}
                {phone !== '' ? (
                    <a className="builder-access__row builder-access__row--link" href={`tel:${phone.replace(/[^0-9+]/g, '')}`}>
                        <i className="fas fa-phone" aria-hidden="true" />
                        <span>{phone}</span>
                    </a>
                ) : null}
                {block.note ? (
                    <div className="builder-access__row">
                        <i className="fas fa-info-circle" aria-hidden="true" />
                        <span>{String(block.note)}</span>
                    </div>
                ) : null}
            </div>
            {block.map_url ? (
                <a href={String(block.map_url)} className="builder-btn builder-access__map-btn" data-builder-external="1">
                    <i className="fas fa-map" aria-hidden="true" /> 地図を開く
                </a>
            ) : null}
        </section>
    );
}

function SocialLinksBlock({ block }: BuilderBlockProps): JSX.Element | null {
    const links = ((Array.isArray(block.links) ? block.links : []) as BuilderNavItem[]).filter((l) => l && l.url);
    if (links.length === 0) {
        return null;
    }
    return (
        <section className="builder-block builder-social" style={baseStyleObject(block)}>
            <div className="builder-social__links">
                {links.map((l, i) => (
                    <a
                        key={i}
                        href={String(l.url)}
                        className={`builder-social__link builder-social__link--${String(l.type || '')}`}
                        data-builder-external="1"
                        aria-label={String(l.type || '')}
                    >
                        <i className={SOCIAL_ICON_MAP[String(l.type || '')] || 'fas fa-link'} aria-hidden="true" />
                    </a>
                ))}
            </div>
        </section>
    );
}

function FaqBlock({ block }: BuilderBlockProps): JSX.Element {
    const items = (Array.isArray(block.items) ? block.items : []) as BuilderNavItem[];
    const design = block.design != null ? String(block.design) : 'accordion';
    return (
        <section className={`builder-block builder-faq builder-faq--design-${design}`} style={baseStyleObject(block)}>
            {block.title ? (
                <div className="builder-block__title">
                    <i className="far fa-question-circle" aria-hidden="true" /> {String(block.title)}
                </div>
            ) : null}
            <div className="builder-faq__list">
                {items.map((it, i) => (
                    <details key={i} className="builder-faq__item">
                        <summary className="builder-faq__q">
                            <span>{String(it.q || '')}</span>
                            <i className="fas fa-chevron-down" aria-hidden="true" />
                        </summary>
                        <div className="builder-faq__a">{String(it.a || '')}</div>
                    </details>
                ))}
            </div>
        </section>
    );
}

function VideoBlock({ block }: BuilderBlockProps): JSX.Element | null {
    const id = String(block.youtube_id || '');
    if (!/^[a-zA-Z0-9_-]{11}$/.test(id)) {
        return null;
    }
    return (
        <section className="builder-block builder-video" style={baseStyleObject(block)}>
            <div className="builder-video__frame">
                <iframe
                    src={`https://www.youtube-nocookie.com/embed/${id}`}
                    title="YouTube video"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowFullScreen
                />
            </div>
            {block.caption ? <p className="builder-video__caption">{String(block.caption)}</p> : null}
        </section>
    );
}

function ButtonGroupBlock({ block }: BuilderBlockProps): JSX.Element | null {
    const buttons = ((Array.isArray(block.buttons) ? block.buttons : []) as BuilderNavItem[]).filter((b) => b && b.text);
    if (buttons.length === 0) {
        return null;
    }
    return (
        <section className="builder-block builder-btn-group" style={baseStyleObject(block)}>
            <div className="builder-btn-group__row" data-count={buttons.length}>
                {buttons.map((b, i) => {
                    const action = b.action || '#';
                    const isOutline = b.variant === 'outline';
                    const color = b.color || '';
                    const style: CSSProperties & Record<string, string> = {};
                    if (color !== '') {
                        if (isOutline) {
                            style['--btn-accent'] = color;
                            style.color = color;
                            style.borderColor = color;
                        } else {
                            style['--btn-bg'] = color;
                        }
                    }
                    return (
                        <a
                            key={i}
                            href={action}
                            className={`builder-btn builder-btn-group__btn${isOutline ? ' builder-btn-group__btn--outline' : ''}`}
                            {...externalProps(action)}
                            style={Object.keys(style).length > 0 ? style : undefined}
                        >
                            {String(b.text)}
                        </a>
                    );
                })}
            </div>
        </section>
    );
}

function FallbackTextBlock({ block }: BuilderBlockProps): JSX.Element {
    return (
        <section
            className="builder-block"
            style={mergeStyles({ background: '#fff', padding: '12px 14px' }, baseStyleObject(block))}
        >
            {String(block.text || block.body || '')}
        </section>
    );
}

export function BuilderBlock({ block }: BuilderBlockProps): JSX.Element | null {
    if (block.hidden) {
        return null;
    }
    const type = block.type != null ? String(block.type) : 'text';
    switch (type) {
        case 'data':
            return <DataBlock block={block} />;
        case 'member_summary':
            return <MemberSummaryBlock block={block} />;
        case 'stats_row':
            return <StatsRowBlock block={block} />;
        case 'quick_actions':
            return <QuickActionsBlock block={block} />;
        case 'hero':
            return <HeroBlock block={block} />;
        case 'heading':
            return <HeadingBlock block={block} />;
        case 'text':
            return <TextBlock block={block} />;
        case 'button':
            return <ButtonBlock block={block} />;
        case 'image':
            return <ImageBlock block={block} />;
        case 'friend_add':
            return <FriendAddBlock block={block} />;
        case 'card':
            return <CardBlock block={block} />;
        case 'collection':
            return <CollectionBlock block={block} />;
        case 'slider':
            return <SliderBlock block={block} />;
        case 'icon_text':
            return <IconTextBlock block={block} />;
        case 'list':
            return <ListBlock block={block} />;
        case 'banner':
            return <BannerBlock block={block} />;
        case 'spacer':
            return <div style={mergeStyles({ height: `${Number(block.height || 16)}px` }, marginBottomStyle(block))} />;
        case 'divider':
            return (
                <div style={mergeStyles({ padding: '8px 0' }, marginBottomStyle(block))}>
                    <hr style={{
                        border: 'none',
                        borderTop: `1px ${String(block.divider_style || 'solid')} ${String(block.divider_color || '#e5e7eb')}`,
                        margin: 0,
                    }}
                    />
                </div>
            );
        case 'hours':
            return <HoursBlock block={block} />;
        case 'access':
            return <AccessBlock block={block} />;
        case 'social_links':
            return <SocialLinksBlock block={block} />;
        case 'faq':
            return <FaqBlock block={block} />;
        case 'video':
            return <VideoBlock block={block} />;
        case 'button_group':
            return <ButtonGroupBlock block={block} />;
        default:
            return <FallbackTextBlock block={block} />;
    }
}
