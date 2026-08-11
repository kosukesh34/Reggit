import type { ReactNode } from 'react';
import { AppConfigProvider } from '../context/AppConfigContext';
import { RuntimeDataProvider } from '../context/RuntimeDataContext';
import { AppRouter } from './router';

export function App({
  isAdminStudio = false,
  checkout = null,
}: {
  isAdminStudio?: boolean;
  checkout?: ReactNode;
}) {
  return (
    <AppConfigProvider isAdminStudio={isAdminStudio}>
      <RuntimeDataProvider>
        <AppRouter />
        {checkout}
      </RuntimeDataProvider>
    </AppConfigProvider>
  );
}
