import type { ReactNode } from 'react';
import { AppConfigProvider } from '../context/AppConfigContext';
import { RuntimeDataProvider } from '../context/RuntimeDataContext';
import { NativeConfigProvider, type NativeConfig } from '../native/nativeConfig';
import { NativeNavigationProvider } from '../native/NativeNavigation';
import { AppRouter } from './router';

export function App({
  isAdminStudio = false,
  checkout = null,
  initialNativeConfig,
}: {
  isAdminStudio?: boolean;
  checkout?: ReactNode;
  initialNativeConfig?: NativeConfig;
}) {
  return (
    <AppConfigProvider isAdminStudio={isAdminStudio}>
      <NativeConfigProvider initialConfig={initialNativeConfig}>
        <RuntimeDataProvider>
          <NativeNavigationProvider><AppRouter /></NativeNavigationProvider>
          {checkout}
        </RuntimeDataProvider>
      </NativeConfigProvider>
    </AppConfigProvider>
  );
}
