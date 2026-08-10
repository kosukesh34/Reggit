import React from 'react';
import { TabPageWithBuilderOrPaint } from '../TabPageWithBuilderOrPaint';
import { paintReservations } from '../tabPainters';

export const ReservationsPage = React.memo(function ReservationsPage(): JSX.Element {
    return <TabPageWithBuilderOrPaint tab="reservations" paint={paintReservations} />;
});
