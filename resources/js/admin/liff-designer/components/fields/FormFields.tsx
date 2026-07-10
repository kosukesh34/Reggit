import React from 'react';
import { ACTION_TYPES, ALIGN_OPTIONS, TAB_LABELS } from '../../catalog/blockCatalog';

export function FieldGroup({ label, children, hint }: { label: string; children: React.ReactNode; hint?: string }) {
    return (
        <div className="ld-form-group">
            <label className="ld-form-label">{label}</label>
            {children}
            {hint !== undefined && hint !== '' ? <p className="ld-form-hint">{hint}</p> : null}
        </div>
    );
}

function isLikelyImageUrl(value: string): boolean {
    const trimmed = value.trim();
    return trimmed !== '' && (trimmed.startsWith('http') || trimmed.startsWith('/') || trimmed.startsWith('data:image'));
}

export function ImageUrlField({
    label,
    value,
    onChange,
    hint,
    compact = false,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    hint?: string;
    compact?: boolean;
}) {
    const trimmed = value.trim();
    const hasImage = isLikelyImageUrl(trimmed);

    return (
        <FieldGroup
            label={label}
            hint={hint !== undefined && hint !== '' ? hint : '画像のURLを入力するとプレビューに反映されます'}
        >
            <div className={`ld-image-field${compact ? ' ld-image-field--compact' : ''}`}>
                <div className={`ld-image-field__preview${hasImage ? '' : ' is-empty'}`}>
                    {hasImage ? (
                        <img src={trimmed} alt="" />
                    ) : (
                        <span className="ld-image-field__placeholder">
                            <i className="fas fa-image" />
                            <span>画像未設定</span>
                        </span>
                    )}
                </div>
                <div className="ld-image-field__controls">
                    <input
                        type="url"
                        className="ld-form-input"
                        value={value}
                        placeholder="https://example.com/image.jpg"
                        onChange={(e) => onChange(e.target.value)}
                    />
                    {hasImage ? (
                        <button type="button" className="ld-btn ld-btn--ghost ld-btn--xs ld-image-field__clear" onClick={() => onChange('')}>
                            <i className="fas fa-times" /> クリア
                        </button>
                    ) : null}
                </div>
            </div>
        </FieldGroup>
    );
}

export function TextInput({
    label,
    value,
    onChange,
    placeholder,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
    placeholder?: string;
}) {
    return (
        <FieldGroup label={label}>
            <input
                type="text"
                className="ld-form-input"
                value={value}
                placeholder={placeholder}
                onChange={(e) => onChange(e.target.value)}
            />
        </FieldGroup>
    );
}

export function TextArea({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
    return (
        <FieldGroup label={label}>
            <textarea className="ld-form-textarea" rows={3} value={value} onChange={(e) => onChange(e.target.value)} />
        </FieldGroup>
    );
}

export function SelectField({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Array<{ value: string; label: string }>;
    onChange: (v: string) => void;
}) {
    return (
        <FieldGroup label={label}>
            <select className="ld-form-select" value={value} onChange={(e) => onChange(e.target.value)}>
                {options.map((o) => (
                    <option key={o.value === '' ? '_empty' : o.value} value={o.value}>{o.label}</option>
                ))}
            </select>
        </FieldGroup>
    );
}

export function RangeField({
    label,
    value,
    min,
    max,
    onChange,
    unit,
}: {
    label: string;
    value: number;
    min: number;
    max: number;
    onChange: (v: number) => void;
    unit?: string;
}) {
    return (
        <FieldGroup label={label}>
            <div className="ld-range-row">
                <input
                    type="range"
                    className="ld-form-range"
                    min={min}
                    max={max}
                    value={value}
                    onChange={(e) => onChange(Number(e.target.value))}
                />
                <span className="ld-range-val">{value}{unit !== undefined && unit !== '' ? unit : ''}</span>
            </div>
        </FieldGroup>
    );
}

export function CheckboxField({
    label,
    checked,
    onChange,
}: {
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
}) {
    return (
        <label className="ld-checkbox">
            <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />
            <span>{label}</span>
        </label>
    );
}

export function ColorField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
}) {
    const hex = value || '#000000';
    return (
        <FieldGroup label={label}>
            <div className="ld-color-row">
                <input type="color" className="ld-color-picker" value={hex} onChange={(e) => onChange(e.target.value)} />
                <input
                    type="text"
                    className="ld-form-input ld-form-input--sm"
                    value={hex}
                    maxLength={7}
                    onChange={(e) => onChange(e.target.value)}
                />
            </div>
        </FieldGroup>
    );
}

