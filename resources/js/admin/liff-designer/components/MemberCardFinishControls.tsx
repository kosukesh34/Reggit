import React from 'react';
import {
    MEMBER_CARD_BG_STYLES,
    MEMBER_CARD_DESIGN_CHOICES,
    MEMBER_CARD_GLOSS_CHOICES,
    MEMBER_CARD_SURFACE_CHOICES,
} from '../catalog/blockCatalog';

export function MemberCardDesignThumb({ design }: { design: string }) {
    return (
        <span className={`ld-mcard-thumb ld-mcard-thumb--${design}`} aria-hidden>
            <span className="ld-mcard-thumb__logo" />
            <span className="ld-mcard-thumb__lines">
                <i />
                <i />
            </span>
            <span className="ld-mcard-thumb__rank" />
            <span className="ld-mcard-thumb__code">
                <i /><i /><i /><i /><i /><i /><i /><i />
            </span>
        </span>
    );
}

export function MemberCardDesignPicker({
    value,
    onChange,
}: {
    value: string;
    onChange: (v: string) => void;
}) {
    return (
        <div className="ld-mcard-finish">
            <p className="ld-mcard-finish__label">レイアウト</p>
            <div className="ld-apply-setup__cards">
                {MEMBER_CARD_DESIGN_CHOICES.map((choice) => (
                    <button
                        key={choice.value}
                        type="button"
                        className={`ld-apply-setup__card${value === choice.value ? ' is-active' : ''}`}
                        onClick={() => onChange(choice.value)}
                    >
                        <MemberCardDesignThumb design={choice.value} />
                        <em>{choice.label}</em>
                    </button>
                ))}
            </div>
        </div>
    );
}

type Props = {
    bgStyle: string;
    gloss: string;
    surface: string;
    onBgStyle: (v: string) => void;
    onGloss: (v: string) => void;
    onSurface: (v: string) => void;
};

function ChipRow({
    label,
    value,
    options,
    onChange,
    kind,
}: {
    label: string;
    value: string;
    options: Array<{ value: string; label: string }>;
    onChange: (v: string) => void;
    kind: 'bg' | 'gloss' | 'surface';
}) {
    return (
        <div className="ld-mcard-finish">
            <p className="ld-mcard-finish__label">{label}</p>
            <div className="ld-mcard-finish__chips">
                {options.map((opt) => (
                    <button
                        key={opt.value}
                        type="button"
                        className={`ld-mcard-finish__chip ld-mcard-finish__chip--${kind}-${opt.value}${value === opt.value ? ' is-active' : ''}`}
                        onClick={() => onChange(opt.value)}
                    >
                        <span className="ld-mcard-finish__swatch" aria-hidden />
                        <em>{opt.label}</em>
                    </button>
                ))}
            </div>
        </div>
    );
}

export function MemberCardFinishControls({
    bgStyle,
    gloss,
    surface,
    onBgStyle,
    onGloss,
    onSurface,
}: Props) {
    return (
        <div className="ld-mcard-finish-group">
            <ChipRow
                label="塗り（背景）"
                value={bgStyle || 'gradient'}
                options={MEMBER_CARD_BG_STYLES}
                onChange={onBgStyle}
                kind="bg"
            />
            <ChipRow
                label="光沢"
                value={gloss || 'soft'}
                options={MEMBER_CARD_GLOSS_CHOICES}
                onChange={onGloss}
                kind="gloss"
            />
            <ChipRow
                label="表面"
                value={surface || 'plastic'}
                options={MEMBER_CARD_SURFACE_CHOICES}
                onChange={onSurface}
                kind="surface"
            />
        </div>
    );
}
