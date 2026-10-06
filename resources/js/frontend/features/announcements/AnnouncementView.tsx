import type { JSX } from 'react';
import React, { useEffect, useRef, useState } from 'react';
import {
    fetchAnnouncements,
    formatPublishedAt,
    openAnnouncementDetail,
    type Announcement,
} from './api';

type AnnouncementViewProps = {
    /** iOS ホーム埋め込み行 */
    iosHome?: boolean;
};

type LoadState = 'loading' | 'ready' | 'error';

const ANNOUNCEMENT_FORM_PREVIEW_ID = '__reggit_announcement_form_preview__';

function excerpt(body: string, maxLen: number): string {
    const text = body.replace(/\s+/g, ' ').trim();
    if (text.length <= maxLen) {
        return text;
    }
    return text.slice(0, maxLen) + '…';
}

function resolveListDesign(root: HTMLElement | null): string {
    if (root === null) {
        return 'cards';
    }
    const fromBlock = root.closest('[data-design]')?.getAttribute('data-design');
    if (fromBlock !== null && fromBlock !== undefined && fromBlock !== '') {
        return fromBlock;
    }
    return 'cards';
}

function adaptAnnouncementMediaRatio(image: HTMLImageElement): void {
    const media = image.parentElement;
    if (media === null || image.naturalWidth <= 0 || image.naturalHeight <= 0) return;
    const ratio = Math.min(3.2, Math.max(0.6, image.naturalWidth / image.naturalHeight));
    media.style.setProperty('--announcement-media-aspect', String(ratio));
}

function AnnouncementListItem({
    item,
    design,
    index,
    onOpen,
}: {
    item: Announcement;
    design: string;
    index: number;
    onOpen: (id: string) => void;
}): JSX.Element {
    const date = formatPublishedAt(item.published_at);
    const isPinned = item.is_pinned === true;
    const isUnread = item.is_read !== true;
    const hasImage = typeof item.image_url === 'string' && item.image_url !== '';
    const id = String(item.id ?? '');
    const href = `#announcement/${id}`;
    const bodyExcerpt = excerpt(item.body || '', design === 'compact' ? 48 : 90);
    const featured = design === 'cards' && index === 0 && hasImage;
    const classes = [
        'announcement-item',
        'announcement-item--link',
        `announcement-item--${design}`,
        hasImage ? 'announcement-item--has-image' : 'announcement-item--no-image',
        isPinned ? 'announcement-item--pinned' : '',
        isUnread ? 'announcement-item--unread' : '',
        featured ? 'announcement-item--featured' : '',
    ].filter((c) => c !== '').join(' ');

    return (
        <a
            href={href}
            className={classes}
            data-announcement-id={id}
            onClick={(event) => {
                event.preventDefault();
                if (id !== '') {
                    onOpen(id);
                }
            }}
        >
            {hasImage ? (
                <div className="announcement-item__media">
                    <img
                        src={item.image_url || ''}
                        alt=""
                        loading="lazy"
                        onLoad={(event) => adaptAnnouncementMediaRatio(event.currentTarget)}
                    />
                </div>
            ) : (
                <div className="announcement-item__media announcement-item__media--placeholder" aria-hidden="true">
                    <i className="fas fa-bullhorn" />
                </div>
            )}
            <div className="announcement-item__content">
                {(isPinned || isUnread) ? (
                    <div className="announcement-item__badges">
                        {isPinned ? (
                            <span className="announcement-badge announcement-badge--pinned">
                                <i className="fas fa-thumbtack" aria-hidden="true" />
                                固定
                            </span>
                        ) : null}
                        {isUnread ? <span className="announcement-badge announcement-badge--new">NEW</span> : null}
                    </div>
                ) : null}
                <div className="announcement-title">{item.title || ''}</div>
                {bodyExcerpt !== '' ? <div className="announcement-body">{bodyExcerpt}</div> : null}
                <div className="announcement-item__meta">
                    {date !== '' ? (
                        <time className="announcement-date" dateTime={item.published_at || ''}>{date}</time>
                    ) : null}
                    <span className="announcement-item__more">詳しく見る</span>
                </div>
            </div>
        </a>
    );
}

