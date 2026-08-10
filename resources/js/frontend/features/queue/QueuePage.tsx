import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { QueueView } from './QueueView';

function QueueShell(): JSX.Element {
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
            id="queueSection"
            data-tab="queue"
            data-react-owned="1"
        >
            <div className="queue-data-mount" data-react-owned="1">
                <QueueView />
            </div>
        </div>
    );
}

export const QueuePage = React.memo(function QueuePage(): JSX.Element {
    if (isFixedUiShell()) {
        return <QueueShell />;
    }
    const blocks = getTabBlocks('queue');
    if (blocks !== null) {
        return <BuilderLayout tab="queue" blocks={blocks} />;
    }
    return <QueueShell />;
});
