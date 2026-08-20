import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import type { ChannelAuthorizationHandler } from 'pusher-js';

type LiffEcho = Echo<'reverb'>;

let echo: LiffEcho | null | undefined;

function reverbAppKey(): string {
    if (typeof window !== 'undefined') {
        const fromWindow = String(window.__REVERB_APP_KEY__ ?? '').trim();
        if (fromWindow !== '') {
            return fromWindow;
        }
    }
    const fromEnv = import.meta.env.VITE_REVERB_APP_KEY;
    return typeof fromEnv === 'string' ? fromEnv.trim() : '';
}

function identityHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
    if (typeof window === 'undefined') {
        return headers;
    }
    const projectKey = window.__PROJECT_KEY__;
    if (typeof projectKey === 'string' && projectKey !== '') {
        headers['X-Project-Key'] = projectKey;
    }
    const lineUserId = window.__LINE_USER_ID__;
    if (typeof lineUserId === 'string' && lineUserId !== '') {
        headers['X-Line-User-Id'] = lineUserId;
    }
    try {
        const idToken = typeof window.liff?.getIDToken === 'function' ? window.liff.getIDToken() : null;
        if (typeof idToken === 'string' && idToken.trim() !== '') {
            headers['X-Line-Id-Token'] = idToken.trim();
        }
    } catch (_e) {
        // ignore
    }
    const apiToken = window.__API_TOKEN__;
    if (typeof apiToken === 'string' && apiToken !== '') {
        headers.Authorization = `Bearer ${apiToken}`;
    }
    const deviceIdentifier = window.__MOBILE_DEVICE_INFO__?.device_identifier;
    if (typeof deviceIdentifier === 'string' && deviceIdentifier.trim() !== '') {
        headers['X-Device-Identifier'] = deviceIdentifier.trim();
    }

    return headers;
}

const authorizeChannel: ChannelAuthorizationHandler = (params, callback) => {
    void (async () => {
        try {
            const response = await fetch('/api/broadcasting/auth', {
                method: 'POST',
                credentials: 'same-origin',
                headers: identityHeaders(),
                body: JSON.stringify({
                    socket_id: params.socketId,
                    channel_name: params.channelName,
                }),
            });
            const data = await response.json() as { auth?: string };
            if (!response.ok || typeof data.auth !== 'string') {
                callback(new Error('broadcast auth failed'), null);
                return;
            }
            callback(null, { auth: data.auth });
        } catch (error: unknown) {
            callback(error instanceof Error ? error : new Error('broadcast auth failed'), null);
        }
    })();
};

export function getLiffEcho(): LiffEcho | null {
    if (echo !== undefined) {
        return echo;
    }
    const key = reverbAppKey();
    if (key === '' || typeof window === 'undefined') {
        echo = null;
        return null;
    }
    const tunnelHost = String(window.__REVERB_WS_HOST__ ?? '').trim();
    const wsHost = tunnelHost !== '' ? tunnelHost : window.location.hostname;
    const forceTLS = tunnelHost !== '' || window.location.protocol === 'https:';
    const port = tunnelHost !== ''
        ? 443
        : (window.location.port !== ''
            ? Number(window.location.port)
            : (forceTLS ? 443 : 80));
    echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        Pusher,
        authEndpoint: '/api/broadcasting/auth',
        channelAuthorization: {
            customHandler: authorizeChannel,
        },
    });

    return echo;
}
