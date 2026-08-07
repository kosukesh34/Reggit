import React, { useCallback, useEffect, useState } from 'react';
import { AppConstants } from '../../../shared/constants';
import { useFrontendApp } from '../../context/FrontendAppContext';

type Announcement = {
    id?: number | string;
    title?: string;
    body?: string;
    image_url?: string | null;
    published_at?: string;
    is_pinned?: boolean;
    is_read?: boolean;
};

function excerpt(body: string, maxLen: number): string {
    const text = body.replace(/\s+/g, ' ').trim();
    if (text.length <= maxLen) {
        return text;
    }
    return text.slice(0, maxLen) + '…';
}

export function AnnouncementPage(): JSX.Element {
    const { apiClient, pageRouter } = useFrontendApp();
    const [list, setList] = useState<Announcement[]>([]);
    const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');

    const load = useCallback(async () => {
        setStatus('loading');
        try {
            const result = await apiClient.get(AppConstants.API_ENDPOINTS.ANNOUNCEMENTS);
            const data = result.success ? result.data as { announcements?: Announcement[] } : null;
            setList(data && Array.isArray(data.announcements) ? data.announcements : []);
            setStatus('ready');
        } catch {
            setStatus('error');
        }
    }, [apiClient]);

    useEffect(() => {
        void load();
    }, [load]);

    const openDetail = async (item: Announcement) => {
        const section = pageRouter.openPage({
            key: 'announcement_detail',
            hash: 'announcement/' + String(item.id),
            returnHash: pageRouter.resolveReturnHash('announcements'),
            title: item.title || 'お知らせ',
            bodyClass: 'liff-page-announcement-detail',
        });
        if (section === null) {
            return;
        }
        const date = item.published_at ? new Date(item.published_at).toLocaleString('ja-JP') : '';
        const body = (item.body || '').replace(/\n/g, '<br>');
        section.innerHTML = `
            <article class="announcement-detail">
                ${item.image_url ? `<div class="announcement-detail__hero"><img src="${item.image_url}" alt=""></div>` : ''}
                <div class="announcement-detail__panel">
                    <h1 class="announcement-detail__title">${item.title || ''}</h1>
                    ${date !== '' ? `<p class="announcement-detail__date">${date}</p>` : ''}
                    ${body !== '' ? `<div class="announcement-detail__body">${body}</div>` : ''}
                </div>
            </article>
        `;
        try {
            await apiClient.post(AppConstants.API_ENDPOINTS.ANNOUNCEMENT_READ(item.id ?? ''), {});
        } catch {
            /* ignore */
        }
    };

    if (status === 'loading') {
        return <div className="loading"><i className="fas fa-spinner fa-spin" /><p>読み込み中...</p></div>;
    }
    if (status === 'error') {
        return (
            <div className="announcements announcements--empty">
                <div className="announcement-empty">
                    <span className="announcement-empty__icon" aria-hidden="true"><i className="fas fa-exclamation-circle" /></span>
                    <p className="announcement-empty__title">読み込みに失敗しました</p>
                </div>
            </div>
        );
    }
    if (list.length === 0) {
        return (
            <div className="announcements announcements--empty announcements--design-cards">
                <div className="announcement-empty">
                    <span className="announcement-empty__icon" aria-hidden="true"><i className="fas fa-bullhorn" /></span>
                    <p className="announcement-empty__title">お知らせはまだありません</p>
                    <p className="announcement-empty__hint">新しいお知らせが届くとここに表示されます</p>
                </div>
            </div>
        );
    }

    return (
        <div className="announcements announcements--design-cards">
            <div className="announcements-list">
                {list.map((a) => {
                    const date = a.published_at ? new Date(a.published_at).toLocaleDateString('ja-JP') : '';
                    const text = excerpt(a.body || '', 90);
                    return (
                        <button
                            key={String(a.id)}
                            type="button"
                            className={`announcement-item announcement-item--cards${a.is_read !== true ? ' announcement-item--unread' : ''}${a.is_pinned === true ? ' announcement-item--pinned' : ''}`}
                            onClick={() => void openDetail(a)}
                        >
                            <div className="announcement-item__content">
                                <div className="announcement-title">{a.title || ''}</div>
                                {text !== '' ? <div className="announcement-body">{text}</div> : null}
                                <div className="announcement-item__meta">
                                    {date !== '' ? <time className="announcement-date">{date}</time> : null}
                                    <span className="announcement-item__more">詳しく見る</span>
                                </div>
                            </div>
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
