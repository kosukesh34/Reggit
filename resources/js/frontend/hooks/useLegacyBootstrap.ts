import { useEffect, useState } from 'react';
import { loadLegacyFrontendScripts } from '../legacy/loadLegacyFrontendScripts';

export type BootstrapStatus = 'idle' | 'loading' | 'ready' | 'error';

export function useLegacyBootstrap(): { status: BootstrapStatus; errorMessage: string | null } {
    const [status, setStatus] = useState<BootstrapStatus>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    useEffect(() => {
        let isMounted = true;
        setStatus('loading');

        const bootstrap = async (): Promise<void> => {
            try {
                await loadLegacyFrontendScripts();
                if (isMounted) {
                    setStatus('ready');
                }
            } catch (error) {
                if (!isMounted) {
                    return;
                }
                const message = error instanceof Error ? error.message : 'Unknown frontend bootstrap error';
                setErrorMessage(message);
                setStatus('error');
                // eslint-disable-next-line no-console
                console.error('[frontend-bootstrap] failed:', error);
            }
        };

        void bootstrap();

        return () => {
            isMounted = false;
        };
    }, []);

    return { status, errorMessage };
}
