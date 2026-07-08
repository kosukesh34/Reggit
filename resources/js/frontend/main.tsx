import '../../css/app.css';
import { createRoot } from 'react-dom/client';
import { useLegacyBootstrap } from './hooks/useLegacyBootstrap';

function FrontendBootstrap(): null {
    useLegacyBootstrap();
    return null;
}

const mount = document.getElementById('react-frontend-root');
if (mount !== null) {
    createRoot(mount).render(<FrontendBootstrap />);
}