export function AnnouncementView({ iosHome = false }: AnnouncementViewProps): JSX.Element {
    const rootRef = useRef<HTMLDivElement>(null);
    const isAnnouncementFormPreview = window.__IS_DESIGNER_EMBED_PREVIEW__ === true
        && window.__ANNOUNCEMENT_FORM_PREVIEW__ === true;
    const [state, setState] = useState<LoadState>('loading');
    const [list, setList] = useState<Announcement[]>([]);
    const [formPreviewAnnouncement, setFormPreviewAnnouncement] = useState<Announcement | null>(null);
    const [design, setDesign] = useState('cards');

    useEffect(() => {
        let cancelled = false;
        setState('loading');
        void (async () => {
            try {
                const next = await fetchAnnouncements();
                if (cancelled) {
                    return;
                }
                setList(next);
                setDesign(resolveListDesign(rootRef.current));
                setState('ready');
            } catch {
                if (!cancelled) {
                    setState('error');
                }
            }
        })();
        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        if (!isAnnouncementFormPreview) return undefined;

        const receiveAnnouncementPreview = (event: MessageEvent): void => {
            if (event.origin !== window.location.origin || event.data?.type !== 'reggit:announcement-form-preview:update') {
                return;
            }
            const incoming = event.data.announcement as Partial<Announcement> | undefined;
            if (incoming === undefined || incoming === null || typeof incoming !== 'object') return;
            setFormPreviewAnnouncement({
                ...incoming,
                id: ANNOUNCEMENT_FORM_PREVIEW_ID,
                title: typeof incoming.title === 'string' ? incoming.title : 'お知らせのタイトル',
                body: typeof incoming.body === 'string' ? incoming.body : '',
                published_at: typeof incoming.published_at === 'string' ? incoming.published_at : new Date().toISOString(),
                is_read: false,
            });
            setDesign(resolveListDesign(rootRef.current));
            setState('ready');
        };

        window.addEventListener('message', receiveAnnouncementPreview);
        if (window.parent !== window) {
            window.parent.postMessage({ type: 'reggit:announcement-form-preview:ready' }, window.location.origin);
        }
        return () => window.removeEventListener('message', receiveAnnouncementPreview);
    }, [isAnnouncementFormPreview]);

    const displayList = formPreviewAnnouncement !== null ? [formPreviewAnnouncement] : list;

    const onOpen = (id: string): void => {
        if (id === ANNOUNCEMENT_FORM_PREVIEW_ID) return;
        openAnnouncementDetail(id);
        setList((prev) => prev.map((a) => (String(a.id) === id ? { ...a, is_read: true } : a)));
    };

    if (iosHome) {
        if (state === 'error') {
            return (
                <div className="ios-row">
                    <div className="ios-row__body">
                        <div className="ios-row__title">読み込みに失敗しました</div>
                    </div>
                </div>
            );
        }
        if (state === 'loading') {
            return (
                <div className="ios-row">
                    <div className="ios-row__body">
                        <div className="ios-row__title">読み込み中...</div>
                    </div>
                </div>
            );
        }
        if (displayList.length === 0) {
            return (
                <div className="ios-row">
                    <div className="ios-row__body">
                        <div className="ios-row__title">お知らせはまだありません</div>
                        <div className="ios-row__subtitle">新しいお知らせが届くとここに表示されます</div>
                    </div>
                </div>
            );
        }
        return (
            <>
                {displayList.slice(0, 5).map((a) => {
                    const id = String(a.id ?? '');
                    const date = formatPublishedAt(a.published_at);
                    return (
                        <a
                            key={id}
                            href={`#announcement/${id}`}
                            className="ios-row"
                            data-announcement-id={id}
                            onClick={(event) => {
                                event.preventDefault();
                                if (id !== '') {
                                    onOpen(id);
                                }
                            }}
                        >
                            <div className="ios-row__body">
                                <div className="ios-row__title">{a.title || ''}</div>
                                {date !== '' ? <div className="ios-row__subtitle">{date}</div> : null}
                            </div>
                            <span className="ios-row__chevron" aria-hidden="true">›</span>
                        </a>
                    );
                })}
            </>
        );
    }

    if (formPreviewAnnouncement === null && state === 'error') {
        return (
            <div className="announcements announcements--empty">
                <div className="announcement-empty">
                    <span className="announcement-empty__icon" aria-hidden="true">
                        <i className="fas fa-exclamation-circle" />
                    </span>
                    <p className="announcement-empty__title">読み込みに失敗しました</p>
                </div>
            </div>
        );
    }

    if (formPreviewAnnouncement === null && state === 'loading') {
        return (
            <div className="loading">
                <i className="fas fa-spinner fa-spin" />
                <p>読み込み中...</p>
            </div>
        );
    }

    if (displayList.length === 0) {
        return (
            <div ref={rootRef} className={`announcements announcements--empty announcements--design-${design}`}>
                <div className="announcement-empty">
                    <span className="announcement-empty__icon" aria-hidden="true">
                        <i className="fas fa-bullhorn" />
                    </span>
                    <p className="announcement-empty__title">お知らせはまだありません</p>
                    <p className="announcement-empty__hint">新しいお知らせが届くとここに表示されます</p>
                </div>
            </div>
        );
    }

    return (
        <div ref={rootRef} className={`announcements announcements--design-${design}`}>
            <div className="announcements-list">
                {displayList.map((item, index) => (
                    <AnnouncementListItem
                        key={String(item.id ?? index)}
                        item={item}
                        design={design}
                        index={index}
                        onOpen={onOpen}
                    />
                ))}
            </div>
        </div>
    );
}
