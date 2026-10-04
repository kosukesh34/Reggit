import { pointQrTokenFromUrl } from '../../../frontend/member/pointQrToken';
import { useState } from 'react';
import { useRuntimeData } from '../../context/RuntimeDataContext';
import { ApiClient } from '../../../shared/utils/apiClient';
import { ensureLiffIdentityForLoad } from '../../../frontend/demo/ensureIdentity';


export function PointQrClaim() {
    const { mode, refresh } = useRuntimeData();
    const [token] = useState(() => pointQrTokenFromUrl(window.location.href));
    const [busy, setBusy] = useState(false);
    const [done, setDone] = useState(false);
    const [message, setMessage] = useState('');
    if (!token || mode !== 'live') return null;

    const claim = async () => {
        if (busy || done) return;
        setBusy(true);
        try {
            await ensureLiffIdentityForLoad({ waitNativeDevice: true });
            const result = await new ApiClient().post(`/api/points/grant-code/${token}?project_key=${encodeURIComponent(window.__PROJECT_KEY__ || '')}`, {});
            if (result.success === false) throw new Error(result.error);
            setDone(true);
            setMessage(`${(result.data as { granted_points: number }).granted_points}ポイントを受け取りました。`);
            document.dispatchEvent(new CustomEvent('liff-member-reload'));
            await refresh();
        } catch (error) {
            setMessage(error instanceof Error ? error.message : 'ポイントを受け取れませんでした。');
        } finally {
            setBusy(false);
        }
    };

    return <section className="card card--lg" aria-label="ポイント付与QR">
        <h2>ポイントを受け取る</h2>
        <p>会員登録・ログイン後に、店舗QRのポイントを受け取れます。</p>
        {message ? <p role="status">{message}</p> : null}
        {!done ? <button className="button button--solid button--md" type="button" disabled={busy} onClick={() => { void claim(); }}>{busy ? '受け取り中…' : 'ポイントを受け取る'}</button> : null}
    </section>;
}
