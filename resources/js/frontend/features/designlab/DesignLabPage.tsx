import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { DesignLabView } from './DesignLabView';

function DesignLabShell(): JSX.Element {
    const sectionRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const section = sectionRef.current;
        if (section === null) {
            return;
        }
        section.dataset.reactOwned = '1';
        section.dataset.layoutRendered = '1';
    }, []);

    return (
        <div
            ref={sectionRef}
            className="tab-section"
            id="designlabSection"
            data-tab="designlab"
            data-react-owned="1"
        >
            <div className="designlab-data-mount" data-react-owned="1">
                <DesignLabView />
            </div>
        </div>
    );
}

export const DesignLabPage = React.memo(function DesignLabPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <DesignLabShell />;
    }
    const blocks = getTabBlocks('designlab');
    if (blocks !== null) {
        return <BuilderLayout tab="designlab" blocks={blocks} />;
    }
    return <DesignLabShell />;
});
