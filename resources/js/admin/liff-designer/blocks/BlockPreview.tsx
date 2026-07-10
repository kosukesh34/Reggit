import React from 'react';
import { findBlockMeta } from '../catalog/blockCatalog';
import type { LiffBlock } from '../catalog/types';
import { DataMockPreview, MemberSummaryMock } from './DataMockPreview';

function baseStyle(block: LiffBlock): React.CSSProperties {
    return {
        borderRadius: Number(block.border_radius ?? 10),
        marginBottom: Number(block.margin_bottom ?? 0),
        opacity: Number(block.opacity ?? 100) / 100,
        textAlign: (block.text_align as React.CSSProperties['textAlign']) || 'left',
        background: String(block.background_color || '') || undefined,
        boxShadow: block.shadow === true ? '0 2px 8px rgba(0,0,0,0.08)' : undefined,
    };
}

export function BlockPreview({ block, friendAddUrl }: { block: LiffBlock; friendAddUrl?: string }) {
    const style = baseStyle(block);
    switch (block.type) {
        case 'hero':
            return (
                <div
                    className={`ld-preview-hero ld-preview-hero--${String(block.design || 'editorial')}`}
                    style={{
                        ...style,
                        background: block.image_url
                            ? `linear-gradient(rgba(0,0,0,.35),rgba(0,0,0,.45)), url(${String(block.image_url)}) center/cover`
                            : `linear-gradient(135deg, ${String(block.gradient_start || '#667eea')}, ${String(block.gradient_end || '#764ba2')})`,
                        color: '#fff',
                        padding: '28px 16px',
                    }}
                >
                    <div style={{ fontWeight: 800, fontSize: 18 }}>{String(block.title || '')}</div>
                    <div style={{ fontSize: 12, opacity: 0.9, marginTop: 4 }}>{String(block.subtitle || '')}</div>
                </div>
            );
        case 'heading': {
            const size = block.size === 'large' ? 20 : block.size === 'small' ? 14 : 16;
            return <div style={{ ...style, fontWeight: 700, fontSize: size, color: String(block.text_color || '#1f2937'), padding: '4px 0' }}>{String(block.text || '')}</div>;
        }
        case 'text':
            return <div style={{ ...style, fontSize: Number(block.font_size || 13), color: String(block.text_color || '#4b5563'), lineHeight: 1.55, whiteSpace: 'pre-wrap' }}>{String(block.text || '')}</div>;
        case 'image':
            return (
                <div style={{ ...style, overflow: 'hidden' }}>
                    {block.image_url ? (
                        <img src={String(block.image_url)} alt={String(block.alt || '')} style={{ width: '100%', display: 'block', objectFit: (block.fit as React.CSSProperties['objectFit']) || 'cover', maxHeight: 180 }} />
                    ) : (
                        <div style={{ height: 100, background: '#f3f4f6' }} />
                    )}
                </div>
            );
        case 'button': {
            const design = String(block.design || 'cta');
            const isOutline = design === 'outline';
            const isSoft = design === 'soft';
            return (
                <div style={{ ...style, textAlign: 'center', padding: '4px 0' }}>
                    <span
                        className={`ld-preview-button ld-preview-button--${design}`}
                        style={{
                            display: block.full_width !== false ? 'block' : 'inline-block',
                            background: isOutline ? 'transparent' : isSoft ? `${String(block.color || '#06C755')}22` : String(block.color || '#06C755'),
                            color: isOutline ? String(block.color || '#06C755') : String(block.text_color || '#fff'),
                            border: isOutline ? `2px solid ${String(block.color || '#06C755')}` : undefined,
                            padding: '10px 18px',
                            borderRadius: design === 'soft' ? 14 : 8,
                            fontWeight: 700,
                            fontSize: 13,
                        }}
                    >
                        {String(block.text || 'ボタン')}
                    </span>
                </div>
            );
        }
        case 'card': {
            const design = String(block.design || 'editorial');
            const isHorizontal = design === 'horizontal';
            const isOverlay = design === 'overlay';
            if (isHorizontal) {
                return (
                    <div style={{ ...style, background: '#fff', display: 'flex', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                        <div style={{ width: '40%', minHeight: Number(block.image_height || 100), background: block.image_url ? `url(${String(block.image_url)}) center/cover` : '#f3f4f6' }} />
                        <div style={{ padding: 12, flex: 1 }}>
                            <div style={{ fontWeight: 700, fontSize: 14 }}>{String(block.title || '')}</div>
                            {block.description ? <div style={{ fontSize: 12, color: '#6b7280', marginTop: 4 }}>{String(block.description)}</div> : null}
                        </div>
                    </div>
                );
            }
            if (isOverlay) {
                return (
                    <div style={{ ...style, position: 'relative', overflow: 'hidden', borderRadius: 12, minHeight: Number(block.image_height || 140) }}>
                        <div style={{ position: 'absolute', inset: 0, background: block.image_url ? `url(${String(block.image_url)}) center/cover` : '#44403c' }} />
                        <div style={{ position: 'relative', padding: 12, marginTop: 80, background: 'linear-gradient(transparent, rgba(0,0,0,.7))', color: '#fff' }}>
                            <div style={{ fontWeight: 700 }}>{String(block.title || '')}</div>
                            {block.description ? <div style={{ fontSize: 12, opacity: 0.9 }}>{String(block.description)}</div> : null}
                        </div>
                    </div>
                );
            }
            return (
                <div style={{ ...style, background: '#fff', overflow: 'hidden', boxShadow: '0 1px 3px rgba(0,0,0,.06)' }}>
                    {block.image_url ? (
                        <img src={String(block.image_url)} alt="" style={{ width: '100%', height: Number(block.image_height || 140), objectFit: 'cover', display: 'block' }} />
                    ) : (
                        <div style={{ height: Number(block.image_height || 140), background: '#f3f4f6' }} />
                    )}
                    <div style={{ padding: 12 }}>
                        <div style={{ fontWeight: 700, fontSize: 14 }}>{String(block.title || '')}</div>
                        {block.description ? <div style={{ fontSize: 12, color: '#6b7280', marginTop: 4 }}>{String(block.description)}</div> : null}
                    </div>
                </div>
            );
        }
        case 'collection': {
            const items = Array.isArray(block.items) ? block.items as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style }}>
                    {block.section_label ? <div style={{ fontWeight: 700, fontSize: 13, marginBottom: 8 }}>{String(block.section_label)}</div> : null}
                    <div style={{ display: 'flex', gap: Number(block.gap ?? 8), overflowX: 'auto' }}>
                        {items.slice(0, 6).map((it, i) => (
                            <div key={i} style={{ minWidth: Number(block.item_width || 120), background: '#fff', borderRadius: 10, overflow: 'hidden', boxShadow: '0 1px 2px rgba(0,0,0,.05)' }}>
                                <div style={{ height: Number(block.item_image_height || 80), background: it.image_url ? `url(${String(it.image_url)}) center/cover` : '#eee' }} />
                                <div style={{ padding: 8, fontSize: 11, fontWeight: 600 }}>{String(it.title || '')}</div>
                            </div>
                        ))}
                    </div>
                </div>
            );
        }
        case 'slider': {
            const slides = Array.isArray(block.slides) ? block.slides as Array<Record<string, unknown>> : [];
            const first = slides[0];
            return (
                <div style={{ ...style, height: Number(block.slide_height || 140), borderRadius: 12, overflow: 'hidden', background: first?.image_url ? `url(${String(first.image_url)}) center/cover` : '#e5e7eb', position: 'relative' }}>
                    {first?.caption ? (
                        <div style={{ position: 'absolute', left: 0, right: 0, bottom: 0, padding: 8, background: 'rgba(0,0,0,.4)', color: '#fff', fontSize: 12 }}>{String(first.caption)}</div>
                    ) : null}
                </div>
            );
        }
        case 'icon_text': {
            const rows = Array.isArray(block.rows) ? block.rows as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style, background: '#fff', padding: 12, display: 'flex', flexDirection: 'column', gap: 8 }}>
                    {rows.map((r, i) => (
                        <div key={i} style={{ display: 'flex', gap: 8, alignItems: 'center', fontSize: 13 }}>
                            <i className={`fas ${String(r.icon || 'fa-check')}`} style={{ color: String(r.color || '#4f46e5') }} />
                            <span>{String(r.text || '')}</span>
                        </div>
                    ))}
                </div>
            );
        }
        case 'list': {
            const items = Array.isArray(block.items) ? block.items.map(String) : [];
            const Tag = block.style === 'number' ? 'ol' : 'ul';
            return (
                <Tag style={{ ...style, margin: 0, paddingLeft: 20, color: String(block.text_color || '#374151'), fontSize: 13 }}>
                    {items.map((t, i) => <li key={i}>{t}</li>)}
                </Tag>
            );
        }
        case 'banner': {
            const design = String(block.design || 'promo');
            const isOutline = design === 'outline';
            const isPill = design === 'pill';
            const isSoft = design === 'soft';
            return (
                <div
                    className={`ld-preview-banner ld-preview-banner--${design}`}
                    style={{
                        ...style,
                        background: isOutline || isSoft ? 'transparent' : String(block.color || '#3b82f6'),
                        color: isOutline ? String(block.color || '#3b82f6') : String(block.text_color || '#fff'),
                        border: isOutline ? `2px solid ${String(block.color || '#3b82f6')}` : undefined,
                        backgroundColor: isSoft ? `${String(block.color || '#3b82f6')}22` : undefined,
                        padding: '10px 12px',
                        display: 'flex',
                        gap: 8,
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontSize: 13,
                        fontWeight: 600,
                        borderRadius: isPill ? 999 : style.borderRadius,
                    }}
                >
                    <i className={`fas ${String(block.icon || 'fa-info-circle')}`} />
                    <span>{String(block.text || '')}</span>
                </div>
            );
        }
        case 'spacer':
            return <div style={{ height: Number(block.height || 16) }} />;
        case 'divider':
            return <hr style={{ ...style, border: 'none', borderTop: `1px ${String(block.divider_style || 'solid')} ${String(block.divider_color || '#e5e7eb')}`, margin: '8px 0' }} />;
        case 'data':
            return (
                <div style={style}>
                    <DataMockPreview source={String(block.source || 'products')} />
                </div>
            );
        case 'member_summary':
            return (
                <div style={style}>
                    <MemberSummaryMock greeting={String(block.greeting || '')} design={String(block.design || 'rewards')} />
                </div>
            );
        case 'quick_actions': {
            const actions = Array.isArray(block.actions) ? block.actions as Array<Record<string, unknown>> : [];
            const design = String(block.design || 'grid');
            const isPill = design === 'pill';
            const isCompact = design === 'compact';
            return (
                <div style={style}>
                    <div className={`ld-mock-qa ld-mock-qa--${design}`} style={{ display: 'grid', gridTemplateColumns: isCompact ? 'repeat(4, 1fr)' : 'repeat(4, 1fr)', gap: isCompact ? 4 : 8 }}>
                        {actions.slice(0, 8).map((a, i) => (
                            <div
                                key={i}
                                className="ld-mock-qa__tile"
                                style={{
                                    background: '#fff',
                                    borderRadius: isPill ? 999 : 12,
                                    padding: isCompact ? 6 : 10,
                                    textAlign: 'center',
                                    fontSize: isCompact ? 9 : 10,
                                    boxShadow: '0 1px 2px rgba(0,0,0,.05)',
                                }}
                            >
                                <i className={`fas ${String(a.icon || 'fa-circle')} ld-mock-qa__icon`} style={{ display: 'block', marginBottom: 4, fontSize: isCompact ? 12 : 14 }} />
                                {String(a.label || '')}
                            </div>
                        ))}
                    </div>
                </div>
            );
        }
        case 'friend_add': {
            const url = String(block.url || friendAddUrl || '');
            return (
                <div style={{ ...style, textAlign: 'center', padding: 8 }}>
                    <span style={{ display: 'inline-flex', gap: 8, alignItems: 'center', background: String(block.color || '#06C755'), color: String(block.text_color || '#fff'), padding: '10px 18px', borderRadius: 8, fontWeight: 700, fontSize: 13 }}>
                        <i className="fab fa-line" /> {String(block.text || '友だち追加')}
                    </span>
                    {url === '' ? <div style={{ fontSize: 10, color: '#ef4444', marginTop: 6 }}>URL未設定</div> : null}
                </div>
            );
        }
        case 'hours': {
            const rows = Array.isArray(block.rows) ? block.rows as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style, background: '#fff', padding: 12, borderRadius: 12 }}>
                    <div style={{ fontWeight: 700, marginBottom: 8 }}>{String(block.title || '営業時間')}</div>
                    {rows.map((r, i) => (
                        <div key={i} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 12, padding: '4px 0', borderTop: i ? '1px solid #f3f4f6' : undefined }}>
                            <span>{String(r.day || '')}</span>
                            <span style={{ color: '#6b7280' }}>{String(r.time || '')}</span>
                        </div>
                    ))}
                </div>
            );
        }
        case 'access':
            return (
                <div style={{ ...style, background: '#fff', padding: 12, borderRadius: 12, fontSize: 12 }}>
                    <div style={{ fontWeight: 700, marginBottom: 6 }}>{String(block.title || '店舗情報')}</div>
                    <div><i className="fas fa-map-marker-alt" /> {String(block.address || '')}</div>
                    <div style={{ marginTop: 4 }}><i className="fas fa-phone" /> {String(block.phone || '')}</div>
                </div>
            );
        case 'social_links': {
            const links = Array.isArray(block.links) ? block.links as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style, display: 'flex', gap: 8, justifyContent: 'center', padding: 8 }}>
                    {links.map((l, i) => (
                        <span key={i} style={{ width: 32, height: 32, borderRadius: 999, background: '#f3f4f6', display: 'inline-flex', alignItems: 'center', justifyContent: 'center', fontSize: 12 }}>
                            {String(l.type || '').slice(0, 2)}
                        </span>
                    ))}
                </div>
            );
        }
        case 'faq': {
            const items = Array.isArray(block.items) ? block.items as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style, background: '#fff', padding: 12, borderRadius: 12 }}>
                    <div style={{ fontWeight: 700, marginBottom: 8 }}>{String(block.title || 'FAQ')}</div>
                    {items.slice(0, 3).map((it, i) => (
                        <div key={i} style={{ fontSize: 12, padding: '6px 0', borderTop: i ? '1px solid #f3f4f6' : undefined }}>
                            <strong>Q.</strong> {String(it.q || '')}
                        </div>
                    ))}
                </div>
            );
        }
        case 'video':
            return (
                <div style={{ ...style, background: '#111', color: '#fff', borderRadius: 12, height: 120, display: 'flex', alignItems: 'center', justifyContent: 'center', flexDirection: 'column', gap: 6 }}>
                    <i className="fas fa-play-circle" style={{ fontSize: 28 }} />
                    <span style={{ fontSize: 11 }}>{String(block.caption || '動画')}</span>
                </div>
            );
        case 'button_group': {
            const buttons = Array.isArray(block.buttons) ? block.buttons as Array<Record<string, unknown>> : [];
            return (
                <div style={{ ...style, display: 'flex', gap: 8 }}>
                    {buttons.map((b, i) => (
                        <span
                            key={i}
                            style={{
                                flex: 1,
                                textAlign: 'center',
                                padding: '8px 6px',
                                borderRadius: 8,
                                fontSize: 12,
                                fontWeight: 700,
                                background: b.variant === 'outline' ? 'transparent' : String(b.color || '#1c1917'),
                                color: b.variant === 'outline' ? String(b.color || '#1c1917') : '#fff',
                                border: b.variant === 'outline' ? `1px solid ${String(b.color || '#1c1917')}` : undefined,
                            }}
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
                <div style={{ ...style, padding: 12, border: '1px dashed #f59e0b', background: '#fffbeb', fontSize: 12, color: '#92400e' }}>
                    未対応: {meta?.label || block.type}
                </div>
            );
        }
    }
}
