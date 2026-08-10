import { useEffect, useRef } from 'react';
import {
    getMemberRegistrationMode,
    shouldShowRegistrationForm,
    syncMemberRegistrationModeFromPayload,
} from '../utils/membershipPolicy';

/**
 * 会員登録ゲート。互換のため window.app.memberManager を使う。
 * Phase F で hook 内 fetch に置き換える。
 */
export function useMembershipGate(switchTab: (tab: string) => void): void {
    const handledRef = useRef(false);

    useEffect(() => {
        if (handledRef.current) {
            return;
        }
        if (window.__IS_DESIGNER_EMBED_PREVIEW__ === true) {
            handledRef.current = true;
            return;
        }
        const mode = getMemberRegistrationMode();
        if (mode === 'optional') {
            handledRef.current = true;
            return;
        }

        let cancelled = false;

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
            const mm = app.memberManager;
            if (mm === undefined || mm === null) {
                return;
            }
            try {
                if (typeof mm.ensureIdentityReady === 'function') {
                    await mm.ensureIdentityReady(1500);
                }
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__) {
                    return;
                }
                if (typeof mm.fetchMemberContext === 'function') {
                    await mm.fetchMemberContext({});
                }
            } catch {
                if (window.__IS_LIFF__ === true && !window.__LINE_USER_ID__) {
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
                return;
            }
            if (!shouldShowRegistrationForm()) {
                handledRef.current = true;
                return;
            }
            handledRef.current = true;
            switchTab('member');
            if (typeof app.loadMemberTab === 'function') {
                await app.loadMemberTab();
            }
        };

        void run();
        return () => {
            cancelled = true;
        };
    }, [switchTab]);
}
