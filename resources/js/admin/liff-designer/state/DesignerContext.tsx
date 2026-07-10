import React, { createContext, useContext, useMemo, useReducer } from 'react';
import type { DesignerConfig, DesignerState } from '../catalog/types';
import { designerReducer, hydrateDesignerState, type DesignerAction } from './designerReducer';

type DesignerContextValue = {
    state: DesignerState;
    dispatch: React.Dispatch<DesignerAction>;
    config: DesignerConfig;
};

const DesignerContext = createContext<DesignerContextValue | null>(null);

export function DesignerProvider({ config, children }: { config: DesignerConfig; children: React.ReactNode }) {
    const [state, dispatch] = useReducer(designerReducer, config, hydrateDesignerState);
    const value = useMemo(() => ({ state, dispatch, config }), [state, dispatch, config]);
    return <DesignerContext.Provider value={value}>{children}</DesignerContext.Provider>;
}

export function useDesigner(): DesignerContextValue {
    const ctx = useContext(DesignerContext);
    if (ctx === null) {
        throw new Error('useDesigner must be used within DesignerProvider');
    }
    return ctx;
}