function parseAction(action: string): { kind: string; param: string } {
    const s = String(action || '');
    if (s === '') return { kind: '', param: '' };
    if (s.startsWith('#')) return { kind: 'tab', param: s.slice(1) };
    if (/^tel:/i.test(s)) return { kind: 'tel', param: s.replace(/^tel:/i, '') };
    if (/^mailto:/i.test(s)) return { kind: 'mailto', param: s.replace(/^mailto:/i, '') };
    return { kind: 'url', param: s };
}

function buildAction(kind: string, param: string): string {
    if (kind === '') return '';
    if (kind === 'tab') return param ? `#${param}` : '';
    if (kind === 'tel') return param ? `tel:${param}` : '';
    if (kind === 'mailto') return param ? `mailto:${param}` : '';
    return param || '';
}

export function ActionField({
    label,
    value,
    onChange,
}: {
    label: string;
    value: string;
    onChange: (v: string) => void;
}) {
    const current = parseAction(value);
    const tabOptions = Object.keys(TAB_LABELS)
        .filter((k) => k !== 'announcements')
        .map((k) => ({ value: k, label: TAB_LABELS[k] }));

    return (
        <FieldGroup label={label}>
            <select
                className="ld-form-select"
                value={current.kind}
                onChange={(e) => {
                    const kind = e.target.value;
                    if (kind === 'tab') {
                        onChange(buildAction('tab', current.param || tabOptions[0]?.value || 'home'));
                    } else {
                        onChange(buildAction(kind, kind === current.kind ? current.param : ''));
                    }
                }}
            >
                {ACTION_TYPES.map((o) => (
                    <option key={o.value === '' ? '_none' : o.value} value={o.value}>{o.label}</option>
                ))}
            </select>
            {current.kind === 'tab' ? (
                <select
                    className="ld-form-select ld-form-select--nested"
                    value={current.param || tabOptions[0]?.value}
                    onChange={(e) => onChange(buildAction('tab', e.target.value))}
                >
                    {tabOptions.map((o) => (
                        <option key={o.value} value={o.value}>{o.label}</option>
                    ))}
                </select>
            ) : null}
            {current.kind === 'url' || current.kind === 'tel' || current.kind === 'mailto' ? (
                <input
                    type="text"
                    className="ld-form-input ld-form-input--nested"
                    value={current.param}
                    placeholder={current.kind === 'tel' ? '03-1234-5678' : current.kind === 'mailto' ? 'info@example.com' : 'https://...'}
                    onChange={(e) => onChange(buildAction(current.kind, e.target.value))}
                />
            ) : null}
        </FieldGroup>
    );
}

export function AlignField({ value, onChange }: { value: string; onChange: (v: string) => void }) {
    return <SelectField label="文字揃え" value={value || 'left'} options={ALIGN_OPTIONS} onChange={onChange} />;
}

export function DesignPicker({
    value,
    options,
    onChange,
}: {
    type?: string;
    value: string;
    options: Array<{ value: string; label: string }>;
    onChange: (v: string) => void;
}) {
    if (options.length === 0) return null;
    const current = value || options[0]?.value || '';
    return (
        <FieldGroup label="デザイン" hint="選択した見た目がプレビューと本番画面に反映されます。">
            <div className="ld-design-presets">
                {options.map((opt, index) => (
                    <button
                        key={opt.value === '' ? '_auto' : opt.value}
                        type="button"
                        className={`ld-design-preset${current === opt.value ? ' is-active' : ''}`}
                        onClick={() => onChange(opt.value)}
                    >
                        <span className={`ld-design-preset__swatch ld-design-preset__swatch--${index % 4}`} />
                        <span className="ld-design-preset__label">{opt.label}</span>
                    </button>
                ))}
            </div>
        </FieldGroup>
    );
}
