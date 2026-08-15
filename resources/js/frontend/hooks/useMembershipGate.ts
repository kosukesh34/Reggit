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
        const settle = (): void => {
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

        let cancelled = false;

        const emitLoginPopup = (forced: boolean): void => {
            document.dispatchEvent(new CustomEvent('liff-member-open-login-popup', { detail: { forced } }));
        };

        const run = async (): Promise<void> => {
            const waitApp = (): Promise<NonNullable<Window['app']>> =>
                new Promise((resolve) => {
                    const tick = (): void => {
                        if (window.app !== undefined && window.app !== null) {
                            resolve(window.app);
                            return;
                        }
                        window.setTimeout(tick, 50);
                    };
                    tick();
                });

            const app = await waitApp();
            if (cancelled) {
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
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__) {
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
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__) {
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
            openRegister(forced);
            if (!cancelled) {
                settle();
            }
        };

        void run();
        const onReady = (): void => {
            if (!handledRef.current && !cancelled) {
                void run();
            }
        };
        document.addEventListener('liff-ready', onReady);
        return () => {
            cancelled = true;
            document.removeEventListener('liff-ready', onReady);
        };
    }, []);
}
