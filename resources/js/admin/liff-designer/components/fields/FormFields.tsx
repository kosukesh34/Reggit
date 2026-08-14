import React, { useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { ACTION_TYPES, ALIGN_OPTIONS, PRODUCT_CATEGORY_CHOICES, TAB_LABELS } from '../../catalog/blockCatalog';

const COLOR_PRESETS: string[] = [
    '#ffffff', '#fafaf9', '#f5f5f4', '#e7e5e4', '#d6d3d1', '#a8a29e', '#78716c', '#57534e', '#292524', '#1c1917', '#000000',
    '#fef2f2', '#fee2e2', '#fecaca', '#f87171', '#ef4444', '#dc2626', '#991b1b',
    '#fff7ed', '#ffedd5', '#fed7aa', '#fb923c', '#f97316', '#ea580c', '#9a3412',
    '#fefce8', '#fef9c3', '#fde047', '#eab308', '#ca8a04', '#854d0e',
    '#f0fdf4', '#dcfce7', '#86efac', '#22c55e', '#16a34a', '#166534',
    '#ecfdf5', '#d1fae5', '#6ee7b7', '#10b981', '#059669', '#065f46',
    '#f0fdfa', '#ccfbf1', '#5eead4', '#14b8a6', '#0d9488', '#115e59',
    '#ecfeff', '#cffafe', '#67e8f9', '#06b6d4', '#0891b2', '#155e75',
    '#eff6ff', '#dbeafe', '#93c5fd', '#3b82f6', '#2563eb', '#1e40af',
    '#eef2ff', '#e0e7ff', '#a5b4fc', '#6366f1', '#4f46e5', '#3730a3',
    '#f5f3ff', '#ede9fe', '#c4b5fd', '#8b5cf6', '#7c3aed', '#5b21b6',
    '#fdf2f8', '#fce7f3', '#f9a8d4', '#ec4899', '#db2777', '#9d174d',
    '#06C755', '#0284c7', '#0ea5e9', '#7c3aed', '#c2410c', '#be123c',
];

const RECENT_COLORS_KEY = 'ld-recent-colors';
const RECENT_COLORS_MAX = 10;

function normalizeHex(value: string): string {
    const raw = value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(raw)) return raw.toLowerCase();
    if (/^#[0-9a-fA-F]{3}$/.test(raw)) {
        const r = raw[1];
        const g = raw[2];
        const b = raw[3];
        return `#${r}${r}${g}${g}${b}${b}`.toLowerCase();
    }
    if (/^[0-9a-fA-F]{6}$/.test(raw)) return `#${raw.toLowerCase()}`;
    return '';
}

function readRecentColors(): string[] {
    try {
        const raw = window.localStorage.getItem(RECENT_COLORS_KEY);
        if (raw === null || raw === '') return [];
        const parsed = JSON.parse(raw) as unknown;
        if (!Array.isArray(parsed)) return [];
        return parsed
            .filter((v): v is string => typeof v === 'string')
            .map(normalizeHex)
            .filter((v) => v !== '')
            .slice(0, RECENT_COLORS_MAX);
    } catch {
        return [];
    }
}

function pushRecentColor(hex: string): string[] {
    const next = [hex, ...readRecentColors().filter((c) => c !== hex)].slice(0, RECENT_COLORS_MAX);
    try {
        window.localStorage.setItem(RECENT_COLORS_KEY, JSON.stringify(next));
    } catch {
        // ignore quota / private mode
    }
    return next;
}

