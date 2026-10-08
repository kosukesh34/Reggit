export type PostalCodeValidation = {
    digits: string;
    errorMessage: string | null;
};

export type PostalAddress = {
    fullAddress: string;
    prefecture: string;
    city: string;
    street: string;
};

export function validatePostalCode(value: unknown): PostalCodeValidation {
    const digits = String(value ?? '').normalize('NFKC').trim().replace(/[‐－−ー]/g, '-').replace(/\D/g, '');
    if (digits.length < 3) {
        return { digits, errorMessage: '郵便番号を3桁以上入力してください。' };
    }
    if (digits.length !== 7) {
        return { digits, errorMessage: '郵便番号は7桁で入力してください。' };
    }
    return { digits, errorMessage: null };
}

export function resolvePostalAddress(candidate: unknown): PostalAddress | null {
    if (!candidate || typeof candidate !== 'object') return null;
    const row = candidate as Record<string, unknown>;
    const fullAddress = String(row.full_address ?? row.fullAddress ?? '').trim();
    if (!fullAddress) return null;

    return {
        fullAddress,
        prefecture: String(row.pref_name ?? row.prefName ?? ''),
        city: String(row.city_name ?? row.cityName ?? ''),
        street: [row.town_name ?? row.townName, row.block_name ?? row.blockName]
            .filter(Boolean)
            .map(String)
            .join(''),
    };
}
