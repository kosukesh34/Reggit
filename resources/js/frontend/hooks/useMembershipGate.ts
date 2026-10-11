import { useEffect, useRef } from 'react';
import { isDemoDataMode } from '../demo/demoMode';
import { getMemberManager, openRegister } from '../features/member/api';
import {
    getMemberRegistrationMode,
    shouldAutoCreateMember,
    shouldShowRegistrationForm,
    shouldShowStartupLoginPopup,
    syncMemberRegistrationModeFromPayload,
} from '../utils/membershipPolicy';

/**
 * 会員登録ゲート。互換のため window.app.memberManager を使う。
 * Phase F で hook 内 fetch に置き換える。
 */
export function useMembershipGate(
    switchTab: (tab: string) => boolean | void,
    onSettled?: () => void,
): void {
    const handledRef = useRef(false);
    const switchTabRef = useRef(switchTab);
    switchTabRef.current = switchTab;
    const onSettledRef = useRef(onSettled);
    onSettledRef.current = onSettled;

    useEffect(() => {
        let cancelled = false;
        let settled = false;
        const settle = (): void => {
            if (cancelled || settled) {
                return;
            }
            settled = true;
            onSettledRef.current?.();
        };
        if (handledRef.current) {
            settle();
            return;
        }
        if (window.__IS_DESIGNER_EMBED_PREVIEW__ === true || isDemoDataMode()) {
            handledRef.current = true;
            settle();
            return;
        }

        let running = false;
        let waitTimer: number | undefined;
        let finishWait: ((app: NonNullable<Window['app']> | null) => void) | undefined;

        const emitLoginPopup = (forced: boolean): void => {
            document.dispatchEvent(new CustomEvent('liff-member-open-login-popup', { detail: { forced } }));
        };

        const run = async (): Promise<void> => {
            const waitApp = (): Promise<NonNullable<Window['app']> | null> =>
                new Promise((resolve) => {
                    finishWait = resolve;
                    const tick = (): void => {
                        if (cancelled) {
                            resolve(null);
                            return;
                        }
                        if (window.app !== undefined && window.app !== null) {
                            resolve(window.app);
                            return;
                        }
                        waitTimer = window.setTimeout(tick, 50);
                    };
                    tick();
                });

            const app = await waitApp();
            if (cancelled || app === null) {
                return;
            }
            const mm = getMemberManager();
            if (mm === undefined) {
                handledRef.current = true;
                settle();
                return;
            }
            try {
                if (typeof mm.ensureIdentityReady === 'function') {
                    await mm.ensureIdentityReady(1500);
                }
                if (cancelled) {
                    return;
                }
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__ && !shouldAutoCreateMember()) {
                    const mode = getMemberRegistrationMode();
                    if (mode === 'popup' || mode === 'prompt' || mode === 'force') {
                        emitLoginPopup(mode === 'force');
                    }
                    handledRef.current = true;
                    settle();
                    return;
                }
                if (typeof mm.fetchMemberContext === 'function') {
                    await mm.fetchMemberContext({});
                }
            } catch {
                if (cancelled) {
                    return;
                }
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__ && !shouldAutoCreateMember()) {
                    const mode = getMemberRegistrationMode();
                    if (mode === 'popup' || mode === 'prompt' || mode === 'force') {
                        emitLoginPopup(mode === 'force');
                    }
                    handledRef.current = true;
                    settle();
                    return;
                }
            }
            if (cancelled) {
                return;
            }
            const data = typeof mm.getMemberData === 'function' ? mm.getMemberData() : null;
            syncMemberRegistrationModeFromPayload(data as Record<string, unknown> | null);
            if (data !== null && data !== undefined && data.is_member === true) {
                handledRef.current = true;
                settle();
                return;
            }
            if (shouldAutoCreateMember() && typeof mm.canAutoRegister === 'function' && mm.canAutoRegister() === true && typeof mm.autoRegister === 'function') {
                await mm.autoRegister();
                if (cancelled) {
                    return;
                }
                handledRef.current = true;
                settle();
                return;
            }
            if (shouldShowStartupLoginPopup()) {
                emitLoginPopup(false);
                handledRef.current = true;
                settle();
                return;
            }
            if (!shouldShowRegistrationForm()) {
                handledRef.current = true;
                settle();
                return;
            }
            handledRef.current = true;
            const forced = getMemberRegistrationMode() === 'force';
            const switched = switchTabRef.current('member');
            if (switched !== false && typeof app.loadMemberTab === 'function') {
                await app.loadMemberTab();
            }
            if (cancelled) {
                return;
            }
            openRegister(forced);
            if (!cancelled) {
                settle();
            }
        };

        const onReady = (): void => {
            if (running || handledRef.current || cancelled) {
                return;
            }
            running = true;
            void run().catch(() => {
                if (!cancelled) {
                    handledRef.current = true;
                    settle();
                }
            }).finally(() => {
                running = false;
            });
        };
        onReady();
        document.addEventListener('liff-ready', onReady);
        return () => {
            cancelled = true;
            window.clearTimeout(waitTimer);
            finishWait?.(null);
            document.removeEventListener('liff-ready', onReady);
        };
    }, []);
}
