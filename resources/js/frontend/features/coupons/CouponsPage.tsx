import React from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { CouponsView } from './CouponsView';

function CouponsShell(): JSX.Element {
    return (
        <div
            className="tab-section"
            id="couponsSection"
            data-tab="coupons"
            data-react-owned="1"
            data-layout-rendered="1"
        >
            <CouponsView />
        </div>
    );
}

export const CouponsPage = React.memo(function CouponsPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <CouponsShell />;
    }
    const blocks = getTabBlocks('coupons');
    if (blocks !== null) {
        return <BuilderLayout tab="coupons" blocks={blocks} />;
    }
    return <CouponsShell />;
});
