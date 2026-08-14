import React, { useMemo } from 'react';
import { getMemberManager, type MemberPayload } from './api';

export type ProfileFieldSetting = {
    key: string;
    label?: string;
    required?: boolean;
    input_type?: string;
    values?: unknown[];
    description?: string;
};

const DEFAULT_FIELDS: ProfileFieldSetting[] = [
    { key: 'name', label: 'お名前', required: true },
    { key: 'kana', label: 'お名前（カナ）', required: false },
    { key: 'phone', label: '電話番号', required: false },
    { key: 'email', label: 'メールアドレス', required: false },
    { key: 'birthday', label: '誕生日', required: false },
    { key: 'postal_code', label: '郵便番号', required: false },
    { key: 'prefecture', label: '都道府県', required: false },
    { key: 'city', label: '市区町村', required: false },
    { key: 'street', label: '番地・建物名', required: false },
    { key: 'address', label: '住所', required: false },
    { key: 'sex', label: '性別', required: false },
];

const ADDRESS_KEYS = ['postal_code', 'prefecture', 'city', 'street', 'address'];

function resolveFields(memberData: MemberPayload | null | undefined): ProfileFieldSetting[] {
    const root = (memberData && memberData.member) ? (memberData.member as MemberPayload) : memberData;
    const fromTop = memberData?.profile_field_settings;
    if (Array.isArray(fromTop) && fromTop.length > 0) {
        return fromTop as ProfileFieldSetting[];
    }
    const fromRoot = root?.profile_field_settings;
    if (Array.isArray(fromRoot) && fromRoot.length > 0) {
        return fromRoot as ProfileFieldSetting[];
    }
    return DEFAULT_FIELDS;
}

function resolveProfile(
    memberData: MemberPayload | null | undefined,
    isRegister: boolean,
    initialProfile?: Record<string, unknown> | null,
): Record<string, unknown> {
    const root = (memberData && memberData.member) ? (memberData.member as MemberPayload) : memberData;
    let profile: Record<string, unknown> = {};
    if (memberData?.profile !== null && memberData?.profile !== undefined && typeof memberData.profile === 'object') {
        profile = { ...memberData.profile };
    } else if (root?.profile !== null && root?.profile !== undefined && typeof root.profile === 'object') {
        profile = { ...root.profile };
    }
    if (isRegister && initialProfile !== null && initialProfile !== undefined) {
        profile = { ...profile, ...initialProfile };
    }
    return profile;
}

function fieldId(key: string): string {
    return `profileEdit_${key.replace(/[^a-z0-9_]/gi, '_')}`;
}

function splitName(value: unknown): { last: string; first: string } {
    if (value == null || String(value).trim() === '') {
        return { last: '', first: '' };
    }
    const parts = String(value).trim().split(/\s+/);
    return { last: parts[0] || '', first: parts.slice(1).join(' ') || '' };
}

function RequiredMark({ required }: { required?: boolean }): JSX.Element | null {
    if (required !== true) {
        return null;
    }
    return (
        <>
            {' '}
            <span className="required">*</span>
        </>
    );
}

function FieldHint({ description }: { description?: string }): JSX.Element | null {
    if (description === undefined || description === '') {
        return null;
    }
    return <p className="member-form-field-hint">{description}</p>;
}

