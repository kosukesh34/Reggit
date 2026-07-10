import React, { useEffect, useState } from 'react';
import { normalizeBlock } from '../catalog/blockCatalog';
import { useDesigner } from '../state/DesignerContext';
import type { LiffBlock } from '../catalog/types';

const INDUSTRY_PRESETS = [
    { id: 'restaurant', icon: 'fa-utensils', label: 'レストラン・居酒屋', desc: '予約・注文・会員を網羅した飲食店向け', gradient: 'linear-gradient(135deg, #ef4444, #f97316)', patternId: 'super-pattern-classic' },
    { id: 'cafe', icon: 'fa-coffee', label: 'カフェ・ベーカリー', desc: 'ナチュラルな雰囲気で魅せるカフェ向け', gradient: 'linear-gradient(135deg, #84cc16, #14b8a6)', patternId: 'super-pattern-cafe' },
    { id: 'fastfood', icon: 'fa-hamburger', label: 'ファストフード・テイクアウト', desc: 'スピード注文を最優先にした高速導線', gradient: 'linear-gradient(135deg, #2563eb, #ea580c)', patternId: 'super-pattern-fast' },
    { id: 'salon', icon: 'fa-cut', label: '美容室・サロン', desc: '予約と会員管理を重視したサロン向け', gradient: 'linear-gradient(135deg, #ec4899, #f472b6)', patternId: 'super-pattern-salon' },
    { id: 'retail', icon: 'fa-store', label: '小売・物販ショップ', desc: '商品訴求と会員ロイヤリティの物販向け', gradient: 'linear-gradient(135deg, #7c3aed, #a855f7)', patternId: 'super-pattern-retail' },
    { id: 'fitness', icon: 'fa-dumbbell', label: 'フィットネス・ジム', desc: 'レッスン予約と来館管理のジム向け', gradient: 'linear-gradient(135deg, #0891b2, #06b6d4)', patternId: 'super-pattern-fitness' },
] as const;

type TemplateItem = {
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    layouts?: Record<string, unknown[]>;
    theme?: Record<string, unknown>;
    tab_settings?: Record<string, unknown>;
};

function isLayoutEmpty(layouts: Record<string, LiffBlock[]>): boolean {
    return Object.values(layouts).every((rows) => rows.length === 0);
}

