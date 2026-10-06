import type { JSX } from 'react';
import React from 'react';
import { isDemoDataMode } from '../../demo/demoMode';
import { lotteryFlags } from './couponHelpers';

function openPlay(play: 'gacha' | 'scratch'): void {
    try {
        window.sessionStorage.setItem(
            isDemoDataMode() ? 'liff_demo_play_focus' : 'liff_lottery_play_focus',
            play,
        );
    } catch {
        // ignore
    }
    const app = window.app as {
        switchTab?: (tab: string) => void;
        designLabManager?: { openGachaPopup?: () => Promise<unknown> };
    } | undefined;
    if (typeof app?.switchTab === 'function') {
        if (play === 'gacha' && typeof app.designLabManager?.openGachaPopup === 'function') {
            void app.designLabManager.openGachaPopup().then((opened) => {
                if (opened === true) {
                    return;
                }
                app.switchTab?.('designlab');
            });
            return;
        }
        app.switchTab('designlab');
        return;
    }
    window.location.hash = 'designlab';
}

export function LotteryPlayEntry(): JSX.Element | null {
    const { showGacha, showScratch } = lotteryFlags();
    if (!showGacha && !showScratch) {
        return null;
    }
    const eyebrow = isDemoDataMode() ? 'DEMO PLAY' : 'SPECIAL PLAY';

    return (
        <section className="demo-play-entry" aria-label="クーポンあそび">
            <div className="demo-play-entry__head">
                <p className="demo-play-entry__eyebrow">{eyebrow}</p>
                <h3 className="demo-play-entry__title">クーポンを当てよう</h3>
                <p className="demo-play-entry__sub">ガチャやスクラッチで特典クーポンを獲得できます</p>
            </div>
            <div className="demo-play-entry__actions">
                {showGacha ? (
                    <button
                        type="button"
                        className="demo-play-entry__btn demo-play-entry__btn--gacha"
                        data-demo-play="gacha"
                        onClick={() => openPlay('gacha')}
                    >
                        <span className="demo-play-entry__btn-label">ガチャガチャ</span>
                        <span className="demo-play-entry__btn-hint">回して当てる</span>
                    </button>
                ) : null}
                {showScratch ? (
                    <button
                        type="button"
                        className="demo-play-entry__btn demo-play-entry__btn--scratch"
                        data-demo-play="scratch"
                        onClick={() => openPlay('scratch')}
                    >
                        <span className="demo-play-entry__btn-label">スクラッチ</span>
                        <span className="demo-play-entry__btn-hint">こすって開ける</span>
                    </button>
                ) : null}
            </div>
        </section>
    );
}
