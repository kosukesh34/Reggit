import React, { useEffect, useRef, useState } from 'react';
import { isDemoDataMode } from '../../demo/demoMode';
import {
    chipLabel,
    getDesignLabManager,
    hasPending,
    prepareLottery,
    showGachaSection,
    showScratchSection,
    wonCouponsForDisplay,
    type LotteryState,
} from './api';

function GachaMachine({ chip }: { chip: string }): JSX.Element {
    return (
        <div className="pp-play-card">
            <div className="pp-play-card__top">
                <span className="pp-play-card__title">クーポンガチャ</span>
                <span className="pp-play-card__chip" data-gacha-chip>{chip}</span>
            </div>
            <p className="pp-play-card__rule">回すとカプセルが出ます。中身は開くまで見えません。</p>
            <div className="pp-gacha" id="designGachaStage" data-state="idle">
                <div className="pp-gacha__stage">
                    <div className="pp-gacha__machine" id="designGachaMachine">
                        <span className="pp-gacha__price">権利で1回</span>
                        <div className="pp-gacha__dome">
                            <div className="pp-gacha__dome-glass" id="designGachaDome" />
                            <span className="pp-gacha__dome-shine" aria-hidden="true" />
                            <span className="pp-gacha__dome-shine pp-gacha__dome-shine--soft" aria-hidden="true" />
                            <span className="pp-gacha__dome-rim" aria-hidden="true" />
                            <span className="pp-gacha__collar" aria-hidden="true" />
                        </div>
                        <div className="pp-gacha__body">
                            <span className="pp-gacha__body-gloss" aria-hidden="true" />
                            <span className="pp-gacha__turn-hint" aria-hidden="true" />
                            <div className="pp-gacha__knob-wrap">
                                <button type="button" className="pp-gacha__knob" id="designGachaKnob" aria-label="レバーを回す">
                                    <span className="pp-gacha__knob-plate" aria-hidden="true" />
                                    <span className="pp-gacha__knob-bar" aria-hidden="true" />
                                    <span className="pp-gacha__knob-hub" aria-hidden="true" />
                                </button>
                            </div>
                            <div className="pp-gacha__outlet" aria-hidden="true">
                                <span className="pp-gacha__flap" />
                                <div className="pp-gacha__chute">
                                    <div className="pp-gacha__fall" id="designGachaFall">
                                        <span className="pp-gacha__fall-half pp-gacha__fall-half--top"><i /></span>
                                        <span className="pp-gacha__fall-half pp-gacha__fall-half--bot"><i /></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="pp-gacha__tray" aria-hidden="true" />
                        <div className="pp-gacha__stand" aria-hidden="true" />
                    </div>
                </div>
                <button type="button" className="pp-cta" id="designGachaBtn">レバーを回す</button>
            </div>
        </div>
    );
}

function ScratchPanel({ chip }: { chip: string }): JSX.Element {
    return (
        <section className="design-lab__section" data-lottery-section="scratch" aria-label="スクラッチ">
            <h2 className="design-lab__section-title">スクラッチ</h2>
            <p className="design-lab__section-lead">円の銀面をこすってクーポンを開けます</p>
            <div className="pp-play-card">
                <div className="pp-play-card__top">
                    <span className="pp-play-card__title">スクラッチくじ</span>
                    <span className="pp-play-card__chip" data-scratch-chip>{chip}</span>
                </div>
                <p className="pp-play-card__rule">銀面をこすって結果を確認しましょう。中身は削るまで見えません。</p>
                <div className="pp-scratch" id="designScratchStage" data-state="sealed">
                    <div className="pp-scratch__circle" id="designScratchCircle">
                        <div className="pp-scratch__prize" id="designScratchPrize">
                            <p className="pp-scratch__prize-eyebrow" id="designScratchEyebrow" hidden>あたり</p>
                            <p className="pp-scratch__prize-name" id="designScratchName" />
                            <p className="pp-scratch__prize-desc" id="designScratchDesc" />
                        </div>
                        <canvas
                            className="pp-scratch__canvas"
                            id="designScratchCanvas"
                            width={360}
                            height={360}
                            aria-label="スクラッチ円"
                        />
                        <div className="pp-scratch__shine" aria-hidden="true" />
                    </div>
                    <div className="pp-scratch__progress-row">
                        <div className="pp-scratch__track"><div className="pp-scratch__fill" id="designScratchFill" /></div>
                        <span className="pp-scratch__pct" id="designScratchProgress">0%</span>
                    </div>
                    <p className="pp-scratch__hint" id="designScratchHint">円の中をこすってください</p>
                    {isDemoDataMode() ? (
                        <button type="button" className="pp-cta pp-cta--ghost" id="designScratchReset">やり直す</button>
                    ) : null}
                </div>
            </div>
        </section>
    );
}

