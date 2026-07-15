import React, { useEffect, useState } from 'react';
import { BlockPreview } from '../blocks/BlockPreview';
import { PhoneAppHeader, PhoneAppMain, PhoneAppTabs, PhoneDeviceFrame } from './PhoneAppShell';
import { useDesigner } from '../state/DesignerContext';
import {
    contentPadding,
    frameSystemClasses,
    resolvePhoneBodyStyle,
    sectionGap,
    screenTabStyleClass,
} from '../utils/globalDesign';

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
    const enabledNav = state.menuItems.filter((m) => m.enabled);
    const bodyStyle = resolvePhoneBodyStyle(tabSettings, state.globalSettings);
    const padding = contentPadding(tabSettings, state.globalSettings);
    const gap = sectionGap(state.globalSettings);
    const tabStyleClass = screenTabStyleClass(state.globalSettings);
    const systemClass = frameSystemClasses(state.globalSettings);
    const friendAddUrl = config.lineFriendAddUrl || '';
    const shopTitle = document.title.replace(/\s*[|\-].*$/, '').trim() || '店舗アプリ';

    return (
        <div className="ld-preview-overlay" onClick={() => dispatch({ type: 'SET_UI', patch: { previewOpen: false } })}>
            <div className="ld-preview-overlay__chrome" onClick={(e) => e.stopPropagation()}>
                <div className="ld-preview-overlay__top">
                    <div>
                        <p className="ld-preview-overlay__eyebrow">Live Preview</p>
                        <h2 className="ld-preview-overlay__title">{menuItem?.label || 'プレビュー'}</h2>
                    </div>
                    <button
                        type="button"
                        className="ld-preview-overlay__close"
                        onClick={() => dispatch({ type: 'SET_UI', patch: { previewOpen: false } })}
                        aria-label="閉じる"
                    >
                        <i className="fas fa-times" />
                    </button>
                </div>
                <p className="ld-preview-overlay__hint">下部タブで画面を切り替えられます。見た目は保存後の利用者画面に近い表示です。</p>
                <div className="ld-preview-overlay__phone">
                    <PhoneDeviceFrame
                        className={systemClass}
                        size="large"
                        maxHeight={Math.max(380, Math.min(560, window.innerHeight - 180))}
                        screenStyle={bodyStyle}
                        screenClassName={tabStyleClass}
                    >
                        <PhoneAppHeader
                            title={shopTitle}
                            friendAddUrl={friendAddUrl}
                            showCart={enabledNav.some((m) => m.tab === 'products' || m.tab === 'orders')}
                            onAccountClick={() => setPreviewTab('member')}
                            onCartClick={() => setPreviewTab('products')}
                        />
                        <PhoneAppMain
                            padding={padding}
                            gap={gap}
                            onClick={(e) => {
                                const navEl = (e.target as HTMLElement).closest('[data-preview-nav]');
                                if (navEl === null) return;
                                const tab = navEl.getAttribute('data-preview-nav');
                                if (tab !== null && tab !== '') setPreviewTab(tab);
                            }}
                        >
                            {blocks.length === 0 ? (
                                <p className="ld-preview-empty">この画面にはブロックがありません</p>
                            ) : (
                                blocks.map((block) => (
                                    <div
                                        key={block.id}
                                        className={`ld-block ld-block--${block.type}${block.design ? ` ld-block--${block.type}-design-${String(block.design)}` : ''}`}
                                    >
                                        <BlockPreview block={block} friendAddUrl={friendAddUrl} />
                                    </div>
                                ))
                            )}
                        </PhoneAppMain>
                        <PhoneAppTabs items={enabledNav} activeTab={previewTab} onSelect={setPreviewTab} />
                    </PhoneDeviceFrame>
                </div>
            </div>
        </div>
    );
}