function ProfileFieldRows({
    fields,
    profile,
    birthdayFallback,
}: {
    fields: ProfileFieldSetting[];
    profile: Record<string, unknown>;
    birthdayFallback: string;
}): JSX.Element[] {
    const nameParts = splitName(profile.name);
    const kanaParts = splitName(profile.kana);

    const rowNodes: Array<{ key: string; isAddress: boolean; node: JSX.Element }> = [];

    fields.forEach((f) => {
        const key = f.key;
        const label = f.label || key;
        const required = f.required === true;
        const val = profile[key] != null ? String(profile[key]) : '';
        const inputType = f.input_type || 'text';
        const values = Array.isArray(f.values) ? f.values.map((v) => String(v)) : [];
        const id = fieldId(key);
        const isAddress = ADDRESS_KEYS.includes(key);

        if (key === 'name') {
            rowNodes.push({
                key: 'name',
                isAddress: false,
                node: (
                    <div className="member-form-name-row" key="name">
                        <div className="member-form-group">
                            <label htmlFor="profileEdit_name_last">
                                姓
                                <RequiredMark required={required} />
                            </label>
                            <input
                                type="text"
                                id="profileEdit_name_last"
                                name="name_last"
                                defaultValue={nameParts.last}
                                maxLength={85}
                                placeholder="例: 山田"
                                autoComplete="family-name"
                                required={required}
                            />
                        </div>
                        <div className="member-form-group">
                            <label htmlFor="profileEdit_name_first">
                                名
                                <RequiredMark required={required} />
                            </label>
                            <input
                                type="text"
                                id="profileEdit_name_first"
                                name="name_first"
                                defaultValue={nameParts.first}
                                maxLength={85}
                                placeholder="例: 太郎"
                                autoComplete="given-name"
                                required={required}
                            />
                        </div>
                    </div>
                ),
            });
            return;
        }

        if (key === 'kana') {
            rowNodes.push({
                key: 'kana',
                isAddress: false,
                node: (
                    <div className="member-form-name-row" key="kana">
                        <div className="member-form-group">
                            <label htmlFor="profileEdit_kana_last">
                                フリガナ（姓）
                                <RequiredMark required={required} />
                            </label>
                            <input
                                type="text"
                                id="profileEdit_kana_last"
                                name="kana_last"
                                defaultValue={kanaParts.last}
                                maxLength={85}
                                placeholder="例: ヤマダ"
                                inputMode="text"
                                required={required}
                            />
                        </div>
                        <div className="member-form-group">
                            <label htmlFor="profileEdit_kana_first">
                                フリガナ（名）
                                <RequiredMark required={required} />
                            </label>
                            <input
                                type="text"
                                id="profileEdit_kana_first"
                                name="kana_first"
                                defaultValue={kanaParts.first}
                                maxLength={85}
                                placeholder="例: タロウ"
                                inputMode="text"
                                required={required}
                            />
                        </div>
                    </div>
                ),
            });
            return;
        }

        if (key === 'sex') {
            const checked0 = !val || val === '0';
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <fieldset className="member-form-group member-form-group--choice" key={key}>
                        <legend className="member-form-group-label">
                            {label}
                            <RequiredMark required={required} />
                        </legend>
                        <div className="member-form-segment member-form-segment--cols-3" role="radiogroup" aria-label={label}>
                            <label className="member-form-segment-btn">
                                <input type="radio" id={`${id}_1`} name="sex" value="1" defaultChecked={val === '1'} required={required} />
                                <span className="member-form-segment-btn-text">男性</span>
                            </label>
                            <label className="member-form-segment-btn">
                                <input type="radio" id={`${id}_2`} name="sex" value="2" defaultChecked={val === '2'} required={required} />
                                <span className="member-form-segment-btn-text">女性</span>
                            </label>
                            <label className="member-form-segment-btn">
                                <input type="radio" id={`${id}_0`} name="sex" value="" defaultChecked={checked0} />
                                <span className="member-form-segment-btn-text">未選択</span>
                            </label>
                        </div>
                    </fieldset>
                ),
            });
            return;
        }

        if (key === 'mail_receive_flag') {
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <fieldset className="member-form-group member-form-group--choice" key={key}>
                        <legend className="member-form-group-label">
                            {label}
                            <RequiredMark required={required} />
                        </legend>
                        <div className="member-form-segment member-form-segment--cols-2" role="radiogroup" aria-label={label}>
                            <label className="member-form-segment-btn">
                                <input type="radio" id={`${id}_1`} name="mail_receive_flag" value="1" defaultChecked={val === '1'} required={required} />
                                <span className="member-form-segment-btn-text">許可する</span>
                            </label>
                            <label className="member-form-segment-btn">
                                <input type="radio" id={`${id}_0`} name="mail_receive_flag" value="0" defaultChecked={val === '0'} required={required} />
                                <span className="member-form-segment-btn-text">許可しない</span>
                            </label>
                        </div>
                    </fieldset>
                ),
            });
            return;
        }

        if (key === 'notification_preference') {
            const preference = val || 'all';
            const options = [
                { value: 'all', label: 'すべて受け取る', note: '新着情報とクーポン情報' },
                { value: 'coupon_only', label: 'クーポン情報のみ', note: 'クーポンが付く配信だけ' },
                { value: 'none', label: 'すべて受け取らない', note: 'LINE・Push通知を送信しません' },
            ];
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <fieldset className="member-form-group member-form-group--choice" key={key}>
                        <legend className="member-form-group-label">
                            {label}
                            <RequiredMark required={required} />
                        </legend>
                        <div className="member-form-notification-options" role="radiogroup" aria-label={label}>
                            {options.map((option) => (
                                <label className="member-form-notification-option" key={option.value}>
                                    <input
                                        type="radio"
                                        name="notification_preference"
                                        value={option.value}
                                        defaultChecked={preference === option.value}
                                        required
                                    />
                                    <span><strong>{option.label}</strong><small>{option.note}</small></span>
                                </label>
                            ))}
                        </div>
                        <FieldHint description={f.description} />
                    </fieldset>
                ),
            });
            return;
        }

        if (inputType === 'radio' && values.length > 0) {
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <fieldset className="member-form-group member-form-group--choice" key={key}>
                        <legend className="member-form-group-label">
                            {label}
                            <RequiredMark required={required} />
                        </legend>
                        <div className="member-form-segment" role="radiogroup" aria-label={label}>
                            {values.map((optionVal, optionIndex) => (
                                <label className="member-form-segment-btn" key={`${key}_${optionIndex}`}>
                                    <input
                                        type="radio"
                                        id={`${id}_${optionIndex}`}
                                        name={key}
                                        value={optionVal}
                                        defaultChecked={val === optionVal}
                                        required={required}
                                    />
                                    <span className="member-form-segment-btn-text">{optionVal}</span>
                                </label>
                            ))}
                        </div>
                        <FieldHint description={f.description} />
                    </fieldset>
                ),
            });
            return;
        }

        if (inputType === 'selectbox' && values.length > 0) {
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <div className="member-form-group" key={key}>
                        <label htmlFor={id}>
                            {label}
                            <RequiredMark required={required} />
                        </label>
                        <select id={id} name={key} defaultValue={val} required={required}>
                            <option value="">未選択</option>
                            {values.map((optionVal) => (
                                <option key={optionVal} value={optionVal}>{optionVal}</option>
                            ))}
                        </select>
                        <FieldHint description={f.description} />
                    </div>
                ),
            });
            return;
        }

        if (inputType === 'checkbox' && values.length > 0) {
            let checkedList: string[] = [];
            if (Array.isArray(profile[key])) {
                checkedList = (profile[key] as unknown[]).map((v) => String(v));
            } else if (val) {
                checkedList = [val];
            }
            rowNodes.push({
                key,
                isAddress: false,
                node: (
                    <fieldset className="member-form-group member-form-group--choice" key={key}>
                        <legend className="member-form-group-label">
                            {label}
                            <RequiredMark required={required} />
                        </legend>
                        <div className="member-form-checkbox-list">
                            {values.map((optionVal, optionIndex) => (
                                <label className="member-form-checkbox" key={`${key}_${optionIndex}`}>
                                    <input
                                        type="checkbox"
                                        id={`${id}_${optionIndex}`}
                                        name={`${key}[]`}
                                        value={optionVal}
                                        defaultChecked={checkedList.indexOf(optionVal) !== -1}
                                    />
                                    <span>{optionVal}</span>
                                </label>
                            ))}
                        </div>
                        <FieldHint description={f.description} />
                    </fieldset>
                ),
            });
            return;
        }

        if (inputType === 'textarea' || key === 'address') {
            const maxLen = key === 'address' ? 85 : 500;
            rowNodes.push({
                key,
                isAddress,
                node: (
                    <div className="member-form-group" key={key}>
                        <label htmlFor={id}>
                            {label}
                            <RequiredMark required={required} />
                        </label>
                        <textarea id={id} name={key} maxLength={maxLen} rows={3} defaultValue={val} required={required} />
                        <FieldHint description={f.description} />
                    </div>
                ),
            });
            return;
        }

        let input: JSX.Element;
        if (key === 'birthday') {
            input = (
                <input
                    type="date"
                    id={id}
                    name={key}
                    defaultValue={val || birthdayFallback}
                    required={required}
                />
            );
        } else if (key === 'email') {
            input = (
                <input type="email" id={id} name={key} defaultValue={val} maxLength={255} required={required} />
            );
        } else if (key === 'postal_code') {
            input = (
                <div className="member-form-postal-row">
                    <input
                        type="text"
                        id={id}
                        name={key}
                        defaultValue={val}
                        placeholder="例: 100-0001"
                        maxLength={8}
                        inputMode="numeric"
                        autoComplete="postal-code"
                        required={required}
                    />
                    <button type="button" className="member-btn member-btn--secondary postal-code-lookup-btn" data-action="postal-code-lookup">
                        <i className="fas fa-search-location" />
                        {' '}
                        住所を自動入力
                    </button>
                </div>
            );
        } else if (key === 'phone') {
            input = (
                <input type="tel" id={id} name={key} defaultValue={val} maxLength={20} required={required} />
            );
        } else {
            input = (
                <input type="text" id={id} name={key} defaultValue={val} maxLength={255} required={required} />
            );
        }

        rowNodes.push({
            key,
            isAddress,
            node: (
                <div className="member-form-group" key={key}>
                    <label htmlFor={id}>
                        {label}
                        <RequiredMark required={required} />
                    </label>
                    {input}
                    <FieldHint description={f.description} />
                </div>
            ),
        });
    });

    const grouped: JSX.Element[] = [];
    let addressChunk: JSX.Element[] = [];
    const flushAddress = (): void => {
        if (addressChunk.length === 0) {
            return;
        }
        const hasPostal = fields.some((field) => field.key === 'postal_code');
        grouped.push(
            <section className="member-form-section member-form-section--address" key={`address-section-${grouped.length}`}>
                <h4 className="member-form-section-title">住所</h4>
                {hasPostal ? (
                    <p className="member-form-section-hint">郵便番号を入力して「住所を自動入力」を押すと住所が入力されます</p>
                ) : null}
                {addressChunk}
            </section>,
        );
        addressChunk = [];
    };

    rowNodes.forEach((row) => {
        if (row.isAddress) {
            addressChunk.push(row.node);
            return;
        }
        flushAddress();
        grouped.push(row.node);
    });
    flushAddress();
    return grouped;
}

