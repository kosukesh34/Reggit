export interface ProfileFieldDefinition {
    key?: unknown;
    input_type?: unknown;
}

export interface ProfileFormValueReader {
    value(name: string): string | undefined;
    checkedValue(name: string): string | undefined;
    checkedValues(name: string): string[];
}

export function buildProfilePayload(
    fields: ProfileFieldDefinition[],
    values: ProfileFormValueReader,
): Record<string, string | string[] | null> {
    const payload: Record<string, string | string[] | null> = {};

    for (const field of Array.isArray(fields) ? fields : []) {
        const key = field?.key == null ? '' : String(field.key);
        if (key === '') continue;

        const inputType = typeof field.input_type === 'string' ? field.input_type : 'text';
        if (key === 'name' || key === 'kana') {
            payload[key] = joinNameParts(values.value(`${key}_last`), values.value(`${key}_first`));
            continue;
        }
        if (inputType === 'checkbox') {
            payload[key] = values.checkedValues(`${key}[]`).map(trim).filter(Boolean);
            continue;
        }
        if (isChoiceField(key, inputType)) {
            const value = trim(values.checkedValue(key) ?? values.value(key));
            if (key === 'mail_receive_flag' || key === 'notification_preference') {
                if (value !== undefined) payload[key] = value;
            } else if (value !== undefined || key === 'sex') {
                payload[key] = value || null;
            }
            continue;
        }

        const value = trim(values.value(key));
        if (value !== undefined) {
            payload[key] = value || null;
        }
    }

    return payload;
}

function joinNameParts(last: string | undefined, first: string | undefined): string | null {
    return [trim(last), trim(first)].filter(Boolean).join(' ') || null;
}

function isChoiceField(key: string, inputType: string): boolean {
    return inputType === 'radio'
        || inputType === 'selectbox'
        || key === 'sex'
        || key === 'mail_receive_flag'
        || key === 'notification_preference';
}

function trim(value: string | undefined): string | undefined {
    return value === undefined ? undefined : value.trim();
}
