import React, { useEffect, useRef } from 'react';
import { isFixedUiShell } from '../../demo/demoMode';
import { BuilderLayout } from '../../layout/BuilderLayout';
import { getTabBlocks } from '../../layout/getTabBlocks';
import { ReservationsView } from './ReservationsView';

function ReservationsShell(): JSX.Element {
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
            id="reservationsSection"
            data-tab="reservations"
            data-react-owned="1"
        >
            <div className="reservations-data-mount reservations-section" data-react-owned="1">
                <ReservationsView />
            </div>
        </div>
    );
}

export const ReservationsPage = React.memo(function ReservationsPage(): JSX.Element {
    if (isFixedUiShell()) {
        return <ReservationsShell />;
    }
    const blocks = getTabBlocks('reservations');
    if (blocks !== null) {
        return <BuilderLayout tab="reservations" blocks={blocks} />;
    }
    return <ReservationsShell />;
});
