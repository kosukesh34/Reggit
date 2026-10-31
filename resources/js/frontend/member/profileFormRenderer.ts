export type HtmlEscaper = (value: string) => string;

export type ProfileField = {
    key?: unknown;
    label?: unknown;
    required?: unknown;
    input_type?: unknown;
    values?: unknown;
    options?: Array<{ value: string; label: string }>;
    description?: unknown;
};

export type ProfileFormOptions = {
    isRegister?: boolean;
    initialProfile?: Record<string, unknown>;
    fullScreen?: boolean;
    forced?: boolean;
    isNativeApp?: boolean;
};

const DEFAULT_FIELDS: ProfileField[] = [
    { key: 'name', label: 'お名前', required: true },
    { key: 'kana', label: 'お名前（カナ）' },
    { key: 'phone', label: '電話番号' },
    { key: 'email', label: 'メールアドレス' },
    { key: 'postal_code', label: '郵便番号' },
    { key: 'address', label: '住所' },
    { key: 'birthday', label: '誕生日' },
    { key: 'sex', label: '性別' },
];

export function renderProfileForm(
    memberData: Record<string, any> | null,
    member: Record<string, any> | null,
    options: ProfileFormOptions,
    escapeHtml: HtmlEscaper,
): string {
    const root = memberData?.member ?? memberData;
    const fields = normalizeFields(memberData?.profile_field_settings ?? root?.profile_field_settings);
    let profile = normalizeRecord(memberData?.profile ?? root?.profile);
    if (options.isRegister && options.initialProfile) {
        profile = { ...profile, ...options.initialProfile };
    }

    const birthday = member?.birthday ? String(member.birthday).split('T')[0] : stringValue(profile.birthday);
    const [nameLast, ...nameFirstParts] = splitName(profile.name);
    const [kanaLast, ...kanaFirstParts] = splitName(profile.kana);
    const values = {
        nameLast,
        nameFirst: nameFirstParts.join(' '),
        kanaLast,
        kanaFirst: kanaFirstParts.join(' '),
        birthday,
    };
    const rows = fields.map((field) => renderField(field, profile, values, options, escapeHtml));
    const groupedRows = groupAddressRows(rows, fields);
    return renderFrame(groupedRows, options, escapeHtml);
}

