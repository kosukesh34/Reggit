import { useEffect } from 'react';

export function AdminSidebar(): null {
    useEffect(() => {
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar === null || overlay === null) {
            return;
        }

        const openSidebar = (): void => {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-visible');
            document.body.style.overflow = 'hidden';
        };

        const closeSidebar = (): void => {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-visible');
            document.body.style.overflow = '';
        };

        const onToggleClick = (): void => {
            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
                return;
            }
            openSidebar();
        };

        const onOverlayClick = (): void => {
            closeSidebar();
        };

        const sidebarLinks = sidebar.querySelectorAll('.nav-item');
        const linkHandlers: Array<{ link: Element; handler: () => void }> = [];

        toggle?.addEventListener('click', onToggleClick);
        overlay.addEventListener('click', onOverlayClick);

        sidebarLinks.forEach((link) => {
            const handler = (): void => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            };
            link.addEventListener('click', handler);
            linkHandlers.push({ link, handler });
        });

        return () => {
            toggle?.removeEventListener('click', onToggleClick);
            overlay.removeEventListener('click', onOverlayClick);
            linkHandlers.forEach(({ link, handler }) => {
                link.removeEventListener('click', handler);
            });
            document.body.style.overflow = '';
        };
    }, []);

    return null;
}
