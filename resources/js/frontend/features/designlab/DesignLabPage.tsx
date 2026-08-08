import React, { useEffect, useRef } from 'react';
import { useFrontendApp } from '../../context/FrontendAppContext';
import { DesignLabManager } from './DesignLabManager';

export function DesignLabPage(): JSX.Element {
    const { apiClient } = useFrontendApp();
    const mountRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const el = mountRef.current;
        if (el === null) {
            return;
        }
        const manager = new DesignLabManager(apiClient);
        void manager.load(el);
        return () => {
            manager.closeGachaPopup();
            document.body.classList.remove('liff-lottery-ui');
        };
    }, [apiClient]);

    return <div ref={mountRef} className="designlab-mount" />;
}