function WonList(): JSX.Element {
    const won = wonCouponsForDisplay();
    if (won === null) {
        return <li className="design-lab__won-empty">獲得クーポンはクーポン一覧で確認できます</li>;
    }
    if (won.length === 0) {
        return <li className="design-lab__won-empty">まだありません</li>;
    }
    return (
        <>
            {won.map((c, i) => (
                <li key={`${c.coupon_name}-${i}`} className="design-lab__won-item">
                    <span className="design-lab__won-badge">{c.source === 'scratch' ? 'スクラッチ' : 'ガチャ'}</span>
                    <span className="design-lab__won-name">{c.coupon_name}</span>
                </li>
            ))}
        </>
    );
}

export function DesignLabView(): JSX.Element {
    const rootRef = useRef<HTMLDivElement>(null);
    const [state, setState] = useState<LotteryState | null>(null);
    const [ready, setReady] = useState(false);

    useEffect(() => {
        document.body.classList.add('liff-lottery-ui');
        let cancelled = false;
        void prepareLottery().then((lottery) => {
            if (cancelled) {
                return;
            }
            setState(lottery);
            setReady(true);
        });
        return () => {
            cancelled = true;
            document.body.classList.remove('liff-lottery-ui');
        };
    }, []);

    const showGacha = showGachaSection(state);
    const showScratch = showScratchSection(state);
    const gachaChip = chipLabel(hasPending(state, 'gacha'));
    const scratchChip = chipLabel(hasPending(state, 'scratch'));

    useEffect(() => {
        if (!ready) {
            return;
        }
        const root = rootRef.current;
        const mgr = getDesignLabManager();
        if (root === null || mgr === undefined) {
            return;
        }
        // ガチャ操作は Manager、スクラッチ canvas は useEffect で Manager の init を canvas に適用
        if (typeof mgr.hydrateFromReact === 'function') {
            mgr.hydrateFromReact(root, { scratchInit: false });
        }
        if (showScratch && typeof mgr.initScratchFromReact === 'function') {
            // rAF: canvas の layout 確定後に foil を描く（cssSize < 48 ガード回避）
            const raf = window.requestAnimationFrame(() => {
                mgr.initScratchFromReact?.(root);
            });
            return () => window.cancelAnimationFrame(raf);
        }
        return undefined;
    }, [ready, showGacha, showScratch, state]);

    if (!ready) {
        return (
            <div className="loading">
                <i className="fas fa-spinner fa-spin" />
                <p>読み込み中...</p>
            </div>
        );
    }

    return (
        <div ref={rootRef} className="design-lab">
            {!showGacha && !showScratch ? (
                <p className="design-lab__section-lead">現在あそべるコンテンツはありません</p>
            ) : null}
            {showGacha ? (
                <section className="design-lab__section" data-lottery-section="gacha" aria-label="ガチャ">
                    <GachaMachine chip={gachaChip} />
                </section>
            ) : null}
            {showScratch ? <ScratchPanel chip={scratchChip} /> : null}
            <section className="design-lab__section" aria-label="獲得クーポン">
                <h2 className="design-lab__section-title">獲得したクーポン</h2>
                <ul className="design-lab__won" id="designWonList">
                    <WonList />
                </ul>
            </section>
        </div>
    );
}
