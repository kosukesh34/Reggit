import { AppConfigProvider } from '../context/AppConfigContext';
import { RuntimeDataProvider } from '../context/RuntimeDataContext';
import { AppRouter } from './router';

export function App({ isAdminStudio = false }: { isAdminStudio?: boolean }) {
  return (
    <AppConfigProvider isAdminStudio={isAdminStudio}>
      <RuntimeDataProvider>
        <AppRouter />
      </RuntimeDataProvider>
    </AppConfigProvider>
  );
}
