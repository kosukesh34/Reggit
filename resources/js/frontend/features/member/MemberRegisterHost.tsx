import React, { useEffect, useMemo, useRef, useState } from 'react';
import { getMemberManager } from './api';
import { MemberProfileForm } from './MemberProfileForm';

type MemberManagerHooks = {
    ensureGuestMemberContext?: () => Promise<unknown>;
    _registerFullScreen?: boolean;
    _forcedRegister?: boolean;
    _profileEditFullScreen?: boolean;
    _setupPostalCodeLookup?: (container: HTMLElement) => void;
    _syncMemberScreenOverlay?: () => void;
    saveRegisterForm?: (e: Event | React.FormEvent) => void | Promise<void>;
    saveProfileEdit?: (e: Event | React.FormEvent) => void | Promise<void>;
    closeMemberScreen?: () => void;
    memberData?: { is_member?: boolean } | null;
};

/**
 * 登録／プロフィール編集。MemberProfileForm（JSX）を描画し、
 * submit・郵便番号 lookup は MemberManager を再利用する。
 */
export function MemberRegisterHost({
    mode,
    forced,
}: {
    mode: 'register' | 'edit';
    forced?: boolean;
}): JSX.Element {
    const containerRef = useRef<HTMLDivElement>(null);
    const [ready, setReady] = useState(mode === 'edit');

    const initialProfile = useMemo((): Record<string, unknown> | null => {
        if (mode !== 'register') {
            return null;
        }
        const displayName = window.__LINE_PROFILE__?.displayName;
        if (typeof displayName === 'string' && displayName !== '') {
            return { name: displayName };
        }
        return null;
    }, [mode]);

    useEffect(() => {
        const mm = getMemberManager() as MemberManagerHooks | undefined;
        if (mm === undefined) {
            return;
        }

        let cancelled = false;
        const run = async (): Promise<void> => {
            if (mode === 'register') {
                try {
                    await mm.ensureGuestMemberContext?.();
                } catch {
                    /* ignore */
                }
                if (cancelled) {
                    return;
                }
                if (mm.memberData?.is_member === true) {
                    mm._forcedRegister = false;
                    return;
                }
                mm._registerFullScreen = true;
                mm._forcedRegister = forced === true;
                mm._profileEditFullScreen = false;
            } else {
                mm._registerFullScreen = false;
                mm._profileEditFullScreen = true;
            }
            mm._syncMemberScreenOverlay?.();
            setReady(true);
        };
        void run();

        return () => {
            cancelled = true;
        };
    }, [mode, forced]);

    useEffect(() => {
        if (!ready) {
            return;
        }
        const host = containerRef.current;
        const mm = getMemberManager() as MemberManagerHooks | undefined;
        if (host === null || mm === undefined || typeof mm._setupPostalCodeLookup !== 'function') {
            return;
        }
        mm._setupPostalCodeLookup(host);
    }, [ready, mode]);

    const handleSubmit = (event: React.FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        const mm = getMemberManager() as MemberManagerHooks | undefined;
        if (mm === undefined) {
            return;
        }
        if (mode === 'register') {
            void mm.saveRegisterForm?.(event);
            return;
        }
        void mm.saveProfileEdit?.(event);
    };

    const handleCancel = (): void => {
        const mm = getMemberManager() as MemberManagerHooks | undefined;
        mm?.closeMemberScreen?.();
    };

    if (!ready) {
        return (
            <div className="member-register-react-host" data-react-owned="1">
                <div className="loading">
                    <i className="fas fa-spinner fa-spin" aria-hidden="true" />
                    <p>読み込み中...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="member-register-react-host" data-react-owned="1">
            <MemberProfileForm
                mode={mode}
                forced={forced === true}
                fullScreen
                containerRef={containerRef}
                initialProfile={initialProfile}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
            />
        </div>
    );
}