function renderField(field: ProfileField, profile: Record<string, unknown>, names: Record<string, string>, options: ProfileFormOptions, escapeHtml: HtmlEscaper): string {
    const key = stringValue(field.key);
    const label = stringValue(field.label) || key;
    const required = field.required ? ' required' : '';
    const requiredLabel = field.required ? ' <span class="required">*</span>' : '';
    const value = stringValue(profile[key]);
    const inputType = stringValue(field.input_type) || 'text';
    const choices = Array.isArray(field.values) ? field.values.map(String) : [];
    const description = field.description ? `<p class="member-form-field-hint">${escapeHtml(String(field.description))}</p>` : '';

    if (key === 'name' || key === 'kana') {
        const isKana = key === 'kana';
        const last = isKana ? names.kanaLast : names.nameLast;
        const first = isKana ? names.kanaFirst : names.nameFirst;
        const lastLabel = isKana ? 'フリガナ（姓）' : '姓';
        const firstLabel = isKana ? 'フリガナ（名）' : '名';
        const lastPlaceholder = isKana ? '例: ヤマダ' : '例: 山田';
        const firstPlaceholder = isKana ? '例: タロウ' : '例: 太郎';
        const extra = isKana ? ' inputmode="katakana"' : ' autocomplete="family-name"';
        const firstExtra = isKana ? ' inputmode="katakana"' : ' autocomplete="given-name"';
        return `<div class="member-form-name-row"><div class="member-form-group"><label for="profileEdit_${key}_last">${lastLabel}${requiredLabel}</label><input type="text" id="profileEdit_${key}_last" name="${key}_last" value="${escapeHtml(last)}" maxlength="85" placeholder="${lastPlaceholder}"${extra}${required}></div><div class="member-form-group"><label for="profileEdit_${key}_first">${firstLabel}${requiredLabel}</label><input type="text" id="profileEdit_${key}_first" name="${key}_first" value="${escapeHtml(first)}" maxlength="85" placeholder="${firstPlaceholder}"${firstExtra}${required}></div></div>`;
    }

    const id = `profileEdit_${key.replace(/[^a-z0-9_]/gi, '_')}`;
    const labelHtml = `<label for="${id}">${escapeHtml(label)}${requiredLabel}</label>`;
    if (key === 'sex') {
        return group(labelHtml, `<select id="${id}" name="sex"${required}><option value=""${!value || value === '0' ? ' selected' : ''}>未選択</option><option value="1"${value === '1' ? ' selected' : ''}>男性</option><option value="2"${value === '2' ? ' selected' : ''}>女性</option></select>`);
    }
    if (key === 'mail_receive_flag') {
        return group(labelHtml, `<select id="${id}" name="mail_receive_flag"${required}><option value="">未選択</option><option value="1"${value === '1' ? ' selected' : ''}>許可する</option><option value="0"${value === '0' ? ' selected' : ''}>許可しない</option></select>`);
    }
    if (key === 'customer_store_id') {
        const choices = field.options ?? [];
        const html = choices.map((option) => `<option value="${escapeHtml(option.value)}"${value === option.value ? ' selected' : ''}>${escapeHtml(option.label)}</option>`).join('');
        return group(labelHtml, `<select id="${id}" name="customer_store_id"${required}><option value="">未選択</option>${html}</select>${description}`);
    }
    if (key === 'notification_preference') {
        const selected = value || 'all';
        const choiceHtml = [['all', 'すべて受け取る'], ['coupon_only', 'クーポン情報のみ'], ['none', '受け取らない']]
            .map(([optionValue, text]) => `<option value="${optionValue}"${selected === optionValue ? ' selected' : ''}>${text}</option>`).join('');
        const channel = options.isNativeApp ? 'Push通知' : 'LINE通知';
        const notificationLabel = `<label for="${id}">${escapeHtml(label)} <span class="required">*</span></label>`;
        return group(notificationLabel, `<select id="${id}" name="notification_preference" required>${choiceHtml}</select><p class="member-form-field-hint">${channel}の受け取り範囲を選べます</p>`);
    }
    if ((inputType === 'radio' || inputType === 'selectbox') && choices.length) {
        const choiceHtml = ['<option value="">未選択</option>', ...choices.map((choice) => `<option value="${escapeHtml(choice)}"${value === choice ? ' selected' : ''}>${escapeHtml(choice)}</option>`)].join('');
        return group(labelHtml, `<select id="${id}" name="${escapeHtml(key)}"${required}>${choiceHtml}</select>${description}`);
    }
    if (inputType === 'checkbox' && choices.length) {
        const checked = Array.isArray(profile[key]) ? profile[key].map(String) : value ? [value] : [];
        const choiceHtml = choices.map((choice, index) => `<label class="member-form-checkbox"><input type="checkbox" id="${id}_${index}" name="${escapeHtml(key)}[]" value="${escapeHtml(choice)}"${checked.includes(choice) ? ' checked' : ''}><span>${escapeHtml(choice)}</span></label>`).join('');
        return `<fieldset class="member-form-group member-form-group--choice"><legend class="member-form-group-label">${escapeHtml(label)}${requiredLabel}</legend><div class="member-form-checkbox-list">${choiceHtml}</div>${description}</fieldset>`;
    }

    let input: string;
    if (inputType === 'textarea' || key === 'address') {
        input = `<textarea id="${id}" name="${escapeHtml(key)}" maxlength="${key === 'address' ? 85 : 500}" rows="3"${required}>${escapeHtml(value)}</textarea>`;
    } else if (key === 'birthday') {
        input = `<input type="date" id="${id}" name="${escapeHtml(key)}" value="${escapeHtml(value || names.birthday)}"${required}>`;
    } else if (key === 'email') {
        input = `<input type="email" id="${id}" name="${escapeHtml(key)}" value="${escapeHtml(value)}" maxlength="255"${required}>`;
    } else if (key === 'postal_code') {
        input = `<div class="member-form-postal-row"><input type="text" id="${id}" name="${escapeHtml(key)}" value="${escapeHtml(value)}" placeholder="例: 100-0001" maxlength="8" inputmode="numeric" autocomplete="postal-code"${required}><button type="button" class="member-btn member-btn--secondary postal-code-lookup-btn" data-action="postal-code-lookup"><i class="fas fa-search-location"></i> 住所を自動入力</button></div>`;
    } else {
        const type = key === 'phone' ? 'tel' : 'text';
        const maxLength = key === 'phone' ? 20 : 255;
        input = `<input type="${type}" id="${id}" name="${escapeHtml(key)}" value="${escapeHtml(value)}" maxlength="${maxLength}"${required}>`;
    }
    return group(labelHtml, input + description);
}