export type MemberProfileFormProps = {
    mode: 'register' | 'edit';
    forced?: boolean;
    fullScreen?: boolean;
    formRef?: React.RefObject<HTMLFormElement | null>;
    containerRef?: React.RefObject<HTMLDivElement | null>;
    memberData?: MemberPayload | null;
    initialProfile?: Record<string, unknown> | null;
    onSubmit: (event: React.FormEvent<HTMLFormElement>) => void;
    onCancel: () => void;
};

export function MemberProfileForm({
    mode,
    forced = false,
    fullScreen = true,
    formRef,
    containerRef,
    memberData: memberDataProp,
    initialProfile,
    onSubmit,
    onCancel,
}: MemberProfileFormProps): JSX.Element {
    const mm = getMemberManager();
    const memberData = memberDataProp ?? mm?.memberData ?? null;
    const isRegister = mode === 'register';
    const fields = useMemo(() => resolveFields(memberData), [memberData]);
    const profile = useMemo(
        () => resolveProfile(memberData, isRegister, initialProfile),
        [memberData, isRegister, initialProfile],
    );

    const member = memberData?.member as { birthday?: string } | null | undefined;
    const birthdayFallback = member?.birthday != null
        ? String(member.birthday).split('T')[0]
        : '';

    const hideCancel = forced === true || (isRegister && mm?._forcedRegister === true);
    const title = isRegister ? '会員登録' : 'プロフィール編集';
    const formId = isRegister ? 'registerForm' : 'profileEditForm';
    const submitLabel = isRegister ? '登録する' : '保存';
    const titleIcon = isRegister ? 'fa-user-plus' : 'fa-user-edit';

    const actions = (
        <div className="member-form-actions">
            {hideCancel ? null : (
                <button type="button" className="member-btn member-btn--secondary" onClick={onCancel}>
                    キャンセル
                </button>
            )}
            <button type="submit" className="member-btn member-btn--primary" form={fullScreen ? formId : undefined}>
                <i className="fas fa-check" />
                {' '}
                {submitLabel}
            </button>
        </div>
    );

    const formInner = (
        <div className="member-modal-inner">
            <h3 className="member-modal-title">
                <i className={`fas ${titleIcon}`} />
                {' '}
                {title}
            </h3>
            <form id={formId} className="member-form" ref={formRef} onSubmit={onSubmit}>
                <ProfileFieldRows fields={fields} profile={profile} birthdayFallback={birthdayFallback} />
                {fullScreen ? null : actions}
            </form>
        </div>
    );

    if (!fullScreen) {
        return (
            <div ref={containerRef} className="member-profile-form-host" data-react-owned="1">
                {formInner}
            </div>
        );
    }

    return (
        <div ref={containerRef} className="member-register-fullscreen" data-react-owned="1">
            <div className="member-register-fullscreen-header">
                <h2 className="member-register-fullscreen-title">{title}</h2>
            </div>
            <div className="member-register-fullscreen-body">{formInner}</div>
            <div className="member-register-fullscreen-footer">{actions}</div>
        </div>
    );
}
