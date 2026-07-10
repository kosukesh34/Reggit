import React, { useEffect, useState } from 'react';
import { BlockPreview } from '../blocks/BlockPreview';
import { useDesigner } from '../state/DesignerContext';

export function PreviewOverlay() {
    const { state, dispatch, config } = useDesigner();
    const [previewTab, setPreviewTab] = useState(state.activeTab);

    useEffect(() => {
        if (state.previewOpen) {
            setPreviewTab(state.activeTab);
        }
    }, [state.previewOpen, state.activeTab]);

    if (!state.previewOpen) return null;

    const blocks = state.layouts[previewTab] || [];
    const menuItem = state.menuItems.find((m) => m.tab === previewTab);
    const tabSettings = state.tabSettings[previewTab] || {};
    const enabledNav = state.menuItems.filter((m) => m.enabled).slice(0, 5);

    return (
        <div className="ld-preview-overlay" onClick={() => dispatch({ type: 'SET_UI', patch: { previewOpen: false } })}>
            <button
                type="button"
                className="ld-preview-overlay__close"
                onClick={() => dispatch({ type: 'SET_UI', patch: { previewOpen: false } })}
            >
                <i className="fas fa-times" />
            </button>
            <p className="ld-preview-overlay__hint">下部のタブをタップして画面を切り替えられます</p>
            <div className="ld-phone" onClick={(e) => e.stopPropagation()}>
                <div className="ld-phone__frame is-line">
                    <div className="ld-phone__notch" />
                    <div className="ld-phone__header">
                        <span className="ld-phone__header-title">{menuItem?.label || 'ホーム'}</span>
                    </div>
                    <div
                        className="ld-phone__body"
                        style={{
                            backgroundColor: String(tabSettings.background_color || '#fafaf9'),
                            backgroundImage: tabSettings.background_image ? `url(${String(tabSettings.background_image)})` : undefined,
                        }}
                    >
                        <div className="ld-phone__content" style={{ padding: Number(tabSettings.content_padding ?? 18) }}>
                            {blocks.length === 0 ? (
                                <p className="ld-preview-empty">この画面にはブロックがありません</p>
                            ) : (
                                blocks.map((block) => (
                                    <div key={block.id} className="ld-block">
                                        <BlockPreview block={block} friendAddUrl={config.lineFriendAddUrl || ''} />
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                    <div className="ld-phone__nav">
                        {enabledNav.map((item) => (
                            <button
                                key={item.tab}
                                type="button"
                                className={`ld-phone__nav-item${previewTab === item.tab ? ' is-active' : ''}`}
                                onClick={() => setPreviewTab(item.tab)}
                            >
                                <i className={`fas ${item.icon}`} />
                                <span>{item.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
