import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { MemberView } from './MemberView';

function MemberShell(): JSX.Element {
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
            id="memberSection"
            data-tab="member"
            data-react-owned="1"
        >
            <div className="member-section member-card-mount" data-react-owned="1">
                <MemberView />
            </div>
        </div>
    );
}

export const MemberPage = React.memo(function MemberPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <MemberShell />;
    }
    const blocks = getTabBlocks('member');
    if (blocks !== null) {
        return <BuilderLayout tab="member" blocks={blocks} />;
    }
    return <MemberShell />;
});