export function OnboardingOverlay() {
    const { state, dispatch, config } = useDesigner();
    const [visible, setVisible] = useState(false);
    const [closing, setClosing] = useState(false);
    const [step, setStep] = useState<1 | 2 | 3>(1);
    const [selectedIndustry, setSelectedIndustry] = useState<string | null>(null);

    useEffect(() => {
        if (!isLayoutEmpty(state.layouts)) return;
        try {
            if (sessionStorage.getItem('ld-onboarding-dismissed') === '1') return;
        } catch {
            /* ignore */
        }
        const timer = window.setTimeout(() => setVisible(true), 400);
        return () => window.clearTimeout(timer);
    }, [state.layouts]);

    const dismiss = () => {
        setClosing(true);
        try {
            sessionStorage.setItem('ld-onboarding-dismissed', '1');
        } catch {
            /* ignore */
        }
        window.setTimeout(() => setVisible(false), 280);
    };

    const applyPattern = (patternId: string) => {
        const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
        const pattern = templates.find((t) => t.id === patternId);
        if (pattern === undefined || pattern.layouts === undefined) return;
        const idCounter = { n: Date.now() % 100000 };
        const layouts: Record<string, LiffBlock[]> = { ...state.layouts };
        Object.keys(pattern.layouts).forEach((tab) => {
            const rows = Array.isArray(pattern.layouts?.[tab]) ? pattern.layouts![tab] : [];
            layouts[tab] = rows.map((item) => normalizeBlock((item || {}) as Record<string, unknown>, idCounter));
        });
        dispatch({
            type: 'APPLY_LAYOUTS',
            layouts,
            globalSettings: pattern.theme ? { ...state.globalSettings, ...pattern.theme } : undefined,
            tabSettings: pattern.tab_settings
                ? { ...state.tabSettings, ...(pattern.tab_settings as Record<string, Record<string, unknown>>) }
                : undefined,
        });
        dispatch({ type: 'SHOW_TOAST', message: `テンプレート「${pattern.label || pattern.name || '選択'}」を適用しました` });
        setStep(3);
    };

    if (!visible) return null;

    const industry = INDUSTRY_PRESETS.find((p) => p.id === selectedIndustry);
    const templates = (Array.isArray(config.templates) ? config.templates : []) as TemplateItem[];
    const pattern = industry !== undefined ? templates.find((t) => t.id === industry.patternId) : undefined;
    const homeBlocks = pattern?.layouts?.home ?? [];
    const totalBlocks = pattern?.layouts
        ? Object.values(pattern.layouts).reduce((sum, rows) => sum + (Array.isArray(rows) ? rows.length : 0), 0)
        : 0;

    return (
        <div className={`ld-onboarding${closing ? ' ld-onboarding--closing' : ' ld-onboarding--visible'}`}>
            {step === 1 ? (
                <div className="ld-ob-container">
                    <div className="ld-ob-header">
                        <div className="ld-ob-step-indicator">
                            <span className="ld-ob-step is-active">1</span>
                            <span className="ld-ob-step-line" />
                            <span className="ld-ob-step">2</span>
                            <span className="ld-ob-step-line" />
                            <span className="ld-ob-step">3</span>
                        </div>
                        <button type="button" className="ld-btn ld-btn--ghost ld-ob-skip" onClick={dismiss}>スキップ</button>
                    </div>
                    <div className="ld-ob-hero-section">
                        <div className="ld-ob-logo"><i className="fas fa-wand-magic-sparkles" /></div>
                        <h1 className="ld-ob-title">LIFFアプリを作成しましょう</h1>
                        <p className="ld-ob-subtitle">
                            お店のジャンルに合ったテンプレートを選ぶだけで、
                            <br />
                            すぐに編集を始められます。
                        </p>
                    </div>
                    <div className="ld-ob-section-label"><i className="fas fa-store" /> ジャンルを選んでください</div>
                    <div className="ld-ob-cards">
                        {INDUSTRY_PRESETS.map((preset) => (
                            <button
                                key={preset.id}
                                type="button"
                                className="ld-ob-card"
                                onClick={() => {
                                    setSelectedIndustry(preset.id);
                                    setStep(2);
                                }}
                            >
                                <div className="ld-ob-card__icon" style={{ background: preset.gradient }}>
                                    <i className={`fas ${preset.icon}`} />
                                </div>
                                <div className="ld-ob-card__body">
                                    <div className="ld-ob-card__label">{preset.label}</div>
                                    <div className="ld-ob-card__desc">{preset.desc}</div>
                                </div>
                                <div className="ld-ob-card__arrow"><i className="fas fa-chevron-right" /></div>
                            </button>
                        ))}
                    </div>
                    <div className="ld-ob-footer">
                        <button type="button" className="ld-btn ld-btn--ghost" onClick={dismiss}>
                            <i className="fas fa-file" /> 白紙から始める
                        </button>
                    </div>
                </div>
            ) : null}

            {step === 2 && industry !== undefined ? (
                <div className="ld-ob-container">
                    <div className="ld-ob-header">
                        <div className="ld-ob-step-indicator">
                            <span className="ld-ob-step is-done"><i className="fas fa-check" /></span>
                            <span className="ld-ob-step-line is-done" />
                            <span className="ld-ob-step is-active">2</span>
                            <span className="ld-ob-step-line" />
                            <span className="ld-ob-step">3</span>
                        </div>
                        <button type="button" className="ld-btn ld-btn--ghost ld-ob-skip" onClick={dismiss}>スキップ</button>
                    </div>
                    <div className="ld-ob-hero-section">
                        <h1 className="ld-ob-title">テンプレートを確認</h1>
                        <p className="ld-ob-subtitle">「{industry.label}」向けのレイアウトをプレビューします</p>
                    </div>
                    <div className="ld-ob-preview-area">
                        <div className="ld-ob-preview-phone">
                            <div className="ld-ob-preview-phone__notch" />
                            <div className="ld-ob-preview-phone__header" style={{ background: industry.gradient }}>ホーム</div>
                            <div className="ld-ob-preview-phone__body">
                                {homeBlocks.slice(0, 5).map((block, i) => (
                                    <div key={i} className="ld-ob-preview-block">
                                        <i className="fas fa-cube" />
                                        {' '}
                                        {String((block as Record<string, unknown>).type || 'block')}
                                    </div>
                                ))}
                            </div>
                            <div className="ld-ob-preview-phone__nav">
                                <i className="fas fa-home" />
                                <i className="fas fa-utensils" />
                                <i className="fas fa-receipt" />
                                <i className="fas fa-user" />
                            </div>
                        </div>
                        <div className="ld-ob-preview-info">
                            <div className="ld-ob-preview-info__badge" style={{ background: industry.gradient }}>
                                <i className={`fas ${industry.icon}`} />
                                {' '}
                                {industry.label}
                            </div>
                            <div className="ld-ob-preview-info__title">{pattern?.label || pattern?.name || industry.label}</div>
                            <div className="ld-ob-preview-info__desc">{pattern?.description || industry.desc}</div>
                            <div className="ld-ob-preview-info__stats">
                                <div className="ld-ob-stat">
                                    <span className="ld-ob-stat__num">{Object.keys(pattern?.layouts || {}).length || '—'}</span>
                                    <span className="ld-ob-stat__label">画面</span>
                                </div>
                                <div className="ld-ob-stat">
                                    <span className="ld-ob-stat__num">{totalBlocks}</span>
                                    <span className="ld-ob-stat__label">ブロック</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="ld-ob-actions">
                        <button type="button" className="ld-btn ld-btn--ghost" onClick={() => { setStep(1); setSelectedIndustry(null); }}>
                            <i className="fas fa-arrow-left" /> 戻る
                        </button>
                        <button
                            type="button"
                            className="ld-btn ld-btn--primary ld-btn--lg"
                            onClick={() => applyPattern(industry.patternId)}
                        >
                            <i className="fas fa-magic" /> このテンプレートを適用
                        </button>
                    </div>
                </div>
            ) : null}

            {step === 3 ? (
                <div className="ld-ob-container ld-ob-container--compact">
                    <div className="ld-ob-complete">
                        <div className="ld-ob-complete__icon"><i className="fas fa-rocket" /></div>
                        <h1 className="ld-ob-title">準備完了！</h1>
                        <p className="ld-ob-subtitle">
                            テンプレートが適用されました。
                            <br />
                            ブロックをクリックしてカスタマイズしましょう。
                        </p>
                        <div className="ld-ob-tips">
                            <div className="ld-ob-tip">
                                <div className="ld-ob-tip__icon"><i className="fas fa-mouse-pointer" /></div>
                                <div><strong>ブロックをクリック</strong><br />右パネルで文字・色を変更</div>
                            </div>
                            <div className="ld-ob-tip">
                                <div className="ld-ob-tip__icon"><i className="fas fa-grip-vertical" /></div>
                                <div><strong>ドラッグで並べ替え</strong><br />順序を自由に変更</div>
                            </div>
                            <div className="ld-ob-tip">
                                <div className="ld-ob-tip__icon"><i className="fas fa-plus-circle" /></div>
                                <div><strong>右パネルから追加</strong><br />新しいブロックを配置</div>
                            </div>
                        </div>
                        <button type="button" className="ld-btn ld-btn--primary ld-btn--lg" onClick={dismiss}>
                            <i className="fas fa-palette" /> カスタマイズを始める
                        </button>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