type PalettePos = { top: number; left: number; width: number };

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
    const fileRef = useRef<HTMLInputElement>(null);
    const [isDragOver, setIsDragOver] = useState(false);

    const applyFile = (file: File | undefined): void => {
        if (file === undefined || !file.type.startsWith('image/')) {
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            window.alert('画像サイズは2MB以下にしてください');
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                onChange(reader.result);
            }
        };
        reader.readAsDataURL(file);
    };

    return (
        <FieldGroup
            label={label}
            hint={hint !== undefined && hint !== '' ? hint : 'クリックまたはドラッグ＆ドロップ、もしくはURLを入力'}
        >
            <div className={`ld-image-field${compact ? ' ld-image-field--compact' : ''}${isDragOver ? ' is-dragover' : ''}`}>
                <button
                    type="button"
                    className={`ld-image-field__preview${hasImage ? '' : ' is-empty'}`}
                    onClick={() => fileRef.current?.click()}
                    onDragOver={(e) => {
                        e.preventDefault();
                        setIsDragOver(true);
                    }}
                    onDragLeave={() => setIsDragOver(false)}
                    onDrop={(e) => {
                        e.preventDefault();
                        setIsDragOver(false);
                        applyFile(e.dataTransfer.files[0]);
                    }}
                >
                    {hasImage ? (
                        <img src={trimmed} alt="" />
                    ) : (
                        <span className="ld-image-field__placeholder">
                            <i className="fas fa-cloud-upload-alt" />
                            <span>クリックまたはドラッグ＆ドロップ</span>
                        </span>
                    )}
                </button>
                <input
                    ref={fileRef}
                    type="file"
                    accept="image/png,image/jpeg,image/gif,image/webp"
                    hidden
                    onChange={(e) => {
                        applyFile(e.target.files?.[0]);
                        e.target.value = '';
                    }}
                />
                <div className="ld-image-field__controls">
                    <input
                        type="url"
                        className="ld-form-input"
                        value={value.startsWith('data:image') ? '' : value}
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
    const pickerId = useId();
    const triggerRef = useRef<HTMLButtonElement>(null);
    const panelRef = useRef<HTMLDivElement>(null);
    const [open, setOpen] = useState(false);
    const [pos, setPos] = useState<PalettePos | null>(null);
    const [draftHex, setDraftHex] = useState(value || '');
    const [recent, setRecent] = useState<string[]>([]);
    const normalized = normalizeHex(value) || '#000000';

    useEffect(() => {
        setDraftHex(value || '');
    }, [value]);

    useLayoutEffect(() => {
        if (!open) return;
        setRecent(readRecentColors());
        const btn = triggerRef.current;
        if (btn === null) return;
        const rect = btn.getBoundingClientRect();
        const width = Math.min(320, Math.max(260, window.innerWidth - 24));
        const left = Math.min(Math.max(12, rect.left), window.innerWidth - width - 12);
        const below = rect.bottom + 8;
        const estimatedHeight = 420;
        const top = below + estimatedHeight > window.innerHeight - 12
            ? Math.max(12, rect.top - estimatedHeight - 8)
            : below;
        setPos({ top, left, width });
    }, [open]);

    useEffect(() => {
        if (!open) return;
        const onPointerDown = (e: PointerEvent) => {
            const target = e.target as Node | null;
            if (target === null) return;
            if (triggerRef.current !== null && triggerRef.current.contains(target)) return;
            if (panelRef.current !== null && panelRef.current.contains(target)) return;
            setOpen(false);
        };
        const onKeyDown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setOpen(false);
        };
        const onScroll = () => setOpen(false);
        document.addEventListener('pointerdown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);
        window.addEventListener('scroll', onScroll, true);
        window.addEventListener('resize', onScroll);
        return () => {
            document.removeEventListener('pointerdown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
            window.removeEventListener('scroll', onScroll, true);
            window.removeEventListener('resize', onScroll);
        };
    }, [open]);

    const commitColor = (next: string, close = false) => {
        const hex = normalizeHex(next);
        if (hex === '') return;
        onChange(hex);
        setDraftHex(hex);
        setRecent(pushRecentColor(hex));
        if (close) setOpen(false);
    };

    const onEyeDropper = async () => {
        const EyeDropperCtor = (window as Window & { EyeDropper?: new () => { open: () => Promise<{ sRGBHex: string }> } }).EyeDropper;
        if (EyeDropperCtor === undefined) return;
        try {
            const result = await new EyeDropperCtor().open();
            commitColor(result.sRGBHex);
        } catch {
            // user cancelled
        }
    };

    const hasEyeDropper = typeof window !== 'undefined'
        && (window as Window & { EyeDropper?: unknown }).EyeDropper !== undefined;

    return (
        <FieldGroup label={label}>
            <div className="ld-color-row">
                <button
                    type="button"
                    ref={triggerRef}
                    className={`ld-color-swatch${open ? ' is-open' : ''}`}
                    style={{ background: normalized }}
                    aria-label={`${label}を選択`}
                    aria-haspopup="dialog"
                    aria-expanded={open}
                    onClick={() => setOpen((prev) => !prev)}
                />
                <input
                    type="text"
                    className="ld-form-input ld-form-input--sm ld-color-hex"
                    value={draftHex}
                    maxLength={7}
                    spellCheck={false}
                    placeholder="#000000"
                    onChange={(e) => {
                        const next = e.target.value;
                        setDraftHex(next);
                        const hex = normalizeHex(next);
                        if (hex !== '') {
                            onChange(hex);
                            setRecent(pushRecentColor(hex));
                        }
                    }}
                    onBlur={() => {
                        const hex = normalizeHex(draftHex);
                        if (hex !== '') {
                            setDraftHex(hex);
                            onChange(hex);
                        } else {
                            setDraftHex(normalized);
                        }
                    }}
                />
                <button
                    type="button"
                    className="ld-btn ld-btn--ghost ld-btn--xs ld-color-open"
                    onClick={() => setOpen(true)}
                >
                    パレット
                </button>
            </div>
            {open && pos !== null
                ? createPortal(
                    <div
                        ref={panelRef}
                        className="ld-color-palette"
                        role="dialog"
                        aria-label={`${label}カラーパレット`}
                        style={{ top: pos.top, left: pos.left, width: pos.width }}
                    >
                        <div className="ld-color-palette__preview">
                            <span className="ld-color-palette__chip" style={{ background: normalized }} />
                            <div className="ld-color-palette__meta">
                                <strong>{normalized.toUpperCase()}</strong>
                                <span>クリックで適用・自由に変更可</span>
                            </div>
                        </div>

                        {recent.length > 0 ? (
                            <div className="ld-color-palette__section">
                                <p className="ld-color-palette__label">最近使った色</p>
                                <div className="ld-color-palette__grid">
                                    {recent.map((c) => (
                                        <button
                                            key={`recent-${c}`}
                                            type="button"
                                            className={`ld-color-palette__swatch${c === normalized ? ' is-active' : ''}`}
                                            style={{ background: c }}
                                            title={c}
                                            aria-label={c}
                                            onClick={() => commitColor(c)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : null}

                        <div className="ld-color-palette__section">
                            <p className="ld-color-palette__label">カラーパレット</p>
                            <div className="ld-color-palette__grid">
                                {COLOR_PRESETS.map((c) => (
                                    <button
                                        key={c}
                                        type="button"
                                        className={`ld-color-palette__swatch${c === normalized ? ' is-active' : ''}`}
                                        style={{ background: c }}
                                        title={c}
                                        aria-label={c}
                                        onClick={() => commitColor(c)}
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="ld-color-palette__section">
                            <p className="ld-color-palette__label">自由にカスタマイズ</p>
                            <div className="ld-color-palette__free">
                                <label className="ld-color-palette__free-swatch" htmlFor={pickerId} title="カラーピッカーを開く">
                                    <span style={{ background: normalized }} />
                                    <i className="fas fa-eye-dropper" />
                                    <input
                                        id={pickerId}
                                        type="color"
                                        className="ld-color-palette__native"
                                        value={normalized}
                                        onChange={(e) => commitColor(e.target.value)}
                                    />
                                </label>
                                <input
                                    type="text"
                                    className="ld-form-input ld-form-input--sm"
                                    value={draftHex}
                                    maxLength={7}
                                    spellCheck={false}
                                    placeholder="#RRGGBB"
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        setDraftHex(next);
                                        const hex = normalizeHex(next);
                                        if (hex !== '') commitColor(hex);
                                    }}
                                />
                                {hasEyeDropper ? (
                                    <button type="button" className="ld-btn ld-btn--soft ld-btn--xs" onClick={() => { void onEyeDropper(); }}>
                                        スポイト
                                    </button>
                                ) : null}
                            </div>
                            <p className="ld-color-palette__hint">左の色をクリックすると、フルカラーピッカーで任意の色を選べます</p>
                        </div>

                        <div className="ld-color-palette__foot">
                            <button type="button" className="ld-btn ld-btn--primary ld-btn--xs" onClick={() => setOpen(false)}>
                                完了
                            </button>
                        </div>
                    </div>,
                    document.body,
                )
                : null}
        </FieldGroup>
    );
}

function parseAction(action: string): { kind: string; param: string; category: string } {
    const s = String(action || '');
    if (s === '') return { kind: '', param: '', category: '' };
    if (s.startsWith('#')) {
        const [tab, query] = s.slice(1).split('?');
        const category = query !== undefined ? (new URLSearchParams(query).get('category') || '') : '';
        return { kind: 'tab', param: tab, category };
    }
    if (/^tel:/i.test(s)) return { kind: 'tel', param: s.replace(/^tel:/i, ''), category: '' };
    if (/^mailto:/i.test(s)) return { kind: 'mailto', param: s.replace(/^mailto:/i, ''), category: '' };
    return { kind: 'url', param: s, category: '' };
}

function buildAction(kind: string, param: string, category = ''): string {
    if (kind === '') return '';
    if (kind === 'tab') {
        if (!param) return '';
        return category !== '' && param === 'products' ? `#${param}?category=${category}` : `#${param}`;
    }
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
            {current.kind === 'tab' && current.param === 'products' ? (
                <select
                    className="ld-form-select ld-form-select--nested"
                    value={current.category}
                    onChange={(e) => onChange(buildAction('tab', 'products', e.target.value))}
                    aria-label="商品カテゴリ絞り込み"
                >
                    {PRODUCT_CATEGORY_CHOICES.map((o) => (
                        <option key={o.value === '' ? '_all' : o.value} value={o.value}>{o.label}</option>
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
