import React from 'react';
import { isFixedUiShell } from '../demo/demoMode';
import { BuilderLayout } from '../layout/BuilderLayout';
import { getTabBlocks } from '../layout/getTabBlocks';
import { ManagerPaintHost } from './ManagerPaintHost';
import { ManagerTabHost } from './ManagerTabHost';

type AppPaintTarget = NonNullable<Window['app']>;

type TabPageWithBuilderOrPaintProps = {
    tab: string;
    paint: (app: AppPaintTarget) => void;
};

/**
 * Designer ブロックあり → BuilderLayout、
 * 固定デモUI → ManagerTabHost、
 * それ以外 → React 所有マウント + Manager paint。
 */
export function TabPageWithBuilderOrPaint({ tab, paint }: TabPageWithBuilderOrPaintProps): JSX.Element {
    if (isFixedUiShell()) {
        return <ManagerTabHost tab={tab} />;
    }
    const blocks = getTabBlocks(tab);
    if (blocks !== null) {
        return <BuilderLayout tab={tab} blocks={blocks} />;
    }
    return <ManagerPaintHost tab={tab} paint={paint} />;
}
