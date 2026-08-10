import React from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { FixedHomeView } from './FixedHomeView';
import { HomeFallback } from './HomeFallback';

/**
 * ホーム: 固定デモUIは FixedHomeView、
 * Designer レイアウトがあれば BuilderLayout、それ以外は HomeFallback。
 */
export const HomePage = React.memo(function HomePage(): JSX.Element {
    if (isFixedUiShell()) {
        return <FixedHomeView />;
    }
    const blocks = getTabBlocks('home');
    if (blocks !== null) {
        return <BuilderLayout tab="home" blocks={blocks} />;
    }
    return <HomeFallback />;
});
