import React from 'react';

type IconProps = {
    className?: string;
};

export function IconHome({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 10.5L12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
        </svg>
    );
}

export function IconOrder({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 7h16v2.2l-1.2 10.1a1.8 1.8 0 0 1-1.8 1.6H7a1.8 1.8 0 0 1-1.8-1.6L4 9.2V7z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <path d="M8 7V6a4 4 0 0 1 8 0v1" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </svg>
    );
}

export function IconCard({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="3" y="6" width="18" height="12" rx="2.5" stroke="currentColor" strokeWidth="1.7" />
            <path d="M3 10h18" stroke="currentColor" strokeWidth="1.7" />
            <path d="M7 15h4" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </svg>
    );
}

export function IconVisit({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10z" stroke="currentColor" strokeWidth="1.7" strokeLinejoin="round" />
            <circle cx="12" cy="11" r="2.2" stroke="currentColor" strokeWidth="1.7" />
        </svg>
    );
}

export function IconCart({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 8h12l-1 11H7L6 8z" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" />
            <path d="M9 8V7a3 3 0 0 1 6 0v1" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}

export function IconChevron({ className }: IconProps): JSX.Element {
    return (
        <svg className={className} width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M9 6l6 6-6 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

export function iconForPrimary(primary: string): JSX.Element {
    if (primary === 'products') {
        return <IconOrder />;
    }
    if (primary === 'member') {
        return <IconCard />;
    }
    if (primary === 'queue') {
        return <IconVisit />;
    }
    return <IconHome />;
}
