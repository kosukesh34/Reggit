import React, { createContext, useContext, useMemo } from 'react';
import { useLiffRouter, type LiffRouter } from '../hooks/useLiffRouter';

type LiffAppContextValue = LiffRouter;

const LiffAppContext = createContext<LiffAppContextValue | null>(null);

export function LiffAppProvider({ children }: { children: React.ReactNode }): JSX.Element {
    const router = useLiffRouter();
    const value = useMemo(() => router, [router.activeTab, router.switchTab]);
    return <LiffAppContext.Provider value={value}>{children}</LiffAppContext.Provider>;
}

export function useLiffApp(): LiffAppContextValue {
    const ctx = useContext(LiffAppContext);
    if (ctx === null) {
        throw new Error('useLiffApp must be used within LiffAppProvider');
    }
    return ctx;
}
