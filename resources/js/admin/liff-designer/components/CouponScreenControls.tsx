import React from 'react';
import { COUPON_MEDIA_STYLES, COUPON_SCREEN_DESIGN_CHOICES } from '../catalog/blockCatalog';

type Props = {
    design: string;
    mediaStyle: string;
    showAvailable: boolean;
    showHistory: boolean;
    onDesign: (v: string) => void;
    onMediaStyle: (v: string) => void;
    onShowAvailable: (v: boolean) => void;
    onShowHistory: (v: boolean) => void;
};

export function CouponScreenControls({
    design,
    mediaStyle,
    showAvailable,
    showHistory,
    onDesign,
    onMediaStyle,
    onShowAvailable,
    onShowHistory,
}: Props) {
    return (
        <div className="ld-coupon-controls">
            <div className="ld-mcard-finish">
                <p className="ld-mcard-finish__label">画面デザイン</p>
                <div className="ld-coupon-controls__designs">
                    {COUPON_SCREEN_DESIGN_CHOICES.map((opt) => (
                        <button
                            key={opt.value}
                            type="button"
                            className={`ld-coupon-design${design === opt.value ? ' is-active' : ''}`}
                            onClick={() => onDesign(opt.value)}
                        >
                            <span className={`ld-coupon-design__mock ld-coupon-design__mock--${opt.value}`} aria-hidden>
                                <i />
                                <i />
                            </span>
                            <em>{opt.label}</em>
                        </button>
                    ))}
                </div>
            </div>

            <div className="ld-mcard-finish">
                <p className="ld-mcard-finish__label">画像の見せ方</p>
                <div className="ld-mcard-finish__chips">
                    {COUPON_MEDIA_STYLES.map((opt) => (
                        <button
                            key={opt.value}
                            type="button"
                            className={`ld-mcard-finish__chip ld-coupon-media--${opt.value}${mediaStyle === opt.value ? ' is-active' : ''}`}
                            onClick={() => onMediaStyle(opt.value)}
                        >
                            <span className="ld-mcard-finish__swatch" aria-hidden />
                            <em>{opt.label}</em>
                        </button>
                    ))}
                </div>
            </div>

            <div className="ld-mcard-finish">
                <p className="ld-mcard-finish__label">一覧に出すクーポン</p>
                <div className="ld-coupon-controls__toggles">
                    <button
                        type="button"
                        className={`ld-coupon-toggle${showAvailable ? ' is-active' : ''}`}
                        onClick={() => onShowAvailable(!showAvailable)}
                        aria-pressed={showAvailable}
                    >
                        <i className="fas fa-check-circle" />
                        未使用・利用可能
                    </button>
                    <button
                        type="button"
                        className={`ld-coupon-toggle${showHistory ? ' is-active' : ''}`}
                        onClick={() => onShowHistory(!showHistory)}
                        aria-pressed={showHistory}
                    >
                        <i className="fas fa-history" />
                        利用済み・期限切れ
                    </button>
                </div>
                {!showAvailable && !showHistory ? (
                    <p className="ld-form-hint ld-form-hint--warn">どちらか一方はオンにしてください</p>
                ) : null}
            </div>
        </div>
    );
}
