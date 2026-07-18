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
    const [asGuest, setAsGuest] = useState(state.previewAsGuest);

    useEffect(() => {
        if (state.previewOpen) {
            setPreviewTab(state.activeTab);
            setAsGuest(state.previewAsGuest);
        }
    }, [state.previewOpen, state.activeTab, state.previewAsGuest]);

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
    const shopTitle =
        String(state.globalSettings.app_display_name || '').trim()
        || document.title.replace(/\s*[|\-].*$/, '').trim()
        || '店舗アプリ';
    const requireMembership = state.globalSettings.require_membership !== false;

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
                <div className="ld-preview-overlay__controls">
                    <div className="ld-bg-style-tabs" role="group" aria-label="プレビューの見え方">
                        <button
                            type="button"
                            className={`ld-bg-style-tab${!asGuest ? ' is-active' : ''}`}
                            onClick={() => setAsGuest(false)}
                        >
                            会員表示
                        </button>
                        <button
                            type="button"
                            className={`ld-bg-style-tab${asGuest ? ' is-active' : ''}`}
                            onClick={() => setAsGuest(true)}
                        >
                            ゲスト表示
                        </button>
                    </div>
                    <p className="ld-preview-overlay__hint">
                        下部タブで画面を切り替えます。空の画面は他タブの内容を流用しません。
                        {requireMembership ? ' 本番は会員必須です。' : ' 本番はゲスト閲覧可です。'}
                    </p>
                </div>
                <div className="ld-preview-overlay__phone">
                    <PhoneDeviceFrame
                        className={`${systemClass}${asGuest ? ' ld-phone--guest-preview' : ''}`}
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
                            key={previewTab}
                            className="ld-phone-app-main--tab-enter"
                            padding={padding}
                            gap={gap}
                            onClick={(e) => {
                                if (e === undefined) return;
                                const navEl = (e.target as HTMLElement).closest('[data-preview-nav]');
                                if (navEl === null) return;
                                const tab = navEl.getAttribute('data-preview-nav');
                                if (tab !== null && tab !== '') setPreviewTab(tab);
                            }}
                        >
                            {asGuest ? (
                                <div className="ld-guest-preview-banner">
                                    ゲスト表示プレビュー
                                    {requireMembership
                                        ? ' · 会員向けブロックは登録導線に置き換わります'
                                        : ' · 会員登録は任意の設定です'}
                                </div>
                            ) : null}
                            {blocks.length === 0 ? (
                                <div className="ld-preview-empty">
                                    <p>この画面にはブロックがありません</p>
                                    <span>キャンバスで「{menuItem?.label || previewTab}」にブロックを追加してください</span>
                                </div>
                            ) : (
                                blocks.map((block) => (
                                    <div
                                        key={block.id}
                                        className={`ld-block ld-block--${block.type}${block.design ? ` ld-block--${block.type}-design-${String(block.design)}` : ''}`}
                                    >
                                        <BlockPreview
                                            block={block}
                                            friendAddUrl={friendAddUrl}
                                            shopName={shopTitle}
                                            asGuest={asGuest}
                                            memberCardDesign={String(state.globalSettings.member_card_design || '')}
                                        />
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
