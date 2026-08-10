import React from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { ManagerTabHost } from '../ManagerTabHost';

/**
 * ホーム: Designer レイアウトがあるときは React BuilderLayout、
 * それ以外（固定デモUI・未設定）は Manager 経路。
 */
export const HomePage = React.memo(function HomePage(): JSX.Element {
    if (isFixedUiShell()) {
        return <ManagerTabHost tab="home" />;
    }
    const blocks = getTabBlocks('home');
    if (blocks !== null) {
        return <BuilderLayout tab="home" blocks={blocks} />;
    }
    return <ManagerTabHost tab="home" />;
});
