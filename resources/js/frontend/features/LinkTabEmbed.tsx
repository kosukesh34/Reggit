import React, { useEffect, useRef } from 'react';

type LinkTabEmbedProps = {
    tab: string;
    linkUrl?: string;
};

/**
 * 外部 URL タブ（link_*）の iframe 埋め込み。
 * 描画は ReggitApp.mountLinkTabEmbed に委譲する。
 */
function LinkTabEmbedInner({ tab, linkUrl }: LinkTabEmbedProps): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null || linkUrl === undefined || linkUrl === '') {
            return;
        }

        const tryMount = (): boolean => {
            const app = window.app as { mountLinkTabEmbed?: (el: HTMLElement | null) => void } | undefined;
            if (app === undefined || typeof app.mountLinkTabEmbed !== 'function') {
                return false;
            }
            if (section.dataset.layoutRendered === '1') {
                return true;
            }
            app.mountLinkTabEmbed(section);
            return section.dataset.layoutRendered === '1';
        };

        if (tryMount()) {
            return;
        }

        const onReady = (): void => {
            tryMount();
        };
        document.addEventListener('liff-ready', onReady);
        const timer = window.setInterval(() => {
            if (tryMount()) {
                window.clearInterval(timer);
            }
        }, 50);
        const stop = window.setTimeout(() => window.clearInterval(timer), 5000);

        return () => {
            document.removeEventListener('liff-ready', onReady);
            window.clearInterval(timer);
            window.clearTimeout(stop);
        };
    }, [tab, linkUrl]);

    return (
        <div
            ref={sectionRef}
            className="tab-section tab-section--link-embed"
            id={`${tab}Section`}
            data-tab={tab}
            data-link-url={linkUrl}
        />
    );
}

export const LinkTabEmbed = React.memo(LinkTabEmbedInner);
