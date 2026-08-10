import '../../css/app.css';
import './app';
import { createRoot } from 'react-dom/client';
import { FrontendApp } from './FrontendApp';

const appContainer = document.getElementById('app-container');
if (appContainer !== null) {
    appContainer.replaceChildren();
    createRoot(appContainer).render(<FrontendApp />);
} else {
    const fallback = document.getElementById('react-frontend-root');
    if (fallback !== null) {
        createRoot(fallback).render(<FrontendApp />);
    }
}
