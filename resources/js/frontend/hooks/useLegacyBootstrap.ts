import { useEffect, useState } from 'react';
import { exposeFrontendLegacyGlobals } from '../../shared/utils/exposeFrontendLegacyGlobals';

export type BootstrapStatus = 'idle' | 'loading' | 'ready' | 'error';

export function useLegacyBootstrap(): { status: BootstrapStatus; errorMessage: string | null } {
    const [status, setStatus] = useState<BootstrapStatus>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);

    useEffect(() => {
        let isMounted = true;
        setStatus('loading');

        try {
            exposeFrontendLegacyGlobals();
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

        return () => {
            isMounted = false;
        };
    }, []);

    return { status, errorMessage };
}
