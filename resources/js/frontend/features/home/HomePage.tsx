import React from 'react';
import { useFrontendApp } from '../../context/FrontendAppContext';

/** ホーム: LIFF レイアウトブロックは段階移行。最低限の導線を React で提供 */
export function HomePage(): JSX.Element {
    const { switchTab } = useFrontendApp();
    const menu = Array.isArray(window.__FRONTEND_MENU__) ? window.__FRONTEND_MENU__ : [];

    return (
        <div className="home-react">
            <h2 className="section-title">ホーム</h2>
            <div className="home-react__links">
                {menu
                    .filter((item): item is { tab: string; label: string } => typeof item?.tab === 'string' && item.tab !== 'home')
                    .slice(0, 8)
                    .map((item) => (
                        <button
                            key={item.tab}
                            type="button"
                            className="home-react__link"
                            onClick={() => switchTab(item.tab)}
                        >
                            {item.label || item.tab}
                        </button>
                    ))}
            </div>
        </div>
    );
}
