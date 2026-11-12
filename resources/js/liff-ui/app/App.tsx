import type { ReactNode } from 'react';
import { AppConfigProvider } from '../context/AppConfigContext';
import { RuntimeDataProvider } from '../context/RuntimeDataContext';
import { NativeConfigProvider, type NativeConfig } from '../native/nativeConfig';
import { NativeNavigationProvider } from '../native/NativeNavigation';
import { AppRouter } from './router';

export function App({
  checkout = null,
  initialNativeConfig,
}: {
  checkout?: ReactNode;
  initialNativeConfig?: NativeConfig;
}) {
  return (
    <AppConfigProvider>
      <NativeConfigProvider initialConfig={initialNativeConfig}>
        <RuntimeDataProvider>
          <NativeNavigationProvider><AppRouter /></NativeNavigationProvider>
          {checkout}
        </RuntimeDataProvider>
      </NativeConfigProvider>
    </AppConfigProvider>
  );
}