function group(label: string, input: string): string {
    return `<div class="member-form-group">${label}${input}</div>`;
}

function groupAddressRows(rows: string[], fields: ProfileField[]): string[] {
    const grouped: string[] = [];
    let addressRows: string[] = [];
    const flush = () => {
        if (!addressRows.length) return;
        const hint = fields.some((field) => field.key === 'postal_code') ? '<p class="member-form-section-hint">郵便番号を入力して「住所を自動入力」を押すと住所が入力されます</p>' : '';
        grouped.push(`<section class="member-form-section member-form-section--address"><h4 class="member-form-section-title">住所</h4>${hint}${addressRows.join('')}</section>`);
        addressRows = [];
    };
    rows.forEach((row) => {
        if (row.includes('profileEdit_postal_code') || row.includes('profileEdit_address')) addressRows.push(row);
        else { flush(); grouped.push(row); }
    });
    flush();
    return grouped;
}

function renderFrame(rows: string[], options: ProfileFormOptions, escapeHtml: HtmlEscaper): string {
    const register = options.isRegister === true;
    const title = register ? '会員パスの発行' : 'プロフィール編集';
    const formId = register ? 'registerForm' : 'profileEditForm';
    const submitLabel = register ? '登録する' : '保存';
    const cancelCall = options.fullScreen ? 'window.app.memberManager.closeMemberScreen()' : 'window.app.memberManager.closeProfileEdit()';
    const submit = `<button type="submit" class="member-btn member-btn--primary"${options.fullScreen ? ` form="${formId}"` : ''}><i class="fas fa-check"></i> ${escapeHtml(submitLabel)}</button>`;
    const actions = `<div class="member-form-actions">${options.forced ? '' : `<button type="button" class="member-btn member-btn--secondary" onclick="${cancelCall}">キャンセル</button>`}${submit}</div>`;
    const inner = `<div class="member-modal-inner"><h3 class="member-modal-title"><i class="fas ${register ? 'fa-user-plus' : 'fa-user-edit'}"></i> ${escapeHtml(title)}</h3><form id="${formId}" class="member-form">${rows.join('')}${options.fullScreen ? '' : actions}</form></div>`;
    return options.fullScreen ? `<div class="member-register-fullscreen"><div class="member-register-fullscreen-header"><h2 class="member-register-fullscreen-title">${escapeHtml(title)}</h2></div><div class="member-register-fullscreen-body">${inner}</div><div class="member-register-fullscreen-footer">${actions}</div></div>` : inner;
}

function normalizeFields(value: unknown): ProfileField[] {
    return Array.isArray(value) ? value : DEFAULT_FIELDS;
}

function normalizeRecord(value: unknown): Record<string, unknown> {
    return value && typeof value === 'object' ? value as Record<string, unknown> : {};
}

function splitName(value: unknown): string[] {
    const normalized = stringValue(value).trim();
    return normalized ? normalized.split(/\s+/) : ['', ''];
}

function stringValue(value: unknown): string {
    return value == null ? '' : String(value);
}
