<?php

namespace App\Services\Smaregi;

/**
 * スマレジ pos/customers PATCH / POST 用のリクエストボディを組み立てる。
 * 空文字や不正なメールアドレスを送ると API が 400 になるため、送信前に正規化する。
 */
class SmaregiCustomerUpdatePayloadBuilder
{
    /**
     * @param  array<string, mixed>  $data  name / kana / email / phone / 住所・生年月日等
     * @return array<string, string>
     */
    public function buildFromProfileData(array $data): array
    {
        $body = [];

        $name = trim((string) ($data['name'] ?? ''));
        if ($name !== '') {
            $parts = preg_split('/\s+/u', $name, 2);
            $body['lastName'] = $parts[0] ?? $name;
            $body['firstName'] = $parts[1] ?? '';
        }

        $kana = trim((string) ($data['kana'] ?? ''));
        if ($kana !== '') {
            $kanaParts = preg_split('/\s+/u', $kana, 2);
            $body['lastKana'] = $this->normalizeSmaregiKana($kanaParts[0] ?? $kana);
            $body['firstKana'] = $this->normalizeSmaregiKana($kanaParts[1] ?? '');
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) ($data['email'] ?? ''));
            if ($email !== '' && strtolower($email) !== 'null' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $body['mailAddress'] = $email;
            }
        }

        if (array_key_exists('phone', $data)) {
            $phone = trim((string) ($data['phone'] ?? ''));
            if ($phone !== '' && strtolower($phone) !== 'null') {
                $body['phoneNumber'] = $phone;
            }
        }

        if (array_key_exists('postal_code', $data)) {
            $postCode = trim((string) ($data['postal_code'] ?? ''));
            if ($postCode !== '') {
                $body['postCode'] = $postCode;
            }
        }

        $address = $this->composeAddress($data);
        if ($address !== '') {
            $body['address'] = mb_substr($address, 0, 85);
        }

        if (array_key_exists('birthday', $data)) {
            $birthday = $data['birthday'];
            if ($birthday instanceof \DateTimeInterface) {
                $body['birthDate'] = $birthday->format('Y-m-d');
            } elseif (is_string($birthday) && trim($birthday) !== '') {
                $body['birthDate'] = trim($birthday);
            }
        }

        if (array_key_exists('sex', $data)) {
            $sex = trim((string) ($data['sex'] ?? ''));
            if (in_array($sex, ['0', '1', '2'], true)) {
                $body['sex'] = $sex;
            }
        }

        if (array_key_exists('mail_receive_flag', $data)) {
            $flag = trim((string) ($data['mail_receive_flag'] ?? ''));
            if (in_array($flag, ['0', '1'], true)) {
                $body['mailReceiveFlag'] = $flag;
            }
        }

        return $body;
    }

    /**
     * 管理画面の会員編集用（ユーザー + 会員フィールド）。
     *
     * @param  array<string, mixed>  $userFields
     * @param  array<string, mixed>  $memberFields
     * @return array<string, string>
     */
    public function buildFromAdminMemberData(array $userFields, array $memberFields = []): array
    {
        $merged = array_merge($userFields, $memberFields);
        $body = $this->buildFromProfileData($merged);

        if (array_key_exists('member_number', $memberFields)) {
            $code = trim((string) ($memberFields['member_number'] ?? ''));
            if ($code !== '') {
                $body['customerCode'] = $code;
            }
        }

        return $body;
    }

    /**
     * 会員新規登録時用。氏名・カナが空のときスマレジ必須対策のプレースホルダを補う。
     *
     * @param  array<string, string>  $body
     * @return array<string, string>
     */
    public function withRegisterNamePlaceholders(array $body): array
    {
        if (! isset($body['lastName']) || trim((string) $body['lastName']) === '') {
            $body['lastName'] = '—';
        }
        if (! isset($body['firstName']) || trim((string) $body['firstName']) === '') {
            $body['firstName'] = '—';
        }

        $body['lastKana'] = $this->normalizeSmaregiKana((string) ($body['lastKana'] ?? ''));
        $body['firstKana'] = $this->normalizeSmaregiKana((string) ($body['firstKana'] ?? ''));

        return $body;
    }

    /**
     * 都道府県・市区町村・番地と自由入力住所からスマレジ address を組み立てる。
     *
     * @param  array<string, mixed>  $data
     */
    public function composeAddress(array $data): string
    {
        $address = trim((string) ($data['address'] ?? ''));
        if ($address !== '') {
            return $address;
        }

        return trim(implode('', array_filter([
            trim((string) ($data['prefecture'] ?? '')),
            trim((string) ($data['city'] ?? '')),
            trim((string) ($data['street'] ?? '')),
        ], static fn (string $part): bool => $part !== '')));
    }

    private function normalizeSmaregiKana(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'ー';
        }

        $katakana = mb_convert_kana($value, 'CKV', 'UTF-8');
        if (preg_match('/^[\x{30A1}-\x{30F6}\x{30FC}\x{30FB}ー]+$/u', $katakana) === 1) {
            return $katakana;
        }

        return 'ー';
    }
}
