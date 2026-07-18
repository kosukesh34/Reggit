import React, { type CSSProperties, type ReactNode } from 'react';
import type { MenuItem } from '../catalog/types';

export const DEVICE_SHELL_WIDTH = 403;
export const DEVICE_SHELL_HEIGHT = 840;
export const DEVICE_SCREEN_INSET = 14;
export const DEVICE_SCREEN_WIDTH = 375;
export const DEVICE_SCREEN_HEIGHT = 812;
export const DEVICE_BEZEL_SRC = '/images/admin/liff-iphone-bezel.svg';

export type DeviceFrameSize = 'canvas' | 'card' | 'large';

const SIZE_SHELL_WIDTH: Record<DeviceFrameSize, number> = {
    canvas: DEVICE_SHELL_WIDTH,
    card: 210,
    large: 260,
};

type DeviceFrameProps = {
    className?: string;
    size?: DeviceFrameSize;
    zoom?: number;
    /** 表示シェルの高さ上限（px）。幅指定より小さい場合は縮小して収める */
    maxHeight?: number;
    screenStyle?: CSSProperties;
    screenClassName?: string;
    children: ReactNode;
};

export function PhoneDeviceFrame({
    className = '',
    size = 'canvas',
    zoom = 100,
    maxHeight,
    screenStyle,
    screenClassName = '',
    children,
}: DeviceFrameProps) {
    const zoomFactor = size === 'canvas' ? zoom / 100 : 1;
    const widthScale = (SIZE_SHELL_WIDTH[size] * zoomFactor) / DEVICE_SHELL_WIDTH;
    const heightScale =
        maxHeight !== undefined && maxHeight > 0
            ? maxHeight / DEVICE_SHELL_HEIGHT
            : widthScale;
    const scale = Math.min(widthScale, heightScale);
    const displayWidth = DEVICE_SHELL_WIDTH * scale;
    const displayHeight = DEVICE_SHELL_HEIGHT * scale;
    const screenLeft = DEVICE_SCREEN_INSET * scale;
    const screenTop = DEVICE_SCREEN_INSET * scale;
    const screenDisplayWidth = DEVICE_SCREEN_WIDTH * scale;
    const screenDisplayHeight = DEVICE_SCREEN_HEIGHT * scale;

    return (
        <div
            className={`ld-phone-device ld-phone-device--${size} ld-phone__frame is-device ${className}`.trim()}
            style={
                {
                    width: displayWidth,
                    height: displayHeight,
                    ['--ld-device-scale' as string]: String(scale),
                    ['--ld-device-width' as string]: `${displayWidth}px`,
                    ['--ld-device-height' as string]: `${displayHeight}px`,
                } as CSSProperties
            }
        >
            <img
                src={DEVICE_BEZEL_SRC}
                alt=""
                className="ld-phone-device__bezel"
                draggable={false}
                width={DEVICE_SHELL_WIDTH}
                height={DEVICE_SHELL_HEIGHT}
                style={{ width: displayWidth, height: displayHeight }}
            />
            <div
                className="ld-phone-device__viewport"
                style={{
                    top: screenTop,
                    left: screenLeft,
                    width: screenDisplayWidth,
                    height: screenDisplayHeight,
                }}
            >
                <div
                    className={`ld-phone-device__screen ld-phone__screen ${screenClassName}`.trim()}
                    style={{
                        ...screenStyle,
                        width: DEVICE_SCREEN_WIDTH,
                        height: DEVICE_SCREEN_HEIGHT,
                        transform: `scale(${scale})`,
                        transformOrigin: 'top left',
                    }}
                >
                    <div className="ld-phone-device__status ld-phone__status" aria-hidden>
                        <span>9:41</span>
                        <span className="ld-phone-device__status-icons ld-phone__status-icons">
                            <i className="fas fa-signal" />
                            <i className="fas fa-wifi" />
                            <i className="fas fa-battery-full" />
                        </span>
                    </div>
                    <div className="ld-phone-device__island" aria-hidden />
                    {children}
                </div>
            </div>
        </div>
    );
}

type HeaderProps = {
    title: string;
    friendAddUrl?: string;
    showCart?: boolean;
    onAccountClick?: () => void;
    onCartClick?: () => void;
};

export function PhoneAppHeader({
    title,
    friendAddUrl,
    showCart = false,
    onAccountClick,
    onCartClick,
}: HeaderProps) {
    return (
        <header className="header ld-phone-app-header">
            <div className="header-content">
                <h1 className="logo">{title}</h1>
                {friendAddUrl !== undefined && friendAddUrl !== '' ? (
                    <span className="header-friend-add">
                        <i className="fab fa-line" /> 友だち追加
                    </span>
                ) : null}
                <div className="header-actions">
                    <button
                        type="button"
                        className="account-btn"
                        title="会員・設定"
                        onClick={(e) => {
                            e.stopPropagation();
                            onAccountClick?.();
                        }}
                    >
                        <i className="fas fa-user" />
                    </button>
                    {showCart ? (
                        <button
                            type="button"
                            className="cart-btn"
                            title="注文内容"
                            onClick={(e) => {
                                e.stopPropagation();
                                onCartClick?.();
                            }}
                        >
                            <i className="fas fa-shopping-bag" />
                        </button>
                    ) : null}
                </div>
            </div>
        </header>
    );
}

type TabsProps = {
    items: Array<Pick<MenuItem, 'tab' | 'label' | 'icon'>>;
    activeTab: string;
    onSelect?: (tab: string) => void;
};

export function PhoneAppTabs({ items, activeTab, onSelect }: TabsProps) {
    return (
        <footer className="main-tabs ld-phone-app-tabs">
            {items.map((item) => {
                const isActive = activeTab === item.tab;
                return (
                    <button
                        key={item.tab}
                        type="button"
                        className={`main-tab-btn${isActive ? ' active' : ''}`}
                        onClick={(e) => {
                            e.stopPropagation();
                            onSelect?.(item.tab);
                        }}
                    >
                        <i className={`fas ${item.icon}`} />
                        <span>{item.label}</span>
                    </button>
                );
            })}
        </footer>
    );
}

type MainProps = {
    padding: number;
    gap: number;
    className?: string;
    onClick?: (e?: React.MouseEvent) => void;
    onDragOver?: (e: React.DragEvent) => void;
    onDrop?: (e: React.DragEvent) => void;
    children: ReactNode;
};

export function PhoneAppMain({ padding, gap, className, onClick, onDragOver, onDrop, children }: MainProps) {
    return (
        <main
            className={`main-content ld-phone-app-main${className ? ` ${className}` : ''}`}
            onClick={onClick}
            onDragOver={onDragOver}
            onDrop={onDrop}
        >
            <div className="tab-section">
                <div
                    className="builder-container ld-phone__content"
                    style={
                        {
                            '--content-padding': `${padding}px`,
                            '--section-gap': `${gap}px`,
                            padding: `${padding}px`,
                            gap: `${gap}px`,
                        } as CSSProperties
                    }
                >
                    {children}
                </div>
            </div>
        </main>
    );
}

export const DEFAULT_PREVIEW_TABS: Array<Pick<MenuItem, 'tab' | 'label' | 'icon'>> = [
    { tab: 'home', label: 'ホーム', icon: 'fa-house' },
    { tab: 'products', label: '商品', icon: 'fa-utensils' },
    { tab: 'orders', label: '注文', icon: 'fa-receipt' },
    { tab: 'coupons', label: 'クーポン', icon: 'fa-ticket-alt' },
    { tab: 'member', label: '会員証', icon: 'fa-id-card' },
];
